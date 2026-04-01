<?php ob_start();
/**
 * GMS — Officer Coverage Dashboard
 */
require "conn.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
require "validate.php";

$days = isset($_GET['days']) ? min((int)$_GET['days'], 30) : 7;

// ── CSV export — must be before any HTML output ───────────────────────────────
if(isset($_GET['export']) && $_GET['export']==='csv'){
    // Re-run the officer summary query for export
    $exportOfficers = [];
    $r = $conn->query("
        SELECT dl.officer_id AS userid,
               COALESCE(fo.name, CONCAT('Officer #', dl.officer_id)) AS name,
               COUNT(dl.id) AS total_pings,
               COUNT(DISTINCT DATE(dl.created_at)) AS active_days,
               MAX(dl.created_at) AS last_seen,
               ROUND(AVG(dl.battery_level),0) AS avg_battery,
               SUM(CASE WHEN HOUR(dl.created_at) BETWEEN 6 AND 18 THEN 1 ELSE 0 END) AS work_pings,
               SUM(CASE WHEN HOUR(dl.created_at)<6 OR HOUR(dl.created_at)>=19 THEN 1 ELSE 0 END) AS offhour_pings
        FROM device_locations dl
        LEFT JOIN field_officers fo ON fo.id = dl.officer_id
        WHERE dl.created_at >= NOW() - INTERVAL $days DAY AND dl.officer_id IS NOT NULL
        GROUP BY dl.officer_id, fo.name ORDER BY total_pings DESC
    ");
    if($r){while($row=$r->fetch_assoc()) $exportOfficers[]=$row; $r->free();}
    $conn->close();
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="officer_coverage_'.$days.'days.csv"');
    echo "Officer,Total Pings,Active Days,Work Pings,Off-Hours Pings,Avg Battery,Last Seen\n";
    foreach($exportOfficers as $o){
        echo '"'.str_replace('"','""',$o['name']).'",'.
             $o['total_pings'].','.
             $o['active_days'].','.
             $o['work_pings'].','.
             $o['offhour_pings'].','.
             ($o['avg_battery']??'').','.$o['last_seen']."\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Officer Coverage</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;
    --green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;
    --red:#e84040;--blue:#4a9eff;--purple:#b47eff;
    --text:#c8e6c9;--muted:#4a6b4a;--radius:6px;
  }
  html,body{height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text)}
  .shell{display:grid;grid-template-rows:56px 1fr;grid-template-columns:340px 1fr;height:100vh}

  header{
    grid-column:1/-1;display:flex;align-items:center;gap:10px;
    padding:0 20px;background:var(--surface);border-bottom:1px solid var(--border);flex-wrap:wrap;
  }
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  .hdr-links{display:flex;gap:6px;margin-left:auto}
  .hdr-link{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:4px;white-space:nowrap}
  .hdr-link:hover{color:var(--green);border-color:var(--green)}

  aside{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
  .sb-head{padding:12px 16px 8px;border-bottom:1px solid var(--border)}
  .sb-head h2{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase}
  .sb-head p{font-size:10px;color:var(--muted);margin-top:2px}

  /* Tab bar inside sidebar */
  .sb-tabs{display:flex;border-bottom:1px solid var(--border)}
  .sb-tab{flex:1;font-family:'Space Mono',monospace;font-size:9px;padding:7px 4px;text-align:center;cursor:pointer;border:none;background:transparent;color:var(--muted);border-bottom:2px solid transparent;transition:all .2s}
  .sb-tab.active{color:var(--green);border-bottom-color:var(--green)}

  .sb-body{flex:1;overflow-y:auto}
  .sb-body::-webkit-scrollbar{width:3px}
  .sb-body::-webkit-scrollbar-thumb{background:var(--border)}

  /* Officer cards */
  .officer-card{padding:10px 14px;border-bottom:1px solid #0f1a0f;cursor:pointer;border-left:3px solid transparent;transition:background .15s}
  .officer-card:hover{background:rgba(61,220,104,.04)}
  .officer-card.selected{background:rgba(61,220,104,.08);border-left-color:var(--green)}
  .oc-name{font-size:12px;font-weight:700}
  .oc-stats{display:flex;gap:8px;margin-top:5px;flex-wrap:wrap}
  .oc-stat{font-size:10px;color:var(--muted)}
  .oc-stat b{color:var(--text)}
  .oc-badges{display:flex;gap:4px;margin-top:4px;flex-wrap:wrap}
  .oc-badge{font-size:9px;padding:1px 5px;border-radius:3px;border:1px solid}

  .coverage-bar{height:3px;background:var(--border);border-radius:2px;margin-top:6px}
  .coverage-fill{height:100%;border-radius:2px;background:var(--green);transition:width .4s}

  /* Comparison chart */
  .compare-chart{padding:12px 14px}
  .cc-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);margin-bottom:8px}
  .cc-row{display:flex;align-items:center;gap:8px;margin-bottom:6px}
  .cc-name{font-size:10px;width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0}
  .cc-bar-wrap{flex:1;height:14px;background:var(--border);border-radius:2px;position:relative;cursor:pointer}
  .cc-bar{height:100%;border-radius:2px;transition:width .4s}
  .cc-val{font-size:9px;color:var(--muted);width:40px;text-align:right;flex-shrink:0}

  /* Map */
  .map-wrap{position:relative}
  #map{width:100%;height:100%}

  /* Summary panel */
  #summary-panel{
    position:absolute;top:16px;right:16px;z-index:1000;
    width:280px;background:var(--surface);border:1px solid var(--border);
    border-radius:8px;padding:14px;box-shadow:0 4px 20px rgba(0,0,0,.6);
    display:none;max-height:85vh;overflow-y:auto;
  }
  #summary-panel::-webkit-scrollbar{width:3px}
  #summary-panel::-webkit-scrollbar-thumb{background:var(--border)}
  #summary-panel.visible{display:block}
  .sp-name{font-family:'Syne',sans-serif;font-size:14px;font-weight:800;color:var(--green);margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}
  .sp-close{font-size:12px;color:var(--muted);cursor:pointer;padding:2px 6px;border:1px solid var(--border);border-radius:3px}
  .sp-close:hover{color:var(--red);border-color:var(--red)}
  .sp-row{display:flex;justify-content:space-between;font-size:11px;margin-top:5px}
  .sp-label{color:var(--muted)}
  .sp-val{color:var(--text);font-weight:700}
  .sp-val.good{color:var(--green)}
  .sp-val.warn{color:var(--amber)}
  .sp-val.bad{color:var(--red)}
  .sp-divider{border:none;border-top:1px solid var(--border);margin:10px 0}
  .sp-section{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:8px 0 5px;font-weight:700}

  /* Daily rows */
  .day-row{display:flex;justify-content:space-between;align-items:center;font-size:10px;padding:4px 0;border-bottom:1px solid #0f1a0f}
  .day-label{color:var(--muted)}
  .day-pings{color:var(--green)}
  .day-times{color:var(--muted);font-size:9px}

  /* Activity calendar */
  .cal-grid{display:flex;gap:2px;flex-wrap:wrap;margin-top:6px}
  .cal-day{width:14px;height:14px;border-radius:2px;cursor:pointer;position:relative}
  .cal-day:hover::after{content:attr(data-tip);position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:#000;color:#fff;font-size:9px;padding:2px 5px;border-radius:3px;white-space:nowrap;z-index:10}

  /* Export btn */
  .btn-export{
    display:block;width:100%;margin-top:10px;
    font-family:'Space Mono',monospace;font-size:11px;cursor:pointer;
    background:var(--green-dim);border:1px solid var(--green);color:var(--green);
    padding:7px;border-radius:var(--radius);text-align:center;text-decoration:none;
  }
  .btn-export:hover{background:#1e4a22}
</style>
</head>
<body>
<?php
// ── Haversine in PHP ──────────────────────────────────────────────────────────
function phpHdist($lat1,$lng1,$lat2,$lng2){
    $R=6371; $dLat=deg2rad($lat2-$lat1); $dLng=deg2rad($lng2-$lng1);
    $a=sin($dLat/2)*sin($dLat/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R*2*atan2(sqrt($a),sqrt(1-$a));
}

// ── Heatmap points ────────────────────────────────────────────────────────────
$heatPoints = [];
$r = $conn->query("SELECT latitude,longitude,COUNT(*) AS w FROM device_locations WHERE created_at>=NOW()-INTERVAL $days DAY GROUP BY ROUND(latitude,4),ROUND(longitude,4)");
if($r){while($row=$r->fetch_assoc()) $heatPoints[]=[(float)$row['latitude'],(float)$row['longitude'],min((int)$row['w'],10)]; $r->free();}

// ── Per-officer summary + working hours + start/end ───────────────────────────
$officers = [];
$r = $conn->query("
    SELECT
        dl.officer_id                                           AS userid,
        fo.id                                                   AS fo_id,
        COALESCE(fo.name, CONCAT('Officer #', dl.officer_id))  AS name,
        fo.phone                                                AS phone,
        COUNT(dl.id)                                            AS total_pings,
        COUNT(DISTINCT DATE(dl.created_at))                     AS active_days,
        MAX(dl.created_at)                                      AS last_seen,
        ROUND(AVG(dl.battery_level),0)                          AS avg_battery,
        COUNT(DISTINCT dl.device_id)                            AS devices,
        SUM(CASE WHEN HOUR(dl.created_at) BETWEEN 6 AND 18 THEN 1 ELSE 0 END) AS work_pings,
        SUM(CASE WHEN HOUR(dl.created_at) < 6 OR HOUR(dl.created_at) >= 19 THEN 1 ELSE 0 END) AS offhour_pings
    FROM device_locations dl
    LEFT JOIN field_officers fo ON fo.id = dl.officer_id
    WHERE dl.created_at >= NOW() - INTERVAL $days DAY
      AND dl.officer_id IS NOT NULL
    GROUP BY dl.officer_id, fo.name, fo.phone
    ORDER BY total_pings DESC
");
if($r){ while($row=$r->fetch_assoc()) $officers[]=$row; $r->free(); }
else { error_log('officer_coverage query error: '.$conn->error); }

// ── Per-officer daily detail: pings, start, end, max gap ─────────────────────
$dailyDetail = [];
$r = $conn->query("
    SELECT
        officer_id,
        DATE(created_at)                                    AS day,
        COUNT(*)                                            AS pings,
        MIN(TIME(created_at))                               AS first_ping,
        MAX(TIME(created_at))                               AS last_ping,
        TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) AS active_minutes
    FROM device_locations
    WHERE created_at >= NOW() - INTERVAL $days DAY
    GROUP BY officer_id, DATE(created_at)
    ORDER BY officer_id, day DESC
");
if($r){while($row=$r->fetch_assoc()) $dailyDetail[$row['officer_id']][]=$row; $r->free();}

// ── Trails + distance calculation ────────────────────────────────────────────
$trails = [];
$distanceByOfficer = [];
$r = $conn->query("
    SELECT officer_id, latitude, longitude, created_at
    FROM device_locations
    WHERE created_at >= NOW() - INTERVAL $days DAY
      AND officer_id IS NOT NULL
    ORDER BY officer_id, created_at ASC
");
if($r){
    while($row=$r->fetch_assoc()){
        $oid = $row['officer_id'];
        $lat = (float)$row['latitude'];
        $lng = (float)$row['longitude'];
        if(!isset($trails[$oid])){
            $trails[$oid] = [];
            $distanceByOfficer[$oid] = 0;
        }
        if(!empty($trails[$oid])){
            $prev = end($trails[$oid]);
            $distanceByOfficer[$oid] += phpHdist($prev[0],$prev[1],$lat,$lng);
        }
        $trails[$oid][] = [$lat,$lng];
    }
    $r->free();
}

// ── Season + assignment check ─────────────────────────────────────────────────
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $r->free(); }

$hasAssignments = false;
if($seasonId){
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId");
    if($r && $row=$r->fetch_assoc()){ $hasAssignments = $row['cnt'] > 0; $r->free(); }
}

// ── Growers passed near (within 500m) per officer ────────────────────────────
// Use assigned growers if assignments exist, otherwise all growers
$growerLocations = [];
if($hasAssignments){
    // Per-officer assigned growers
    $r = $conn->query("
        SELECT g.id AS grower_id, g.grower_num, g.name, g.surname,
               ll.latitude AS lat, ll.longitude AS lng,
               gfo.field_officerid AS assigned_userid
        FROM grower_field_officer gfo
        JOIN growers g   ON g.id  = gfo.growerid
        JOIN lat_long ll ON ll.growerid = g.id
        WHERE gfo.seasonid = $seasonId
          AND ll.latitude IS NOT NULL AND ll.latitude != 0
    ");
} else {
    $r = $conn->query("
        SELECT g.id AS grower_id, g.grower_num, g.name, g.surname,
               ll.latitude AS lat, ll.longitude AS lng,
               NULL AS assigned_userid
        FROM growers g
        JOIN lat_long ll ON ll.growerid=g.id
        WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    ");
}
if($r){while($row=$r->fetch_assoc()) $growerLocations[]=$row; $r->free();}

// Build officer proximity counts — per officer, count unique growers within 500m of any ping
// If assignments exist, only count growers assigned to that officer
$growersPassed   = [];
$visitConversion = [];

if(!empty($growerLocations)){
    $r = $conn->query("SELECT officer_id,latitude,longitude FROM device_locations WHERE created_at>=NOW()-INTERVAL $days DAY AND officer_id IS NOT NULL");
    $pingsByOfficer = [];
    if($r){while($row=$r->fetch_assoc()) $pingsByOfficer[$row['officer_id']][] = [(float)$row['latitude'],(float)$row['longitude']]; $r->free();}

    $visitedGrowers = [];
    $r = $conn->query("SELECT DISTINCT growerid, userid FROM visits WHERE created_at>=NOW()-INTERVAL $days DAY");
    if($r){while($row=$r->fetch_assoc()) $visitedGrowers[$row['userid']][$row['growerid']]=true; $r->free();}

    // Map field_officers.id => userid for assignment matching
    $officerUserids = [];
    $r = $conn->query("SELECT id, userid FROM field_officers");
    if($r){while($row=$r->fetch_assoc()) $officerUserids[$row['id']]=$row['userid']; $r->free();}

    foreach($pingsByOfficer as $oid => $pings){
        $officerUserid = $officerUserids[$oid] ?? null;
        $passedIds = [];
        foreach($growerLocations as $g){
            // If assignments exist, only count growers assigned to this officer
            if($hasAssignments && $g['assigned_userid'] && $officerUserid && $g['assigned_userid'] != $officerUserid) continue;
            foreach($pings as $ping){
                $dist = phpHdist($ping[0],$ping[1],(float)$g['lat'],(float)$g['lng']);
                if($dist <= 0.5){ $passedIds[$g['grower_id']] = true; break; }
            }
        }
        $passed  = count($passedIds);
        $visited = 0;
        foreach(array_keys($passedIds) as $gid){
            if(isset($visitedGrowers[$oid][$gid])) $visited++;
        }
        $growersPassed[$oid]   = $passed;
        $visitConversion[$oid] = ['passed'=>$passed,'visited'=>$visited];
    }
}

// ── Longest silence gap during working hours ──────────────────────────────────
$longestGap = []; // [officer_id] => minutes
$r = $conn->query("
    SELECT officer_id, created_at
    FROM device_locations
    WHERE created_at >= NOW() - INTERVAL $days DAY
      AND HOUR(created_at) BETWEEN 6 AND 19
      AND officer_id IS NOT NULL
    ORDER BY officer_id, created_at ASC
");
if($r){
    $prev = []; $gaps = [];
    while($row=$r->fetch_assoc()){
        $oid = $row['officer_id'];
        if(isset($prev[$oid])){
            $diff = (strtotime($row['created_at']) - strtotime($prev[$oid])) / 60;
            if(!isset($gaps[$oid]) || $diff > $gaps[$oid]) $gaps[$oid] = round($diff);
        }
        $prev[$oid] = $row['created_at'];
    }
    $longestGap = $gaps;
    $r->free();
}

// ── Activity calendar (last 30 days) ─────────────────────────────────────────
$activityCal = [];
$r = $conn->query("
    SELECT officer_id, DATE(created_at) AS day, COUNT(*) AS pings
    FROM device_locations
    WHERE created_at >= NOW() - INTERVAL 30 DAY AND officer_id IS NOT NULL
    GROUP BY officer_id, DATE(created_at)
");
if($r){while($row=$r->fetch_assoc()) $activityCal[$row['officer_id']][$row['day']]=(int)$row['pings']; $r->free();}

$conn->close();

// Merge all per-officer stats
foreach($officers as &$o){
    $oid = $o['userid'];
    $o['distance_km']    = round($distanceByOfficer[$oid] ?? 0, 1);
    $o['longest_gap']    = $longestGap[$oid] ?? 0;
    $o['growers_passed'] = $growersPassed[$oid] ?? 0;
    $o['visits_logged']  = $visitConversion[$oid]['visited'] ?? 0;
    $o['conversion_pct'] = ($visitConversion[$oid]['passed'] ?? 0) > 0
        ? round(($visitConversion[$oid]['visited'] / $visitConversion[$oid]['passed']) * 100)
        : 0;
}
unset($o);

$maxPings = max(1, max(array_column($officers,'total_pings') ?: [1]));
$maxDist  = max(1, max(array_column($officers,'distance_km') ?: [1]));
?>

<div class="shell">
  <header>
    <div class="logo">GMS<span>/</span>Coverage</div>
    <a href="device_tracker.php" class="back">← Tracker</a>
    <select onchange="location.href='?days='+this.value" style="margin-left:8px">
      <option value="3"  <?=$days==3?'selected':''?>>Last 3 days</option>
      <option value="7"  <?=$days==7?'selected':''?>>Last 7 days</option>
      <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
      <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
    </select>
    <div class="hdr-links">
      <a href="reports_hub.php"        class="hdr-link">📋 Reports Hub</a>
      <a href="officer_league.php"     class="hdr-link">🏆 League</a>
      <a href="route_planner.php"      class="hdr-link">🗺 Route</a>
      <a href="visit_backlog.php"      class="hdr-link">📋 Backlog</a>
      <a href="dead_zones.php"         class="hdr-link">🚫 Dead Zones</a>
      <a href="?days=<?=$days?>&export=csv" class="hdr-link">⬇ CSV</a>
    </div>
    <div style="font-size:10px;color:var(--muted);margin-left:8px">
      <?=count($officers)?> officers ·
      <?php if($hasAssignments): ?>
      <span style="color:var(--green)">📋 Assigned growers</span>
      <?php else: ?>
      <span style="color:var(--amber)">📂 All growers</span>
      <?php endif?>
    </div>
  </header>

  <aside>
    <div class="sb-head">
      <h2>Field Officers</h2>
      <p>Click to view trail + stats</p>
    </div>
    <div class="sb-tabs">
      <button class="sb-tab active" onclick="sbTab('list',this)">Officers</button>
      <button class="sb-tab"        onclick="sbTab('compare',this)">Compare</button>
    </div>

    <!-- Officer list tab -->
    <div id="sb-list" class="sb-body">
    <?php foreach($officers as $i=>$o):
      $pct      = round(($o['total_pings']/$maxPings)*100);
      $gapColor = $o['longest_gap']>120?'var(--red)':($o['longest_gap']>60?'var(--amber)':'var(--green)');
      $convColor= $o['conversion_pct']>=50?'var(--green)':($o['conversion_pct']>=25?'var(--amber)':'var(--red)');
    ?>
    <div class="officer-card" data-index="<?=$i?>" onclick="selectOfficer(<?=$i?>)">
      <div class="oc-name"><?=htmlspecialchars($o['name'])?></div>
      <div class="oc-stats">
        <span class="oc-stat"><b><?=(int)$o['total_pings']?></b> pings</span>
        <span class="oc-stat"><b><?=(int)$o['active_days']?></b> days</span>
        <span class="oc-stat"><b><?=$o['distance_km']?></b>km</span>
        <?php if($o['avg_battery']): ?><span class="oc-stat">🔋<b><?=(int)$o['avg_battery']?>%</b></span><?php endif?>
      </div>
      <div class="oc-badges">
        <span class="oc-badge" style="color:var(--blue);border-color:#003050;background:#001020">👨‍🌾<?=$o['growers_passed']?> near</span>
        <span class="oc-badge" style="color:<?=$convColor?>;border-color:<?=$convColor?>20;background:<?=$convColor?>10"><?=$o['conversion_pct']?>% conv</span>
        <?php if($o['longest_gap']>0): ?><span class="oc-badge" style="color:<?=$gapColor?>;border-color:<?=$gapColor?>20">⏱<?=$o['longest_gap']?>m gap</span><?php endif?>
      </div>
      <div class="coverage-bar"><div class="coverage-fill" style="width:<?=$pct?>%"></div></div>
    </div>
    <?php endforeach?>
    </div>

    <!-- Compare tab -->
    <div id="sb-compare" class="sb-body" style="display:none">
      <div class="compare-chart">
        <div class="cc-label">Pings</div>
        <?php foreach($officers as $i=>$o): $pct=round(($o['total_pings']/$maxPings)*100); ?>
        <div class="cc-row" onclick="selectOfficer(<?=$i?>)" style="cursor:pointer">
          <div class="cc-name" title="<?=htmlspecialchars($o['name'])?>"><?=htmlspecialchars(substr($o['name'],0,12))?></div>
          <div class="cc-bar-wrap"><div class="cc-bar" style="width:<?=$pct?>%;background:var(--green)"></div></div>
          <div class="cc-val"><?=(int)$o['total_pings']?></div>
        </div>
        <?php endforeach?>

        <div class="cc-label" style="margin-top:16px">Distance (km)</div>
        <?php foreach($officers as $i=>$o): $pct=round(($o['distance_km']/$maxDist)*100); ?>
        <div class="cc-row" onclick="selectOfficer(<?=$i?>)" style="cursor:pointer">
          <div class="cc-name"><?=htmlspecialchars(substr($o['name'],0,12))?></div>
          <div class="cc-bar-wrap"><div class="cc-bar" style="width:<?=$pct?>%;background:var(--amber)"></div></div>
          <div class="cc-val"><?=$o['distance_km']?>km</div>
        </div>
        <?php endforeach?>

        <div class="cc-label" style="margin-top:16px">Visit Conversion %</div>
        <?php foreach($officers as $i=>$o):
          $pct = $o['conversion_pct'];
          $col = $pct>=50?'var(--green)':($pct>=25?'var(--amber)':'var(--red)');
        ?>
        <div class="cc-row" onclick="selectOfficer(<?=$i?>)" style="cursor:pointer">
          <div class="cc-name"><?=htmlspecialchars(substr($o['name'],0,12))?></div>
          <div class="cc-bar-wrap"><div class="cc-bar" style="width:<?=$pct?>%;background:<?=$col?>"></div></div>
          <div class="cc-val"><?=$pct?>%</div>
        </div>
        <?php endforeach?>
      </div>
    </div>
  </aside>

  <div class="map-wrap">
    <div id="map"></div>
    <div id="summary-panel">
      <div class="sp-name">
        <span id="sp-name">—</span>
        <span class="sp-close" onclick="document.getElementById('summary-panel').classList.remove('visible')">✕</span>
      </div>

      <!-- Core stats -->
      <div class="sp-row"><span class="sp-label">Total Pings</span><span class="sp-val" id="sp-pings">—</span></div>
      <div class="sp-row"><span class="sp-label">Active Days</span><span class="sp-val" id="sp-days">—</span></div>
      <div class="sp-row"><span class="sp-label">Distance Covered</span><span class="sp-val" id="sp-dist">—</span></div>
      <div class="sp-row"><span class="sp-label">Avg Battery</span><span class="sp-val" id="sp-batt">—</span></div>

      <hr class="sp-divider">

      <!-- Working hours -->
      <div class="sp-section">⏰ Working Hours</div>
      <div class="sp-row"><span class="sp-label">In-hours pings</span><span class="sp-val good" id="sp-workhrs">—</span></div>
      <div class="sp-row"><span class="sp-label">Off-hours pings</span><span class="sp-val warn" id="sp-offhrs">—</span></div>
      <div class="sp-row"><span class="sp-label">Longest silence</span><span class="sp-val" id="sp-gap">—</span></div>

      <hr class="sp-divider">

      <!-- Grower coverage -->
      <div class="sp-section">👨‍🌾 Grower Coverage</div>
      <div class="sp-row"><span class="sp-label">Growers passed near</span><span class="sp-val" id="sp-passed">—</span></div>
      <div class="sp-row"><span class="sp-label">Visits logged</span><span class="sp-val good" id="sp-visited">—</span></div>
      <div class="sp-row"><span class="sp-label">Visit conversion</span><span class="sp-val" id="sp-conv">—</span></div>

      <hr class="sp-divider">

      <!-- Activity calendar -->
      <div class="sp-section">📅 Activity Calendar (30 days)</div>
      <div class="cal-grid" id="sp-calendar"></div>

      <hr class="sp-divider">

      <!-- Daily breakdown -->
      <div class="sp-section">📋 Daily Breakdown</div>
      <div id="sp-daily"></div>

      <a class="btn-export" id="sp-export" href="#">⬇ Export This Officer CSV</a>
    </div>
  </div>
</div>

<script>
const officers   = <?=json_encode($officers)?>;
const heatPoints = <?=json_encode($heatPoints)?>;
const trails     = <?=json_encode($trails)?>;
const dailyData  = <?=json_encode($dailyDetail)?>;
const activityCal= <?=json_encode($activityCal)?>;
const days       = <?=$days?>;

const COLORS = ['#3ddc68','#4a9eff','#f5a623','#b47eff','#ff7043','#26c6da','#ec407a','#66bb6a'];
let map, heatLayer, trailLayers = {};

map = L.map('map');
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',maxZoom:19}).addTo(map);

if(heatPoints.length){
  heatLayer = L.heatLayer(heatPoints,{radius:25,blur:20,maxZoom:17,gradient:{0.2:'#0d200d',0.5:'#1a5e30',0.8:'#3ddc68',1.0:'#f5a623'}}).addTo(map);
  map.fitBounds(L.latLngBounds(heatPoints.map(p=>[p[0],p[1]])).pad(0.1));
} else {
  map.setView([-17.8292,31.0522],11);
}

Object.entries(trails).forEach(([oid,pts],i)=>{
  const color = COLORS[i%COLORS.length];
  const o = officers.find(o=>o.userid==oid);
  trailLayers[oid] = L.polyline(pts,{color,weight:2,opacity:.7}).bindPopup(o?.name||oid);
});

// ── Select officer ────────────────────────────────────────────────────────────
function selectOfficer(i){
  const o = officers[i];
  document.querySelectorAll('.officer-card').forEach(c=>c.classList.remove('selected'));
  document.querySelector(`.officer-card[data-index="${i}"]`)?.classList.add('selected');

  Object.values(trailLayers).forEach(l=>{if(map.hasLayer(l))map.removeLayer(l)});
  const trail = trailLayers[o.userid];
  if(trail){ trail.addTo(map); if(trail.getBounds().isValid()) map.fitBounds(trail.getBounds().pad(0.15)); }

  const panel = document.getElementById('summary-panel');
  panel.classList.add('visible');

  // Core
  document.getElementById('sp-name').textContent   = o.name;
  document.getElementById('sp-pings').textContent  = o.total_pings||0;
  document.getElementById('sp-days').textContent   = o.active_days||0;
  document.getElementById('sp-dist').textContent   = o.distance_km+'km';
  document.getElementById('sp-batt').textContent   = o.avg_battery ? o.avg_battery+'%' : '—';

  // Working hours
  const total = (o.work_pings||0)+(o.offhour_pings||0);
  const workPct = total>0 ? Math.round((o.work_pings/total)*100) : 0;
  document.getElementById('sp-workhrs').textContent = (o.work_pings||0)+' ('+workPct+'%)';
  document.getElementById('sp-offhrs').textContent  = (o.offhour_pings||0);

  const gap = o.longest_gap||0;
  const gapEl = document.getElementById('sp-gap');
  gapEl.textContent = gap>0 ? (gap>=60 ? Math.floor(gap/60)+'h '+(gap%60)+'m' : gap+'m') : '—';
  gapEl.className = 'sp-val'+(gap>120?' bad':gap>60?' warn':' good');

  // Grower coverage
  document.getElementById('sp-passed').textContent  = o.growers_passed||0;
  document.getElementById('sp-visited').textContent = o.visits_logged||0;
  const convEl = document.getElementById('sp-conv');
  convEl.textContent = o.conversion_pct+'%';
  convEl.className = 'sp-val'+(o.conversion_pct>=50?' good':o.conversion_pct>=25?' warn':' bad');

  // Activity calendar (last 30 days)
  const cal     = activityCal[o.userid] || {};
  const calEl   = document.getElementById('sp-calendar');
  const maxPings= Math.max(1,...Object.values(cal));
  let calHtml   = '';
  for(let d=29;d>=0;d--){
    const date = new Date(); date.setDate(date.getDate()-d);
    const key  = date.toISOString().substring(0,10);
    const pings= cal[key]||0;
    const alpha= pings>0 ? Math.max(0.2, pings/maxPings) : 0;
    const bg   = pings>0 ? `rgba(61,220,104,${alpha})` : 'var(--border)';
    calHtml += `<div class="cal-day" style="background:${bg}" data-tip="${key}: ${pings} pings"></div>`;
  }
  calEl.innerHTML = calHtml;

  // Daily breakdown
  const daily = dailyData[o.userid]||[];
  document.getElementById('sp-daily').innerHTML = daily.length
    ? daily.slice(0,7).map(d=>`
        <div class="day-row">
          <span class="day-label">${d.day}</span>
          <span class="day-pings">${d.pings} pings</span>
          <span class="day-times">${d.first_ping?.substring(0,5)||'—'}–${d.last_ping?.substring(0,5)||'—'}</span>
        </div>`).join('')
    : '<div style="font-size:10px;color:var(--muted)">No data</div>';

  // Export link
  document.getElementById('sp-export').href = `officer_report.php?officer_id=${o.fo_id||o.userid}&days=${days}`;
}

// ── Sidebar tab switch ────────────────────────────────────────────────────────
function sbTab(name, btn){
  document.querySelectorAll('.sb-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('sb-list').style.display    = name==='list'    ? 'block' : 'none';
  document.getElementById('sb-compare').style.display = name==='compare' ? 'block' : 'none';
}
</script>
</body>
</html>
