<style>
    .power-grid {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Hero – Output-Spannung */
    .power-hero-section {
        width: 100%;
        max-width: 1000px;
        display: flex;
        justify-content: center;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .power-hero-card {
        width: 100%;
        background: rgba(0, 255, 255, 0.04);
        border: 2px solid rgba(0, 255, 255, 0.35);
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        position: relative;
        transition: border-color 0.4s, background 0.4s;
    }

    /* Status-Themes */
    .power-hero-card.state-online {
        background: rgba(0, 255, 100, 0.05);
        border-color: rgba(0, 255, 100, 0.4);
    }
    .power-hero-card.state-battery {
        background: rgba(253, 245, 0, 0.05);
        border-color: rgba(253, 245, 0, 0.5);
        animation: pulseYellow 2s infinite;
    }
    .power-hero-card.state-fault {
        background: rgba(255, 0, 80, 0.07);
        border-color: rgba(255, 0, 80, 0.6);
        animation: pulseRed 1.5s infinite;
    }

    @keyframes pulseYellow {
        0%,100% { box-shadow: 0 0 0 rgba(253,245,0,0); }
        50%     { box-shadow: 0 0 25px rgba(253,245,0,0.35); }
    }
    @keyframes pulseRed {
        0%,100% { box-shadow: 0 0 0 rgba(255,0,80,0); }
        50%     { box-shadow: 0 0 30px rgba(255,0,80,0.5); }
    }

    .power-hero-card .sensor-title {
        color: var(--neon-cyan);
        font-size: 20px;
        letter-spacing: 4px;
        margin-bottom: 20px;
        text-shadow: 0 0 10px rgba(0,255,255,0.5);
        text-transform: uppercase;
    }
    .power-hero-card.state-online  .sensor-title { color: var(--neon-green); text-shadow: 0 0 10px rgba(0,255,100,0.5); }
    .power-hero-card.state-battery .sensor-title { color: var(--neon-yellow); text-shadow: 0 0 10px rgba(253,245,0,0.5); }
    .power-hero-card.state-fault   .sensor-title { color: var(--neon-pink); text-shadow: 0 0 10px rgba(255,0,80,0.6); }

    .power-hero-main {
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .power-hero-value {
        font-family: var(--font-heading);
        font-size: 80px;
        font-weight: bold;
        color: var(--neon-cyan);
        text-shadow: 0 0 15px rgba(0,255,255,0.8);
    }
    .power-hero-card.state-online  .power-hero-value { color: var(--neon-green); text-shadow: 0 0 15px rgba(0,255,100,0.8); }
    .power-hero-card.state-battery .power-hero-value { color: var(--neon-yellow); text-shadow: 0 0 15px rgba(253,245,0,0.8); }
    .power-hero-card.state-fault   .power-hero-value { color: var(--neon-pink); text-shadow: 0 0 15px rgba(255,0,80,0.8); }

    .power-hero-sub {
        font-size: 24px;
        color: #ccc;
        letter-spacing: 2px;
    }

    .power-hero-status {
        font-family: var(--font-heading);
        font-size: 28px;
        letter-spacing: 6px;
        color: var(--neon-cyan);
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .power-hero-card.state-online  .power-hero-status { color: var(--neon-green); }
    .power-hero-card.state-battery .power-hero-status { color: var(--neon-yellow); }
    .power-hero-card.state-fault   .power-hero-status { color: var(--neon-pink); }

    .power-hero-telemetry {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 40px;
        border-top: 1px solid rgba(0, 255, 255, 0.2);
        padding-top: 30px;
        margin-top: 20px;
    }

    .power-hero-telemetry .data-block {
        flex: 1 1 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .power-hero-telemetry .data-label {
        color: #888;
        font-size: 14px;
        margin-bottom: 5px;
        letter-spacing: 2px;
    }

    .power-hero-telemetry .data-val {
        font-size: 24px;
        color: var(--text-main);
        font-weight: bold;
    }

    /* Sub-Cards */
    .power-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        width: 100%;
        max-width: 1200px;
        margin-bottom: 40px;
    }

    .power-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(0, 255, 255, 0.2);
        border-radius: 4px;
        padding: 25px;
        text-align: center;
    }

    .power-card .sensor-title {
        color: #888;
        font-size: 14px;
        letter-spacing: 2px;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .power-card .sensor-val {
        font-family: var(--font-heading);
        font-size: 40px;
        font-weight: bold;
        color: var(--neon-cyan);
        margin-bottom: 5px;
    }

    .power-card .sensor-sub {
        font-size: 14px;
        color: #aaa;
        letter-spacing: 1px;
    }

    /* Battery- und Load-Bar */
    .power-bar-container {
        width: 100%;
        height: 8px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 4px;
        margin: 10px 0 4px;
        overflow: hidden;
    }
    .power-bar {
        height: 100%;
        width: 0%;
        transition: width 0.6s ease-out, background 0.3s;
    }
    .power-bar.battery { background: linear-gradient(90deg, #ff0040, #ffa500, var(--neon-yellow), var(--neon-green)); }
    .power-bar.load    { background: linear-gradient(90deg, var(--neon-green), var(--neon-yellow), #ffa500, var(--neon-pink)); }

    .power-meta {
        width: 100%;
        max-width: 1200px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 11px;
        color: #555;
        letter-spacing: 2px;
        padding: 0 4px 20px;
    }

    @media (max-width: 768px) {
        .power-hero-value { font-size: 50px; }
        .power-hero-status { font-size: 22px; letter-spacing: 3px; }
    }
</style>

<div class="power-grid">
    <div class="status-panel">
        <h2 class="label">APC USV // POWERNET</h2>
        <div class="system-message" id="power-status">FETCHING...</div>
    </div>

    <!-- HERO: Output + Status -->
    <div class="power-hero-section">
        <div class="power-hero-card" id="power-hero">
            <h4 class="sensor-title">USV STATUS</h4>

            <div class="power-hero-status" id="power-state">--</div>

            <div class="power-hero-main">
                <div class="power-hero-value" id="power-out-voltage">--V</div>
                <div class="power-hero-sub" id="power-out-freq">-- HZ</div>
            </div>

            <div class="power-hero-telemetry">
                <div class="data-block">
                    <span class="data-label">EINGANG</span>
                    <span class="data-val" id="power-in-voltage">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">FREQ. EIN</span>
                    <span class="data-val" id="power-in-freq">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">MIN / MAX</span>
                    <span class="data-val" id="power-in-minmax">-- / --</span>
                </div>
                <div class="data-block">
                    <span class="data-label">LETZTER TRANSFER</span>
                    <span class="data-val" id="power-xfer" style="font-size:14px;">--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub-Cards -->
    <div class="power-cards">
        <div class="power-card">
            <h4 class="sensor-title">AKKU LADESTAND</h4>
            <div class="sensor-val" id="power-bat-cap">--%</div>
            <div class="power-bar-container">
                <div class="power-bar battery" id="power-bat-bar" style="width:0%;"></div>
            </div>
            <div class="sensor-sub" id="power-bat-status">--</div>
        </div>

        <div class="power-card">
            <h4 class="sensor-title">RESTLAUFZEIT</h4>
            <div class="sensor-val" id="power-runtime">-- MIN</div>
            <div class="sensor-sub" id="power-runtime-sub">bei aktueller Last</div>
        </div>

        <div class="power-card">
            <h4 class="sensor-title">LAST</h4>
            <div class="sensor-val" id="power-load">--%</div>
            <div class="power-bar-container">
                <div class="power-bar load" id="power-load-bar" style="width:0%;"></div>
            </div>
            <div class="sensor-sub" id="power-load-sub">-- A AUSGANG</div>
        </div>

        <div class="power-card">
            <h4 class="sensor-title">AKKU</h4>
            <div class="sensor-val" id="power-bat-volt">--V</div>
            <div class="sensor-sub" id="power-bat-temp">-- °C</div>
        </div>
    </div>

    <div class="power-meta">
        <span id="power-model">MODELL: --</span>
        <span id="power-fw">FW: --</span>
        <span id="power-replace">AKKU: --</span>
        <span id="power-updated">LAST UPDATE: --</span>
    </div>
</div>

<script>
(function() {
    function fmt(value, unit, digits) {
        if (value === null || value === undefined || value === "") return "--";
        if (typeof value === "number" && !isNaN(value)) {
            if (digits === undefined) digits = (Math.abs(value) >= 100 ? 0 : 1);
            return value.toFixed(digits) + (unit ? " " + unit : "");
        }
        return value + (unit ? " " + unit : "");
    }

    function setText(id, txt) {
        var el = document.getElementById(id);
        if (el) el.innerText = txt;
    }

    function applyState(stateCode) {
        var hero = document.getElementById('power-hero');
        if (!hero) return;
        hero.classList.remove('state-online', 'state-battery', 'state-fault');
        if (stateCode === 'ONLINE') hero.classList.add('state-online');
        else if (stateCode === 'ON_BATTERY' || stateCode === 'ON_SMART_BOOST' || stateCode === 'ON_SMART_TRIM') hero.classList.add('state-battery');
        else if (stateCode === 'HARDWARE_FAILURE_BYPASS' || stateCode === 'OFF' || stateCode === 'SWITCHED_BYPASS' || stateCode === 'SOFTWARE_BYPASS') hero.classList.add('state-fault');
    }

    function fetchPower() {
        fetch('api/apc.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error || data.code !== 0) {
                    setText('power-status', (data.error || data.msg || 'ERROR').toUpperCase());
                    return;
                }
                setText('power-status', 'LIVE TELEMETRY // CONNECTED');

                var d = data.data;

                // Status
                var outState = d.status.output || {};
                applyState(outState.code);
                setText('power-state', outState.label || '--');

                // Hero – Output
                setText('power-out-voltage', fmt(d.output.voltage.value, 'V'));
                setText('power-out-freq',    fmt(d.output.frequency.value, 'HZ'));

                // Input
                setText('power-in-voltage', fmt(d.input.voltage.value, 'V'));
                setText('power-in-freq',    fmt(d.input.frequency.value, 'Hz'));
                var minV = d.input.min.value, maxV = d.input.max.value;
                setText('power-in-minmax',
                    (minV !== null ? minV.toFixed(0) : '--') + ' / ' +
                    (maxV !== null ? maxV.toFixed(0) : '--') + ' V'
                );
                setText('power-xfer', d.status.last_transfer_reason || '--');

                // Akku
                var capVal = d.battery.capacity.value;
                setText('power-bat-cap', capVal !== null ? capVal.toFixed(0) + '%' : '--%');
                var bar = document.getElementById('power-bat-bar');
                if (bar && capVal !== null) bar.style.width = Math.max(0, Math.min(100, capVal)) + '%';
                setText('power-bat-status',
                    (d.status.battery.label || '--') +
                    (d.status.replace && d.status.replace.code === 'REPLACE' ? ' // AUSTAUSCH!' : '')
                );

                // Laufzeit
                var rt = d.battery.runtime.minutes;
                setText('power-runtime', rt !== null ? rt.toFixed(0) + ' MIN' : '-- MIN');

                // Last
                var load = d.output.load.value;
                setText('power-load', load !== null ? load.toFixed(0) + '%' : '--%');
                var lbar = document.getElementById('power-load-bar');
                if (lbar && load !== null) lbar.style.width = Math.max(0, Math.min(100, load)) + '%';
                setText('power-load-sub', fmt(d.output.current.value, 'A') + ' AUSGANG');

                // Battery V + Temp
                setText('power-bat-volt', fmt(d.battery.voltage.value, 'V'));
                setText('power-bat-temp', fmt(d.battery.temperature.value, '°C'));

                // Meta
                setText('power-model',   'MODELL: ' + (d.identity.model || '--'));
                setText('power-fw',      'FW: '     + (d.identity.firmware || '--'));
                setText('power-replace', 'AKKU: '   + (d.status.replace.label || '--'));
                var ts = new Date(d.fetched_at);
                setText('power-updated', 'LAST UPDATE: ' + ts.toLocaleTimeString('de-DE', { hour12: false }));
            })
            .catch(function() {
                setText('power-status', 'NETWORK ERROR');
            });
    }

    fetchPower();
    setInterval(fetchPower, 15000);
})();
</script>
