<?php
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
mysqli_report(MYSQLI_REPORT_OFF); // prevent fatal on query errors

/* ================================================================
   ACTIVE SEASON  (seasonid scope only)
   ================================================================ */
$seasonId = 0; $seasonName = '—';
$r = $conn->query("SELECT id, name FROM seasons WHERE active=1 LIMIT 1");
if($r && $row = $r->fetch_assoc()){ $seasonId = (int)$row['id']; $seasonName = $row['name']; $r->free(); }

/* ================================================================
   KPIs  — raw rollover table, seasonid only
   ================================================================ */
$kpis = ['total_amount'=>0.0,'total_records'=>0,'growers_count'=>0,'officers_count'=>0,'avg_amount'=>0.0];
$rk = $conn->query("
  SELECT COUNT(*)               AS cnt,
         COALESCE(SUM(amount),0) AS tot,
         COUNT(DISTINCT growerid) AS growers,
         COUNT(DISTINCT userid)   AS officers
  FROM   rollover
  WHERE  seasonid = $seasonId AND amount > 0
");
if($rk && $rk !== false && $row = $rk->fetch_assoc()){
  $kpis['total_records']  = (int)$row['cnt'];
  $kpis['total_amount']   = (float)$row['tot'];
  $kpis['growers_count']  = (int)$row['growers'];
  $kpis['officers_count'] = (int)$row['officers'];
  $kpis['avg_amount']     = $row['cnt'] > 0 ? round($row['tot'] / $row['cnt'], 2) : 0;
  $rk->free();
}

/* ================================================================
   FILTERS
   ================================================================ */
$fSearch   = isset($_GET['search']) ? trim($_GET['search']) : '';
$fDateFrom = isset($_GET['from'])   ? trim($_GET['from'])   : '';
$fDateTo   = isset($_GET['to'])     ? trim($_GET['to'])     : '';

$where = ["rv.seasonid = $seasonId", "rv.amount > 0"];
if($fDateFrom != ''){ $fd = $conn->real_escape_string($fDateFrom); $where[] = "DATE(rv.`datetime`) >= '$fd'"; }
if($fDateTo   != ''){ $ft = $conn->real_escape_string($fDateTo);   $where[] = "DATE(rv.`datetime`) <= '$ft'"; }
$whereStr = implode(' AND ', $where);

/* ================================================================
   MAIN QUERY — raw rollover table only, no joins
   ================================================================ */
$records = [];
$rr = $conn->query("SELECT rv.id, rv.userid, rv.growerid,
         rv.rollover_seasonid, rv.seasonid,
         rv.amount, rv.`datetime`,
         g.name, g.surname, g.grower_num
  FROM rollover rv
  LEFT JOIN growers g ON g.id = rv.growerid
  WHERE $whereStr
  ORDER BY rv.`datetime` DESC
  LIMIT 500");
if($rr && $rr !== false){ while($row = $rr->fetch_assoc()){ $records[] = $row; } $rr->free(); }

/* client-side search applied in JS — no need to filter in SQL */
$officersList = []; /* removed — no users join */

$conn->close();

/* helpers — PHP 7.0+ compatible */
$totAmt = 0;
foreach($records as $_rec){ $totAmt += (float)$_rec['amount']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>GMS · Season Rollover</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
/* ============================================================
   GMS DESIGN SYSTEM — matches reports_hub / grower_payments
   Space Mono · Syne · dark green on #0a0f0a
   ============================================================ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0f0a;
  --surface:#111a11;
  --surface2:#162016;
  --border:#1f2e1f;
  --border2:#2a3d2a;
  --green:#3ddc68;
  --green-dim:#1a5e30;
  --amber:#f5a623;
  --amber-dim:#3a2800;
  --red:#e84040;
  --red-dim:#200000;
  --blue:#4a9eff;
  --blue-dim:#001020;
  --purple:#b47eff;
  --purple-dim:#1a0a2e;
  --text:#c8e6c9;
  --text2:#7aaa7a;
  --muted:#4a6b4a;
  --radius:6px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100%}

/* HEADER */
header{
  display:flex;align-items:center;gap:10px;
  padding:0 20px;height:56px;
  background:var(--surface);border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
.logo span{color:var(--muted)}
.back{font-size:11px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:4px;transition:all .2s}
.back:hover{color:var(--green);border-color:var(--green)}
.header-right{margin-left:auto;display:flex;align-items:center;gap:8px;font-size:10px;color:var(--muted)}

/* PAGE */
.content{padding:20px;max-width:1400px;margin:0 auto}

/* STAT ROW */
.stat-row{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
  gap:1px;background:var(--border);
  border:1px solid var(--border);border-radius:var(--radius);
  overflow:hidden;margin-bottom:24px;
}
.stat-card{background:var(--surface);padding:16px 20px;text-align:center;transition:background .15s}
.stat-card:hover{background:var(--surface2)}
.stat-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px}
.stat-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:3px}
.stat-sub{font-size:9px;color:var(--muted)}

/* FILTER BAR */
.filter-bar{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  padding:10px 0;margin-bottom:12px;
}
.f-input,.f-select{
  background:var(--surface);border:1px solid var(--border);
  color:var(--text2);padding:5px 9px;border-radius:4px;
  font-size:10px;font-family:'Space Mono',monospace;outline:none;
}
.f-input:focus,.f-select:focus{border-color:var(--green);color:var(--text)}
.f-input{min-width:200px}
.f-count{margin-left:auto;font-size:10px;color:var(--muted)}

/* STRIP */
.strip{
  display:flex;align-items:center;gap:16px;flex-wrap:wrap;
  padding:7px 0 10px;font-size:10px;color:var(--muted);
}
.strip strong{color:var(--text2)}
.strip-sep{color:var(--border2)}

/* TABLE */
.table-wrap{
  border:1px solid var(--border);border-radius:var(--radius);
  overflow:hidden;
}
table{width:100%;border-collapse:collapse}
thead tr{background:var(--surface);border-bottom:1px solid var(--border2)}
thead th{
  padding:9px 14px;text-align:left;
  font-size:9px;color:var(--muted);letter-spacing:.5px;
  text-transform:uppercase;font-weight:700;white-space:nowrap;
  cursor:pointer;user-select:none;transition:color .15s;
}
thead th:hover{color:var(--text2)}
thead th.sorted{color:var(--green)}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:var(--surface)}
tbody td{padding:10px 14px;font-size:11px;color:var(--text2)}
.td-id{font-size:10px;color:var(--muted)}
.td-name{color:var(--text);font-weight:700}
.td-sub{font-size:9px;color:var(--muted);margin-top:1px}
.td-amt{color:var(--green);font-weight:700;font-size:12px}
.td-mono{font-size:10px;color:var(--muted)}
.check-cell{width:32px;padding-left:14px!important}
.check-cell input[type=checkbox]{width:13px;height:13px;accent-color:var(--green);cursor:pointer}

/* BADGES */
.badge{
  display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;
  border:1px solid;
}
.badge-ok    {background:#0d200d;color:var(--green);border-color:var(--green-dim)}
.badge-warn  {background:var(--amber-dim);color:var(--amber);border-color:#5a3800}
.badge-info  {background:var(--blue-dim);color:var(--blue);border-color:#003050}
.badge-purple{background:var(--purple-dim);color:var(--purple);border-color:#3a1a5e}

/* DRAWER */
.drawer-overlay{position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);display:none}
.drawer-overlay.open{display:block}
.drawer{
  position:fixed;top:0;right:-460px;bottom:0;width:440px;z-index:301;
  background:var(--surface);border-left:1px solid var(--border2);
  transition:right .26s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
}
.drawer.open{right:0}
.drawer-head{
  padding:16px 20px 12px;border-bottom:1px solid var(--border);
  display:flex;align-items:flex-start;justify-content:space-between;
}
.drawer-id{font-size:9px;color:var(--muted);margin-bottom:4px;letter-spacing:.4px}
.drawer-name{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--text);line-height:1.1}
.drawer-close{background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;transition:color .15s}
.drawer-close:hover{color:var(--text)}
.drawer-body{flex:1;overflow-y:auto;padding:16px 20px}
.drawer-amount{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;color:var(--green);padding:12px 0 8px;line-height:1}
.d-section{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:14px 0 8px;padding-bottom:5px;border-bottom:1px solid var(--border)}
.d-row{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--border);font-size:11px}
.d-row:last-child{border-bottom:none}
.d-label{color:var(--muted);font-size:10px;flex-shrink:0;margin-right:12px;padding-top:1px}
.d-val{color:var(--text);text-align:right;word-break:break-all;font-weight:700}
.drawer-foot{padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px}

/* BULK BAR */
.bulk-bar{
  position:fixed;bottom:0;left:0;right:0;z-index:200;
  background:var(--surface2);border-top:1px solid var(--border2);
  padding:10px 20px;display:flex;align-items:center;gap:10px;
  transform:translateY(100%);transition:transform .2s ease;
}
.bulk-bar.show{transform:translateY(0)}
.bulk-n{font-size:11px;color:var(--green);margin-right:4px}
.bulk-sep{flex:1}

/* MODAL */
.modal-bg{position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.72);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{
  background:var(--surface);border:1px solid var(--border2);
  border-radius:var(--radius);padding:22px;width:420px;max-width:92vw;
  position:relative;animation:slideUp .18s ease both;
}
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--text);margin-bottom:3px}
.modal-sub{font-size:10px;color:var(--muted);margin-bottom:16px}
.modal-close{position:absolute;top:12px;right:14px;background:none;border:none;color:var(--muted);font-size:16px;cursor:pointer}
.modal-close:hover{color:var(--text)}
.m-label{font-size:10px;color:var(--muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.m-select,.m-input{
  width:100%;background:var(--bg);border:1px solid var(--border);
  color:var(--text);padding:7px 9px;border-radius:4px;
  font-size:11px;font-family:'Space Mono',monospace;outline:none;margin-bottom:12px;
}
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

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.stat-card{animation:fadeUp .3s ease both}
.stat-card:nth-child(1){animation-delay:.04s}.stat-card:nth-child(2){animation-delay:.08s}
.stat-card:nth-child(3){animation-delay:.12s}.stat-card:nth-child(4){animation-delay:.16s}
.stat-card:nth-child(5){animation-delay:.20s}

/* SCROLLBAR */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--muted)}

/* EMPTY */
.empty-cell{text-align:center!important;padding:48px!important;color:var(--muted)!important;font-size:11px!important}
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="logo">GMS<span>/</span>Rollover</div>
  <a href="reports_hub.php" class="back">← Hub</a>
  <a href="loans_dashboard.php" class="back">📊 Loans</a>
  <a href="grower_payments.php" class="back">💳 Payments</a>
  <div class="header-right">
    Season: <?= htmlspecialchars($seasonName) ?> · <?= date('d M Y H:i') ?>
    &nbsp;
    <button class="btn btn-outline btn-sm" onclick="document.getElementById('exportModal').classList.add('open')">↓ Export</button>
  </div>
</header>

<div class="content">

  <!-- PAGE TITLE -->
  <div style="margin-bottom:20px">
    <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800">
      🔄 Season Rollover
    </div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px">
      Rollover records for season: <strong style="color:var(--text2)"><?= htmlspecialchars($seasonName) ?></strong>
      &nbsp;·&nbsp; Season ID: <strong style="color:var(--green)"><?= $seasonId ?></strong>
    </div>
  </div>

  <!-- STAT ROW -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-label">Total Rollover Amount</div>
      <div class="stat-val" style="color:var(--green)">$<?= number_format($kpis['total_amount'],2) ?></div>
      <div class="stat-sub"><?= number_format($kpis['total_records']) ?> records</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Growers</div>
      <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['growers_count']) ?></div>
      <div class="stat-sub">Distinct growers</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Officers</div>
      <div class="stat-val" style="color:var(--blue)"><?= number_format($kpis['officers_count']) ?></div>
      <div class="stat-sub">Field officers</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Avg per Record</div>
      <div class="stat-val" style="color:var(--amber)">$<?= number_format($kpis['avg_amount'],2) ?></div>
      <div class="stat-sub">Average amount</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Records</div>
      <div class="stat-val" style="color:var(--text2)"><?= number_format($kpis['total_records']) ?></div>
      <div class="stat-sub">This season</div>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <input class="f-input" type="text" id="searchInput"
      placeholder="🔍  Search grower ID, user ID, amount…" oninput="applyFilters()">
    <input class="f-input" type="date" id="dateFrom"
      style="min-width:auto;width:130px" onchange="applyFilters()">
    <input class="f-input" type="date" id="dateTo"
      style="min-width:auto;width:130px" onchange="applyFilters()">
    <span class="f-count" id="fCount"><?= count($records) ?> records</span>
  </div>

  <!-- SUMMARY STRIP -->
  <div class="strip">
    <span>Showing <strong id="sCount"><?= count($records) ?></strong> records</span>
    <span class="strip-sep">·</span>
    <span>Total: <strong id="sAmt" style="color:var(--green)">$<?= number_format($totAmt,2) ?></strong></span>
    <span class="strip-sep">·</span>
    <span>Season ID: <strong style="color:var(--text2)"><?= $seasonId ?></strong></span>
  </div>

  <!-- TABLE -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th class="check-cell">
            <input type="checkbox" id="checkAll" onchange="toggleAll(this)">
          </th>
          <th onclick="sortTable('id')">#</th>
          <th onclick="sortTable('grower')">Grower</th>
          <th onclick="sortTable('growerid')">Grower ID</th>
          <th onclick="sortTable('userid')">User ID</th>
          <th onclick="sortTable('rollover_seasonid')">Rollover Season ID</th>
          <th onclick="sortTable('seasonid')">Season ID</th>
          <th onclick="sortTable('amount')">Amount ($)</th>
          <th onclick="sortTable('datetime')">Date / Time</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if(empty($records)): ?>
        <tr><td colspan="9" class="empty-cell">No rollover records found for season ID: <?= $seasonId ?> (<?= htmlspecialchars($seasonName) ?>)</td></tr>
        <?php else: foreach($records as $rec):
          $dt = !empty($rec['datetime']) ? date('d M Y  H:i', strtotime(str_replace('T',' ',$rec['datetime']))) : '—';
        ?>
        <tr
          data-id="<?= $rec['id'] ?>"
          data-grower="<?= strtolower(htmlspecialchars(trim(($rec['name']??'').' '.($rec['surname']??'')))) ?>"
          data-growerid="<?= $rec['growerid'] ?>"
          data-userid="<?= $rec['userid'] ?>"
          data-rollover_seasonid="<?= $rec['rollover_seasonid'] ?>"
          data-seasonid="<?= $rec['seasonid'] ?>"
          data-amount="<?= $rec['amount'] ?>"
          data-datetime="<?= htmlspecialchars($rec['datetime'] ?? '') ?>"
          onclick="openDrawer(this)"
        >
          <td class="check-cell" onclick="event.stopPropagation()">
            <input type="checkbox" class="row-check" onchange="updateBulk()">
          </td>
          <td class="td-id"><?= $rec['id'] ?></td>
          <td>
              <div class="td-name"><?= htmlspecialchars(trim(($rec['name']??'').' '.($rec['surname']??''))) ?: '#'.$rec['growerid'] ?></div>
              <div class="td-grower-num" style="font-size:9px;color:var(--muted)"><?= htmlspecialchars($rec['grower_num'] ?? '') ?></div>
          </td>
          <td class="td-mono"><?= $rec['growerid'] ?></td>
          <td class="td-mono"><?= $rec['userid'] ?></td>
          <td><span class="badge badge-warn"><?= $rec['rollover_seasonid'] ?></span></td>
          <td><span class="badge badge-ok"><?= $rec['seasonid'] ?></span></td>
          <td class="td-amt">$<?= number_format($rec['amount'],2) ?></td>
          <td class="td-mono" style="white-space:nowrap"><?= $dt ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- /content -->

<!-- BULK BAR -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-n" id="bulkN">0 selected</span>
  <button class="btn btn-outline btn-sm"
    onclick="document.getElementById('exportModal').classList.add('open')">↓ Export Selection</button>
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
      <div style="font-size:9px;color:var(--muted);margin-top:2px" id="dGrowerNum"></div>
    </div>
    <button class="drawer-close" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-body">
    <div class="drawer-amount" id="dAmount">$0.00</div>

    <div class="d-section">Rollover Details</div>
    <div class="d-row"><span class="d-label">Field Officer</span><span class="d-val" id="dOfficer">—</span></div>
    <div class="d-row"><span class="d-label">From Season</span><span class="d-val" id="dFromSeason">—</span></div>
    <div class="d-row"><span class="d-label">To Season</span><span class="d-val" id="dToSeason">—</span></div>
    <div class="d-row"><span class="d-label">Date / Time</span><span class="d-val td-mono" id="dDatetime">—</span></div>

    <div class="d-section">System</div>
    <div class="d-row"><span class="d-label">Rollover ID</span><span class="d-val td-mono" id="dRvId">—</span></div>
    <div class="d-row"><span class="d-label">Grower ID</span><span class="d-val td-mono" id="dGrowerId">—</span></div>
    <div class="d-row"><span class="d-label">Season ID</span><span class="d-val td-mono" id="dSeasonId">—</span></div>
    <div class="d-row"><span class="d-label">Rollover Season ID</span><span class="d-val td-mono" id="dRvSeasonId">—</span></div>
    <div class="d-row"><span class="d-label">User ID</span><span class="d-val td-mono" id="dUserId">—</span></div>
  </div>
  <div class="drawer-foot">
    <button class="btn btn-outline" style="flex:1" onclick="closeDrawer()">Close</button>
  </div>
</div>

<!-- EXPORT MODAL -->
<div class="modal-bg" id="exportModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('exportModal').classList.remove('open')">✕</button>
    <div class="modal-title">Export Rollover</div>
    <div class="modal-sub">Season: <?= htmlspecialchars($seasonName) ?> (ID: <?= $seasonId ?>)</div>

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
      <label class="m-check"><input type="checkbox" checked> Field officer</label>
      <label class="m-check"><input type="checkbox" checked> Rollover amount</label>
      <label class="m-check"><input type="checkbox" checked> From / To season</label>
      <label class="m-check"><input type="checkbox" checked> Date &amp; time</label>
      <label class="m-check"><input type="checkbox"> Officer breakdown sheet</label>
    </div>

    <div class="modal-actions">
      <button class="btn btn-outline" onclick="document.getElementById('exportModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-green" onclick="document.getElementById('exportModal').classList.remove('open')">↓ Download</button>
    </div>
  </div>
</div>

<script>
/* ROW DATA */
const rowData = <?php
  $js = [];
  foreach($records as $rec){
    $js[] = [
      'id'               => $rec['id'],
      'grower'           => trim(($rec['name']??'').' '.($rec['surname']??'')) ?: '#'.$rec['growerid'],
      'grower_num'       => $rec['grower_num'] ?? '',
      'growerid'         => $rec['growerid'],
      'userid'           => $rec['userid'],
      'rollover_seasonid'=> $rec['rollover_seasonid'],
      'seasonid'         => $rec['seasonid'],
      'amount'           => $rec['amount'],
      'datetime'         => isset($rec['datetime']) ? $rec['datetime'] : '',
    ];
  }
  echo json_encode($js);
?>;

/* FILTERS */
function applyFilters(){
  const search = document.getElementById('searchInput').value.toLowerCase();
  const dfrom  = document.getElementById('dateFrom').value;
  const dto    = document.getElementById('dateTo').value;
  let cnt = 0, totAmt = 0;

  document.querySelectorAll('#tableBody tr[data-id]').forEach(row => {
    const rText = (row.dataset.grower||'') + String(row.dataset.growerid||'') + String(row.dataset.userid||'');
    const rDt   = row.dataset.datetime ? row.dataset.datetime.substring(0,10) : '';
    const show  =
      (!search || rText.includes(search)) &&
      (!dfrom  || (rDt && rDt >= dfrom))  &&
      (!dto    || (rDt && rDt <= dto));
    row.style.display = show ? '' : 'none';
    if(show){ cnt++; totAmt += parseFloat(row.dataset.amount)||0; }
  });

  const fmt = n => n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
  document.getElementById('fCount').textContent  = cnt + ' records';
  document.getElementById('sCount').textContent  = cnt;
  document.getElementById('sAmt').textContent    = '$' + fmt(totAmt);
}

/* SORT */
let sortKey = '', sortDir = 1;
function sortTable(key){
  if(sortKey === key) sortDir *= -1; else { sortKey = key; sortDir = 1; }
  const tbody = document.getElementById('tableBody');
  const rows  = [...tbody.querySelectorAll('tr[data-id]')];
  rows.sort((a,b) => {
    let av = a.dataset[key]||'', bv = b.dataset[key]||'';
    if(key === 'amount'){ av = parseFloat(av); bv = parseFloat(bv); }
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
  const fmt = n => parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');

  document.getElementById('dId').textContent         = rec.id;
  document.getElementById('dGrower').textContent     = rec.grower || ('Grower ID: ' + rec.growerid);
  document.getElementById('dGrowerNum').textContent  = 'Grower ID: ' + rec.growerid + '  ·  User ID: ' + rec.userid;
  document.getElementById('dAmount').textContent     = '$' + fmt(rec.amount);
  document.getElementById('dOfficer').textContent    = rec.userid;
  document.getElementById('dFromSeason').textContent = rec.rollover_seasonid;
  document.getElementById('dToSeason').textContent   = rec.seasonid;
  document.getElementById('dRvId').textContent       = rec.id;
  document.getElementById('dGrowerId').textContent   = rec.growerid;
  document.getElementById('dSeasonId').textContent   = rec.seasonid;
  document.getElementById('dRvSeasonId').textContent = rec.rollover_seasonid;
  document.getElementById('dUserId').textContent     = rec.userid;

  var dtVal = rec.datetime || '';
  var dt = dtVal ? new Date(dtVal.replace(' ','T')) : null;
  document.getElementById('dDatetime').textContent = dt && !isNaN(dt)
    ? dt.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})
    : (dtVal || '—');

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
</script>
</body>
</html>
<?php ob_end_flush(); ?>
