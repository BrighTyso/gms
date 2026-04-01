<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

$target  = isset($_GET['target'])  ? (int)$_GET['target']  : 14;
$officer = isset($_GET['officer']) ? (int)$_GET['officer'] : 0;

// Officers
$officers = [];
$r = $conn->query("SELECT id,name,userid FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $officers[]=$row; $r->free();}

// Season
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$seasonId=(int)$row['id']; $r->free();}

// Officer userid
$officerUserid = 0;
if($officer){
    foreach($officers as $o){ if($o['id']==$officer){ $officerUserid=(int)$o['userid']; break; } }
}

// Grower join — assigned if season exists
$growerJoin = '';
if($seasonId && $officerUserid){
    $growerJoin = "JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.field_officerid=$officerUserid AND gfo.seasonid=$seasonId";
} elseif($seasonId){
    $growerJoin = "LEFT JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId";
}

$visitWhere = $officerUserid ? "AND v.userid=$officerUserid" : "";

$growers = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           COALESCE(fo.name,'Unassigned')              AS assigned_officer,
           COUNT(v.id)                                 AS total_visits,
           MAX(v.created_at)                           AS last_visit,
           MIN(v.created_at)                           AS first_visit,
           DATEDIFF(NOW(), MAX(v.created_at))          AS days_since_last,
           CASE WHEN COUNT(v.id)>1 THEN
               ROUND(DATEDIFF(MAX(v.created_at),MIN(v.created_at))/(COUNT(v.id)-1))
           ELSE NULL END                               AS avg_days_between
    FROM growers g
    $growerJoin
    LEFT JOIN visits v ON v.growerid=g.id $visitWhere
    LEFT JOIN grower_field_officer gfo2 ON gfo2.growerid=g.id AND gfo2.seasonid=$seasonId
    LEFT JOIN field_officers fo ON fo.userid=gfo2.field_officerid
    GROUP BY g.id, g.grower_num, g.name, g.surname, fo.name
    ORDER BY days_since_last DESC, g.name
");
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

$conn->close();

// Classify each grower
$onTrack=0; $overdue=0; $neverVisited=0; $overVisited=0;
foreach($growers as &$g){
    $ds  = $g['days_since_last'] ?? 999;
    $avg = $g['avg_days_between'];
    $tv  = (int)$g['total_visits'];
    if($tv === 0)                    { $g['status']='never';   $neverVisited++; }
    elseif($ds <= $target)           { $g['status']='ok';      $onTrack++; }
    elseif($ds <= $target * 1.5)     { $g['status']='due';     $overdue++; }
    else                             { $g['status']='overdue'; $overdue++; }
    if($avg !== null && $avg < ($target * 0.5)) $overVisited++;
}
unset($g);

$total       = count($growers);
$onTrackPct  = $total > 0 ? round(($onTrack / $total) * 100) : 0;

// CSV export — before any HTML
if(isset($_GET['export'])){
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visit_frequency.csv"');
    echo "Grower,Number,Assigned Officer,Total Visits,Last Visit,Days Since,Avg Gap (days),Status\n";
    foreach($growers as $g){
        printf('"%s","%s","%s",%d,"%s",%s,"%s","%s"'."\n",
            str_replace('"','""',$g['name'].' '.$g['surname']),
            $g['grower_num'],
            str_replace('"','""',$g['assigned_officer']),
            $g['total_visits'],
            $g['last_visit'] ?? 'Never',
            $g['days_since_last'] ?? 'N/A',
            $g['avg_days_between'] ?? 'N/A',
            $g['status']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Visit Frequency</title>
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
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px}
  .sum-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px}
  .sum-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-top:4px}
  .sum-label{font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted)}
  .section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
  .sh{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .sh h3{font-family:'Syne',sans-serif;font-size:13px;font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:11px}
  th{text-align:left;padding:8px 14px;font-size:9px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--border);background:#0d150d;white-space:nowrap;cursor:pointer}
  th:hover{color:var(--text)}
  td{padding:8px 14px;border-bottom:1px solid #0f1a0f}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:rgba(61,220,104,.02)}
  .freq-bar{display:flex;align-items:center;gap:6px}
  .fb-track{width:60px;height:6px;background:var(--border);border-radius:3px;flex-shrink:0}
  .fb-fill{height:100%;border-radius:3px}
  .badge{display:inline-block;padding:2px 7px;border-radius:3px;font-size:9px;font-weight:700;border:1px solid}
  .b-ok{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .b-due{background:#1e1500;color:var(--amber);border-color:#3a2800}
  .b-over{background:#200000;color:var(--red);border-color:#400000}
  .b-never{background:#0d0d20;color:var(--blue);border-color:#003050}
  .btn-sm{font-family:'Space Mono',monospace;font-size:10px;padding:3px 8px;border-radius:3px;border:1px solid var(--green-dim);color:var(--green);background:transparent;cursor:pointer;text-decoration:none}
  .btn-sm:hover{background:var(--green-dim)}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Visit Frequency</div>
  <a href="reports_hub.php"      class="back">← Reports</a>
  <a href="officer_coverage.php" class="back">← Coverage</a>
  <form method="GET" style="display:flex;gap:6px;align-items:center">
    <select name="officer" onchange="this.form.submit()">
      <option value="0">All Officers</option>
      <?php foreach($officers as $o): ?>
      <option value="<?=$o['id']?>" <?=$officer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
      <?php endforeach?>
    </select>
    <select name="target" onchange="this.form.submit()">
      <option value="7"  <?=$target==7?'selected':''?>>Target: 7 days</option>
      <option value="14" <?=$target==14?'selected':''?>>Target: 14 days</option>
      <option value="21" <?=$target==21?'selected':''?>>Target: 21 days</option>
      <option value="30" <?=$target==30?'selected':''?>>Target: 30 days</option>
    </select>
  </form>
  <a href="?target=<?=$target?>&officer=<?=$officer?>&export=1" class="back" style="margin-left:auto">⬇ CSV</a>
</header>

<div class="content">
  <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;margin-bottom:16px">
    📅 Visit Frequency Report
    <div style="font-size:11px;font-weight:400;color:var(--muted);margin-top:4px">Target: visit every <?=$target?> days · <?=$total?> growers</div>
  </div>

  <div class="summary-grid">
    <div class="sum-card"><div class="sum-label">Total Growers</div><div class="sum-val"><?=$total?></div></div>
    <div class="sum-card"><div class="sum-label">On Track ≤<?=$target?>d</div><div class="sum-val" style="color:var(--green)"><?=$onTrack?><span style="font-size:12px"> (<?=$onTrackPct?>%)</span></div></div>
    <div class="sum-card"><div class="sum-label">Overdue</div><div class="sum-val" style="color:var(--red)"><?=$overdue?></div></div>
    <div class="sum-card"><div class="sum-label">Never Visited</div><div class="sum-val" style="color:var(--amber)"><?=$neverVisited?></div></div>
    <div class="sum-card"><div class="sum-label">Over-visited</div><div class="sum-val" style="color:var(--blue)"><?=$overVisited?></div></div>
  </div>

  <div class="section">
    <div class="sh">
      <h3>👨‍🌾 Grower Visit Frequency</h3>
      <span style="font-size:10px;color:var(--muted)"><?=$total?> growers · click column to sort</span>
    </div>
    <table id="freqTable">
      <thead>
        <tr>
          <th onclick="sortTable(0)">Grower</th>
          <th onclick="sortTable(1)">Assigned Officer</th>
          <th onclick="sortTable(2)">Total Visits</th>
          <th onclick="sortTable(3)">Last Visit</th>
          <th onclick="sortTable(4)">Days Since ▼</th>
          <th onclick="sortTable(5)">Avg Gap</th>
          <th onclick="sortTable(6)">Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($growers as $g):
        $ds  = $g['days_since_last'] ?? null;
        $avg = $g['avg_days_between'];
        $tv  = (int)$g['total_visits'];
        switch($g['status']){
          case 'ok':      $cls='b-ok';   $lbl='✅ On track'; break;
          case 'due':     $cls='b-due';  $lbl='⚠️ Due soon'; break;
          case 'overdue': $cls='b-over'; $lbl='❌ Overdue';  break;
          default:        $cls='b-never';$lbl='🆕 Never';    break;
        }
        $dsCol = $ds===null?'var(--muted)':($ds<=$target?'var(--green)':($ds<=$target*1.5?'var(--amber)':'var(--red))'));
        $avgCol= $avg===null?'var(--muted)':($avg<=$target?'var(--green)':($avg<=$target*1.5?'var(--amber)':'var(--red))'));
      ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--muted)"><?=htmlspecialchars($g['assigned_officer'])?></td>
        <td style="color:var(--green);font-weight:700"><?=$tv?></td>
        <td style="color:var(--muted)"><?=$g['last_visit']?date('d M Y',strtotime($g['last_visit'])):'—'?></td>
        <td>
          <?php if($ds!==null): ?>
          <div class="freq-bar">
            <div class="fb-track"><div class="fb-fill" style="width:<?=min(100,round($ds/$target*50))?>%;background:<?=$dsCol?>"></div></div>
            <span style="color:<?=$dsCol?>"><?=$ds?>d</span>
          </div>
          <?php else: ?>—<?php endif?>
        </td>
        <td>
          <?php if($avg!==null): ?>
          <div class="freq-bar">
            <div class="fb-track"><div class="fb-fill" style="width:<?=min(100,round(($target/$avg)*50))?>%;background:<?=$avgCol?>"></div></div>
            <span style="color:<?=$avgCol?>"><?=$avg?>d</span>
          </div>
          <?php else: ?>—<?php endif?>
        </td>
        <td><span class="badge <?=$cls?>"><?=$lbl?></span></td>
        <td><a href="route_planner.php" class="btn-sm">Route →</a></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
</div>
<script>
let sc=4, sa=false;
function sortTable(col){
  const t=document.getElementById('freqTable'), b=t.querySelector('tbody');
  const rows=Array.from(b.querySelectorAll('tr'));
  sa = sc===col ? !sa : false; sc=col;
  rows.sort((a,b)=>{
    const av=a.cells[col]?.textContent?.trim()||'';
    const bv=b.cells[col]?.textContent?.trim()||'';
    const an=parseFloat(av), bn=parseFloat(bv);
    const cmp=isNaN(an)||isNaN(bn)?av.localeCompare(bv):an-bn;
    return sa?cmp:-cmp;
  });
  rows.forEach(r=>b.appendChild(r));
}
</script>
</body>
</html>
