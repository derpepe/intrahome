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
</div>
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

                val.innerText = stateLabel || "--";
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
</script>
