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
</script>
