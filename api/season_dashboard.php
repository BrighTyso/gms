<?php
// v1773990861 — GMS green scheme
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// ── Active season ─────────────────────────────────────────────────────────────
$seasonId = 0; $seasonName = '—';
$r = $conn->query("SELECT id, name FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $seasonName=$row['name']; $r->free(); }

// ── Filters ───────────────────────────────────────────────────────────────────
$filterOfficer = isset($_GET['officer_id']) && $_GET['officer_id']!=='' ? (int)$_GET['officer_id'] : null;
$officerWhere  = $filterOfficer ? "AND userid=$filterOfficer" : "";
$gWhere        = "seasonid=$seasonId" . ($filterOfficer ? " AND userid=$filterOfficer" : "");

// ── Officers dropdown ─────────────────────────────────────────────────────────
$allOfficers = [];
$r = $conn->query("SELECT id, userid, name FROM field_officers ORDER BY name");
if($r){ while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free(); }

// ── Total growers ─────────────────────────────────────────────────────────────
$totalGrowers = 0;
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM grower_field_officer WHERE seasonid=$seasonId" . ($filterOfficer ? " AND field_officerid=$filterOfficer" : ""));
if($r && $row=$r->fetch_assoc()){ $totalGrowers=(int)$row['cnt']; $r->free(); }

// ── Stage 1: Seedbed ──────────────────────────────────────────────────────────
$seedbed = ['germination'=>0,'avg_germ_pct'=>0,'pest_issues'=>0,'soil_health'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, AVG(CAST(germination_percentage AS DECIMAL(5,2))) AS avg_germ FROM seed_germination WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $seedbed['germination']=(int)$row['cnt']; $seedbed['avg_germ_pct']=round((float)$row['avg_germ'],1); $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM diseases_pest_control WHERE $gWhere AND (seedbed_pest_identified IS NOT NULL AND seedbed_pest_identified!='' OR seedbed_disease_identified IS NOT NULL AND seedbed_disease_identified!='')");
if($r && $row=$r->fetch_assoc()){ $seedbed['pest_issues']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM seedbed_soil_health WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $seedbed['soil_health']=(int)$row['cnt']; $r->free(); }

// ── Stage 2: Land Prep ────────────────────────────────────────────────────────
$landprep = ['ploughed'=>0,'disced'=>0,'ridged'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM landprep_ploughing WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $landprep['ploughed']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM landprep_discing WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $landprep['disced']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM landprep_ridging WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $landprep['ridged']=(int)$row['cnt']; $r->free(); }

// ── Stage 3: Transplanting ────────────────────────────────────────────────────
$transplant = ['count'=>0,'avg_survival'=>0,'total_ha'=>0,'diseases'=>0,'pests'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, AVG(CAST(transplant_survival_rate AS DECIMAL(5,2))) AS avg_surv, SUM(CAST(NULLIF(hectares_transplanted,'') AS DECIMAL(10,2))) AS total_ha FROM tobacco_transplanting WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $transplant['count']=(int)$row['cnt']; $transplant['avg_survival']=round((float)$row['avg_surv'],1); $transplant['total_ha']=round((float)$row['total_ha'],1); $r->free(); }

// ── Stage 4: Crop Growth ──────────────────────────────────────────────────────
$cropGrowth = ['measured'=>0,'avg_height'=>0,'pests'=>0,'diseases'=>0,'nutrition'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, AVG(CAST(NULLIF(avg_plant_height,'') AS DECIMAL(10,2))) AS avg_h FROM crop_measurement WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $cropGrowth['measured']=(int)$row['cnt']; $cropGrowth['avg_height']=round((float)$row['avg_h'],1); $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM pest_management WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $cropGrowth['pests']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM disease_management WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $cropGrowth['diseases']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM crop_nutrition WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $cropGrowth['nutrition']=(int)$row['cnt']; $r->free(); }

// ── Stage 5: Flowering ────────────────────────────────────────────────────────
$flowering = ['topped'=>0,'avg_harvest_ready'=>0,'avg_weight'=>0,'harvest_ready'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, AVG(harvest_ready_pct) AS avg_hr, AVG(expected_weight) AS avg_w, SUM(harvest_ready_pct>=80) AS ready FROM flowering_ripening_stage WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $flowering['topped']=(int)$row['cnt']; $flowering['avg_harvest_ready']=round((float)$row['avg_hr'],1); $flowering['avg_weight']=round((float)$row['avg_w'],1); $flowering['harvest_ready']=(int)$row['ready']; $r->free(); }

// ── Stage 6: Harvesting ───────────────────────────────────────────────────────
$harvest = ['assessed'=>0,'ready'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, SUM(harvest_readiness='Ready') AS ready FROM harvesting_readiness WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $harvest['assessed']=(int)$row['cnt']; $harvest['ready']=(int)$row['ready']; $r->free(); }

// ── Stage 7: Curing ───────────────────────────────────────────────────────────
$curing = ['total_barns'=>0,'yellowing'=>0,'lamina'=>0,'midrib'=>0,'mould'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS barns FROM tobacco_curing WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $curing['total_barns']=(int)$row['barns']; $r->free(); }
$r = $conn->query("SELECT COUNT(*) AS cnt FROM yellowing_stage WHERE $gWhere AND yellowing_status='Complete'");
if($r && $row=$r->fetch_assoc()){ $curing['yellowing']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(*) AS cnt FROM lamina_stage WHERE $gWhere AND lamina_status='Complete'");
if($r && $row=$r->fetch_assoc()){ $curing['lamina']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(*) AS cnt FROM midrib_stage WHERE $gWhere AND midrib_status='Complete'");
if($r && $row=$r->fetch_assoc()){ $curing['midrib']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(*) AS cnt FROM curing_leaf_quality WHERE $gWhere AND mould_presence NOT IN ('None','No','')");
if($r && $row=$r->fetch_assoc()){ $curing['mould']=(int)$row['cnt']; $r->free(); }

// ── Stage 8: Grading ──────────────────────────────────────────────────────────
$grading = ['graded'=>0,'market_ready'=>0,'quality_issues'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt, SUM(market_readiness='Ready') AS ready FROM overall_grading WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $grading['graded']=(int)$row['cnt']; $grading['market_ready']=(int)$row['ready']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM leaf_grading_quality WHERE $gWhere AND (foreign_matter NOT IN ('None','No','') OR excessive_midribs NOT IN ('None','No',''))");
if($r && $row=$r->fetch_assoc()){ $grading['quality_issues']=(int)$row['cnt']; $r->free(); }

// ── Barn Assessment ───────────────────────────────────────────────────────────
$barnStats = ['assessed'=>0,'fire_issues'=>0,'ventilation_issues'=>0];
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM barn_structure WHERE $gWhere");
if($r && $row=$r->fetch_assoc()){ $barnStats['assessed']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM barn_fire_safety WHERE $gWhere AND firebreak_cleared NOT IN ('Yes','Good','Clear','')");
if($r && $row=$r->fetch_assoc()){ $barnStats['fire_issues']=(int)$row['cnt']; $r->free(); }
$r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM barn_ventilation WHERE $gWhere AND vent_condition NOT IN ('Good','Excellent','')");
if($r && $row=$r->fetch_assoc()){ $barnStats['ventilation_issues']=(int)$row['cnt']; $r->free(); }

// ── Officer activity depth ────────────────────────────────────────────────────
$officerActivity = [];
$r = $conn->query("
    SELECT fo.name AS officer,
           (SELECT COUNT(*) FROM seed_germination WHERE userid=fo.userid AND seasonid=$seasonId) AS seedbed,
           (SELECT COUNT(*) FROM tobacco_transplanting WHERE userid=fo.userid AND seasonid=$seasonId) AS transplant,
           (SELECT COUNT(*) FROM crop_measurement WHERE userid=fo.userid AND seasonid=$seasonId) AS crop_meas,
           (SELECT COUNT(*) FROM flowering_ripening_stage WHERE userid=fo.userid AND seasonid=$seasonId) AS flowering,
           (SELECT COUNT(*) FROM tobacco_curing WHERE userid=fo.userid AND seasonid=$seasonId) AS curing,
           (SELECT COUNT(*) FROM overall_grading WHERE userid=fo.userid AND seasonid=$seasonId) AS grading
    FROM field_officers fo ORDER BY fo.name
");
if($r){ while($row=$r->fetch_assoc()) $officerActivity[]=$row; $r->free(); }

// ── At-risk growers ───────────────────────────────────────────────────────────
$riskGrowers = [];
$r = $conn->query("
    SELECT g.grower_num, g.name, g.surname, fo.name AS officer,
           COALESCE(ns.field_drought_severity,'—') AS drought,
           COALESCE(pm.observed_pest,'—') AS pest,
           COALESCE(dm.observed_disease,'—') AS disease
    FROM growers g
    JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid=$seasonId
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    LEFT JOIN (SELECT growerid, field_drought_severity FROM nutrient_stress WHERE seasonid=$seasonId ORDER BY created_at DESC LIMIT 1) ns ON ns.growerid=g.id
    LEFT JOIN (SELECT growerid, observed_pest FROM pest_management WHERE seasonid=$seasonId ORDER BY created_at DESC LIMIT 1) pm ON pm.growerid=g.id
    LEFT JOIN (SELECT growerid, observed_disease FROM disease_management WHERE seasonid=$seasonId ORDER BY created_at DESC LIMIT 1) dm ON dm.growerid=g.id
    WHERE (ns.field_drought_severity NOT IN ('Low','None','—') OR pm.observed_pest NOT IN ('None','—','') OR dm.observed_disease NOT IN ('None','—',''))
    " . ($filterOfficer ? "AND gfo.field_officerid=$filterOfficer" : "") . "
    LIMIT 15
");
if($r){ while($row=$r->fetch_assoc()) $riskGrowers[]=$row; $r->free(); }

// ── Weekly activity trend ─────────────────────────────────────────────────────
$weeklyActivity = [];
$r = $conn->query("
    SELECT DATE_FORMAT(created_at,'%Y-%u') AS yw, MIN(DATE(created_at)) AS week_start, COUNT(*) AS cnt
    FROM (
        SELECT created_at FROM seed_germination WHERE seasonid=$seasonId $officerWhere
        UNION ALL SELECT created_at FROM tobacco_transplanting WHERE seasonid=$seasonId $officerWhere
        UNION ALL SELECT created_at FROM crop_measurement WHERE seasonid=$seasonId $officerWhere
        UNION ALL SELECT created_at FROM flowering_ripening_stage WHERE seasonid=$seasonId $officerWhere
        UNION ALL SELECT created_at FROM tobacco_curing WHERE seasonid=$seasonId $officerWhere
    ) all_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
    GROUP BY yw ORDER BY yw
");
if($r){ while($row=$r->fetch_assoc()) $weeklyActivity[]=$row; $r->free(); }

// ── Bike stats ────────────────────────────────────────────────────────────────
$bikeStats = ['total_km'=>0,'total_fuel'=>0];
$r = $conn->query("SELECT SUM(CAST(NULLIF(kms,'') AS DECIMAL(10,2))) AS total_km, SUM(CAST(NULLIF(fuel,'') AS DECIMAL(10,2))) AS total_fuel FROM bike_performance_and_efficiency WHERE seasonid=$seasonId" . ($filterOfficer ? " AND userid=$filterOfficer" : ""));
if($r && $row=$r->fetch_assoc()){ $bikeStats['total_km']=round((float)$row['total_km'],1); $bikeStats['total_fuel']=round((float)$row['total_fuel'],1); $r->free(); }

$conn->close();

// ── Pipeline percentages ──────────────────────────────────────────────────────
$pipe = [];
$pipeData = [
    'seedbed'    => $seedbed['germination'],
    'landprep'   => $landprep['ploughed'],
    'transplant' => $transplant['count'],
    'growth'     => $cropGrowth['measured'],
    'flowering'  => $flowering['topped'],
    'harvesting' => $harvest['assessed'],
    'curing'     => $curing['total_barns'],
    'grading'    => $grading['graded'],
];
foreach($pipeData as $k=>$v) $pipe[$k] = $totalGrowers>0 ? round(($v/$totalGrowers)*100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Season Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080d08;--surface:#0f170f;--surface2:#141e14;--surface3:#192419;
  --border:#1a2a1a;--border2:#243224;
  --green:#3ddc68;--green-dim:#1a5e30;--green-glow:rgba(61,220,104,.1);
  --amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b060ff;
  --text:#c8e6c9;--muted:#4a6b4a;--radius:8px;--radius2:4px;
}
html,body{min-height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);font-size:12px}

header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;
  background:var(--surface);border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;flex-wrap:wrap}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);letter-spacing:-1px}
.logo span{color:var(--muted)}
.back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);transition:.15s}
.back:hover{color:var(--green);border-color:var(--green)}
select{background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:11px;padding:4px 8px;border-radius:var(--radius2);outline:none}
select:focus{border-color:var(--green)}
.season-badge{margin-left:auto;font-size:10px;color:var(--green);border:1px solid var(--green-dim);padding:3px 10px;border-radius:var(--radius2)}

.page{padding:20px;max-width:1600px;margin:0 auto}
.section-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;text-transform:uppercase;
  letter-spacing:1px;color:var(--muted);margin-bottom:14px;display:flex;align-items:center;gap:8px}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:24px}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:14px 16px;position:relative;overflow:hidden;transition:.2s}
.kpi::before{content:'';position:absolute;inset:0;background:var(--green-glow);opacity:0;transition:.2s}
.kpi:hover::before{opacity:1}
.kpi-icon{font-size:18px;margin-bottom:6px}
.kpi-val{font-family:'Syne',sans-serif;font-size:clamp(16px,1.8vw,24px);font-weight:900;line-height:1;margin-bottom:3px;word-break:break-word}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.kpi-sub{font-size:10px;color:var(--muted);margin-top:4px}

/* Pipeline */
.pipeline{display:flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px}
.pipe-step{flex:1;padding:12px 8px;text-align:center;border-right:1px solid var(--border);
  background:var(--surface);transition:.15s}
.pipe-step:last-child{border-right:none}
.pipe-step:hover{background:var(--surface2)}
.pipe-icon{font-size:16px;margin-bottom:4px}
.pipe-num{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;line-height:1}
.pipe-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);margin-top:2px}
.pipe-bar{height:3px;background:var(--border2);margin-top:6px;border-radius:2px}
.pipe-fill{height:100%;border-radius:2px;transition:width .7s}
.pipe-pct{font-size:8px;color:var(--muted);margin-top:2px;font-family:'Space Mono',monospace}
@media(max-width:900px){.pipeline{flex-wrap:wrap}.pipe-step{min-width:25%}}

/* Stage grid */
.stage-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;margin-bottom:24px}
.stage-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:.2s}
.stage-card:hover{border-color:var(--green-dim)}
.stage-head{padding:10px 14px;border-bottom:1px solid var(--border);background:var(--surface2);display:flex;align-items:center;gap:8px}
.stage-num{font-family:'Syne',sans-serif;font-size:10px;font-weight:800;background:var(--green-dim);color:var(--green);padding:1px 7px;border-radius:3px}
.stage-name{font-family:'Syne',sans-serif;font-size:12px;font-weight:700}
.stage-pct{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;margin-left:auto}
.stage-body{padding:10px 14px}
.stage-bar{height:3px;background:var(--border2);border-radius:2px;margin:6px 0 8px}
.stage-bar-fill{height:100%;border-radius:2px;background:linear-gradient(90deg,var(--green-dim),var(--green));transition:width .7s}
.stage-stat{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #0d180d;font-size:11px}
.stage-stat:last-child{border-bottom:none}
.sl{color:var(--muted)}.sv{font-family:'Space Mono',monospace}

/* Cards */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
@media(max-width:1100px){.two-col{grid-template-columns:1fr}}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.card-head{padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--surface2)}
.card-head h3{font-family:'Syne',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.badge{font-size:9px;color:var(--muted);border:1px solid var(--border);padding:2px 7px;border-radius:var(--radius2)}
.chart-wrap{height:200px;padding:10px;position:relative}

/* Officer activity */
.act-table{width:100%;border-collapse:collapse;font-size:11px}
.act-table th{padding:6px 10px;font-size:8px;text-transform:uppercase;letter-spacing:.4px;
  color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);text-align:center}
.act-table th:first-child{text-align:left}
.act-table td{padding:6px 10px;border-bottom:1px solid #0d180d;text-align:center}
.act-table td:first-child{text-align:left;font-weight:700}
.act-table tr:last-child td{border-bottom:none}
.act-table tr:hover td{background:rgba(61,220,104,.02)}
.act-dot{display:inline-flex;align-items:center;justify-content:center;
  width:22px;height:22px;border-radius:50%;font-size:9px;font-weight:700}
.dot-0{background:var(--border2);color:var(--muted)}
.dot-low{background:#0d200d;color:var(--green);border:1px solid var(--green-dim)}
.dot-med{background:#1a2e00;color:#a3e635;border:1px solid #3a5a00}
.dot-high{background:#2a1f00;color:var(--amber);border:1px solid #4a3500}

/* Risk table */
.risk-table{width:100%;border-collapse:collapse;font-size:11px}
.risk-table th{padding:6px 10px;font-size:8px;text-transform:uppercase;letter-spacing:.4px;
  color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);text-align:left}
.risk-table td{padding:6px 10px;border-bottom:1px solid #0d180d}
.risk-table tr:last-child td{border-bottom:none}
.risk-table tr:hover td{background:rgba(232,64,64,.03)}
.rp{display:inline-block;padding:1px 6px;border-radius:3px;font-size:9px}
.rp-hi{background:#200a0a;color:var(--red);border:1px solid #4a1010}
.rp-md{background:#1e1500;color:var(--amber);border:1px solid #3a2800}
.rp-ok{color:var(--muted);font-size:9px}

/* Barn + bike strip */
.barn-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border)}
.barn-cell{background:var(--surface);padding:12px;text-align:center}
.barn-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800}
.barn-lbl{font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px}
.bike-row{display:flex;gap:10px;padding:12px 14px;border-top:1px solid var(--border);flex-wrap:wrap}
.bike-chip{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius2);padding:8px 12px;flex:1;min-width:90px;text-align:center}
.bike-val{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--blue)}
.bike-lbl{font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px}

.empty{padding:24px;text-align:center;color:var(--muted)}
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-thumb{background:var(--border2)}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Season</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="loans_dashboard.php" class="back">💰 Loans</a>
  <a href="cluster_performance.php" class="back">🗺 Clusters</a>
  <form method="GET" style="display:flex;align-items:center;gap:6px">
    <select name="officer_id" onchange="this.form.submit()">
      <option value="">All Officers</option>
      <?php foreach($allOfficers as $o): ?>
      <option value="<?=$o['userid']?>" <?=$filterOfficer==$o['userid']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
      <?php endforeach?>
    </select>
    <?php if($filterOfficer): ?>
    <a href="?" style="font-size:10px;color:var(--red);border:1px solid #3a1010;padding:3px 8px;border-radius:3px;text-decoration:none">✕</a>
    <?php endif?>
  </form>
  <div class="season-badge">Season <?=htmlspecialchars($seasonName)?> · <?=number_format($totalGrowers)?> growers</div>
</header>

<div class="page">

  <!-- KPIs -->
  <div class="section-title">Overview</div>
  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-icon">🌱</div><div class="kpi-val" style="color:var(--green)"><?=number_format($totalGrowers)?></div><div class="kpi-label">Total Growers</div><div class="kpi-sub">Active this season</div></div>
    <div class="kpi"><div class="kpi-icon">🌿</div><div class="kpi-val" style="color:var(--green)"><?=$seedbed['avg_germ_pct']?>%</div><div class="kpi-label">Avg Germination</div><div class="kpi-sub"><?=$seedbed['germination']?> assessed</div></div>
    <div class="kpi"><div class="kpi-icon">🌾</div><div class="kpi-val" style="color:var(--blue)"><?=number_format($transplant['total_ha'],1)?> ha</div><div class="kpi-label">Ha Transplanted</div><div class="kpi-sub"><?=$transplant['avg_survival']?>% survival</div></div>
    <div class="kpi"><div class="kpi-icon">📏</div><div class="kpi-val" style="color:var(--amber)"><?=$cropGrowth['avg_height']?> cm</div><div class="kpi-label">Avg Plant Height</div><div class="kpi-sub"><?=$cropGrowth['measured']?> measured</div></div>
    <div class="kpi"><div class="kpi-icon">🌸</div><div class="kpi-val" style="color:var(--green)"><?=$flowering['harvest_ready']?></div><div class="kpi-label">Ready to Harvest</div><div class="kpi-sub"><?=$flowering['avg_harvest_ready']?>% avg maturity</div></div>
    <div class="kpi"><div class="kpi-icon">🔥</div><div class="kpi-val" style="color:var(--amber)"><?=$curing['total_barns']?></div><div class="kpi-label">Barns Curing</div><div class="kpi-sub"><?=$curing['mould']?> mould detected</div></div>
    <div class="kpi"><div class="kpi-icon">📦</div><div class="kpi-val" style="color:var(--green)"><?=$grading['market_ready']?></div><div class="kpi-label">Market Ready</div><div class="kpi-sub"><?=$grading['graded']?> graded</div></div>
    <div class="kpi"><div class="kpi-icon">🚨</div><div class="kpi-val" style="color:<?=count($riskGrowers)>0?'var(--red)':'var(--green)'?>"><?=count($riskGrowers)?></div><div class="kpi-label">At-Risk Growers</div><div class="kpi-sub">Pest / disease / drought</div></div>
  </div>

  <!-- Pipeline -->
  <div class="section-title">Crop Stage Pipeline</div>
  <div class="pipeline">
    <?php
    $stages = [
      ['🌱','Seedbed',$seedbed['germination'],$pipe['seedbed']],
      ['🚜','Land Prep',$landprep['ploughed'],$pipe['landprep']],
      ['🌿','Transplant',$transplant['count'],$pipe['transplant']],
      ['📈','Growth',$cropGrowth['measured'],$pipe['growth']],
      ['🌸','Flowering',$flowering['topped'],$pipe['flowering']],
      ['✂️','Harvest',$harvest['assessed'],$pipe['harvesting']],
      ['🔥','Curing',$curing['total_barns'],$pipe['curing']],
      ['🏷','Grading',$grading['graded'],$pipe['grading']],
    ];
    foreach($stages as [$icon,$name,$cnt,$pct]):
      $col = $pct>=70?'var(--green)':($pct>=40?'var(--amber)':'var(--red)');
    ?>
    <div class="pipe-step">
      <div class="pipe-icon"><?=$icon?></div>
      <div class="pipe-num" style="color:<?=$col?>"><?=number_format($cnt)?></div>
      <div class="pipe-lbl"><?=$name?></div>
      <div class="pipe-bar"><div class="pipe-fill" style="width:<?=$pct?>%;background:<?=$col?>"></div></div>
      <div class="pipe-pct"><?=$pct?>%</div>
    </div>
    <?php endforeach?>
  </div>

  <!-- Stage cards -->
  <div class="section-title">Stage Breakdown</div>
  <div class="stage-grid">

    <?php
    $stageCards = [
      ['01','🌱 Seedbed',$pipe['seedbed'],[
        ['Assessed',$seedbed['germination'],'var(--text)'],
        ['Avg Germination',$seedbed['avg_germ_pct'].'%','var(--green)'],
        ['Pest/Disease Issues',$seedbed['pest_issues'],$seedbed['pest_issues']>0?'var(--red)':'var(--green)'],
        ['Soil Health Assessed',$seedbed['soil_health'],'var(--text)'],
      ]],
      ['02','🚜 Land Prep',$pipe['landprep'],[
        ['Ploughed',$landprep['ploughed'],'var(--text)'],
        ['Disced',$landprep['disced'],'var(--text)'],
        ['Ridged',$landprep['ridged'],'var(--text)'],
        ['Completion',$pipe['landprep'].'%','var(--amber)'],
      ]],
      ['03','🌿 Transplanting',$pipe['transplant'],[
        ['Growers Transplanted',$transplant['count'],'var(--text)'],
        ['Avg Survival Rate',$transplant['avg_survival'].'%','var(--green)'],
        ['Total Hectares',$transplant['total_ha'].' ha','var(--blue)'],
        ['Diseases Reported',$transplant['diseases'],$transplant['diseases']>0?'var(--amber)':'var(--green)'],
      ]],
      ['04','📈 Crop Growth',$pipe['growth'],[
        ['Measurements Done',$cropGrowth['measured'],'var(--text)'],
        ['Avg Plant Height',$cropGrowth['avg_height'].' cm','var(--blue)'],
        ['Pest Management',$cropGrowth['pests'],'var(--amber)'],
        ['Nutrition Applied',$cropGrowth['nutrition'],'var(--text)'],
      ]],
      ['05','🌸 Flowering',$pipe['flowering'],[
        ['Topped',$flowering['topped'],'var(--text)'],
        ['Avg Maturity',$flowering['avg_harvest_ready'].'%','var(--amber)'],
        ['Ready to Harvest',$flowering['harvest_ready'],'var(--green)'],
        ['Avg Expected Weight',$flowering['avg_weight'].' kg','var(--text)'],
      ]],
      ['06','✂️ Harvesting',$pipe['harvesting'],[
        ['Assessed',$harvest['assessed'],'var(--text)'],
        ['Ready',$harvest['ready'],'var(--green)'],
        ['Pending',max(0,$harvest['assessed']-$harvest['ready']),'var(--amber)'],
        ['Not Yet Assessed',max(0,$totalGrowers-$harvest['assessed']),'var(--red)'],
      ]],
      ['07','🔥 Curing',$pipe['curing'],[
        ['Barns Active',$curing['total_barns'],'var(--text)'],
        ['Yellowing Complete',$curing['yellowing'],'var(--amber)'],
        ['Lamina Complete',$curing['lamina'],'var(--blue)'],
        ['Mould Detected',$curing['mould'],$curing['mould']>0?'var(--red)':'var(--green)'],
      ]],
      ['08','🏷 Grading',$pipe['grading'],[
        ['Graded',$grading['graded'],'var(--text)'],
        ['Market Ready',$grading['market_ready'],'var(--green)'],
        ['Quality Issues',$grading['quality_issues'],$grading['quality_issues']>0?'var(--red)':'var(--green)'],
        ['Not Yet Graded',max(0,$totalGrowers-$grading['graded']),'var(--amber)'],
      ]],
    ];
    foreach($stageCards as [$num,$name,$pct,$stats]):
      $pcol = $pct>=70?'var(--green)':($pct>=40?'var(--amber)':'var(--red)');
    ?>
    <div class="stage-card">
      <div class="stage-head">
        <span class="stage-num"><?=$num?></span>
        <span class="stage-name"><?=$name?></span>
        <span class="stage-pct" style="color:<?=$pcol?>"><?=$pct?>%</span>
      </div>
      <div class="stage-body">
        <div class="stage-bar"><div class="stage-bar-fill" style="width:<?=$pct?>%"></div></div>
        <?php foreach($stats as [$lbl,$val,$col]): ?>
        <div class="stage-stat"><span class="sl"><?=$lbl?></span><span class="sv" style="color:<?=$col?>"><?=$val?></span></div>
        <?php endforeach?>
      </div>
    </div>
    <?php endforeach?>
  </div>

  <!-- Activity chart + Barn -->
  <div class="two-col">
    <div class="card">
      <div class="card-head"><h3>📊 Weekly Field Activity</h3><span class="badge">Last 12 weeks</span></div>
      <div class="chart-wrap"><canvas id="activityChart"></canvas></div>
    </div>
    <div class="card">
      <div class="card-head"><h3>🏚 Barn Assessment</h3><span class="badge"><?=$barnStats['assessed']?> assessed</span></div>
      <div class="barn-row">
        <div class="barn-cell"><div class="barn-val" style="color:var(--blue)"><?=$barnStats['assessed']?></div><div class="barn-lbl">Assessed</div></div>
        <div class="barn-cell"><div class="barn-val" style="color:<?=$barnStats['fire_issues']>0?'var(--red)':'var(--green)'?>"><?=$barnStats['fire_issues']?></div><div class="barn-lbl">Fire Issues</div></div>
        <div class="barn-cell"><div class="barn-val" style="color:<?=$barnStats['ventilation_issues']>0?'var(--amber)':'var(--green)'?>"><?=$barnStats['ventilation_issues']?></div><div class="barn-lbl">Vent Issues</div></div>
      </div>
      <div class="bike-row">
        <div class="bike-chip"><div class="bike-val"><?=number_format($bikeStats['total_km'],0)?></div><div class="bike-lbl">Total KM</div></div>
        <div class="bike-chip"><div class="bike-val"><?=number_format($bikeStats['total_fuel'],1)?> L</div><div class="bike-lbl">Fuel Used</div></div>
        <div class="bike-chip"><div class="bike-val"><?=($bikeStats['total_km']>0&&$bikeStats['total_fuel']>0)?number_format($bikeStats['total_km']/$bikeStats['total_fuel'],1):'—'?></div><div class="bike-lbl">km/Litre</div></div>
      </div>
    </div>
  </div>

  <!-- Risk + Officer depth -->
  <div class="two-col">
    <div class="card">
      <div class="card-head"><h3>🚨 At-Risk Growers</h3><span class="badge" style="color:var(--red)"><?=count($riskGrowers)?> flagged</span></div>
      <?php if(empty($riskGrowers)): ?>
      <div class="empty" style="color:var(--green)">✅ No at-risk growers detected</div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="risk-table">
        <thead><tr><th>Grower</th><th>Officer</th><th>Drought</th><th>Pest</th><th>Disease</th></tr></thead>
        <tbody>
        <?php foreach($riskGrowers as $rg):
          $dc = in_array($rg['drought'],['High','Severe'])?'rp-hi':(in_array($rg['drought'],['Medium','Moderate'])?'rp-md':'rp-ok');
          $pc = !in_array($rg['pest'],['—','None',''])?'rp-md':'rp-ok';
          $dsc = !in_array($rg['disease'],['—','None',''])?'rp-hi':'rp-ok';
        ?>
        <tr>
          <td><b><?=htmlspecialchars($rg['name'].' '.$rg['surname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$rg['grower_num']?></span></td>
          <td style="color:var(--muted)"><?=htmlspecialchars($rg['officer'])?></td>
          <td><span class="rp <?=$dc?>"><?=$rg['drought']?></span></td>
          <td><span class="rp <?=$pc?>"><?=mb_substr($rg['pest'],0,14)?></span></td>
          <td><span class="rp <?=$dsc?>"><?=mb_substr($rg['disease'],0,14)?></span></td>
        </tr>
        <?php endforeach?>
        </tbody>
      </table>
      </div>
      <?php endif?>
    </div>

    <div class="card">
      <div class="card-head"><h3>👮 Officer Field Depth</h3><span class="badge">Inspection counts</span></div>
      <?php if(empty($officerActivity)): ?>
      <div class="empty">No activity data</div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="act-table">
        <thead><tr><th>Officer</th><th>🌱</th><th>🌿</th><th>📈</th><th>🌸</th><th>🔥</th><th>🏷</th></tr></thead>
        <tbody>
        <?php
        function dotC($v){ return $v==0?'dot-0':($v<=3?'dot-low':($v<=8?'dot-med':'dot-high')); }
        foreach($officerActivity as $oa):
        ?>
        <tr>
          <td><?=htmlspecialchars($oa['officer'])?></td>
          <?php foreach(['seedbed','transplant','crop_meas','flowering','curing','grading'] as $f): ?>
          <td><span class="act-dot <?=dotC($oa[$f])?>"><?=$oa[$f]?></span></td>
          <?php endforeach?>
        </tr>
        <?php endforeach?>
        </tbody>
      </table>
      </div>
      <div style="padding:6px 14px;font-size:9px;color:var(--muted);display:flex;gap:10px;border-top:1px solid var(--border)">
        <span>● Grey=0</span><span style="color:var(--green)">● Green=1-3</span><span style="color:#a3e635">● Lime=4-8</span><span style="color:var(--amber)">● Gold=9+</span>
      </div>
      <?php endif?>
    </div>
  </div>

</div>

<script>
const actData = <?=json_encode(array_values($weeklyActivity))?>;
const actLabels = actData.map(w=>{const d=new Date(w.week_start);return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'});});
new Chart(document.getElementById('activityChart'),{
  type:'bar',
  data:{
    labels:actLabels.length?actLabels:['No data'],
    datasets:[{label:'Activities',data:actData.map(w=>w.cnt),backgroundColor:'rgba(61,220,104,.25)',borderColor:'#3ddc68',borderWidth:1,borderRadius:3}]
  },
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f170f',borderColor:'#1a2a1a',borderWidth:1,titleColor:'#c8e6c9',bodyColor:'#4a6b4a',titleFont:{family:'Space Mono',size:10},bodyFont:{family:'Space Mono',size:9}}},
    scales:{x:{ticks:{color:'#4a6b4a',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}},y:{ticks:{color:'#3ddc68',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}}}
  }
});
</script>
</body>
</html>
