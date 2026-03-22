<?php
header('Content-Type: application/json');

$config_file = __DIR__ . '/../config.php';
if (!file_exists($config_file)) {
    echo json_encode(['error' => 'config.php is missing.']);
    exit;
}

$config = include $config_file;
$ecowitt = $config['ecowitt'] ?? [];

$app_key = $ecowitt['application_key'] ?? '';
$api_key = $ecowitt['api_key'] ?? '';
$mac = $ecowitt['mac_address'] ?? '';

if (empty($app_key) || empty($api_key) || empty($mac)) {
    echo json_encode(['error' => 'API credentials incomplete.']);
    exit;
}

// History from beginning of today
$start = date('Y-m-d 00:00:00');
$end = date('Y-m-d 23:59:59');

$url = sprintf(
    "https://api.ecowitt.net/api/v3/device/history?application_key=%s&api_key=%s&mac=%s&call_back=outdoor.temperature&cycle_type=1hour&start_date=%s&end_date=%s&temp_unitid=1",
    urlencode($app_key),
    urlencode($api_key),
    urlencode($mac),
    urlencode($start),
    urlencode($end)
);

// Cache for 30 minutes to save API requests and load times
$cache_file = __DIR__ . '/../.ecowitt_hist_cache.json';
$cache_time = 1800; // 30 minutes

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    echo file_get_contents($cache_file);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $http_code === 200) {
    file_put_contents($cache_file, $response);
    echo $response;
} else {
    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file);
    } else {
        echo json_encode(['error' => 'Failed to reach EcoWitt History API']);
    }
}
