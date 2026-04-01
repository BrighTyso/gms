<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

$days     = isset($_GET['days'])     ? min((int)$_GET['days'], 90) : 30;
$radiusKm = isset($_GET['radius'])   ? min((float)$_GET['radius'], 20) : 5;

$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$seasonId=(int)$row['id']; $r->free();}

// Load all growers with location + visit status + assigned officer + loan/payment data
$growers = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           ll.latitude AS lat, ll.longitude AS lng,
           v.last_visit,
           DATEDIFF(NOW(), v.last_visit) AS days_since,
           fo.name AS officer_name, fo.id AS officer_id, fo.userid AS officer_userid,
           COALESCE(ln.loan_value, 0)   AS loan_value,
           COALESCE(py.amount_paid, 0)  AS amount_paid,
           COALESCE(ln.loan_value, 0) - COALESCE(py.amount_paid, 0) AS outstanding,
           COALESCE(ln.loan_count, 0)   AS loan_count
    FROM growers g
    JOIN lat_long ll ON ll.growerid=g.id
    LEFT JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId
    LEFT JOIN field_officers fo ON fo.userid=gfo.field_officerid
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=g.id
    LEFT JOIN (
        SELECT l.growerid,
               COUNT(*) AS loan_count,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS loan_value
        FROM loans l
        LEFT JOIN (
            SELECT pr2.productid, pr2.splitid, pr2.amount, pr2.seasonid
            FROM prices pr2
            INNER JOIN (SELECT productid, splitid, seasonid, MAX(id) AS max_id FROM prices WHERE seasonid=$seasonId GROUP BY productid, splitid, seasonid) lt ON lt.max_id=pr2.id
        ) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE l.seasonid=$seasonId
        GROUP BY l.growerid
    ) ln ON ln.growerid=g.id
    LEFT JOIN (
        SELECT growerid, COALESCE(SUM(amount),0) AS amount_paid
        FROM loan_payments WHERE seasonid=$seasonId
        GROUP BY growerid
    ) py ON py.growerid=g.id
    WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    ORDER BY g.name
");
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

// ── Load latest location ping per field officer ───────────────────────────────
$officerPings = [];
$r = $conn->query("
    SELECT
        fo.id          AS officer_id,
        fo.name        AS officer_name,
        dl.latitude    AS lat,
        dl.longitude   AS lng,
        dl.battery_level,
        dl.device_timestamp,
        TIMESTAMPDIFF(MINUTE, dl.device_timestamp, NOW()) AS mins_ago
    FROM field_officers fo
    JOIN (
        SELECT officer_id, latitude, longitude, battery_level, device_timestamp,
               ROW_NUMBER() OVER (PARTITION BY officer_id ORDER BY device_timestamp DESC) AS rn
        FROM device_locations
        WHERE device_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ) dl ON dl.officer_id = fo.id AND dl.rn = 1
    WHERE dl.latitude IS NOT NULL
      AND dl.latitude  != 0
      AND dl.longitude != 0
");
if($r){ while($row=$r->fetch_assoc()) $officerPings[]=$row; $r->free(); }

$conn->close();

// ── Cluster growers using simple grid bucketing ───────────────────────────────
// Round lat/lng to nearest ~5km grid cell (0.05 degrees ≈ 5.5km)
$gridSize = $radiusKm / 110; // degrees per km ≈ 1/110
$clusters = [];

foreach($growers as $g){
    $lat = (float)$g['lat'];
    $lng = (float)$g['lng'];
    // Round to grid
    $gridLat = round($lat / $gridSize) * $gridSize;
    $gridLng = round($lng / $gridSize) * $gridSize;
    $key     = round($gridLat,4).','.round($gridLng,4);

    if(!isset($clusters[$key])){
        $clusters[$key] = [
            'key'         => $key,
            'lat'         => $gridLat,
            'lng'         => $gridLng,
            'growers'     => [],
            'visited'     => 0,
            'overdue'     => 0,
            'never'       => 0,
            'officers'    => [],
            'officer_ids' => [],
            'loan_value'  => 0,
            'amount_paid' => 0,
            'outstanding' => 0,
            'loan_count'  => 0,
        ];
    }
    $clusters[$key]['growers'][] = $g;

    if(!$g['last_visit']){
        $clusters[$key]['never']++;
    } elseif($g['days_since'] >= 14){
        $clusters[$key]['overdue']++;
    } else {
        $clusters[$key]['visited']++;
    }

    if($g['officer_name'] && !in_array($g['officer_name'],$clusters[$key]['officers'])){
        $clusters[$key]['officers'][]    = $g['officer_name'];
        $clusters[$key]['officer_ids'][] = $g['officer_userid'];
    }
    $clusters[$key]['loan_value']  += (float)$g['loan_value'];
    $clusters[$key]['amount_paid'] += (float)$g['amount_paid'];
    $clusters[$key]['outstanding'] += (float)$g['outstanding'];
    $clusters[$key]['loan_count']  += (int)$g['loan_count'];
}

// Calculate coverage % per cluster and sort by worst coverage
foreach($clusters as &$c){
    $total = count($c['growers']);
    $c['total']        = $total;
    $c['coverage_pct'] = $total > 0 ? round(($c['visited'] / $total) * 100) : 0;
    $c['center_label'] = 'Area '.round($c['lat'],3).', '.round($c['lng'],3);
}
unset($c);

usort($clusters, fn($a,$b) => $a['coverage_pct'] - $b['coverage_pct']);

$totalClusters  = count($clusters);
$criticalCount  = count(array_filter($clusters, fn($c) => $c['coverage_pct'] < 30));
$avgCoverage    = $totalClusters > 0 ? round(array_sum(array_column($clusters,'coverage_pct')) / $totalClusters) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Cluster Performance</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--text:#c8e6c9;--muted:#4a6b4a;--radius:6px}
  .leaflet-marker-icon.leaflet-interactive { cursor:pointer }
  .leaflet-marker-icon:not(.leaflet-interactive){ pointer-events:none !important }
  .leaflet-pane path.leaflet-interactive{ cursor:pointer }
  .leaflet-pane svg path:not(.leaflet-interactive){ pointer-events:none !important }
    width:26px;height:26px;border-radius:50%;
    background:#4a9eff;border:2px solid #fff;
    display:flex;align-items:center;justify-content:center;
    font-size:13px;box-shadow:0 0 0 4px rgba(74,158,255,.25);
    animation:officer-pulse 2s ease-in-out infinite;
  }
  @keyframes officer-pulse{
    0%,100%{box-shadow:0 0 0 4px rgba(74,158,255,.25)}
    50%{box-shadow:0 0 0 9px rgba(74,158,255,.08)}
  }
  .officer-stale .officer-icon{background:#888;animation:none;box-shadow:0 0 0 4px rgba(136,136,136,.2)}
  html,body{height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text)}
  .shell{display:grid;grid-template-rows:56px 1fr;grid-template-columns:340px 1fr;height:100vh}
  header{grid-column:1/-1;display:flex;align-items:center;gap:10px;padding:0 20px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  aside{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
  .stats-bar{display:flex;border-bottom:1px solid var(--border)}
  .stat-box{flex:1;padding:10px;text-align:center;border-right:1px solid var(--border)}
  .stat-box:last-child{border-right:none}
  .stat-val{font-family:'Syne',sans-serif;font-size:18px;font-weight:800}
  .stat-label{font-size:9px;color:var(--muted);text-transform:uppercase;margin-top:2px}
  .sb-head{padding:10px 14px 8px;border-bottom:1px solid var(--border)}
  .sb-head h2{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase}
  .search-wrap{padding:8px 14px;border-bottom:1px solid var(--border);position:relative}
  .search-wrap input{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);
    font-family:'Space Mono',monospace;font-size:11px;padding:6px 28px 6px 10px;border-radius:4px;outline:none}
  .search-wrap input:focus{border-color:var(--green)}
  .search-wrap input::placeholder{color:var(--muted)}
  .search-clear{position:absolute;right:20px;top:50%;transform:translateY(-50%);
    cursor:pointer;color:var(--muted);font-size:14px;display:none;background:none;border:none;padding:2px}
  .search-clear:hover{color:var(--green)}
  .search-result-count{font-size:9px;color:var(--muted);padding:4px 14px;border-bottom:1px solid var(--border);display:none}
  .cl-item.search-hidden{display:none}
  .cl-item.search-match .cl-name{color:var(--green)}
  .cluster-list{flex:1;overflow-y:auto}
  .cluster-list::-webkit-scrollbar{width:3px}
  .cluster-list::-webkit-scrollbar-thumb{background:var(--border)}
  .cl-item{padding:10px 14px;border-bottom:1px solid #0f1a0f;cursor:pointer;border-left:3px solid transparent;transition:background .15s}
  .cl-item:hover{background:rgba(61,220,104,.04)}
  .cl-item.selected{background:rgba(61,220,104,.08);border-left-color:var(--green)}
  .cl-name{font-size:11px;font-weight:700}
  .cl-bar{height:4px;background:var(--border);border-radius:2px;margin-top:6px}
  .cl-fill{height:100%;border-radius:2px}
  .cl-stats{display:flex;gap:8px;margin-top:4px;font-size:9px;color:var(--muted);flex-wrap:wrap}
  .map-wrap{position:relative}
  #map{width:100%;height:100%}
  #cluster-panel{position:absolute;top:16px;right:16px;z-index:1000;width:260px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px;display:none;box-shadow:0 4px 20px rgba(0,0,0,.7);max-height:80vh;overflow-y:auto}
  .cp-grower-row{display:flex;justify-content:space-between;font-size:10px;padding:5px 6px;border-radius:4px;cursor:pointer;margin-bottom:2px;border:1px solid transparent;transition:background .15s,border-color .15s}
  .cp-grower-row:hover{background:rgba(61,220,104,.08);border-color:var(--green-dim)}
  .cp-grower-row.pinged{background:rgba(61,220,104,.15);border-color:var(--green)}
  .cp-close{font-size:11px;color:var(--muted);cursor:pointer;padding:1px 6px;border:1px solid var(--border);border-radius:3px}
  .cp-row{display:flex;justify-content:space-between;font-size:11px;margin-top:5px}
  .cp-label{color:var(--muted)}
  .cp-val{color:var(--text);font-weight:700}
</style>
</head>
<body>
<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Clusters</div>
    <a href="reports_hub.php" class="back">← Reports</a>
  <a href="season_dashboard.php" class="back">🌱 Season</a>
    <form method="GET" style="display:flex;gap:6px">
      <select name="radius" onchange="this.form.submit()">
        <option value="3"  <?=$radiusKm==3?'selected':''?>>3km grid</option>
        <option value="5"  <?=$radiusKm==5?'selected':''?>>5km grid</option>
        <option value="10" <?=$radiusKm==10?'selected':''?>>10km grid</option>
        <option value="20" <?=$radiusKm==20?'selected':''?>>20km grid</option>
      </select>
      <select name="days" onchange="this.form.submit()">
        <option value="14" <?=$days==14?'selected':''?>>14 days</option>
        <option value="30" <?=$days==30?'selected':''?>>30 days</option>
        <option value="60" <?=$days==60?'selected':''?>>60 days</option>
      </select>
    </form>
    <button id="btn-officers" onclick="toggleOfficers()" style="background:var(--surface);border:1px solid var(--blue);color:var(--blue);font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;cursor:pointer">📡 Officers ON</button>
    <div style="margin-left:auto;font-size:10px;color:var(--muted)"><?=$totalClusters?> clusters · <?=$avgCoverage?>% avg coverage</div>
  </header>
  <aside>
    <div class="stats-bar">
      <div class="stat-box"><div class="stat-val"><?=$totalClusters?></div><div class="stat-label">Clusters</div></div>
      <div class="stat-box"><div class="stat-val" style="color:var(--red)"><?=$criticalCount?></div><div class="stat-label">Critical</div></div>
      <div class="stat-box"><div class="stat-val" style="color:var(--green)"><?=$avgCoverage?>%</div><div class="stat-label">Avg Cov</div></div>
    </div>
    <div class="sb-head">
      <h2>Areas by Coverage</h2>
      <p style="font-size:10px;color:var(--muted);margin-top:2px">Worst coverage first · <?=$radiusKm?>km grid</p>
    </div>
    <div class="search-wrap">
      <input type="text" id="cluster-search" placeholder="🔍  Search grower or officer..." oninput="handleSearch(this.value)">
      <button class="search-clear" id="search-clear-btn" onclick="clearSearch()">✕</button>
    </div>
    <div class="search-result-count" id="search-result-count"></div>
    <div class="cluster-list">
    <?php foreach($clusters as $i=>$c):
      $col = $c['coverage_pct']>=70?'var(--green)':($c['coverage_pct']>=40?'var(--amber)':'var(--red)');
      $officers = implode(', ', array_slice($c['officers'], 0, 2));
      if(count($c['officers'])>2) $officers .= ' +'.count($c['officers'])-2;
    ?>
    <?php
      $searchGrowers = implode('|', array_map(fn($g) => strtolower($g['name'].' '.$g['surname'].' '.$g['grower_num']), $c['growers']));
      $searchOfficers = implode('|', array_map('strtolower', $c['officers']));
    ?>
    <div class="cl-item" data-idx="<?=$i?>" data-growers="<?=htmlspecialchars($searchGrowers)?>" data-officers="<?=htmlspecialchars($searchOfficers)?>" onclick="flyToCluster(<?=$i?>)">
      <div class="cl-name">
        <?=$c['total']?> growers
        <span style="color:<?=$col?>;margin-left:6px"><?=$c['coverage_pct']?>%</span>
      </div>
      <div class="cl-stats">
        <span style="color:var(--green)"><?=$c['visited']?> visited</span>
        <span style="color:var(--amber)"><?=$c['overdue']?> overdue</span>
        <span style="color:var(--red)"><?=$c['never']?> never</span>
        <?php if($officers): ?><span>👮 <?=htmlspecialchars($officers)?></span><?php endif?>
      </div>
      <div class="cl-bar"><div class="cl-fill" style="width:<?=$c['coverage_pct']?>%;background:<?=$col?>"></div></div>
    </div>
    <?php endforeach?>
    </div>
  </aside>
  <div class="map-wrap">
    <div id="map"></div>
    <div id="cluster-panel">
      <div class="cp-title">
        <span id="cp-title">—</span>
        <span class="cp-close" onclick="
          document.getElementById('cluster-panel').style.display='none';
          if(selectedMarker!==null){
            const c=clusters[selectedMarker];
            const col=c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
            const r=Math.max(10,Math.min(35,c.total*3));
            clusterMarkers[selectedMarker].setStyle({radius:r,color:col,fillColor:col,fillOpacity:.25,weight:2});
            clusterCircles[selectedMarker].setStyle({weight:0,opacity:0,fillOpacity:0});
            growerMarkers.forEach(group=>group.forEach(({marker,col})=>{
              marker.setStyle({radius:4,color:col,fillColor:col,fillOpacity:.5,weight:1,opacity:1});
            }));
            selectedMarker=null;
            pinnedGrower=null;
          }
          document.querySelectorAll('.cl-item').forEach(el=>el.classList.remove('selected'));
        ">✕</span>
      </div>
      <div id="cp-rows"></div>
    </div>
  </div>
</div>
<script>
const clusters = <?=json_encode(array_values($clusters))?>;
const officersData = <?=json_encode(array_values($officerPings))?>;
const map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',maxZoom:19}).addTo(map);

const clusterMarkers = [];
const clusterCircles = [];
const bounds = [];

clusters.forEach((c,i) => {
  const lat=c.lat, lng=c.lng;
  bounds.push([lat,lng]);
  const col = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
  const r   = Math.max(10, Math.min(35, c.total * 3));
  const mk  = L.circleMarker([lat,lng],{
    radius:r, color:col, fillColor:col, fillOpacity:.25, weight:2,
    interactive:true, bubblingMouseEvents:false
  }).addTo(map);

  const label = L.divIcon({
    className:'', iconSize:[40,20], iconAnchor:[20,10],
    html:`<div style="text-align:center;font-family:'Space Mono',monospace;font-size:10px;font-weight:700;color:${col};pointer-events:none">${c.coverage_pct}%<br><span style="font-size:8px;color:#4a6b4a">${c.total}</span></div>`
  });
  L.marker([lat,lng],{icon:label,interactive:false,bubblingMouseEvents:false}).addTo(map);
  mk.on('click',()=>{ highlightMarker(i); showCluster(i); });
  // Transparent larger hit-area circle so clicking near the marker works
  const hitArea = L.circleMarker([lat,lng],{
    radius: Math.max(r+10, 20),
    color:'transparent', fillColor:'transparent',
    fillOpacity:0.01, weight:0,
    interactive:true, bubblingMouseEvents:false
  }).addTo(map);
  hitArea.on('click',()=>{ highlightMarker(i); showCluster(i); });
  clusterMarkers.push(mk);

  // Compute actual radius from farthest grower in this cluster
  let maxDist = 500; // minimum 500m
  c.growers.forEach(g => {
    const glat=parseFloat(g.lat), glng=parseFloat(g.lng);
    if(!glat||!glng) return;
    const dlat=(glat-lat)*111000;
    const dlng=(glng-lng)*111000*Math.cos(lat*Math.PI/180);
    const dist=Math.sqrt(dlat*dlat+dlng*dlng);
    if(dist>maxDist) maxDist=dist;
  });
  const col2 = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
  const circle = L.circle([lat,lng],{
    radius: maxDist + 200,
    color: col2, fillColor: col2,
    fillOpacity: 0, weight: 0,
    dashArray: '6 5', opacity: 0,
    interactive: false
  }).addTo(map);
  clusterCircles.push(circle);
});

// Also show individual growers — track by cluster index
const growerMarkers = clusters.map(() => []);

clusters.forEach((c,ci) => {
  c.growers.forEach(g => {
    const lat=parseFloat(g.lat), lng=parseFloat(g.lng);
    if(!lat||!lng) return;
    const col = !g.last_visit?'#e84040':g.days_since>=14?'#f5a623':'#3ddc68';
    const mk = L.circleMarker([lat,lng],{radius:4,color:col,fillColor:col,fillOpacity:.5,weight:1})
      .addTo(map).bindPopup(`<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>${g.last_visit?g.days_since+'d since visit':'Never visited'}${g.officer_name?'<br>👮 '+g.officer_name:''}${parseFloat(g.outstanding||0)>0?'<br>💸 $'+parseFloat(g.outstanding).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})+' owed':''}`);
    growerMarkers[ci].push({marker:mk, col});
  });
});

if(bounds.length) map.fitBounds(L.latLngBounds(bounds).pad(0.2));
else map.setView([-17.8292,31.0522],10);

// ── Field Officer location markers ───────────────────────────────────────────
const officerMarkers = [];
let officersVisible = true;

officersData.forEach(o => {
  const lat = parseFloat(o.lat), lng = parseFloat(o.lng);
  if(!lat || !lng) return;

  const stale = parseInt(o.mins_ago) > 60;
  const minsAgo = parseInt(o.mins_ago);
  const timeLabel = minsAgo < 1   ? 'Just now'
                  : minsAgo < 60  ? minsAgo + 'm ago'
                  : minsAgo < 1440? Math.floor(minsAgo/60) + 'h ago'
                  : 'Over a day ago';
  const batteryColor = !o.battery_level ? '#888'
                     : o.battery_level >= 50 ? '#3ddc68'
                     : o.battery_level >= 20 ? '#f5a623' : '#e84040';
  const batteryLabel = o.battery_level ? `🔋 <span style="color:${batteryColor}">${o.battery_level}%</span>` : '';

  const icon = L.divIcon({
    className: stale ? 'officer-stale' : '',
    iconSize:  [26, 26],
    iconAnchor:[13, 13],
    html: `<div class="officer-icon">👮</div>`
  });

  const popup = `
    <div style="font-family:'Space Mono',monospace;font-size:11px;min-width:150px">
      <div style="font-weight:700;color:#4a9eff;margin-bottom:4px">👮 ${o.officer_name}</div>
      <div style="color:#aaa;margin-bottom:2px">🕐 ${timeLabel}</div>
      ${batteryLabel ? `<div>${batteryLabel}</div>` : ''}
      ${stale ? '<div style="color:#f5a623;font-size:10px;margin-top:4px">⚠ Last seen &gt;1h ago</div>' : ''}
    </div>`;

  const mk = L.marker([lat, lng], {icon})
    .addTo(map)
    .bindPopup(popup);

  officerMarkers.push(mk);
});

function toggleOfficers(){
  officersVisible = !officersVisible;
  const btn = document.getElementById('btn-officers');
  officerMarkers.forEach(mk => {
    if(officersVisible) mk.addTo(map);
    else map.removeLayer(mk);
  });
  btn.textContent = `📡 Officers ${officersVisible ? 'ON' : 'OFF'}`;
  btn.style.borderColor = officersVisible ? 'var(--blue)' : 'var(--muted)';
  btn.style.color        = officersVisible ? 'var(--blue)' : 'var(--muted)';
}

let selectedMarker = null;

function flyToCluster(i){
  const c=clusters[i];
  map.setView([c.lat,c.lng],12,{animate:true});
  document.querySelectorAll('.cl-item').forEach(el=>el.classList.remove('selected'));
  document.querySelector(`.cl-item[data-idx="${i}"]`)?.classList.add('selected');
  highlightMarker(i);
  showCluster(i);
}

function highlightMarker(i){
  // Reset previous cluster marker + circle
  if(selectedMarker !== null){
    const prev = clusters[selectedMarker];
    const prevCol = prev.coverage_pct>=70?'#3ddc68':prev.coverage_pct>=40?'#f5a623':'#e84040';
    const prevR   = Math.max(10, Math.min(35, prev.total * 3));
    clusterMarkers[selectedMarker].setStyle({
      radius: prevR, color: prevCol, fillColor: prevCol,
      fillOpacity: .25, weight: 2, opacity: 1
    });
    // Hide circle
    clusterCircles[selectedMarker].setStyle({weight:0, opacity:0, fillOpacity:0});
    // Restore all grower markers to normal
    growerMarkers.forEach((group) => {
      group.forEach(({marker, col}) => {
        marker.setStyle({radius:4, color:col, fillColor:col, fillOpacity:.5, weight:1, opacity:1});
      });
    });
  }

  selectedMarker = i;
  const c   = clusters[i];
  const col = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
  const r   = Math.max(10, Math.min(35, c.total * 3));

  // Glow the cluster marker
  clusterMarkers[i].setStyle({
    radius: r + 8, color: '#ffffff', fillColor: col,
    fillOpacity: .55, weight: 3, opacity: 1
  });
  clusterMarkers[i].bringToFront();

  // Show boundary circle with glow
  clusterCircles[i].setStyle({
    weight: 2, opacity: .9, color: col,
    fillColor: col, fillOpacity: .06,
    dashArray: '8 6'
  });

  // Dim ALL grower markers first
  growerMarkers.forEach((group) => {
    group.forEach(({marker, col}) => {
      marker.setStyle({radius:3, color:col, fillColor:col, fillOpacity:.08, weight:1, opacity:.2});
    });
  });

  // Glow THIS cluster's growers
  growerMarkers[i].forEach(({marker, col}) => {
    marker.setStyle({
      radius: 7, color: '#ffffff', fillColor: col,
      fillOpacity: .9, weight: 2, opacity: 1
    });
    marker.bringToFront();
  });
}

function showCluster(i){
  const c=clusters[i];
  const col = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
  const recPct = c.loan_value>0 ? Math.round((c.amount_paid/c.loan_value)*100) : 0;
  const recCol = recPct>=70?'#3ddc68':recPct>=40?'#f5a623':'#e84040';
  document.getElementById('cluster-panel').style.display='block';
  document.getElementById('cp-title').textContent = c.total+' growers · '+c.coverage_pct+'%';

  // Officer rows with live location + last-seen time
  const officerRows = c.officers.length ? c.officers.map((o,oi)=>{
    const oUserId = c.officer_ids ? c.officer_ids[oi] : null;
    const oData   = officersData.find(od => od.officer_id == oUserId || od.officer_name === o);
    const stale   = oData && parseInt(oData.mins_ago) > 60;
    const mins    = oData ? parseInt(oData.mins_ago) : null;
    const timeLabel = mins===null ? '' : mins<1?'Just now':mins<60?mins+'m ago':Math.floor(mins/60)+'h ago';
    const timeStr = timeLabel ? `<span style="font-size:9px;color:${stale?'#f5a623':'#4a6b4a'};margin-left:4px">· ${timeLabel}</span>` : '';
    const locBtn  = oData
      ? `<span onclick="flyToOfficer('${oData.officer_id}')" style="cursor:pointer;color:#4a9eff;font-size:9px;border:1px solid #002050;padding:1px 6px;border-radius:3px;margin-left:auto;white-space:nowrap">${stale?'⚠':'📍'} Locate</span>`
      : '<span style="font-size:9px;color:#4a6b4a;margin-left:auto">offline</span>';
    const battStr = oData && oData.battery_level
      ? `<span style="font-size:8px;color:${oData.battery_level>=50?'#3ddc68':oData.battery_level>=20?'#f5a623':'#e84040'}">🔋${oData.battery_level}%</span>`
      : '';
    return `<div style="display:flex;align-items:center;gap:4px;padding:5px 0;border-bottom:1px solid #0d180d">
      <span style="font-size:11px;color:#3ddc68">👮 ${o}</span>${timeStr}${battStr ? ' '+battStr : ''}${locBtn}
    </div>`;
  }).join('') : '<div style="font-size:11px;color:var(--muted);padding:4px 0">None assigned to this area</div>';

  // Grower rows: visit status + outstanding amount
  const growerRows = c.growers.map((g,gi)=>{
    const gcol   = !g.last_visit?'#e84040':parseInt(g.days_since)>=14?'#f5a623':'#3ddc68';
    const vlbl   = !g.last_visit?'Never visited':parseInt(g.days_since)+'d ago';
    const owed   = parseFloat(g.outstanding||0);
    const owedStr = owed>0
      ? `<span style="color:#e84040;font-size:9px">• $${owed.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2})} owed</span>`
      : '<span style="color:#1a5e30;font-size:9px">• clear</span>';
    return `<div class="cp-grower-row" id="grow-row-${i}-${gi}" onclick="flyToGrower(${i},${gi})">
      <div style="min-width:0;flex:1">
        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700">
          ${g.name} ${g.surname} <span style="color:var(--muted);font-size:9px">#${g.grower_num}</span>
        </div>
        <div style="display:flex;gap:6px;margin-top:2px;flex-wrap:wrap">
          <span style="color:${gcol};font-size:9px">${vlbl}</span>${owedStr}
        </div>
      </div>
    </div>`;
  }).join('');

  const fmt = v => '$'+parseFloat(v||0).toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});

  document.getElementById('cp-rows').innerHTML = `
    <!-- Financial summary mini cards -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-bottom:8px">
      <div style="background:#0d180d;border-radius:4px;padding:7px 10px">
        <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:${col}">${c.coverage_pct}%</div>
        <div style="font-size:8px;color:var(--muted);text-transform:uppercase">Coverage</div>
      </div>
      <div style="background:#0d180d;border-radius:4px;padding:7px 10px">
        <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800">${c.total}</div>
        <div style="font-size:8px;color:var(--muted);text-transform:uppercase">Growers</div>
      </div>
      <div style="background:#0d180d;border-radius:4px;padding:7px 10px">
        <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:#3ddc68">${fmt(c.loan_value)}</div>
        <div style="font-size:8px;color:var(--muted);text-transform:uppercase">Loan Value</div>
      </div>
      <div style="background:#0d180d;border-radius:4px;padding:7px 10px">
        <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:#e84040">${fmt(c.outstanding)}</div>
        <div style="font-size:8px;color:var(--muted);text-transform:uppercase">Outstanding</div>
      </div>
    </div>

    <!-- Recovery progress bar -->
    <div style="height:5px;background:var(--border);border-radius:3px;margin-bottom:3px;overflow:hidden">
      <div style="height:100%;width:${Math.min(100,recPct)}%;background:${recCol};border-radius:3px;transition:width .6s"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--muted);margin-bottom:8px">
      <span>Recovery ${recPct}%</span>
      <span>${fmt(c.amount_paid)} paid · ${c.loan_count} loans</span>
    </div>

    <!-- Visit counts -->
    <div style="display:flex;gap:8px;font-size:9px;margin-bottom:8px;flex-wrap:wrap;padding:4px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
      <span style="color:#3ddc68">✓ ${c.visited} visited</span>
      <span style="color:#f5a623">⏰ ${c.overdue} overdue</span>
      <span style="color:#e84040">✕ ${c.never} never</span>
    </div>

    <!-- Officers -->
    <div style="font-size:9px;color:var(--muted);margin:6px 0 4px;text-transform:uppercase;letter-spacing:.4px">Field Officers</div>
    ${officerRows}

    <!-- Growers -->
    <div style="font-size:9px;color:var(--muted);margin:8px 0 4px;text-transform:uppercase;letter-spacing:.4px">Growers — tap to locate</div>
    ${growerRows}
  `;
  document.querySelectorAll('.cl-item').forEach(el=>el.classList.remove('selected'));
  document.querySelector(`.cl-item[data-idx="${i}"]`)?.classList.add('selected');
  document.querySelector(`.cl-item[data-idx="${i}"]`)?.scrollIntoView({behavior:'smooth',block:'nearest'});
}

let pinnedGrower = null;

function flyToGrower(ci, gi){
  const g   = clusters[ci].growers[gi];
  const lat = parseFloat(g.lat), lng = parseFloat(g.lng);
  if(!lat||!lng) return;

  // Clear previous pinned highlight
  if(pinnedGrower){
    const {ci:pci, gi:pgi} = pinnedGrower;
    const {marker, col} = growerMarkers[pci][pgi];
    marker.setStyle({radius:7, color:'#ffffff', fillColor:col, fillOpacity:.9, weight:2, opacity:1});
    document.getElementById(`grow-row-${pci}-${pgi}`)?.classList.remove('pinged');
  }

  // Fly to grower
  map.setView([lat, lng], 16, {animate:true});

  // Pulse the marker — extra large white ring
  const {marker, col} = growerMarkers[ci][gi];
  marker.setStyle({radius:14, color:'#ffffff', fillColor:col, fillOpacity:1, weight:3, opacity:1});
  marker.bringToFront();
  marker.openPopup();

  // Highlight the sidebar row
  document.getElementById(`grow-row-${ci}-${gi}`)?.classList.add('pinged');
  pinnedGrower = {ci, gi};
}
function flyToOfficer(officerId){
  const idx = officersData.findIndex(o => o.officer_id == officerId);
  if(idx === -1) return;
  const o   = officersData[idx];
  const lat = parseFloat(o.lat), lng = parseFloat(o.lng);
  if(!lat || !lng) return;
  if(!officersVisible) toggleOfficers(); // auto-enable layer if hidden
  map.setView([lat, lng], 15, {animate:true});
  officerMarkers[idx]?.openPopup();
}

// ── Search ────────────────────────────────────────────────────────────────────
let searchMatchIndices = [];

function handleSearch(q) {
  q = q.trim().toLowerCase();
  const clearBtn = document.getElementById('search-clear-btn');
  const countEl  = document.getElementById('search-result-count');
  const items    = document.querySelectorAll('.cl-item');

  clearBtn.style.display = q ? 'block' : 'none';
  if (!q) { clearSearch(); return; }

  searchMatchIndices = [];
  let matchCount = 0;

  items.forEach(el => {
    const growers  = el.dataset.growers  || '';
    const officers = el.dataset.officers || '';
    const idx      = parseInt(el.dataset.idx);
    const matched  = growers.includes(q) || officers.includes(q);
    el.classList.toggle('search-hidden', !matched);
    el.classList.toggle('search-match',   matched);
    if (matched) { matchCount++; searchMatchIndices.push(idx); }
  });

  countEl.style.display = 'block';
  if (matchCount === 0) {
    countEl.textContent = 'No matches found';
    countEl.style.color = 'var(--red)';
  } else {
    countEl.textContent = matchCount + ' cluster' + (matchCount > 1 ? 's' : '') + ' match';
    countEl.style.color = 'var(--green)';
  }

  clusters.forEach((c, i) => {
    const isMatch = searchMatchIndices.includes(i);
    const col = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
    const r   = Math.max(10, Math.min(35, c.total * 3));
    if (isMatch) {
      clusterMarkers[i].setStyle({radius:r+4, color:'#ffffff', fillColor:col, fillOpacity:.5, weight:3, opacity:1});
      clusterMarkers[i].bringToFront();
    } else {
      clusterMarkers[i].setStyle({radius:r, color:col, fillColor:col, fillOpacity:.06, weight:1, opacity:.3});
    }
  });

  if (matchCount === 1) {
    flyToCluster(searchMatchIndices[0]);
  } else if (matchCount > 1) {
    const matchBounds = searchMatchIndices.map(i => [clusters[i].lat, clusters[i].lng]);
    map.fitBounds(L.latLngBounds(matchBounds).pad(0.3), {animate:true});
  }
}

function clearSearch() {
  document.getElementById('cluster-search').value = '';
  document.getElementById('search-clear-btn').style.display = 'none';
  document.getElementById('search-result-count').style.display = 'none';
  document.querySelectorAll('.cl-item').forEach(el => {
    el.classList.remove('search-hidden', 'search-match');
  });
  clusters.forEach((c, i) => {
    if (i === selectedMarker) return;
    const col = c.coverage_pct>=70?'#3ddc68':c.coverage_pct>=40?'#f5a623':'#e84040';
    const r   = Math.max(10, Math.min(35, c.total * 3));
    clusterMarkers[i].setStyle({radius:r, color:col, fillColor:col, fillOpacity:.25, weight:2, opacity:1});
  });
  searchMatchIndices = [];
}

</script>
</html>
