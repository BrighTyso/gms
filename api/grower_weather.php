<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Grower Weather (Yesterday)</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root {
    --bg:#0a0f0a; --surface:#111a11; --border:#1f2e1f;
    --green:#3ddc68; --green-dim:#1a5e30; --amber:#f5a623;
    --red:#e84040; --blue:#4a9eff; --purple:#b47eff;
    --text:#c8e6c9; --muted:#4a6b4a; --radius:6px;
  }
  html,body { min-height:100%; font-family:'Space Mono',monospace; background:var(--bg); color:var(--text); }

  header {
    display:flex; align-items:center; gap:12px; padding:0 20px; height:56px;
    background:var(--surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100;
  }
  .logo { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--green); }
  .logo span { color:var(--muted); }
  .back { font-size:11px; color:var(--muted); text-decoration:none; border:1px solid var(--border); padding:4px 10px; border-radius:4px; }
  .back:hover { color:var(--green); border-color:var(--green); }
  select, input {
    background:var(--surface); border:1px solid var(--border); color:var(--text);
    font-family:'Space Mono',monospace; font-size:11px; padding:4px 8px; border-radius:4px;
  }
  .hdr-stat { font-size:10px; color:var(--muted); }
  .hdr-stat b { color:var(--text); }

  .content { padding:20px; max-width:1400px; margin:0 auto; }

  /* ── Summary cards ── */
  .summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:24px; }
  .sum-card {
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    padding:14px 16px;
  }
  .sum-label { font-size:9px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
  .sum-value { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; margin-top:4px; }
  .sum-sub   { font-size:10px; color:var(--muted); margin-top:2px; }

  /* ── Tab bar ── */
  .tab-bar { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:20px; }
  .tab {
    font-family:'Space Mono',monospace; font-size:11px; padding:8px 16px; cursor:pointer;
    border:none; background:transparent; color:var(--muted); border-bottom:2px solid transparent;
    transition:all .2s;
  }
  .tab.active { color:var(--green); border-bottom-color:var(--green); }
  .tab:hover  { color:var(--text); }

  .tab-content { display:none; }
  .tab-content.active { display:block; }

  /* ── Search ── */
  .search-row { display:flex; gap:8px; margin-bottom:16px; align-items:center; }
  #growerSearch { flex:1; max-width:300px; }

  /* ── Daily weather table ── */
  .weather-table { width:100%; border-collapse:collapse; font-size:11px; }
  .weather-table th {
    text-align:left; padding:8px 12px; font-size:9px; text-transform:uppercase;
    letter-spacing:.5px; color:var(--muted); border-bottom:1px solid var(--border);
    background:var(--surface); position:sticky; top:56px;
  }
  .weather-table td { padding:8px 12px; border-bottom:1px solid #0f1a0f; }
  .weather-table tr:hover td { background:rgba(61,220,104,.03); }

  .temp-chip {
    display:inline-flex; align-items:center; gap:4px;
    background:#001020; border:1px solid #003050;
    border-radius:3px; padding:2px 6px; font-size:10px; color:var(--blue);
  }
  .rain-chip {
    display:inline-flex; align-items:center; gap:3px;
    background:#001828; border:1px solid #003050;
    border-radius:3px; padding:2px 6px; font-size:10px; color:#7ec8ff;
  }
  .rain-chip.heavy { background:#200010; border-color:#500030; color:#ff7eb3; }
  .hum-chip  { font-size:10px; color:var(--muted); }
  .wind-chip { font-size:10px; color:var(--muted); }
  .no-rain   { font-size:10px; color:var(--muted); }

  /* ── Accumulated weather table ── */
  .accum-bar-wrap { display:flex; align-items:center; gap:8px; }
  .accum-bar      { flex:1; height:6px; background:var(--border); border-radius:3px; max-width:120px; }
  .accum-fill     { height:100%; border-radius:3px; background:var(--blue); }
  .accum-fill.rain { background:var(--blue); }
  .accum-fill.temp { background:var(--amber); }

  /* ── Grower detail panel ── */
  .grower-detail {
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    padding:16px; margin-bottom:20px; display:none;
  }
  .grower-detail.visible { display:block; }
  .gd-name { font-family:'Syne',sans-serif; font-size:16px; font-weight:800; color:var(--green); margin-bottom:12px; }
  .gd-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .gd-section-title { font-size:9px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); margin-bottom:8px; }

  /* Mini chart bars */
  .mini-chart { display:flex; align-items:flex-end; gap:2px; height:40px; margin-top:6px; }
  .mc-bar { flex:1; min-width:6px; border-radius:2px 2px 0 0; position:relative; cursor:pointer; }
  .mc-bar:hover::after {
    content: attr(data-tip);
    position:absolute; bottom:100%; left:50%; transform:translateX(-50%);
    background:#000; color:#fff; font-size:9px; padding:2px 5px;
    border-radius:3px; white-space:nowrap; z-index:10;
  }

  .empty-row td { text-align:center; color:var(--muted); padding:20px; }
</style>
</head>
<body>

<?php
require "conn.php";
require "validate.php";

$searchGrower = trim($_GET['grower'] ?? '');
$days         = isset($_GET['days']) ? min((int)$_GET['days'], 90) : 7;
$viewGrowerId = isset($_GET['grower_id']) ? (int)$_GET['grower_id'] : 0;

// ── Current season ────────────────────────────────────────────────────────────
$currentSeasonId = 0;
$sr = $conn->query("SELECT id FROM seasons ORDER BY id DESC LIMIT 1");
if ($sr && $row = $sr->fetch_assoc()) { $currentSeasonId = (int)$row['id']; $sr->free(); }

// ── Season summary stats ──────────────────────────────────────────────────────
$stats = ['avg_temp'=>0,'max_rain'=>0,'total_growers'=>0,'avg_humidity'=>0];
$sr = $conn->query("
    SELECT
        ROUND(AVG(temp),1)     AS avg_temp,
        ROUND(MAX(rain),1)     AS max_rain,
        COUNT(DISTINCT growerid) AS total_growers,
        ROUND(AVG(humidity),0) AS avg_humidity
    FROM weather
    WHERE seasonid = $currentSeasonId
      AND DATE(datetime) = CURDATE() - INTERVAL 1 DAY
");
if ($sr && $row = $sr->fetch_assoc()) { $stats = $row; $sr->free(); }

// ── Yesterday's daily weather per grower ─────────────────────────────────────────
$whereGrower = $searchGrower ? "AND (g.name LIKE '%".mysqli_real_escape_string($conn,$searchGrower)."%' OR g.surname LIKE '%".mysqli_real_escape_string($conn,$searchGrower)."%' OR g.grower_num LIKE '%".mysqli_real_escape_string($conn,$searchGrower)."%')" : '';

$dailyWeather = [];
$dwr = $conn->query("
    SELECT
        g.id AS grower_id, g.grower_num, g.name, g.surname,
        w.temp, w.temp_min, w.temp_max,
        w.humidity, w.rain, w.wind_speed, w.clouds, w.pressure,
        w.city, w.datetime, w.dt
    FROM weather w
    JOIN growers g ON g.id = w.growerid
    INNER JOIN (
        SELECT growerid, MAX(datetime) AS max_dt
        FROM weather
        WHERE DATE(datetime) = CURDATE() - INTERVAL 1 DAY
          AND seasonid = $currentSeasonId
        GROUP BY growerid
    ) latest ON w.growerid = latest.growerid AND w.datetime = latest.max_dt
    WHERE w.seasonid = $currentSeasonId $whereGrower
    ORDER BY g.name, g.surname
");
if ($dwr) { while ($row = $dwr->fetch_assoc()) $dailyWeather[] = $row; $dwr->free(); }

// ── Accumulated weather per grower ───────────────────────────────────────────
$accumWeather = [];
$awr = $conn->query("
    SELECT
        g.id AS grower_id, g.grower_num, g.name, g.surname,
        wt.temp, wt.temp_min, wt.temp_max,
        wt.humidity, wt.rain, wt.wind_speed, wt.clouds,
        wt.datetime,
        ld.last_weather_date
    FROM grower_weather_total wt
    JOIN growers g ON g.id = wt.growerid
    INNER JOIN (
        SELECT growerid, MAX(datetime) AS max_dt
        FROM grower_weather_total
        WHERE seasonid = $currentSeasonId
        GROUP BY growerid
    ) latest ON wt.growerid = latest.growerid AND wt.datetime = latest.max_dt
    LEFT JOIN (
        SELECT growerid, MAX(datetime) AS last_weather_date
        FROM weather
        WHERE seasonid = $currentSeasonId
        GROUP BY growerid
    ) ld ON ld.growerid = wt.growerid
    WHERE wt.seasonid = $currentSeasonId $whereGrower
    ORDER BY wt.rain DESC
");
if ($awr) { while ($row = $awr->fetch_assoc()) $accumWeather[] = $row; $awr->free(); }

// ── Grower detail: last N days weather history ────────────────────────────────
$growerHistory  = [];
$growerAccumHistory = [];
$growerInfo     = null;
if ($viewGrowerId) {
    $gr = $conn->query("SELECT id, grower_num, name, surname FROM growers WHERE id = $viewGrowerId LIMIT 1");
    if ($gr && $row = $gr->fetch_assoc()) { $growerInfo = $row; $gr->free(); }

    $hr = $conn->query("
        SELECT DATE(datetime) AS day, temp, temp_min, temp_max,
               humidity, rain, wind_speed, clouds
        FROM weather
        WHERE growerid = $viewGrowerId
          AND seasonid = $currentSeasonId
          AND datetime >= NOW() - INTERVAL $days DAY
        ORDER BY datetime DESC
        LIMIT 30
    ");
    if ($hr) { while ($row = $hr->fetch_assoc()) $growerHistory[] = $row; $hr->free(); }

    $ar = $conn->query("
        SELECT DATE(datetime) AS day, rain, temp_max, temp_min, humidity
        FROM grower_weather_total
        WHERE growerid = $viewGrowerId
          AND seasonid = $currentSeasonId
        ORDER BY datetime DESC
        LIMIT 1
    ");
    if ($ar && $row = $ar->fetch_assoc()) { $growerAccumHistory = $row; $ar->free(); }
}

$conn->close();

// Max rain for bar scaling
$maxRainAccum = max(1, max(array_column($accumWeather, 'rain') ?: [1]));
$maxRainDaily = max(1, max(array_column($dailyWeather, 'rain') ?: [1]));
?>

<header>
  <div class="logo">GMS<span>/</span>Weather</div>
  <a href="device_tracker.php" class="back">← Tracker</a>
  <form method="GET" style="display:flex;gap:8px;align-items:center">
    <input type="text" name="grower" id="growerSearch" placeholder="Search grower..." value="<?= htmlspecialchars($searchGrower) ?>">
    <select name="days" onchange="this.form.submit()">
      <option value="7"  <?= $days==7  ?'selected':'' ?>>Last 7 days</option>
      <option value="14" <?= $days==14 ?'selected':'' ?>>Last 14 days</option>
      <option value="30" <?= $days==30 ?'selected':'' ?>>Last 30 days</option>
      <option value="90" <?= $days==90 ?'selected':'' ?>>Season (90d)</option>
    </select>
    <button type="submit" style="background:var(--green-dim);border:1px solid var(--green);color:var(--green);font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;cursor:pointer">Search</button>
  </form>
  <div style="margin-left:auto;display:flex;gap:16px">
    <div class="hdr-stat">Yesterday: <b style="color:var(--blue)"><?= count($dailyWeather) ?></b> growers</div>
    <div class="hdr-stat">Avg Temp: <b style="color:var(--amber)"><?= $stats['avg_temp'] ?>°C</b></div>
    <div class="hdr-stat">Avg Humidity: <b style="color:var(--blue)"><?= $stats['avg_humidity'] ?>%</b></div>
    <div class="hdr-stat">Max Rain: <b style="color:var(--blue)"><?= $stats['max_rain'] ?>mm</b></div>
  </div>
</header>

<div class="content">

  <?php if ($growerInfo): ?>
  <!-- Grower detail panel -->
  <div class="grower-detail visible">
    <div class="gd-name">
      <?= htmlspecialchars($growerInfo['name'].' '.$growerInfo['surname']) ?>
      <span style="color:var(--muted);font-size:12px"> #<?= $growerInfo['grower_num'] ?></span>
      <a href="?days=<?= $days ?>" style="font-size:11px;color:var(--muted);margin-left:12px;text-decoration:none">✕ Close</a>
    </div>
    <div class="gd-grid">
      <div>
        <div class="gd-section-title">📅 Last <?= $days ?> days — Daily Weather</div>
        <?php if (!empty($growerHistory)): ?>
        <div class="mini-chart">
          <?php
          $maxH = max(1, max(array_column($growerHistory, 'rain') ?: [1]));
          foreach (array_reverse($growerHistory) as $h):
            $h = (float)$h['rain'];
            $pct = min(100, round(($h / $maxH) * 100));
          ?>
          <div class="mc-bar" style="height:<?= max(4,$pct) ?>%;background:var(--blue);opacity:.7"
               data-tip="<?= $h ?>mm rain"></div>
          <?php endforeach ?>
        </div>
        <div style="font-size:9px;color:var(--muted);margin-top:4px">Rainfall (mm) per day</div>
        <table class="weather-table" style="margin-top:12px">
          <tr><th>Date</th><th>Temp</th><th>Rain</th><th>Humidity</th><th>Wind</th></tr>
          <?php foreach ($growerHistory as $h): ?>
          <tr>
            <td style="color:var(--muted)"><?= $h['day'] ?></td>
            <td><span class="temp-chip">🌡 <?= round($h['temp'],1) ?>°C (<?= round($h['temp_min'],1) ?>–<?= round($h['temp_max'],1) ?>)</span></td>
            <td><?php if ($h['rain'] > 0): ?><span class="rain-chip <?= $h['rain']>20?'heavy':'' ?>">🌧 <?= round($h['rain'],1) ?>mm</span><?php else: ?><span class="no-rain">—</span><?php endif ?></td>
            <td><span class="hum-chip">💧<?= round($h['humidity'],0) ?>%</span></td>
            <td><span class="wind-chip">💨<?= round($h['wind_speed'],1) ?>m/s</span></td>
          </tr>
          <?php endforeach ?>
        </table>
        <?php else: ?>
        <div style="color:var(--muted);font-size:11px">No history available</div>
        <?php endif ?>
      </div>
      <div>
        <div class="gd-section-title">📊 Season Accumulated Totals</div>
        <?php if ($growerAccumHistory): ?>
        <table class="weather-table">
          <tr><th>Metric</th><th>Season Total / Avg</th></tr>
          <tr><td>🌧 Total Rain</td><td><b style="color:var(--blue)"><?= round($growerAccumHistory['rain'],1) ?>mm</b></td></tr>
          <tr><td>🌡 Max Temp</td><td><b style="color:var(--amber)"><?= round($growerAccumHistory['temp_max'],1) ?>°C</b></td></tr>
          <tr><td>🌡 Min Temp</td><td><b style="color:var(--blue)"><?= round($growerAccumHistory['temp_min'],1) ?>°C</b></td></tr>
          <tr><td>💧 Avg Humidity</td><td><b style="color:var(--blue)"><?= round($growerAccumHistory['humidity'],0) ?>%</b></td></tr>
        </table>
        <?php else: ?>
        <div style="color:var(--muted);font-size:11px">No accumulated data available</div>
        <?php endif ?>
      </div>
    </div>
  </div>
  <?php endif ?>

  <!-- Summary stats -->
  <div class="summary-grid">
    <div class="sum-card">
      <div class="sum-label">Growers with weather yesterday</div>
      <div class="sum-value" style="color:var(--green)"><?= count($dailyWeather) ?></div>
      <div class="sum-sub">of <?= $stats['total_growers'] ?> total</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Avg Temperature yesterday</div>
      <div class="sum-value" style="color:var(--amber)"><?= $stats['avg_temp'] ?>°C</div>
      <div class="sum-sub">across all growers yesterday</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Avg Humidity yesterday</div>
      <div class="sum-value" style="color:var(--blue)"><?= $stats['avg_humidity'] ?>%</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Highest rain yesterday</div>
      <div class="sum-value" style="color:var(--blue)"><?= $stats['max_rain'] ?>mm</div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Growers with rain yesterday</div>
      <div class="sum-value" style="color:var(--blue)"><?= count(array_filter($dailyWeather, fn($w) => (float)$w['rain'] > 0)) ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-label">Heavy rain (>20mm)</div>
      <div class="sum-value" style="color:var(--red)"><?= count(array_filter($dailyWeather, fn($w) => (float)$w['rain'] > 20)) ?></div>
      <div class="sum-sub">growers affected</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tab-bar">
    <button class="tab active" onclick="switchTab('daily',this)">📅 Yesterday's Weather</button>
    <button class="tab"        onclick="switchTab('accum',this)">📊 Season Accumulated</button>
  </div>

  <!-- Tab: Daily Weather -->
  <div id="tab-daily" class="tab-content active">
    <table class="weather-table">
      <thead>
        <tr>
          <th>Grower</th>
          <th>City</th>
          <th>Temperature</th>
          <th>Rain</th>
          <th>Humidity</th>
          <th>Wind</th>
          <th>Clouds</th>
          <th>Updated</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($dailyWeather)): ?>
        <tr class="empty-row"><td colspan="9">No weather data for yesterday</td></tr>
      <?php else: foreach ($dailyWeather as $w): ?>
        <tr>
          <td>
            <b><?= htmlspecialchars($w['name'].' '.$w['surname']) ?></b>
            <span style="color:var(--muted);font-size:9px"> #<?= $w['grower_num'] ?></span>
          </td>
          <td style="color:var(--muted)"><?= htmlspecialchars($w['city'] ?? '—') ?></td>
          <td>
            <span class="temp-chip">
              🌡 <?= round($w['temp'],1) ?>°C
              <span style="color:var(--muted)">(<?= round($w['temp_min'],1) ?>–<?= round($w['temp_max'],1) ?>)</span>
            </span>
          </td>
          <td>
            <?php if ((float)$w['rain'] > 0): ?>
              <span class="rain-chip <?= (float)$w['rain']>20?'heavy':'' ?>">
                🌧 <?= round($w['rain'],1) ?>mm
              </span>
            <?php else: ?>
              <span class="no-rain">No rain</span>
            <?php endif ?>
          </td>
          <td><span class="hum-chip">💧 <?= round($w['humidity'],0) ?>%</span></td>
          <td><span class="wind-chip">💨 <?= round($w['wind_speed'],1) ?>m/s</span></td>
          <td>
            <div style="display:flex;align-items:center;gap:4px">
              <div style="width:40px;height:4px;background:var(--border);border-radius:2px">
                <div style="width:<?= min(100,round($w['clouds'])) ?>%;height:100%;background:var(--muted);border-radius:2px"></div>
              </div>
              <span style="font-size:10px;color:var(--muted)"><?= round($w['clouds'],0) ?>%</span>
            </div>
          </td>
          <td style="color:var(--muted);font-size:10px"><?= date('H:i', strtotime($w['datetime'])) ?></td>
          <td>
            <a href="?grower_id=<?= $w['grower_id'] ?>&days=<?= $days ?><?= $searchGrower?'&grower='.urlencode($searchGrower):'' ?>"
               style="font-size:10px;color:var(--green);text-decoration:none">Detail →</a>
          </td>
        </tr>
      <?php endforeach; endif ?>
      </tbody>
    </table>
  </div>

  <!-- Tab: Accumulated -->
  <div id="tab-accum" class="tab-content">
    <table class="weather-table">
      <thead>
        <tr>
          <th>Grower</th>
          <th>Season Rain</th>
          <th>Max Temp</th>
          <th>Min Temp</th>
          <th>Avg Humidity</th>
          <th>Wind</th>
          <th>Last Updated</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($accumWeather)): ?>
        <tr class="empty-row"><td colspan="8">No accumulated weather data</td></tr>
      <?php else: foreach ($accumWeather as $w): ?>
        <tr>
          <td>
            <b><?= htmlspecialchars($w['name'].' '.$w['surname']) ?></b>
            <span style="color:var(--muted);font-size:9px"> #<?= $w['grower_num'] ?></span>
          </td>
          <td>
            <div class="accum-bar-wrap">
              <div class="accum-bar">
                <div class="accum-fill rain" style="width:<?= min(100,round(((float)$w['rain']/$maxRainAccum)*100)) ?>%"></div>
              </div>
              <span style="color:var(--blue);font-size:11px;font-weight:700"><?= round($w['rain'],1) ?>mm</span>
            </div>
          </td>
          <td><span style="color:var(--amber)"><?= round($w['temp_max'],1) ?>°C</span></td>
          <td><span style="color:var(--blue)"><?= round($w['temp_min'],1) ?>°C</span></td>
          <td><span class="hum-chip">💧 <?= round($w['humidity'],0) ?>%</span></td>
          <td><span class="wind-chip">💨 <?= round($w['wind_speed'],1) ?>m/s</span></td>
          <td style="color:var(--muted);font-size:10px"><?= date('d M Y', strtotime($w['last_weather_date'] ?? $w['datetime'])) ?></td>
          <td>
            <a href="?grower_id=<?= $w['grower_id'] ?>&days=<?= $days ?><?= $searchGrower?'&grower='.urlencode($searchGrower):'' ?>"
               style="font-size:10px;color:var(--green);text-decoration:none">Detail →</a>
          </td>
        </tr>
      <?php endforeach; endif ?>
      </tbody>
    </table>
  </div>

</div>

<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-' + name).classList.add('active');
}
</script>
</body>
</html>