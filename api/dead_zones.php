<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Dead Zones</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--text:#c8e6c9;--muted:#4a6b4a}
  html,body{height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text)}
  .shell{display:grid;grid-template-rows:56px 1fr;grid-template-columns:320px 1fr;height:100vh}
  header{grid-column:1/-1;display:flex;align-items:center;gap:10px;padding:0 20px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select,input[type=text]{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}

  aside{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
  .sb-head{padding:12px 16px 8px;border-bottom:1px solid var(--border)}
  .sb-head h2{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase}
  .sb-head p{font-size:10px;color:var(--muted);margin-top:2px}

  /* Sidebar tabs */
  .sb-tabs{display:flex;border-bottom:1px solid var(--border)}
  .sb-tab{flex:1;font-family:'Space Mono',monospace;font-size:9px;padding:7px 4px;text-align:center;cursor:pointer;border:none;background:transparent;color:var(--muted);border-bottom:2px solid transparent;transition:all .2s}
  .sb-tab.active{color:var(--green);border-bottom-color:var(--green)}

  .stats-bar{display:flex;gap:0;border-bottom:1px solid var(--border)}
  .stat-box{flex:1;padding:10px 12px;border-right:1px solid var(--border);text-align:center}
  .stat-box:last-child{border-right:none}
  .stat-val{font-family:'Syne',sans-serif;font-size:18px;font-weight:800}
  .stat-label{font-size:9px;color:var(--muted);margin-top:2px;text-transform:uppercase}

  .zone-list{flex:1;overflow-y:auto}
  .zone-list::-webkit-scrollbar{width:3px}
  .zone-list::-webkit-scrollbar-thumb{background:var(--border)}

  .zone-item{padding:10px 14px;border-bottom:1px solid #0f1a0f;cursor:pointer;border-left:3px solid var(--red);transition:background .15s}
  .zone-item:hover{background:rgba(232,64,64,.05)}
  .zone-item.selected{background:rgba(232,64,64,.1)}
  .zi-name{font-size:11px;font-weight:700}
  .zi-meta{font-size:9px;color:var(--muted);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap}
  .zi-days{color:var(--red);font-weight:700}
  .zi-days.warn{color:var(--amber)}

  /* Search panel */
  .search-panel{display:flex;flex-direction:column;overflow:hidden;height:100%}
  .search-box{padding:10px 12px;border-bottom:1px solid var(--border);display:flex;gap:6px}
  .search-box input{flex:1;font-size:11px}
  .search-box button{font-family:'Space Mono',monospace;font-size:10px;padding:4px 10px;border:1px solid var(--green);color:var(--green);background:var(--green-dim);border-radius:4px;cursor:pointer}
  .search-box button:hover{background:#1e4a22}
  .search-results{flex:1;overflow-y:auto}
  .search-results::-webkit-scrollbar{width:3px}
  .search-results::-webkit-scrollbar-thumb{background:var(--border)}
  .sr-item{padding:10px 14px;border-bottom:1px solid #0f1a0f;cursor:pointer;transition:background .15s}
  .sr-item:hover{background:rgba(61,220,104,.04)}
  .sr-item.active{background:rgba(61,220,104,.08);border-left:3px solid var(--green)}
  .sr-name{font-size:11px;font-weight:700}
  .sr-meta{font-size:9px;color:var(--muted);margin-top:3px;display:flex;flex-wrap:wrap;gap:6px}
  .sr-dist{color:var(--amber);font-weight:700}
  .sr-empty{padding:20px;text-align:center;color:var(--muted);font-size:11px}

  /* Close growers panel on map */
  #grower-panel{position:absolute;top:16px;right:16px;z-index:1000;width:280px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px;display:none;box-shadow:0 4px 20px rgba(0,0,0,.6);max-height:80vh;overflow-y:auto}
  #grower-panel::-webkit-scrollbar{width:3px}
  #grower-panel::-webkit-scrollbar-thumb{background:var(--border)}
  .gp-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:var(--green);margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}
  .gp-close{font-size:11px;color:var(--muted);cursor:pointer;padding:1px 6px;border:1px solid var(--border);border-radius:3px}
  .gp-close:hover{color:var(--red);border-color:var(--red)}
  .gp-row{display:flex;justify-content:space-between;font-size:11px;padding:5px 0;border-bottom:1px solid #0f1a0f}
  .gp-row:last-child{border-bottom:none}
  .gp-label{color:var(--muted)}
  .gp-val{color:var(--text);font-weight:700;text-align:right;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

  .map-wrap{position:relative}
  #map{width:100%;height:100%}

  .legend{position:absolute;bottom:20px;left:16px;z-index:1000;background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:10px 14px;font-size:10px}
  .leg-row{display:flex;align-items:center;gap:6px;margin-bottom:4px}
  .leg-row:last-child{margin-bottom:0}
  .leg-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0}
</style>
</head>
<body>
<?php
require "conn.php";
require "validate.php";

$days = isset($_GET['days']) ? min((int)$_GET['days'], 30) : 14;

function phpHdist($lat1,$lng1,$lat2,$lng2){
    $R=6371; $dLat=deg2rad($lat2-$lat1); $dLng=deg2rad($lng2-$lng1);
    $a=sin($dLat/2)*sin($dLat/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

// ── All growers with home location ────────────────────────────────────────────
// ── Current season ────────────────────────────────────────────────────────────
$seasonId=0;
$r=$conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$r->free();}

// ── Check if any assignments exist ───────────────────────────────────────────
$hasAssignments=false;
if($seasonId){
    $r=$conn->query("SELECT COUNT(*) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId");
    if($r&&$row=$r->fetch_assoc()){$hasAssignments=$row['cnt']>0;$r->free();}
}

$growers = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           ll.latitude AS lat, ll.longitude AS lng,
           v.last_visit, DATEDIFF(NOW(), v.last_visit) AS days_since_visit,
           ".($hasAssignments && $seasonId ? "fo_a.name AS assigned_officer, fo_a.id AS assigned_officer_id" : "NULL AS assigned_officer, NULL AS assigned_officer_id")."
    FROM growers g
    JOIN lat_long ll ON ll.growerid = g.id
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid = g.id
    ".($hasAssignments && $seasonId ? "
    LEFT JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId
    LEFT JOIN field_officers fo_a ON fo_a.userid=gfo.field_officerid" : "")."
    WHERE ll.latitude IS NOT NULL AND ll.latitude != 0
    ORDER BY g.name, g.surname
");
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

// ── Latest officer positions with names ───────────────────────────────────────
$officerPositions = [];
$r = $conn->query("
    SELECT dl.officer_id, dl.latitude, dl.longitude, dl.created_at,
           fo.name AS officer_name
    FROM device_locations dl
    INNER JOIN (
        SELECT officer_id, MAX(id) AS max_id
        FROM device_locations
        WHERE created_at >= NOW() - INTERVAL $days DAY
          AND officer_id IS NOT NULL
        GROUP BY officer_id
    ) latest ON dl.id = latest.max_id
    LEFT JOIN field_officers fo ON fo.id = dl.officer_id
");
if($r){
    while($row=$r->fetch_assoc()){
        $officerPositions[] = [
            'officer_id'   => $row['officer_id'],
            'name'         => $row['officer_name'] ?? ('Officer #'.$row['officer_id']),
            'lat'          => (float)$row['latitude'],
            'lng'          => (float)$row['longitude'],
            'last_seen'    => $row['created_at'],
        ];
    }
    $r->free();
}

// ── All officer location history for ping dots ────────────────────────────────
$r = $conn->query("
    SELECT latitude, longitude
    FROM device_locations
    WHERE created_at >= NOW() - INTERVAL $days DAY
      AND officer_id IS NOT NULL
");
if($r){while($row=$r->fetch_assoc()) $officerPings[]=[(float)$row['latitude'],(float)$row['longitude']]; $r->free();}

$conn->close();

// ── Classify each grower ─────────────────────────────────────────────────────
$RADIUS = 0.5; // 500m

// Build a map of field_officers.id => last position for assigned officer lookup
$officerPosById = [];
foreach($officerPositions as $op){
    $officerPosById[$op['officer_id']] = $op;
}

$deadZones = []; $totalCovered = 0;

foreach($growers as $g){
    $gLat = (float)$g['lat'];
    $gLng = (float)$g['lng'];

    // Find min distance to any officer ping (for coverage check)
    $minDist = PHP_FLOAT_MAX;
    foreach($officerPings as $p){
        $d = phpHdist($gLat,$gLng,$p[0],$p[1]);
        if($d < $minDist) $minDist = $d;
    }

    // Find nearest officer current position + name
    $nearestOfficer     = null;
    $nearestOfficerDist = PHP_FLOAT_MAX;
    foreach($officerPositions as $op){
        $d = phpHdist($gLat,$gLng,$op['lat'],$op['lng']);
        if($d < $nearestOfficerDist){
            $nearestOfficerDist = $d;
            $nearestOfficer     = $op;
        }
    }

    // Find assigned officer's last GPS position
    $assignedOfficerPos = null;
    if(!empty($g['assigned_officer_id'])){
        // field_officers.id is stored in assigned_officer_id
        $assignedOfficerPos = $officerPosById[$g['assigned_officer_id']] ?? null;
    }

    $officerNearby = $minDist <= $RADIUS;
    $daysSince     = $g['days_since_visit'] !== null ? (int)$g['days_since_visit'] : 999;
    $neverVisited  = empty($g['last_visit']);

    if(!$officerNearby){
        $deadZones[] = [
            'id'                      => $g['id'],
            'grower_num'              => $g['grower_num'],
            'name'                    => $g['name'].' '.$g['surname'],
            'lat'                     => $gLat,
            'lng'                     => $gLng,
            'min_dist_km'             => round($minDist, 2),
            'last_visit'              => $g['last_visit'],
            'days_since'              => $neverVisited ? null : $daysSince,
            'never'                   => $neverVisited,
            'assigned_officer'        => $g['assigned_officer'] ?? null,
            'assigned_officer_id'     => $g['assigned_officer_id'] ?? null,
            'assigned_officer_lat'    => $assignedOfficerPos ? $assignedOfficerPos['lat'] : null,
            'assigned_officer_lng'    => $assignedOfficerPos ? $assignedOfficerPos['lng'] : null,
            'assigned_officer_seen'   => $assignedOfficerPos ? $assignedOfficerPos['last_seen'] : null,
            'nearest_officer'         => $nearestOfficer['name'] ?? null,
            'nearest_officer_dist'    => $nearestOfficer ? round($nearestOfficerDist, 2) : null,
            'nearest_officer_lat'     => $nearestOfficer['lat'] ?? null,
            'nearest_officer_lng'     => $nearestOfficer['lng'] ?? null,
            'nearest_officer_seen'    => $nearestOfficer['last_seen'] ?? null,
        ];
    } else {
        $totalCovered++;
    }
}

$totalGrowers  = count($growers);
$totalDeadZones= count($deadZones);
$coveragePct   = $totalGrowers > 0 ? round(($totalCovered / $totalGrowers) * 100) : 0;
$neverVisited  = count(array_filter($deadZones, fn($z) => $z['never']));

// Sort dead zones by days since visit DESC (most overdue first)
usort($deadZones, fn($a,$b) => ($b['days_since'] ?? 999) - ($a['days_since'] ?? 999));
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Dead Zones</div>
    <a href="officer_coverage.php" class="back">← Coverage</a>
    <select onchange="location.href='?days='+this.value" style="margin-left:8px">
      <option value="7"  <?=$days==7?'selected':''?>>Last 7 days</option>
      <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
      <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
    </select>
    <div style="margin-left:auto;font-size:10px;color:var(--muted)">No officer within 500m in last <?=$days?> days</div>
  </header>

  <aside>
    <div class="stats-bar">
      <div class="stat-box"><div class="stat-val" style="color:var(--red)"><?=$totalDeadZones?></div><div class="stat-label">Dead Zones</div></div>
      <div class="stat-box"><div class="stat-val" style="color:var(--green)"><?=$coveragePct?>%</div><div class="stat-label">Covered</div></div>
      <div class="stat-box"><div class="stat-val" style="color:var(--amber)"><?=$neverVisited?></div><div class="stat-label">Never Visited</div></div>
    </div>

    <!-- Tabs -->
    <div class="sb-tabs">
      <button class="sb-tab active" onclick="switchTab('dead',this)">🚫 Dead Zones</button>
      <button class="sb-tab"        onclick="switchTab('grower',this)">🔍 Grower</button>
      <button class="sb-tab"        onclick="switchTab('officer',this)">👮 Officer</button>
    </div>

    <!-- Tab: Dead zones list -->
    <div id="tab-dead" class="zone-list">
    <?php foreach($deadZones as $i=>$z):
      $daysLabel     = $z['never'] ? 'Never visited' : ($z['days_since'].'d since visit');
      $daysClass     = $z['never'] || $z['days_since']>30 ? '' : 'warn';
      $assignedStr   = $z['assigned_officer'] ?? '—';
      $assignedHasPos= !empty($z['assigned_officer_lat']);
    ?>
    <div class="zone-item" data-index="<?=$i?>" onclick="flyTo(<?=$z['lat']?>,<?=$z['lng']?>,<?=$i?>)">
      <div class="zi-name"><?=htmlspecialchars($z['name'])?> <span style="color:var(--muted);font-size:9px">#<?=$z['grower_num']?></span></div>
      <div class="zi-meta">
        <span class="zi-days <?=$daysClass?>"><?=$daysLabel?></span>
        <span>Nearest ping: <?=$z['min_dist_km']?>km</span>
      </div>
      <?php if(!empty($z['assigned_officer'])): ?>
      <div style="font-size:9px;color:var(--green);margin-top:3px">
        📋 <?=htmlspecialchars($assignedStr)?>
        <?php if($assignedHasPos): ?>
          <span style="color:var(--muted)">· <?=date('d M H:i',strtotime($z['assigned_officer_seen']))?></span>
        <?php else: ?><span style="color:var(--red)">· no GPS</span><?php endif?>
      </div>
      <?php endif?>
      <?php if($z['nearest_officer'] && $z['nearest_officer'] !== ($z['assigned_officer']??'')): ?>
      <div style="font-size:9px;color:var(--blue);margin-top:2px">👮 Nearest: <?=htmlspecialchars($z['nearest_officer'])?> (<?=$z['nearest_officer_dist']?>km)</div>
      <?php endif?>
    </div>
    <?php endforeach?>
    <?php if(empty($deadZones)): ?>
    <div style="padding:20px;text-align:center;color:var(--green);font-size:11px">✅ All growers covered!</div>
    <?php endif?>
    </div>

    <!-- Tab: Search by grower -->
    <div id="tab-grower" class="search-panel" style="display:none">
      <div class="search-box">
        <input type="text" id="grower-search" placeholder="Name or grower #..." oninput="searchGrower(this.value)">
        <button onclick="clearGrowerSearch()">✕</button>
      </div>
      <div class="search-results" id="grower-results">
        <div class="sr-empty">Type to search growers<br><span style="color:var(--muted);font-size:9px">Shows closest growers nearby</span></div>
      </div>
    </div>

    <!-- Tab: Search by officer -->
    <div id="tab-officer" class="search-panel" style="display:none">
      <div class="search-box">
        <input type="text" id="officer-search" placeholder="Officer name..." oninput="searchOfficer(this.value)">
        <button onclick="clearOfficerSearch()">✕</button>
      </div>
      <div class="search-results" id="officer-results">
        <div class="sr-empty">Type to search officers<br><span style="color:var(--muted);font-size:9px">Shows their assigned growers sorted by distance</span></div>
      </div>
    </div>
  </aside>

  <div class="map-wrap">
    <div id="map"></div>

    <!-- Info panel shown on grower/officer search click -->
    <div id="grower-panel">
      <div class="gp-title">
        <span id="gp-name">—</span>
        <span class="gp-close" onclick="document.getElementById('grower-panel').style.display='none'">✕</span>
      </div>
      <div id="gp-rows"></div>
    </div>

    <div class="legend">
      <div class="leg-row"><div class="leg-dot" style="background:var(--red)"></div> Uncovered grower</div>
      <div class="leg-row"><div class="leg-dot" style="background:var(--green)"></div> Covered grower</div>
      <div class="leg-row"><div class="leg-dot" style="background:#0d200d;border:2px solid #3ddc68"></div> Officer last position</div>
      <div class="leg-row"><div style="width:18px;border-top:2px dashed #3ddc68"></div> → Assigned officer</div>
      <div class="leg-row"><div style="width:18px;border-top:2px dashed #4a9eff"></div> → Nearest officer</div>
      <div class="leg-row"><div style="width:18px;border-top:2px dashed #f5a623"></div> → Search result</div>
    </div>
  </div>
</div>

<script>
const deadZones       = <?=json_encode($deadZones)?>;
const allGrowers      = <?=json_encode($growers)?>;
const officerPings    = <?=json_encode(array_slice($officerPings,0,2000))?>;
const officerPositions= <?=json_encode($officerPositions)?>;

const map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',maxZoom:19}).addTo(map);

// Officer ping dots
officerPings.forEach(p => {
  L.circleMarker([p[0],p[1]],{radius:3,color:'#4a9eff',fillColor:'#4a9eff',fillOpacity:.2,weight:0}).addTo(map);
});

// Officer current position markers — store refs by officer_id for highlight
const officerMarkers = {};
officerPositions.forEach(o => {
  const mk = L.circleMarker([o.lat,o.lng],{
    radius:8, color:'#3ddc68', fillColor:'#0d200d', fillOpacity:1, weight:2
  }).addTo(map).bindPopup(`<b>👮 ${o.name}</b><br>Last seen: ${o.last_seen}`);
  officerMarkers[o.officer_id] = mk;
});

// Dead zone markers
const deadMarkers = [];
deadZones.forEach((z,i) => {
  const daysStr    = z.never ? 'Never visited' : z.days_since+'d since visit';
  const assignedStr= z.assigned_officer
    ? `<br><span style="color:#3ddc68">📋 Assigned: ${z.assigned_officer}${z.assigned_officer_seen ? ' · '+z.assigned_officer_seen.substring(0,16) : ' · no GPS'}</span>`
    : '';
  const officerStr = z.nearest_officer && z.nearest_officer !== z.assigned_officer
    ? `<br><span style="color:#4a9eff">👮 Nearest: ${z.nearest_officer} (${z.nearest_officer_dist}km)</span>`
    : '';
  const mk = L.circleMarker([z.lat,z.lng],{
    radius:10, color:'#e84040', fillColor:'#e84040', fillOpacity:.7, weight:2
  }).addTo(map).bindPopup(
    `<b>${z.name}</b> <span style="color:#aaa">#${z.grower_num}</span><br>` +
    `<span style="color:#e84040">${daysStr}</span><br>` +
    `Nearest ping: ${z.min_dist_km}km` +
    assignedStr + officerStr
  );
  mk.on('click', () => {
    document.querySelectorAll('.zone-item').forEach(el=>el.classList.remove('selected'));
    document.querySelector(`.zone-item[data-index="${i}"]`)?.classList.add('selected');
    document.querySelector(`.zone-item[data-index="${i}"]`)?.scrollIntoView({behavior:'smooth',block:'nearest'});
    drawLines(z);
  });
  deadMarkers.push(mk);
});

// Covered grower markers
const deadIds = new Set(deadZones.map(z=>z.id));
allGrowers.forEach(g => {
  if(deadIds.has(g.id)) return;
  L.circleMarker([parseFloat(g.lat),parseFloat(g.lng)],{
    radius:5, color:'#3ddc68', fillColor:'#3ddc68', fillOpacity:.5, weight:1
  }).addTo(map);
});

// Fit map
if(deadZones.length){
  map.fitBounds(L.latLngBounds(deadZones.map(z=>[z.lat,z.lng])).pad(0.2));
} else {
  map.setView([-17.8292,31.0522],11);
}

// Lines and highlights
let activeLayers = [];

function clearActiveLayers(){
  activeLayers.forEach(l => map.removeLayer(l));
  activeLayers = [];
  // Reset all officer markers to default style
  Object.values(officerMarkers).forEach(mk => mk.setStyle({radius:8,color:'#3ddc68',fillColor:'#0d200d',weight:2}));
}

function drawLines(z){
  clearActiveLayers();

  // Green line + pulse to ASSIGNED officer
  if(z.assigned_officer_lat && z.assigned_officer_lng){
    const line = L.polyline(
      [[z.lat,z.lng],[z.assigned_officer_lat,z.assigned_officer_lng]],
      {color:'#3ddc68', weight:2.5, dashArray:'6 3', opacity:.9}
    ).addTo(map);
    activeLayers.push(line);

    // Highlight assigned officer marker
    const aId = z.assigned_officer_id;
    if(aId && officerMarkers[aId]){
      officerMarkers[aId].setStyle({radius:12,color:'#3ddc68',fillColor:'#1a5e30',weight:3});
      officerMarkers[aId].openPopup();
    }
  }

  // Blue dashed line to NEAREST officer (only if different from assigned)
  if(z.nearest_officer_lat && z.nearest_officer_lng &&
     !(z.nearest_officer_lat === z.assigned_officer_lat && z.nearest_officer_lng === z.assigned_officer_lng)){
    const line2 = L.polyline(
      [[z.lat,z.lng],[z.nearest_officer_lat,z.nearest_officer_lng]],
      {color:'#4a9eff', weight:2, dashArray:'4 4', opacity:.7}
    ).addTo(map);
    activeLayers.push(line2);
  }
}

function flyTo(lat,lng,i){
  map.setView([lat,lng],15,{animate:true});
  deadMarkers[i]?.openPopup();
  document.querySelectorAll('.zone-item').forEach(el=>el.classList.remove('selected'));
  document.querySelector(`.zone-item[data-index="${i}"]`)?.classList.add('selected');
  drawLines(deadZones[i]);
}

// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(name, btn){
  document.querySelectorAll('.sb-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-dead').style.display    = name==='dead'    ? 'block' : 'none';
  document.getElementById('tab-grower').style.display  = name==='grower'  ? 'flex'  : 'none';
  document.getElementById('tab-officer').style.display = name==='officer' ? 'flex'  : 'none';
}

// ── Haversine (JS) ────────────────────────────────────────────────────────────
function jsDist(lat1,lng1,lat2,lng2){
  const R=6371, r=x=>x*Math.PI/180;
  const dLa=r(lat2-lat1), dLo=r(lng2-lng1);
  const a=Math.sin(dLa/2)**2+Math.cos(r(lat1))*Math.cos(r(lat2))*Math.sin(dLo/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

// ── Search layers ─────────────────────────────────────────────────────────────
let searchLayers = [];
function clearSearchLayers(){
  searchLayers.forEach(l=>map.removeLayer(l)); searchLayers=[];
}

// ── Grower search ─────────────────────────────────────────────────────────────
function searchGrower(q){
  const out = document.getElementById('grower-results');
  q = q.trim().toLowerCase();
  if(q.length < 2){
    out.innerHTML='<div class="sr-empty">Type at least 2 characters</div>';
    clearSearchLayers();
    document.getElementById('grower-panel').style.display='none';
    return;
  }

  const matches = allGrowers.filter(g =>
    (g.name+' '+g.surname).toLowerCase().includes(q) ||
    String(g.grower_num).includes(q)
  ).slice(0, 20);

  if(!matches.length){
    out.innerHTML='<div class="sr-empty">No growers found</div>';
    clearSearchLayers();
    return;
  }

  // ── Auto-plot all matches on map as user types ────────────────────────────
  clearSearchLayers();
  const bounds = [];

  matches.forEach((g, i) => {
    const lat=parseFloat(g.lat), lng=parseFloat(g.lng);
    if(!lat || !lng) return;
    bounds.push([lat,lng]);

    // Orange marker for each matched grower
    const mk = L.circleMarker([lat,lng],{
      radius:10, color:'#f5a623', fillColor:'#f5a623', fillOpacity:.8, weight:3
    }).addTo(map).bindPopup(
      `<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>` +
      (g.last_visit ? `${g.days_since_visit}d since visit` : '<span style="color:#e84040">Never visited</span>') +
      (g.assigned_officer ? `<br>📋 ${g.assigned_officer}` : '')
    );
    mk.on('click', () => showGrowerResult(i));
    searchLayers.push(mk);

    // Number label
    const label = L.divIcon({
      className:'', iconSize:[18,18], iconAnchor:[9,9],
      html:`<div style="width:18px;height:18px;border-radius:50%;background:#f5a623;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#000">${i+1}</div>`
    });
    searchLayers.push(L.marker([lat,lng],{icon:label,interactive:false}).addTo(map));

    // For the first result show nearby OFFICERS with lines
    if(i === 0){
      const nearOfficers = officerPositions
        .map(o=>({...o, dist:jsDist(lat,lng,o.lat,o.lng)}))
        .sort((a,b)=>a.dist-b.dist).slice(0,5);

      nearOfficers.forEach(o=>{
        const isAssigned = g.assigned_officer === o.name;
        const col = isAssigned ? '#3ddc68' : '#4a9eff';
        const om = L.circleMarker([o.lat,o.lng],{radius:8,color:col,fillColor:isAssigned?'#1a5e30':'#001020',fillOpacity:1,weight:2})
          .addTo(map).bindPopup(`<b>👮 ${o.name}</b><br>${o.dist.toFixed(2)}km from ${g.name}<br>${isAssigned?'📋 Assigned officer':''}`);
        searchLayers.push(om);
        const line = L.polyline([[lat,lng],[o.lat,o.lng]],{color:col,weight:1.5,dashArray:'4 3',opacity:.7}).addTo(map);
        searchLayers.push(line);
      });
    }
  });

  // Fit map to show all matches
  if(bounds.length === 1){
    map.setView(bounds[0], 15, {animate:true});
  } else if(bounds.length > 1){
    map.fitBounds(L.latLngBounds(bounds).pad(0.2), {animate:true});
  }

  window._growerMatches = matches;
  showGrowerResult(0, true);

  // Build sidebar list — show nearby officers
  out.innerHTML = matches.map((g,i) => {
    const lat=parseFloat(g.lat), lng=parseFloat(g.lng);
    const nearOfficers = officerPositions
      .map(o=>({...o, dist:jsDist(lat,lng,o.lat,o.lng)}))
      .sort((a,b)=>a.dist-b.dist).slice(0,3);
    const officerHtml = nearOfficers.map(o=>{
      const isAssigned = g.assigned_officer === o.name;
      return `<span style="color:${isAssigned?'var(--green)':'var(--muted)'};font-size:9px">
        ${isAssigned?'📋':'👮'} ${o.name} <b style="color:var(--amber)">${o.dist.toFixed(1)}km</b>
      </span>`;
    }).join(' · ');
    return `<div class="sr-item${i===0?' active':''}" onclick="showGrowerResult(${i})" data-gi="${i}">
      <div class="sr-name"><b style="color:var(--amber)">${i+1}</b> ${g.name} ${g.surname} <span style="color:var(--muted);font-size:9px">#${g.grower_num}</span></div>
      <div class="sr-meta">
        ${g.last_visit ? `<span>${g.days_since_visit??'?'}d since visit</span>` : '<span style="color:var(--red)">Never visited</span>'}
        ${g.assigned_officer ? `<span style="color:var(--green)">📋 ${g.assigned_officer}</span>` : ''}
      </div>
      ${officerHtml ? `<div style="margin-top:3px">${officerHtml}</div>` : ''}
    </div>`;
  }).join('');
}

function showGrowerResult(i, silent=false){
  const g = window._growerMatches[i];
  if(!g) return;
  const lat=parseFloat(g.lat), lng=parseFloat(g.lng);

  // Find nearby officers sorted by distance
  const nearOfficers = officerPositions
    .map(o=>({...o, dist:jsDist(lat,lng,o.lat,o.lng)}))
    .sort((a,b)=>a.dist-b.dist).slice(0,8);

  // Find the assigned officer object
  const assignedOfficer = nearOfficers.find(o => o.name === g.assigned_officer) || nearOfficers[0];

  if(!silent){
    clearSearchLayers();

    // Fit bounds to show both grower and assigned officer
    const boundsPoints = [[lat,lng]];
    if(assignedOfficer) boundsPoints.push([assignedOfficer.lat, assignedOfficer.lng]);
    if(boundsPoints.length > 1){
      map.fitBounds(L.latLngBounds(boundsPoints).pad(0.3), {animate:true});
    } else {
      map.setView([lat,lng], 14, {animate:true});
    }

    // Grower marker
    const mk = L.circleMarker([lat,lng],{radius:12,color:'#f5a623',fillColor:'#f5a623',fillOpacity:.8,weight:3})
      .addTo(map).bindPopup(
        `<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>` +
        (g.last_visit ? `${g.days_since_visit}d since visit` : 'Never visited') +
        (g.assigned_officer ? `<br>📋 ${g.assigned_officer}` : '')
      ).openPopup();
    searchLayers.push(mk);

    // Draw all nearby officers with lines
    nearOfficers.forEach(o=>{
      const isAssigned = g.assigned_officer === o.name;
      const col = isAssigned ? '#3ddc68' : '#4a9eff';
      const om = L.circleMarker([o.lat,o.lng],{
        radius: isAssigned ? 12 : 7,
        color:col, fillColor:isAssigned?'#1a5e30':'#001020', fillOpacity:1, weight:2
      }).addTo(map).bindPopup(
        `<b>👮 ${o.name}</b><br>${o.dist.toFixed(2)}km from ${g.name} ${g.surname}<br>` +
        `Last seen: ${o.last_seen.substring(0,16)}` +
        (isAssigned ? '<br><b style="color:#3ddc68">📋 Assigned officer</b>' : '')
      );
      searchLayers.push(om);
      const line = L.polyline([[lat,lng],[o.lat,o.lng]],{
        color:col, weight:isAssigned?2.5:1.5, dashArray:'5 3', opacity:.8
      }).addTo(map);
      searchLayers.push(line);

      // Auto-open assigned officer popup after a short delay so grower popup shows first
      if(isAssigned){
        setTimeout(()=>{ om.openPopup(); }, 800);
        // Also pulse the permanent officer marker if it exists
        if(officerMarkers[o.officer_id]){
          officerMarkers[o.officer_id].setStyle({radius:14,color:'#3ddc68',fillColor:'#1a5e30',weight:3});
        }
      }
    });
  }

  // Info panel — show nearby officers
  document.getElementById('grower-panel').style.display='block';
  document.getElementById('gp-name').textContent = g.name+' '+g.surname;
  document.getElementById('gp-rows').innerHTML = `
    <div class="gp-row"><span class="gp-label">Grower #</span><span class="gp-val">${g.grower_num}</span></div>
    <div class="gp-row"><span class="gp-label">Last visit</span><span class="gp-val">${g.last_visit ? g.days_since_visit+'d ago' : 'Never'}</span></div>
    <div class="gp-row"><span class="gp-label">Assigned officer</span><span class="gp-val" style="color:var(--green)">${g.assigned_officer||'—'}</span></div>
    ${assignedOfficer ? `<div class="gp-row"><span class="gp-label">Officer distance</span><span class="gp-val" style="color:var(--amber)">${assignedOfficer.dist.toFixed(2)}km away</span></div>
    <div class="gp-row"><span class="gp-label">Officer last seen</span><span class="gp-val">${assignedOfficer.last_seen.substring(0,16)}</span></div>` : ''}
    <hr style="border-color:var(--border);margin:8px 0">
    <div style="font-size:9px;color:var(--muted);margin-bottom:6px">
      ALL NEARBY OFFICERS &nbsp;
      <span style="color:var(--green)">█</span> assigned &nbsp;
      <span style="color:var(--blue)">█</span> other
    </div>
    ${nearOfficers.map(o=>{
      const isAssigned = g.assigned_officer === o.name;
      return `<div class="gp-row" style="cursor:pointer" onclick="pingOfficerOnMap(${o.officer_id})">
        <span class="gp-label" style="color:${isAssigned?'var(--green)':'var(--text)'}">
          ${isAssigned?'📋':'👮'} ${o.name}
        </span>
        <span class="gp-val" style="color:var(--amber)">${o.dist.toFixed(2)}km</span>
      </div>`;
    }).join('')}
  `;

  document.querySelectorAll('.sr-item').forEach(el=>el.classList.remove('active'));
  document.querySelector(`.sr-item[data-gi="${i}"]`)?.classList.add('active');
}

// Click officer row in panel → fly to and open their popup on map
function pingOfficerOnMap(officerId){
  const o = officerPositions.find(o=>o.officer_id==officerId);
  if(!o) return;
  map.setView([o.lat,o.lng],16,{animate:true});
  // Open popup on search layer officer marker if exists, else permanent marker
  const permanentMk = officerMarkers[officerId];
  if(permanentMk){ permanentMk.openPopup(); return; }
  // Find in search layers
  searchLayers.forEach(l => {
    if(l.getLatLng && l.getLatLng().lat===o.lat && l.getLatLng().lng===o.lng) l.openPopup();
  });
}

function clearGrowerSearch(){
  document.getElementById('grower-search').value='';
  document.getElementById('grower-results').innerHTML='<div class="sr-empty">Type to search growers</div>';
  clearSearchLayers();
  document.getElementById('grower-panel').style.display='none';
}

// ── Officer search ────────────────────────────────────────────────────────────
function searchOfficer(q){
  const out = document.getElementById('officer-results');
  q = q.trim().toLowerCase();
  if(q.length < 2){
    out.innerHTML='<div class="sr-empty">Type at least 2 characters</div>';
    clearSearchLayers();
    document.getElementById('grower-panel').style.display='none';
    return;
  }

  const matches = officerPositions.filter(o=>o.name.toLowerCase().includes(q)).slice(0,10);
  if(!matches.length){
    out.innerHTML='<div class="sr-empty">No officers found</div>';
    clearSearchLayers();
    return;
  }

  // ── Auto-plot all matching officers on map as user types ──────────────────
  clearSearchLayers();
  const bounds = [];

  matches.forEach((o, i) => {
    if(!o.lat || !o.lng) return;
    bounds.push([o.lat, o.lng]);

    // Highlighted officer marker
    const mk = L.circleMarker([o.lat,o.lng],{
      radius:12, color:'#3ddc68', fillColor:'#1a5e30', fillOpacity:1, weight:3
    }).addTo(map).bindPopup(`<b>👮 ${o.name}</b><br>Last seen: ${o.last_seen}`);
    mk.on('click', ()=>showOfficerResult(i));
    searchLayers.push(mk);

    // Number label
    const label = L.divIcon({
      className:'', iconSize:[18,18], iconAnchor:[9,9],
      html:`<div style="width:18px;height:18px;border-radius:50%;background:#3ddc68;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#000">${i+1}</div>`
    });
    searchLayers.push(L.marker([o.lat,o.lng],{icon:label,interactive:false}).addTo(map));

    // For first match, show nearby growers
    if(i === 0){
      const nearby = allGrowers
        .filter(g=>g.lat && g.lng)
        .map(g=>({...g, dist:jsDist(o.lat,o.lng,parseFloat(g.lat),parseFloat(g.lng))}))
        .sort((a,b)=>a.dist-b.dist).slice(0,8);

      nearby.forEach(g=>{
        const gLat=parseFloat(g.lat), gLng=parseFloat(g.lng);
        const isAssigned = g.assigned_officer_id === o.officer_id;
        const col = isAssigned ? '#3ddc68' : '#4a9eff';
        const gm = L.circleMarker([gLat,gLng],{radius:6,color:col,fillColor:col,fillOpacity:.5,weight:1.5})
          .addTo(map).bindPopup(`<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>${g.dist.toFixed(2)}km from officer<br>${isAssigned?'📋 Assigned':'Not assigned'}`);
        searchLayers.push(gm);
        const line = L.polyline([[o.lat,o.lng],[gLat,gLng]],{color:col,weight:1,dashArray:'3 3',opacity:.5}).addTo(map);
        searchLayers.push(line);
      });
    }
  });

  // Fit map
  if(bounds.length === 1){
    map.setView(bounds[0], 13, {animate:true});
  } else if(bounds.length > 1){
    map.fitBounds(L.latLngBounds(bounds).pad(0.2), {animate:true});
  }

  // Auto-show first match info panel
  window._officerMatches = matches;
  showOfficerResult(0, true);

  // Build sidebar list
  out.innerHTML = matches.map((o,i) => {
    const nearby = allGrowers
      .filter(g=>g.lat && g.lng)
      .map(g=>({...g, dist:jsDist(o.lat,o.lng,parseFloat(g.lat),parseFloat(g.lng))}))
      .sort((a,b)=>a.dist-b.dist).slice(0,4);
    return `<div class="sr-item${i===0?' active':''}" onclick="showOfficerResult(${i})" data-oi="${i}">
      <div class="sr-name"><b style="color:var(--green)">${i+1}</b> 👮 ${o.name}</div>
      <div class="sr-meta"><span style="color:var(--muted)">Last seen: ${o.last_seen.substring(0,16)}</span></div>
      <div style="margin-top:3px">${nearby.map(g=>
        `<span style="color:var(--muted);font-size:9px">${g.name} ${g.surname} <b style="color:var(--amber)">${g.dist.toFixed(1)}km</b></span>`
      ).join(' · ')}</div>
    </div>`;
  }).join('');
}

function showOfficerResult(i, silent=false){
  const o = window._officerMatches[i];
  if(!o) return;

  if(!silent){
    clearSearchLayers();
    map.setView([o.lat,o.lng],13,{animate:true});

    const mk = L.circleMarker([o.lat,o.lng],{radius:14,color:'#3ddc68',fillColor:'#1a5e30',fillOpacity:1,weight:3})
      .addTo(map).bindPopup(`<b>👮 ${o.name}</b><br>Last seen: ${o.last_seen}`).openPopup();
    searchLayers.push(mk);

    const nearby = allGrowers
      .filter(g=>g.lat && g.lng)
      .map(g=>({...g, dist:jsDist(o.lat,o.lng,parseFloat(g.lat),parseFloat(g.lng))}))
      .sort((a,b)=>a.dist-b.dist).slice(0,10);

    nearby.forEach(g=>{
      const gLat=parseFloat(g.lat), gLng=parseFloat(g.lng);
      const isAssigned = g.assigned_officer_id === o.officer_id;
      const col = isAssigned ? '#3ddc68' : '#4a9eff';
      const gm = L.circleMarker([gLat,gLng],{radius:7,color:col,fillColor:col,fillOpacity:.6,weight:2})
        .addTo(map).bindPopup(`<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>${g.dist.toFixed(2)}km from officer<br>${isAssigned?'📋 Assigned':'Not assigned'}<br>${g.last_visit?g.days_since_visit+'d since visit':'Never visited'}`);
      searchLayers.push(gm);
      const line = L.polyline([[o.lat,o.lng],[gLat,gLng]],{color:col,weight:1.5,dashArray:'4 3',opacity:.5}).addTo(map);
      searchLayers.push(line);
    });
  }

  // Always update info panel
  const nearby = allGrowers
    .filter(g=>g.lat && g.lng)
    .map(g=>({...g, dist:jsDist(o.lat,o.lng,parseFloat(g.lat),parseFloat(g.lng))}))
    .sort((a,b)=>a.dist-b.dist).slice(0,10);

  const panel = document.getElementById('grower-panel');
  panel.style.display='block';
  document.getElementById('gp-name').textContent = '👮 '+o.name;
  document.getElementById('gp-rows').innerHTML = `
    <div class="gp-row"><span class="gp-label">Last seen</span><span class="gp-val">${o.last_seen.substring(0,16)}</span></div>
    <div class="gp-row"><span class="gp-label">Growers nearby</span><span class="gp-val">${nearby.length}</span></div>
    <hr style="border-color:var(--border);margin:8px 0">
    <div style="font-size:9px;color:var(--muted);margin-bottom:6px">
      CLOSEST GROWERS &nbsp;
      <span style="color:var(--green)">█</span> assigned &nbsp;
      <span style="color:var(--blue)">█</span> other
    </div>
    ${nearby.map(g=>{
      const isAssigned = g.assigned_officer_id === o.officer_id;
      return `<div class="gp-row">
        <span class="gp-label" style="color:${isAssigned?'var(--green)':'var(--muted)'}">${g.name} ${g.surname}</span>
        <span class="gp-val" style="color:var(--amber)">${g.dist.toFixed(2)}km</span>
      </div>`;
    }).join('')}
  `;

  document.querySelectorAll('.sr-item').forEach(el=>el.classList.remove('active'));
  document.querySelector(`.sr-item[data-oi="${i}"]`)?.classList.add('active');
}

function clearOfficerSearch(){
  document.getElementById('officer-search').value='';
  document.getElementById('officer-results').innerHTML='<div class="sr-empty">Type to search officers</div>';
  clearSearchLayers();
  document.getElementById('grower-panel').style.display='none';
}
</script>
</body>
</html>
