<?php ob_start();
require "conn.php";
require "validate.php";

$threshold = isset($_GET['days'])   ? (int)$_GET['days']   : 14;
$officer   = isset($_GET['officer'])? (int)$_GET['officer']: 0;
$search    = trim($_GET['search']??'');

// ── CSV export — must run before any HTML output ──────────────────────────────
if(isset($_GET['export'])){
    $exportRows=[];
    $r=$conn->query("
        SELECT g.name, g.surname, g.grower_num,
               DATEDIFF(NOW(),v.last_visit) AS days_since,
               v.last_visit,
               fo.name AS last_officer,
               geo.last_geo
        FROM growers g
        LEFT JOIN (SELECT growerid,MAX(created_at) AS last_visit,userid FROM visits GROUP BY growerid) v ON v.growerid=g.id
        LEFT JOIN (SELECT growerid,MAX(created_at) AS last_geo,userid FROM grower_geofence_entry_point GROUP BY growerid) geo ON geo.growerid=g.id
        LEFT JOIN field_officers fo ON fo.id=v.userid
        WHERE v.last_visit IS NULL OR DATEDIFF(NOW(),v.last_visit)>=$threshold
        ORDER BY days_since DESC
    ");
    if($r){while($row=$r->fetch_assoc()) $exportRows[]=$row; $r->free();}
    $conn->close();
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visit_backlog.csv"');
    echo "Grower,Number,Days Since Visit,Last Visit,Last Officer,Last Geo Contact\n";
    foreach($exportRows as $g){
        echo '"'.str_replace('"','""',$g['name'].' '.$g['surname']).'",'.
             '"'.$g['grower_num'].'",'.
             '"'.($g['days_since']??'Never').'",'.
             '"'.($g['last_visit']??'—').'",'.
             '"'.str_replace('"','""',$g['last_officer']??'—').'",'.
             '"'.($g['last_geo']??'—').'"'."\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Visit Backlog</title>
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
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px}
  .sum-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px}
  .sum-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-top:4px}
  .sum-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .sum-sub{font-size:10px;color:var(--muted);margin-top:2px}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .db{display:inline-block;padding:2px 7px;border-radius:3px;font-size:10px;font-weight:700}
  .d-crit{background:#200000;color:var(--red);border:1px solid #400000}
  .d-over{background:#1e1500;color:var(--amber);border:1px solid #3a2800}
  .d-ok{background:#0d200d;color:var(--green);border:1px solid var(--green-dim)}
  .btn-sm{font-family:'Space Mono',monospace;font-size:10px;padding:3px 8px;border-radius:3px;border:1px solid var(--green-dim);color:var(--green);background:transparent;cursor:pointer;text-decoration:none;white-space:nowrap}
  .btn-sm:hover{background:var(--green-dim)}
  .empty{padding:20px;text-align:center;color:var(--muted);font-size:11px}
  .mini-bar{display:flex;align-items:center;gap:6px}
  .mb-track{flex:1;height:4px;background:var(--border);border-radius:2px;max-width:80px}
  .mb-fill{height:100%;border-radius:2px}
  input[type=text]{background:var(--surface);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px}
</style>
</head>
<body>
<?php
$officers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc())$officers[]=$row;$r->free();}

// ── Current season ────────────────────────────────────────────────────────────
$currentSeasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $currentSeasonId=(int)$row['id']; $r->free(); }

$officerFilter = $officer ? "AND gfo.field_officerid = (SELECT userid FROM field_officers WHERE id=$officer LIMIT 1)" : '';
$searchWhere   = $search  ? "AND (g.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR g.surname LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR g.grower_num LIKE '%".mysqli_real_escape_string($conn,$search)."%')" : '';

// ── Officer backlog — assigned growers not visited ────────────────────────────
// Uses grower_field_officer to get proper assignment per officer
$officerBacklog=[];
$r=$conn->query("
    SELECT fo.id, fo.name, fo.userid,
           COUNT(DISTINCT gfo.growerid)                                          AS assigned_total,
           SUM(CASE WHEN v.last_visit IS NULL THEN 1 ELSE 0 END)                AS never_visited,
           SUM(CASE WHEN v.last_visit IS NULL
                     OR DATEDIFF(NOW(),v.last_visit) >= $threshold THEN 1 ELSE 0 END) AS overdue,
           SUM(CASE WHEN v.last_visit IS NOT NULL
                     AND DATEDIFF(NOW(),v.last_visit) < $threshold THEN 1 ELSE 0 END) AS visited_ok,
           (SELECT COUNT(*) FROM visits vv WHERE vv.userid=fo.userid AND vv.created_at>=NOW()-INTERVAL 7 DAY) AS visits_7d,
           (SELECT COUNT(*) FROM grower_geofence_entry_point ge WHERE ge.userid=fo.userid AND ge.created_at>=NOW()-INTERVAL 7 DAY) AS geo_7d
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid = fo.userid
                                  AND gfo.seasonid = $currentSeasonId
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=gfo.growerid
    GROUP BY fo.id, fo.name, fo.userid
    ORDER BY overdue DESC
");
if($r){while($row=$r->fetch_assoc())$officerBacklog[]=$row;$r->free();}

// ── Overdue assigned growers ──────────────────────────────────────────────────
$overdueGrowers=[];
$r=$conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           v.last_visit,
           DATEDIFF(NOW(), v.last_visit)      AS days_since,
           geo.last_geo,
           DATEDIFF(NOW(), geo.last_geo)      AS geo_days,
           fo.name                            AS assigned_officer,
           fo.id                              AS assigned_officer_id,
           fo.userid                          AS assigned_officer_userid
    FROM grower_field_officer gfo
    JOIN growers g ON g.id = gfo.growerid
    JOIN field_officers fo ON fo.userid = gfo.field_officerid
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=g.id
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_geo FROM grower_geofence_entry_point GROUP BY growerid) geo ON geo.growerid=g.id
    WHERE gfo.seasonid = $currentSeasonId
      AND (v.last_visit IS NULL OR DATEDIFF(NOW(),v.last_visit) >= $threshold)
    $officerFilter
    $searchWhere
    ORDER BY days_since DESC, g.name
");
if($r){while($row=$r->fetch_assoc())$overdueGrowers[]=$row;$r->free();}

// ── Never visited assigned growers ────────────────────────────────────────────
$neverVisited=[];
$r=$conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           fo.name AS assigned_officer,
           fo.id   AS assigned_officer_id,
           geo.last_geo, geo_fo.name AS last_near_officer
    FROM grower_field_officer gfo
    JOIN growers g ON g.id = gfo.growerid
    JOIN field_officers fo ON fo.userid = gfo.field_officerid
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_geo, userid FROM grower_geofence_entry_point GROUP BY growerid) geo ON geo.growerid=g.id
    LEFT JOIN field_officers geo_fo ON geo_fo.id = geo.userid
    WHERE gfo.seasonid = $currentSeasonId
      AND NOT EXISTS(SELECT 1 FROM visits WHERE growerid=g.id)
    $officerFilter
    ORDER BY fo.name, g.name
");
if($r){while($row=$r->fetch_assoc())$neverVisited[]=$row;$r->free();}

// ── Conversion gaps — officer was near assigned grower but no visit logged ────
$conversionGaps=[];
$r=$conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           fo.name AS officer_name, fo.id AS officer_id,
           geo.geo_count, geo.last_geo,
           v.last_visit, DATEDIFF(NOW(),v.last_visit) AS days_since_visit
    FROM grower_field_officer gfo
    JOIN growers g ON g.id = gfo.growerid
    JOIN field_officers fo ON fo.userid = gfo.field_officerid
    JOIN (
        SELECT growerid, COUNT(*) AS geo_count, MAX(created_at) AS last_geo, userid
        FROM grower_geofence_entry_point
        WHERE created_at >= NOW()-INTERVAL 30 DAY
        GROUP BY growerid, userid
    ) geo ON geo.growerid=g.id AND geo.userid=gfo.field_officerid
    LEFT JOIN (SELECT growerid, MAX(created_at) AS last_visit FROM visits GROUP BY growerid) v ON v.growerid=g.id
    WHERE gfo.seasonid = $currentSeasonId
      AND (v.last_visit IS NULL OR DATE(v.last_visit) < DATE(geo.last_geo))
    ORDER BY geo.geo_count DESC, g.name
    LIMIT 50
");
if($r){while($row=$r->fetch_assoc())$conversionGaps[]=$row;$r->free();}

$conn->close();

$totalOverdue=count($overdueGrowers);
$totalNever=count($neverVisited);
$totalGaps=count($conversionGaps);
$critical=count(array_filter($overdueGrowers,fn($g)=>($g['days_since']??999)>=30));
?>
<header>
  <div class="logo">GMS<span>/</span>Backlog</div>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <a href="visit_schedule.php" class="back">📅 Schedule</a>
  <a href="route_planner.php"  class="back">🗺 Route</a>
  <form method="GET" style="display:flex;gap:6px;align-items:center">
    <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Search grower...">
    <select name="days" onchange="this.form.submit()">
      <option value="7"  <?=$threshold==7?'selected':''?>>7+ days</option>
      <option value="14" <?=$threshold==14?'selected':''?>>14+ days</option>
      <option value="21" <?=$threshold==21?'selected':''?>>21+ days</option>
      <option value="30" <?=$threshold==30?'selected':''?>>30+ days</option>
    </select>
    <select name="officer" onchange="this.form.submit()">
      <option value="0">All Officers</option>
      <?php foreach($officers as $o): ?>
      <option value="<?=$o['id']?>" <?=$officer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
      <?php endforeach?>
    </select>
    <button type="submit" style="background:var(--green-dim);border:1px solid var(--green);color:var(--green);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:4px;cursor:pointer">Go</button>
  </form>
  <a href="?days=<?=$threshold?>&officer=<?=$officer?>&export=1" class="back" style="margin-left:auto">⬇ CSV</a>
</header>

<div class="content">
  <div class="summary-grid">
    <div class="sum-card"><div class="sum-label">Overdue <?=$threshold?>+ days</div><div class="sum-val" style="color:var(--red)"><?=$totalOverdue?></div><div class="sum-sub">assigned growers</div></div>
    <div class="sum-card"><div class="sum-label">Critical 30+ days</div><div class="sum-val" style="color:var(--red)"><?=$critical?></div></div>
    <div class="sum-card"><div class="sum-label">Never Visited</div><div class="sum-val" style="color:var(--amber)"><?=$totalNever?></div><div class="sum-sub">assigned this season</div></div>
    <div class="sum-card"><div class="sum-label">Near But Not Logged</div><div class="sum-val" style="color:var(--blue)"><?=$totalGaps?></div><div class="sum-sub">in last 30 days</div></div>
  </div>

  <!-- Officer assignment summary -->
  <div class="section">
    <div class="sh"><h3>📊 Officer Assignment Summary — Season <?=$currentSeasonId?></h3></div>
    <?php if(empty($officerBacklog)): ?>
    <div class="empty">No assignments found for current season</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Officer</th>
          <th>Assigned</th>
          <th>Visited OK</th>
          <th>Overdue</th>
          <th>Never Visited</th>
          <th>Coverage %</th>
          <th>Visits (7d)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($officerBacklog as $ob):
        $total   = (int)$ob['assigned_total'];
        $ok      = (int)$ob['visited_ok'];
        $overdue = (int)$ob['overdue'];
        $never   = (int)$ob['never_visited'];
        $covPct  = $total > 0 ? round(($ok / $total) * 100) : 0;
        $covCol  = $covPct >= 70 ? 'var(--green)' : ($covPct >= 40 ? 'var(--amber)' : 'var(--red)');
      ?>
      <tr>
        <td><b><?=htmlspecialchars($ob['name'])?></b></td>
        <td style="color:var(--text);font-weight:700"><?=$total?></td>
        <td style="color:var(--green)"><?=$ok?></td>
        <td>
          <?php if($overdue > 0): ?>
          <?php $oc = $overdue > 10 ? 'var(--red)' : ($overdue > 5 ? 'var(--amber)' : 'var(--green)'); ?>
          <span class="db" style="color:<?=$oc?>;background:<?=$oc?>18;border:1px solid <?=$oc?>40"><?=$overdue?></span>
          <?php else: ?><span style="color:var(--green)">0</span><?php endif?>
        </td>
        <td>
          <?php if($never > 0): ?>
          <span class="db d-over"><?=$never?></span>
          <?php else: ?><span style="color:var(--green)">0</span><?php endif?>
        </td>
        <td>
          <div class="mini-bar">
            <div class="mb-track" style="max-width:60px"><div class="mb-fill" style="width:<?=$covPct?>%;background:<?=$covCol?>"></div></div>
            <span style="font-size:10px;color:<?=$covCol?>"><?=$covPct?>%</span>
          </div>
        </td>
        <td style="color:var(--green)"><?=$ob['visits_7d']?> <span style="color:var(--muted)">/ <?=$ob['geo_7d']?> near</span></td>
        <td style="white-space:nowrap">
          <a href="route_planner.php?officer_id=<?=$ob['id']?>" class="btn-sm">🗺 Route</a>
          <a href="officer_report.php?officer_id=<?=$ob['id']?>&days=30" class="btn-sm" style="margin-left:4px">📋 Report</a>
          <a href="?days=<?=$threshold?>&officer=<?=$ob['id']?>" class="btn-sm" style="margin-left:4px">🔍 Filter</a>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    <?php endif?>
  </div>

  <!-- Near but not visited — conversion gaps -->
  <?php if(!empty($conversionGaps)): ?>
  <div class="section">
    <div class="sh"><h3>📍 Assigned Officer Was Near But No Visit Logged</h3><span style="font-size:10px;color:var(--muted)">last 30 days · <?=$totalGaps?> growers</span></div>
    <table>
      <thead><tr><th>Grower</th><th>Assigned Officer</th><th>Times Near</th><th>Last Near</th><th>Last Visit</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($conversionGaps as $g):
        $noVisit  = empty($g['last_visit']);
        $daysSince= $noVisit ? null : (int)$g['days_since_visit'];
        $cls      = $noVisit ? 'd-crit' : ($daysSince >= 14 ? 'd-over' : 'd-ok');
        $lbl      = $noVisit ? 'Never visited' : $daysSince.'d ago';
      ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--blue)"><?=htmlspecialchars($g['officer_name']??'—')?></td>
        <td>
          <div class="mini-bar">
            <div class="mb-track"><div class="mb-fill" style="width:<?=min(100,($g['geo_count']??0)*10)?>%;background:var(--blue)"></div></div>
            <span style="font-size:10px;color:var(--blue)"><?=$g['geo_count']?>×</span>
          </div>
        </td>
        <td style="color:var(--muted);font-size:10px"><?=$g['last_geo'] ? date('d M', strtotime($g['last_geo'])) : '—'?></td>
        <td><span class="db <?=$cls?>"><?=$lbl?></span></td>
        <td><a href="route_planner.php?officer_id=<?=$g['officer_id']?>" class="btn-sm">Route →</a></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endif?>

  <!-- Overdue assigned growers -->
  <div class="section">
    <div class="sh"><h3>⚠️ Overdue Assigned Growers (<?=$threshold?>+ days)</h3><span style="font-size:10px;color:var(--muted)"><?=$totalOverdue?></span></div>
    <?php if(empty($overdueGrowers)): ?>
    <div class="empty">✅ No overdue growers for the selected filter</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Grower</th><th>Assigned Officer</th><th>Last Visit</th><th>Days Overdue</th><th>Officer Nearby?</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($overdueGrowers as $g):
        $ds  = $g['days_since'] ?? null;
        $cls = $ds === null ? 'd-crit' : ($ds >= 30 ? 'd-crit' : ($ds >= 14 ? 'd-over' : 'd-ok'));
        $lbl = $ds === null ? 'Never' : $ds.'d ago';
        $geoRecent = !empty($g['last_geo']) && ($g['geo_days'] ?? 999) <= 14;
      ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--blue);font-weight:700"><?=htmlspecialchars($g['assigned_officer']??'—')?></td>
        <td style="color:var(--muted)"><?=$g['last_visit'] ? date('d M Y', strtotime($g['last_visit'])) : '—'?></td>
        <td><span class="db <?=$cls?>"><?=$lbl?></span></td>
        <td>
          <?php if($geoRecent): ?>
          <span style="color:var(--blue)">📍 <?=$g['geo_days']?>d ago</span>
          <?php else: ?>
          <span style="color:var(--muted)">—</span>
          <?php endif?>
        </td>
        <td>
          <a href="route_planner.php?officer_id=<?=$g['assigned_officer_id']?>" class="btn-sm">🗺 Route</a>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    <?php endif?>
  </div>

  <!-- Never visited assigned growers -->
  <?php if(!empty($neverVisited)): ?>
  <div class="section">
    <div class="sh"><h3>🆕 Never Visited — Assigned This Season (<?=$totalNever?>)</h3></div>
    <table>
      <thead><tr><th>Grower</th><th>Assigned Officer</th><th>Officer Nearby?</th><th>Last Near</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($neverVisited as $g): ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--blue);font-weight:700"><?=htmlspecialchars($g['assigned_officer']??'—')?></td>
        <td style="color:var(--muted)"><?=htmlspecialchars($g['last_near_officer'] ?? '—')?></td>
        <td style="color:var(--muted)"><?=$g['last_geo'] ? date('d M Y', strtotime($g['last_geo'])) : 'Never'?></td>
        <td><a href="route_planner.php?officer_id=<?=$g['assigned_officer_id']?>" class="btn-sm">🗺 Plan Route →</a></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
  <?php endif?>
</div>
</body>
</html>
