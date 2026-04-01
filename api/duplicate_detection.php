<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// Duplicate growers by name
$dupNames = [];
$r = $conn->query("
    SELECT LOWER(TRIM(CONCAT(name,' ',surname))) AS full_name,
           COUNT(*) AS cnt,
           GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids,
           GROUP_CONCAT(grower_num ORDER BY id SEPARATOR ', ') AS nums
    FROM growers
    GROUP BY LOWER(TRIM(CONCAT(name,' ',surname)))
    HAVING cnt > 1
    ORDER BY cnt DESC, full_name
");
if($r){while($row=$r->fetch_assoc()) $dupNames[]=$row; $r->free();}

// Duplicate growers by grower number
$dupNums = [];
$r = $conn->query("
    SELECT grower_num, COUNT(*) AS cnt,
           GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids,
           GROUP_CONCAT(CONCAT(name,' ',surname) ORDER BY id SEPARATOR ' | ') AS names
    FROM growers
    WHERE grower_num IS NOT NULL AND grower_num != ''
    GROUP BY grower_num
    HAVING cnt > 1
    ORDER BY cnt DESC
");
if($r){while($row=$r->fetch_assoc()) $dupNums[]=$row; $r->free();}

// Detect visits officer column name
$vOfficerCol = 'userid';
$colCheck = $conn->query("SHOW COLUMNS FROM visits LIKE 'userid'");
if (!$colCheck || $colCheck->num_rows === 0) {
    foreach (['field_officer_id','officer_id','fo_id','user_id'] as $alt) {
        $c = $conn->query("SHOW COLUMNS FROM visits LIKE '$alt'");
        if ($c && $c->num_rows > 0) { $vOfficerCol = $alt; break; }
    }
}

// Double-logged visits (same officer, same grower, same day, multiple times)
$dupVisits = [];
$r = $conn->query("
    SELECT v.`$vOfficerCol` AS officer_uid, fo.name AS officer_name,
           v.growerid, g.name AS grower_name, g.surname AS grower_surname, g.grower_num,
           DATE(v.created_at) AS visit_date,
           COUNT(*) AS visit_count
    FROM visits v
    JOIN growers g ON g.id=v.growerid
    JOIN field_officers fo ON fo.userid=v.`$vOfficerCol`
    GROUP BY v.`$vOfficerCol`, v.growerid, DATE(v.created_at)
    HAVING visit_count > 1
    ORDER BY visit_date DESC, visit_count DESC
    LIMIT 100
");
if($r){while($row=$r->fetch_assoc()) $dupVisits[]=$row; $r->free();}

// Growers with identical GPS coordinates (possible duplicates)
$dupCoords = [];
$r = $conn->query("
    SELECT ll.latitude, ll.longitude,
           COUNT(*) AS cnt,
           GROUP_CONCAT(CONCAT(g.name,' ',g.surname,' #',g.grower_num) ORDER BY g.id SEPARATOR ' | ') AS grower_list
    FROM lat_long ll
    JOIN growers g ON g.id=ll.growerid
    WHERE ll.latitude IS NOT NULL AND ll.latitude!=0
    GROUP BY ll.latitude, ll.longitude
    HAVING cnt > 1
    ORDER BY cnt DESC
    LIMIT 50
");
if($r){while($row=$r->fetch_assoc()) $dupCoords[]=$row; $r->free();}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Duplicate Detection</title>
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
  .content{padding:20px;max-width:1100px;margin:0 auto}
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;text-align:center}
  .card-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-top:4px}
  .card-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(232,64,64,.03)}
  .badge{display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;border:1px solid}
  .b-warn{background:#1e1500;color:var(--amber);border-color:#3a2800}
  .b-high{background:#200000;color:var(--red);border-color:#400000}
  .empty{padding:20px;text-align:center;color:var(--green);font-size:11px}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Duplicates</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="data_quality.php" class="back">🔍 Data Quality</a>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)"><?=date('d M Y H:i')?> CAT</div>
</header>

<div class="content">
  <?php
$dupNamesCol  = count($dupNames)  > 0 ? 'var(--red)'   : 'var(--green)';
$dupNumsCol   = count($dupNums)   > 0 ? 'var(--red)'   : 'var(--green)';
$dupVisitsCol = count($dupVisits) > 0 ? 'var(--amber)'  : 'var(--green)';
$dupCoordsCol = count($dupCoords) > 0 ? 'var(--amber)'  : 'var(--green)';
?>
<div class="summary-grid">
    <div class="card"><div class="card-label">Duplicate Names</div><div class="card-val" style="color:<?=$dupNamesCol?>"><?=count($dupNames)?></div></div>
    <div class="card"><div class="card-label">Duplicate Numbers</div><div class="card-val" style="color:<?=$dupNumsCol?>"><?=count($dupNums)?></div></div>
    <div class="card"><div class="card-label">Double Visits</div><div class="card-val" style="color:<?=$dupVisitsCol?>"><?=count($dupVisits)?></div></div>
    <div class="card"><div class="card-label">Same GPS</div><div class="card-val" style="color:<?=$dupCoordsCol?>"><?=count($dupCoords)?></div></div>
  </div>

  <!-- Duplicate names -->
  <div class="section">
    <div class="sh"><h3>👥 Duplicate Grower Names</h3><span style="font-size:10px;color:var(--muted)"><?=count($dupNames)?> groups</span></div>
    <?php if(empty($dupNames)): ?><div class="empty">✅ No duplicate names found</div><?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Count</th><th>Grower IDs</th><th>Grower Numbers</th></tr></thead>
      <tbody>
      <?php foreach($dupNames as $d): ?>
      <tr>
        <td><b><?=htmlspecialchars(ucwords($d['full_name']))?></b></td>
        <td><span class="badge b-high"><?=$d['cnt']?>×</span></td>
        <td style="color:var(--muted);font-size:10px">IDs: <?=$d['ids']?></td>
        <td style="color:var(--muted);font-size:10px">#<?=$d['nums']?></td>
      </tr>
      <?php endforeach?></tbody></table><?php endif?>
  </div>

  <!-- Duplicate grower numbers -->
  <div class="section">
    <div class="sh"><h3>🔢 Duplicate Grower Numbers</h3><span style="font-size:10px;color:var(--muted)"><?=count($dupNums)?> groups</span></div>
    <?php if(empty($dupNums)): ?><div class="empty">✅ No duplicate grower numbers found</div><?php else: ?>
    <table>
      <thead><tr><th>Grower #</th><th>Count</th><th>Names</th><th>IDs</th></tr></thead>
      <tbody>
      <?php foreach($dupNums as $d): ?>
      <tr>
        <td><b>#<?=htmlspecialchars($d['grower_num'])?></b></td>
        <td><span class="badge b-high"><?=$d['cnt']?>×</span></td>
        <td><?=htmlspecialchars($d['names'])?></td>
        <td style="color:var(--muted);font-size:10px">IDs: <?=$d['ids']?></td>
      </tr>
      <?php endforeach?></tbody></table><?php endif?>
  </div>

  <!-- Double visits -->
  <div class="section">
    <div class="sh"><h3>📋 Double-Logged Visits (Same Officer + Grower + Day)</h3><span style="font-size:10px;color:var(--muted)"><?=count($dupVisits)?> cases</span></div>
    <?php if(empty($dupVisits)): ?><div class="empty">✅ No double-logged visits found</div><?php else: ?>
    <table>
      <thead><tr><th>Officer</th><th>Grower</th><th>Date</th><th>Times Logged</th></tr></thead>
      <tbody>
      <?php foreach($dupVisits as $v): ?>
      <tr>
        <td><?=htmlspecialchars($v['officer_name'])?></td>
        <td><b><?=htmlspecialchars($v['grower_name'].' '.$v['grower_surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$v['grower_num']?></span></td>
        <td style="color:var(--muted)"><?=$v['visit_date']?></td>
        <td><span class="badge b-warn"><?=$v['visit_count']?>× logged</span></td>
      </tr>
      <?php endforeach?></tbody></table><?php endif?>
  </div>

  <!-- Same GPS -->
  <div class="section">
    <div class="sh"><h3>📍 Growers with Identical GPS Coordinates</h3><span style="font-size:10px;color:var(--muted)"><?=count($dupCoords)?> locations</span></div>
    <?php if(empty($dupCoords)): ?><div class="empty">✅ No shared GPS coordinates found</div><?php else: ?>
    <table>
      <thead><tr><th>Coordinates</th><th>Count</th><th>Growers</th></tr></thead>
      <tbody>
      <?php foreach($dupCoords as $d): ?>
      <tr>
        <td style="color:var(--muted);font-size:10px"><?=round($d['latitude'],5)?>, <?=round($d['longitude'],5)?></td>
        <td><span class="badge b-warn"><?=$d['cnt']?> growers</span></td>
        <td style="font-size:10px"><?=htmlspecialchars($d['grower_list'])?></td>
      </tr>
      <?php endforeach?></tbody></table><?php endif?>
  </div>
</div>
</body>
</html>
