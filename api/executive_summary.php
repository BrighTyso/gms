<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;

$season = null;
$r = $conn->query("SELECT * FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$season=$row; $r->free();}
$seasonId   = $season ? (int)$season['id'] : 0;
$seasonName = $season ? $season['name']    : 'Unknown';

// ── All key metrics in as few queries as possible ─────────────────────────────

// Visits
$totalVisits=$visits7d=$visitsPrev7d=0;
$r=$conn->query("SELECT COUNT(*) AS all_time, SUM(created_at>=NOW()-INTERVAL 7 DAY) AS week, SUM(created_at BETWEEN NOW()-INTERVAL 14 DAY AND NOW()-INTERVAL 7 DAY) AS prev_week FROM visits");
if($r&&$row=$r->fetch_assoc()){$totalVisits=(int)$row['all_time'];$visits7d=(int)$row['week'];$visitsPrev7d=(int)$row['prev_week'];$r->free();}

// Growers
$totalAssigned=$visitedOnce=$neverVisited=0;
$r=$conn->query("SELECT COUNT(DISTINCT growerid) AS total FROM grower_field_officer WHERE seasonid=$seasonId");
if($r&&$row=$r->fetch_assoc()){$totalAssigned=(int)$row['total'];$r->free();}
$r=$conn->query("SELECT COUNT(DISTINCT gfo.growerid) AS cnt FROM grower_field_officer gfo WHERE gfo.seasonid=$seasonId AND EXISTS(SELECT 1 FROM visits v WHERE v.growerid=gfo.growerid)");
if($r&&$row=$r->fetch_assoc()){$visitedOnce=(int)$row['cnt'];$r->free();}
$neverVisited=$totalAssigned-$visitedOnce;

// Officers
$activeOfficers=$totalOfficers=$inactiveOfficers=0;
$r=$conn->query("SELECT COUNT(*) AS total FROM field_officers");
if($r&&$row=$r->fetch_assoc()){$totalOfficers=(int)$row['total'];$r->free();}
$r=$conn->query("SELECT COUNT(DISTINCT officer_id) AS cnt FROM device_locations WHERE created_at>=NOW()-INTERVAL 7 DAY");
if($r&&$row=$r->fetch_assoc()){$activeOfficers=(int)$row['cnt'];$r->free();}
$inactiveOfficers=$totalOfficers-$activeOfficers;

// Dead zones — lightweight: growers with no visit in last 14 days and GPS on record
$deadZones=0;
$r=$conn->query("
    SELECT COUNT(DISTINCT ll.growerid) AS cnt
    FROM lat_long ll
    WHERE ll.latitude IS NOT NULL AND ll.latitude != 0
      AND NOT EXISTS (
          SELECT 1 FROM visits v
          WHERE v.growerid = ll.growerid
            AND v.created_at >= NOW() - INTERVAL 14 DAY
      )
");
if($r&&$row=$r->fetch_assoc()){$deadZones=(int)$row['cnt'];$r->free();}

// Detect visits officer column
$vCol='userid';
$cc=$conn->query("SHOW COLUMNS FROM visits LIKE 'userid'");
if(!$cc||$cc->num_rows===0){
    foreach(['field_officer_id','officer_id','fo_id','user_id'] as $alt){
        $c=$conn->query("SHOW COLUMNS FROM visits LIKE '$alt'");
        if($c&&$c->num_rows>0){$vCol=$alt;break;}
    }
}

// Top 3 officers by visits this week
$topOfficers=[];
$r=$conn->query("SELECT fo.name, COUNT(*) AS cnt FROM visits v JOIN field_officers fo ON fo.userid=v.`$vCol` WHERE v.created_at>=NOW()-INTERVAL 7 DAY GROUP BY fo.id, fo.name ORDER BY cnt DESC LIMIT 3");
if($r){while($row=$r->fetch_assoc())$topOfficers[]=$row;$r->free();}

// Bottom 3 (zero visits this week but had GPS activity before)
$bottomOfficers=[];
$r=$conn->query("SELECT fo.name, COUNT(v.id) AS cnt FROM field_officers fo LEFT JOIN visits v ON v.`$vCol`=fo.userid AND v.created_at>=NOW()-INTERVAL 7 DAY GROUP BY fo.id, fo.name HAVING cnt=0 AND EXISTS(SELECT 1 FROM device_locations dl WHERE dl.officer_id=fo.id AND dl.created_at>=NOW()-INTERVAL 30 DAY) LIMIT 3");
if($r){while($row=$r->fetch_assoc())$bottomOfficers[]=$row;$r->free();}

// Geo gaps — officers near grower but no visit logged same day
$geoGaps=0;
$r=$conn->query("
    SELECT COUNT(DISTINCT growerid) AS cnt
    FROM grower_geofence_entry_point gep
    WHERE gep.created_at >= NOW() - INTERVAL 30 DAY
      AND NOT EXISTS (
          SELECT 1 FROM visits v
          WHERE v.growerid = gep.growerid
            AND DATE(v.created_at) = DATE(gep.created_at)
      )
");
if($r&&$row=$r->fetch_assoc()){$geoGaps=(int)$row['cnt'];$r->free();}

// Health score
$coveragePct = $totalAssigned > 0 ? round(($visitedOnce/$totalAssigned)*100) : 0;
$officerPct  = $totalOfficers > 0 ? round(($activeOfficers/$totalOfficers)*100) : 0;
$trendBonus  = $visits7d >= $visitsPrev7d ? 10 : 0;
$healthScore = min(100, round(($coveragePct*0.4)+($officerPct*0.3)+$trendBonus));
$hCol = $healthScore>=70?'#3ddc68':($healthScore>=40?'#f5a623':'#e84040');
$hLbl = $healthScore>=70?'HEALTHY':($healthScore>=40?'AT RISK':'CRITICAL');

$conn->close();

$visitTrend      = $visits7d - $visitsPrev7d;
$trendColor      = $visitTrend >= 0 ? 'var(--green)' : 'var(--red)';
$trendPrefix     = $visitTrend >= 0 ? '▲ +' : '';
$coverageColor   = $coveragePct >= 70 ? 'var(--green)' : ($coveragePct >= 40 ? 'var(--amber)' : 'var(--red)');
$officerValColor = $inactiveOfficers > 0 ? 'var(--amber)' : 'var(--green)';
$inactiveColor   = $inactiveOfficers > 0 ? 'var(--red)' : 'var(--muted)';
$deadZoneColor   = $deadZones > 0 ? 'var(--red)' : 'var(--green)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Executive Summary</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--text:#c8e6c9;--muted:#4a6b4a;--radius:6px}
  html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}
  header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;flex-wrap:wrap}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  .btn{font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--green);color:var(--green);background:var(--green-dim);cursor:pointer}
  .btn:hover{background:#1e4a22}
  .content{padding:24px;max-width:1100px;margin:0 auto}

  .report-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid var(--border)}
  .rh-left .title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800}
  .rh-left .sub{font-size:11px;color:var(--muted);margin-top:4px}
  .rh-right{text-align:right}
  .health-badge{display:inline-block;padding:6px 14px;border-radius:4px;font-family:'Syne',sans-serif;font-size:18px;font-weight:800;border:2px solid}

  .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
  .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;text-align:center}
  .kpi-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin:6px 0}
  .kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .kpi-trend{font-size:10px;margin-top:4px}

  .two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
  .panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px}
  .panel-title{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:12px;color:var(--muted)}
  .panel-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #0f1a0f;font-size:11px}
  .panel-row:last-child{border-bottom:none}

  .risk-item{padding:8px 12px;border-radius:4px;margin-bottom:6px;font-size:11px;border:1px solid}
  .risk-high{background:#200000;border-color:#400000;color:var(--red)}
  .risk-med{background:#1e1500;border-color:#3a2800;color:var(--amber)}
  .risk-low{background:#0d200d;border-color:var(--green-dim);color:var(--green)}

  .cov-bar{height:8px;background:var(--border);border-radius:4px;margin:8px 0;overflow:hidden}
  .cov-fill{height:100%;border-radius:4px}

  @media print{
    header,header *{display:none!important}
    body{background:#fff;color:#000}
    :root{--bg:#fff;--surface:#f9f9f9;--border:#ddd;--green:#1a7a3c;--amber:#b36e00;--red:#c00;--blue:#004080;--text:#000;--muted:#666}
    .content{padding:0}
  }
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Executive</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <button class="btn" onclick="window.print()" style="margin-left:auto">🖨 Print / PDF</button>
</header>

<div class="content">
  <div class="report-header">
    <div class="rh-left">
      <div class="title">GMS Executive Summary</div>
      <div class="sub">Season: <?=htmlspecialchars($seasonName)?> · Generated <?=date('d M Y H:i')?> CAT</div>
    </div>
    <div class="rh-right">
      <div class="health-badge" style="color:<?=$hCol?>;border-color:<?=$hCol?>;background:<?=$hCol?>18">
        <?=$hLbl?> <?=$healthScore?>/100
      </div>
      <div style="font-size:10px;color:var(--muted);margin-top:6px">Season Health Score</div>
    </div>
  </div>

  <!-- KPI row -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-label">Visits This Week</div>
      <div class="kpi-val" style="color:var(--green)"><?=$visits7d?></div>
      <div class="kpi-trend" style="color:<?=$trendColor?>"><?=$trendPrefix?><?=$visitTrend?> vs last week</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Grower Coverage</div>
      <div class="kpi-val" style="color:<?=$coverageColor?>"><?=$coveragePct?>%</div>
      <div class="kpi-trend" style="color:var(--muted)"><?=$visitedOnce?>/<?=$totalAssigned?> growers</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Active Officers</div>
      <div class="kpi-val" style="color:<?=$officerValColor?>"><?=$activeOfficers?>/<?=$totalOfficers?></div>
      <div class="kpi-trend" style="color:<?=$inactiveColor?>"><?=$inactiveOfficers?> inactive this week</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Dead Zones</div>
      <div class="kpi-val" style="color:<?=$deadZoneColor?>"><?=$deadZones?></div>
      <div class="kpi-trend" style="color:var(--muted)">no officer nearby 14d</div>
    </div>
  </div>

  <!-- Coverage bar -->
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-title">Season Coverage Progress</div>
    <div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:4px">
      <span style="color:var(--green)">Visited once: <?=$visitedOnce?></span>
      <span style="color:var(--amber)">Never visited: <?=$neverVisited?></span>
    </div>
    <div class="cov-bar">
      <div class="cov-fill" style="width:<?=$coveragePct?>%;background:var(--green)"></div>
    </div>
    <div style="font-size:10px;color:var(--muted);text-align:right"><?=$coveragePct?>% of <?=$totalAssigned?> assigned growers</div>
  </div>

  <!-- Two column: top performers + risks -->
  <div class="two-col">
    <div class="panel">
      <div class="panel-title">🏆 Top Officers — This Week</div>
      <?php if(empty($topOfficers)): ?>
      <div style="font-size:11px;color:var(--muted)">No visits logged this week</div>
      <?php else: foreach($topOfficers as $i=>$o): ?>
      <div class="panel-row">
        <span><?=$i===0?'🥇':($i===1?'🥈':'🥉')?> <?=htmlspecialchars($o['name'])?></span>
        <b style="color:var(--green)"><?=$o['cnt']?> visits</b>
      </div>
      <?php endforeach; endif?>
    </div>
    <div class="panel">
      <div class="panel-title">⚠️ Officers — Zero Visits This Week</div>
      <?php if(empty($bottomOfficers)): ?>
      <div style="font-size:11px;color:var(--green)">✅ All officers logged visits this week</div>
      <?php else: foreach($bottomOfficers as $o): ?>
      <div class="panel-row">
        <span style="color:var(--red)">❌ <?=htmlspecialchars($o['name'])?></span>
        <span style="font-size:10px;color:var(--muted)">0 visits</span>
      </div>
      <?php endforeach; endif?>
    </div>
  </div>

  <!-- Top 3 risks -->
  <div class="panel" style="margin-bottom:16px">
    <div class="panel-title">🚨 Top Risks This Week</div>
    <?php if($neverVisited > 0): ?>
    <div class="risk-item risk-<?=$neverVisited>20?'high':($neverVisited>10?'med':'low')?>">
      <?=$neverVisited?> assigned growers have never been visited this season
    </div>
    <?php endif?>
    <?php if($inactiveOfficers > 0): ?>
    <div class="risk-item risk-<?=$inactiveOfficers>2?'high':'med'?>">
      <?=$inactiveOfficers?> officer<?=$inactiveOfficers>1?'s':''?> had zero GPS activity in the last 7 days
    </div>
    <?php endif?>
    <?php if($geoGaps > 0): ?>
    <div class="risk-item risk-med">
      <?=$geoGaps?> growers had officers nearby in last 30 days but no visit was logged
    </div>
    <?php endif?>
    <?php if($deadZones > 0): ?>
    <div class="risk-item risk-<?=$deadZones>10?'high':'med'?>">
      <?=$deadZones?> growers have had no officer within 500m in the last 14 days
    </div>
    <?php endif?>
    <?php if($visitTrend < 0): ?>
    <div class="risk-item risk-med">
      Visit pace dropped by <?=abs($visitTrend)?> this week compared to last week
    </div>
    <?php endif?>
    <?php if($neverVisited==0 && $inactiveOfficers==0 && $geoGaps==0 && $deadZones==0 && $visitTrend>=0): ?>
    <div class="risk-item risk-low">✅ No significant risks identified this week</div>
    <?php endif?>
  </div>

  <div style="font-size:10px;color:var(--muted);text-align:center;padding-top:10px;border-top:1px solid var(--border)">
    Generated by GMS · <?=date('d M Y H:i')?> CAT · Season: <?=htmlspecialchars($seasonName)?>
  </div>
</div>
</body>
</html>
