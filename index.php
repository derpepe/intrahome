<?php
if (isset($_GET['api']) && $_GET['api'] === 'status') {
    header('Content-Type: application/json');
    $statusOutput = shell_exec(__DIR__ . '/scripts/killswitch.sh status 2>&1');
    $status = trim($statusOutput) === "blocked" ? "OFFLINE" : "ONLINE";
    echo json_encode(['status' => $status]);
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'killswitch', 'weather'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    if ($action === "on") {
        shell_exec(__DIR__ . '/scripts/killswitch.sh on 2>&1');
        $message = "SYSTEM OFFLINE. VERBINDUNG GETRENNT.";
    } elseif ($action === "off") {
        shell_exec(__DIR__ . '/scripts/killswitch.sh off 2>&1');
        $message = "SYSTEM ONLINE. VERBINDUNG WIEDERHERGESTELLT.";
    }
}

// Get global status
$statusOutput = shell_exec(__DIR__ . '/scripts/killswitch.sh status 2>&1');
$status = trim($statusOutput) === "blocked" ? "OFFLINE" : "ONLINE";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INTRAHOME // RAD LAB</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="favicon.svg">
    <link rel="stylesheet" href="fonts/fonts.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $status === 'OFFLINE' ? 'status-offline' : 'status-online' ?>">

<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="?page=dashboard" style="text-decoration: none;">
                <div class="brand-container">
                    <div class="brand">INTRAHOME</div>
                    <div class="brand-sub" style="font-size: 10px; color: var(--neon-cyan); letter-spacing: 2px;">CORE SYSTEM</div>
                </div>
            </a>
            <button class="mobile-menu-toggle" id="menuToggle">[ M E N U ]</button>
        </div>
        
        <nav class="main-nav">
            <a href="?page=dashboard" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">[D]</span> DASHBOARD
            </a>
            <a href="?page=killswitch" class="nav-item <?= $page === 'killswitch' ? 'active' : '' ?>">
                <span class="nav-icon">[K]</span> KILLSWITCH
            </a>
            <a href="?page=weather" class="nav-item <?= $page === 'weather' ? 'active' : '' ?>">
                <span class="nav-icon">[W]</span> WETTERSTATION
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="sys-time" id="clock">00:00:00</div>
            <div class="footer-status" style="font-size: 10px; color: #555; margin-top: 5px;">SYS.VER: 2.0.0</div>
        </div>
    </aside>

    <main class="page-content">
        <div class="grid-overlay"></div>
        <div class="page-inner">
            <header class="page-header">
                <h2 class="page-title"><?= strtoupper($page) ?> // TERMINAL</h2>
            </header>
            
            <div class="component-container">
                <?php include __DIR__ . "/pages/{$page}.php"; ?>
            </div>

            <footer class="footer">
                <div class="bar-chart">
                    <div class="bar bar-1"></div>
                    <div class="bar bar-2"></div>
                    <div class="bar bar-3"></div>
                    <div class="bar bar-4"></div>
                    <div class="bar bar-5"></div>
                </div>
                <div class="bottom-text">LOCAL NET</div>
            </footer>
        </div>
    </main>
</div>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.querySelector('.main-nav');
    
    if(menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('nav-open');
            if(mainNav.classList.contains('nav-open')) {
                menuToggle.innerText = '[ X ]';
                menuToggle.classList.add('active');
            } else {
                menuToggle.innerText = '[ M E N U ]';
                menuToggle.classList.remove('active');
            }
        });
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleTimeString('de-DE', { hour12: false });
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

</body>
</html>