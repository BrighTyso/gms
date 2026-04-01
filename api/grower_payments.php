<?php
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");
mysqli_report(MYSQLI_REPORT_OFF); // prevent fatal on query errors

/* ================================================================
   ACTIVE SEASON
   ================================================================ */
$seasonId = 0; $seasonName = '—';
$r = $conn->query("SELECT id, name FROM seasons WHERE active=1 LIMIT 1");
if($r && $row = $r->fetch_assoc()){ $seasonId=(int)$row['id']; $seasonName=$row['name']; $r->free(); }

/* ================================================================
   FILTER INPUTS
   ================================================================ */
$fOfficer   = isset($_GET['officer'])   ? (int)$_GET['officer']   : 0;
$fSync      = isset($_GET['sync'])      ? (int)$_GET['sync']      : -1;
$fSurrogate = isset($_GET['surrogate']) ? (int)$_GET['surrogate'] : -1;
$fDateFrom  = isset($_GET['from'])      ? trim($_GET['from'])      : '';
$fDateTo    = isset($_GET['to'])        ? trim($_GET['to'])        : '';
$fSearch    = isset($_GET['search'])    ? trim($_GET['search'])    : '';

/* ================================================================
   KPI QUERIES
   ================================================================ */
$kpis = ['total_paid'=>0.0,'total_mass'=>0.0,'total_records'=>0,
         'synced'=>0,'unsynced'=>0,'surrogate'=>0,'growers_paid'=>0];

$rk = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS tot_amt, COALESCE(SUM(mass),0) AS tot_mass FROM loan_payments WHERE seasonid=$seasonId");
if($rk && $row=$rk->fetch_assoc()){ $kpis['total_records']=(int)$row['cnt']; $kpis['total_paid']=(float)$row['tot_amt']; $kpis['total_mass']=(float)$row['tot_mass']; $rk->free(); }

$rk = $conn->query("SELECT sync, COUNT(*) AS cnt FROM loan_payments WHERE seasonid=$seasonId GROUP BY sync");
if($rk && $rk!==false){ while($row=$rk->fetch_assoc()){ if((int)$row['sync']===1) $kpis['synced']=(int)$row['cnt']; else $kpis['unsynced']=(int)$row['cnt']; } $rk->free(); }

$rk = $conn->query("SELECT COUNT(*) AS cnt FROM loan_payments WHERE seasonid=$seasonId AND surrogate=1");
if($rk && $row=$rk->fetch_assoc()){ $kpis['surrogate']=(int)$row['cnt']; $rk->free(); }

$rk = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM loan_payments WHERE seasonid=$seasonId");
if($rk && $row=$rk->fetch_assoc()){ $kpis['growers_paid']=(int)$row['cnt']; $rk->free(); }

/* ================================================================
   DESCRIPTION BREAKDOWN (sidebar)
   ================================================================ */
$desc_totals = [];
$rd = $conn->query("SELECT description, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS tot FROM loan_payments WHERE seasonid=$seasonId GROUP BY description ORDER BY tot DESC LIMIT 6");
if($rd && $rd!==false){ while($row=$rd->fetch_assoc()){ $desc_totals[]=$row; } $rd->free(); }

/* ================================================================
   OFFICERS DROPDOWN
   ================================================================ */
$officers = [];
$ro = $conn->query("SELECT DISTINCT userid FROM loan_payments WHERE seasonid=$seasonId ORDER BY userid");
if($ro && $ro!==false){ while($row=$ro->fetch_assoc()){ $officers[]=['userid'=>$row['userid'],'oname'=>'User '.$row['userid']]; } $ro->free(); }

/* ================================================================
   MAIN DATA QUERY
   ================================================================ */
$where = ["lp.seasonid=$seasonId"];
if($fOfficer>0)    $where[] = "lp.userid=$fOfficer";
if($fSync>=0)      $where[] = "lp.sync=$fSync";
if($fSurrogate>=0) $where[] = "lp.surrogate=$fSurrogate";
if($fDateFrom!='') { $fd=$conn->real_escape_string($fDateFrom); $where[]="DATE(lp.datetime)>='$fd'"; }
if($fDateTo!='')   { $ft=$conn->real_escape_string($fDateTo);   $where[]="DATE(lp.datetime)<='$ft'"; }
if($fSearch!=''){
  $fs=$conn->real_escape_string($fSearch);
  $where[]="(reference_num LIKE '%$fs%' OR receipt_number LIKE '%$fs%' OR description LIKE '%$fs%')";
}
$whereStr = implode(' AND ',$where);

$payments = [];
$_sql = "SELECT lp.id, lp.userid, lp.seasonid, lp.growerid,
         lp.reference_num, lp.receipt_number, lp.description,
         lp.amount, lp.mass, lp.bales, lp.sync, lp.surrogate,
         lp.created_at, lp.datetime,
         g.name, g.surname, g.grower_num
  FROM loan_payments lp
  LEFT JOIN growers g ON g.id = lp.growerid
  WHERE lp.seasonid = $seasonId
  ORDER BY lp.datetime DESC
  LIMIT 500";
$rp = $conn->query($_sql);
if($rp && $rp !== false){ while($row=$rp->fetch_assoc()){ $payments[]=$row; } $rp->free(); }
/* DEBUG — remove after confirming fix: */
// if(!$rp){ error_log('GMS payments query failed: '.$conn->error.' SQL: '.$_sql); }

$conn->close();

/* helpers — PHP 7.0+ compatible, no arrow functions */
$totAmt = 0; $totMass = 0; $nSynced = 0; $nUnsync = 0; $nSurr = 0;
foreach($payments as $_p){
  $totAmt  += (float)$_p['amount'];
  $totMass += (float)$_p['mass'];
  if((int)$_p['sync']      === 1) $nSynced++; else $nUnsync++;
  if((int)$_p['surrogate'] === 1) $nSurr++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>GMS · Grower Payments</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
/* ============================================================
   GMS DESIGN SYSTEM — matches reports_hub.php
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
  --green-glow:rgba(61,220,104,.06);
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

/* ---- HEADER ---- */
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

/* ---- LAYOUT ---- */
.page-wrap{display:flex;min-height:calc(100vh - 56px)}

/* ---- SIDEBAR ---- */
.sidebar{
  width:240px;flex-shrink:0;
  border-right:1px solid var(--border);
  background:var(--surface);
  padding:20px 0;
  position:sticky;top:56px;height:calc(100vh - 56px);
  overflow-y:auto;
}
.sb-section{padding:0 16px;margin-bottom:24px}
.sb-title{
  font-size:9px;text-transform:uppercase;letter-spacing:.5px;
  color:var(--muted);margin-bottom:10px;
  padding-bottom:6px;border-bottom:1px solid var(--border);
}

/* desc bars */
.desc-row{margin-bottom:9px}
.desc-hd{display:flex;justify-content:space-between;margin-bottom:3px}
.desc-name{font-size:10px;color:var(--text2);max-width:138px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.desc-amt{font-size:9px;color:var(--muted)}
.desc-bar{height:3px;background:var(--border2);border-radius:2px;overflow:hidden}
.desc-fill{height:100%;border-radius:2px;background:var(--green);transition:width .55s ease}
.desc-fill.c1{background:var(--blue)}
.desc-fill.c2{background:var(--purple)}
.desc-fill.c3{background:var(--amber)}
.desc-fill.c4{background:#3ddc68}
.desc-fill.c5{background:var(--red)}

/* sync legend */
.sync-legend{display:flex;flex-direction:column;gap:7px;margin-top:4px}
.sync-item{display:flex;align-items:center;gap:8px;font-size:10px;color:var(--text2)}
.sync-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* quick filter links */
.qf-btn{
  display:flex;align-items:center;gap:6px;
  font-size:10px;color:var(--muted);
  padding:5px 8px;border-radius:4px;
  cursor:pointer;border:1px solid transparent;
  transition:all .15s;margin-bottom:4px;
  background:none;width:100%;text-align:left;
  font-family:'Space Mono',monospace;
}
.qf-btn:hover{color:var(--green);border-color:var(--border);background:var(--green-glow)}

/* ---- MAIN ---- */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* ---- STAT ROW ---- */
.stat-row{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
  gap:1px;background:var(--border);
  border-bottom:1px solid var(--border);
}
.stat-card{background:var(--surface);padding:16px 20px;text-align:center;transition:background .15s}
.stat-card:hover{background:var(--surface2)}
.stat-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px}
.stat-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:3px}
.stat-sub{font-size:9px;color:var(--muted)}

/* ---- FILTER BAR ---- */
.filter-bar{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  padding:10px 20px;background:var(--surface);
  border-bottom:1px solid var(--border);
}
.f-input,.f-select{
  background:var(--bg);border:1px solid var(--border);
  color:var(--text2);padding:5px 9px;border-radius:4px;
  font-size:10px;font-family:'Space Mono',monospace;outline:none;
}
.f-input:focus,.f-select:focus{border-color:var(--green);color:var(--text)}
.f-input{min-width:180px}
.tab-group{display:flex;border:1px solid var(--border);border-radius:4px;overflow:hidden}
.tab{
  padding:5px 11px;font-size:10px;font-family:'Space Mono',monospace;
  cursor:pointer;color:var(--muted);background:var(--bg);border:none;transition:all .12s;
}
.tab:hover{color:var(--text2)}
.tab.active{background:var(--surface2);color:var(--green)}
.f-count{margin-left:auto;font-size:10px;color:var(--muted)}

/* ---- STRIP ---- */
.strip{
  display:flex;align-items:center;gap:16px;flex-wrap:wrap;
  padding:7px 20px;background:var(--bg);
  border-bottom:1px solid var(--border);
  font-size:10px;color:var(--muted);
}
.strip strong{color:var(--text2)}
.strip-sep{color:var(--border2)}

/* ---- TABLE ---- */
.table-wrap{flex:1;overflow:auto}
table{width:100%;border-collapse:collapse}
thead tr{background:var(--surface);border-bottom:1px solid var(--border2);position:sticky;top:0;z-index:10}
thead th{
  padding:9px 14px;text-align:left;
  font-size:9px;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;
  font-family:'Space Mono',monospace;font-weight:700;white-space:nowrap;
  cursor:pointer;user-select:none;transition:color .15s;
}
thead th:hover{color:var(--text2)}
thead th.sorted{color:var(--green)}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:var(--surface);border-color:var(--border2)}
tbody tr.surr-row{background:rgba(180,126,255,.04)}
tbody tr.surr-row:hover{background:rgba(180,126,255,.08)}
tbody td{padding:10px 14px;font-size:11px;color:var(--text2)}
.td-id{font-size:10px;color:var(--muted)}
.td-name{color:var(--text);font-weight:700}
.td-grower-num{font-size:9px;color:var(--muted);margin-top:1px}
.td-amt{color:var(--green);font-weight:700;font-size:12px}
.td-mono{font-family:'Space Mono',monospace;font-size:10px;color:var(--muted)}
.check-cell{width:32px;padding-left:14px!important}
.check-cell input[type=checkbox]{width:13px;height:13px;accent-color:var(--green);cursor:pointer}

/* ---- BADGES ---- */
.badge{
  display:inline-block;font-size:9px;padding:2px 6px;border-radius:3px;
  border:1px solid;font-family:'Space Mono',monospace;
}
.badge-ok    {background:#0d200d;color:var(--green);border-color:var(--green-dim)}
.badge-warn  {background:var(--amber-dim);color:var(--amber);border-color:#5a3800}
.badge-info  {background:var(--blue-dim);color:var(--blue);border-color:#003050}
.badge-purple{background:var(--purple-dim);color:var(--purple);border-color:#3a1a5e}
.badge-alert {background:var(--red-dim);color:var(--red);border-color:#400000}

/* ---- DRAWER ---- */
.drawer-overlay{position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);display:none}
.drawer-overlay.open{display:block}
.drawer{
  position:fixed;top:0;right:-460px;bottom:0;width:440px;z-index:301;
  background:var(--surface);border-left:1px solid var(--border2);
  transition:right .26s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;
  font-family:'Space Mono',monospace;
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
.drawer-amount{
  font-family:'Syne',sans-serif;font-size:36px;font-weight:800;
  color:var(--green);padding:12px 0 8px;line-height:1;
}
.d-section{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:14px 0 8px;padding-bottom:5px;border-bottom:1px solid var(--border)}
.d-row{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--border);font-size:11px}
.d-row:last-child{border-bottom:none}
.d-label{color:var(--muted);font-size:10px;flex-shrink:0;margin-right:12px;padding-top:1px}
.d-val{color:var(--text);text-align:right;word-break:break-all;font-weight:700}
.drawer-foot{padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px}

/* ---- BULK BAR ---- */
.bulk-bar{
  position:fixed;bottom:0;left:0;right:0;z-index:200;
  background:var(--surface2);border-top:1px solid var(--border2);
  padding:10px 20px;display:flex;align-items:center;gap:10px;
  transform:translateY(100%);transition:transform .2s ease;
  font-family:'Space Mono',monospace;
}
.bulk-bar.show{transform:translateY(0)}
.bulk-n{font-size:11px;color:var(--green);margin-right:4px}
.bulk-sep{flex:1}

/* ---- MODAL ---- */
.modal-bg{position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.72);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{
  background:var(--surface);border:1px solid var(--border2);
  border-radius:var(--radius);padding:22px;width:420px;max-width:92vw;
  position:relative;animation:slideUp .18s ease both;
  font-family:'Space Mono',monospace;
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
  font-size:11px;font-family:'Space Mono',monospace;outline:none;
  margin-bottom:12px;
}
.m-select:focus,.m-input:focus{border-color:var(--green)}
.m-check-group{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.m-check{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--text2);cursor:pointer}
.m-check input{accent-color:var(--green);width:13px;height:13px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}

/* ---- BUTTONS ---- */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:4px;font-size:11px;font-family:'Space Mono',monospace;cursor:pointer;border:none;transition:all .15s;text-decoration:none;white-space:nowrap}
.btn-green{background:var(--green);color:#0a0f0a;font-weight:700}.btn-green:hover{background:#5af080}
.btn-outline{background:transparent;color:var(--muted);border:1px solid var(--border)}.btn-outline:hover{color:var(--green);border-color:var(--green)}
.btn-sm{padding:4px 9px;font-size:10px}

/* ---- EMPTY ---- */
.empty-cell{text-align:center;padding:48px!important;color:var(--muted)!important;font-size:11px!important}

/* ---- ANIMATIONS ---- */
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.stat-card{animation:fadeUp .3s ease both}
.stat-card:nth-child(1){animation-delay:.04s}.stat-card:nth-child(2){animation-delay:.08s}
.stat-card:nth-child(3){animation-delay:.12s}.stat-card:nth-child(4){animation-delay:.16s}
.stat-card:nth-child(5){animation-delay:.20s}.stat-card:nth-child(6){animation-delay:.24s}
.stat-card:nth-child(7){animation-delay:.28s}

/* ---- SCROLLBAR ---- */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--muted)}
</style>
</head>
<body>

<!-- ============================================================ HEADER -->
<header>
  <div class="logo">GMS<span>/</span>Payments</div>
  <a href="reports_hub.php" class="back">← Hub</a>
  <a href="loans_dashboard.php" class="back">📊 Loans</a>
  <a href="loans_report.php"    class="back">📋 Report</a>
  <div class="header-right">
    Season: <?= htmlspecialchars($seasonName) ?> · <?= date('d M Y H:i') ?>
    &nbsp;
    <button class="btn btn-outline btn-sm" onclick="document.getElementById('exportModal').classList.add('open')">↓ Export</button>
  </div>
</header>

<div class="page-wrap">

  <!-- ============================================================ SIDEBAR -->
  <aside class="sidebar">

    <div class="sb-section">
      <div class="sb-title">Amount by Description</div>
      <?php
        $maxD = count($desc_totals) ? max(array_column($desc_totals,'tot')) : 1;
        $fillCls = ['','c1','c2','c3','c4','c5'];
        foreach($desc_totals as $i=>$d):
          $pct = $maxD>0 ? round($d['tot']/$maxD*100) : 0;
      ?>
      <div class="desc-row">
        <div class="desc-hd">
          <span class="desc-name" title="<?= htmlspecialchars($d['description']) ?>"><?= htmlspecialchars($d['description']) ?></span>
          <span class="desc-amt">$<?= number_format($d['tot'],0) ?></span>
        </div>
        <div class="desc-bar">
          <div class="desc-fill <?= $fillCls[$i%6] ?>" data-w="<?= $pct ?>" style="width:0%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($desc_totals)): ?>
        <div style="font-size:10px;color:var(--muted)">No data yet</div>
      <?php endif; ?>
    </div>

    <div class="sb-section">
      <div class="sb-title">Sync Status</div>
      <?php
        $tot = $kpis['total_records']?:1;
        $sp  = round($kpis['synced']/$tot*100);
        $circ= 2*M_PI*24; $sd=$circ*($sp/100);
      ?>
      <div style="display:flex;align-items:center;gap:12px;padding:4px 0 8px">
        <svg width="60" height="60" viewBox="0 0 60 60" style="flex-shrink:0">
          <circle cx="30" cy="30" r="24" fill="none" stroke="var(--border2)" stroke-width="6"/>
          <circle cx="30" cy="30" r="24" fill="none" stroke="var(--green)" stroke-width="6"
            stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $circ-$sd ?>"
            stroke-linecap="butt" transform="rotate(-90 30 30)" opacity=".9"/>
          <text x="30" y="35" text-anchor="middle"
            font-family="'Syne',sans-serif" font-size="13" font-weight="800"
            fill="var(--text2)"><?= $sp ?>%</text>
        </svg>
        <div class="sync-legend">
          <div class="sync-item"><span class="sync-dot" style="background:var(--green)"></span>Synced (<?= $kpis['synced'] ?>)</div>
          <div class="sync-item"><span class="sync-dot" style="background:var(--amber)"></span>Unsynced (<?= $kpis['unsynced'] ?>)</div>
          <div class="sync-item"><span class="sync-dot" style="background:var(--purple)"></span>Surrogate (<?= $kpis['surrogate'] ?>)</div>
        </div>
      </div>
    </div>

    <div class="sb-section">
      <div class="sb-title">Quick Filters</div>
      <button class="qf-btn" onclick="qf('unsynced')">⏳ Unsynced only</button>
      <button class="qf-btn" onclick="qf('surrogate')">👥 Surrogate only</button>
      <button class="qf-btn" onclick="qf('synced')">✓ Synced only</button>
      <button class="qf-btn" onclick="qf('')">↺ Show all</button>
    </div>

  </aside>

  <!-- ============================================================ MAIN -->
  <div class="main">

    <!-- STAT ROW -->
    <div class="stat-row">
      <div class="stat-card">
        <div class="stat-label">Total Paid</div>
        <div class="stat-val" style="color:var(--green)">$<?= number_format($kpis['total_paid'],2) ?></div>
        <div class="stat-sub"><?= number_format($kpis['total_records']) ?> records</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Mass (kg)</div>
        <div class="stat-val" style="color:var(--blue)"><?= number_format($kpis['total_mass'],2) ?></div>
        <div class="stat-sub">Tobacco mass</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Growers Paid</div>
        <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['growers_paid']) ?></div>
        <div class="stat-sub">Distinct growers</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Synced</div>
        <div class="stat-val" style="color:var(--green)"><?= number_format($kpis['synced']) ?></div>
        <div class="stat-sub">On server</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Unsynced</div>
        <div class="stat-val" style="color:var(--amber)"><?= number_format($kpis['unsynced']) ?></div>
        <div class="stat-sub">Pending sync</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Surrogate</div>
        <div class="stat-val" style="color:var(--purple)"><?= number_format($kpis['surrogate']) ?></div>
        <div class="stat-sub">On behalf</div>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <input class="f-input" type="text" id="searchInput" placeholder="🔍  Grower, ref, receipt, description…" oninput="applyFilters()">
      <select class="f-select" id="officerFilter" onchange="applyFilters()">
        <option value="">All Officers</option>
        <?php foreach($officers as $o): ?>
        <option value="<?= $o['userid'] ?>"><?= htmlspecialchars($o['oname']) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="f-input" type="date" id="dateFrom" style="min-width:auto;width:126px" onchange="applyFilters()">
      <input class="f-input" type="date" id="dateTo"   style="min-width:auto;width:126px" onchange="applyFilters()">
      <div class="tab-group">
        <button class="tab active" onclick="setSyncFilter('all',this)">All</button>
        <button class="tab" onclick="setSyncFilter('synced',this)">Synced</button>
        <button class="tab" onclick="setSyncFilter('unsynced',this)">Unsynced</button>
        <button class="tab" onclick="setSyncFilter('surrogate',this)">Surrogate</button>
      </div>
      <span class="f-count" id="fCount"><?= count($payments) ?> records</span>
    </div>

    <!-- SUMMARY STRIP -->
    <div class="strip">
      <span>Showing <strong id="sCount"><?= count($payments) ?></strong></span>
      <span class="strip-sep">·</span>
      <span>Amount: <strong id="sAmt" style="color:var(--green)">$<?= number_format($totAmt,2) ?></strong></span>
      <span class="strip-sep">·</span>
      <span>Mass: <strong id="sMass" style="color:var(--blue)"><?= number_format($totMass,2) ?> kg</strong></span>
      <span class="strip-sep">·</span>
      <span>Synced: <strong id="sSynced" style="color:var(--green)"><?= $nSynced ?></strong></span>
      <span class="strip-sep">·</span>
      <span>Unsynced: <strong id="sUnsync" style="color:var(--amber)"><?= $nUnsync ?></strong></span>
      <span class="strip-sep">·</span>
      <span>Surrogate: <strong id="sSurr" style="color:var(--purple)"><?= $nSurr ?></strong></span>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="check-cell"><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th>
            <th onclick="sortTable('id')">#</th>
            <th onclick="sortTable('grower')">Grower</th>
            <th onclick="sortTable('officer')">Field Officer</th>
            <th onclick="sortTable('desc')">Description</th>
            <th onclick="sortTable('amount')">Amount ($)</th>
            <th onclick="sortTable('mass')">Mass (kg)</th>
            <th onclick="sortTable('bales')">Bales</th>
            <th onclick="sortTable('ref')">Reference No.</th>
            <th onclick="sortTable('receipt')">Receipt No.</th>
            <th onclick="sortTable('datetime')">Date / Time</th>
            <th onclick="sortTable('created')">Created At</th>
            <th>Sync</th>
            <th>Surrogate</th>
          </tr>
        </thead>
        <tbody id="payBody">
          <?php if(empty($payments)): ?>
          <tr><td colspan="14" class="empty-cell">No payment records found for this season.</td></tr>
          <?php else: foreach($payments as $p):
            $isSurr  = (int)$p['surrogate']===1;
            $isSynced= (int)$p['sync']===1;
            $dt      = !empty($p['datetime']) ? date('d M Y  H:i',strtotime($p['datetime'])) : '—';
          ?>
          <tr
            class="<?= $isSurr?'surr-row':'' ?>"
            data-id="<?= $p['id'] ?>"
            data-grower="<?= strtolower(htmlspecialchars(trim(($p['name']??'').' '.($p['surname']??'')))) ?>"
            data-officer="<?= $p['userid'] ?>"
            data-desc="<?= strtolower(htmlspecialchars($p['description'])) ?>"
            data-amount="<?= $p['amount'] ?>"
            data-mass="<?= $p['mass'] ?>"
            data-bales="<?= $p['bales'] ?>"
            data-ref="<?= strtolower($p['reference_num']) ?>"
            data-receipt="<?= strtolower($p['receipt_number']) ?>"
            data-datetime="<?= $p['datetime'] ?>"
            data-created="<?= htmlspecialchars($p['created_at']) ?>"
            data-sync="<?= $p['sync'] ?>"
            data-surrogate="<?= $p['surrogate'] ?>"
            data-userid="<?= $p['userid'] ?>"
            onclick="openDrawer(this)"
          >
            <td class="check-cell" onclick="event.stopPropagation()">
              <input type="checkbox" class="row-check" onchange="updateBulk()">
            </td>
            <td class="td-id"><?= $p['id'] ?></td>
            <td>
              <div class="td-name"><?= htmlspecialchars(trim($p['name'].' '.$p['surname'])) ?: '#'.$p['growerid'] ?></div>
              <div class="td-grower-num"><?= htmlspecialchars($p['grower_num'] ?? '') ?></div>
            </td>
            <td style="font-size:11px" class="td-mono"><?= $p['userid'] ?></td>
            <td style="font-size:11px;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($p['description']) ?>">
              <?= htmlspecialchars($p['description']) ?>
            </td>
            <td class="td-amt">$<?= number_format($p['amount'],2) ?></td>
            <td class="td-mono"><?= number_format($p['mass'],2) ?></td>
            <td class="td-mono"><?= number_format($p['bales'],0) ?></td>
            <td class="td-mono"><?= htmlspecialchars($p['reference_num']) ?></td>
            <td class="td-mono"><?= htmlspecialchars($p['receipt_number']) ?></td>
            <td class="td-mono" style="white-space:nowrap"><?= $dt ?></td>
            <td class="td-mono" style="font-size:9px"><?= htmlspecialchars($p['created_at']) ?></td>
            <td>
              <?= $isSynced
                ? '<span class="badge badge-ok">Synced</span>'
                : '<span class="badge badge-warn">Pending</span>' ?>
            </td>
            <td>
              <?= $isSurr
                ? '<span class="badge badge-purple">Yes</span>'
                : '<span class="badge badge-info">No</span>' ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div><!-- /table-wrap -->

  </div><!-- /main -->
</div><!-- /page-wrap -->

<!-- ============================================================ BULK BAR -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-n" id="bulkN">0 selected</span>
  <button class="btn btn-outline btn-sm" onclick="document.getElementById('exportModal').classList.add('open')">↓ Export Selection</button>
  <div class="bulk-sep"></div>
  <button class="btn btn-outline btn-sm" onclick="clearAll()">✕ Clear</button>
</div>

<!-- ============================================================ DETAIL DRAWER -->
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
    <div id="dBadges" style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:4px"></div>

    <div class="d-section">Payment Details</div>
    <div class="d-row"><span class="d-label">Grower Number</span><span class="d-val td-mono" id="dGrowerNumber">—</span></div>
    <div class="d-row"><span class="d-label">Description</span><span class="d-val" id="dDesc">—</span></div>
    <div class="d-row"><span class="d-label">Mass (kg)</span><span class="d-val" id="dMass">—</span></div>
    <div class="d-row"><span class="d-label">Bales</span><span class="d-val" id="dBales">—</span></div>
    <div class="d-row"><span class="d-label">Field Officer</span><span class="d-val" id="dOfficer">—</span></div>

    <div class="d-section">References</div>
    <div class="d-row"><span class="d-label">Reference No.</span><span class="d-val td-mono" id="dRef">—</span></div>
    <div class="d-row"><span class="d-label">Receipt No.</span><span class="d-val td-mono" id="dReceipt">—</span></div>

    <div class="d-section">Timestamps</div>
    <div class="d-row"><span class="d-label">Payment DateTime</span><span class="d-val td-mono" id="dDatetime">—</span></div>
    <div class="d-row"><span class="d-label">Created At</span><span class="d-val td-mono" id="dCreated">—</span></div>

    <div class="d-section">System Flags</div>
    <div class="d-row"><span class="d-label">Sync Status</span><span class="d-val" id="dSyncSt">—</span></div>
    <div class="d-row"><span class="d-label">Surrogate</span><span class="d-val" id="dSurrSt">—</span></div>
    <div class="d-row"><span class="d-label">Season ID</span><span class="d-val td-mono" id="dSeasonId">—</span></div>
    <div class="d-row"><span class="d-label">User ID</span><span class="d-val td-mono" id="dUserId">—</span></div>
  </div>
  <div class="drawer-foot">
    <button class="btn btn-outline" style="flex:1" onclick="closeDrawer()">Close</button>
  </div>
</div>

<!-- ============================================================ EXPORT MODAL -->
<div class="modal-bg" id="exportModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('exportModal').classList.remove('open')">✕</button>
    <div class="modal-title">Export Payments</div>
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
      <label class="m-check"><input type="checkbox" checked> Reference &amp; receipt numbers</label>
      <label class="m-check"><input type="checkbox" checked> Amount &amp; mass</label>
      <label class="m-check"><input type="checkbox" checked> Description</label>
      <label class="m-check"><input type="checkbox" checked> Sync &amp; surrogate flags</label>
      <label class="m-check"><input type="checkbox" checked> Payment datetime &amp; created_at</label>
      <label class="m-check"><input type="checkbox"> Officer breakdown sheet</label>
    </div>

    <label class="m-label">Group By</label>
    <select class="m-select">
      <option>Date</option><option>Grower</option><option>Field Officer</option>
      <option>Description</option><option>Sync Status</option>
    </select>

    <div class="modal-actions">
      <button class="btn btn-outline" onclick="document.getElementById('exportModal').classList.remove('open')">Cancel</button>
      <button class="btn btn-green" onclick="document.getElementById('exportModal').classList.remove('open')">↓ Download</button>
    </div>
  </div>
</div>

<script>
/* ---- ROW DATA ---- */
const rowData = <?php
  $js=[];
  foreach($payments as $p){
    $js[]=[
      'id'=>$p['id'],'grower'=>trim(($p['name']??'').' '.($p['surname']??'')) ?: '#'.$p['growerid'],'grower_num'=>$p['grower_num']??'',
      'officer'=>$p['userid'],'desc'=>$p['description'],
      'amount'=>$p['amount'],'mass'=>$p['mass'],'bales'=>$p['bales'],
      'ref'=>$p['reference_num'],'receipt'=>$p['receipt_number'],
      'datetime'=>$p['datetime'],'created_at'=>$p['created_at'],
      'sync'=>(int)$p['sync'],'surrogate'=>(int)$p['surrogate'],
      'growerid'=>$p['growerid'],'seasonid'=>$p['seasonid'],'userid'=>$p['userid'],
    ];
  }
  echo json_encode($js);
?>;

/* ---- FILTER STATE ---- */
let syncFilter = 'all';

function setSyncFilter(val, el){
  syncFilter = val;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  applyFilters();
}
function qf(val){
  syncFilter = val === '' ? 'all' : val;
  document.querySelectorAll('.tab').forEach(t => {
    const map = {'all':'All','synced':'Synced','unsynced':'Unsynced','surrogate':'Surrogate'};
    t.classList.toggle('active', t.textContent.trim() === (map[syncFilter]||'All'));
  });
  applyFilters();
}

function applyFilters(){
  const search = document.getElementById('searchInput').value.toLowerCase();
  const dfrom  = document.getElementById('dateFrom').value;
  const dto    = document.getElementById('dateTo').value;
  let cnt=0, totAmt=0, totMass=0, synced=0, unsynced=0, surrogate=0;

  document.querySelectorAll('#payBody tr[data-id]').forEach(row => {
    const rs   = row.dataset.grower+row.dataset.officer+row.dataset.ref+row.dataset.receipt+row.dataset.desc;
    const rsy  = parseInt(row.dataset.sync);
    const rsu  = parseInt(row.dataset.surrogate);
    const rdt  = row.dataset.datetime ? row.dataset.datetime.substring(0,10) : '';
    let show   = true;
    if(search && !rs.includes(search))           show = false;
    if(dfrom  && rdt && rdt < dfrom)             show = false;
    if(dto    && rdt && rdt > dto)               show = false;
    if(syncFilter==='synced'    && rsy!==1)      show = false;
    if(syncFilter==='unsynced'  && rsy!==0)      show = false;
    if(syncFilter==='surrogate' && rsu!==1)      show = false;
    row.style.display = show ? '' : 'none';
    if(show){
      cnt++;
      totAmt  += parseFloat(row.dataset.amount)||0;
      totMass += parseFloat(row.dataset.mass)||0;
      if(rsy===1) synced++; else unsynced++;
      if(rsu===1) surrogate++;
    }
  });

  const fmt = n => n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
  document.getElementById('fCount').textContent  = cnt+' records';
  document.getElementById('sCount').textContent  = cnt;
  document.getElementById('sAmt').textContent    = '$'+fmt(totAmt);
  document.getElementById('sMass').textContent   = fmt(totMass)+' kg';
  document.getElementById('sSynced').textContent = synced;
  document.getElementById('sUnsync').textContent = unsynced;
  document.getElementById('sSurr').textContent   = surrogate;
}

/* ---- SORT ---- */
let sortKey='', sortDir=1;
function sortTable(key){
  if(sortKey===key) sortDir*=-1; else{sortKey=key;sortDir=1;}
  const tbody = document.getElementById('payBody');
  const trows = [...tbody.querySelectorAll('tr[data-id]')];
  trows.sort((a,b)=>{
    let av=a.dataset[key]||'', bv=b.dataset[key]||'';
    if(key==='amount'||key==='mass'){av=parseFloat(av);bv=parseFloat(bv);}
    return av<bv?-sortDir:av>bv?sortDir:0;
  });
  trows.forEach(r=>tbody.appendChild(r));
  document.querySelectorAll('thead th').forEach(t=>t.classList.remove('sorted'));
}

/* ---- CHECKBOXES ---- */
function toggleAll(cb){ document.querySelectorAll('.row-check').forEach(c=>c.checked=cb.checked); updateBulk(); }
function updateBulk(){
  const n = document.querySelectorAll('.row-check:checked').length;
  document.getElementById('bulkN').textContent = n+' selected';
  document.getElementById('bulkBar').classList.toggle('show', n>0);
}
function clearAll(){
  document.querySelectorAll('.row-check,#checkAll').forEach(c=>c.checked=false);
  document.getElementById('bulkBar').classList.remove('show');
}

/* ---- DRAWER ---- */
function openDrawer(row){
  const rec = rowData.find(r=>String(r.id)===String(row.dataset.id));
  if(!rec) return;
  const fmt = n => parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');

  document.getElementById('dId').textContent         = rec.id;
  document.getElementById('dGrower').textContent     = rec.grower || ('Grower ID: ' + rec.growerid);
  document.getElementById('dGrowerNum').textContent  = rec.grower_num ? rec.grower_num : '';
  document.getElementById('dAmount').textContent     = '$'+fmt(rec.amount);
  document.getElementById('dGrowerNumber').textContent = rec.grower_num || '—';
  document.getElementById('dDesc').textContent         = rec.desc;
  document.getElementById('dBales').textContent        = rec.bales || '0';
  document.getElementById('dMass').textContent       = fmt(rec.mass)+' kg';
  document.getElementById('dOfficer').textContent    = rec.officer;
  document.getElementById('dRef').textContent        = rec.ref;
  document.getElementById('dReceipt').textContent    = rec.receipt;
  document.getElementById('dCreated').textContent    = rec.created_at;
  document.getElementById('dSeasonId').textContent   = rec.seasonid;
  document.getElementById('dUserId').textContent     = rec.userid;

  const dt = rec.datetime ? new Date(rec.datetime) : null;
  document.getElementById('dDatetime').textContent = dt
    ? dt.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})
    : '—';

  document.getElementById('dBadges').innerHTML =
    (rec.sync===1
      ? '<span class="badge badge-ok">Synced</span>'
      : '<span class="badge badge-warn">Unsynced</span>') +
    (rec.surrogate===1
      ? '<span class="badge badge-purple">Surrogate</span>'
      : '<span class="badge badge-info">Direct</span>');

  document.getElementById('dSyncSt').innerHTML = rec.sync===1
    ? '<span style="color:var(--green)">✓ Synced to server</span>'
    : '<span style="color:var(--amber)">⏳ Pending sync</span>';
  document.getElementById('dSurrSt').innerHTML = rec.surrogate===1
    ? '<span style="color:var(--purple)">Yes — captured on behalf</span>'
    : '<span style="color:var(--muted)">No — direct entry</span>';

  document.getElementById('drawerOv').classList.add('open');
  document.getElementById('drawer').classList.add('open');
}
function closeDrawer(){
  document.getElementById('drawerOv').classList.remove('open');
  document.getElementById('drawer').classList.remove('open');
}

/* ---- MODAL BACKDROP ---- */
document.querySelectorAll('.modal-bg').forEach(m=>{
  m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});
});

/* ---- ANIMATE BARS ---- */
window.addEventListener('load',()=>{
  document.querySelectorAll('.desc-fill[data-w]').forEach(el=>{
    setTimeout(()=>el.style.width=el.dataset.w+'%', 150);
  });
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>
