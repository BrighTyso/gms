<?php ob_start();
require "conn.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
require "validate.php";
$days = isset($_GET['days']) ? min((int)$_GET['days'], 30) : 14;
$expectedStart = '06:00'; // Expected start time
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Punctuality Report</title>
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
  select{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
  .content{padding:20px;max-width:1300px;margin:0 auto}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f;vertical-align:middle}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .badge{display:inline-block;padding:2px 7px;border-radius:3px;font-size:9px;font-weight:700}
  .b-ok{background:#0d200d;color:var(--green);border:1px solid var(--green-dim)}
  .b-late{background:#1e1500;color:var(--amber);border:1px solid #3a2800}
  .b-very-late{background:#200000;color:var(--red);border:1px solid #400000}
  .b-absent{background:#0d0d0d;color:var(--muted);border:1px solid var(--border)}
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px}
  .sum-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px}
  .sum-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-top:4px}
  .sum-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  /* Timeline dots */
  .timeline{display:flex;gap:3px;align-items:center}
  .tl-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0;cursor:default;position:relative}
  .tl-dot:hover::after{content:attr(data-tip);position:absolute;bottom:120%;left:50%;transform:translateX(-50%);background:#111;color:#fff;font-size:9px;padding:2px 6px;border-radius:3px;white-space:nowrap;z-index:10}
</style>
</head>
<body>
<?php
// ── Officers ───────────────────────────────────────────────────────────────
$officers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc())$officers[]=$row;$r->free();}

// ── Daily first ping per officer ───────────────────────────────────────────
$r=$conn->query("
    SELECT
        fo.id AS officer_id,
        fo.name AS officer_name,
        DATE(dl.created_at)         AS day,
        MIN(TIME(dl.created_at))    AS first_ping,
        MAX(TIME(dl.created_at))    AS last_ping,
        COUNT(*)                    AS total_pings,
        TIMESTAMPDIFF(MINUTE,
            CONCAT(DATE(dl.created_at),' 06:00:00'),
            CONCAT(DATE(dl.created_at),' ',MIN(TIME(dl.created_at)))
        )                           AS mins_late
    FROM device_locations dl
    JOIN field_officers fo ON fo.id=dl.officer_id
    WHERE dl.created_at >= NOW()-INTERVAL $days DAY
      AND HOUR(dl.created_at) >= 5  -- ignore midnight pings
    GROUP BY fo.id, fo.name, DATE(dl.created_at)
    ORDER BY fo.name, day DESC
");

$byOfficer=[];
if($r){
    while($row=$r->fetch_assoc()){
        $byOfficer[$row['officer_id']]['name']=$row['officer_name'];
        $byOfficer[$row['officer_id']]['days'][]=$row;
    }
    $r->free();
}
$conn->close();

// ── Build summary per officer ──────────────────────────────────────────────
$summaries=[];
foreach($byOfficer as $oid=>$data){
    $days2=$data['days'];
    $onTime=0;$late=0;$veryLate=0;$avgMinsLate=0;$totalMins=0;$earlyCount=0;
    foreach($days2 as $d){
        $ml=(int)$d['mins_late'];
        if($ml<=0) $onTime++;
        elseif($ml<=60) $late++;
        else $veryLate++;
        if($ml>0) $totalMins+=$ml;
        if($d['first_ping']<'06:00:00') $earlyCount++;
    }
    $totalDays=count($days2);
    $avgMinsLate=$totalDays>0?round($totalMins/$totalDays):0;
    $summaries[]=[
        'id'=>$oid,'name'=>$data['name'],
        'days'=>$days2,'total_days'=>$totalDays,
        'on_time'=>$onTime,'late'=>$late,'very_late'=>$veryLate,
        'avg_mins_late'=>$avgMinsLate,'early'=>$earlyCount,
        'punctuality_pct'=>$totalDays>0?round(($onTime/$totalDays)*100):0,
    ];
}
usort($summaries,fn($a,$b)=>$b['punctuality_pct']-$a['punctuality_pct']);

$totalOfficers=count($summaries);
$avgPunctuality=$totalOfficers>0?round(array_sum(array_column($summaries,'punctuality_pct'))/$totalOfficers):0;
$chronicallyLate=count(array_filter($summaries,fn($s)=>$s['punctuality_pct']<50));
$allDates=[];
for($i=$days-1;$i>=0;$i--) $allDates[]=date('Y-m-d',strtotime("-{$i} days"));
?>
<header>
  <div class="logo">GMS<span>/</span>Punctuality</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <a href="season_dashboard.php" class="back">📊 Season</a>
  <a href="officer_kpi.php"      class="back">🏆 KPI</a>
  <select onchange="location.href='?days='+this.value" style="margin-left:8px">
    <option value="7"  <?=$days==7?'selected':''?>>Last 7 days</option>
    <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
    <option value="21" <?=$days==21?'selected':''?>>Last 21 days</option>
    <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
  </select>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">Expected start: <?=$expectedStart?></div>
</header>
<div class="content">
  <?php $avgPunctualityCol=$avgPunctuality>=80?"var(--green)":($avgPunctuality>=60?"var(--amber)":"var(--red)"); ?>
  <div class="summary-grid">
    <div class="sum-card"><div class="sum-label">Officers Tracked</div><div class="sum-val"><?=$totalOfficers?></div></div>
    <div class="sum-card"><div class="sum-label">Avg Punctuality</div><div class="sum-val" style="color:<?=$avgPunctualityCol?>"><?=$avgPunctuality?>%</div></div>
    <div class="sum-card"><div class="sum-label">Chronically Late</div><div class="sum-val" style="color:var(--red)"><?=$chronicallyLate?></div><div style="font-size:10px;color:var(--muted)">below 50%</div></div>
  </div>

  <!-- Summary table -->
  <div class="section">
    <div class="sh"><h3>⏰ Officer Punctuality Summary</h3><span style="font-size:10px;color:var(--muted)">Expected start: <?=$expectedStart?> · Last <?=$days?> days</span></div>
    <table>
      <thead>
        <tr>
          <th>Officer</th>
          <th>Active Days</th>
          <th>On Time</th>
          <th>Late (≤60m)</th>
          <th>Very Late</th>
          <th>Avg Delay</th>
          <th>Punctuality</th>
          <th>Timeline</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($summaries as $s):
        $pct=$s['punctuality_pct'];
        $col=$pct>=80?'var(--green)':($pct>=60?'var(--amber)':'var(--red)');
        // Build day map
        $dayMap=[];
        foreach($s['days'] as $d) $dayMap[$d['day']]=$d;
      ?>
      <tr>
        <td><b><?=htmlspecialchars($s['name'])?></b></td>
        <td><?=$s['total_days']?></td>
        <td style="color:var(--green)"><?=$s['on_time']?></td>
        <td style="color:var(--amber)"><?=$s['late']?></td>
        <td style="color:var(--red)"><?=$s['very_late']?></td>
        <td style="color:var(--muted)"><?=$s['avg_mins_late']>0?'+'.$s['avg_mins_late'].'m':'—'?></td>
        <td>
          <div style="display:flex;align-items:center;gap:6px">
            <div style="width:60px;height:6px;background:var(--border);border-radius:3px">
              <div style="width:<?=$pct?>%;height:100%;border-radius:3px;background:<?=$col?>"></div>
            </div>
            <span style="color:<?=$col?>;font-weight:700"><?=$pct?>%</span>
          </div>
        </td>
        <td>
          <div class="timeline">
          <?php foreach($allDates as $date):
            if(isset($dayMap[$date])){
              $ml=(int)$dayMap[$date]['mins_late'];
              $fp=$dayMap[$date]['first_ping'];
              if($ml<=0) { $dotCol='#3ddc68'; $status='On time'; }
              elseif($ml<=60){ $dotCol='#f5a623'; $status='+'.$ml.'m late'; }
              else{ $dotCol='#e84040'; $status='+'.$ml.'m very late'; }
              echo "<div class='tl-dot' style='background:$dotCol' data-tip='$date $fp ($status)'></div>";
            } else {
              $dow=date('N',strtotime($date));
              $isWkd=$dow>=6;
              echo "<div class='tl-dot' style='background:".($isWkd?'#0d0d0d':'#1a1a1a')."' data-tip='$date ".($isWkd?'Weekend':'No GPS')."'></div>";
            }
          endforeach?>
          </div>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>

  <!-- Detail per officer -->
  <?php foreach($summaries as $s): ?>
  <div class="section">
    <div class="sh">
      <h3>👮 <?=htmlspecialchars($s['name'])?> — Daily Log</h3>
      <span class="badge <?=$s['punctuality_pct']>=80?'b-ok':($s['punctuality_pct']>=60?'b-late':'b-very-late')?>"><?=$s['punctuality_pct']?>% on time</span>
    </div>
    <table>
      <thead><tr><th>Date</th><th>First Ping</th><th>Last Ping</th><th>Pings</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($s['days'] as $d):
        $ml=(int)$d['mins_late'];
        $fpCol=$ml<=0?'var(--green)':($ml<=60?'var(--amber)':'var(--red)');
        if($ml<=0){ $cls='b-ok'; $lbl='✅ On time'; }
        elseif($ml<=60){ $cls='b-late'; $lbl='⚠️ +'.$ml.'m late'; }
        else{ $cls='b-very-late'; $lbl='❌ +'.$ml.'m late'; }
        $dow=date('D',strtotime($d['day']));
      ?>
      <tr>
        <td><?=$d['day']?> <span style="color:var(--muted);font-size:9px"><?=$dow?></span></td>
        <td style="color:<?=$fpCol?>;font-weight:700"><?=substr($d['first_ping'],0,5)?></td>
        <td style="color:var(--muted)"><?=substr($d['last_ping'],0,5)?></td>
        <td style="color:var(--muted)"><?=$d['total_pings']?></td>
        <td><span class="badge <?=$cls?>"><?=$lbl?></span></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endforeach?>
</div>
</body>
</html>
