<?php
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
mysqli_report(MYSQLI_REPORT_OFF);

/* ================================================================
   ACTIVE SEASON
   ================================================================ */
$seasonId = 0; $seasonName = '—';
$r = $conn->query("SELECT id, name FROM seasons WHERE active=1 LIMIT 1");
if($r && $row = $r->fetch_assoc()){ $seasonId=(int)$row['id']; $seasonName=$row['name']; $r->free(); }

/* ================================================================
   KPIs
   ================================================================ */
$kpis = ['total_bales'=>0,'total_records'=>0,'growers_count'=>0,'synced'=>0,'unsynced'=>0,'avg_bales'=>0.0];

$rk = $conn->query("
  SELECT COUNT(*) AS cnt,
         COALESCE(SUM(bales),0) AS tot_bales,
         COUNT(DISTINCT growerid) AS growers,
         SUM(sync=1) AS synced,
         SUM(sync=0) AS unsynced
  FROM questionnaires_bales_answers_by_grower
  WHERE seasonid = $seasonId
");
if($rk && $rk !== false && $row = $rk->fetch_assoc()){
  $kpis['total_records'] = (int)$row['cnt'];
  $kpis['total_bales']   = (int)$row['tot_bales'];
  $kpis['growers_count'] = (int)$row['growers'];
  $kpis['synced']        = (int)$row['synced'];
  $kpis['unsynced']      = (int)$row['unsynced'];
  $kpis['avg_bales']     = $row['cnt'] > 0 ? round($row['tot_bales'] / $row['cnt'], 1) : 0;
  $rk->free();
}

/* ================================================================
   TOP QUESTIONS breakdown (sidebar)
   ================================================================ */
$questions = [];
$rq = $conn->query("
  SELECT question, COUNT(*) AS cnt, COALESCE(SUM(bales),0) AS tot
  FROM questionnaires_bales_answers_by_grower
  WHERE seasonid = $seasonId
  GROUP BY question ORDER BY tot DESC LIMIT 6
");
if($rq && $rq !== false){ while($row=$rq->fetch_assoc()){ $questions[]=$row; } $rq->free(); }

/* ================================================================
   FILTERS
   ================================================================ */
$fSearch   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$fSync     = isset($_GET['sync'])     ? (int)$_GET['sync']      : -1;
$fDateFrom = isset($_GET['from'])     ? trim($_GET['from'])      : '';
$fDateTo   = isset($_GET['to'])       ? trim($_GET['to'])        : '';

$where = ["b.seasonid = $seasonId"];
if($fSync >= 0)    $where[] = "b.sync = $fSync";
if($fDateFrom != ''){ $fd = $conn->real_escape_string($fDateFrom); $where[] = "DATE(b.datetime_sync) >= '$fd'"; }
if($fDateTo   != ''){ $ft = $conn->real_escape_string($fDateTo);   $where[] = "DATE(b.datetime_sync) <= '$ft'"; }
if($fSearch   != ''){
  $fs = $conn->real_escape_string($fSearch);
  $where[] = "(b.question LIKE '%$fs%' OR g.name LIKE '%$fs%' OR g.surname LIKE '%$fs%' OR g.grower_num LIKE '%$fs%')";
}
$whereStr = implode(' AND ', $where);

/* ================================================================
   MAIN QUERY — join growers for name + grower_num
   ================================================================ */
$records = [];
$rr = $conn->query("
  SELECT b.id, b.userid, b.seasonid, b.growerid,
         b.question, b.bales, b.latitude, b.longitude,
         b.question_created_at, b.created_at, b.datetimes,
         b.datetime_sync, b.sync,
         g.name, g.surname, g.grower_num
  FROM questionnaires_bales_answers_by_grower b
  LEFT JOIN growers g ON g.id = b.growerid
  WHERE $whereStr
  ORDER BY b.datetime_sync DESC
  LIMIT 500
");
if($rr && $rr !== false){ while($row=$rr->fetch_assoc()){ $records[]=$row; } $rr->free(); }

$conn->close();

/* helpers */
$totBales = 0;
foreach($records as $_r){ $totBales += (int)$_r['bales']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>GMS · Bale Tracking</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0f0a;--surface:#111a11;--surface2:#162016;
  --border:#1f2e1f;--border2:#2a3d2a;
  --green:#3ddc68;--green-dim:#1a5e30;
  --amber:#f5a623;--amber-dim:#3a2800;
  --red:#e84040;--red-dim:#200000;
  --blue:#4a9eff;--blue-dim:#001020;
  --purple:#b47eff;--purple-dim:#1a0a2e;
  --text:#c8e6c9;--text2:#7aaa7a;--muted:#4a6b4a;
  --radius:6px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}

/* HEADER */
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
.logo span{color:var(--muted)}
.back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px;transition:all .2s}
.back:hover{color:var(--green);border-color:var(--green)}
.header-right{margin-left:auto;display:flex;align-items:center;gap:8px;font-size:10px;color:var(--muted)}

/* LAYOUT */
.page-wrap{display:flex;min-height:calc(100vh - 56px)}

/* SIDEBAR */
.sidebar{width:240px;flex-shrink:0;border-right:1px solid var(--border);background:var(--surface);padding:20px 0;position:sticky;top:56px;height:calc(100vh - 56px);overflow-y:auto}
.sb-section{padding:0 16px;margin-bottom:24px}
.sb-title{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border)}
.q-row{margin-bottom:9px}
.q-hd{display:flex;justify-content:space-between;margin-bottom:3px}
.q-name{font-size:10px;color:var(--text2);max-width:148px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.q-val{font-size:9px;color:var(--muted)}
.q-bar{height:3px;background:var(--border2);border-radius:2px;overflow:hidden}
.q-fill{height:100%;background:var(--green);border-radius:2px;transition:width .55s ease}
.sync-item{display:flex;align-items:center;gap:8px;font-size:10px;color:var(--text2);margin-bottom:7px}
.sync-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.qf-btn{display:flex;align-items:center;gap:6px;font-size:10px;color:var(--muted);padding:5px 8px;border-radius:4px;cursor:pointer;border:1px solid transparent;transition:all .15s;margin-bottom:4px;background:none;width:100%;text-align:left;font-family:'Space Mono',monospace}
.qf-btn:hover{color:var(--green);border-color:var(--border);background:rgba(61,220,104,.04)}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* STAT ROW */
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1px;background:var(--border);border-bottom:1px solid var(--border)}
.stat-card{background:var(--surface);padding:16px 20px;text-align:center;transition:background .15s;animation:fadeUp .3s ease both}
.stat-card:hover{background:var(--surface2)}
.stat-card:nth-child(1){animation-delay:.04s}.stat-card:nth-child(2){animation-delay:.08s}
.stat-card:nth-child(3){animation-delay:.12s}.stat-card:nth-child(4){animation-delay:.16s}
.stat-card:nth-child(5){animation-delay:.20s}.stat-card:nth-child(6){animation-delay:.24s}
.stat-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px}
.stat-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:3px}
.stat-sub{font-size:9px;color:var(--muted)}

/* FILTER BAR */
.filter-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 20px;background:var(--surface);border-bottom:1px solid var(--border)}
.f-input,.f-select{background:var(--bg);border:1px solid var(--border);color:var(--text2);padding:5px 9px;border-radius:4px;font-size:10px;font-family:'Space Mono',monospace;outline:none}
.f-input:focus,.f-select:focus{border-color:var(--green);color:var(--text)}
.f-input{min-width:200px}
.tab-group{display:flex;border:1px solid var(--border);border-radius:4px;overflow:hidden}
.tab{padding:5px 11px;font-size:10px;font-family:'Space Mono',monospace;cursor:pointer;color:var(--muted);background:var(--bg);border:none;transition:all .12s}
.tab:hover{color:var(--text2)}.tab.active{background:var(--surface2);color:var(--green)}
.f-count{margin-left:auto;font-size:10px;color:var(--muted)}

/* STRIP */
.strip{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:7px 20px;background:var(--bg);border-bottom:1px solid var(--border);font-size:10px;color:var(--muted)}
.strip strong{color:var(--text2)}
.strip-sep{color:var(--border2)}

/* TABLE */
.table-wrap{flex:1;overflow:auto}
table{width:100%;border-collapse:collapse}
thead tr{background:var(--surface);border-bottom:1px solid var(--border2);position:sticky;top:0;z-index:10}
thead th{padding:9px 14px;text-align:left;font-size:9px;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;font-weight:700;white-space:nowrap;cursor:pointer;user-select:none;transition:color .15s}
thead th:hover{color:var(--text2)}
thead th.sorted{color:var(--green)}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:var(--surface)}
tbody td{padding:10px 14px;font-size:11px;color:var(--text2)}
.td-id{font-size:10px;color:var(--muted)}
.td-name{color:var(--text);font-weight:700}
.td-sub{font-size:9px;color:var(--muted);margin-top:1px}
.td-mono{font-size:10px;color:var(--muted)}
.td-bales{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--green)}
.td-q{font-size:11px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.check-cell{width:32px;padding-left:14px!important}
.check-cell input[type=checkbox]{width:13px;height:13px;accent-color:var(--green);cursor:pointer}

/* BADGES */
.badge{display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;border:1px solid}
.badge-ok    {background:#0d200d;color:var(--green);border-color:var(--green-dim)}
.badge-warn  {background:var(--amber-dim);color:var(--amber);border-color:#5a3800}
.badge-info  {background:var(--blue-dim);color:var(--blue);border-color:#003050}

/* DRAWER */
.drawer-overlay{position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);display:none}
.drawer-overlay.open{display:block}
.drawer{position:fixed;top:0;right:-460px;bottom:0;width:440px;z-index:301;background:var(--surface);border-left:1px solid var(--border2);transition:right .26s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
.drawer.open{right:0}
.drawer-head{padding:16px 20px 12px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between}
.drawer-id{font-size:9px;color:var(--muted);margin-bottom:4px;letter-spacing:.4px}
.drawer-name{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--text);line-height:1.1}
.drawer-close{background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;transition:color .15s}
.drawer-close:hover{color:var(--text)}
.drawer-body{flex:1;overflow-y:auto;padding:16px 20px}
.drawer-bales{font-family:'Syne',sans-serif;font-size:48px;font-weight:800;color:var(--green);line-height:1;padding:12px 0 2px}
.drawer-bales-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px}
.d-section{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:14px 0 8px;padding-bottom:5px;border-bottom:1px solid var(--border)}
.d-row{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--border);font-size:11px}
.d-row:last-child{border-bottom:none}
.d-label{color:var(--muted);font-size:10px;flex-shrink:0;margin-right:12px;padding-top:1px}
.d-val{color:var(--text);text-align:right;word-break:break-all;font-weight:700}
.drawer-foot{padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px}

/* BULK BAR */
.bulk-bar{position:fixed;bottom:0;left:0;right:0;z-index:200;background:var(--surface2);border-top:1px solid var(--border2);padding:10px 20px;display:flex;align-items:center;gap:10px;transform:translateY(100%);transition:transform .2s ease}
.bulk-bar.show{transform:translateY(0)}
.bulk-n{font-size:11px;color:var(--green);margin-right:4px}
.bulk-sep{flex:1}

/* MODAL */
.modal-bg{position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.72);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);padding:22px;width:420px;max-width:92vw;position:relative;animation:slideUp .18s ease both}
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--text);margin-bottom:3px}
.modal-sub{font-size:10px;color:var(--muted);margin-bottom:16px}
.modal-close{position:absolute;top:12px;right:14px;background:none;border:none;color:var(--muted);font-size:16px;cursor:pointer}
.modal-close:hover{color:var(--text)}
.m-label{font-size:10px;color:var(--muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.m-select,.m-input{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);padding:7px 9px;border-radius:4px;font-size:11px;font-family:'Space Mono',monospace;outline:none;margin-bottom:12px}
.m-select:focus,.m-input:focus{border-color:var(--green)}
.m-check-group{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.m-check{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--text2);cursor:pointer}
.m-check input{accent-color:var(--green);width:13px;height:13px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:4px;font-size:11px;font-family:'Space Mono',monospace;cursor:pointer;border:none;transition:all .15s;text-decoration:none;white-space:nowrap}
.btn-green{background:var(--green);color:#0a0f0a;font-weight:700}.btn-green:hover{background:#5af080}
.btn-outline{background:transparent;color:var(--muted);border:1px solid var(--border)}.btn-outline:hover{color:var(--green);border-color:var(--green)}
.btn-sm{padding:4px 9px;font-size:10px}

/* UTILS */
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--muted)}
.empty-cell{text-align:center!important;padding:48px!important;color:var(--muted)!important;font-size:11px!important}
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="logo">GMS<span>/</span>Bales</div>
  <a href="reports_hub.php" class="back">← Hub</a>
  <a href="grower_payments.php" class="back">💳 Payments</a>
  <a href="season_rollover.php" class="back">🔄 Rollover</a>
  <div class="header-right">
    Season: <?= htmlspecialchars($seasonName) ?> · <?= date('d M Y H:i') ?>
    &nbsp;
    <button class="btn btn-outline btn-sm" onclick="document.getElementById('exportModal').classList.add('open')">↓ Export</button>
  </div>
</header>

<div class="page-wrap">

  <!-- SIDEBAR -->
  <aside class="sidebar">

    <div class="sb-section">
      <div class="sb-title">Bales by Question</div>
      <?php
        $maxQ = count($questions) ? max(array_column($questions,'tot')) : 1;
        $colors = ['var(--green)','var(--blue)','var(--amber)','var(--purple)','var(--red)','var(--text2)'];
        foreach($questions as $i => $q):
          $pct = $maxQ > 0 ? round($q['tot'] / $maxQ * 100) : 0;
      ?>
      <div class="q-row">
        <div class="q-hd">
          <span class="q-name" title="<?= htmlspecialchars($q['question']) ?>"><?= htmlspecialchars($q['question']) ?></span>
          <span class="q-val"><?= number_format($q['tot']) ?></span>
        </div>
        <div class="q-bar">
          <div class="q-fill" data-w="<?= $pct ?>" style="width:0%;background:<?= $colors[$i % 6] ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($questions)): ?>
        <div style="font-size:10px;color:var(--muted)">No data yet</div>
      <?php endif; ?>
    </div>

    <div class="sb-section">
      <div class="sb-title">Sync Status</div>
      <?php
        $tot = $kpis['total_records'] ?: 1;
        $sp  = round($kpis['synced'] / $tot * 100);
        $circ = 2 * M_PI * 24; $sd = $circ * ($sp / 100);
      ?>
      <div style="display:flex;align-items:center;gap:12px;padding:4px 0 8px">
        <svg width="60" height="60" viewBox="0 0 60 60" style="flex-shrink:0">
          <circle cx="30" cy="30" r="24" fill="none" stroke="var(--border2)" stroke-width="6"/>
          <circle cx="30" cy="30" r="24" fill="none" stroke="var(--green)" stroke-width="6"
            stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $circ - $sd ?>"
            stroke-linecap="butt" transform="rotate(-90 30 30)" opacity=".9"/>
          <text x="30" y="35" text-anchor="middle"
            font-family="'Syne',sans-serif" font-size="13" font-weight="800"
            fill="var(--text2)"><?= $sp ?>%</text>
        </svg>
        <div>
          <div class="sync-item"><span class="sync-dot" style="background:var(--green)"></span>Synced (<?= $kpis['synced'] ?>)</div>
          <div class="sync-item"><span class="sync-dot" style="background:var(--amber)"></span>Unsynced (<?= $kpis['unsynced'] ?>)</div>
        </div>
      </div>
    </div>

    <div class="sb-section">
      <div class="sb-title">Quick Filters</div>
      <button class="qf-btn" onclick="setSyncFilter(1)">✓ Synced only</button>
      <button class="qf-btn" onclick="setSyncFilter(0)">⏳ Unsynced only</button>
      <button class="qf-btn" onclick="setSyncFilter(-1)">↺ Show all</button>
    </div>

  </aside>

  <!-- MAIN -->
  <div class="main">

    <!-- STAT ROW -->
    <div class="stat-row">
      <div class="stat-card">
        <div class="stat-label">Total Bales</div>
        <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['total_bales']) ?></div>
        <div class="stat-sub"><?= number_format($kpis['total_records']) ?> records</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Growers</div>
        <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['growers_count']) ?></div>
        <div class="stat-sub">Distinct growers</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Avg Bales</div>
        <div class="stat-val" style="color:var(--amber)"><?= number_format($kpis['avg_bales'],1) ?></div>
        <div class="stat-sub">Per record</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Synced</div>
        <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['synced']) ?></div>
        <div class="stat-sub">Uploaded</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Unsynced</div>
        <div class="stat-val" style="color:var(--amber)"><?= number_format($kpis['unsynced']) ?></div>
        <div class="stat-sub">Pending sync</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Questions</div>
        <div class="stat-val" style="color:var(--blue)"><?= count($questions) ?></div>
        <div class="stat-sub">Distinct types</div>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <input class="f-input" type="text" id="searchInput"
        placeholder="🔍  Grower name, grower num, question…" oninput="applyFilters()">
      <input class="f-input" type="date" id="dateFrom" style="min-width:auto;width:130px" onchange="applyFilters()">
      <input class="f-input" type="date" id="dateTo"   style="min-width:auto;width:130px" onchange="applyFilters()">
      <div class="tab-group">
        <button class="tab active" onclick="setTab('all',this)">All</button>
        <button class="tab" onclick="setTab('synced',this)">Synced</button>
        <button class="tab" onclick="setTab('unsynced',this)">Unsynced</button>
      </div>
      <span class="f-count" id="fCount"><?= count($records) ?> records</span>
    </div>

    <!-- STRIP -->
    <div class="strip">
      <span>Showing <strong id="sCount"><?= count($records) ?></strong> records</span>
      <span class="strip-sep">·</span>
      <span>Total Bales: <strong id="sBales" style="color:var(--green)"><?= number_format($totBales) ?></strong></span>
      <span class="strip-sep">·</span>
      <span>Season: <strong style="color:var(--text2)"><?= htmlspecialchars($seasonName) ?></strong></span>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="check-cell"><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th>
            <th onclick="sortTable('id')">#</th>
            <th onclick="sortTable('grower')">Grower</th>
            <th onclick="sortTable('grower_num')">Grower No.</th>
            <th onclick="sortTable('question')">Question</th>
            <th onclick="sortTable('bales')">Bales</th>
            <th onclick="sortTable('created_at')">Created At</th>
            <th onclick="sortTable('datetimes')">Device Time</th>
            <th onclick="sortTable('datetime_sync')">Sync Time</th>
            <th>Sync</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <?php if(empty($records)): ?>
          <tr><td colspan="11" class="empty-cell">No bale records found for season: <?= htmlspecialchars($seasonName) ?></td></tr>
          <?php else: foreach($records as $rec):
            $growerName = trim(($rec['name']??'').' '.($rec['surname']??''));
            $isSynced   = (int)$rec['sync'] === 1;
            $hasGps     = !empty($rec['latitude']) && $rec['latitude'] !== '0' && $rec['latitude'] !== '0.0';
          ?>
          <tr
            data-id="<?= $rec['id'] ?>"
            data-grower="<?= strtolower(htmlspecialchars($growerName)) ?>"
            data-grower_num="<?= strtolower(htmlspecialchars($rec['grower_num']??'')) ?>"
            data-question="<?= strtolower(htmlspecialchars($rec['question'])) ?>"
            data-bales="<?= $rec['bales'] ?>"
            data-sync="<?= $rec['sync'] ?>"
            data-created_at="<?= $rec['created_at'] ?>"
            data-datetimes="<?= $rec['datetimes'] ?>"
            data-datetime_sync="<?= $rec['datetime_sync'] ?>"
            onclick="openDrawer(this)"
          >
            <td class="check-cell" onclick="event.stopPropagation()">
              <input type="checkbox" class="row-check" onchange="updateBulk()">
            </td>
            <td class="td-id"><?= $rec['id'] ?></td>
            <td>
              <div class="td-name"><?= htmlspecialchars($growerName) ?: 'Grower #'.$rec['growerid'] ?></div>
              <div class="td-sub">ID: <?= $rec['growerid'] ?></div>
            </td>
            <td class="td-mono"><?= htmlspecialchars($rec['grower_num']??'—') ?></td>
            <td class="td-q" title="<?= htmlspecialchars($rec['question']) ?>">
              <?= htmlspecialchars($rec['question']) ?>
            </td>
            <td class="td-bales"><?= number_format($rec['bales']) ?></td>
            <td class="td-mono"><?= htmlspecialchars($rec['created_at']) ?></td>
            <td class="td-mono"><?= htmlspecialchars($rec['datetimes']) ?></td>
            <td class="td-mono" style="white-space:nowrap">
              <?= !empty($rec['datetime_sync']) ? date('d M Y H:i', strtotime($rec['datetime_sync'])) : '—' ?>
            </td>
            <td>
              <?= $isSynced
                ? '<span class="badge badge-ok">Synced</span>'
                : '<span class="badge badge-warn">Pending</span>' ?>
            </td>
            <td>
              <?php if($hasGps): ?>
                <a href="https://maps.google.com/?q=<?= $rec['latitude'] ?>,<?= $rec['longitude'] ?>"
                   target="_blank" class="badge badge-info" onclick="event.stopPropagation()">📍 Map</a>
              <?php else: ?>
                <span class="td-mono">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- BULK BAR -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-n" id="bulkN">0 selected</span>
  <button class="btn btn-outline btn-sm" onclick="document.getElementById('exportModal').classList.add('open')">↓ Export Selection</button>
  <div class="bulk-sep"></div>
  <button class="btn btn-outline btn-sm" onclick="clearAll()">✕ Clear</button>
</div>

<!-- DETAIL DRAWER -->
<div class="drawer-overlay" id="drawerOv" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
  <div class="drawer-head">
    <div>
      <div class="drawer-id">Record <span id="dId">—</span></div>
      <div class="drawer-name" id="dGrower">—</div>
      <div style="font-size:9px;color:var(--muted);margin-top:2px" id="dGrowerSub"></div>
    </div>
    <button class="drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="drawer-bales" id="dBales">0</div>
    <div class="drawer-bales-label">Bales</div>
    <div id="dSyncBadge"></div>

    <div class="d-section">Questionnaire</div>
    <div class="d-row"><span class="d-label">Question</span><span class="d-val" id="dQuestion">—</span></div>
    <div class="d-row"><span class="d-label">Question Created</span><span class="d-val td-mono" id="dQCreated">—</span></div>

    <div class="d-section">Timestamps</div>
    <div class="d-row"><span class="d-label">Created At</span><span class="d-val td-mono" id="dCreated">—</span></div>
    <div class="d-row"><span class="d-label">Device Time</span><span class="d-val td-mono" id="dDatetimes">—</span></div>
    <div class="d-row"><span class="d-label">Sync Time</span><span class="d-val td-mono" id="dDatetimeSync">—</span></div>

    <div class="d-section">Location</div>
    <div class="d-row"><span class="d-label">Latitude</span><span class="d-val td-mono" id="dLat">—</span></div>
    <div class="d-row"><span class="d-label">Longitude</span><span class="d-val td-mono" id="dLng">—</span></div>
    <div style="margin-top:8px" id="dMapLink"></div>

    <div class="d-section">System</div>
    <div class="d-row"><span class="d-label">Record ID</span><span class="d-val td-mono" id="dRId">—</span></div>
    <div class="d-row"><span class="d-label">Grower ID</span><span class="d-val td-mono" id="dGrowerId">—</span></div>
    <div class="d-row"><span class="d-label">User ID</span><span class="d-val td-mono" id="dUserId">—</span></div>
    <div class="d-row"><span class="d-label">Season ID</span><span class="d-val td-mono" id="dSeasonId">—</span></div>
  </div>
  <div class="drawer-foot">
    <button class="btn btn-outline" style="flex:1" onclick="closeDrawer()">Close</button>
  </div>
</div>

<!-- EXPORT MODAL -->
<div class="modal-bg" id="exportModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('exportModal').classList.remove('open')">✕</button>
    <div class="modal-title">Export Bale Records</div>
    <div class="modal-sub">Season: <?= htmlspecialchars($seasonName) ?></div>
    <label class="m-label">Format</label>
    <select class="m-select"><option>Excel (.xlsx)</option><option>CSV</option><option>PDF Summary</option></select>
    <label class="m-label">Date Range</label>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
      <input class="m-input" style="margin-bottom:0" type="date" value="<?= date('Y-m-01') ?>">
      <input class="m-input" style="margin-bottom:0" type="date" value="<?= date('Y-m-d') ?>">
    </div>
    <label class="m-label">Include Columns</label>
    <div class="m-check-group">
      <label class="m-check"><input type="checkbox" checked> Grower name &amp; number</label>
      <label class="m-check"><input type="checkbox" checked> Question</label>
      <label class="m-check"><input type="checkbox" checked> Bales</label>
      <label class="m-check"><input type="checkbox" checked> Timestamps</label>
      <label class="m-check"><input type="checkbox" checked> GPS coordinates</label>
      <label class="m-check"><input type="checkbox"> Sync status</label>
    </div>
    <div class="modal-actions">
      <button class="btn btn-outline" onclick="document.getElementById('exportModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-green" onclick="document.getElementById('exportModal').classList.remove('open')">↓ Download</button>
    </div>
  </div>
</div>

<script>
const rowData = <?php
  $js = [];
  foreach($records as $rec){
    $growerName = trim(($rec['name']??'').' '.($rec['surname']??''));
    $js[] = [
      'id'            => $rec['id'],
      'grower'        => $growerName ?: 'Grower #'.$rec['growerid'],
      'grower_num'    => $rec['grower_num'] ?? '',
      'growerid'      => $rec['growerid'],
      'userid'        => $rec['userid'],
      'seasonid'      => $rec['seasonid'],
      'question'      => $rec['question'],
      'bales'         => $rec['bales'],
      'latitude'      => $rec['latitude'],
      'longitude'     => $rec['longitude'],
      'question_created_at' => $rec['question_created_at'],
      'created_at'    => $rec['created_at'],
      'datetimes'     => $rec['datetimes'],
      'datetime_sync' => $rec['datetime_sync'],
      'sync'          => (int)$rec['sync'],
    ];
  }
  echo json_encode($js);
?>;

/* SYNC FILTER */
let syncFilter = 'all';
function setTab(val, el){
  syncFilter = val;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  applyFilters();
}
function setSyncFilter(val){
  syncFilter = val === -1 ? 'all' : (val === 1 ? 'synced' : 'unsynced');
  document.querySelectorAll('.tab').forEach(t => {
    t.classList.toggle('active', t.textContent.trim().toLowerCase() === syncFilter);
  });
  applyFilters();
}

/* FILTERS */
function applyFilters(){
  const search = document.getElementById('searchInput').value.toLowerCase();
  const dfrom  = document.getElementById('dateFrom').value;
  const dto    = document.getElementById('dateTo').value;
  let cnt = 0, totBales = 0;

  document.querySelectorAll('#tableBody tr[data-id]').forEach(row => {
    const rText = (row.dataset.grower||'') + (row.dataset.grower_num||'') + (row.dataset.question||'');
    const rSync = parseInt(row.dataset.sync);
    const rDt   = row.dataset.datetime_sync ? row.dataset.datetime_sync.substring(0,10) : '';
    const show  =
      (!search || rText.includes(search)) &&
      (!dfrom  || (rDt && rDt >= dfrom))  &&
      (!dto    || (rDt && rDt <= dto))    &&
      (syncFilter === 'all' || (syncFilter === 'synced' && rSync === 1) || (syncFilter === 'unsynced' && rSync === 0));
    row.style.display = show ? '' : 'none';
    if(show){ cnt++; totBales += parseInt(row.dataset.bales)||0; }
  });

  document.getElementById('fCount').textContent  = cnt + ' records';
  document.getElementById('sCount').textContent  = cnt;
  document.getElementById('sBales').textContent  = totBales.toLocaleString();
}

/* SORT */
let sortKey = '', sortDir = 1;
function sortTable(key){
  if(sortKey === key) sortDir *= -1; else { sortKey = key; sortDir = 1; }
  const tbody = document.getElementById('tableBody');
  const rows  = [...tbody.querySelectorAll('tr[data-id]')];
  rows.sort((a,b) => {
    let av = a.dataset[key]||'', bv = b.dataset[key]||'';
    if(key === 'bales'){ av = parseInt(av)||0; bv = parseInt(bv)||0; }
    return av < bv ? -sortDir : av > bv ? sortDir : 0;
  });
  rows.forEach(r => tbody.appendChild(r));
  document.querySelectorAll('thead th').forEach(t => t.classList.remove('sorted'));
  event.currentTarget && event.currentTarget.classList.add('sorted');
}

/* CHECKBOXES */
function toggleAll(cb){ document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked); updateBulk(); }
function updateBulk(){
  const n = document.querySelectorAll('.row-check:checked').length;
  document.getElementById('bulkN').textContent = n + ' selected';
  document.getElementById('bulkBar').classList.toggle('show', n > 0);
}
function clearAll(){
  document.querySelectorAll('.row-check,#checkAll').forEach(c => c.checked = false);
  document.getElementById('bulkBar').classList.remove('show');
}

/* DRAWER */
function openDrawer(row){
  const rec = rowData.find(r => String(r.id) === String(row.dataset.id));
  if(!rec) return;

  document.getElementById('dId').textContent         = rec.id;
  document.getElementById('dGrower').textContent     = rec.grower;
  document.getElementById('dGrowerSub').textContent  = rec.grower_num ? rec.grower_num + '  ·  ID: ' + rec.growerid : 'ID: ' + rec.growerid;
  document.getElementById('dBales').textContent      = Number(rec.bales).toLocaleString();
  document.getElementById('dQuestion').textContent   = rec.question;
  document.getElementById('dQCreated').textContent   = rec.question_created_at || '—';
  document.getElementById('dCreated').textContent    = rec.created_at || '—';
  document.getElementById('dDatetimes').textContent  = rec.datetimes || '—';
  document.getElementById('dDatetimeSync').textContent = rec.datetime_sync || '—';
  document.getElementById('dLat').textContent        = rec.latitude || '—';
  document.getElementById('dLng').textContent        = rec.longitude || '—';
  document.getElementById('dRId').textContent        = rec.id;
  document.getElementById('dGrowerId').textContent   = rec.growerid;
  document.getElementById('dUserId').textContent     = rec.userid;
  document.getElementById('dSeasonId').textContent   = rec.seasonid;

  document.getElementById('dSyncBadge').innerHTML = rec.sync === 1
    ? '<span class="badge badge-ok">Synced</span>'
    : '<span class="badge badge-warn">Pending</span>';

  const hasGps = rec.latitude && rec.latitude !== '0' && rec.latitude !== '0.0';
  document.getElementById('dMapLink').innerHTML = hasGps
    ? '<a href="https://maps.google.com/?q='+rec.latitude+','+rec.longitude+'" target="_blank" class="btn btn-outline btn-sm">📍 Open in Maps</a>'
    : '';

  document.getElementById('drawerOv').classList.add('open');
  document.getElementById('drawer').classList.add('open');
}
function closeDrawer(){
  document.getElementById('drawerOv').classList.remove('open');
  document.getElementById('drawer').classList.remove('open');
}

/* MODAL BACKDROP */
document.querySelectorAll('.modal-bg').forEach(m => {
  m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); });
});

/* ANIMATE BARS */
window.addEventListener('load', () => {
  document.querySelectorAll('.q-fill[data-w]').forEach(el => {
    setTimeout(() => el.style.width = el.dataset.w + '%', 150);
  });
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>
