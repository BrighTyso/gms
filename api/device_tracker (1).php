<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GMS · Device Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root {
    --bg:#0a0f0a; --surface:#111a11; --border:#1f2e1f;
    --green:#3ddc68; --green-dim:#1a5e30; --amber:#f5a623;
    --red:#e84040; --blue:#4a9eff; --purple:#b47eff; --text:#c8e6c9;
    --muted:#4a6b4a; --radius:6px;
  }
  html, body { height:100%; font-family:'Space Mono',monospace; background:var(--bg); color:var(--text); }
  .shell { display:grid; grid-template-rows:56px 1fr; grid-template-columns:330px 1fr; height:100vh; }

  header {
    grid-column:1/-1; display:flex; align-items:center; gap:12px;
    padding:0 20px; background:var(--surface); border-bottom:1px solid var(--border);
  }
  .logo { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--green); }
  .logo span { color:var(--muted); }
  .hdr-stats { display:flex; gap:14px; margin-left:auto; }
  .hdr-stat  { font-size:10px; color:var(--muted); }
  .hdr-stat b { color:var(--text); }
  #countdown { font-size:10px; color:var(--muted); margin-left:8px; }

  aside {
    background:var(--surface); border-right:1px solid var(--border);
    display:flex; flex-direction:column; overflow:hidden;
  }
  .sb-head { padding:14px 16px 10px; border-bottom:1px solid var(--border); }
  .sb-head h2 { font-family:'Syne',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
  .sb-head p  { font-size:10px; color:var(--muted); margin-top:2px; }

  .filter-row { display:flex; gap:5px; padding:8px 12px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
  .fbtn {
    font-family:'Space Mono',monospace; font-size:9px; cursor:pointer;
    border:1px solid var(--border); background:transparent; color:var(--muted);
    padding:4px 8px; border-radius:20px; transition:all .2s; white-space:nowrap;
  }
  .fbtn.active         { border-color:var(--green); color:var(--green); background:#0d200d; }
  .fbtn.f-amber.active { border-color:var(--amber); color:var(--amber); background:#1e1500; }
  .fbtn.f-red.active   { border-color:var(--red);   color:var(--red);   background:#200000; }
  .fbtn.f-blue.active  { border-color:var(--blue);  color:var(--blue);  background:#001020; }

  .layer-row {
    display:flex; gap:5px; padding:6px 12px;
    border-bottom:1px solid var(--border); flex-wrap:wrap; align-items:center;
  }
  .layer-label { font-size:9px; color:var(--muted); white-space:nowrap; }
  .lbtn {
    font-family:'Space Mono',monospace; font-size:9px; cursor:pointer;
    border:1px solid var(--border); background:transparent; color:var(--muted);
    padding:3px 7px; border-radius:3px; transition:all .2s; white-space:nowrap;
  }
  .lbtn.on { background:#0d200d; color:var(--text); border-color:var(--muted); }

  .device-list { flex:1; overflow-y:auto; }
  .device-list::-webkit-scrollbar { width:3px; }
  .device-list::-webkit-scrollbar-thumb { background:var(--border); }

  .device-card {
    display:flex; align-items:flex-start; gap:10px;
    padding:10px 14px; cursor:pointer;
    border-left:3px solid transparent; border-bottom:1px solid #0f1a0f;
    transition:background .15s, border-color .15s;
  }
  .device-card:hover    { background:rgba(61,220,104,.04); }
  .device-card.selected { background:rgba(61,220,104,.08); border-left-color:var(--green); }

  .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:4px; }
  .dot-green { background:var(--green); box-shadow:0 0 6px var(--green); animation:pulse 2s infinite; }
  .dot-amber { background:var(--amber); }
  .dot-red   { background:var(--red); }
  @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.4} }

  .card-body { flex:1; min-width:0; }
  .card-name { font-size:12px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .card-sub  { font-size:10px; color:var(--muted); margin-top:3px; display:flex; gap:5px; flex-wrap:wrap; align-items:center; }

  .badge { display:inline-block; font-size:9px; padding:1px 5px; border-radius:3px; font-family:'Space Mono',monospace; }
  .b-sms    { background:#001828; color:var(--blue);   border:1px solid #003050; }
  .b-queue  { background:#1a1200; color:var(--amber);  border:1px solid #3a2800; }
  .b-live   { background:#0d200d; color:var(--green);  border:1px solid var(--green-dim); }
  .b-batt   { background:#200000; color:var(--red);    border:1px solid #400000; }
  .b-grower { background:#0e0020; color:var(--purple); border:1px solid #2a0050; }

  .map-wrap { position:relative; }
  #map { width:100%; height:100%; background:#0d150d; }

  #detail-panel {
    position:absolute; bottom:16px; right:16px; z-index:1000;
    width:290px; background:var(--surface); border:1px solid var(--border);
    border-radius:8px; padding:14px; display:none;
    box-shadow:0 4px 24px rgba(0,0,0,.7);
    max-height:82vh; overflow-y:auto;
  }
  #detail-panel::-webkit-scrollbar { width:3px; }
  #detail-panel::-webkit-scrollbar-thumb { background:var(--border); }
  #detail-panel.visible { display:block; }

  .dp-name  { font-family:'Syne',sans-serif; font-size:15px; font-weight:800; color:var(--green); margin-bottom:10px; }
  .dp-row   { display:flex; justify-content:space-between; font-size:11px; margin-top:5px; }
  .dp-label { color:var(--muted); }
  .dp-val   { color:var(--text); font-weight:700; }
  .dp-val.warn { color:var(--red); }
  .dp-coords { font-size:10px; color:var(--muted); margin-top:8px; word-break:break-all; }
  .dp-divider { border:none; border-top:1px solid var(--border); margin:10px 0; }

  .dp-section-title {
    font-size:9px; text-transform:uppercase; letter-spacing:.5px;
    color:var(--muted); margin:10px 0 6px; font-weight:700;
  }

  .nearby-list { display:flex; flex-direction:column; gap:5px; }
  .nearby-item {
    background:#0d150d; border:1px solid var(--border); border-radius:4px;
    padding:7px 9px; cursor:pointer; transition:border-color .15s;
  }
  .nearby-item:hover             { border-color:var(--green); }
  .nearby-item.visited           { border-left:3px solid var(--green); }
  .nearby-item.unvisited         { border-left:3px solid var(--red); }
  .ni-name    { font-size:11px; font-weight:700; }
  .ni-meta    { font-size:9px; color:var(--muted); margin-top:3px; display:flex; gap:6px; flex-wrap:wrap; }
  .ni-dist    { color:var(--amber); font-weight:700; }
  .ni-visited   { color:var(--green); }
  .ni-unvisited { color:var(--red); }

  .btn-history {
    display:block; width:100%; margin-top:10px;
    font-family:'Space Mono',monospace; font-size:11px;
    background:var(--green-dim); border:1px solid var(--green); color:var(--green);
    padding:7px; border-radius:var(--radius); cursor:pointer; text-align:center;
  }
  .btn-history:hover { background:#1e4a22; }

  .gms-marker {
    width:30px; height:30px; border-radius:50%;
    border:2px solid var(--green); background:#0d200d;
    display:flex; align-items:center; justify-content:center; font-size:14px;
    box-shadow:0 0 10px rgba(61,220,104,.4);
  }
  .gms-marker.stale { border-color:var(--amber); box-shadow:0 0 8px rgba(245,166,35,.3); }
  .gms-marker.lost  { border-color:var(--red);   box-shadow:0 0 8px rgba(232,64,64,.3); }

  .grower-pin {
    width:22px; height:22px; border-radius:50%; border:2px solid;
    display:flex; align-items:center; justify-content:center; font-size:11px;
  }
  .grower-pin.home    { background:#0a0a20; border-color:#4a9eff; }
  .grower-pin.farm    { background:#0a200a; border-color:#3ddc68; }
  .grower-pin.seedbed { background:#1a1200; border-color:#f5a623; }
  .grower-pin.barn    { background:#200a00; border-color:#ff7043; }
  .grower-pin.unvisited { opacity:.55; filter:grayscale(.3); }
</style>
</head>
<body>

<?php
$devices = [];
$growers = [];
require "conn.php";
require "validate.php";

// ── Device locations ──────────────────────────────────────────────────────────
$result = $conn->query("
    SELECT dl.device_id, dl.officer_id,
           dl.latitude, dl.longitude, dl.accuracy, dl.battery_level,
           dl.source, dl.created_at,
           fo.name  AS officer_name,
           fo.phone AS officer_phone,
           TIMESTAMPDIFF(MINUTE, dl.created_at, NOW()) AS minutes_ago
    FROM device_locations dl
    INNER JOIN (
        SELECT device_id, MAX(id) AS max_id
        FROM device_locations
        GROUP BY device_id
    ) latest ON dl.id = latest.max_id
    LEFT JOIN field_officers fo ON dl.officer_id = fo.id
    ORDER BY dl.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) $devices[] = $row;
    $result->free();
}

// ── Growers with 4 separate location tables + last visit ─────────────────────
$growerResult = $conn->query("
    SELECT
        g.id,
        g.grower_num,
        g.name,
        g.surname,
        ll.latitude          AS home_lat,
        ll.longitude         AS home_lng,
        gf.latitude          AS farm_lat,
        gf.longitude         AS farm_lng,
        sl.latitude          AS seedbed_lat,
        sl.longitude         AS seedbed_lng,
        bl.latitude          AS barn_lat,
        bl.longitude         AS barn_lng,
        v.last_visit,
        DATEDIFF(NOW(), v.last_visit) AS days_since_visit
    FROM growers g
    LEFT JOIN lat_long         ll ON ll.growerid  = g.id
    LEFT JOIN grower_farm      gf ON gf.growerid  = g.id
    LEFT JOIN seedbed_location sl ON sl.growerid  = g.id
    LEFT JOIN barn_location    bl ON bl.growerid  = g.id
    LEFT JOIN (
        SELECT growerid, MAX(created_at) AS last_visit
        FROM visits
        GROUP BY growerid
    ) v ON v.growerid = g.id
    WHERE ll.latitude  IS NOT NULL OR gf.latitude IS NOT NULL
       OR sl.latitude  IS NOT NULL OR bl.latitude IS NOT NULL
    ORDER BY g.name, g.surname
");
if ($growerResult) {
    while ($row = $growerResult->fetch_assoc()) $growers[] = $row;
    $growerResult->free();
}

$conn->close();

// ── Build flat grower pins array for JS ───────────────────────────────────────
function validCoord($lat, $lng) {
    $lat = (float)$lat; $lng = (float)$lng;
    return ($lat != 0 || $lng != 0) && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
        ? ['lat' => $lat, 'lng' => $lng] : null;
}

$growerPins = [];
foreach ($growers as $g) {
    $visited   = !empty($g['last_visit']);
    $daysSince = $visited ? (int)$g['days_since_visit'] : null;
    $locs = [
        'home'    => validCoord($g['home_lat'],    $g['home_lng']),
        'farm'    => validCoord($g['farm_lat'],    $g['farm_lng']),
        'seedbed' => validCoord($g['seedbed_lat'], $g['seedbed_lng']),
        'barn'    => validCoord($g['barn_lat'],    $g['barn_lng']),
    ];
    foreach ($locs as $type => $coords) {
        if (!$coords) continue;
        $growerPins[] = [
            'grower_id'  => (int)$g['id'],
            'grower_num' => $g['grower_num'],
            'name'       => $g['name'] . ' ' . $g['surname'],
            'type'       => $type,
            'lat'        => $coords['lat'],
            'lng'        => $coords['lng'],
            'visited'    => $visited,
            'last_visit' => $g['last_visit'],
            'days_since' => $daysSince,
        ];
    }
}

function statusInfo($m) {
    return $m <= 30 ? ['green','Active'] : ($m <= 120 ? ['amber','Stale'] : ['red','Lost']);
}

$total        = count($devices);
$active       = count(array_filter($devices, fn($d) => $d['minutes_ago'] <= 30));
$stale        = count(array_filter($devices, fn($d) => $d['minutes_ago'] > 30 && $d['minutes_ago'] <= 120));
$lost         = count(array_filter($devices, fn($d) => $d['minutes_ago'] > 120));
$lowBatt      = count(array_filter($devices, fn($d) => $d['battery_level'] !== null && $d['battery_level'] <= 20));
$viaSms       = count(array_filter($devices, fn($d) => $d['source'] === 'sms'));
$totalGrowers = count($growers);
$unvisited    = count(array_filter($growers, fn($g) => empty($g['last_visit'])));
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Tracker</div>
    <div class="hdr-stats">
      <div class="hdr-stat"><b style="color:var(--green)"><?= $active ?></b> Active</div>
      <div class="hdr-stat"><b style="color:var(--amber)"><?= $stale ?></b> Stale</div>
      <div class="hdr-stat"><b style="color:var(--red)"><?= $lost ?></b> Lost</div>
      <?php if ($viaSms > 0): ?><div class="hdr-stat"><b style="color:var(--blue)">📱<?= $viaSms ?></b> SMS</div><?php endif ?>
      <?php if ($lowBatt > 0): ?><div class="hdr-stat"><b style="color:var(--red)">🔋<?= $lowBatt ?></b> Low</div><?php endif ?>
      <div class="hdr-stat"><b style="color:var(--purple)"><?= $totalGrowers ?></b> Growers</div>
      <?php if ($unvisited > 0): ?><div class="hdr-stat"><b style="color:var(--red)"><?= $unvisited ?></b> Unvisited</div><?php endif ?>
    </div>
    <span id="countdown">Auto-refresh in 60s</span>
  </header>

  <aside>
    <div class="sb-head">
      <h2>Devices (<?= $total ?>)</h2>
      <p>Click to locate on map</p>
    </div>

    <div class="filter-row">
      <button class="fbtn active"  onclick="doFilter('all',this)">All</button>
      <button class="fbtn f-amber" onclick="doFilter('amber',this)">Stale</button>
      <button class="fbtn f-red"   onclick="doFilter('red',this)">Lost</button>
      <button class="fbtn f-blue"  onclick="doFilter('sms',this)">Via SMS</button>
    </div>

    <!-- Grower layer toggles -->
    <div class="layer-row">
      <span class="layer-label">Layers:</span>
      <button class="lbtn on" id="lbtn-home"      onclick="toggleLayer('home',this)">🏠</button>
      <button class="lbtn on" id="lbtn-farm"      onclick="toggleLayer('farm',this)">🌱</button>
      <button class="lbtn on" id="lbtn-seedbed"   onclick="toggleLayer('seedbed',this)">🌿</button>
      <button class="lbtn on" id="lbtn-barn"      onclick="toggleLayer('barn',this)">🏚</button>
      <button class="lbtn on" id="lbtn-visited"   onclick="toggleLayer('visited',this)" style="color:var(--green)">✅</button>
      <button class="lbtn on" id="lbtn-unvisited" onclick="toggleLayer('unvisited',this)" style="color:var(--red)">❌</button>
    </div>

    <div class="device-list" id="deviceList">
    <?php foreach ($devices as $i => $d):
      [$color, $label] = statusInfo((int)$d['minutes_ago']);
      $name   = htmlspecialchars($d['officer_name'] ?? ('Device …' . substr($d['device_id'], -6)));
      $batt   = $d['battery_level'] !== null ? (int)$d['battery_level'] : -1;
      $ago    = (int)$d['minutes_ago'];
      $agoStr = $ago < 60 ? "{$ago}m ago" : round($ago / 60, 1) . "h ago";
      $src    = $d['source'] ?? 'realtime';
      $srcBadge = $src === 'sms'
                ? '<span class="badge b-sms">📱 SMS</span>'
                : ($src === 'offline_queue'
                    ? '<span class="badge b-queue">📦 Queued</span>'
                    : '<span class="badge b-live">🟢 Live</span>');
      $oGrowers   = count($growers);
      $oUnvisited = count(array_filter($growers, fn($g) => empty($g['last_visit'])));
    ?>
    <div class="device-card"
         data-index="<?= $i ?>"
         data-status="<?= $color ?>"
         data-source="<?= htmlspecialchars($src) ?>"
         onclick="selectDevice(<?= $i ?>)">
      <div class="status-dot dot-<?= $color ?>"></div>
      <div class="card-body">
        <div class="card-name"><?= $name ?></div>
        <div class="card-sub">
          <span><?= $label ?> · <?= $agoStr ?></span>
          <?= $srcBadge ?>
          <?php if ($batt >= 0): ?>
            <span class="badge <?= $batt <= 20 ? 'b-batt' : '' ?>">🔋<?= $batt ?>%</span>
          <?php endif ?>
          <?php if ($oGrowers > 0): ?>
            <span class="badge b-grower">👨‍🌾<?= $oGrowers ?>
              <?php if ($oUnvisited > 0): ?>&nbsp;<span style="color:var(--red)"><?= $oUnvisited ?>✗</span><?php endif ?>
            </span>
          <?php endif ?>
        </div>
      </div>
    </div>
    <?php endforeach ?>
    </div>
  </aside>

  <div class="map-wrap">
    <div id="map"></div>

    <div id="detail-panel">
      <div class="dp-name" id="dp-name">—</div>
      <div class="dp-row"><span class="dp-label">Phone</span><span class="dp-val" id="dp-phone">—</span></div>
      <div class="dp-row"><span class="dp-label">Battery</span><span class="dp-val" id="dp-batt">—</span></div>
      <div class="dp-row"><span class="dp-label">Last Seen</span><span class="dp-val" id="dp-seen">—</span></div>
      <div class="dp-row"><span class="dp-label">Accuracy</span><span class="dp-val" id="dp-acc">—</span></div>
      <div class="dp-row"><span class="dp-label">Source</span><span class="dp-val" id="dp-src">—</span></div>
      <hr class="dp-divider">
      <div class="dp-coords" id="dp-coords">—</div>

      <div class="dp-section-title">📍 Nearest Grower Locations</div>
      <div class="nearby-list" id="dp-nearby">
        <div style="font-size:10px;color:var(--muted)">Select a device to see nearby growers</div>
      </div>

      <button class="btn-history" onclick="viewHistory()">📍 View Location History</button>
    </div>
  </div>
</div>

<script>
const devices    = <?= json_encode($devices) ?>;
const growerPins = <?= json_encode($growerPins) ?>;

let map, officerMarkers = [], growerMarkers = [], selectedIndex = null;
let layers = { home:true, farm:true, seedbed:true, barn:true, visited:true, unvisited:true };

// ── Map init ──────────────────────────────────────────────────────────────────
map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
  { attribution:'© OpenStreetMap © CARTO', maxZoom:19 }).addTo(map);

// ── Officer markers ───────────────────────────────────────────────────────────
devices.forEach((d, i) => {
  const m   = parseInt(d.minutes_ago);
  const cls = m <= 30 ? '' : m <= 120 ? 'stale' : 'lost';
  const icon = L.divIcon({
    className: '',
    html: `<div class="gms-marker ${cls}">${cls==='lost'?'🔴':cls==='stale'?'🟡':'🟢'}</div>`,
    iconSize:[30,30], iconAnchor:[15,15], popupAnchor:[0,-18]
  });
  const mk = L.marker([parseFloat(d.latitude), parseFloat(d.longitude)], {icon})
    .addTo(map)
    .bindPopup(`<b>${d.officer_name||d.device_id}</b><br>${d.created_at}`);
  mk.on('click', () => selectDevice(i));
  officerMarkers.push(mk);
});

// ── Grower location markers ───────────────────────────────────────────────────
const typeEmoji = { home:'🏠', farm:'🌱', seedbed:'🌿', barn:'🏚' };

growerPins.forEach(p => {
  const icon = L.divIcon({
    className: '',
    html: `<div class="grower-pin ${p.type} ${p.visited?'visited':'unvisited'}">${typeEmoji[p.type]||'📍'}</div>`,
    iconSize:[22,22], iconAnchor:[11,11], popupAnchor:[0,-14]
  });

  const visitStr = p.last_visit
    ? `✅ Last visited: ${p.last_visit}<br><span style="color:#3ddc68">${p.days_since} days ago</span>`
    : `<span style="color:#e84040">❌ Never visited</span>`;

  const mk = L.marker([p.lat, p.lng], {icon})
    .addTo(map)
    .bindPopup(
      `<b>${p.name}</b> <span style="font-size:10px;color:#aaa">#${p.grower_num}</span><br>` +
      `<span style="font-size:10px">${typeEmoji[p.type]} ${p.type.charAt(0).toUpperCase()+p.type.slice(1)} location</span><br>` +
      `Officer: ${p.officer_name}<br>${visitStr}`
    );
  mk._pin = p;
  growerMarkers.push(mk);
});

// Fit map to all markers
const allMk = [...officerMarkers, ...growerMarkers];
if (allMk.length) map.fitBounds(L.featureGroup(allMk).getBounds().pad(0.15));
else map.setView([-17.8292, 31.0522], 12);

// ── Layer toggles ─────────────────────────────────────────────────────────────
function toggleLayer(type, btn) {
  layers[type] = !layers[type];
  btn.classList.toggle('on', layers[type]);
  applyLayers();
}

function applyLayers() {
  growerMarkers.forEach(mk => {
    const p = mk._pin;
    const show = layers[p.type] && (p.visited ? layers.visited : layers.unvisited);
    show ? (!map.hasLayer(mk) && mk.addTo(map)) : (map.hasLayer(mk) && map.removeLayer(mk));
  });
}

// ── Select device ─────────────────────────────────────────────────────────────
function selectDevice(i) {
  selectedIndex = i;
  const d = devices[i];
  document.querySelectorAll('.device-card').forEach(c => c.classList.remove('selected'));
  document.querySelector(`.device-card[data-index="${i}"]`)?.classList.add('selected');
  map.setView([d.latitude, d.longitude], 14, {animate:true});
  officerMarkers[i].openPopup();

  document.getElementById('detail-panel').classList.add('visible');
  document.getElementById('dp-name').textContent  = d.officer_name || d.device_id;
  document.getElementById('dp-phone').textContent = d.officer_phone || '—';

  const b   = d.battery_level != null ? parseInt(d.battery_level) : -1;
  const bEl = document.getElementById('dp-batt');
  bEl.textContent = b >= 0 ? b+'%' : '—';
  bEl.className   = 'dp-val' + (b >= 0 && b <= 20 ? ' warn' : '');

  const ago = parseInt(d.minutes_ago);
  document.getElementById('dp-seen').textContent = ago < 60 ? ago+' min ago' : (ago/60).toFixed(1)+' hrs ago';
  document.getElementById('dp-acc').textContent  = d.accuracy ? '±'+Math.round(d.accuracy)+'m' : '—';

  const srcMap = {realtime:'🟢 Internet (live)', sms:'📱 Via SMS', offline_queue:'📦 Offline queue'};
  document.getElementById('dp-src').textContent    = srcMap[d.source] || d.source;
  document.getElementById('dp-coords').textContent = parseFloat(d.latitude).toFixed(6)+', '+parseFloat(d.longitude).toFixed(6);

  showNearby(parseFloat(d.latitude), parseFloat(d.longitude));
}

// ── Nearest growers ───────────────────────────────────────────────────────────
function hdist(lat1,lng1,lat2,lng2) {
  const R=6371, r=x=>x*Math.PI/180, dLa=r(lat2-lat1), dLo=r(lng2-lng1);
  const a=Math.sin(dLa/2)**2+Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dLo/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function showNearby(oLat, oLng) {
  // Deduplicate by grower — keep shortest distance pin per grower
  const byGrower = {};
  growerPins.forEach(p => {
    const d = hdist(oLat, oLng, p.lat, p.lng);
    if (!byGrower[p.grower_id] || d < byGrower[p.grower_id].dist)
      byGrower[p.grower_id] = {...p, dist:d};
  });

  const nearby = Object.values(byGrower).sort((a,b)=>a.dist-b.dist).slice(0,8);
  const el = document.getElementById('dp-nearby');

  if (!nearby.length) {
    el.innerHTML = '<div style="font-size:10px;color:var(--muted)">No growers found</div>';
    return;
  }

  el.innerHTML = nearby.map(p => {
    const distStr  = p.dist < 1 ? Math.round(p.dist*1000)+'m' : p.dist.toFixed(1)+'km';
    const visitStr = p.visited
      ? `<span class="ni-visited">✅ ${p.days_since}d ago</span>`
      : `<span class="ni-unvisited">❌ Not visited</span>`;

    return `<div class="nearby-item ${p.visited?'visited':'unvisited'}"
                 onclick="map.setView([${p.lat},${p.lng}],17,{animate:true})">
      <div class="ni-name">${p.name} <span style="color:var(--muted);font-size:9px">#${p.grower_num}</span></div>
      <div class="ni-meta">
        <span class="ni-dist">${distStr}</span>
        ${visitStr}
      </div>
    </div>`;
  }).join('');
}

// ── Status filter ─────────────────────────────────────────────────────────────
function doFilter(type, btn) {
  document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.device-card').forEach(card => {
    const show = type==='all'
      || (type==='sms'  && card.dataset.source==='sms')
      || (type!=='sms'  && card.dataset.status===type);
    card.style.display = show ? 'flex' : 'none';
  });
}

function viewHistory() {
  if (selectedIndex===null) return;
  window.location.href = 'device_history.php?device_id='+encodeURIComponent(devices[selectedIndex].device_id);
}

let secs = 60;
setInterval(() => {
  secs--;
  document.getElementById('countdown').textContent = `Auto-refresh in ${secs}s`;
  if (secs <= 0) location.reload();
}, 1000);
</script>
</body>
</html>