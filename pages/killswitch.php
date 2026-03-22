<?php
// $status and $message are available from index.php
?>
<div class="main-content killswitch-widget">
    <div class="status-panel">
        <h2 class="label">NETZWERKSTATUS</h2>
        <div class="status-value glitch" data-text="<?= $status ?>"><?= $status ?></div>
    </div>

    <?php if ($message): ?>
    <div class="system-message blink"><?= htmlspecialchars($message) ?></div>
    <?php else: ?>
    <div class="system-message">SYSTEM BEREIT FÜR EINGABE</div>
    <?php endif; ?>

    <div class="controls">
        <form method="POST" action="?page=killswitch">
            <?php if ($status === "ONLINE"): ?>
            <input type="hidden" name="action" value="on">
            <button type="submit" class="btn btn-kill">
                <span class="btn-text">INTERNET ABSCHALTEN</span>
                <span class="btn-subtext">>> INITIATE KILLSWITCH</span>
            </button>
            <?php else: ?>
            <input type="hidden" name="action" value="off">
            <button type="submit" class="btn btn-restore">
                <span class="btn-text">INTERNET FREIGEBEN</span>
                <span class="btn-subtext">>> RESTORE CONNECTION</span>
            </button>
            <?php endif; ?>
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
            <span class="data-val"><?= $status === 'OFFLINE' ? 'LOCKED' : 'OPEN' ?></span>
        </div>
    </div>
</div>
