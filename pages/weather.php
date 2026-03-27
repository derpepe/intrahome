<style>
    /* Base */
    .weather-grid {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Outdoor Hero Card */
    .outdoor-section {
        width: 100%;
        max-width: 1000px;
        display: flex;
        justify-content: center;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .outdoor-card {
        width: 100%;
        background: rgba(253, 245, 0, 0.05);
        border: 2px solid rgba(253, 245, 0, 0.3);
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        position: relative;
    }

    .outdoor-card .sensor-title {
        color: var(--neon-yellow);
        font-size: 20px;
        letter-spacing: 4px;
        margin-bottom: 20px;
        text-shadow: 0 0 10px rgba(253, 245, 0, 0.5);
        text-transform: uppercase;
    }

    .outdoor-main {
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 20px;
        margin-bottom: 40px;
    }

    .outdoor-temp {
        font-family: var(--font-heading);
        font-size: 80px;
        font-weight: bold;
        color: var(--neon-yellow);
        text-shadow: 0 0 15px rgba(253, 245, 0, 0.8);
    }

    .outdoor-hum {
        font-size: 24px;
        color: #ccc;
    }

    .outdoor-telemetry {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 40px;
        border-top: 1px solid rgba(253, 245, 0, 0.2);
        padding-top: 30px;
    }

    .outdoor-telemetry .data-block {
        flex: 1 1 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .outdoor-telemetry .data-label {
        color: #888;
        font-size: 14px;
        margin-bottom: 5px;
        letter-spacing: 2px;
    }

    .outdoor-telemetry .data-val {
        font-size: 24px;
        color: var(--text-main);
        font-weight: bold;
    }

    /* Indoor Room Cards */
    .sensor-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        width: 100%;
        max-width: 1200px;
        margin-bottom: 40px;
    }

    .sensor-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(0, 255, 255, 0.2);
        border-radius: 4px;
        padding: 25px;
        text-align: center;
    }

    .sensor-card .sensor-title {
        color: #888;
        font-size: 14px;
        letter-spacing: 2px;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .sensor-card .sensor-temp {
        font-family: var(--font-heading);
        font-size: 40px;
        font-weight: bold;
        color: var(--neon-cyan);
        margin-bottom: 5px;
    }

    .sensor-card .sensor-hum {
        font-size: 16px;
        color: #aaa;
    }

    .uv-scale-container {
        width: 100%;
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        margin: 8px 0;
        overflow: hidden;
    }

    .uv-scale-bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, var(--neon-green), var(--neon-yellow), #ffa500, var(--neon-pink), #ff0000);
        transition: width 0.5s ease-out;
    }

    @media (max-width: 768px) {
        .outdoor-temp {
            font-size: 50px;
        }
    }
</style>

<?php
$config_file = __DIR__ . '/../config.php';
$config = file_exists($config_file) ? include $config_file : [];
$rooms = $config['ecowitt']['room_names'] ?? [
    'ch1' => 'RAUM 1',
    'ch2' => 'RAUM 2',
    'ch3' => 'RAUM 3',
    'ch4' => 'RAUM 4'
];
?>

<div class="weather-grid">
    <div class="status-panel">
        <h2 class="label">ECOWITT SENSOR NETWORK</h2>
        <div class="system-message" id="weather-status">FETCHING...</div>
    </div>

    <!-- BIG OUTDOOR HERO -->
    <div class="outdoor-section">
        <div class="outdoor-card">
            <h4 class="sensor-title">AUSSEN & WETTERBEDINGUNGEN</h4>

            <div class="outdoor-main">
                <div class="outdoor-temp" id="sensor-outdoor-temp">--°C</div>
                <div class="outdoor-hum" id="sensor-outdoor-hum">--LF</div>
            </div>

            <div class="outdoor-telemetry">
                <div class="data-block">
                    <span class="data-label">WIND</span>
                    <span class="data-val" id="weather-wind">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">BAROMETER</span>
                    <span class="data-val" id="weather-pressure">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">REGEN / TAG</span>
                    <span class="data-val" id="weather-rain">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">HELLIGKEIT</span>
                    <span class="data-val" id="weather-solar">--</span>
                </div>
                <div class="data-block">
                    <span class="data-label">UV INDEX</span>
                    <div class="uv-scale-container">
                        <div class="uv-scale-bar" id="weather-uvi-bar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IDENTICAL ROOM CARDS -->
    <div class="sensor-cards">
        <div class="sensor-card">
            <h4 class="sensor-title">WOHNZIMMER</h4>
            <div class="sensor-temp" id="sensor-indoor-temp">--°C</div>
            <div class="sensor-hum" id="sensor-indoor-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">
                <?= htmlspecialchars($rooms['ch1'])?>
            </h4>
            <div class="sensor-temp" id="sensor-ch1-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch1-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">
                <?= htmlspecialchars($rooms['ch2'])?>
            </h4>
            <div class="sensor-temp" id="sensor-ch2-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch2-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">
                <?= htmlspecialchars($rooms['ch3'])?>
            </h4>
            <div class="sensor-temp" id="sensor-ch3-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch3-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">
                <?= htmlspecialchars($rooms['ch4'])?>
            </h4>
            <div class="sensor-temp" id="sensor-ch4-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch4-hum">--LF</div>
        </div>
    </div>
</div>

<script>
    function fetchWeather() {
        fetch('api/ecowitt.php')
            .then(response => response.json())
            .then(data => {
                if (data.error || data.code !== 0) {
                    document.getElementById('weather-status').innerText = (data.error || data.msg || "ERROR").toUpperCase();
                    return;
                }
                document.getElementById('weather-status').innerText = "LIVE TELEMETRY // CONNECTED";

                try {
                    const parseSensor = (sensorData, tempId, humId) => {
                        if (sensorData && sensorData.temperature) {
                            document.getElementById(tempId).innerText = sensorData.temperature.value + " " + sensorData.temperature.unit;
                        }
                        if (sensorData && sensorData.humidity) {
                            document.getElementById(humId).innerText = sensorData.humidity.value + sensorData.humidity.unit + " LF";
                        }
                    };

                    // Populate outdoor main
                    parseSensor(data.data.outdoor, 'sensor-outdoor-temp', 'sensor-outdoor-hum');

                    // Populate rooms
                    parseSensor(data.data.indoor, 'sensor-indoor-temp', 'sensor-indoor-hum');
                    parseSensor(data.data.temp_and_humidity_ch1, 'sensor-ch1-temp', 'sensor-ch1-hum');
                    parseSensor(data.data.temp_and_humidity_ch2, 'sensor-ch2-temp', 'sensor-ch2-hum');
                    parseSensor(data.data.temp_and_humidity_ch3, 'sensor-ch3-temp', 'sensor-ch3-hum');
                    parseSensor(data.data.temp_and_humidity_ch4, 'sensor-ch4-temp', 'sensor-ch4-hum');

                    // Outdoor extras
                    if (data.data.wind && data.data.wind.wind_speed) {
                        document.getElementById('weather-wind').innerText = data.data.wind.wind_speed.value + " " + data.data.wind.wind_speed.unit;
                    }
                    if (data.data.pressure && data.data.pressure.relative) {
                        document.getElementById('weather-pressure').innerText = data.data.pressure.relative.value + " " + data.data.pressure.relative.unit;
                    }
                    let rainObj = data.data.rainfall_piezo || data.data.rainfall;
                    if (rainObj && rainObj.daily) {
                        document.getElementById('weather-rain').innerText = rainObj.daily.value + " " + rainObj.daily.unit;
                    }
                    if (data.data.solar_and_uvi && data.data.solar_and_uvi.solar) {
                        document.getElementById('weather-solar').innerText = data.data.solar_and_uvi.solar.value + " " + data.data.solar_and_uvi.solar.unit;
                    }
                    if (data.data.solar_and_uvi && data.data.solar_and_uvi.uvi) {
                        let uvi = parseFloat(data.data.solar_and_uvi.uvi.value);
                        let uviPercent = Math.min((uvi / 11) * 100, 100);
                        let bar = document.getElementById('weather-uvi-bar');
                        if (bar) {
                            bar.style.width = uviPercent + '%';
                            // We can use a tooltip or just rely on the scale.
                        }
                    }
                } catch (e) { console.log(e); }
            })
            .catch(err => {
                document.getElementById('weather-status').innerText = "NETWORK ERROR";
            });
    }

    fetchWeather();
    setInterval(fetchWeather, 60000);
</script>