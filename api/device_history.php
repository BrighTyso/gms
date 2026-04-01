<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Location History</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root {
    --bg:#0a0f0a; --surface:#111a11; --border:#1f2e1f;
    --green:#3ddc68; --amber:#f5a623; --red:#e84040; --blue:#4a9eff;
    --text:#c8e6c9; --muted:#4a6b4a;
  }
  html, body { height:100%; font-family:'Space Mono',monospace; background:var(--bg); color:var(--text); }
  .shell { display:grid; grid-template-rows:56px 1fr; height:100vh; }

  header {
    display:flex; align-items:center; gap:10px; padding:0 20px;
    background:var(--surface); border-bottom:1px solid var(--border); flex-wrap:wrap;
  }
  .logo { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--green); }
  .logo span { color:var(--muted); }
  .back {
    font-family:'Space Mono',monospace; font-size:11px; text-decoration:none;
    background:transparent; border:1px solid var(--border); color:var(--muted);
    padding:5px 12px; border-radius:4px; cursor:pointer;
  }
  .back:hover { border-color:var(--green); color:var(--green); }
  .hdr-info { font-size:11px; color:var(--muted); }
  .hdr-info b { color:var(--text); }
  select {
    background:var(--surface); border:1px solid var(--border); color:var(--text);
    padding:4px 8px; font-family:'Space Mono',monospace; font-size:11px; border-radius:4px;
  }
  .legend { display:flex; gap:10px; margin-left:auto; align-items:center; font-size:10px; color:var(--muted); flex-wrap:wrap; }
  .li { display:flex; align-items:center; gap:4px; }
  .ld { width:10px; height:10px; border-radius:50%; }

  .replay-bar {
    position:absolute; bottom:20px; left:50%; transform:translateX(-50%); z-index:1000;
    background:var(--surface); border:1px solid var(--border); border-radius:8px;
    padding:10px 16px; display:flex; align-items:center; gap:12px;
    box-shadow:0 4px 20px rgba(0,0,0,.7); min-width:340px; max-width:92vw;
  }
  .replay-btn {
    font-family:'Space Mono',monospace; font-size:11px; cursor:pointer;
    background:var(--green-dim); border:1px solid var(--green); color:var(--green);
    padding:5px 12px; border-radius:4px; white-space:nowrap;
  }
  .replay-btn:hover { background:#1e4a22; }
  .replay-btn.stop  { background:#200000; border-color:var(--red); color:var(--red); }
  .replay-speed {
    font-family:'Space Mono',monospace; font-size:10px;
    background:var(--surface); border:1px solid var(--border);
    color:var(--text); padding:4px 6px; border-radius:4px;
  }
  .replay-progress { flex:1; height:3px; background:var(--border); border-radius:2px; cursor:pointer; min-width:60px; }
  .replay-fill     { height:100%; background:var(--green); border-radius:2px; width:0%; transition:width .1s; }
  .replay-label    { font-size:10px; color:var(--muted); white-space:nowrap; min-width:80px; text-align:right; }

  /* Floating info card shown during replay */
  #replay-info {
    position:absolute; bottom:78px; left:50%; transform:translateX(-50%); z-index:1000;
    background:var(--surface); border:1px solid var(--border); border-radius:8px;
    padding:10px 16px; display:none;
    box-shadow:0 4px 20px rgba(0,0,0,.7); min-width:300px; max-width:92vw;
  }
  #replay-info.visible { display:block; }
  .ri-grid {
    display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; text-align:center;
  }
  .ri-cell { display:flex; flex-direction:column; gap:2px; }
  .ri-label { font-size:9px; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); }
  .ri-value { font-size:14px; font-weight:700; font-family:'Syne',sans-serif; }
  .ri-divider { border:none; border-top:1px solid var(--border); margin:8px 0; }
  .ri-row { display:flex; justify-content:space-between; font-size:10px; margin-top:4px; }
  .ri-row span { color:var(--muted); }
  .ri-row b { color:var(--text); }

  #map { width:100%; height:100%; }
</style>
</head>
<body>

<?php
$device_id = isset($_GET['device_id']) ? trim($_GET['device_id']) : '';
$hours     = isset($_GET['hours'])     ? min((int)$_GET['hours'], 168) : 24;

$points  = [];
$officer = 'Unknown';

if ($device_id) {
    // ── DB connection ─────────────────────────────────────────────────────────
    require "conn.php";
require "validate.php";

    $stmt = $conn->prepare("
        SELECT dl.latitude, dl.longitude, dl.accuracy, dl.battery_level,
               dl.source, dl.created_at,
               fo.name AS officer_name
        FROM device_locations dl
        LEFT JOIN field_officers fo ON dl.officer_id = fo.id
        WHERE dl.device_id = ?
          AND dl.created_at >= NOW() - INTERVAL ? HOUR
        ORDER BY dl.created_at ASC
    ");

    if ($stmt) {
        $stmt->bind_param('si', $device_id, $hours);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $points[] = $row;
        }

        $stmt->close();
    }

    $conn->close();

    if (!empty($points[0]['officer_name'])) {
        $officer = $points[0]['officer_name'];
    }
}

$cLive  = count(array_filter($points, fn($p) => $p['source'] === 'realtime'));
$cSms   = count(array_filter($points, fn($p) => $p['source'] === 'sms'));
$cQueue = count(array_filter($points, fn($p) => $p['source'] === 'offline_queue'));
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>History</div>
    <a href="device_tracker.php" class="back">← Back</a>
    <div class="hdr-info">
      <b><?= htmlspecialchars($officer) ?></b>
      &nbsp;·&nbsp; …<?= htmlspecialchars(substr($device_id, -8)) ?>
      &nbsp;·&nbsp; <?= count($points) ?> pings
    </div>
    <select onchange="location.href='?device_id=<?= urlencode($device_id) ?>&hours='+this.value">
      <option value="6"   <?= $hours == 6   ? 'selected' : '' ?>>Last 6h</option>
      <option value="24"  <?= $hours == 24  ? 'selected' : '' ?>>Last 24h</option>
      <option value="48"  <?= $hours == 48  ? 'selected' : '' ?>>Last 48h</option>
      <option value="168" <?= $hours == 168 ? 'selected' : '' ?>>Last 7 days</option>
    </select>
    <div class="legend">
      <?php if ($cLive  > 0): ?><div class="li"><div class="ld" style="background:var(--green)"></div>Live (<?= $cLive ?>)</div><?php endif ?>
      <?php if ($cSms   > 0): ?><div class="li"><div class="ld" style="background:var(--blue)"></div>SMS (<?= $cSms ?>)</div><?php endif ?>
      <?php if ($cQueue > 0): ?><div class="li"><div class="ld" style="background:var(--amber)"></div>Queued (<?= $cQueue ?>)</div><?php endif ?>
    </div>
  </header>
  <div id="map"></div>
  <?php if (!empty($points)): ?>
  <!-- Floating info card shown during replay -->
  <div id="replay-info">
    <div class="ri-grid">
      <div class="ri-cell">
        <div class="ri-label">Time</div>
        <div class="ri-value" id="ri-time" style="color:var(--green)">—</div>
      </div>
      <div class="ri-cell">
        <div class="ri-label">Step dist</div>
        <div class="ri-value" id="ri-step-dist" style="color:var(--amber)">—</div>
      </div>
      <div class="ri-cell">
        <div class="ri-label">Total dist</div>
        <div class="ri-value" id="ri-total-dist" style="color:var(--blue)">—</div>
      </div>
    </div>
    <hr class="ri-divider">
    <div class="ri-row">
      <span>Time from prev</span><b id="ri-elapsed">—</b>
    </div>
    <div class="ri-row">
      <span>Speed (est)</span><b id="ri-speed">—</b>
    </div>
    <div class="ri-row">
      <span>Battery</span><b id="ri-batt">—</b>
    </div>
    <div class="ri-row">
      <span>Source</span><b id="ri-src">—</b>
    </div>
  </div>

  <div class="replay-bar" id="replayBar">
    <button class="replay-btn" id="replayBtn" onclick="toggleReplay()">▶ Play</button>
    <div class="replay-progress" id="replayProgress" onclick="seekReplay(event)">
      <div class="replay-fill" id="replayFill"></div>
    </div>
    <select class="replay-speed" id="replaySpeed">
      <option value="800">1×</option>
      <option value="400">2×</option>
      <option value="150">5×</option>
      <option value="50">10×</option>
    </select>
    <div class="replay-label" id="replayLabel">0 / <?= count($points) ?></div>
  </div>
  <?php endif ?>
</div>

<script>
const points = <?= json_encode($points) ?>;

const map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
  { attribution: '© OpenStreetMap © CARTO', maxZoom: 19 }).addTo(map);

function srcColor(s) { return s==='sms'?'#4a9eff':s==='offline_queue'?'#f5a623':'#3ddc68'; }
function srcLabel(s) { return s==='sms'?'📱 SMS':s==='offline_queue'?'📦 Queued':'🟢 Live'; }

// ── Haversine distance (km) ───────────────────────────────────────────────────
function hdist(lat1,lng1,lat2,lng2) {
  const R=6371, r=x=>x*Math.PI/180;
  const dLa=r(lat2-lat1), dLo=r(lng2-lng1);
  const a=Math.sin(dLa/2)**2+Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dLo/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function fmtDist(km) {
  return km < 1 ? Math.round(km*1000)+'m' : km.toFixed(2)+'km';
}

function fmtElapsed(ms) {
  if (ms < 0) return '—';
  const s = Math.floor(ms/1000), m = Math.floor(s/60), h = Math.floor(m/60);
  if (h > 0)  return h+'h '+(m%60)+'m';
  if (m > 0)  return m+'m '+(s%60)+'s';
  return s+'s';
}

// Pre-calculate cumulative distances and timestamps
const cumDist = [0]; // cumDist[i] = total distance from point 0 to point i
for (let i = 1; i < points.length; i++) {
  const prev = points[i-1], cur = points[i];
  const d = hdist(
    parseFloat(prev.latitude), parseFloat(prev.longitude),
    parseFloat(cur.latitude),  parseFloat(cur.longitude)
  );
  cumDist.push(cumDist[i-1] + d);
}

// ── Static route line ─────────────────────────────────────────────────────────
let replayMarker = null, replayTimer = null, replayIdx = 0, isPlaying = false;

if (points.length > 0) {
  const ll = points.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
  L.polyline(ll, { color:'#3ddc68', weight:2, opacity:.3, dashArray:'4 4' }).addTo(map);

  // Static start/end markers
  points.forEach((p, i) => {
    const first = i===0, last = i===points.length-1;
    if (!first && !last) return;
    const c = srcColor(p.source);
    L.circleMarker([parseFloat(p.latitude), parseFloat(p.longitude)], {
      radius:9, color:c, fillColor:c, fillOpacity:1, weight:2
    }).addTo(map).bindPopup(
      `<b>${first?'🚩 Start':'📍 End'}</b><br>${p.created_at}<br>` +
      `Battery: ${p.battery_level!=null?p.battery_level+'%':'—'}<br>${srcLabel(p.source)}`
    );
  });

  map.fitBounds(ll, { padding:[40,40] });

  const replayIcon = L.divIcon({
    className:'',
    html:`<div style="width:28px;height:28px;border-radius:50%;background:#0d200d;border:2px solid #3ddc68;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 0 12px rgba(61,220,104,.6)">🟢</div>`,
    iconSize:[28,28], iconAnchor:[14,14]
  });
  replayMarker = L.marker([parseFloat(points[0].latitude), parseFloat(points[0].longitude)], {icon:replayIcon})
    .addTo(map).bindPopup('');

  updateReplayLabel(0);
} else {
  map.setView([-17.8292, 31.0522], 12);
}

// ── Replay controls ───────────────────────────────────────────────────────────
function toggleReplay() {
  if (!points.length) return;
  isPlaying = !isPlaying;
  const btn = document.getElementById('replayBtn');
  btn.textContent = isPlaying ? '⏸ Pause' : '▶ Play';
  btn.className   = isPlaying ? 'replay-btn stop' : 'replay-btn';
  if (isPlaying) {
    if (replayIdx >= points.length - 1) replayIdx = 0;
    document.getElementById('replay-info').classList.add('visible');
    stepReplay();
  } else {
    clearTimeout(replayTimer);
  }
}

function stepReplay() {
  if (!isPlaying || replayIdx >= points.length) {
    isPlaying = false;
    document.getElementById('replayBtn').textContent = '▶ Play';
    document.getElementById('replayBtn').className   = 'replay-btn';
    return;
  }

  const p   = points[replayIdx];
  const lat = parseFloat(p.latitude);
  const lng = parseFloat(p.longitude);
  const c   = srcColor(p.source);

  // Step distance from previous point
  const stepDist = replayIdx > 0
    ? hdist(parseFloat(points[replayIdx-1].latitude), parseFloat(points[replayIdx-1].longitude), lat, lng)
    : 0;

  // Time elapsed from previous point
  let elapsedMs = -1;
  if (replayIdx > 0) {
    const prevTime = new Date(points[replayIdx-1].created_at).getTime();
    const curTime  = new Date(p.created_at).getTime();
    elapsedMs      = curTime - prevTime;
  }

  // Estimated speed (km/h)
  let speedStr = '—';
  if (elapsedMs > 0 && stepDist > 0) {
    const hours = elapsedMs / 3600000;
    const kmh   = stepDist / hours;
    speedStr    = kmh < 1 ? '<1 km/h' : Math.round(kmh) + ' km/h';
  }

  // Update info card
  const time = p.created_at.split(' ')[1]?.substring(0,5) || '';
  document.getElementById('ri-time').textContent       = time;
  document.getElementById('ri-step-dist').textContent  = replayIdx > 0 ? fmtDist(stepDist) : '—';
  document.getElementById('ri-total-dist').textContent = fmtDist(cumDist[replayIdx]);
  document.getElementById('ri-elapsed').textContent    = fmtElapsed(elapsedMs);
  document.getElementById('ri-speed').textContent      = speedStr;
  document.getElementById('ri-batt').textContent       = p.battery_level != null ? p.battery_level+'%' : '—';
  document.getElementById('ri-src').textContent        = srcLabel(p.source);

  replayMarker.setLatLng([lat, lng]);
  replayMarker.setPopupContent(
    `<b>${time}</b> · ${srcLabel(p.source)}<br>` +
    `Step: ${fmtDist(stepDist)} · Total: ${fmtDist(cumDist[replayIdx])}<br>` +
    `Time from prev: ${fmtElapsed(elapsedMs)}<br>` +
    `Speed: ${speedStr}<br>` +
    `Battery: ${p.battery_level!=null?p.battery_level+'%':'—'}`
  );

  // Drop breadcrumb
  L.circleMarker([lat,lng],{radius:4,color:c,fillColor:c,fillOpacity:.7,weight:1}).addTo(map);
  map.panTo([lat, lng], {animate:true, duration:0.3});

  updateReplayLabel(replayIdx);
  replayIdx++;

  const speed = parseInt(document.getElementById('replaySpeed').value);
  replayTimer = setTimeout(stepReplay, speed);
}

function seekReplay(e) {
  if (!points.length) return;
  const rect = e.currentTarget.getBoundingClientRect();
  const pct  = (e.clientX - rect.left) / rect.width;
  replayIdx  = Math.max(0, Math.min(points.length - 1, Math.floor(pct * points.length)));
  updateReplayLabel(replayIdx);
  document.getElementById('replay-info').classList.add('visible');
  if (replayIdx < points.length) {
    const p = points[replayIdx];
    replayMarker.setLatLng([parseFloat(p.latitude), parseFloat(p.longitude)]);
    map.panTo([parseFloat(p.latitude), parseFloat(p.longitude)]);

    // Update info card on seek too
    const time = p.created_at.split(' ')[1]?.substring(0,5) || '';
    const stepDist = replayIdx > 0
      ? hdist(parseFloat(points[replayIdx-1].latitude), parseFloat(points[replayIdx-1].longitude),
              parseFloat(p.latitude), parseFloat(p.longitude))
      : 0;
    document.getElementById('ri-time').textContent       = time;
    document.getElementById('ri-step-dist').textContent  = replayIdx > 0 ? fmtDist(stepDist) : '—';
    document.getElementById('ri-total-dist').textContent = fmtDist(cumDist[replayIdx]);
    document.getElementById('ri-batt').textContent       = p.battery_level != null ? p.battery_level+'%' : '—';
    document.getElementById('ri-src').textContent        = srcLabel(p.source);
  }
}

function updateReplayLabel(idx) {
  const pct = points.length > 1 ? (idx / (points.length-1)) * 100 : 0;
  document.getElementById('replayFill').style.width = pct+'%';
  if (points[idx]) {
    const t = points[idx].created_at.split(' ')[1]?.substring(0,5) || '';
    document.getElementById('replayLabel').textContent = `${t} (${idx+1}/${points.length})`;
  }
}
</script>
</body>
</html>