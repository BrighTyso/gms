<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// Per-officer device health
$devices = [];
$r = $conn->query("
    SELECT
        fo.id AS officer_id,
        fo.name AS officer_name,
        fo.phone,
        dl.device_id,
        dl.last_ping,
        dl.avg_battery,
        dl.pings_today,
        dl.pings_7d,
        dl.sms_pings,
        dl.internet_pings,
        TIMESTAMPDIFF(HOUR, dl.last_ping, NOW()) AS hours_since_ping,
        dl.last_battery,
        dl.last_source
    FROM field_officers fo
    LEFT JOIN (
        SELECT
            officer_id,
            device_id,
            MAX(created_at)                                                     AS last_ping,
            ROUND(AVG(battery_level),0)                                         AS avg_battery,
            MAX(battery_level)                                                  AS last_battery,
            SUM(DATE(created_at)=CURDATE())                                     AS pings_today,
            COUNT(CASE WHEN created_at>=NOW()-INTERVAL 7 DAY THEN 1 END)       AS pings_7d,
            COUNT(CASE WHEN source='sms' AND created_at>=NOW()-INTERVAL 7 DAY THEN 1 END) AS sms_pings,
            COUNT(CASE WHEN source='realtime' AND created_at>=NOW()-INTERVAL 7 DAY THEN 1 END) AS internet_pings,
            (SELECT source FROM device_locations dl2 WHERE dl2.officer_id=dl.officer_id ORDER BY created_at DESC LIMIT 1) AS last_source
        FROM device_locations dl
        WHERE created_at >= NOW() - INTERVAL 7 DAY
        GROUP BY officer_id, device_id
    ) dl ON dl.officer_id = fo.id
    ORDER BY hours_since_ping ASC, fo.name
");
if($r){while($row=$r->fetch_assoc()) $devices[]=$row; $r->free();}

$conn->close();

// Categorise
$online  = array_filter($devices, fn($d) => ($d['hours_since_ping']??999) <= 2);
$recent  = array_filter($devices, fn($d) => ($d['hours_since_ping']??999) > 2  && ($d['hours_since_ping']??999) <= 24);
$offline = array_filter($devices, fn($d) => ($d['hours_since_ping']??999) > 24 || $d['last_ping'] === null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Device Sync Status</title>
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
  .content{padding:20px;max-width:1200px;margin:0 auto}
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;text-align:center}
  .card-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-top:4px}
  .card-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px}
  .batt-bar{display:flex;align-items:center;gap:5px}
  .batt-track{width:50px;height:6px;background:var(--border);border-radius:3px;overflow:hidden}
  .batt-fill{height:100%;border-radius:3px}
  .source-badge{font-size:9px;padding:1px 5px;border-radius:3px;border:1px solid}
  .s-internet{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .s-sms{background:#001020;color:var(--blue);border-color:#003050}
  .s-mixed{background:#1e1500;color:var(--amber);border-color:#3a2800}
  .empty{padding:20px;text-align:center;color:var(--muted);font-size:11px}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Devices</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">Live · <?=date('d M Y H:i')?> CAT</div>
  <button onclick="location.reload()" style="font-family:'Space Mono',monospace;font-size:11px;padding:4px 10px;border:1px solid var(--border);color:var(--muted);background:transparent;cursor:pointer;border-radius:4px">⟳ Refresh</button>
</header>

<div class="content">
  <div class="summary-grid">
    <div class="card"><div class="card-label">Online (≤2h)</div><div class="card-val" style="color:var(--green)"><?=count($online)?></div></div>
    <div class="card"><div class="card-label">Recent (≤24h)</div><div class="card-val" style="color:var(--amber)"><?=count($recent)?></div></div>
    <div class="card"><div class="card-label">Offline / No Data</div><div class="card-val" style="color:var(--red)"><?=count($offline)?></div></div>
    <div class="card"><div class="card-label">Total Officers</div><div class="card-val"><?=count($devices)?></div></div>
  </div>

  <?php
  $sections = [
    ['label'=>'🟢 Online — Last ping within 2 hours', 'items'=>$online,  'col'=>'var(--green)'],
    ['label'=>'🟡 Recent — Last ping within 24 hours', 'items'=>$recent,  'col'=>'var(--amber)'],
    ['label'=>'🔴 Offline / No Data',                 'items'=>$offline, 'col'=>'var(--red)'],
  ];
  foreach($sections as $sec):
    if(empty($sec['items'])) continue;
  ?>
  <div class="section">
    <div class="sh">
      <h3><?=$sec['label']?></h3>
      <span style="font-size:10px;color:var(--muted)"><?=count($sec['items'])?> officers</span>
    </div>
    <table>
      <thead>
        <tr><th>Officer</th><th>Last Ping</th><th>Hours Ago</th><th>Battery</th><th>Pings Today</th><th>7d Pings</th><th>Internet vs SMS</th><th>Last Source</th><th>Device</th></tr>
      </thead>
      <tbody>
      <?php foreach($sec['items'] as $d):
        $hrs      = $d['hours_since_ping'] ?? null;
        $hrsLabel = $hrs === null ? 'Never' : ($hrs < 1 ? '<1h ago' : $hrs.'h ago');
        $batt     = (int)($d['avg_battery'] ?? 0);
        $battCol  = $batt <= 20 ? 'var(--red)' : ($batt <= 40 ? 'var(--amber)' : 'var(--green)');
        $internet = (int)($d['internet_pings'] ?? 0);
        $sms      = (int)($d['sms_pings'] ?? 0);
        $total7d  = (int)($d['pings_7d'] ?? 0);
        $srcBadge = ($internet > 0 && $sms > 0) ? 'mixed' : ($sms > $internet ? 'sms' : 'internet');
        $srcLabel = $d['last_source'] ?? '—';
        $device   = $d['device_id'] ? '…'.substr($d['device_id'],-6) : '—';
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center">
            <div class="status-dot" style="background:<?=$sec['col']?>"></div>
            <b><?=htmlspecialchars($d['officer_name'])?></b>
          </div>
        </td>
        <td style="color:var(--muted)"><?=$d['last_ping'] ? date('d M H:i', strtotime($d['last_ping'])) : '—'?></td>
        <td style="color:<?=$sec['col']?>"><?=$hrsLabel?></td>
        <td>
          <?php if($batt > 0): ?>
          <div class="batt-bar">
            <div class="batt-track"><div class="batt-fill" style="width:<?=$batt?>%;background:<?=$battCol?>"></div></div>
            <span style="color:<?=$battCol?>"><?=$batt?>%</span>
          </div>
          <?php else: ?>—<?php endif?>
        </td>
        <td><?=$d['pings_today']??0?></td>
        <td><?=$total7d?></td>
        <td>
          <?php if($total7d > 0): ?>
          <span style="color:var(--green);font-size:10px"><?=$internet?> net</span> /
          <span style="color:var(--blue);font-size:10px"><?=$sms?> sms</span>
          <?php else: ?>—<?php endif?>
        </td>
        <td><span class="source-badge s-<?=$srcBadge?>"><?=ucfirst($srcLabel)?></span></td>
        <td style="color:var(--muted);font-size:10px"><?=$device?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endforeach?>
</div>
</body>
</html>
