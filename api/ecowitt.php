<?php
header('Content-Type: application/json');

$config_file = __DIR__ . '/../config.php';
if (!file_exists($config_file)) {
    echo json_encode(['error' => 'config.php is missing.']);
    exit;
}

$config = include $config_file;
$ecowitt = $config['ecowitt'] ?? [];

$application_key = $ecowitt['application_key'] ?? '';
$api_key = $ecowitt['api_key'] ?? '';
$mac = $ecowitt['mac_address'] ?? '';

if (empty($application_key) || empty($api_key) || empty($mac)) {
    echo json_encode(['error' => 'API credentials incomplete. Please add Application Key and MAC to config.php.']);
    exit;
}

// Simple cURL fetch
$url = sprintf(
    "https://api.ecowitt.net/api/v3/device/real_time?application_key=%s&api_key=%s&mac=%s&temp_unitid=1&wind_speed_unitid=7&pressure_unitid=3&rainfall_unitid=12&call_back=all",
    urlencode($application_key),
    urlencode($api_key),
    urlencode($mac)
);

// Cache implementation
$cache_file = __DIR__ . '/../.ecowitt_cache.json';
$cache_time = 300; // 5 minutes cache

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    echo file_get_contents($cache_file);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $http_code === 200) {
    file_put_contents($cache_file, $response);
    echo $response;
} else {
    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file); // Serving stale cache
    } else {
        echo json_encode(['error' => 'Failed to reach EcoWitt API']);
    }
}
