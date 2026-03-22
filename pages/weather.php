<style>
.weather-grid { width: 100%; display: flex; flex-direction: column; align-items: center; }
.sensor-cards { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; width: 100%; max-width: 1200px; margin-top: 30px;}
.sensor-card { flex: 1 1 200px; background: rgba(255,255,255,0.02); border: 1px solid rgba(0, 255, 255, 0.2); border-radius: 4px; padding: 25px; text-align: center; }
.sensor-title { color: #888; font-size: 14px; letter-spacing: 2px; margin-bottom: 15px; }
.sensor-temp { font-family: var(--font-heading); font-size: 40px; font-weight: bold; color: var(--neon-cyan); margin-bottom: 5px;}
.sensor-hum { font-size: 16px; color: #aaa; }
#sensor-outdoor-temp { font-size: 55px; color: var(--neon-yellow); text-shadow: 0 0 10px rgba(253, 245, 0, 0.5);}
.sensor-card.outdoor { border-color: rgba(253, 245, 0, 0.4); background: rgba(253, 245, 0, 0.05); }
</style>

<div class="weather-grid">
    <div class="status-panel">
        <h2 class="label">ECOWITT SENSOR NETWORK</h2>
        <div class="system-message" id="weather-status">FETCHING...</div>
    </div>
    
    <div class="sensor-cards">
        <div class="sensor-card outdoor">
            <h4 class="sensor-title">AUSSEN</h4>
            <div class="sensor-temp glitch" data-text="--°C" id="sensor-outdoor-temp">--°C</div>
            <div class="sensor-hum" id="sensor-outdoor-hum">--LF</div>
        </div>
        
        <div class="sensor-card">
            <h4 class="sensor-title">INNEN / BASIS</h4>
            <div class="sensor-temp" id="sensor-indoor-temp">--°C</div>
            <div class="sensor-hum" id="sensor-indoor-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">RAUM 1</h4>
            <div class="sensor-temp" id="sensor-ch1-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch1-hum">--LF</div>
        </div>
        
        <div class="sensor-card">
            <h4 class="sensor-title">RAUM 2</h4>
            <div class="sensor-temp" id="sensor-ch2-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch2-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">RAUM 3</h4>
            <div class="sensor-temp" id="sensor-ch3-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch3-hum">--LF</div>
        </div>

        <div class="sensor-card">
            <h4 class="sensor-title">RAUM 4</h4>
            <div class="sensor-temp" id="sensor-ch4-temp">--°C</div>
            <div class="sensor-hum" id="sensor-ch4-hum">--LF</div>
        </div>
    </div>

    <!-- Additional conditions -->
    <div class="telemetry" style="margin-top: 50px;">
        <div class="data-block">
            <span class="data-label">WIND</span>
            <span class="data-val" id="weather-wind">--</span>
        </div>
        <div class="data-block">
            <span class="data-label">LUFTDRUCK</span>
            <span class="data-val" id="weather-pressure">--</span>
        </div>
        <div class="data-block">
            <span class="data-label">REGEN / TAG</span>
            <span class="data-val" id="weather-rain">--</span>
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

                // Populate all rooms
                parseSensor(data.data.outdoor, 'sensor-outdoor-temp', 'sensor-outdoor-hum');
                if(data.data.outdoor && data.data.outdoor.temperature) {
                    document.getElementById('sensor-outdoor-temp').setAttribute('data-text', data.data.outdoor.temperature.value + " " + data.data.outdoor.temperature.unit);
                }

                parseSensor(data.data.indoor, 'sensor-indoor-temp', 'sensor-indoor-hum');
                parseSensor(data.data.temp_and_humidity_ch1, 'sensor-ch1-temp', 'sensor-ch1-hum');
                parseSensor(data.data.temp_and_humidity_ch2, 'sensor-ch2-temp', 'sensor-ch2-hum');
                parseSensor(data.data.temp_and_humidity_ch3, 'sensor-ch3-temp', 'sensor-ch3-hum');
                parseSensor(data.data.temp_and_humidity_ch4, 'sensor-ch4-temp', 'sensor-ch4-hum');
                
                // Extra conditions
                if(data.data.wind) {
                    document.getElementById('weather-wind').innerText = data.data.wind.wind_speed.value + " " + data.data.wind.wind_speed.unit;
                }
                if(data.data.pressure) {
                    document.getElementById('weather-pressure').innerText = data.data.pressure.relative.value + " " + data.data.pressure.relative.unit;
                }
                if(data.data.rainfall_piezo) {
                    document.getElementById('weather-rain').innerText = data.data.rainfall_piezo.daily.value + " " + data.data.rainfall_piezo.daily.unit;
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
