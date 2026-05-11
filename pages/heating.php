<?php
// ===== Server-seitiger Initial-Fetch (damit auch ohne JS sofort was sichtbar ist) =====
// Wir rufen die Heating-Logik in einem isolierten Scope auf, damit JSON-Header
// und Cache-Logik aus api/heating.php nicht mit der Seitenausgabe kollidieren.
$_HEATING_INLINE = true; // Signalisiert api/heating.php: nicht exit(), sondern return
ob_start();
try {
    include __DIR__ . '/../api/heating.php';
} catch (\Throwable $e) {
    // Falls API komplett scheitert, leeres Result zurückgeben
}
$_heating_json = ob_get_clean();
unset($_HEATING_INLINE);
$initial = json_decode($_heating_json, true) ?: ['ok' => false, 'errors' => ['Initial-Fetch fehlgeschlagen'], 'data' => [], 'wp_status' => null, 'outside_temp' => null, 'host' => ''];
unset($_heating_json);

$wpState  = $initial['wp_status']['state'] ?? 'UNBEKANNT';
$wpCode   = $initial['wp_status']['state_code'] ?? null;
$wpLock   = $initial['wp_status']['lock_reason'] ?? null;
$outside  = $initial['outside_temp'] ?? null;
$data     = $initial['data'] ?? [];

function v(?array $point, string $key = 'value', $default = null) {
    return $point ? ($point[$key] ?? $default) : $default;
}

// Thema basierend auf State
$theme = 'idle';
if ($wpCode !== null) {
    if ($wpCode === 0) $theme = 'idle';
    elseif (in_array($wpCode, [1,2,3], true)) $theme = 'heat';
    elseif ($wpCode === 4) $theme = 'water';
    elseif ($wpCode === 5) $theme = 'cool';
    elseif ($wpCode === 10) $theme = 'defrost';
    elseif ($wpCode === 30) $theme = 'lock';
    else $theme = 'unknown';
}
?>
<style>
    .heating-grid {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 30px;
    }

    /* Hero: WP-Status */
    .heating-hero {
        width: 100%;
        max-width: 1000px;
        background: rgba(0, 255, 255, 0.04);
        border: 2px solid rgba(0, 255, 255, 0.35);
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        position: relative;
        transition: border-color 0.4s, background 0.4s;
    }
    .heating-hero.theme-idle    { background: rgba(120,120,120,0.05); border-color: rgba(180,180,180,0.35); }
    .heating-hero.theme-heat    { background: rgba(255, 90, 0, 0.07); border-color: rgba(255, 130, 0, 0.55); animation: pulseOrange 2.5s infinite; }
    .heating-hero.theme-water   { background: rgba(0, 180, 255, 0.06); border-color: rgba(0, 200, 255, 0.5); animation: pulseBlue 2.5s infinite; }
    .heating-hero.theme-cool    { background: rgba(100, 180, 255, 0.06); border-color: rgba(100, 180, 255, 0.5); }
    .heating-hero.theme-defrost { background: rgba(253, 245, 0, 0.06); border-color: rgba(253, 245, 0, 0.55); animation: pulseYellow 2s infinite; }
    .heating-hero.theme-lock    { background: rgba(255, 0, 80, 0.07); border-color: rgba(255, 0, 80, 0.6); animation: pulseRed 1.5s infinite; }

    @keyframes pulseOrange { 0%,100% { box-shadow: 0 0 0 rgba(255,130,0,0); } 50% { box-shadow: 0 0 25px rgba(255,130,0,0.35); } }
    @keyframes pulseBlue   { 0%,100% { box-shadow: 0 0 0 rgba(0,200,255,0); } 50% { box-shadow: 0 0 25px rgba(0,200,255,0.35); } }
    @keyframes pulseYellow { 0%,100% { box-shadow: 0 0 0 rgba(253,245,0,0); } 50% { box-shadow: 0 0 25px rgba(253,245,0,0.35); } }
    @keyframes pulseRed    { 0%,100% { box-shadow: 0 0 0 rgba(255,0,80,0); } 50% { box-shadow: 0 0 30px rgba(255,0,80,0.5); } }

    .heating-hero .label {
        color: var(--neon-cyan);
        font-size: 18px;
        letter-spacing: 4px;
        margin-bottom: 15px;
        text-shadow: 0 0 10px rgba(0,255,255,0.5);
        text-transform: uppercase;
    }
    .heating-hero.theme-heat    .label { color: #ff9040; text-shadow: 0 0 10px rgba(255,144,64,0.5); }
    .heating-hero.theme-water   .label { color: #00c8ff; text-shadow: 0 0 10px rgba(0,200,255,0.5); }
    .heating-hero.theme-defrost .label { color: var(--neon-yellow); text-shadow: 0 0 10px rgba(253,245,0,0.5); }
    .heating-hero.theme-lock    .label { color: var(--neon-pink); text-shadow: 0 0 10px rgba(255,0,80,0.6); }
    .heating-hero.theme-idle    .label { color: #999; text-shadow: none; }

    .heating-hero .state-text {
        font-family: var(--font-heading);
        font-size: 72px;
        font-weight: bold;
        color: var(--neon-cyan);
        letter-spacing: 6px;
        margin: 10px 0;
        text-shadow: 0 0 20px rgba(0,255,255,0.4);
        text-transform: uppercase;
    }
    .heating-hero.theme-heat    .state-text { color: #ff9040; text-shadow: 0 0 20px rgba(255,144,64,0.5); }
    .heating-hero.theme-water   .state-text { color: #00c8ff; text-shadow: 0 0 20px rgba(0,200,255,0.5); }
    .heating-hero.theme-defrost .state-text { color: var(--neon-yellow); text-shadow: 0 0 20px rgba(253,245,0,0.4); }
    .heating-hero.theme-lock    .state-text { color: var(--neon-pink); text-shadow: 0 0 20px rgba(255,0,80,0.5); }
    .heating-hero.theme-idle    .state-text { color: #c0c0c0; text-shadow: none; }

    .heating-hero .sublabel {
        color: rgba(255,255,255,0.6);
        font-size: 13px;
        letter-spacing: 3px;
        margin-top: 8px;
        text-transform: uppercase;
    }

    .heating-outside {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid rgba(0,255,255,0.15);
        display: flex;
        justify-content: center;
        gap: 60px;
        flex-wrap: wrap;
    }
    .heating-outside .stat {
        text-align: center;
    }
    .heating-outside .stat-label {
        color: rgba(255,255,255,0.5);
        font-size: 11px;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .heating-outside .stat-val {
        font-family: var(--font-heading);
        font-size: 28px;
        color: var(--neon-cyan);
    }

    /* Datensektionen */
    .heating-section {
        width: 100%;
        max-width: 1000px;
    }
    .heating-section-title {
        color: var(--neon-cyan);
        font-size: 14px;
        letter-spacing: 4px;
        text-transform: uppercase;
        margin: 0 0 12px 4px;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(0,255,255,0.2);
    }
    .heating-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }
    .heating-card {
        background: rgba(0,0,0,0.4);
        border: 1px solid rgba(0,255,255,0.18);
        border-radius: 6px;
        padding: 18px 16px;
        text-align: center;
        transition: border-color 0.3s;
    }
    .heating-card.is-soll { border-style: dashed; }
    .heating-card.is-missing { opacity: 0.35; }
    .heating-card-label {
        color: rgba(255,255,255,0.55);
        font-size: 11px;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .heating-card-value {
        font-family: var(--font-heading);
        font-size: 32px;
        font-weight: bold;
        color: var(--neon-cyan);
    }
    .heating-card-unit {
        font-size: 14px;
        color: rgba(255,255,255,0.5);
        margin-left: 3px;
    }
    .heating-card.is-soll .heating-card-value { color: var(--neon-yellow); }
    .heating-pair {
        display: flex;
        gap: 8px;
        align-items: stretch;
    }
    .heating-pair > .heating-card { flex: 1; }

    .heating-meta {
        width: 100%;
        max-width: 1000px;
        display: flex;
        justify-content: space-between;
        font-size: 10px;
        letter-spacing: 2px;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
        padding: 6px 4px;
    }
    .heating-meta a { color: rgba(0,255,255,0.6); text-decoration: none; }
    .heating-meta a:hover { color: var(--neon-cyan); }

    .heating-error {
        background: rgba(255,0,80,0.08);
        border: 1px solid rgba(255,0,80,0.4);
        border-radius: 6px;
        padding: 16px;
        color: var(--neon-pink);
        font-size: 13px;
        max-width: 1000px;
        width: 100%;
    }
</style>

<div class="heating-grid" id="heatingGrid" data-host="<?= htmlspecialchars($initial['host'] ?? '', ENT_QUOTES) ?>">

    <?php if (!empty($initial['errors'])): ?>
        <div class="heating-error">
            FEHLER: <?= htmlspecialchars(implode(' | ', $initial['errors']), ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <!-- Hero: WP-Status -->
    <div class="heating-hero theme-<?= htmlspecialchars($theme, ENT_QUOTES) ?>" id="heatingHero">
        <div class="label">WÄRMEPUMPE</div>
        <div class="state-text" id="heatingState"><?= htmlspecialchars($wpState, ENT_QUOTES) ?></div>
        <?php if ($wpLock): ?>
            <div class="sublabel" id="heatingLock">⚠ <?= htmlspecialchars($wpLock, ENT_QUOTES) ?></div>
        <?php else: ?>
            <div class="sublabel" id="heatingLock" style="display:none;"></div>
        <?php endif; ?>

        <div class="heating-outside">
            <div class="stat">
                <div class="stat-label">Aussentemperatur</div>
                <div class="stat-val" id="heatingOutside">
                    <?= $outside !== null ? number_format($outside, 1, ',', '.') . ' °C' : '–' ?>
                </div>
            </div>
            <div class="stat">
                <div class="stat-label">Vorlauf</div>
                <div class="stat-val" id="heatingVorlauf">
                    <?= isset($data['vorlauf']) ? number_format(v($data['vorlauf']), 1, ',', '.') . ' °C' : '–' ?>
                </div>
            </div>
            <div class="stat">
                <div class="stat-label">Rücklauf HK1</div>
                <div class="stat-val" id="heatingRuecklauf">
                    <?= isset($data['ruecklauf_hk1']) ? number_format(v($data['ruecklauf_hk1']), 1, ',', '.') . ' °C' : '–' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Warmwasser -->
    <div class="heating-section">
        <h3 class="heating-section-title">Warmwasser</h3>
        <div class="heating-pair">
            <div class="heating-card<?= isset($data['warmwasser_ist']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Ist</div>
                <div class="heating-card-value" data-key="warmwasser_ist">
                    <?= isset($data['warmwasser_ist']) ? number_format(v($data['warmwasser_ist']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
            <div class="heating-card is-soll<?= isset($data['warmwasser_soll']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Soll</div>
                <div class="heating-card-value" data-key="warmwasser_soll">
                    <?= isset($data['warmwasser_soll']) ? number_format(v($data['warmwasser_soll']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Heizkreis 2 -->
    <div class="heating-section">
        <h3 class="heating-section-title">Heizkreis 2</h3>
        <div class="heating-pair">
            <div class="heating-card<?= isset($data['hk2_ist']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Ist</div>
                <div class="heating-card-value" data-key="hk2_ist">
                    <?= isset($data['hk2_ist']) ? number_format(v($data['hk2_ist']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
            <div class="heating-card is-soll<?= isset($data['hk2_soll']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Soll</div>
                <div class="heating-card-value" data-key="hk2_soll">
                    <?= isset($data['hk2_soll']) ? number_format(v($data['hk2_soll']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Heizkreis 3 -->
    <div class="heating-section">
        <h3 class="heating-section-title">Heizkreis 3</h3>
        <div class="heating-pair">
            <div class="heating-card<?= isset($data['hk3_ist']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Ist</div>
                <div class="heating-card-value" data-key="hk3_ist">
                    <?= isset($data['hk3_ist']) ? number_format(v($data['hk3_ist']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
            <div class="heating-card is-soll<?= isset($data['hk3_soll']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Soll</div>
                <div class="heating-card-value" data-key="hk3_soll">
                    <?= isset($data['hk3_soll']) ? number_format(v($data['hk3_soll']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Wärmequelle (Sole) -->
    <div class="heating-section">
        <h3 class="heating-section-title">Wärmequelle (Sole)</h3>
        <div class="heating-cards">
            <div class="heating-card<?= isset($data['quelle_ein']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Eintritt</div>
                <div class="heating-card-value" data-key="quelle_ein">
                    <?= isset($data['quelle_ein']) ? number_format(v($data['quelle_ein']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
            <div class="heating-card<?= isset($data['quelle_aus']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Austritt</div>
                <div class="heating-card-value" data-key="quelle_aus">
                    <?= isset($data['quelle_aus']) ? number_format(v($data['quelle_aus']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
            <div class="heating-card<?= isset($data['niederdruck']) ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Niederdruck</div>
                <div class="heating-card-value" data-key="niederdruck">
                    <?= isset($data['niederdruck']) ? number_format(v($data['niederdruck']), 1, ',', '.') : '–' ?><span class="heating-card-unit">bar</span>
                </div>
            </div>
            <div class="heating-card<?= isset($data['heissgas']) && v($data['heissgas']) > 0 ? '' : ' is-missing' ?>">
                <div class="heating-card-label">Heissgas</div>
                <div class="heating-card-value" data-key="heissgas">
                    <?= isset($data['heissgas']) ? number_format(v($data['heissgas']), 1, ',', '.') : '–' ?><span class="heating-card-unit">°C</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Meta -->
    <div class="heating-meta">
        <div>QUELLE: <?= htmlspecialchars($initial['host'] ?? '', ENT_QUOTES) ?> · <a href="http://<?= htmlspecialchars($initial['host'] ?? '', ENT_QUOTES) ?>/http/index/j_index.html" target="_blank">CAREL pCOWeb ↗</a></div>
        <div id="heatingTs"><?= htmlspecialchars(date('H:i:s'), ENT_QUOTES) ?></div>
    </div>
</div>

<script>
(function() {
    const grid = document.getElementById('heatingGrid');
    if (!grid) return;
    const host = grid.dataset.host;

    const themeMap = {
        0: 'idle', 1: 'heat', 2: 'heat', 3: 'heat',
        4: 'water', 5: 'cool', 10: 'defrost', 30: 'lock'
    };

    function fmt(v) {
        if (v === null || v === undefined) return '–';
        return Number(v).toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    }

    function update(data) {
        if (!data) return;

        const hero = document.getElementById('heatingHero');
        const stateEl = document.getElementById('heatingState');
        const lockEl  = document.getElementById('heatingLock');
        const code = data.wp_status ? data.wp_status.state_code : null;
        if (stateEl) stateEl.textContent = data.wp_status ? data.wp_status.state : 'UNBEKANNT';
        if (hero) {
            hero.className = 'heating-hero theme-' + (themeMap[code] || 'unknown');
        }
        if (lockEl) {
            if (data.wp_status && data.wp_status.lock_reason) {
                lockEl.textContent = '⚠ ' + data.wp_status.lock_reason;
                lockEl.style.display = '';
            } else {
                lockEl.style.display = 'none';
            }
        }

        const outside = document.getElementById('heatingOutside');
        if (outside) outside.textContent = (data.outside_temp !== null && data.outside_temp !== undefined) ? (fmt(data.outside_temp) + ' °C') : '–';

        const dataMap = data.data || {};
        // Hero sub-stats
        const vl = document.getElementById('heatingVorlauf');
        if (vl) vl.textContent = dataMap.vorlauf ? (fmt(dataMap.vorlauf.value) + ' °C') : '–';
        const rl = document.getElementById('heatingRuecklauf');
        if (rl) rl.textContent = dataMap.ruecklauf_hk1 ? (fmt(dataMap.ruecklauf_hk1.value) + ' °C') : '–';

        // Cards
        document.querySelectorAll('.heating-card-value[data-key]').forEach(el => {
            const key = el.dataset.key;
            const p = dataMap[key];
            const unitSpan = el.querySelector('.heating-card-unit');
            const unitText = unitSpan ? unitSpan.outerHTML : '';
            if (p && p.value !== null && p.value !== undefined) {
                el.innerHTML = fmt(p.value) + unitText;
                const card = el.closest('.heating-card');
                if (card) card.classList.remove('is-missing');
            } else {
                el.innerHTML = '–' + unitText;
            }
        });

        const ts = document.getElementById('heatingTs');
        if (ts) ts.textContent = new Date().toLocaleTimeString('de-DE', { hour12: false });
    }

    async function refresh() {
        try {
            const res = await fetch('api/heating.php?_=' + Date.now(), { cache: 'no-store' });
            if (!res.ok) return;
            const json = await res.json();
            update(json);
        } catch (e) {
            // still keep last values
        }
    }

    setInterval(refresh, 30000); // alle 30s
})();
</script>
