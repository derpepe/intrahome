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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.killswitch-widget form');
    const button = form ? form.querySelector('button') : null;
    const actionInput = form ? form.querySelector('input[name="action"]') : null;
    
    if (form && button && actionInput) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            button.disabled = true;
            button.classList.add('is-loading');
            
            const btnText = button.querySelector('.btn-text');
            const btnSub = button.querySelector('.btn-subtext');
            if (btnText) btnText.innerText = "PROCESSING...";
            if (btnSub) btnSub.innerText = ">> PLEASE WAIT";
            
            const sysMsg = document.querySelector('.system-message');
            if (sysMsg) {
                sysMsg.innerText = ">> UPLINK PENDING <<";
                sysMsg.classList.add('blink');
            }
            
            const statusVal = document.querySelector('.status-value');
            if (statusVal) {
                statusVal.innerText = "PENDING";
                statusVal.dataset.text = "PENDING";
                statusVal.style.color = "var(--neon-yellow)";
                statusVal.style.textShadow = "0 0 20px var(--neon-yellow)";
            }
            
            const formData = new FormData(form);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).catch(err => console.error(err));
            
            const targetStatus = actionInput.value === 'on' ? 'OFFLINE' : 'ONLINE';
            
            const pollInterval = setInterval(() => {
                fetch('?api=status')
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === targetStatus) {
                            clearInterval(pollInterval);
                            window.location.reload();
                        }
                    })
                    .catch(err => console.error("Polling error:", err));
            }, 1500);
        });
    }
});
</script>
