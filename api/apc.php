<?php
header('Content-Type: application/json');

$config_file = __DIR__ . '/../config.php';
if (!file_exists($config_file)) {
    echo json_encode(['error' => 'config.php is missing.']);
    exit;
}

$config = include $config_file;
$apc = $config['apc'] ?? [];

$host      = $apc['host']      ?? '192.168.2.20';
$community = $apc['community'] ?? 'public';
$timeout   = (int)($apc['snmp_timeout_us'] ?? 1500000); // 1.5s
$retries   = (int)($apc['snmp_retries']    ?? 1);

// Cache (kurz, da lokaler Abruf billig ist – aber Hammer auf NMC vermeiden)
$cache_file = __DIR__ . '/../.apc_cache.json';
$cache_time = (int)($apc['cache_seconds'] ?? 10);

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    echo file_get_contents($cache_file);
    exit;
}

if (!function_exists('snmpget')) {
    echo json_encode(['error' => 'PHP-SNMP-Erweiterung fehlt. Bitte php-snmp installieren.']);
    exit;
}

// PowerNet-MIB OIDs (APC) – siehe powernet-mib
$oids = [
    // Battery
    'battery_capacity'   => '.1.3.6.1.4.1.318.1.1.1.2.2.1.0',   // %
    'battery_temp'       => '.1.3.6.1.4.1.318.1.1.1.2.2.2.0',   // °C
    'battery_runtime'    => '.1.3.6.1.4.1.318.1.1.1.2.2.3.0',   // TimeTicks (1/100s)
    'battery_status'     => '.1.3.6.1.4.1.318.1.1.1.2.1.1.0',   // 1=unknown,2=normal,3=low
    'battery_replace'    => '.1.3.6.1.4.1.318.1.1.1.2.2.4.0',   // 1=noNeed,2=needsReplacement
    'battery_voltage'    => '.1.3.6.1.4.1.318.1.1.1.2.2.8.0',   // V DC
    'last_replace_date'  => '.1.3.6.1.4.1.318.1.1.1.2.1.3.0',   // Datum (String)

    // Input
    'input_voltage'      => '.1.3.6.1.4.1.318.1.1.1.3.2.1.0',   // V
    'input_freq'         => '.1.3.6.1.4.1.318.1.1.1.3.2.4.0',   // Hz
    'input_min'          => '.1.3.6.1.4.1.318.1.1.1.3.2.2.0',   // V (last min)
    'input_max'          => '.1.3.6.1.4.1.318.1.1.1.3.2.3.0',   // V (last max)
    'last_xfer_reason'   => '.1.3.6.1.4.1.318.1.1.1.3.2.5.0',   // INTEGER reason

    // Output
    'output_voltage'     => '.1.3.6.1.4.1.318.1.1.1.4.2.1.0',   // V
    'output_freq'        => '.1.3.6.1.4.1.318.1.1.1.4.2.2.0',   // Hz
    'output_load_pct'    => '.1.3.6.1.4.1.318.1.1.1.4.2.3.0',   // %
    'output_current'     => '.1.3.6.1.4.1.318.1.1.1.4.2.4.0',   // A
    'output_status'      => '.1.3.6.1.4.1.318.1.1.1.4.1.1.0',   // siehe statusMap

    // Identity
    'ups_model'          => '.1.3.6.1.4.1.318.1.1.1.1.1.1.0',
    'ups_serial'         => '.1.3.6.1.4.1.318.1.1.1.1.2.3.0',
    'ups_firmware'       => '.1.3.6.1.4.1.318.1.1.1.1.2.1.0',
];

// snmpget einzeln laufen lassen – fehlende OIDs sollen nicht den ganzen Abruf killen
@snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$raw = [];
$any_ok = false;
foreach ($oids as $key => $oid) {
    $val = @snmpget($host, $community, $oid, $timeout, $retries);
    if ($val !== false) {
        $any_ok = true;
        $raw[$key] = $val;
    } else {
        $raw[$key] = null;
    }
}

if (!$any_ok) {
    $err = error_get_last();
    $payload = json_encode([
        'error' => 'SNMP-Abruf fehlgeschlagen. Host/Community pruefen.',
        'detail' => $err['message'] ?? null,
        'host' => $host,
    ]);
    echo $payload;
    exit;
}

// Helpers
$toFloat = function ($v) {
    if ($v === null || $v === '') return null;
    // Werte können wie "230" oder "230.0 Volts" kommen
    if (preg_match('/-?\d+(?:\.\d+)?/', (string)$v, $m)) return (float)$m[0];
    return null;
};
$toInt = function ($v) {
    if ($v === null || $v === '') return null;
    if (preg_match('/-?\d+/', (string)$v, $m)) return (int)$m[0];
    return null;
};
// TimeTicks -> Sekunden
$ticksToSec = function ($v) {
    if ($v === null) return null;
    if (preg_match('/\((\d+)\)/', (string)$v, $m)) return (int)$m[1] / 100; // (12345) Format
    if (preg_match('/(\d+):(\d+):(\d+)(?:\.(\d+))?/', (string)$v, $m)) {
        return ((int)$m[1]) * 3600 + ((int)$m[2]) * 60 + ((int)$m[3]);
    }
    if (is_numeric($v)) return ((float)$v) / 100.0;
    return null;
};

$statusMap = [
    1 => ['code' => 'UNKNOWN',       'label' => 'UNBEKANNT'],
    2 => ['code' => 'ONLINE',        'label' => 'NETZ­BETRIEB'],
    3 => ['code' => 'ON_BATTERY',    'label' => 'BATTERIE'],
    4 => ['code' => 'ON_SMART_BOOST','label' => 'BOOST'],
    5 => ['code' => 'TIMED_SLEEPING','label' => 'SCHLAFEND'],
    6 => ['code' => 'SOFTWARE_BYPASS','label'=> 'BYPASS (SW)'],
    7 => ['code' => 'OFF',           'label' => 'AUS'],
    8 => ['code' => 'REBOOTING',     'label' => 'NEUSTART'],
    9 => ['code' => 'SWITCHED_BYPASS','label'=> 'BYPASS'],
    10=> ['code' => 'HARDWARE_FAILURE_BYPASS','label'=>'FEHLER (BYPASS)'],
    11=> ['code' => 'SLEEPING_UNTIL_POWER_RETURN','label'=>'WARTET AUF NETZ'],
    12=> ['code' => 'ON_SMART_TRIM', 'label' => 'TRIM'],
];

$batteryStatusMap = [
    1 => ['code' => 'UNKNOWN', 'label' => 'UNBEKANNT'],
    2 => ['code' => 'NORMAL',  'label' => 'NORMAL'],
    3 => ['code' => 'LOW',     'label' => 'SCHWACH'],
];

$replaceMap = [
    1 => ['code' => 'OK',      'label' => 'OK'],
    2 => ['code' => 'REPLACE', 'label' => 'AUSTAUSCH NÖTIG'],
];

$lastXferMap = [
    1 => 'KEIN TRANSFER',
    2 => 'HIGH LINE',
    3 => 'BROWNOUT',
    4 => 'BLACKOUT',
    5 => 'KLEINER MONATSTEST',
    6 => 'GROSSER MONATSTEST',
    7 => 'KLEINER TIEFENTEST',
    8 => 'GROSSER TIEFENTEST',
];

$out_status_int = $toInt($raw['output_status']);
$bat_status_int = $toInt($raw['battery_status']);
$replace_int    = $toInt($raw['battery_replace']);
$xfer_int       = $toInt($raw['last_xfer_reason']);

$runtime_sec = $ticksToSec($raw['battery_runtime']);

$data = [
    'identity' => [
        'model'    => $raw['ups_model'],
        'serial'   => $raw['ups_serial'],
        'firmware' => $raw['ups_firmware'],
    ],
    'status' => [
        'output'  => $statusMap[$out_status_int]  ?? ['code' => 'N/A', 'label' => 'N/A'],
        'battery' => $batteryStatusMap[$bat_status_int] ?? ['code' => 'N/A', 'label' => 'N/A'],
        'replace' => $replaceMap[$replace_int]    ?? ['code' => 'N/A', 'label' => 'N/A'],
        'last_transfer_reason' => $lastXferMap[$xfer_int] ?? null,
    ],
    'input' => [
        'voltage'   => ['value' => $toFloat($raw['input_voltage']), 'unit' => 'V'],
        'frequency' => ['value' => $toFloat($raw['input_freq']),    'unit' => 'Hz'],
        'min'       => ['value' => $toFloat($raw['input_min']),     'unit' => 'V'],
        'max'       => ['value' => $toFloat($raw['input_max']),     'unit' => 'V'],
    ],
    'output' => [
        'voltage'   => ['value' => $toFloat($raw['output_voltage']), 'unit' => 'V'],
        'frequency' => ['value' => $toFloat($raw['output_freq']),    'unit' => 'Hz'],
        'load'      => ['value' => $toFloat($raw['output_load_pct']),'unit' => '%'],
        'current'   => ['value' => $toFloat($raw['output_current']), 'unit' => 'A'],
    ],
    'battery' => [
        'capacity'    => ['value' => $toFloat($raw['battery_capacity']), 'unit' => '%'],
        'temperature' => ['value' => $toFloat($raw['battery_temp']),     'unit' => '°C'],
        'voltage'     => ['value' => $toFloat($raw['battery_voltage']),  'unit' => 'V'],
        'runtime'     => [
            'seconds' => $runtime_sec !== null ? (int)$runtime_sec : null,
            'minutes' => $runtime_sec !== null ? round($runtime_sec / 60, 1) : null,
        ],
        'last_replace_date' => $raw['last_replace_date'],
    ],
    'fetched_at' => date('c'),
];

$payload = json_encode(['code' => 0, 'msg' => 'success', 'data' => $data]);
@file_put_contents($cache_file, $payload);
echo $payload;
