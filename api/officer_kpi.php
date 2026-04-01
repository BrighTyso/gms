<?php ob_start();
require "conn.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
require "validate.php";
$officerId = isset($_GET['officer_id']) ? (int)$_GET['officer_id'] : 0;
$month     = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$monthStart= $month.'-01';
$monthEnd  = date('Y-m-t', strtotime($monthStart));
$monthLabel= date('F Y', strtotime($monthStart));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Officer KPI Scorecard</title>
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
  select,input{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  .btn{font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--border);color:var(--muted);background:transparent;cursor:pointer;text-decoration:none}
  .btn:hover{color:var(--green);border-color:var(--green)}
  .content{padding:24px;max-width:1200px;margin:0 auto}

  /* Officer selector grid */
  .officer-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:24px}
  .officer-btn{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;cursor:pointer;transition:all .15s;text-decoration:none;display:block}
  .officer-btn:hover{border-color:var(--green);background:rgba(61,220,104,.04)}
  .officer-btn.active{border-color:var(--green);background:rgba(61,220,104,.1)}
  .ob-name{font-size:12px;font-weight:700;color:var(--text)}
  .ob-sub{font-size:10px;color:var(--muted);margin-top:3px}

  /* Scorecard */
  .scorecard{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px}
  .sc-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px}
  .sc-name{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--green)}
  .sc-period{font-size:11px;color:var(--muted);margin-top:4px}
  .sc-grade{font-family:'Syne',sans-serif;font-size:48px;font-weight:800;line-height:1}

  .kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px}
  .kpi-box{background:#0d150d;border:1px solid var(--border);border-radius:var(--radius);padding:12px;text-align:center}
  .kpi-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin:4px 0}
  .kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .kpi-target{font-size:9px;color:var(--muted);margin-top:2px}

  /* Metric rows */
  .metric-section{margin-bottom:16px}
  .ms-title{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:700;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)}
  .metric-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #0f1a0f;font-size:11px}
  .metric-row:last-child{border-bottom:none}
  .mr-label{color:var(--muted)}
  .mr-val{font-weight:700}
  .mr-bar{display:flex;align-items:center;gap:8px}
  .mini-bar{height:6px;width:80px;background:var(--border);border-radius:3px}
  .mini-fill{height:100%;border-radius:3px}

  /* Week grid */
  .week-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-top:8px}
  .wg-day{text-align:center;font-size:8px;color:var(--muted);padding-bottom:3px}
  .wg-cell{height:20px;border-radius:3px;cursor:pointer}
  .wg-cell:hover{opacity:.8}

  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:7px 12px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d}
  td{padding:7px 12px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}

  @media print{header{position:static}.btn,.back{display:none}}
</style>
</head>
<body>
<?php
// ── Officers list ──────────────────────────────────────────────────────────
$officers=[];
$r=$conn->query("SELECT id,name,userid FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc())$officers[]=$row;$r->free();}

// ── If officer selected, load KPI data ────────────────────────────────────
$kpi=null;
$dailyBreakdown=[];
$growerDetails=[];
if($officerId){
    $officerUserid=$officerId;
    foreach($officers as $o){ if($o['id']==$officerId){$officerUserid=(int)$o['userid'];break;} }

    // Core metrics for the month
    $r=$conn->query("
        SELECT
            COUNT(*) AS total_visits,
            COUNT(DISTINCT growerid) AS unique_growers,
            COUNT(DISTINCT DATE(created_at)) AS active_days
        FROM visits
        WHERE userid=$officerUserid
          AND created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
    ");
    if($r&&$row=$r->fetch_assoc()){$kpi=$row;$r->free();}

    // GPS metrics
    $r=$conn->query("
        SELECT COUNT(*) AS total_pings,
               COUNT(DISTINCT DATE(created_at)) AS ping_days,
               SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 18 THEN 1 ELSE 0 END) AS work_pings,
               SUM(CASE WHEN HOUR(created_at)<6 OR HOUR(created_at)>=19 THEN 1 ELSE 0 END) AS off_pings,
               MIN(created_at) AS first_ping, MAX(created_at) AS last_ping
        FROM device_locations
        WHERE officer_id=$officerId
          AND created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
    ");
    if($r&&$row=$r->fetch_assoc()){
        $kpi=array_merge($kpi??[],$row);
        $r->free();
    }

    // Assigned growers this season
    $seasonId=0;
    $r=$conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
    if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$r->free();}
    $assigned=0;
    if($seasonId){ $r=$conn->query("SELECT COUNT(*) AS c FROM grower_field_officer WHERE field_officerid=$officerUserid AND seasonid=$seasonId"); if($r&&$row=$r->fetch_assoc()){$assigned=$row['c'];$r->free();} }
    $kpi['assigned'] = $assigned;

    // Daily breakdown
    $r=$conn->query("
        SELECT DATE(v.created_at) AS day, COUNT(*) AS visits,
               dl.first_ping, dl.last_ping, dl.pings
        FROM visits v
        LEFT JOIN (
            SELECT DATE(created_at) AS day2,
                   MIN(TIME(created_at)) AS first_ping,
                   MAX(TIME(created_at)) AS last_ping,
                   COUNT(*) AS pings
            FROM device_locations WHERE officer_id=$officerId
              AND created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
            GROUP BY DATE(created_at)
        ) dl ON dl.day2=DATE(v.created_at)
        WHERE v.userid=$officerUserid
          AND v.created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
        GROUP BY DATE(v.created_at)
        ORDER BY day DESC
    ");
    if($r){while($row=$r->fetch_assoc())$dailyBreakdown[]=$row;$r->free();}

    // Growers visited with last visit date
    $r=$conn->query("
        SELECT g.id,g.grower_num,g.name,g.surname,
               COUNT(*) AS visit_count,
               MAX(v.created_at) AS last_visit
        FROM visits v
        JOIN growers g ON g.id=v.growerid
        WHERE v.userid=$officerUserid
          AND v.created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'
        GROUP BY v.growerid,g.id,g.grower_num,g.name,g.surname
        ORDER BY visit_count DESC
    ");
    if($r){while($row=$r->fetch_assoc())$growerDetails[]=$row;$r->free();}

    // Daily ping calendar for month
    $pingCal=[];
    $r=$conn->query("SELECT DATE(created_at) AS day, COUNT(*) AS pings FROM device_locations WHERE officer_id=$officerId AND created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59' GROUP BY DATE(created_at)");
    if($r){while($row=$r->fetch_assoc())$pingCal[$row['day']]=(int)$row['pings'];$r->free();}

    $conn->close();

    // Calculate grade
    $visits     = (int)($kpi['total_visits']??0);
    $activeDays = (int)($kpi['active_days']??0);
    $uniq       = (int)($kpi['unique_growers']??0);
    $covPct     = $assigned>0 ? round(($uniq/$assigned)*100) : 0;
    $workRatio  = ($kpi['total_pings']??0)>0 ? round(($kpi['work_pings']/$kpi['total_pings'])*100) : 0;
    $score = min(100, round(
        ($visits   / max(1, $assigned*2)) * 30 +  // visits target = 2x assigned
        ($covPct   / 100) * 30 +
        ($activeDays / 22) * 25 +                  // 22 working days/month
        ($workRatio / 100) * 15
    ));
    $grade = $score>=90?'A+':($score>=80?'A':($score>=70?'B+':($score>=60?'B':($score>=50?'C':'D'))));
    $gradeColor = $score>=70?'var(--green)':($score>=50?'var(--amber)':'var(--red)');
    $kpi['score']=$score; $kpi['grade']=$grade; $kpi['grade_color']=$gradeColor;
    $kpi['cov_pct']=$covPct; $kpi['work_ratio']=$workRatio;
} else {
    $conn->close();
}
$officerName=''; foreach($officers as $o){if($o['id']==$officerId) $officerName=$o['name'];}
?>
<header>
  <div class="logo">GMS<span>/</span>KPI Scorecard</div>
  <a href="reports_hub.php"      class="back">← Reports</a>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <input type="month" value="<?=$month?>" onchange="location.href='?officer_id=<?=$officerId?>&month='+this.value" style="margin-left:8px">
  <?php if($officerId): ?>
  <button class="btn" onclick="window.print()">🖨 Print</button>
  <?php endif?>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)"><?=$monthLabel?></div>
</header>
<div class="content">

  <?php if(!$officerId): ?>
  <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;margin-bottom:16px">Select an officer to view their KPI scorecard for <?=$monthLabel?></div>
  <div class="officer-grid">
    <?php foreach($officers as $o): ?>
    <a href="?officer_id=<?=$o['id']?>&month=<?=$month?>" class="officer-btn <?=$officerId==$o['id']?'active':''?>">
      <div class="ob-name"><?=htmlspecialchars($o['name'])?></div>
      <div class="ob-sub">View <?=$monthLabel?> scorecard</div>
    </a>
    <?php endforeach?>
  </div>

  <?php else: ?>
  <!-- Scorecard header -->
  <div class="scorecard">
    <div class="sc-header">
      <div>
        <div class="sc-name"><?=htmlspecialchars($officerName)?></div>
        <div class="sc-period">Monthly KPI Scorecard · <?=$monthLabel?></div>
        <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
          <?php foreach($officers as $o): ?>
          <a href="?officer_id=<?=$o['id']?>&month=<?=$month?>" style="font-size:10px;color:<?=$o['id']==$officerId?'var(--green)':'var(--muted)'?>;text-decoration:none;border:1px solid <?=$o['id']==$officerId?'var(--green)':'var(--border)'?>;padding:2px 7px;border-radius:3px"><?=htmlspecialchars($o['name'])?></a>
          <?php endforeach?>
        </div>
      </div>
      <div style="text-align:right">
        <div class="sc-grade" style="color:<?=$kpi['grade_color']??'var(--muted)'?>"><?=$kpi['grade']??'—'?></div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Score: <?=$kpi['score']??0?>/100</div>
      </div>
    </div>

    <!-- KPI boxes -->
    <div class="kpi-row">
      <div class="kpi-box">
        <div class="kpi-label">Total Visits</div>
        <div class="kpi-val" style="color:var(--green)"><?=(int)($kpi['total_visits']??0)?></div>
        <div class="kpi-target">Target: <?=(int)($kpi['assigned']??0)*2?>+</div>
      </div>
      <div class="kpi-box">
        <div class="kpi-label">Growers Covered</div>
        <div class="kpi-val" style="color:var(--blue)"><?=(int)($kpi['unique_growers']??0)?>/<?=(int)($kpi['assigned']??0)?></div>
        <div class="kpi-target"><?=$kpi['cov_pct']??0?>% of assigned</div>
      </div>
      <div class="kpi-box">
        <div class="kpi-label">Active Days</div>
        <div class="kpi-val" style="color:var(--amber)"><?=(int)($kpi['active_days']??0)?></div>
        <div class="kpi-target">Target: 22 days</div>
      </div>
      <div class="kpi-box">
        <div class="kpi-label">GPS Pings</div>
        <div class="kpi-val" style="color:var(--purple)"><?=(int)($kpi['total_pings']??0)?></div>
        <div class="kpi-target"><?=$kpi['work_ratio']??0?>% in-hours</div>
      </div>
    </div>

    <!-- Metric breakdown -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="metric-section">
        <div class="ms-title">Coverage Metrics</div>
        <?php $max1=max(1,$kpi['assigned']??1,$kpi['unique_growers']??0,$kpi['total_visits']??0); ?>
        <div class="metric-row">
          <span class="mr-label">Assigned growers</span>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:100%;background:var(--border)"></div></div><span class="mr-val"><?=(int)($kpi['assigned']??0)?></span></div>
        </div>
        <div class="metric-row">
          <span class="mr-label">Unique visited</span>
          <?php $p1=min(100,round((($kpi['unique_growers']??0)/max(1,$kpi['assigned']??1))*100)); ?>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:<?=$p1?>%;background:var(--blue)"></div></div><span class="mr-val" style="color:var(--blue)"><?=(int)($kpi['unique_growers']??0)?></span></div>
        </div>
        <div class="metric-row">
          <span class="mr-label">Total visits logged</span>
          <?php $p2=min(100,round((($kpi['total_visits']??0)/max(1,($kpi['assigned']??1)*2))*100)); ?>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:<?=$p2?>%;background:var(--green)"></div></div><span class="mr-val" style="color:var(--green)"><?=(int)($kpi['total_visits']??0)?></span></div>
        </div>
      </div>
      <div class="metric-section">
        <div class="ms-title">Activity Metrics</div>
        <div class="metric-row">
          <span class="mr-label">Working days</span>
          <?php $p3=min(100,round((($kpi['active_days']??0)/22)*100)); ?>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:<?=$p3?>%;background:var(--amber)"></div></div><span class="mr-val" style="color:var(--amber)"><?=(int)($kpi['active_days']??0)?>/22</span></div>
        </div>
        <div class="metric-row">
          <span class="mr-label">In-hours pings</span>
          <?php $p4=$kpi['work_ratio']??0; ?>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:<?=$p4?>%;background:var(--green)"></div></div><span class="mr-val"><?=$p4?>%</span></div>
        </div>
        <div class="metric-row">
          <span class="mr-label">Off-hours pings</span>
          <?php $offPct=($kpi['total_pings']??0)>0?round((($kpi['off_pings']??0)/($kpi['total_pings']??1))*100):0; ?>
          <div class="mr-bar"><div class="mini-bar"><div class="mini-fill" style="width:<?=$offPct?>%;background:var(--red)"></div></div><span class="mr-val" style="color:var(--red)"><?=(int)($kpi['off_pings']??0)?></span></div>
        </div>
      </div>
    </div>

    <!-- Activity calendar -->
    <div class="metric-section" style="margin-top:16px">
      <div class="ms-title">Daily Activity — <?=$monthLabel?></div>
      <?php
      $maxPings=max(1,max($pingCal??[1]));
      $firstDay=date('N',strtotime($monthStart)); // 1=Mon
      $daysInMonth=(int)date('t',strtotime($monthStart));
      echo '<div style="display:flex;gap:2px;flex-wrap:wrap">';
      echo '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;width:100%">';
      // Day labels
      foreach(['M','T','W','T','F','S','S'] as $dl) echo "<div style='text-align:center;font-size:8px;color:var(--muted);padding-bottom:2px'>$dl</div>";
      // Blank cells for offset
      for($i=1;$i<$firstDay;$i++) echo "<div></div>";
      for($d=1;$d<=$daysInMonth;$d++){
          $day=date('Y-m',strtotime($monthStart)).'-'.str_pad($d,2,'0',STR_PAD_LEFT);
          $visits=count(array_filter($dailyBreakdown,fn($r)=>$r['day']===$day));
          $pings=$pingCal[$day]??0;
          $alpha=$pings>0?max(0.15,$pings/$maxPings):0;
          $bg=$visits>0?'var(--green)':($pings>0?"rgba(61,220,104,$alpha)":'var(--border)');
          echo "<div style='height:20px;border-radius:2px;background:$bg;cursor:default' title='$day: $visits visits, $pings pings'></div>";
      }
      echo '</div></div>';
      echo '<div style="font-size:9px;color:var(--muted);margin-top:5px"><span style="color:var(--green)">█</span> Visit logged &nbsp; <span style="color:rgba(61,220,104,.4)">█</span> GPS only &nbsp; <span style="color:var(--border)">█</span> No activity</div>';
      ?>
    </div>
  </div>

  <!-- Daily breakdown table -->
  <?php if(!empty($dailyBreakdown)): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid var(--border)"><b style="font-family:'Syne',sans-serif;font-size:13px">📋 Daily Breakdown</b></div>
    <table>
      <thead><tr><th>Date</th><th>Visits</th><th>GPS Start</th><th>GPS End</th><th>Pings</th></tr></thead>
      <tbody>
      <?php foreach($dailyBreakdown as $d): ?>
      <tr>
        <td><?=$d['day']?> <span style="color:var(--muted);font-size:9px"><?=date('D',strtotime($d['day']))?></span></td>
        <td style="color:var(--green);font-weight:700"><?=$d['visits']?></td>
        <td style="color:var(--muted)"><?=$d['first_ping']?substr($d['first_ping'],0,5):'—'?></td>
        <td style="color:var(--muted)"><?=$d['last_ping']?substr($d['last_ping'],0,5):'—'?></td>
        <td style="color:var(--muted)"><?=$d['pings']??'—'?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endif?>

  <!-- Growers visited -->
  <?php if(!empty($growerDetails)): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid var(--border)"><b style="font-family:'Syne',sans-serif;font-size:13px">👨‍🌾 Growers Visited This Month</b></div>
    <table>
      <thead><tr><th>Grower</th><th>Visits</th><th>Last Visit</th></tr></thead>
      <tbody>
      <?php foreach($growerDetails as $g): ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--green);font-weight:700"><?=$g['visit_count']?></td>
        <td style="color:var(--muted)"><?=$g['last_visit']?date('d M Y H:i',strtotime($g['last_visit'])):'—'?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endif?>
  <?php endif?>
</div>
</body>
</html>
