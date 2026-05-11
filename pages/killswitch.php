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

    <div class="smokeping-panel">
        <div class="smokeping-header">
            <h3 class="label">LATENCY // GOOGLE // 3H</h3>
            <a class="smokeping-link" href="http://192.168.2.200/smokeping/?target=Internet" target="_blank" rel="noopener">DETAILS &gt;&gt;</a>
        </div>
        <a href="http://192.168.2.200/smokeping/?target=Internet" target="_blank" rel="noopener" class="smokeping-figure">
            <img id="smokeping-img"
                 src="http://192.168.2.200/smokeping/images/Internet/Google_last_10800.png"
                 alt="Smokeping: Latency Google (letzte 3 Stunden)"
                 loading="lazy">
        </a>
        <div class="smokeping-meta">
            <span id="smokeping-updated">LAST UPDATE: --</span>
            <span>SOURCE: smokeping@192.168.2.200</span>
        </div>
    </div>
</div>

<style>
    .smokeping-panel {
        margin-top: 30px;
        width: 100%;
        max-width: 900px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(0, 255, 255, 0.2);
        border-radius: 4px;
        padding: 20px;
    }
    .smokeping-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .smokeping-header .label {
        color: var(--neon-cyan);
        font-size: 14px;
        letter-spacing: 3px;
        margin: 0;
    }
    .smokeping-link {
        font-size: 12px;
        letter-spacing: 2px;
        color: var(--neon-cyan);
        text-decoration: none;
        border: 1px solid rgba(0, 255, 255, 0.4);
        padding: 4px 10px;
        border-radius: 3px;
        transition: background 0.2s, color 0.2s;
    }
    .smokeping-link:hover {
        background: rgba(0, 255, 255, 0.1);
        color: #fff;
    }
    .smokeping-figure {
        display: block;
        width: 100%;
        background: #f5f5f5;
        border: 1px solid rgba(0, 255, 255, 0.25);
        border-radius: 3px;
        overflow: hidden;
        line-height: 0;
        padding: 6px;
        box-shadow: 0 0 12px rgba(0,255,255,0.08);
    }
    .smokeping-figure img {
        display: block;
        width: 100%;
        height: auto;
    }
    .smokeping-meta {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 11px;
        color: #555;
        letter-spacing: 2px;
        margin-top: 10px;
    }
</style>

<script>
// Smokeping-Graph: alle 60s neu laden (Cache-Buster), Update-Timestamp setzen
(function() {
    const img = document.getElementById('smokeping-img');
    const updated = document.getElementById('smokeping-updated');
    if (!img) return;
    const baseSrc = img.getAttribute('src').split('?')[0];

    function refreshSmokeping() {
        img.src = baseSrc + '?_=' + Date.now();
        if (updated) {
            updated.innerText = 'LAST UPDATE: ' +
                new Date().toLocaleTimeString('de-DE', { hour12: false });
        }
    }
    refreshSmokeping();
    setInterval(refreshSmokeping, 60000);
})();

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
            
            if (actionInput.value === 'off') {
                if (btnText) btnText.innerText = "PENDING...";
                let counter = 300;
                if (btnSub) btnSub.innerText = ">> T-" + counter + "s UNTIL SHOWTIME";
                
                const countdownInterval = setInterval(() => {
                    counter--;
                    if (btnSub) {
                        if (counter >= 0) {
                            btnSub.innerText = ">> T-" + counter + "s UNTIL SHOWTIME";
                        } else {
                            btnSub.innerText = ">> T+" + Math.abs(counter) + "s CONNECTING...";
                        }
                    }
                }, 1000);
            } else {
                if (btnText) btnText.innerText = "PROCESSING...";
                let counter = 20;
                if (btnSub) btnSub.innerText = ">> T-" + counter + "s UNTIL BLACKOUT";
                
                const countdownInterval = setInterval(() => {
                    counter--;
                    if (btnSub) {
                        if (counter >= 0) {
                            btnSub.innerText = ">> T-" + counter + "s UNTIL BLACKOUT";
                        } else {
                            btnSub.innerText = ">> T+" + Math.abs(counter) + "s EXECUTING...";
                        }
                    }
                }, 1000);
            }
            
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
