<?php ob_start();
require "conn.php";
require "validate.php";

$officerId = isset($_GET['officer_id']) ? (int)$_GET['officer_id'] : 0;
$days      = isset($_GET['days'])       ? min((int)$_GET['days'], 90) : 14;

function hdistReport($lat1,$lng1,$lat2,$lng2){
    $R=6371;$x=deg2rad($lat2-$lat1);$y=deg2rad($lng2-$lng1);
    $z=sin($x/2)*sin($x/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($y/2)*sin($y/2);
    return $R*2*atan2(sqrt($z),sqrt(1-$z));
}

// ── CSV export — must run before any HTML output ──────────────────────────────
if(isset($_GET['export']) && $officerId){
    // Resolve userid for visits table
    $exportUserid = $officerId;
    $r = $conn->query("SELECT userid FROM field_officers WHERE id=$officerId LIMIT 1");
    if($r&&$row=$r->fetch_assoc()){$exportUserid=(int)$row['userid'];$r->free();}

    $exportDaily = [];
    $r = $conn->query("
        SELECT DATE(created_at) AS day, COUNT(*) AS pings,
               MIN(TIME(created_at)) AS first_ping, MAX(TIME(created_at)) AS last_ping,
               ROUND(AVG(battery_level),0) AS avg_batt,
               TIMESTAMPDIFF(MINUTE,MIN(created_at),MAX(created_at)) AS active_min,
               SUM(CASE WHEN source='sms' THEN 1 ELSE 0 END) AS sms_pings,
               SUM(CASE WHEN source='realtime' THEN 1 ELSE 0 END) AS live_pings
        FROM device_locations
        WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL $days DAY
        GROUP BY DATE(created_at) ORDER BY day DESC
    ");
    if($r){while($row=$r->fetch_assoc()) $exportDaily[]=$row; $r->free();}

    $pings=[];
    $r=$conn->query("SELECT latitude,longitude,created_at FROM device_locations WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL $days DAY ORDER BY created_at ASC");
    if($r){while($row=$r->fetch_assoc()) $pings[]=$row; $r->free();}

    $growers=[];
    $r=$conn->query("SELECT g.id,g.grower_num,g.name,g.surname,ll.latitude AS lat,ll.longitude AS lng FROM growers g JOIN lat_long ll ON ll.growerid=g.id WHERE ll.latitude IS NOT NULL AND ll.latitude!=0");
    if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

    $visited=[];
    $r=$conn->query("SELECT DISTINCT growerid FROM visits WHERE userid=$exportUserid AND created_at>=NOW()-INTERVAL $days DAY");
    if($r){while($row=$r->fetch_assoc()) $visited[$row['growerid']]=true; $r->free();}

    $exportGrowers=[];
    foreach($growers as $g){
        $min=PHP_FLOAT_MAX; $ct=null;
        foreach($pings as $p){
            $d=hdistReport((float)$p['latitude'],(float)$p['longitude'],(float)$g['lat'],(float)$g['lng']);
            if($d<$min){$min=$d;$ct=$p['created_at'];}
        }
        if($min<=0.5) $exportGrowers[]=['name'=>$g['name'].' '.$g['surname'],'num'=>$g['grower_num'],'dist'=>round($min*1000),'time'=>$ct,'visited'=>isset($visited[$g['id']])];
    }
    $conn->close();
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="officer_'.$officerId.'_report_'.$days.'days.csv"');
    echo "Date,Pings,First Ping,Last Ping,Active Hours,SMS Pings,Live Pings,Avg Battery\n";
    foreach($exportDaily as $d){
        $hrs=$d['active_min']>0?round($d['active_min']/60,1):0;
        echo $d['day'].','.$d['pings'].','.$d['first_ping'].','.$d['last_ping'].','.$hrs.','.$d['sms_pings'].','.$d['live_pings'].','.($d['avg_batt']??'')."\n";
    }
    echo "\nGrower,Number,Distance (m),Closest Time,Visited\n";
    foreach($exportGrowers as $g){
        echo '"'.str_replace('"','""',$g['name']).'","'.$g['num'].'",'.$g['dist'].',"'.$g['time'].'","'.($g['visited']?'Yes':'No')."\"\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Officer Report</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b47eff;--text:#c8e6c9;--muted:#4a6b4a;--radius:6px}
  html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}
  header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  .btn{font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--border);color:var(--muted);text-decoration:none;cursor:pointer;background:transparent}
  .btn:hover{color:var(--green);border-color:var(--green)}
  .btn.primary{background:var(--green-dim);border-color:var(--green);color:var(--green)}

  .content{padding:24px;max-width:1200px;margin:0 auto}

  /* Summary cards */
  .card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px}
  .card-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
  .card-value{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-top:4px}
  .card-sub{font-size:10px;color:var(--muted);margin-top:2px}

  /* Section */
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px}
  .section-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center}

  /* Activity calendar */
  .cal-wrap{overflow-x:auto}
  .cal-grid{display:flex;gap:3px;padding-bottom:4px}
  .cal-col{display:flex;flex-direction:column;gap:3px}
  .cal-cell{width:14px;height:14px;border-radius:2px;cursor:pointer;position:relative}
  .cal-cell:hover::after{content:attr(data-tip);position:absolute;bottom:120%;left:50%;transform:translateX(-50%);background:#111;color:#fff;font-size:9px;padding:3px 6px;border-radius:3px;white-space:nowrap;z-index:10;pointer-events:none}
  .cal-day-label{font-size:8px;color:var(--muted);text-align:center;margin-bottom:1px}

  /* Table */
  .data-table{width:100%;border-collapse:collapse;font-size:11px}
  .data-table th{text-align:left;padding:7px 10px;font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface);position:sticky;top:56px}
  .data-table td{padding:8px 10px;border-bottom:1px solid #0f1a0f}
  .data-table tr:hover td{background:rgba(61,220,104,.03)}
  .data-table tr:last-child td{border-bottom:none}

  /* Badges */
  .badge{display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;border:1px solid}
  .b-visited{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .b-passed{background:#001020;color:var(--blue);border-color:#003050}
  .b-missed{background:#200000;color:var(--red);border-color:#400000}

  /* Mini bar */
  .mini-bar-wrap{display:flex;align-items:center;gap:6px}
  .mini-bar{height:6px;background:var(--border);border-radius:3px;flex:1;max-width:80px}
  .mini-fill{height:100%;border-radius:3px}

  /* No data */
  .no-data{padding:20px;text-align:center;color:var(--muted);font-size:11px}

  @media print{header{position:static}.btn{display:none}}
</style>
</head>
<body>
<?php
// ── All officers for selector ─────────────────────────────────────────────────
$allOfficers = [];
$r = $conn->query("SELECT id, name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}

if(!$officerId){
    echo '<header><div class="logo">GMS<span style="color:var(--muted)">/</span>Report</div><a href="reports_hub.php" class="back">← Reports</a></header>';
    echo '<div style="padding:40px 24px;max-width:500px;margin:0 auto;font-family:\'Space Mono\',monospace">';
    echo '<div style="font-family:\'Syne\',sans-serif;font-size:18px;font-weight:800;color:var(--green);margin-bottom:20px">Select a Field Officer</div>';
    echo '<div style="display:flex;flex-direction:column;gap:8px">';
    foreach($allOfficers as $o){
        $url = '?officer_id='.$o['id'].'&days=14';
        echo '<a href="'.htmlspecialchars($url).'" style="display:block;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:6px;color:var(--text);text-decoration:none;font-size:12px;transition:border-color .2s" onmouseover="this.style.borderColor=\'var(--green)\'" onmouseout="this.style.borderColor=\'var(--border)\'">';
        echo '👮 '.htmlspecialchars($o['name']);
        echo '</a>';
    }
    echo '</div></div>';
    exit;
}

// ── Officer info ──────────────────────────────────────────────────────────────
$officerName  = "Officer #$officerId";
$officerPhone = '';

// Try field_officers.id first (direct match)
$r = $conn->query("SELECT name, phone FROM field_officers WHERE id = $officerId LIMIT 1");
if($r){
    $row = $r->fetch_assoc();
    $r->free();
    if($row && !empty($row['name'])){
        $officerName  = $row['name'];
        $officerPhone = $row['phone'] ?? '';
    }
}

// If still showing Officer #N, try matching on userid column
if(strpos($officerName, 'Officer #') === 0){
    $r = $conn->query("SELECT name, phone FROM field_officers WHERE userid = $officerId LIMIT 1");
    if($r){
        $row = $r->fetch_assoc();
        $r->free();
        if($row && !empty($row['name'])){
            $officerName  = $row['name'];
            $officerPhone = $row['phone'] ?? '';
        }
    }
}

// ── Summary stats ─────────────────────────────────────────────────────────────
$summary = [];
$r = $conn->query("
    SELECT
        COUNT(*)                                                        AS total_pings,
        COUNT(DISTINCT DATE(created_at))                                AS active_days,
        ROUND(AVG(battery_level),0)                                     AS avg_battery,
        MAX(created_at)                                                 AS last_seen,
        MIN(created_at)                                                 AS first_seen,
        SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 18 THEN 1 ELSE 0 END) AS work_pings,
        SUM(CASE WHEN HOUR(created_at)<6 OR HOUR(created_at)>=19 THEN 1 ELSE 0 END) AS off_pings
    FROM device_locations
    WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL $days DAY
");
if($r && $row=$r->fetch_assoc()){ $summary=$row; $r->free(); }

// ── Daily breakdown ───────────────────────────────────────────────────────────
$dailyRows = [];
$r = $conn->query("
    SELECT
        DATE(created_at) AS day,
        COUNT(*)         AS pings,
        MIN(TIME(created_at)) AS first_ping,
        MAX(TIME(created_at)) AS last_ping,
        ROUND(AVG(battery_level),0) AS avg_batt,
        TIMESTAMPDIFF(MINUTE,MIN(created_at),MAX(created_at)) AS active_min,
        SUM(CASE WHEN source='sms' THEN 1 ELSE 0 END) AS sms_pings,
        SUM(CASE WHEN source='realtime' THEN 1 ELSE 0 END) AS live_pings
    FROM device_locations
    WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL $days DAY
    GROUP BY DATE(created_at)
    ORDER BY day DESC
");
if($r){while($row=$r->fetch_assoc()) $dailyRows[]=$row; $r->free();}

// ── Officer pings for proximity calc ─────────────────────────────────────────
$pings = [];
$r = $conn->query("SELECT latitude,longitude,created_at FROM device_locations WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL $days DAY ORDER BY created_at ASC");
if($r){while($row=$r->fetch_assoc()) $pings[]=$row; $r->free();}

// ── Calculate distance ────────────────────────────────────────────────────────
$totalDist = 0;
for($i=1;$i<count($pings);$i++){
    $totalDist += hdistReport((float)$pings[$i-1]['latitude'],(float)$pings[$i-1]['longitude'],(float)$pings[$i]['latitude'],(float)$pings[$i]['longitude']);
}

// ── Longest silence gap (working hours) ──────────────────────────────────────
$longestGap = 0;
$prevTime = null;
foreach($pings as $p){
    $h = (int)date('H', strtotime($p['created_at']));
    if($h>=6 && $h<=19){
        if($prevTime !== null){
            $diff = (strtotime($p['created_at']) - $prevTime) / 60;
            if($diff > $longestGap) $longestGap = round($diff);
        }
        $prevTime = strtotime($p['created_at']);
    }
}

// ── Growers passed near ───────────────────────────────────────────────────────
// Use assigned growers if available, fall back to all growers
$officerUserid = $officerId;
$r = $conn->query("SELECT userid FROM field_officers WHERE id=$officerId LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$officerUserid=(int)$row['userid'];$r->free();}

$seasonId=0;
$r=$conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$r->free();}

$hasAssignments=false;
if($seasonId){
    $r=$conn->query("SELECT COUNT(*) AS cnt FROM grower_field_officer WHERE field_officerid=$officerUserid AND seasonid=$seasonId");
    if($r&&$row=$r->fetch_assoc()){$hasAssignments=$row['cnt']>0;$r->free();}
}

$growers = [];
if($hasAssignments){
    $r = $conn->query("
        SELECT g.id,g.grower_num,g.name,g.surname,
               ll.latitude AS lat,ll.longitude AS lng
        FROM growers g
        JOIN grower_field_officer gfo ON gfo.growerid=g.id
                                      AND gfo.field_officerid=$officerUserid
                                      AND gfo.seasonid=$seasonId
        JOIN lat_long ll ON ll.growerid=g.id
        WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    ");
} else {
    $r = $conn->query("
        SELECT g.id,g.grower_num,g.name,g.surname,
               ll.latitude AS lat,ll.longitude AS lng
        FROM growers g
        JOIN lat_long ll ON ll.growerid=g.id
        WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    ");
}
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

// Get visits logged by this officer in period — use userid (not id) from field_officers
$visitsLogged = [];
$r = $conn->query("SELECT DISTINCT growerid FROM visits WHERE userid=$officerUserid AND created_at>=NOW()-INTERVAL $days DAY");
if($r){while($row=$r->fetch_assoc()) $visitsLogged[$row['growerid']]=true; $r->free();}

$growerProximity = [];
foreach($growers as $g){
    $minDist = PHP_FLOAT_MAX;
    $closestTime = null;
    foreach($pings as $p){
        $d = hdistReport((float)$p['latitude'],(float)$p['longitude'],(float)$g['lat'],(float)$g['lng']);
        if($d < $minDist){ $minDist=$d; $closestTime=$p['created_at']; }
    }
    if($minDist <= 0.5){
        $growerProximity[] = [
            'grower_id'    => $g['id'],
            'grower_num'   => $g['grower_num'],
            'name'         => $g['name'].' '.$g['surname'],
            'min_dist_m'   => round($minDist*1000),
            'closest_time' => $closestTime,
            'visited'      => isset($visitsLogged[$g['id']]),
        ];
    }
}

usort($growerProximity, fn($a,$b) => $a['min_dist_m'] - $b['min_dist_m']);

$passedCount  = count($growerProximity);
$visitedCount = count(array_filter($growerProximity, fn($g)=>$g['visited']));
$missedCount  = $passedCount - $visitedCount;
$convPct      = $passedCount > 0 ? round(($visitedCount/$passedCount)*100) : 0;

// ── Activity calendar (last 30 days) ─────────────────────────────────────────
$calData = [];
$r = $conn->query("SELECT DATE(created_at) AS day, COUNT(*) AS pings FROM device_locations WHERE officer_id=$officerId AND created_at>=NOW()-INTERVAL 30 DAY GROUP BY DATE(created_at)");
if($r){while($row=$r->fetch_assoc()) $calData[$row['day']]=(int)$row['pings']; $r->free();}

$conn->close();
?>

<header>
  <div class="logo">GMS<span>/</span>Report</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <select onchange="location.href='?officer_id='+this.value+'&days=<?=$days?>'">
    <option value="">— Select Officer —</option>
    <?php foreach($allOfficers as $o): ?>
    <option value="<?=$o['id']?>" <?=$officerId==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
    <?php endforeach?>
  </select>
  <select onchange="location.href='?officer_id=<?=$officerId?>&days='+this.value">
    <option value="7"  <?=$days==7?'selected':''?>>Last 7 days</option>
    <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
    <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
    <option value="90" <?=$days==90?'selected':''?>>Last 90 days</option>
  </select>
  <div style="margin-left:auto;display:flex;gap:8px">
    <a href="?officer_id=<?=$officerId?>&days=<?=$days?>&export=1" class="btn">⬇ CSV</a>
    <button class="btn" onclick="window.print()">🖨 Print</button>
  </div>
</header>

<div class="content">

  <!-- Officer header -->
  <div style="margin-bottom:20px">
    <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--green)"><?=htmlspecialchars($officerName)?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px">
      <?=isset($officerPhone)?htmlspecialchars($officerPhone):'—'?> &nbsp;·&nbsp;
      Report period: last <?=$days?> days &nbsp;·&nbsp;
      <?=date('d M Y', strtotime("-{$days} days"))?> – <?=date('d M Y')?>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="card-grid">
    <div class="card">
      <div class="card-label">Total Pings</div>
      <div class="card-value" style="color:var(--green)"><?=(int)($summary['total_pings']??0)?></div>
      <div class="card-sub"><?=(int)($summary['active_days']??0)?> active days</div>
    </div>
    <div class="card">
      <div class="card-label">Distance Covered</div>
      <div class="card-value" style="color:var(--amber)"><?=round($totalDist,1)?>km</div>
      <div class="card-sub"><?=round($totalDist/max(1,(int)($summary['active_days']??1)),1)?>km/day avg</div>
    </div>
    <div class="card">
      <div class="card-label">Work Hours Pings</div>
      <div class="card-value" style="color:var(--green)"><?=(int)($summary['work_pings']??0)?></div>
      <div class="card-sub"><?=(int)($summary['off_pings']??0)?> off-hours</div>
    </div>
    <div class="card">
      <div class="card-label">Longest Silence</div>
      <?php $gapColor = $longestGap>120 ? 'var(--red)' : ($longestGap>60 ? 'var(--amber)' : 'var(--green)'); ?>
      <?php $gapLabel = $longestGap>=60 ? floor($longestGap/60).'h '.($longestGap%60).'m' : $longestGap.'m'; ?>
      <div class="card-value" style="color:<?=$gapColor?>"><?=$gapLabel?></div>
      <div class="card-sub">during work hours</div>
    </div>
    <div class="card">
      <div class="card-label">Growers Passed</div>
      <div class="card-value" style="color:var(--blue)"><?=$passedCount?></div>
      <div class="card-sub">within 500m</div>
    </div>
    <div class="card">
      <div class="card-label">Visits Logged</div>
      <div class="card-value" style="color:var(--green)"><?=$visitedCount?></div>
      <div class="card-sub"><?=$missedCount?> missed · <?=$convPct?>% conversion</div>
    </div>
    <div class="card">
      <div class="card-label">Avg Battery</div>
      <?php $battColor = ($summary['avg_battery']??100) <= 20 ? 'var(--red)' : 'var(--text)'; ?>
      <div class="card-value" style="color:<?=$battColor?>"><?=(int)($summary['avg_battery']??0)?>%</div>
    </div>
  </div>

  <!-- Activity Calendar -->
  <div class="section">
    <div class="section-title">📅 Activity Calendar — Last 30 Days</div>
    <?php
    $maxCal = max(1, max($calData ?: [1]));
    // Build 5 rows x 6 cols (30 days)
    echo '<div class="cal-wrap"><div class="cal-grid">';
    for($w=0; $w<ceil(30/7); $w++){
        echo '<div class="cal-col">';
        for($d=0;$d<7;$d++){
            $dayOffset = ($w*7)+$d;
            if($dayOffset >= 30){ echo '<div class="cal-cell" style="background:transparent"></div>'; continue; }
            $date  = date('Y-m-d', strtotime('-'.(29-$dayOffset).' days'));
            $pings = $calData[$date] ?? 0;
            $alpha = $pings > 0 ? max(0.15, $pings/$maxCal) : 0;
            $bg    = $pings > 0 ? "rgba(61,220,104,$alpha)" : 'var(--border)';
            $dow   = date('D', strtotime($date));
            echo "<div class=\"cal-cell\" style=\"background:$bg\" data-tip=\"$date ($dow): $pings pings\"></div>";
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<div style="font-size:9px;color:var(--muted);margin-top:6px">Each column = 1 week · Darker = more pings</div>';
    echo '</div>';
    ?>
  </div>

  <!-- Daily breakdown -->
  <div class="section">
    <div class="section-title">
      📋 Daily Activity Breakdown
      <span style="font-size:10px;color:var(--muted)"><?=count($dailyRows)?> days with activity</span>
    </div>
    <?php if(empty($dailyRows)): ?>
    <div class="no-data">No activity recorded in this period</div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th><th>Pings</th><th>Start</th><th>End</th>
          <th>Active</th><th>🟢 Live</th><th>📱 SMS</th><th>🔋 Batt</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($dailyRows as $d):
        $hrs   = $d['active_min']>0 ? round($d['active_min']/60,1).'h' : '—';
        $dow   = date('D', strtotime($d['day']));
        $isWkd = in_array($dow,['Sat','Sun']);
      ?>
      <tr <?=$isWkd?'style="opacity:.6"':''?>>
        <td><?=$d['day']?> <span style="color:var(--muted);font-size:9px"><?=$dow?></span></td>
        <td><b><?=$d['pings']?></b></td>
        <td style="color:var(--muted)"><?=substr($d['first_ping'],0,5)?></td>
        <td style="color:var(--muted)"><?=substr($d['last_ping'],0,5)?></td>
        <td style="color:var(--amber)"><?=$hrs?></td>
        <td style="color:var(--green)"><?=(int)$d['live_pings']?></td>
        <td style="color:var(--blue)"><?=(int)$d['sms_pings']?></td>
        <td><?=$d['avg_batt']?$d['avg_batt'].'%':'—'?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    <?php endif?>
  </div>

  <!-- Growers passed near -->
  <div class="section">
    <div class="section-title">
      👨‍🌾 Growers Passed Near (within 500m)
      <span style="font-size:10px;color:var(--muted)"><?=$passedCount?> growers · <?=$convPct?>% visited</span>
      <span style="font-size:9px;padding:1px 6px;border-radius:3px;border:1px solid;<?=$hasAssignments?'color:var(--green);border-color:var(--green-dim);background:#0d200d':'color:var(--amber);border-color:#3a2800;background:#1e1500'?>">
        <?=$hasAssignments?'📋 Assigned':'📂 All growers'?>
      </span>
    </div>

    <?php $convBarColor = $convPct>=50 ? 'var(--green)' : ($convPct>=25 ? 'var(--amber)' : 'var(--red)'); ?>
    <!-- Conversion summary bar -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
      <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden">
        <div style="width:<?=$convPct?>%;height:100%;background:<?=$convBarColor?>"></div>
      </div>
      <span style="font-size:11px;color:var(--text);font-weight:700;white-space:nowrap"><?=$visitedCount?> visited / <?=$passedCount?> passed</span>
    </div>

    <?php if(empty($growerProximity)): ?>
    <div class="no-data">No growers found within 500m during this period</div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Grower</th><th>Closest Distance</th><th>Closest Time</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php foreach($growerProximity as $g): ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td>
          <div class="mini-bar-wrap">
            <div class="mini-bar"><div class="mini-fill" style="width:<?=min(100,round(($g['min_dist_m']/500)*100))?>%;background:var(--amber)"></div></div>
            <span style="font-size:10px;color:var(--amber)"><?=$g['min_dist_m']?>m</span>
          </div>
        </td>
        <td style="color:var(--muted);font-size:10px"><?=$g['closest_time']?substr($g['closest_time'],0,16):'—'?></td>
        <td>
          <?php if($g['visited']): ?>
            <span class="badge b-visited">✅ Visited</span>
          <?php else: ?>
            <span class="badge b-missed">❌ Not logged</span>
          <?php endif?>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    <?php endif?>
  </div>

</div>
</body>
</html>
