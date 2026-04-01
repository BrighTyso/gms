<?php ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

$officer  = isset($_GET['officer']) ? (int)$_GET['officer'] : 0;
$minScore = isset($_GET['min'])     ? (int)$_GET['min']     : 0;
$maxScore = isset($_GET['max'])     ? (int)$_GET['max']     : 100;

$officers = [];
$r = $conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $officers[]=$row; $r->free();}

$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$seasonId=(int)$row['id']; $r->free();}

$officerJoin  = $seasonId ? "LEFT JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId LEFT JOIN field_officers fo_a ON fo_a.userid=gfo.field_officerid" : "LEFT JOIN field_officers fo_a ON 1=0";
$officerWhere = $officer  ? "AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id=$officer LIMIT 1)" : "";

$growers = [];
$r = $conn->query("
    SELECT g.id, g.grower_num, g.name, g.surname,
           g.phone, g.id_num,
           fo_a.name AS officer_name,
           ll.latitude, ll.longitude,
           barn.has_barn,
           farm.has_farm,
           seed.has_seedbed,
           v.visit_count
    FROM growers g
    $officerJoin
    LEFT JOIN (SELECT growerid, latitude, longitude FROM lat_long WHERE latitude IS NOT NULL AND latitude!=0 LIMIT 1) ll ON ll.growerid=g.id
    LEFT JOIN (SELECT growerid, COUNT(*) AS has_barn    FROM barn_location    GROUP BY growerid) barn ON barn.growerid=g.id
    LEFT JOIN (SELECT growerid, COUNT(*) AS has_farm    FROM grower_farm      GROUP BY growerid) farm ON farm.growerid=g.id
    LEFT JOIN (SELECT growerid, COUNT(*) AS has_seedbed FROM seedbed_location GROUP BY growerid) seed ON seed.growerid=g.id
    LEFT JOIN (SELECT growerid, COUNT(*) AS visit_count FROM visits GROUP BY growerid) v ON v.growerid=g.id
    WHERE 1=1 $officerWhere
    ORDER BY g.name, g.surname
");
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}

$conn->close();

// Fields: name(20) phone(15) id_number(20) gps(20) barn(10) farm(10) seedbed(5) = 100pts
foreach($growers as &$g){
    $score = 0;
    $missing = [];
    if(!empty($g['name']) && !empty($g['surname']))  $score+=20; else $missing[]='Name';
    if(!empty($g['phone']))                           $score+=15; else $missing[]='Phone';
    if(!empty($g['id_num']))                          $score+=20; else $missing[]='ID Number';
    if($g['latitude'])                                $score+=20; else $missing[]='GPS Location';
    if($g['has_barn'])                                $score+=10; else $missing[]='Barn';
    if($g['has_farm'])                                $score+=10; else $missing[]='Farm';
    if($g['has_seedbed'])                             $score+=5;  else $missing[]='Seedbed';
    $g['score']   = $score;
    $g['missing'] = $missing;
    $g['level']   = $score>=80?'complete':($score>=50?'partial':'incomplete');
}
unset($g);

// Filter by score range
$growers = array_filter($growers, function($g) use ($minScore,$maxScore){ return $g['score'] >= $minScore && $g['score'] <= $maxScore; });
usort($growers, function($a,$b){ return $a['score'] - $b['score']; });

$complete   = count(array_filter($growers, function($g){ return $g['level']==='complete'; }));
$partial    = count(array_filter($growers, function($g){ return $g['level']==='partial'; }));
$incomplete = count(array_filter($growers, function($g){ return $g['level']==='incomplete'; }));
$total      = count($growers);
$avgScore   = $total > 0 ? round(array_sum(array_column($growers,'score'))/$total) : 0;

if(isset($_GET['export'])){
    ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="data_completeness.csv"');
    echo "Grower,Number,Officer,Score,Level,Missing Fields\n";
    foreach($growers as $g) echo '"'.str_replace('"','""',$g['name'].' '.$g['surname']).'","'.$g['grower_num'].'","'.($g['officer_name']??'—').'",'.$g['score'].',"'.$g['level'].'","'.implode('; ',$g['missing'])."\"\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Data Completeness</title>
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
  .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px}
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
  tr:hover td{background:rgba(61,220,104,.02)}
  .score-bar{display:flex;align-items:center;gap:6px}
  .sb-track{height:6px;background:var(--border);border-radius:3px;width:70px}
  .sb-fill{height:100%;border-radius:3px}
  .missing-tag{display:inline-block;font-size:9px;padding:1px 5px;border-radius:3px;background:#200000;color:var(--red);border:1px solid #400000;margin:1px}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Completeness</div>
  <a href="reports_hub.php"   class="back">← Reports</a>
  <a href="data_quality.php"  class="back">🔍 Quality</a>
  <select onchange="location.href='?officer='+this.value+'&min=<?=$minScore?>&max=<?=$maxScore?>'">
    <option value="0">All Officers</option>
    <?php foreach($officers as $o): ?>
    <option value="<?=$o['id']?>" <?=$officer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
    <?php endforeach?>
  </select>
  <select onchange="location.href='?officer=<?=$officer?>&min=0&max='+this.value">
    <option value="100">All scores</option>
    <option value="79"  <?=$maxScore==79?'selected':''?>>Incomplete + Partial only</option>
    <option value="49"  <?=$maxScore==49?'selected':''?>>Incomplete only</option>
  </select>
  <a href="?officer=<?=$officer?>&min=<?=$minScore?>&max=<?=$maxScore?>&export=1" class="back" style="margin-left:auto">⬇ CSV</a>
</header>

<div class="content">
  <div class="summary-grid">
    <?php $avgScoreCol=$avgScore>=80?"var(--green)":($avgScore>=50?"var(--amber)":"var(--red)"); ?>
    <div class="card"><div class="card-label">Avg Score</div><div class="card-val" style="color:<?=$avgScoreCol?>"><?=$avgScore?>/100</div></div>
    <div class="card"><div class="card-label">Complete (80+)</div><div class="card-val" style="color:var(--green)"><?=$complete?></div></div>
    <div class="card"><div class="card-label">Partial (50-79)</div><div class="card-val" style="color:var(--amber)"><?=$partial?></div></div>
    <div class="card"><div class="card-label">Incomplete (&lt;50)</div><div class="card-val" style="color:var(--red)"><?=$incomplete?></div></div>
  </div>

  <!-- Scoring guide -->
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;font-size:10px;color:var(--muted)">
    <b style="color:var(--text)">Scoring:</b>
    Name (20pts) · Phone (15pts) · ID Number (20pts) · GPS Location (20pts) · Barn (10pts) · Farm (10pts) · Seedbed (5pts) = 100pts total
  </div>

  <div class="section">
    <div class="sh">
      <h3>📊 Grower Data Completeness (<?=$total?> growers)</h3>
      <span style="font-size:10px;color:var(--muted)">Sorted by score ascending</span>
    </div>
    <table>
      <thead><tr><th>Grower</th><th>Officer</th><th>Score</th><th>Visits</th><th>Missing Fields</th></tr></thead>
      <tbody>
      <?php foreach($growers as $g):
        $col = $g['score']>=80?'var(--green)':($g['score']>=50?'var(--amber)':'var(--red)');
      ?>
      <tr>
        <td><b><?=htmlspecialchars($g['name'].' '.$g['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span></td>
        <td style="color:var(--muted)"><?=htmlspecialchars($g['officer_name']??'—')?></td>
        <td>
          <div class="score-bar">
            <div class="sb-track"><div class="sb-fill" style="width:<?=$g['score']?>%;background:<?=$col?>"></div></div>
            <span style="color:<?=$col?>;font-weight:700"><?=$g['score']?></span>
          </div>
        </td>
        <td style="color:var(--muted)"><?=$g['visit_count']??0?></td>
        <td><?php foreach($g['missing'] as $m): ?><span class="missing-tag"><?=htmlspecialchars($m)?></span><?php endforeach?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>