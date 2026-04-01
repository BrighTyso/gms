<?php


ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// Quick summary stats for hub
$stats = [];

// Total visits this week
$r = $conn->query("SELECT COUNT(*) AS cnt FROM visits WHERE created_at >= NOW()-INTERVAL 7 DAY");
if($r && $row=$r->fetch_assoc()){$stats['visits_7d']=(int)$row['cnt'];$r->free();}

// Active officers (pinged in last 7 days)
$r = $conn->query("SELECT COUNT(DISTINCT officer_id) AS cnt FROM device_locations WHERE created_at >= NOW()-INTERVAL 7 DAY");
if($r && $row=$r->fetch_assoc()){$stats['active_officers']=(int)$row['cnt'];$r->free();}

// Overdue growers (no visit 14+ days)
$r = $conn->query("SELECT COUNT(*) AS cnt FROM growers g WHERE NOT EXISTS(SELECT 1 FROM visits WHERE growerid=g.id AND created_at>=NOW()-INTERVAL 14 DAY)");
if($r && $row=$r->fetch_assoc()){$stats['overdue']=(int)$row['cnt'];$r->free();}

// Growers with geo gap
$r = $conn->query("SELECT COUNT(DISTINCT ge.growerid) AS cnt FROM grower_geofence_entry_point ge WHERE ge.created_at>=NOW()-INTERVAL 30 DAY AND NOT EXISTS(SELECT 1 FROM visits v WHERE v.growerid=ge.growerid AND v.userid=ge.userid AND DATE(v.created_at)=DATE(ge.created_at))");
if($r && $row=$r->fetch_assoc()){$stats['geo_gaps']=(int)$row['cnt'];$r->free();}

// Loans stats
$r = $conn->query("SELECT COUNT(*) AS cnt FROM loans l JOIN seasons s ON s.id=l.seasonid WHERE s.active=1");
if($r && $row=$r->fetch_assoc()){$stats['total_loans']=(int)$row['cnt'];$r->free();}
$r = $conn->query("SELECT COUNT(*) AS cnt FROM loans l JOIN seasons s ON s.id=l.seasonid WHERE s.active=1 AND l.verified=0");
if($r && $row=$r->fetch_assoc()){$stats['unverified_loans']=(int)$row['cnt'];$r->free();}

// Current season
$seasonId=0; $seasonName='—';
$r=$conn->query("SELECT id,name FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$seasonName=$row['name'];$r->free();}

// Duplicate count for hub badge
$dupCount=0;
$rd=$conn->query("SELECT COUNT(*) AS cnt FROM (SELECT LOWER(TRIM(CONCAT(name,' ',surname))) AS n FROM growers GROUP BY n HAVING COUNT(*)>1) t");
if($rd && $row=$rd->fetch_assoc()){$dupCount=(int)$row['cnt'];$rd->free();}

// Pending fuel approvals
$pendingFuel=0;
$r=$conn->query("SELECT COUNT(*) AS cnt FROM fuel_requests WHERE status='PENDING'");
if($r&&$row=$r->fetch_assoc()){$pendingFuel=(int)$row['cnt'];$r->free();}

// Visit gap count — growers with loans but no visit this season
$visitGapCount=0;
$r=$conn->query("SELECT COUNT(DISTINCT l.growerid) AS cnt FROM loans l JOIN seasons s ON s.id=l.seasonid AND s.active=1 LEFT JOIN visits v ON v.growerid=l.growerid AND v.seasonid=s.id WHERE v.id IS NULL");
if($r&&$row=$r->fetch_assoc()){$visitGapCount=(int)$row['cnt'];$r->free();}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GMS · Reports Hub</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0a0f0a;--surface:#111a11;--border:#1f2e1f;--green:#3ddc68;--green-dim:#1a5e30;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b47eff;--text:#c8e6c9;--muted:#4a6b4a;--radius:6px}
  html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}
  header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
  .logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
  .logo span{color:var(--muted)}
  .back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px}
  .back:hover{color:var(--green);border-color:var(--green)}
  .content{padding:24px;max-width:1200px;margin:0 auto}

  /* Quick stats */
  .stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:32px}
  .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;text-align:center}
  .stat-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;margin:6px 0}
  .stat-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
  .stat-sub{font-size:10px;color:var(--muted);margin-top:4px}

  /* Report categories */
  .category{margin-bottom:28px}
  .cat-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border)}
  .report-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}

  .report-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;text-decoration:none;color:var(--text);transition:all .2s;display:block}
  .report-card:hover{border-color:var(--green);background:rgba(61,220,104,.05);transform:translateY(-1px)}
  .rc-icon{font-size:24px;margin-bottom:8px}
  .rc-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:var(--green);margin-bottom:5px}
  .rc-desc{font-size:10px;color:var(--muted);line-height:1.5}
  .rc-badge{display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;margin-top:8px;border:1px solid}
  .badge-alert{background:#200000;color:var(--red);border-color:#400000}
  .badge-ok{background:#0d200d;color:var(--green);border-color:var(--green-dim)}
  .badge-info{background:#001020;color:var(--blue);border-color:#003050}
  .badge-warn{background:#1e1500;color:var(--amber);border-color:#3a2800}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Reports</div>
  <a href="device_tracker.php"   class="back">🗺 Tracker</a>
  <a href="officer_coverage.php" class="back">📊 Coverage</a>
  <a href="officer_league.php"   class="back">🏆 League</a>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">Season: <?=htmlspecialchars($seasonName)?> · <?=date('d M Y H:i')?></div>
</header>

<div class="content">
  <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-bottom:20px">
    📋 Reports Hub
    <div style="font-size:11px;font-weight:400;color:var(--muted);margin-top:4px">All GMS reporting and analytics in one place</div>
  </div>

  <!-- Quick stats -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-label">Visits This Week</div>
      <div class="stat-val" style="color:var(--green)"><?=$stats['visits_7d']??0?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Active Officers (7d)</div>
      <div class="stat-val" style="color:var(--blue)"><?=$stats['active_officers']??0?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Overdue Growers (14d)</div>
      <div class="stat-val" style="color:var(--red)"><?=$stats['overdue']??0?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Geo Gaps (30d)</div>
      <div class="stat-val" style="color:var(--amber)"><?=$stats['geo_gaps']??0?></div>
      <div class="stat-sub">near but not logged</div>
    </div>
  </div>

  <!-- Field Operations -->
  <div class="category">
    <div class="cat-title">🌿 Field Operations</div>
    <div class="report-grid">
      <a href="visit_frequency.php" class="report-card">
        <div class="rc-icon">📅</div>
        <div class="rc-title">Visit Frequency</div>
        <div class="rc-desc">How often each grower is being visited vs target frequency. Shows over-visited and neglected growers.</div>
        <span class="rc-badge badge-info">Grower level</span>
      </a>
      <a href="visit_backlog.php" class="report-card">
        <div class="rc-icon">⚠️</div>
        <div class="rc-title">Visit Backlog</div>
        <div class="rc-desc">Overdue growers, never visited, consecutive missed visits. Sorted by assigned officer.</div>
        <?php if(($stats['overdue']??0)>0): ?><span class="rc-badge badge-alert"><?=$stats['overdue']?> overdue</span><?php endif?>
      </a>
      <a href="visit_schedule.php" class="report-card">
        <div class="rc-icon">🗓</div>
        <div class="rc-title">Visit Schedule</div>
        <div class="rc-desc">Weekly grid showing all visits logged and geofence entries per officer per day.</div>
        <span class="rc-badge badge-info">Weekly view</span>
      </a>
      <a href="geo_vs_visit_report.php" class="report-card">
        <div class="rc-icon">📍</div>
        <div class="rc-title">Geo vs Visit Gap</div>
        <div class="rc-desc">Officer was physically near grower but no visit was logged. Identifies missed conversion opportunities.</div>
        <?php if(($stats['geo_gaps']??0)>0): ?><span class="rc-badge badge-warn"><?=$stats['geo_gaps']?> gaps found</span><?php endif?>
      </a>
      <a href="dead_zones.php" class="report-card">
        <div class="rc-icon">🚫</div>
        <div class="rc-title">Dead Zones</div>
        <div class="rc-desc">Map of growers with no officer activity nearby. Search by grower or officer to find coverage gaps.</div>
        <span class="rc-badge badge-alert">Map view</span>
      </a>
      <a href="route_planner.php" class="report-card">
        <div class="rc-icon">🗺</div>
        <div class="rc-title">Route Planner</div>
        <div class="rc-desc">Optimized daily route for each officer. Shows ETA, visit history, pass-by counts per stop.</div>
        <span class="rc-badge badge-ok">Daily planning</span>
      </a>
    </div>
  </div>

  <!-- Officer Performance -->
  <div class="category">
    <div class="cat-title">👮 Officer Performance</div>
    <div class="report-grid">
      <a href="officer_league.php" class="report-card">
        <div class="rc-icon">🏆</div>
        <div class="rc-title">League Table</div>
        <div class="rc-desc">Ranked officer performance. Composite score from visits, conversion, distance, active days. Gold/Silver/Bronze tiers.</div>
        <span class="rc-badge badge-info">Season ranking</span>
      </a>
      <a href="officer_coverage.php" class="report-card">
        <div class="rc-icon">📊</div>
        <div class="rc-title">Coverage Dashboard</div>
        <div class="rc-desc">Heatmap, trails, working hours, distance covered, growers passed near, conversion rate per officer.</div>
        <span class="rc-badge badge-info">Map + stats</span>
      </a>
      <a href="officer_report.php" class="report-card">
        <div class="rc-icon">📋</div>
        <div class="rc-title">Officer Report</div>
        <div class="rc-desc">Per-officer detailed report: activity calendar, daily breakdown, growers passed, visit conversion.</div>
        <span class="rc-badge badge-info">Individual</span>
      </a>
      <a href="officer_kpi.php" class="report-card">
        <div class="rc-icon">🎯</div>
        <div class="rc-title">KPI Scorecard</div>
        <div class="rc-desc">Monthly KPI scorecard per officer: visits target vs actual, recovery target vs actual, distance.</div>
        <span class="rc-badge badge-info">Monthly</span>
      </a>
      <a href="punctuality_report.php" class="report-card">
        <div class="rc-icon">⏰</div>
        <div class="rc-title">Punctuality Report</div>
        <div class="rc-desc">Daily start times per officer vs expected 6AM. Late starters, early finishers, off-hours activity.</div>
        <span class="rc-badge badge-warn">Time tracking</span>
      </a>
      <a href="fuel_dashboard.php" class="report-card" style="border-color:#3a2800;">
        <div class="rc-icon">⛽</div>
        <div class="rc-title" style="color:var(--amber);">Fuel &amp; Distance</div>
        <div class="rc-desc">Weekly fuel allocation from distance data. Route vs actual, cost per visit, compliance score, planned vs actual km and anomaly detection.</div>
        <?php if($pendingFuel>0): ?><span class="rc-badge badge-warn"><?=$pendingFuel?> pending approval</span><?php else: ?><span class="rc-badge badge-info">Mixed fleet</span><?php endif?>
      </a>
    </div>
  </div>

  <!-- Season & Coverage -->
  <div class="category">
    <div class="cat-title">🌱 Season & Coverage</div>
    <div class="report-grid">
      <a href="season_progress.php" class="report-card">
        <div class="rc-icon">📈</div>
        <div class="rc-title">Season Progress</div>
        <div class="rc-desc">Season health score, coverage %, on-track growers, weekly visit chart, officer progress table, completion forecast.</div>
        <span class="rc-badge badge-ok">Season <?=htmlspecialchars($seasonName)?></span>
      </a>
      <a href="executive_summary.php" class="report-card">
        <div class="rc-icon">📄</div>
        <div class="rc-title">Executive Summary</div>
        <div class="rc-desc">One-page printable management summary: KPIs, top risks, top performers, coverage bar. Print to PDF.</div>
        <span class="rc-badge badge-info">Printable</span>
      </a>
      <a href="cluster_performance.php" class="report-card">
        <div class="rc-icon">🗺</div>
        <div class="rc-title">Area / Cluster Performance</div>
        <div class="rc-desc">Groups growers into geographic clusters. Shows coverage % per area. Map view highlights neglected zones.</div>
        <span class="rc-badge badge-info">Map view</span>
      </a>
      <a href="season_dashboard.php" class="report-card">
        <div class="rc-icon">🌿</div>
        <div class="rc-title">Season Dashboard</div>
        <div class="rc-desc">Full crop lifecycle: seedbed, land prep, transplanting, growth, flowering, harvest, curing and grading pipeline. Risk growers, officer depth, barn status, bike stats.</div>
        <span class="rc-badge badge-ok">Season <?=htmlspecialchars($seasonName)?></span>
      </a>
      <a href="grower_weather.php" class="report-card">
        <div class="rc-icon">🌦</div>
        <div class="rc-title">Grower Weather</div>
        <div class="rc-desc">Today's weather and season accumulated rainfall per grower. 30-day mini rainfall charts.</div>
        <span class="rc-badge badge-info">Weather data</span>
      </a>
    </div>
  </div>

  <!-- Loans -->
  <div class="category">
    <div class="cat-title">💰 Loans &amp; Inputs</div>
    <div class="report-grid">
      <a href="loans_dashboard.php" class="report-card">
        <div class="rc-icon">📊</div>
        <div class="rc-title">Loans Dashboard</div>
        <div class="rc-desc">Season loan overview: total value, verification pipeline, loans by product, officer activity, top growers, unverified queue.</div>
        <?php if(($stats['unverified_loans']??0)>0): ?><span class="rc-badge badge-warn"><?=$stats['unverified_loans']?> unverified</span><?php else: ?><span class="rc-badge badge-ok">All verified</span><?php endif?>
      </a>
      <a href="loans_report.php" class="report-card">
        <div class="rc-icon">📋</div>
        <div class="rc-title">Loans Report</div>
        <div class="rc-desc">Full filterable loans report grouped by officer, product, grower, or date. Drill into any officer's loans. Export to Excel.</div>
        <span class="rc-badge badge-info"><?=$stats['total_loans']??0?> loans this season</span>
      </a>
      <a href="grower_payments.php" class="report-card">
        <div class="rc-icon">💳</div>
        <div class="rc-title">Grower Payments</div>
        <div class="rc-desc">Loan repayment tracking and bonus/incentive payments. View history by grower and export payment reports.</div>
        <span class="rc-badge badge-info">Multi-method</span>
      </a>
      <a href="bale_tracking.php" class="report-card">
        <div class="rc-icon">📦</div>
        <div class="rc-title">Bale Tracking</div>
        <div class="rc-desc">Track bales per grower from questionnaire answers. View totals by question type, sync status, GPS and device timestamps.</div>
        <span class="rc-badge badge-ok">Questionnaire data</span>
      </a>
      <a href="season_rollover.php" class="report-card">
        <div class="rc-icon">🔄</div>
        <div class="rc-title">Season Rollover</div>
        <div class="rc-desc">Rollover records for the active season. View amounts carried over per grower with GPS and sync tracking.</div>
        <span class="rc-badge badge-warn">End-of-season</span>
      </a>
    </div>
  </div>

  <!-- System & Data Quality -->
  <div class="category">
    <div class="cat-title">🔧 System &amp; Data Quality</div>
    <div class="report-grid">
      <a href="device_sync_status.php" class="report-card">
        <div class="rc-icon">📱</div>
        <div class="rc-title">Device Sync Status</div>
        <div class="rc-desc">Real-time officer device health: last ping, battery levels, internet vs SMS pings, offline detection.</div>
        <span class="rc-badge badge-info">Live · auto-refresh</span>
      </a>
      <a href="duplicate_detection.php" class="report-card">
        <div class="rc-icon">👥</div>
        <div class="rc-title">Duplicate Detection</div>
        <div class="rc-desc">Finds duplicate grower names, duplicate grower numbers, double-logged visits, identical GPS coordinates.</div>
        <?php if($dupCount>0): ?><span class="rc-badge badge-alert"><?=$dupCount?> duplicate names</span><?php else: ?><span class="rc-badge badge-ok">No duplicates</span><?php endif?>
      </a>
      <a href="data_completeness.php" class="report-card">
        <div class="rc-icon">✅</div>
        <div class="rc-title">Data Completeness</div>
        <div class="rc-desc">Scores each grower profile 0–100. Shows missing phone, GPS, address, ID number, barn, farm, seedbed data.</div>
        <span class="rc-badge badge-warn">Profile audit</span>
      </a>
      <a href="data_quality.php" class="report-card">
        <div class="rc-icon">🔍</div>
        <div class="rc-title">GPS Data Quality</div>
        <div class="rc-desc">Zero coordinates, locations outside Zimbabwe bounding box, growers with no GPS recorded.</div>
        <span class="rc-badge badge-warn">GPS audit</span>
      </a>
    </div>
  </div>

  <!-- Field Intelligence -->
  <div class="category">
    <div class="cat-title">🧠 Field Intelligence</div>
    <div class="report-grid">
      <a href="grower_risk.php" class="report-card" style="border-color:#400000;">
        <div class="rc-icon">🎯</div>
        <div class="rc-title" style="color:var(--red);">Grower Risk Scorecard</div>
        <div class="rc-desc">Composite risk score per grower from visits, unverified loans, transplanting survival, rollover and working capital. Red/Amber/Green ranking.</div>
        <span class="rc-badge badge-alert">Priority 1</span>
      </a>
      <a href="loan_exposure.php" class="report-card" style="border-color:#3a2800;">
        <div class="rc-icon">💳</div>
        <div class="rc-title" style="color:var(--amber);">Loan Exposure vs Working Capital</div>
        <div class="rc-desc">Total debt (loans + charges + rollover) vs working capital per grower. Net exposure, gross exposure and capacity ratio. Flags over-leveraged growers.</div>
        <span class="rc-badge badge-warn">Revenue protection</span>
      </a>
      <a href="workload_balance.php" class="report-card">
        <div class="rc-icon">⚖️</div>
        <div class="rc-title">Officer Workload Balance</div>
        <div class="rc-desc">Growers assigned vs visited, km per visit efficiency, visits per day, loan portfolio value per officer. Flags overloaded and underutilised officers.</div>
        <span class="rc-badge badge-info">Operational</span>
      </a>
      <a href="crop_health.php" class="report-card">
        <div class="rc-icon">🌱</div>
        <div class="rc-title">Transplanting Health Dashboard</div>
        <div class="rc-desc">Survival rates, vigor distribution, pest and disease issues per grower and area. Worst survival areas ranked. Cross-referenced with last visit date.</div>
        <span class="rc-badge badge-info">Crop risk</span>
      </a>
      <a href="visit_gaps.php" class="report-card" style="border-color:#400000;">
        <div class="rc-icon">🔍</div>
        <div class="rc-title">Visit Gap Analysis</div>
        <div class="rc-desc">Growers who received loans but have not been visited since. Never visited, critical gaps (60d+), unverified loan exposure in gap growers.</div>
        <?php if($visitGapCount>0): ?><span class="rc-badge badge-alert"><?=$visitGapCount?> unvisited</span><?php else: ?><span class="rc-badge badge-ok">All clear</span><?php endif?>
      </a>
      <a href="questionnaire_pivot.php" class="report-card">
        <div class="rc-icon">📋</div>
        <div class="rc-title">Questionnaire Answers</div>
        <div class="rc-desc">Dynamic pivot table — all questions as columns, one row per grower. Colour-coded by answer type, completion % per grower, CSV export. Fully dynamic from live data.</div>
        <span class="rc-badge badge-info">Dynamic pivot</span>
      </a>
    </div>
  </div>

  <!-- Business Intelligence -->
  <div class="category">
    <div class="cat-title">📊 Business Intelligence</div>
    <div class="report-grid">
      <a href="bi_overview.php" class="report-card" style="border-color:#1a3a5e;">
        <div class="rc-icon">🧠</div>
        <div class="rc-title" style="color:var(--blue);">BI Overview</div>
        <div class="rc-desc">Cross-domain executive dashboard — loan recovery, field coverage, grade rates and season-on-season trends all in one view.</div>
        <span class="rc-badge badge-info">All domains</span>
      </a>
      <a href="bi_grower_performance.php" class="report-card" style="border-color:#1a3a5e;">
        <div class="rc-icon">🌾</div>
        <div class="rc-title" style="color:var(--blue);">Grower Performance BI</div>
        <div class="rc-desc">Cluster coverage trends, season-on-season grower counts, top engaged growers and at-risk grower drill-down.</div>
        <span class="rc-badge badge-info">Cluster level</span>
      </a>
      <a href="bi_field_officer.php" class="report-card" style="border-color:#1a3a5e;">
        <div class="rc-icon">📍</div>
        <div class="rc-title" style="color:var(--blue);">Field Officer BI</div>
        <div class="rc-desc">Multi-season coverage trends, officer league table, visit frequency distribution and unvisited grower list.</div>
        <span class="rc-badge badge-info">Officer level</span>
      </a>
      <a href="bi_loans.php" class="report-card" style="border-color:#1a3a5e;">
        <div class="rc-icon">💰</div>
        <div class="rc-title" style="color:var(--blue);">Loans &amp; Repayments BI</div>
        <div class="rc-desc">Recovery rate trends, disbursed vs recovered by season, product breakdown and overdue grower aging table.</div>
        <?php if(($stats['unverified_loans']??0)>0): ?><span class="rc-badge badge-warn"><?=$stats['unverified_loans']?> unverified</span><?php else: ?><span class="rc-badge badge-info">Season trends</span><?php endif?>
      </a>
      <a href="bi_crop_harvest.php" class="report-card" style="border-color:#1a3a5e;">
        <div class="rc-icon">🏆</div>
        <div class="rc-title" style="color:var(--blue);">Crop &amp; Harvest BI</div>
        <div class="rc-desc">Grade distribution A1–D, curing pass rate, yield analytics per cluster and season-on-season grade trend lines.</div>
        <span class="rc-badge badge-info">Grade analysis</span>
      </a>
    </div>
  </div>

</div>
</body>
</html>
