<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

$days    = isset($_GET['days'])    ? min((int)$_GET['days'], 90)  : 30;
$officer = isset($_GET['officer']) ? (int)$_GET['officer']        : 0;

// Officers
$officers = [];
$r = $conn->query("SELECT id, name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $officers[]=$row; $r->free();}

// Season
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$seasonId=(int)$row['id']; $r->free();}

$officerWhere = $officer ? "AND ge.userid = (SELECT userid FROM field_officers WHERE id=$officer LIMIT 1)" : "";
// Only join grower_field_officer if assignments exist for current season
$assignJoin = ($seasonId) ? "LEFT JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId" : "";

// Growers where officer was near but no visit logged same day
$gapRows = [];
$r = $conn->query("
    SELECT
        g.id, g.grower_num, g.name, g.surname,
        fo.name                                         AS officer_name,
        fo.id                                           AS officer_id,
        COUNT(DISTINCT DATE(ge.created_at))             AS geo_days,
        MAX(ge.created_at)                              AS last_geo,
        v.last_visit,
        DATEDIFF(NOW(), v.last_visit)                   AS days_since_visit,
        -- Days where officer was near but no visit logged
        SUM(CASE WHEN NOT EXISTS(
            SELECT 1 FROM visits vv
            WHERE vv.growerid=ge.growerid
              AND vv.userid=ge.userid
              AND DATE(vv.created_at)=DATE(ge.created_at)
        ) THEN 1 ELSE 0 END)                            AS missed_days,
        -- Days where officer was near AND visit logged
        SUM(CASE WHEN EXISTS(
            SELECT 1 FROM visits vv
            WHERE vv.growerid=ge.growerid
              AND vv.userid=ge.userid
              AND DATE(vv.created_at)=DATE(ge.created_at)
        ) THEN 1 ELSE 0 END)                            AS logged_days
    FROM grower_geofence_entry_point ge
    JOIN growers g ON g.id = ge.growerid
    JOIN field_officers fo ON fo.userid = ge.userid
    $assignJoin
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=g.id
    WHERE ge.created_at >= NOW() - INTERVAL $days DAY
    $officerWhere
    GROUP BY g.id, g.grower_num, g.name, g.surname, fo.name, fo.id, v.last_visit
    HAVING missed_days > 0
    ORDER BY missed_days DESC, g.name
");
if($r){while($row=$r->fetch_assoc()) $gapRows[]=$row; $r->free();}

// Officer summary
$officerSummary = [];
$r = $conn->query("
    SELECT fo.id, fo.name,
           COUNT(DISTINCT ge.growerid)                  AS total_near,
           SUM(CASE WHEN NOT EXISTS(
               SELECT 1 FROM visits vv
               WHERE vv.growerid=ge.growerid AND vv.userid=ge.userid AND DATE(vv.created_at)=DATE(ge.created_at)
           ) THEN 1 ELSE 0 END)                         AS missed,
           SUM(CASE WHEN EXISTS(
               SELECT 1 FROM visits vv
               WHERE vv.growerid=ge.growerid AND vv.userid=ge.userid AND DATE(vv.created_at)=DATE(ge.created_at)
           ) THEN 1 ELSE 0 END)                         AS logged
    FROM grower_geofence_entry_point ge
    JOIN field_officers fo ON fo.userid=ge.userid
    WHERE ge.created_at >= NOW() - INTERVAL $days DAY
    GROUP BY fo.id, fo.name
    ORDER BY missed DESC
");
if($r){while($row=$r->fetch_assoc()) $officerSummary[]=$row; $r->free();}

$conn->close();

// ── CSV export — before any HTML ─────────────────────────────────────────────
if(isset($_GET['export'])){
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="geo_visit_gap_'.$days.'days.csv"');
    echo "Grower,Number,Officer,Near Days,Missed Days,Logged Days,Conversion %,Last Visit\n";
    foreach($gapRows as $g){
        $total = (int)$g['geo_days'];
        $conv  = $total > 0 ? round(($g['logged_days']/$total)*100) : 0;
        echo '"'.str_replace('"','""',$g['name'].' '.$g['surname']).'",'.
             '"'.$g['grower_num'].'",'.
             '"'.str_replace('"','""',$g['officer_name']).'",'.
             $g['geo_days'].','.$g['missed_days'].','.$g['logged_days'].','.$conv.'%,'.
             '"'.($g['last_visit']??'Never').'"'."\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Geo vs Visit Gap</title>
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
  .content{padding:20px;max-width:1200px;margin:0 auto}
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px}
  .card-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-top:4px}
  .card-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .bar-wrap{display:flex;align-items:center;gap:6px}
  .bar-track{height:6px;background:var(--border);border-radius:3px;width:80px}
  .bar-fill{height:100%;border-radius:3px}
  .badge{display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;border:1px solid}
  .b-high{background:#200000;color:var(--red);border-color:#400000}
  .b-med{background:#1e1500;color:var(--amber);border-color:#3a2800}
  .b-ok{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .empty{padding:20px;text-align:center;color:var(--muted);font-size:11px}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Geo vs Visit</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <select onchange="location.href='?days='+this.value+'&officer=<?=$officer?>'" style="margin-left:8px">
    <option value="14" <?=$days==14?'selected':''?>>Last 14 days</option>
    <option value="30" <?=$days==30?'selected':''?>>Last 30 days</option>
    <option value="60" <?=$days==60?'selected':''?>>Last 60 days</option>
    <option value="90" <?=$days==90?'selected':''?>>Last 90 days</option>
  </select>
  <select onchange="location.href='?days=<?=$days?>&officer='+this.value">
    <option value="0">All Officers</option>
    <?php foreach($officers as $o): ?>
    <option value="<?=$o['id']?>" <?=$officer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
    <?php endforeach?>
  </select>
  <a href="?days=<?=$days?>&officer=<?=$officer?>&export=1" class="back" style="margin-left:auto">⬇ CSV</a>
</header>

<div class="content">
  <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;margin-bottom:16px">
    📍 Geofence vs Visit Gap Report
    <div style="font-size:11px;font-weight:400;color:var(--muted);margin-top:4px">Officer was physically near grower but no visit was logged — last <?=$days?> days</div>
  </div>

  <?php
  $totalGaps    = count($gapRows);
  $totalMissed  = array_sum(array_column($gapRows,'missed_days'));
  $totalLogged  = array_sum(array_column($gapRows,'logged_days'));
  $totalNear    = $totalMissed + $totalLogged;
  $convPct      = $totalNear > 0 ? round(($totalLogged / $totalNear) * 100) : 0;
  ?>

  <div class="summary-grid">
    <div class="card"><div class="card-label">Growers with gaps</div><div class="card-val" style="color:var(--red)"><?=$totalGaps?></div></div>
    <div class="card"><div class="card-label">Missed opportunities</div><div class="card-val" style="color:var(--amber)"><?=$totalMissed?></div></div>
    <div class="card"><div class="card-label">Visits logged</div><div class="card-val" style="color:var(--green)"><?=$totalLogged?></div></div>
    <div class="card"><div class="card-label">Overall conversion</div><div class="card-val" style="color:<?=$convPct>=50?'var(--green)':($convPct>=25?'var(--amber)':'var(--red)')?>"><?=$convPct?>%</div></div>
  </div>

  <!-- Officer summary -->
  <div class="section">
    <div class="sh"><h3>👮 Officer Conversion Summary</h3></div>
    <table>
      <thead><tr><th>Officer</th><th>Near Days</th><th>Missed</th><th>Logged</th><th>Conversion</th></tr></thead>
      <tbody>
      <?php foreach($officerSummary as $o):
        $total = (int)$o['missed'] + (int)$o['logged'];
        $conv  = $total > 0 ? round(($o['logged']/$total)*100) : 0;
        $col   = $conv>=50?'var(--green)':($conv>=25?'var(--amber)':'var(--red)');
      ?>
      <tr>
        <td><b><?=htmlspecialchars($o['name'])?></b></td>
        <td style="color:var(--blue)"><?=$total?></td>
        <td style="color:var(--red)"><?=$o['missed']?></td>
        <td style="color:var(--green)"><?=$o['logged']?></td>
        <td>
          <div class="bar-wrap">
            <div class="bar-track"><div class="bar-fill" style="width:<?=$conv?>%;background:<?=$col?>"></div></div>
            <span style="color:<?=$col?>"><?=$conv?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>

  <!-- Grower detail -->
  <div class="section">
    <div class="sh">
      <h3>🌱 Growers with Missed Opportunities</h3>
      <span style="font-size:10px;color:var(--muted)"><?=$totalGaps?> growers</span>
    </div>
    <?php if(empty($gapRows)): ?>
    <div class="empty">✅ No gaps found — all geofence entries have corresponding visit logs</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Grower</th><th>Officer</th><th>Near Days</th><th>Missed</th><th>Logged</th><th>Conversion</th><th>Last Visit</th></tr></thead>
      <tbody>
      <?php foreach($gapRows as $g):
        $total = (int)$g['geo_days'];
        $conv  = $total > 0 ? round(($g['logged_days']/$total)*100) : 0;
        $col   = $conv>=50?'var(--green)':($conv>=25?'var(--amber)':'var(--red)');
        $cls   = $g['missed_days']>=5?'b-high':($g['missed_days']>=2?'b-med':'b-ok');
        $lastV = $g['last_visit'] ? $g['days_since_visit'].'d ago' : 'Never';
      ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--muted)"><?=htmlspecialchars($g['officer_name'])?></td>
        <td style="color:var(--blue)"><?=$g['geo_days']?></td>
        <td><span class="badge <?=$cls?>"><?=$g['missed_days']?>×</span></td>
        <td style="color:var(--green)"><?=$g['logged_days']?></td>
        <td>
          <div class="bar-wrap">
            <div class="bar-track"><div class="bar-fill" style="width:<?=$conv?>%;background:<?=$col?>"></div></div>
            <span style="color:<?=$col?>"><?=$conv?>%</span>
          </div>
        </td>
        <td style="color:var(--muted)"><?=$lastV?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    <?php endif?>
  </div>
</div>
</body>
</html>
