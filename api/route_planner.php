<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Route Planner</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b47eff;--text:#c8e6c9;--muted:#4a6b4a;--radius:6px}
  html,body{height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text)}
  .shell{display:grid;grid-template-rows:56px 1fr;grid-template-columns:380px 1fr;height:100vh}
  header{grid-column:1/-1;display:flex;align-items:center;gap:8px;padding:0 16px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select,input{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  .btn{font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--green);color:var(--green);background:var(--green-dim);cursor:pointer;text-decoration:none;white-space:nowrap}
  .btn:hover{background:#1e4a22}
  .btn.sec{border-color:var(--border);color:var(--muted);background:transparent}
  .btn.sec:hover{border-color:var(--amber);color:var(--amber)}

  aside{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
  .sb-head{padding:10px 14px 8px;border-bottom:1px solid var(--border)}
  .sb-head h2{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase}
  .sb-head p{font-size:10px;color:var(--muted);margin-top:2px}

  /* Progress bar */
  .progress-bar{padding:8px 14px;border-bottom:1px solid var(--border);background:#0d150d}
  .pb-label{display:flex;justify-content:space-between;font-size:10px;margin-bottom:5px}
  .pb-track{height:6px;background:var(--border);border-radius:3px}
  .pb-fill{height:100%;border-radius:3px;background:var(--green);transition:width .4s}

  /* Summary row */
  .route-summary{display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid var(--border);background:#0d150d}
  .rs-cell{padding:8px 10px;text-align:center;border-right:1px solid var(--border)}
  .rs-cell:last-child{border-right:none}
  .rs-val{font-family:'Syne',sans-serif;font-size:15px;font-weight:800}
  .rs-label{font-size:9px;color:var(--muted);text-transform:uppercase;margin-top:1px}

  .route-list{flex:1;overflow-y:auto}
  .route-list::-webkit-scrollbar{width:3px}
  .route-list::-webkit-scrollbar-thumb{background:var(--border)}

  .stop-item{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid #0f1a0f;cursor:pointer;transition:background .15s;border-left:3px solid transparent}
  .stop-item:hover{background:rgba(61,220,104,.04)}
  .stop-item.selected{background:rgba(61,220,104,.08);border-left-color:var(--green)}
  .stop-item.done{opacity:.45;border-left-color:var(--muted)}
  .stop-num{width:24px;height:24px;border-radius:50%;background:var(--green-dim);border:1px solid var(--green);color:var(--green);font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
  .stop-num.start-pin{background:#001020;border-color:var(--blue);color:var(--blue)}
  .stop-num.high{background:#200000;border-color:var(--red);color:var(--red)}
  .stop-num.med{background:#1e1500;border-color:var(--amber);color:var(--amber)}
  .stop-body{flex:1;min-width:0}
  .stop-name{font-size:11px;font-weight:700}
  .stop-meta{font-size:9px;color:var(--muted);margin-top:3px;display:flex;gap:6px;flex-wrap:wrap}
  .leg-dist{color:var(--amber);font-weight:700}
  .eta{color:var(--purple)}
  .stop-history{font-size:9px;color:var(--muted);margin-top:3px}
  .stop-history span{display:inline-block;background:#0d150d;border:1px solid var(--border);border-radius:3px;padding:1px 5px;margin-right:3px;margin-top:2px}
  .geo-count{color:var(--blue);font-size:9px;margin-top:2px}

  .map-wrap{position:relative}
  #map{width:100%;height:100%}

  /* Stop detail panel */
  #stop-panel{position:absolute;top:16px;right:16px;z-index:1000;width:270px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:14px;display:none;box-shadow:0 4px 20px rgba(0,0,0,.7);max-height:85vh;overflow-y:auto}
  #stop-panel::-webkit-scrollbar{width:3px}
  #stop-panel::-webkit-scrollbar-thumb{background:var(--border)}
  .sp-name{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:var(--green);margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
  .sp-close{font-size:11px;color:var(--muted);cursor:pointer;padding:1px 6px;border:1px solid var(--border);border-radius:3px}
  .sp-close:hover{color:var(--red);border-color:var(--red)}
  .sp-row{display:flex;justify-content:space-between;font-size:11px;margin-top:5px}
  .sp-label{color:var(--muted)}
  .sp-val{color:var(--text);font-weight:700}
  .sp-divider{border:none;border-top:1px solid var(--border);margin:8px 0}
  .sp-section{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);margin:8px 0 5px}
  .visit-row{font-size:10px;padding:3px 0;border-bottom:1px solid #0f1a0f;display:flex;justify-content:space-between}
  .visit-row:last-child{border-bottom:none}

  /* Print styles */
  @media print{
    .shell{display:block;height:auto}
    header,.map-wrap,#stop-panel{display:none!important}
    aside{width:100%;border:none;overflow:visible;height:auto}
    .route-list{overflow:visible;height:auto}
    .stop-item{break-inside:avoid;border-left:3px solid #ccc!important}
    .stop-num.high{border-color:#e84040!important;color:#e84040!important}
  }
  .empty-state{padding:30px 16px;text-align:center;color:var(--muted);font-size:11px}
  .empty-state b{display:block;font-size:14px;color:var(--text);margin-bottom:6px}
</style>
</head>
<body>
<?php
require "conn.php";
require "validate.php";

$officerId = isset($_GET['officer_id']) ? (int)$_GET['officer_id'] : 0;
$date      = isset($_GET['date'])       ? $_GET['date']            : date('Y-m-d');
$priority  = isset($_GET['priority'])   ? $_GET['priority']        : 'mixed';
$maxStops  = isset($_GET['max'])        ? min((int)$_GET['max'], 50) : 20;

function hdist($lat1,$lng1,$lat2,$lng2){
    $R=6371;$dLat=deg2rad($lat2-$lat1);$dLng=deg2rad($lng2-$lng1);
    $a=sin($dLat/2)*sin($dLat/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

$officers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc())$officers[]=$row;$r->free();}

$officerPos=null;
if($officerId){
    $r=$conn->query("SELECT latitude,longitude,created_at FROM device_locations WHERE officer_id=$officerId ORDER BY created_at DESC LIMIT 1");
    if($r&&$row=$r->fetch_assoc()){$officerPos=$row;$r->free();}
}

$growers=[];
if($officerId){
    $escDate=$conn->real_escape_string($date);
    $officerUserid=$officerId;
    $r=$conn->query("SELECT userid FROM field_officers WHERE id=$officerId LIMIT 1");
    if($r&&$row=$r->fetch_assoc()){$officerUserid=(int)$row['userid'];$r->free();}

    $seasonId=0;
    $r=$conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
    if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$r->free();}

    $hasAssignments=false;
    if($seasonId){
        $r=$conn->query("SELECT COUNT(*) AS cnt FROM grower_field_officer WHERE field_officerid=$officerUserid AND seasonid=$seasonId");
        if($r&&$row=$r->fetch_assoc()){$hasAssignments=$row['cnt']>0;$r->free();}
    }

    $growerJoin = $hasAssignments
        ? "JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.field_officerid=$officerUserid AND gfo.seasonid=$seasonId"
        : "";

    $r=$conn->query("
        SELECT g.id, g.grower_num, g.name, g.surname,
               ll.latitude AS lat, ll.longitude AS lng,
               v.last_visit,
               DATEDIFF('$escDate', v.last_visit) AS days_since,
               -- Visited today?
               (SELECT COUNT(*) FROM visits WHERE growerid=g.id AND DATE(created_at)='$escDate') AS visited_today,
               -- Officer passed near today?
               (SELECT COUNT(*) FROM grower_geofence_entry_point WHERE growerid=g.id AND userid=$officerUserid AND DATE(created_at)='$escDate') AS geo_today,
               -- Total geofence entries by this officer
               (SELECT COUNT(*) FROM grower_geofence_entry_point WHERE growerid=g.id AND userid=$officerUserid) AS geo_total,
               -- Total visits ever by this officer
               (SELECT COUNT(*) FROM visits WHERE growerid=g.id AND userid=$officerUserid) AS visit_count_officer
        FROM growers g
        $growerJoin
        JOIN lat_long ll ON ll.growerid=g.id
        LEFT JOIN (SELECT growerid,MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=g.id
        WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    ");
    if($r){while($row=$r->fetch_assoc())$growers[]=$row;$r->free();}
}

// ── Visit history per grower (last 3 visits) ──────────────────────────────────
$visitHistory = [];
if(!empty($growers)){
    $gids = implode(',', array_column($growers,'id'));
    $r=$conn->query("
        SELECT v.growerid, DATE(v.created_at) AS visit_date,
               fo.name AS officer_name
        FROM visits v
        LEFT JOIN field_officers fo ON fo.userid=v.userid
        WHERE v.growerid IN ($gids)
        ORDER BY v.created_at DESC
    ");
    if($r){
        $counts=[];
        while($row=$r->fetch_assoc()){
            $gid=$row['growerid'];
            if(!isset($counts[$gid])) $counts[$gid]=0;
            if($counts[$gid]<3){ $visitHistory[$gid][]=$row; $counts[$gid]++; }
        }
        $r->free();
    }
}

$conn->close();

// ── Nearest-neighbour route ───────────────────────────────────────────────────
$route=[];$totalKm=0;
// Estimate total time: avg 30 min per visit + travel at 40km/h
$AVG_VISIT_MIN = 30;
$AVG_SPEED_KMH = 40;

if($officerPos&&!empty($growers)){
    $unvisited=array_values(array_filter($growers,fn($g)=>!$g['visited_today']&&!$g['geo_today']));
    if($priority==='overdue'){
        usort($unvisited,fn($a,$b)=>($b['days_since']??999)-($a['days_since']??999));
    }
    $unvisited=array_slice($unvisited,0,$maxStops);
    $remaining=$unvisited;
    $cLat=(float)$officerPos['latitude'];$cLng=(float)$officerPos['longitude'];
    $cumTimeMin=0;

    while(!empty($remaining)){
        $bi=0;$bs=PHP_FLOAT_MAX;
        foreach($remaining as $idx=>$g){
            $d=hdist($cLat,$cLng,(float)$g['lat'],(float)$g['lng']);
            $ov=max(1,(int)($g['days_since']??0));
            $s=$priority==='nearest'?$d:($priority==='overdue'?$d:$d/log($ov+1));
            if($s<$bs){$bs=$s;$bi=$idx;}
        }
        $n=$remaining[$bi];
        $leg=hdist($cLat,$cLng,(float)$n['lat'],(float)$n['lng']);
        $totalKm+=$leg;

        // ETA calculation
        $travelMin = $leg > 0 ? round(($leg / $AVG_SPEED_KMH) * 60) : 0;
        $cumTimeMin += $travelMin + $AVG_VISIT_MIN;
        $etaHour  = floor($cumTimeMin / 60);
        $etaMin   = $cumTimeMin % 60;

        $ds=(int)($n['days_since']??999);
        $pl=!$n['last_visit']||$ds>=30?'high':($ds>=14?'med':'low');

        $route[]=array_merge($n,[
            'leg_km'       => round($leg,2),
            'cum_km'       => round($totalKm,2),
            'stop_num'     => count($route)+1,
            'p_level'      => $pl,
            'travel_min'   => $travelMin,
            'cum_time_min' => $cumTimeMin,
            'eta_label'    => $etaHour.'h '.str_pad($etaMin,2,'0',STR_PAD_LEFT).'m from now',
            'visit_history'=> $visitHistory[$n['id']] ?? [],
        ]);
        $cLat=(float)$n['lat'];$cLng=(float)$n['lng'];
        array_splice($remaining,$bi,1);
    }
}

$doneTodayCount = count(array_filter($growers,fn($g)=>$g['visited_today']||$g['geo_today']));
$totalAssigned  = count($growers);
$progressPct    = $totalAssigned > 0 ? round(($doneTodayCount / $totalAssigned) * 100) : 0;
$totalTimeMin   = !empty($route) ? end($route)['cum_time_min'] : 0;
$totalTimeLabel = floor($totalTimeMin/60).'h '.str_pad($totalTimeMin%60,2,'0',STR_PAD_LEFT).'m';
$officerName    = '';
foreach($officers as $o){ if($o['id']==$officerId) $officerName=$o['name']; }
?>
<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Route Planner</div>
    <a href="officer_coverage.php" class="back">← Coverage</a>
    <a href="visit_backlog.php"    class="back">📋 Backlog</a>
    <form method="GET" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <select name="officer_id" onchange="this.form.submit()">
        <option value="">— Officer —</option>
        <?php foreach($officers as $o): ?>
        <option value="<?=$o['id']?>" <?=$officerId==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
        <?php endforeach?>
      </select>
      <input type="date" name="date" value="<?=htmlspecialchars($date)?>" onchange="this.form.submit()">
      <select name="priority" onchange="this.form.submit()">
        <option value="mixed"   <?=$priority==='mixed'?'selected':''?>>Balanced</option>
        <option value="overdue" <?=$priority==='overdue'?'selected':''?>>Most Overdue</option>
        <option value="nearest" <?=$priority==='nearest'?'selected':''?>>Nearest First</option>
      </select>
      <select name="max" onchange="this.form.submit()">
        <option value="10"  <?=$maxStops==10?'selected':''?>>10 stops</option>
        <option value="20"  <?=$maxStops==20?'selected':''?>>20 stops</option>
        <option value="30"  <?=$maxStops==30?'selected':''?>>30 stops</option>
        <option value="50"  <?=$maxStops==50?'selected':''?>>All stops</option>
      </select>
    </form>
    <div style="margin-left:auto;display:flex;gap:6px;align-items:center">
      <?php if(!empty($route)): ?>
      <button class="btn sec" onclick="window.print()">🖨 Print</button>
      <?php endif?>
      <div style="font-size:10px;color:var(--muted)"><?=count($route)?> stops · <?=round($totalKm,1)?>km</div>
    </div>
  </header>

  <aside>
    <div class="sb-head">
      <h2>Route for <?=htmlspecialchars($officerName ?: 'Officer')?></h2>
      <p><?=$date?> · <?=$hasAssignments??false ? '📋 Assigned growers' : '📂 All growers'?></p>
    </div>

    <?php if(!empty($route)): ?>
    <!-- Progress bar -->
    <div class="progress-bar">
      <div class="pb-label">
        <span style="color:var(--green)"><?=$doneTodayCount?> visited today</span>
        <span><?=$progressPct?>% of <?=$totalAssigned?> growers</span>
      </div>
      <div class="pb-track"><div class="pb-fill" style="width:<?=$progressPct?>%"></div></div>
    </div>

    <!-- Summary -->
    <div class="route-summary">
      <div class="rs-cell">
        <div class="rs-val" style="color:var(--green)"><?=count($route)?></div>
        <div class="rs-label">Stops</div>
      </div>
      <div class="rs-cell">
        <div class="rs-val" style="color:var(--amber)"><?=round($totalKm,1)?>km</div>
        <div class="rs-label">Distance</div>
      </div>
      <div class="rs-cell">
        <div class="rs-val" style="color:var(--purple)"><?=$totalTimeLabel?></div>
        <div class="rs-label">Est. Time</div>
      </div>
      <div class="rs-cell">
        <div class="rs-val" style="color:var(--blue)"><?=$doneTodayCount?></div>
        <div class="rs-label">Done</div>
      </div>
    </div>
    <?php endif?>

    <div class="route-list">
      <?php if(!$officerId): ?>
        <div class="empty-state"><b>Select an officer</b>to generate their optimized route</div>
      <?php elseif(!$officerPos): ?>
        <div class="empty-state"><b>No GPS data</b>Officer has no location pings — cannot calculate route</div>
      <?php elseif(empty($route)): ?>
        <div class="empty-state"><b>✅ All done!</b>No unvisited growers for <?=$date?></div>
      <?php else: ?>

        <!-- Officer start -->
        <div class="stop-item" style="cursor:default">
          <div class="stop-num start-pin">📍</div>
          <div class="stop-body">
            <div class="stop-name">Officer Start</div>
            <div class="stop-meta"><span><?=substr($officerPos['created_at'],0,16)?></span></div>
          </div>
        </div>

        <?php foreach($route as $i=>$s):
          $dStr  = $s['last_visit'] ? $s['days_since'].'d ago' : 'Never visited';
          $pCls  = $s['p_level'];
          $pLbl  = $s['p_level']==='high' ? '🔴 Critical' : ($s['p_level']==='med' ? '🟡 Overdue' : '🟢 Recent');
          $hist  = $s['visit_history'];
          $isDone= false;
        ?>
        <div class="stop-item <?=$isDone?'done':''?>" id="stop-<?=$i?>" onclick="flyToStop(<?=$i?>)">
          <div class="stop-num <?=$pCls?>"><?=$s['stop_num']?></div>
          <div class="stop-body">
            <div class="stop-name">
              <?=htmlspecialchars($s['name'].' '.$s['surname'])?>
              <span style="color:var(--muted);font-size:9px"> #<?=$s['grower_num']?></span>
            </div>
            <div class="stop-meta">
              <span class="leg-dist">+<?=$s['leg_km']?>km</span>
              <span><?=$dStr?></span>
              <span class="<?=$pCls?>"><?=$pLbl?></span>
            </div>
            <div class="stop-meta">
              <span class="eta">⏱ <?=$s['eta_label']?></span>
              <?php if($s['travel_min']>0): ?>
              <span style="color:var(--muted)"><?=$s['travel_min']?>min drive</span>
              <?php endif?>
            </div>
            <?php if($s['geo_total']>0||$s['visit_count_officer']>0): ?>
            <div class="geo-count">
              <?php if($s['visit_count_officer']>0): ?>👁 <?=$s['visit_count_officer']?> visit<?=$s['visit_count_officer']>1?'s':''?> by you<?php endif?>
              <?php if($s['geo_total']>0): ?> · 📍 <?=$s['geo_total']?> pass-bys<?php endif?>
            </div>
            <?php endif?>
            <?php if(!empty($hist)): ?>
            <div class="stop-history">
              <?php foreach($hist as $h): ?>
              <span title="<?=htmlspecialchars($h['officer_name']??'')?>">
                <?=date('d M', strtotime($h['visit_date']))?>
              </span>
              <?php endforeach?>
            </div>
            <?php endif?>
          </div>
        </div>
        <?php endforeach?>
      <?php endif?>
    </div>
  </aside>

  <div class="map-wrap">
    <div id="map"></div>
    <div id="stop-panel">
      <div class="sp-name">
        <span id="sp-name">—</span>
        <span class="sp-close" onclick="document.getElementById('stop-panel').style.display='none'">✕</span>
      </div>
      <div class="sp-row"><span class="sp-label">Stop</span><span class="sp-val" id="sp-stop">—</span></div>
      <div class="sp-row"><span class="sp-label">Leg distance</span><span class="sp-val" id="sp-leg">—</span></div>
      <div class="sp-row"><span class="sp-label">Cumulative km</span><span class="sp-val" id="sp-cum">—</span></div>
      <div class="sp-row"><span class="sp-label">Last visited</span><span class="sp-val" id="sp-visit">—</span></div>
      <div class="sp-row"><span class="sp-label">Travel time</span><span class="sp-val" id="sp-travel">—</span></div>
      <div class="sp-row"><span class="sp-label">Est. arrival</span><span class="sp-val" id="sp-eta" style="color:var(--purple)">—</span></div>
      <div class="sp-row"><span class="sp-label">Priority</span><span class="sp-val" id="sp-priority">—</span></div>
      <div class="sp-row"><span class="sp-label">Your visits</span><span class="sp-val" id="sp-visits-you">—</span></div>
      <div class="sp-row"><span class="sp-label">Pass-bys</span><span class="sp-val" id="sp-passbys">—</span></div>
      <hr class="sp-divider">
      <div class="sp-section">Visit History</div>
      <div id="sp-history"></div>
    </div>
  </div>
</div>

<script>
const route      = <?=json_encode($route)?>;
const officerPos = <?=json_encode($officerPos)?>;
const allGrowers = <?=json_encode($growers)?>;

const map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',maxZoom:19}).addTo(map);

const stopMarkers = [];

// Officer start marker
if(officerPos){
  L.circleMarker([parseFloat(officerPos.latitude),parseFloat(officerPos.longitude)],{
    radius:10,color:'#3ddc68',fillColor:'#0d200d',fillOpacity:1,weight:2
  }).addTo(map).bindPopup('<b>👮 Start</b><br>'+officerPos.created_at);
}

// Show all growers as faint grey dots (context)
allGrowers.forEach(g => {
  const lat=parseFloat(g.lat), lng=parseFloat(g.lng);
  if(!lat||!lng) return;
  const isDone = g.visited_today||g.geo_today;
  if(isDone){
    L.circleMarker([lat,lng],{radius:5,color:'#3ddc68',fillColor:'#3ddc68',fillOpacity:.4,weight:1})
      .addTo(map).bindPopup(`<b>✅ ${g.name} ${g.surname}</b><br>Visited today`);
  } else {
    // Only show if NOT in route (would be a numbered marker)
    const inRoute = route.find(s=>s.id==g.id);
    if(!inRoute){
      L.circleMarker([lat,lng],{radius:4,color:'#1f2e1f',fillColor:'#1f2e1f',fillOpacity:.6,weight:1})
        .addTo(map).bindPopup(`<b>${g.name} ${g.surname}</b> #${g.grower_num}<br>${g.last_visit?g.days_since+'d since visit':'Never visited'}`);
    }
  }
});

// Route stops + polyline
if(route.length){
  const pts = officerPos ? [[parseFloat(officerPos.latitude),parseFloat(officerPos.longitude)]] : [];

  route.forEach((s,i) => {
    const lat=parseFloat(s.lat), lng=parseFloat(s.lng);
    pts.push([lat,lng]);
    const col = s.p_level==='high'?'#e84040':s.p_level==='med'?'#f5a623':'#3ddc68';
    const icon = L.divIcon({
      className:'',
      html:`<div style="width:26px;height:26px;border-radius:50%;background:#0d200d;border:2px solid ${col};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:${col}">${s.stop_num}</div>`,
      iconSize:[26,26],iconAnchor:[13,13]
    });
    const lastVisit = s.last_visit ? s.days_since+'d since visit' : 'Never visited';
    const mk = L.marker([lat,lng],{icon}).addTo(map)
      .bindPopup(`<b>${s.name} ${s.surname}</b> #${s.grower_num}<br>Stop ${s.stop_num} · +${s.leg_km}km<br>${lastVisit}<br>⏱ ${s.eta_label}`);
    mk.on('click',()=>showInfo(i));
    stopMarkers.push(mk);
  });

  L.polyline(pts,{color:'#3ddc68',weight:2,opacity:.5,dashArray:'6 3'}).addTo(map);
  map.fitBounds(L.latLngBounds(pts).pad(0.15));
} else {
  map.setView([-17.8292,31.0522],11);
}

function flyToStop(i){
  const s=route[i];
  map.setView([parseFloat(s.lat),parseFloat(s.lng)],16,{animate:true});
  stopMarkers[i]?.openPopup();
  showInfo(i);
  document.querySelectorAll('.stop-item').forEach(el=>el.classList.remove('selected'));
  document.getElementById('stop-'+i)?.classList.add('selected');
}

function showInfo(i){
  const s=route[i];
  document.getElementById('stop-panel').style.display='block';
  document.getElementById('sp-name').textContent    = s.name+' '+s.surname;
  document.getElementById('sp-stop').textContent    = s.stop_num+' of '+route.length;
  document.getElementById('sp-leg').textContent     = '+'+s.leg_km+'km';
  document.getElementById('sp-cum').textContent     = s.cum_km+'km total';
  document.getElementById('sp-visit').textContent   = s.last_visit ? s.days_since+'d ago' : 'Never';
  document.getElementById('sp-travel').textContent  = s.travel_min+'min drive';
  document.getElementById('sp-eta').textContent     = s.eta_label;
  document.getElementById('sp-priority').textContent= s.p_level==='high'?'🔴 Critical':s.p_level==='med'?'🟡 Overdue':'🟢 Recent';
  document.getElementById('sp-visits-you').textContent = s.visit_count_officer || '0';
  document.getElementById('sp-passbys').textContent    = s.geo_total || '0';

  const hist = s.visit_history || [];
  document.getElementById('sp-history').innerHTML = hist.length
    ? hist.map(h=>`<div class="visit-row"><span>${h.visit_date}</span><span style="color:var(--muted)">${h.officer_name||'—'}</span></div>`).join('')
    : '<div style="font-size:10px;color:var(--muted)">No visits recorded</div>';

  document.querySelectorAll('.stop-item').forEach(el=>el.classList.remove('selected'));
  document.getElementById('stop-'+i)?.classList.add('selected');
  document.getElementById('stop-'+i)?.scrollIntoView({behavior:'smooth',block:'nearest'});
}
</script>
</body>
</html>
