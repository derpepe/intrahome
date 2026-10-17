<?php
/**
 * igelcam.php
 *
 * Leitet auf die aktuelle URL der Igelkamera weiter.
 * Die URL ändert sich und wird extern in /opt/igelcam/current_url.txt gepflegt.
 * Sie wird bei jedem Aufruf frisch gelesen, damit der Link nie veraltet ist.
 *
 * ?format=json liefert {"ok":bool,"url":string|null,"updated":ISO-Zeit|null}
 */

$config_file = __DIR__ . '/config.php';
$config = file_exists($config_file) ? require $config_file : [];
$file = $config['igelcam']['url_file'] ?? '/opt/igelcam/current_url.txt';

$url = null;
$updated = null;
if (is_readable($file)) {
    clearstatcache(true, $file);
    $raw = trim((string)@file_get_contents($file));
    // Erste nicht-leere Zeile nehmen, nur http(s)-URLs akzeptieren
    $line = trim(strtok($raw, "\r\n") ?: '');
    if ($line !== '' && preg_match('#^https?://#i', $line) && filter_var($line, FILTER_VALIDATE_URL)) {
        $url = $line;
        $updated = date('c', filemtime($file));
    }
}

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => $url !== null, 'url' => $url, 'updated' => $updated], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($url !== null) {
    header('Cache-Control: no-store');
    header('Location: ' . $url, true, 302);
    exit;
}

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de"><head><meta charset="UTF-8"><title>IGELCAM // OFFLINE</title>
<link rel="stylesheet" href="fonts/fonts.css"><link rel="stylesheet" href="style.css"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="text-align:center;color:var(--neon-pink);letter-spacing:3px;">
    <h2 class="page-title" style="color:var(--neon-pink);">IGELCAM // KEIN LINK</h2>
    <p style="color:#888;">Keine gültige URL in <?= htmlspecialchars($file, ENT_QUOTES) ?> gefunden.</p>
    <p><a href="./" style="color:var(--neon-cyan);">&lt;&lt; ZURÜCK</a></p>
</div>
</body></html>
