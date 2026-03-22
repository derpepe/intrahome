<?php
// PHP Logik für Systemaufrufe
$message = "";
$status = "Unbekannt";

// Ermitteln des aktuellen Status
$statusOutput = shell_exec(__DIR__ . '/scripts/killswitch.sh status 2>&1');
$status = trim($statusOutput) === "blocked" ? "OFFLINE" : "ONLINE";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {
        $action = $_POST["action"];
        if ($action === "on") {
            // "on" bedeutet "killswitch aktiviert" = Internet blockiert
            shell_exec(__DIR__ . '/scripts/killswitch.sh on 2>&1');
            $message = "SYSTEM OFFLINE. VERBINDUNG GETRENNT.";
            $status = "OFFLINE";
        }
        elseif ($action === "off") {
            // "off" bedeutet "killswitch deaktiviert" = Internet offen
            shell_exec(__DIR__ . '/scripts/killswitch.sh off 2>&1');
            $message = "SYSTEM ONLINE. VERBINDUNG WIEDERHERGESTELLT.";
            $status = "ONLINE";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAD LAB - KILLSWITCH</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="<?= $status === 'OFFLINE' ? 'status-offline' : 'status-online'?>">

    <div class="dashboard">
        <div class="grid-overlay"></div>

        <header class="header">
            <div class="brand">INTRAHOME :: KILL SWITCH</div>
            <div class="sys-time" id="clock">00:00:00</div>
        </header>

        <main class="main-content">
            <div class="status-panel">
                <h2 class="label">NETZWERKSTATUS</h2>
                <div class="status-value glitch" data-text="<?= $status?>">
                    <?= $status?>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="system-message blink">
                <?= htmlspecialchars($message)?>
            </div>
            <?php
else: ?>
            <div class="system-message">SYSTEM BEREIT FÜR EINGABE</div>
            <?php
endif; ?>

            <div class="controls">
                <form method="POST" action="">
                    <?php if ($status === "ONLINE"): ?>
                    <!-- Wenn Online, dann Button um Offline zu gehen (Killswitch ON) -->
                    <input type="hidden" name="action" value="on">
                    <button type="submit" class="btn btn-kill">
                        <span class="btn-text">INTERNET ABSCHALTEN</span>
                        <span class="btn-subtext">>> INITIATE KILLSWITCH</span>
                    </button>
                    <?php
else: ?>
                    <!-- Wenn Offline, dann Button um Online zu gehen (Killswitch OFF) -->
                    <input type="hidden" name="action" value="off">
                    <button type="submit" class="btn btn-restore">
                        <span class="btn-text">INTERNET FREIGEBEN</span>
                        <span class="btn-subtext">>> RESTORE CONNECTION</span>
                    </button>
                    <?php
endif; ?>
                </form>
            </div>

            <div class="telemetry">
                <div class="data-block">
                    <span class="data-label">UPTIME</span>
                    <span class="data-val">99.9%</span>
                </div>
                <div class="data-block">
                    <span class="data-label">PWR</span>
                    <span class="data-val">MAX</span>
                </div>
                <div class="data-block">
                    <span class="data-label">SEC</span>
                    <span class="data-val">
                        <?= $status === 'OFFLINE' ? 'LOCKED' : 'OPEN'?>
                    </span>
                </div>
            </div>

        </main>

        <footer class="footer">
            <div class="bar-chart">
                <div class="bar bar-1"></div>
                <div class="bar bar-2"></div>
                <div class="bar bar-3"></div>
                <div class="bar bar-4"></div>
                <div class="bar bar-5"></div>
            </div>
            <div class="bottom-text">SYS.VER: 1.0.4 // LOCAL NET</div>
        </footer>
    </div>

    <script>
        // Uhrzeit aktualisieren
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('de-DE', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

</body>

</html>