<?php
/**
 * api/heating.php
 *
 * Datenabruf von der Carel pCOWeb-Karte (Dimplex / Glen-Dimplex Wärmepumpe).
 * Liest HTML-Seiten der Karte und parst die eingebetteten Live-Werte.
 *
 * Quelle: http://<host>/http/index/j_index.html       (Startseite mit Außentemp und WP-Status)
 *         http://<host>/http/index/j_operatingdata.html  (Tabellen mit allen Betriebswerten)
 *
 * Rückgabe: JSON mit normalisierten Datenpunkten.
 *
 * Cache: kurzes File-Cache (Default 15s), um die Carel-Karte nicht zu fluten.
 */

// Inline-Modus: Wenn diese Datei aus pages/heating.php heraus included wird,
// soll sie die JSON-Ausgabe nur produzieren, aber nicht die Anfrage beenden.
$_HEATING_INLINE = isset($_HEATING_INLINE) ? (bool)$_HEATING_INLINE : false;
if (!$_HEATING_INLINE) {
    @header('Content-Type: application/json; charset=utf-8');
}

$config_file = __DIR__ . '/../config.php';
$config = file_exists($config_file) ? require $config_file : [];
$cfg = $config['heating'] ?? [
    'host'           => '192.168.2.220',
    'timeout'        => 4,
    'cache_seconds'  => 15,
];

$host      = $cfg['host'];
$timeout   = (int)$cfg['timeout'];
$cache_sec = (int)$cfg['cache_seconds'];
$cache_file = __DIR__ . '/.heating_cache.json';

// --- Cache prüfen ---
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_sec)) {
    readfile($cache_file);
    if (!$_HEATING_INLINE) {
        exit;
    }
    return;
}

// --- Hilfsfunktionen ---
function fetch_url(string $url, int $timeout): ?string {
    $ctx = stream_context_create(['http' => [
        'timeout' => $timeout,
        'header'  => "User-Agent: intrahome/1.0\r\n",
        'ignore_errors' => true,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    return $data !== false ? $data : null;
}

function decode_iso(string $s): string {
    // Carel sendet ISO-8859-1, wir wollen UTF-8 für JSON.
    // Nutze mb_convert_encoding wenn verfügbar, sonst iconv, sonst utf8_encode-Äquivalent.
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
    }
    if (function_exists('iconv')) {
        $out = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
        if ($out !== false) return $out;
    }
    // Fallback: manuelle ISO-8859-1 → UTF-8 Konvertierung
    // (äquivalent zum entfernten utf8_encode())
    $out = '';
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = ord($s[$i]);
        if ($c < 0x80) {
            $out .= chr($c);
        } elseif ($c < 0xC0) {
            // 0x80–0xBF in ISO-8859-1 sind Steuerzeichen/Symbole
            $out .= chr(0xC2) . chr($c);
        } else {
            $out .= chr(0xC3) . chr($c - 0x40);
        }
    }
    return $out;
}

function clean_unit(string $u): string {
    $u = html_entity_decode($u, ENT_QUOTES, 'UTF-8');
    $u = str_replace(['&deg;', '&nbsp;'], ['°', ' '], $u);
    return trim(strip_tags($u));
}

/**
 * Parst alle <tr id=...>-Zeilen aus j_operatingdata.html.
 * Liefert Array: [['key' => '...', 'label' => '...', 'value' => float|null, 'unit' => '...'], ...]
 */
function parse_operating_data(string $html): array {
    if (preg_match_all('#<tr id=([a-zA-Z0-9_]+)>(.*?)</tr>#s', $html, $m, PREG_SET_ORDER) === false) {
        return [];
    }
    $rows = [];
    foreach ($m as $row) {
        $key  = $row[1];
        $body = $row[2];
        if (!preg_match('#document\.write\(([A-Za-z_]+)\)#', $body, $lab)) continue;
        if (!preg_match("#<td align=['\"]?right['\"]?>([^<]+)</td>#", $body, $val)) continue;
        $label  = $lab[1];
        $rawVal = trim($val[1]);
        $unit   = '';
        if (preg_match('#<td>([^<]*(?:&deg;|bar|kW|%|h|K\b|V\b|A\b|Hz)[^<]*)</td>#', $body, $u)) {
            $unit = clean_unit($u[1]);
        }
        $rows[] = [
            'key'    => $key,
            'label'  => $label,
            'value'  => is_numeric($rawVal) ? (float)$rawVal : null,
            'raw'    => $rawVal,
            'unit'   => $unit,
        ];
    }
    return $rows;
}

/**
 * Extrahiert Außentemperatur von j_index.html.
 */
function parse_outside_temp(string $html): ?float {
    if (preg_match('#document\.writeln\(Aussentemperatur\);</script>\s*([0-9.,\-]+)\s*&deg;C#', $html, $m)) {
        return (float)str_replace(',', '.', $m[1]);
    }
    return null;
}

/**
 * Extrahiert WP-Status von j_index.html (Variablen z_hswstatusL und z_sp_wertL).
 * z_hswstatusL: 0=Aus, 1=Heizen, 2=?, 3=?, 4=Warmwasser, 5=Kühlen, 10=Abtauen, 11=Durchfluss, 30=Sperre
 */
function parse_wp_status(string $html): array {
    $statusMap = [
        0  => 'Aus',
        1  => 'Heizen',
        2  => 'Heizen',
        3  => 'Heizen',
        4  => 'Warmwasser',
        5  => 'Kühlen',
        10 => 'Abtauen',
        11 => 'Durchflussüberwachung',
        30 => 'WP-Sperre',
    ];
    $sperreMap = [
        6  => 'Einsatzgrenze',
        7  => 'Systemkontrolle',
        9  => 'Pumpenvorlauf',
        10 => 'Mindeststandzeit',
        11 => 'Netzbelastung',
        12 => 'Schaltspielsperre',
        13 => 'Warmwasser-Nacherwärmung',
        14 => 'Regenerativ',
        15 => 'EVU-Sperre',
        16 => 'Sanftanlasser',
    ];

    $statusCode = null;
    $sperreCode = null;
    if (preg_match('#var\s+z_hswstatusL\s*=\s*([0-9.\-]+)#', $html, $m)) {
        $statusCode = (int)floor((float)$m[1]);
    }
    if (preg_match('#var\s+z_sp_wertL\s*=\s*([0-9.\-]+)#', $html, $m)) {
        $sperreCode = (int)floor((float)$m[1] / 10); // wird mit *10 multipliziert in HTML
    }
    // Eigentlich: var z_sp_wertL=0.0*10 → multipliziert mit 10
    if (preg_match('#var\s+z_sp_wertL\s*=\s*([0-9.\-]+)\s*\*\s*10#', $html, $m)) {
        $sperreCode = (int)round((float)$m[1] * 10);
    }

    return [
        'state_code'   => $statusCode,
        'state'        => $statusCode !== null ? ($statusMap[$statusCode] ?? "Unbekannt ($statusCode)") : 'Unbekannt',
        'lock_code'    => $sperreCode,
        'lock_reason'  => ($sperreCode !== null && isset($sperreMap[$sperreCode])) ? $sperreMap[$sperreCode] : null,
    ];
}

// --- Daten abrufen ---
$result = [
    'ok'        => false,
    'timestamp' => date('c'),
    'host'      => $host,
    'outside_temp' => null,
    'wp_status' => null,
    'data'      => [],
    'errors'    => [],
];

$url_index = "http://{$host}/http/index/j_index.html";
$url_op    = "http://{$host}/http/index/j_operatingdata.html";

$html_index = fetch_url($url_index, $timeout);
$html_op    = fetch_url($url_op, $timeout);

if ($html_index === null) {
    $result['errors'][] = "Startseite (j_index.html) nicht erreichbar";
}
if ($html_op === null) {
    $result['errors'][] = "Betriebsdaten (j_operatingdata.html) nicht erreichbar";
}

if ($html_index !== null) {
    $html_index = decode_iso($html_index);
    $result['outside_temp'] = parse_outside_temp($html_index);
    $result['wp_status']    = parse_wp_status($html_index);
}

if ($html_op !== null) {
    $html_op = decode_iso($html_op);
    $rows = parse_operating_data($html_op);

    // Datenpunkte für intrahome zusammenstellen.
    // Pro logischer Größe nehmen wir den ersten Wert > -50 (Sensor verbunden).
    // anz_*_pcol0/pcol1 sind alternative Anzeigevarianten – wir nehmen den ersten gültigen.
    $picks = [
        // logischer Name => Label im HTML, akzeptierte Keys (Reihenfolge = Priorität)
        'vorlauf'         => ['Vorlauftemperatur', ['anz_vl_pcol1', 'anz_vl_pcol0', 'anz_vl_pcol1_1', 'anz_vl_pcol1_2']],
        'ruecklauf_hk1'   => ['HkEins',            ['anz_rl_pcol1', 'anz_rl_pcol0', 'anz_rl_pcol1_1', 'anz_rl_pcol1_2']],
        'hk2_ist'         => ['HkZwei',            ['anz_e_b6_pcl0']],
        'hk2_soll'        => ['HkZwei',            ['anz_p_hk2']],
        'hk3_ist'         => ['HkDrei',            ['anz_e_b8_pcl0']],
        'hk3_soll'        => ['HkDrei',            ['anz_p_hk3']],
        'warmwasser_ist'  => ['Warmwasser',        ['anz_e_b3']],
        'warmwasser_soll' => ['Solltemperatur',    ['anz_ww_soll']],
        'quelle_ein'      => ['Wuelleneintritt',   ['anz_nd_b6_wqe_pcol1']],
        'quelle_aus'      => ['Wuellenaustritt',   ['anz_nd_b7_wqa_pcol1']],
        'niederdruck'     => ['Niederdrucksensor', ['anz_EVD_Sh_Evap_Pres_A_pcol1']],
        'heissgas'        => ['Heissgastemperatur',['anz_wp_rv']],
        'sole'            => ['Sole',              ['anz_e3_b3', 'anz_e4_b2']],
    ];

    // Index für schnellen Lookup
    $byKey = [];
    foreach ($rows as $r) {
        $byKey[$r['key']] = $r;
    }

    foreach ($picks as $logical => [$expectedLabel, $keys]) {
        foreach ($keys as $k) {
            if (isset($byKey[$k])) {
                $r = $byKey[$k];
                // Nur akzeptieren wenn Label passt UND Wert plausibel (-50 .. 300)
                $val = $r['value'];
                if ($r['label'] === $expectedLabel && $val !== null && $val > -50 && $val < 300) {
                    $result['data'][$logical] = [
                        'value' => $val,
                        'unit'  => $r['unit'],
                        'src'   => $k,
                    ];
                    break;
                }
            }
        }
        if (!isset($result['data'][$logical])) {
            $result['data'][$logical] = null;
        }
    }

    $result['raw_rows'] = $rows; // Debug-Hilfe
}

$result['ok'] = empty($result['errors']);

$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Cache schreiben
@file_put_contents($cache_file, $json, LOCK_EX);

echo $json;
if (!$_HEATING_INLINE) {
    exit;
}
