<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// ── Detect visits column name for officer linkage ──────────────────────────
$visitOfficerCol = 'userid'; // default
$colCheck = $conn->query("SHOW COLUMNS FROM visits LIKE 'userid'");
if (!$colCheck || $colCheck->num_rows === 0) {
    // Try common alternatives
    foreach (['field_officer_id','officer_id','fo_id','user_id'] as $alt) {
        $c = $conn->query("SHOW COLUMNS FROM visits LIKE '$alt'");
        if ($c && $c->num_rows > 0) { $visitOfficerCol = $alt; break; }
    }
}

// Season
$season = null;
$r = $conn->query("SELECT * FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$season=$row; $r->free();}
$seasonId   = $season ? (int)$season['id']   : 0;
$seasonName = $season ? $season['name']       : 'Unknown Season';

// Total assigned growers this season
$totalAssigned = 0;
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId");
if($r && $row=$r->fetch_assoc()){$totalAssigned=(int)$row['cnt']; $r->free();}

// Visited at least once this season
$visitedOnce = 0;
$r = $conn->query("
    SELECT COUNT(DISTINCT gfo.growerid) AS cnt
    FROM grower_field_officer gfo
    WHERE gfo.seasonid=$seasonId
      AND EXISTS(SELECT 1 FROM visits v WHERE v.growerid=gfo.growerid)
");
if($r && $row=$r->fetch_assoc()){$visitedOnce=(int)$row['cnt']; $r->free();}

// Visited 2+ times (on track)
$visitedOnTrack = 0;
$r = $conn->query("
    SELECT COUNT(DISTINCT gfo.growerid) AS cnt
    FROM grower_field_officer gfo
    WHERE gfo.seasonid=$seasonId
      AND (SELECT COUNT(*) FROM visits v WHERE v.growerid=gfo.growerid) >= 2
");
if($r && $row=$r->fetch_assoc()){$visitedOnTrack=(int)$row['cnt']; $r->free();}

// Never visited
$neverVisited = $totalAssigned - $visitedOnce;

// Active officers this season
$activeOfficers = 0;
$r = $conn->query("SELECT COUNT(DISTINCT officer_id) AS cnt FROM device_locations WHERE created_at >= NOW()-INTERVAL 7 DAY");
if($r && $row=$r->fetch_assoc()){$activeOfficers=(int)$row['cnt']; $r->free();}

// Total officers assigned
$totalOfficers = 0;
$r = $conn->query("SELECT COUNT(DISTINCT field_officerid) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId");
if($r && $row=$r->fetch_assoc()){$totalOfficers=(int)$row['cnt']; $r->free();}

// Total visits this season
$totalVisits = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM visits");
if($r && $row=$r->fetch_assoc()){$totalVisits=(int)$row['cnt']; $r->free();}

// Visits trend - last 7 days vs prev 7 days
$visits7d = 0; $visitsPrev7d = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM visits WHERE created_at >= NOW()-INTERVAL 7 DAY");
if($r && $row=$r->fetch_assoc()){$visits7d=(int)$row['cnt']; $r->free();}
$r = $conn->query("SELECT COUNT(*) AS cnt FROM visits WHERE created_at BETWEEN NOW()-INTERVAL 14 DAY AND NOW()-INTERVAL 7 DAY");
if($r && $row=$r->fetch_assoc()){$visitsPrev7d=(int)$row['cnt']; $r->free();}

// Weekly visits over last 8 weeks for chart
$weeklyVisits = [];
for($w=7;$w>=0;$w--){
    $start = date('Y-m-d', strtotime("-".($w+1)." weeks monday this week"));
    $end   = date('Y-m-d', strtotime("-$w weeks sunday this week"));
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM visits WHERE DATE(created_at) BETWEEN '$start' AND '$end'");
    if($r && $row=$r->fetch_assoc()){
        $weeklyVisits[] = ['week'=>date('d M', strtotime($start)), 'count'=>(int)$row['cnt']];
        $r->free();
    }
}

// Per-officer progress
$officerProgress = [];
$r = $conn->query("
    SELECT fo.id, fo.name,
           COUNT(DISTINCT gfo.growerid)                                         AS assigned,
           COUNT(DISTINCT CASE WHEN v.cnt > 0 THEN gfo.growerid END)           AS visited,
           COUNT(DISTINCT CASE WHEN v.cnt >= 2 THEN gfo.growerid END)          AS on_track,
           COALESCE(SUM(v.cnt), 0)                                             AS total_visits,
           MAX(dl.last_ping)                                                    AS last_active
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid=fo.userid AND gfo.seasonid=$seasonId
    LEFT JOIN (SELECT growerid, `$visitOfficerCol`, COUNT(*) AS cnt FROM visits GROUP BY growerid, `$visitOfficerCol`) v
          ON v.growerid=gfo.growerid AND v.`$visitOfficerCol`=fo.userid
    LEFT JOIN (SELECT officer_id, MAX(created_at) AS last_ping FROM device_locations GROUP BY officer_id) dl
          ON dl.officer_id=fo.id
    GROUP BY fo.id, fo.name
    ORDER BY visited DESC
");
if($r){while($row=$r->fetch_assoc()) $officerProgress[]=$row; $r->free();}
// If query failed, $officerProgress stays [] — page still renders

// Coverage forecast — at current pace, when will all growers be visited?
$visitsPerDay = 0;
$r = $conn->query("SELECT COUNT(*) / 30 AS daily FROM visits WHERE created_at >= NOW()-INTERVAL 30 DAY");
if($r && $row=$r->fetch_assoc()){$visitsPerDay=round((float)$row['daily'],1); $r->free();}
$remaining    = $neverVisited;
$daysToFinish = $visitsPerDay > 0 ? ceil($remaining / $visitsPerDay) : null;
$finishDate   = $daysToFinish ? date('d M Y', strtotime("+{$daysToFinish} days")) : 'Unknown';

// Season health score (0-100)
$healthScore = 0;
if($totalAssigned > 0){
    $coveragePct    = round(($visitedOnce / $totalAssigned) * 100);
    $onTrackPct     = round(($visitedOnTrack / $totalAssigned) * 100);
    $officerPct     = $totalOfficers > 0 ? round(($activeOfficers / $totalOfficers) * 100) : 0;
    $trendBonus     = $visits7d >= $visitsPrev7d ? 10 : 0;
    $healthScore    = min(100, round(($coveragePct * 0.4) + ($onTrackPct * 0.3) + ($officerPct * 0.2) + $trendBonus));
}

$conn->close();

$coveragePct = $totalAssigned > 0 ? round(($visitedOnce / $totalAssigned) * 100) : 0;
$onTrackPct  = $totalAssigned > 0 ? round(($visitedOnTrack / $totalAssigned) * 100) : 0;
$_wc=array_column($weeklyVisits,'count');$maxWeekly=count($_wc)>0?max(1,max($_wc)):1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Season Progress</title>
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
  .content{padding:24px;max-width:1300px;margin:0 auto}
  .page-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-bottom:6px}
  .page-sub{font-size:11px;color:var(--muted);margin-bottom:24px}

  /* Health score */
  .health-banner{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
  .health-score-ring{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column;border:3px solid;flex-shrink:0}
  .hs-num{font-family:'Syne',sans-serif;font-size:24px;font-weight:800}
  .hs-label{font-size:8px;color:var(--muted);text-transform:uppercase}
  .health-details{flex:1}
  .health-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;margin-bottom:6px}
  .health-sub{font-size:11px;color:var(--muted)}

  /* Stat cards */
  .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px}
  .card-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-top:4px}
  .card-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .card-sub{font-size:10px;color:var(--muted);margin-top:3px}

  /* Coverage bars */
  .cov-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px}
  .cov-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;margin-bottom:14px}
  .cov-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
  .cov-label{font-size:11px;width:140px;flex-shrink:0}
  .cov-bar{flex:1;height:10px;background:var(--border);border-radius:5px;overflow:hidden}
  .cov-fill{height:100%;border-radius:5px;transition:width .5s}
  .cov-pct{font-size:11px;font-weight:700;width:40px;text-align:right;flex-shrink:0}
  .cov-count{font-size:10px;color:var(--muted);width:80px;text-align:right;flex-shrink:0}

  /* Weekly chart */
  .chart-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:16px}
  .chart-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center}
  .bar-chart{display:flex;align-items:flex-end;gap:6px;height:100px}
  .chart-bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
  .chart-bar{width:100%;border-radius:3px 3px 0 0;background:var(--green);transition:height .4s;min-height:2px}
  .chart-bar-val{font-size:9px;color:var(--text);font-weight:700}
  .chart-label{font-size:8px;color:var(--muted);text-align:center;white-space:nowrap}

  /* Officer table */
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .mini-bar{display:flex;align-items:center;gap:6px}
  .mb-track{height:6px;background:var(--border);border-radius:3px;width:80px}
  .mb-fill{height:100%;border-radius:3px}

  /* Forecast */
  .forecast-box{background:#0d150d;border:1px solid var(--green-dim);border-radius:var(--radius);padding:14px;margin-bottom:20px;display:flex;gap:20px;flex-wrap:wrap;align-items:center}
  .fb-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
  .fb-val{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--green);margin-top:3px}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Season</div>
  <a href="reports_hub.php"      class="back">← Reports</a>
  <a href="officer_coverage.php" class="back">📊 Coverage</a>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">Season: <?=htmlspecialchars($seasonName)?> · <?=date('d M Y H:i')?></div>
</header>

<div class="content">
  <div class="page-title">📈 Season Progress Dashboard</div>
  <div class="page-sub">Season: <?=htmlspecialchars($seasonName)?> · Live data as of <?=date('d M Y H:i')?></div>

  <!-- Health score banner -->
  <?php
  $hCol = $healthScore >= 70 ? '#3ddc68' : ($healthScore >= 40 ? '#f5a623' : '#e84040');
  $hLbl = $healthScore >= 70 ? 'Healthy' : ($healthScore >= 40 ? 'At Risk' : 'Critical');
  ?>
  <div class="health-banner">
    <div class="health-score-ring" style="border-color:<?=$hCol?>">
      <div class="hs-num" style="color:<?=$hCol?>"><?=$healthScore?></div>
      <div class="hs-label">/ 100</div>
    </div>
    <div class="health-details">
      <div class="health-title" style="color:<?=$hCol?>">Season Health: <?=$hLbl?></div>
      <div class="health-sub">
        Coverage: <?=$coveragePct?>% · On-track: <?=$onTrackPct?>% · Active officers: <?=$activeOfficers?>/<?=$totalOfficers?> ·
        Visit trend: <?=$visits7d >= $visitsPrev7d ? '▲ Up' : '▼ Down'?> (<?=$visits7d?> vs <?=$visitsPrev7d?> prev week)
      </div>
    </div>
    <div style="text-align:right">
      <div style="font-size:10px;color:var(--muted)">Total visits this season</div>
      <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--green)"><?=$totalVisits?></div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="card">
      <div class="card-label">Total Assigned</div>
      <div class="card-val" style="color:var(--text)"><?=$totalAssigned?></div>
      <div class="card-sub">growers this season</div>
    </div>
    <div class="card">
      <div class="card-label">Visited Once</div>
      <div class="card-val" style="color:var(--green)"><?=$visitedOnce?></div>
      <div class="card-sub"><?=$coveragePct?>% of assigned</div>
    </div>
    <div class="card">
      <div class="card-label">On Track (2+ visits)</div>
      <div class="card-val" style="color:var(--blue)"><?=$visitedOnTrack?></div>
      <div class="card-sub"><?=$onTrackPct?>% of assigned</div>
    </div>
    <div class="card">
      <div class="card-label">Never Visited</div>
      <div class="card-val" style="color:var(--red)"><?=$neverVisited?></div>
      <div class="card-sub"><?=100-$coveragePct?>% of assigned</div>
    </div>
    <div class="card">
      <div class="card-label">Active Officers</div>
      <div class="card-val" style="color:var(--amber)"><?=$activeOfficers?></div>
      <div class="card-sub">pinged in last 7 days</div>
    </div>
    <?php $trend=$visits7d-$visitsPrev7d; $trendCol=$trend>=0?"var(--green)":"var(--red)"; ?>
    <div class="card">
      <div class="card-label">Visits This Week</div>
      <div class="card-val" style="color:var(--green)"><?=$visits7d?></div>
      <div class="card-sub" style="color:<?=$trendCol?>"><?=$trend>=0?'▲ +':''?><?=$trend?> vs last week</div>
    </div>
  </div>

  <!-- Forecast -->
  <div class="forecast-box">
    <div><div class="fb-label">Remaining Unvisited</div><div class="fb-val"><?=$neverVisited?> growers</div></div>
    <div><div class="fb-label">Current Visit Pace</div><div class="fb-val"><?=$visitsPerDay?>/day avg</div></div>
    <div><div class="fb-label">Est. Completion</div><div class="fb-val"><?=$daysToFinish ? $finishDate.' ('.$daysToFinish.' days)' : 'Insufficient data'?></div></div>
    <div style="flex:1;text-align:right;font-size:10px;color:var(--muted)">Based on last 30 days pace</div>
  </div>

  <!-- Coverage breakdown -->
  <div class="cov-section">
    <div class="cov-title">📊 Coverage Breakdown</div>
    <div class="cov-row">
      <div class="cov-label">Visited once+</div>
      <div class="cov-bar"><div class="cov-fill" style="width:<?=$coveragePct?>%;background:var(--green)"></div></div>
      <div class="cov-pct" style="color:var(--green)"><?=$coveragePct?>%</div>
      <div class="cov-count"><?=$visitedOnce?>/<?=$totalAssigned?></div>
    </div>
    <div class="cov-row">
      <div class="cov-label">On track (2+ visits)</div>
      <div class="cov-bar"><div class="cov-fill" style="width:<?=$onTrackPct?>%;background:var(--blue)"></div></div>
      <div class="cov-pct" style="color:var(--blue)"><?=$onTrackPct?>%</div>
      <div class="cov-count"><?=$visitedOnTrack?>/<?=$totalAssigned?></div>
    </div>
    <div class="cov-row">
      <div class="cov-label">Never visited</div>
      <?php $neverPct=100-$coveragePct; ?>
      <div class="cov-bar"><div class="cov-fill" style="width:<?=$neverPct?>%;background:var(--red)"></div></div>
      <div class="cov-pct" style="color:var(--red)"><?=$neverPct?>%</div>
      <div class="cov-count"><?=$neverVisited?>/<?=$totalAssigned?></div>
    </div>
    <div class="cov-row">
      <div class="cov-label">Active officers</div>
      <?php $oPct=$totalOfficers>0?round(($activeOfficers/$totalOfficers)*100):0; ?>
      <div class="cov-bar"><div class="cov-fill" style="width:<?=$oPct?>%;background:var(--amber)"></div></div>
      <div class="cov-pct" style="color:var(--amber)"><?=$oPct?>%</div>
      <div class="cov-count"><?=$activeOfficers?>/<?=$totalOfficers?></div>
    </div>
  </div>

  <!-- Weekly visits chart -->
  <div class="chart-section">
    <div class="chart-title">
      <span>📈 Weekly Visits (Last 8 Weeks)</span>
      <span style="font-size:10px;color:var(--muted)"><?=$visitsPerDay?>/day avg</span>
    </div>
    <div class="bar-chart">
      <?php foreach($weeklyVisits as $w):
        $pct = round(($w['count']/$maxWeekly)*100);
        $col = $w['count'] >= $visitsPrev7d ? 'var(--green)' : 'var(--amber)';
      ?>
      <div class="chart-bar-wrap">
        <div class="chart-bar-val"><?=$w['count']?></div>
        <div class="chart-bar" style="height:<?=max(4,$pct)?>px;background:<?=$col?>"></div>
        <div class="chart-label"><?=$w['week']?></div>
      </div>
      <?php endforeach?>
    </div>
  </div>

  <!-- Officer progress table -->
  <div class="section">
    <div class="sh"><h3>👮 Officer Progress This Season</h3></div>
    <table>
      <thead>
        <tr><th>Officer</th><th>Assigned</th><th>Visited</th><th>On Track</th><th>Coverage</th><th>Total Visits</th><th>Last Active</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php foreach($officerProgress as $o):
        $covPct    = $o['assigned'] > 0 ? round(($o['visited']/$o['assigned'])*100) : 0;
        $col       = $covPct >= 70 ? 'var(--green)' : ($covPct >= 40 ? 'var(--amber)' : 'var(--red)');
        $lastAct   = $o['last_active'] ? date('d M H:i', strtotime($o['last_active'])) : 'Never';
        $inactive  = !$o['last_active'] || strtotime($o['last_active']) < strtotime('-7 days');
        $lastColor = $inactive ? 'var(--red)' : 'var(--muted)';
      ?>
      <tr>
        <td><b><?=htmlspecialchars($o['name'])?></b></td>
        <td><?=$o['assigned']?></td>
        <td style="color:var(--green)"><?=$o['visited']?></td>
        <td style="color:var(--blue)"><?=$o['on_track']?></td>
        <td>
          <div class="mini-bar">
            <div class="mb-track"><div class="mb-fill" style="width:<?=$covPct?>%;background:<?=$col?>"></div></div>
            <span style="color:<?=$col?>"><?=$covPct?>%</span>
          </div>
        </td>
        <td><?=$o['total_visits']?></td>
        <td style="color:<?=$lastColor?>"><?=$lastAct?></td>
        <td><a href="officer_report.php?officer_id=<?=$o['id']?>&days=30" style="font-size:10px;color:var(--green);text-decoration:none;border:1px solid var(--green-dim);padding:2px 7px;border-radius:3px">Report →</a></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
