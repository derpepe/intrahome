<?php
$defcon = ($status === 'OFFLINE') ? 3 : 5;
$defcon_color = ($defcon === 3) ? 'var(--neon-pink)' : 'var(--neon-cyan)';
?>
<div class="dashboard-grid">
    <div class="dashboard-card" style="border-color: <?= $defcon_color ?>;">
        <h3 class="label">THREAT LEVEL</h3>
        <div class="card-value" style="color: <?= $defcon_color ?>; text-shadow: 0 0 15px <?= $defcon_color ?>;">
            DEFCON <?= $defcon ?>
        </div>
        <div class="card-subtext">System Defense State</div>
    </div>

    <a href="?page=killswitch" class="dashboard-card <?= $status === 'OFFLINE' ? 'card-offline' : 'card-online' ?>">
        <h3 class="label">NETZWERK</h3>
        <div class="card-value"><?= $status ?></div>
        <div class="card-subtext">Click to manage >></div>
    </a>
    
    <a href="?page=weather" class="dashboard-card weather-card">
        <h3 class="label">AUSSENTEMP</h3>
        <div class="card-value" id="dash-temp">--°C</div>
        <div class="card-subtext">Fetching data...</div>
    </a>

    <a href="?page=power" class="dashboard-card power-card-dash">
        <h3 class="label">USV</h3>
        <div class="card-value" id="dash-power">--</div>
        <div class="card-subtext" id="dash-power-sub">Fetching data...</div>
    </a>

    <a href="?page=heating" class="dashboard-card heating-card-dash">
        <h3 class="label">HEIZUNG</h3>
        <div class="card-value" id="dash-heating">--</div>
        <div class="card-subtext" id="dash-heating-sub">Fetching data...</div>
    </a>
</div>

<div class="dash-smokeping">
    <div class="dash-smokeping-header">
        <h3 class="label">LATENCY // GOOGLE // 3H</h3>
        <a class="dash-smokeping-link" href="http://192.168.2.200/smokeping/?target=Internet" target="_blank" rel="noopener">DETAILS &gt;&gt;</a>
    </div>
    <a href="http://192.168.2.200/smokeping/?target=Internet" target="_blank" rel="noopener" class="dash-smokeping-figure">
        <img id="dash-smokeping-img"
             src="http://192.168.2.200/smokeping/images/Internet/Google_last_10800.png"
             alt="Smokeping: Latency Google (letzte 3 Stunden)"
             loading="lazy">
    </a>
    <div class="dash-smokeping-meta">
        <span id="dash-smokeping-updated">LAST UPDATE: --</span>
        <span>SOURCE: smokeping@192.168.2.200</span>
    </div>
</div>

<style>
    .dash-smokeping {
        margin-top: 25px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(0, 255, 255, 0.2);
        border-radius: 4px;
        padding: 18px;
    }
    .dash-smokeping-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .dash-smokeping-header .label {
        color: var(--neon-cyan);
        font-size: 13px;
        letter-spacing: 3px;
        margin: 0;
    }
    .dash-smokeping-link {
        font-size: 11px;
        letter-spacing: 2px;
        color: var(--neon-cyan);
        text-decoration: none;
        border: 1px solid rgba(0, 255, 255, 0.4);
        padding: 3px 8px;
        border-radius: 3px;
        transition: background 0.2s, color 0.2s;
    }
    .dash-smokeping-link:hover {
        background: rgba(0, 255, 255, 0.1);
        color: #fff;
    }
    .dash-smokeping-figure {
        display: block;
        background: #f5f5f5;
        border: 1px solid rgba(0, 255, 255, 0.25);
        border-radius: 3px;
        overflow: hidden;
        line-height: 0;
        padding: 6px;
        box-shadow: 0 0 12px rgba(0,255,255,0.08);
    }
    .dash-smokeping-figure img {
        display: block;
        width: 100%;
        height: auto;
    }
    .dash-smokeping-meta {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 10px;
        color: #555;
        letter-spacing: 2px;
        margin-top: 8px;
    }
</style>
<script>
function fetchDashWeather() {
    fetch('api/ecowitt.php')
        .then(response => response.json())
        .then(data => {
            if (data.error || data.code !== 0) {
                document.getElementById('dash-temp').innerText = "ERR";
                document.querySelector('.weather-card .card-subtext').innerText = data.error || data.msg || "Fehler";
                return;
            }
            try {
                let temp = data.data.outdoor.temperature.value + " " + data.data.outdoor.temperature.unit;
                document.getElementById('dash-temp').innerText = temp;
                document.querySelector('.weather-card .card-subtext').innerText = "Live Update >>";
            } catch(e) {}
        });
}

fetchDashWeather();
setInterval(fetchDashWeather, 60000);

function fetchDashPower() {
    fetch('api/apc.php')
        .then(response => response.json())
        .then(data => {
            const card = document.querySelector('.power-card-dash');
            const val  = document.getElementById('dash-power');
            const sub  = document.getElementById('dash-power-sub');
            if (!card || !val || !sub) return;

            if (data.error || data.code !== 0) {
                val.innerText = "ERR";
                sub.innerText = data.error || data.msg || "Fehler";
                return;
            }
            try {
                const d = data.data;
                const stateCode  = d.status.output.code;
                const stateLabel = d.status.output.label;
                const cap = d.battery.capacity.value;
                const inV = d.input.voltage.value;

                // stateLabel kann weiche Trennstriche (&shy;) enthalten
                val.innerHTML = (stateLabel || "--")
                    .replace(/[<>&"']/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[c]))
                    .replace(/\u00AD/g, '&shy;');
                sub.innerText =
                    (cap !== null ? "AKKU " + cap.toFixed(0) + "% // " : "") +
                    (inV !== null ? "IN " + inV.toFixed(0) + " V" : "");

                card.style.borderColor = '';
                card.style.color = '';
                if (stateCode === 'ONLINE') {
                    card.style.borderColor = 'var(--neon-green)';
                } else if (stateCode === 'ON_BATTERY' || stateCode === 'ON_SMART_BOOST' || stateCode === 'ON_SMART_TRIM') {
                    card.style.borderColor = 'var(--neon-yellow)';
                } else if (stateCode && stateCode !== 'UNKNOWN') {
                    card.style.borderColor = 'var(--neon-pink)';
                }
            } catch(e) {}
        })
        .catch(() => {
            const val = document.getElementById('dash-power');
            const sub = document.getElementById('dash-power-sub');
            if (val) val.innerText = "ERR";
            if (sub) sub.innerText = "NETWORK ERROR";
        });
}

fetchDashPower();
setInterval(fetchDashPower, 30000);

function fetchDashHeating() {
    fetch('api/heating.php?_=' + Date.now(), { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            const card = document.querySelector('.heating-card-dash');
            const val  = document.getElementById('dash-heating');
            const sub  = document.getElementById('dash-heating-sub');
            if (!card || !val || !sub) return;

            if (!data || data.ok === false) {
                val.innerText = 'ERR';
                sub.innerText = (data && data.errors && data.errors.length) ? data.errors[0] : 'Fehler';
                card.style.borderColor = 'var(--neon-pink)';
                return;
            }
            const state = data.wp_status ? data.wp_status.state : '--';
            const code  = data.wp_status ? data.wp_status.state_code : null;
            const fmt = v => (v === null || v === undefined) ? '--' :
                Number(v).toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

            val.innerText = (state || '--').toUpperCase();
            const out = data.outside_temp;
            const vlObj = data.data && data.data.vorlauf;
            const vl = vlObj ? vlObj.value : null;
            sub.innerText =
                (out !== null && out !== undefined ? 'AU\u00DFEN ' + fmt(out) + '\u00B0C' : '') +
                (vl  !== null && vl  !== undefined ? ' // VL ' + fmt(vl) + '\u00B0C' : '');

            // Color-Theme nach Status-Code
            let color = '';
            if (code === 0)               color = '';                           // idle → neutral
            else if ([1,2,3].includes(code)) color = '#ff9040';                  // Heizen
            else if (code === 4)          color = '#00c8ff';                    // Warmwasser
            else if (code === 10)         color = 'var(--neon-yellow)';         // Abtauen
            else if (code === 30)         color = 'var(--neon-pink)';           // Sperre
            card.style.borderColor = color;
        })
        .catch(() => {
            const val = document.getElementById('dash-heating');
            const sub = document.getElementById('dash-heating-sub');
            if (val) val.innerText = 'ERR';
            if (sub) sub.innerText = 'NETWORK ERROR';
        });
}

fetchDashHeating();
setInterval(fetchDashHeating, 30000);

// Smokeping-Graph (Dashboard): alle 60s neu laden (Cache-Buster)
(function() {
    const img = document.getElementById('dash-smokeping-img');
    const updated = document.getElementById('dash-smokeping-updated');
    if (!img) return;
    const baseSrc = img.getAttribute('src').split('?')[0];

    function refresh() {
        img.src = baseSrc + '?_=' + Date.now();
        if (updated) {
            updated.innerText = 'LAST UPDATE: ' +
                new Date().toLocaleTimeString('de-DE', { hour12: false });
        }
    }
    refresh();
    setInterval(refresh, 60000);
})();
</script>
