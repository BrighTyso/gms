<?php
/**
 * questionnaire_pivot.php
 * GMS — Questionnaire Answers Pivot Table
 */
ob_start();
if(session_status()===PHP_SESSION_NONE) session_start();
require "conn.php";
require "validate.php";
date_default_timezone_set('Africa/Harare');
$conn->query("SET time_zone = '+02:00'");

// ── Active season ──────────────────────────────────────────────────────────────
$seasonId=0; $seasonName='—';
$r=$conn->query("SELECT id,name FROM seasons WHERE active=1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$seasonName=$row['name'];$r->free();}

// ── Filters ────────────────────────────────────────────────────────────────────
$selSeason  = isset($_GET['season_id'])&&$_GET['season_id']!==''?(int)$_GET['season_id']:$seasonId;
$selOfficer = isset($_GET['officer_id'])&&$_GET['officer_id']!==''?(int)$_GET['officer_id']:null;
$selArea    = isset($_GET['area'])&&$_GET['area']!=='' ? $conn->real_escape_string($_GET['area']) : '';
$search     = isset($_GET['q'])&&$_GET['q']!=='' ? $conn->real_escape_string(trim($_GET['q'])) : '';
$export     = isset($_GET['export']) && $_GET['export']==='csv';
$qDateFrom  = isset($_GET['q_date_from'])&&$_GET['q_date_from']!=='' ? date('Y-m-d',strtotime($_GET['q_date_from'])) : '';
$qDateTo    = isset($_GET['q_date_to'])&&$_GET['q_date_to']!==''     ? date('Y-m-d',strtotime($_GET['q_date_to']))   : '';
$aDateFrom  = isset($_GET['a_date_from'])&&$_GET['a_date_from']!=='' ? date('Y-m-d',strtotime($_GET['a_date_from'])) : '';
$aDateTo    = isset($_GET['a_date_to'])&&$_GET['a_date_to']!==''     ? date('Y-m-d',strtotime($_GET['a_date_to']))   : '';

// Dropdowns
$allSeasons=[]; $r=$conn->query("SELECT id,name FROM seasons ORDER BY id DESC");
if($r){while($row=$r->fetch_assoc()) $allSeasons[]=$row; $r->free();}
$allOfficers=[]; $r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}
$allAreas=[]; $r=$conn->query("SELECT DISTINCT area FROM growers WHERE area IS NOT NULL AND area!='' ORDER BY area");
if($r){while($row=$r->fetch_assoc()) $allAreas[]=$row['area']; $r->free();}

// ── Step 1: Distinct questions ────────────────────────────────────────────────
// Also fetch question_created_at so we can group by date in JS
$questions=[]; // [question => ['count'=>n, 'q_date'=>'YYYY-MM-DD']]
$_qSql = "SELECT qa.question, MIN(qa.question_created_at) AS q_date, COUNT(DISTINCT qa.growerid) AS cnt
    FROM questionnaires_answers_by_grower qa WHERE qa.seasonid={$selSeason}";
if($qDateFrom) $_qSql .= " AND DATE(STR_TO_DATE(qa.question_created_at,'%Y-%m-%d'))>='{$qDateFrom}'";
if($qDateTo)   $_qSql .= " AND DATE(STR_TO_DATE(qa.question_created_at,'%Y-%m-%d'))<='{$qDateTo}'";
if($aDateFrom) $_qSql .= " AND DATE(STR_TO_DATE(qa.created_at,'%Y-%m-%d'))>='{$aDateFrom}'";
if($aDateTo)   $_qSql .= " AND DATE(STR_TO_DATE(qa.created_at,'%Y-%m-%d'))<='{$aDateTo}'";
$_qSql .= " GROUP BY qa.question ORDER BY MIN(qa.question_created_at) DESC, qa.question ASC";
$r=$conn->query($_qSql);
while($r&&$row=$r->fetch_assoc()){
    $questions[$row['question']]=['count'=>(int)$row['cnt'],'q_date'=>substr($row['q_date'],0,10)];
}
$questionKeys=array_keys($questions);

// ── Step 2: WHERE for main data query ─────────────────────────────────────────
$whereParts=["qa.seasonid={$selSeason}"];
if($selOfficer) $whereParts[]="qa.userid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)";
if($selArea)    $whereParts[]="g.area='".$conn->real_escape_string($selArea)."'";
if($search)     $whereParts[]="(g.name LIKE '%{$search}%' OR g.surname LIKE '%{$search}%' OR g.grower_num LIKE '%{$search}%')";
if($qDateFrom)  $whereParts[]="DATE(STR_TO_DATE(qa.question_created_at,'%Y-%m-%d'))>='{$qDateFrom}'";
if($qDateTo)    $whereParts[]="DATE(STR_TO_DATE(qa.question_created_at,'%Y-%m-%d'))<='{$qDateTo}'";
if($aDateFrom)  $whereParts[]="DATE(STR_TO_DATE(qa.created_at,'%Y-%m-%d'))>='{$aDateFrom}'";
if($aDateTo)    $whereParts[]="DATE(STR_TO_DATE(qa.created_at,'%Y-%m-%d'))<='{$aDateTo}'";
$whereStr=implode(' AND ',$whereParts);

// ── Step 3: Fetch and pivot ───────────────────────────────────────────────────
$answerMap=[]; $growerMeta=[];
$r=$conn->query("
    SELECT qa.growerid, qa.question, qa.answer, qa.created_at,
        g.grower_num, CONCAT(g.name,' ',g.surname) AS grower_name,
        g.area, CONCAT(u.name,' ',u.surname) AS officer_name
    FROM questionnaires_answers_by_grower qa
    JOIN growers g ON g.id=qa.growerid
    JOIN users u ON u.id=qa.userid
    WHERE {$whereStr}
    ORDER BY g.name, g.surname, qa.question
");
while($r&&$row=$r->fetch_assoc()){
    $gid=$row['growerid'];
    $answerMap[$gid][$row['question']]=['val'=>$row['answer'],'date'=>substr($row['created_at'],0,10)];
    if(!isset($growerMeta[$gid])){
        $growerMeta[$gid]=['grower_num'=>$row['grower_num'],'grower_name'=>$row['grower_name'],
            'area'=>$row['area'],'officer_name'=>$row['officer_name'],'last_answered'=>$row['created_at']];
    } elseif($row['created_at']>$growerMeta[$gid]['last_answered']){
        $growerMeta[$gid]['last_answered']=$row['created_at'];
    }
}
$totalGrowers=count($growerMeta);
$totalQuestions=count($questionKeys);

// Completion per grower
$completionStats=[];
foreach($growerMeta as $gid=>$m){
    $answered=0;
    foreach($questionKeys as $q) if(!empty($answerMap[$gid][$q]['val'])) $answered++;
    $completionStats[$gid]=$totalQuestions>0?round($answered/$totalQuestions*100):0;
}

// Answer frequency per question (for detail panel)
$qFreq=[];
foreach($questionKeys as $q){
    $freq=[];
    foreach($answerMap as $gid=>$ans){
        $v=$ans[$q]['val']??'';
        if($v!=='') $freq[$v]=($freq[$v]??0)+1;
    }
    arsort($freq);
    $qFreq[$q]=$freq;
}

// CSV export
if($export){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="questionnaire_'.date('Y-m-d').'.csv"');
    $out=fopen('php://output','w');
    $hdr=['Grower #','Grower Name','Area','Officer','Completion %'];
    foreach($questionKeys as $q) $hdr[]=$q;
    $hdr[]='Last Answered';
    fputcsv($out,$hdr);
    foreach($growerMeta as $gid=>$m){
        $row=[$m['grower_num'],$m['grower_name'],$m['area'],$m['officer_name'],$completionStats[$gid].'%'];
        foreach($questionKeys as $q) $row[]=$answerMap[$gid][$q]['val']??'';
        $row[]=date('d M Y',strtotime($m['last_answered']));
        fputcsv($out,$row);
    }
    fclose($out); exit;
}

// Pass data to JS
$jsQuestions=json_encode($questionKeys);
$jsQMeta=json_encode($questions);
$jsAnswerMap=json_encode($answerMap);
$jsGrowerMeta=json_encode($growerMeta);
$jsCompletion=json_encode($completionStats);
$jsQFreq=json_encode($qFreq);

// Active filter count for badge
$activeFilters=0;
if($selOfficer) $activeFilters++;
if($selArea) $activeFilters++;
if($search) $activeFilters++;
if($qDateFrom||$qDateTo) $activeFilters++;
if($aDateFrom||$aDateTo) $activeFilters++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Questionnaire Pivot</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;
    --green:#3ddc68;--green-dim:rgba(61,220,104,.1);--amber:#f5a623;--red:#e84040;
    --blue:#4a9eff;--purple:#b47eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;
    --radius:6px;--radius2:4px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:12px;}

/* ── Header ── */
header{display:flex;align-items:center;gap:8px;padding:0 16px;height:52px;
    background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:200;}
.logo{font-family:'Syne',sans-serif;font-size:17px;font-weight:900;color:var(--green);}
.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);
    padding:3px 8px;border-radius:var(--radius2);transition:all .2s;}
.back:hover{color:var(--green);border-color:var(--green);}
.hbtn{padding:5px 12px;border-radius:var(--radius2);border:none;cursor:pointer;
    font-family:'Space Mono',monospace;font-size:10px;font-weight:700;transition:all .2s;}
.hbtn-green{background:var(--green);color:#000;}
.hbtn-green:hover{background:#2ab854;}
.hbtn-blue{background:rgba(74,158,255,.15);color:var(--blue);border:1px solid rgba(74,158,255,.3);}
.hbtn-blue:hover{background:rgba(74,158,255,.25);}
.filter-badge{background:var(--amber);color:#000;font-size:8px;font-weight:700;
    width:16px;height:16px;border-radius:50%;display:inline-flex;align-items:center;
    justify-content:center;margin-left:-6px;margin-top:-8px;vertical-align:top;}

/* ── Filter Panel (collapsible) ── */
.filter-panel{
    background:var(--surface);border-bottom:1px solid var(--border2);
    overflow:hidden;transition:max-height .35s ease,opacity .25s ease;
    max-height:0;opacity:0;
}
.filter-panel.open{max-height:400px;opacity:1;}
.filter-inner{padding:16px 20px;display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;}
.fi-group{display:flex;flex-direction:column;gap:4px;}
.fi-label{font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);}

/* ── Styled inputs ── */
.gms-select,.gms-input,.gms-date{
    background:var(--surface2);border:1px solid var(--border2);color:var(--text);
    border-radius:var(--radius2);padding:6px 10px;font-family:'Space Mono',monospace;
    font-size:11px;outline:none;transition:border-color .2s;
    appearance:none;-webkit-appearance:none;
}
.gms-select:focus,.gms-input:focus,.gms-date:focus{border-color:var(--green);box-shadow:0 0 0 2px rgba(61,220,104,.1);}
.gms-select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%234a6b4a'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 8px center;padding-right:24px;}

/* Date range group */
.date-group{display:flex;align-items:center;gap:6px;}
.date-separator{font-size:10px;color:var(--muted);}

/* Coloured date group labels */
.fi-label-blue{color:var(--blue);}
.fi-label-green{color:var(--green);}
.gms-date-blue{border-color:rgba(74,158,255,.3);}
.gms-date-blue:focus{border-color:var(--blue);box-shadow:0 0 0 2px rgba(74,158,255,.1);}
.gms-date-green{border-color:rgba(61,220,104,.3);}
.gms-date-green:focus{border-color:var(--green);box-shadow:0 0 0 2px rgba(61,220,104,.1);}

/* Calendar icon colour override for webkit */
input[type=date]::-webkit-calendar-picker-indicator{filter:invert(.4);}

/* Divider in filter */
.fi-divider{width:1px;background:var(--border2);align-self:stretch;margin:0 4px;}

/* Filter action buttons */
.fi-actions{display:flex;gap:8px;padding-bottom:2px;}
.fbtn{padding:6px 14px;border-radius:var(--radius2);border:none;cursor:pointer;
    font-family:'Space Mono',monospace;font-size:10px;font-weight:700;}
.fbtn-apply{background:var(--green);color:#000;}
.fbtn-apply:hover{background:#2ab854;}
.fbtn-reset{background:transparent;border:1px solid var(--border);color:var(--muted);}
.fbtn-reset:hover{border-color:var(--green);color:var(--green);}

/* Active filter pills */
.active-pills{display:flex;gap:6px;flex-wrap:wrap;padding:8px 20px;
    background:var(--surface);border-bottom:1px solid var(--border);}
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
    border-radius:12px;font-size:9px;font-weight:700;}
.pill-blue{background:rgba(74,158,255,.1);color:var(--blue);border:1px solid rgba(74,158,255,.25);}
.pill-green{background:rgba(61,220,104,.1);color:var(--green);border:1px solid rgba(61,220,104,.25);}
.pill-amber{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.25);}

/* ── Page layout ── */
.page{padding:16px 20px 60px;}

/* KPI row */
.kpi-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:11px 15px;min-width:120px;}
.kpi-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:900;line-height:1;margin-bottom:2px;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);}

/* ── Question chips bar ── */
.q-bar-wrap{margin-bottom:14px;}
.q-bar-header{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.q-bar-title{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.q-bar-toggle{font-size:9px;color:var(--blue);cursor:pointer;background:none;border:none;
    font-family:'Space Mono',monospace;padding:0;transition:color .2s;}
.q-bar-toggle:hover{color:var(--green);}
.q-chips-outer{overflow:hidden;transition:max-height .3s ease;max-height:500px;}
.q-chips-outer.collapsed{max-height:44px;}
.q-chips{display:flex;gap:6px;flex-wrap:wrap;}
.q-chip{
    display:inline-flex;align-items:center;gap:5px;padding:5px 11px;
    border-radius:20px;font-size:10px;font-weight:700;cursor:pointer;
    border:1px solid var(--border2);color:var(--muted);background:var(--surface);
    white-space:nowrap;transition:all .18s;user-select:none;
}
.q-chip:hover{border-color:var(--green);color:var(--green);background:var(--green-dim);}
.q-chip.active{background:var(--green-dim);color:var(--green);border-color:var(--green);}
.q-chip.all-chip{color:var(--text);border-color:var(--border2);}
.q-chip.all-chip.active{background:var(--surface2);border-color:var(--dim);color:var(--text);}
.q-cnt{font-size:9px;background:var(--surface2);padding:1px 5px;border-radius:8px;color:var(--muted);}
.q-chip.active .q-cnt{background:rgba(61,220,104,.2);color:var(--green);}
.q-date-badge{font-size:8px;color:var(--muted);margin-left:2px;opacity:.7;}

/* Question date in chip */
.q-chip-date{
    font-size:8px;color:var(--blue);background:rgba(74,158,255,.12);
    border:1px solid rgba(74,158,255,.2);padding:1px 5px;border-radius:6px;
    white-space:nowrap;flex-shrink:0;
}
.q-chip.active .q-chip-date{color:var(--green);background:rgba(61,220,104,.15);border-color:rgba(61,220,104,.3);}

/* Question date in column header */
.q-hdr-date{
    display:block;font-size:8px;color:var(--blue);text-align:center;
    writing-mode:horizontal-tb;transform:none;
    margin-top:3px;margin-bottom:1px;
    background:rgba(74,158,255,.1);border-radius:3px;padding:1px 3px;
}
.q-header.active-col .q-hdr-date{color:var(--green);background:rgba(61,220,104,.15);}

/* ── Single question detail panel ── */
#sq-panel{display:none;margin-bottom:14px;}
#sq-panel.visible{display:block;}
.sq-header{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:var(--green);
    padding:11px 16px;background:var(--green-dim);border:1px solid rgba(61,220,104,.25);
    border-radius:var(--radius) var(--radius) 0 0;display:flex;align-items:center;gap:10px;}
.sq-body{background:var(--surface);border:1px solid rgba(61,220,104,.15);
    border-top:none;border-radius:0 0 var(--radius) var(--radius);padding:14px 16px;}
.sq-dist{display:flex;flex-wrap:wrap;gap:16px;}
.sq-item{min-width:150px;}
.sq-item-label{font-size:10px;font-weight:700;color:var(--text);margin-bottom:4px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;}
.sq-item-bar{height:7px;background:var(--surface2);border-radius:4px;overflow:hidden;margin-bottom:3px;}
.sq-item-fill{height:100%;border-radius:4px;transition:width .5s ease;}
.sq-item-sub{font-size:9px;color:var(--muted);}

/* ── Table ── */
.table-outer{overflow:auto;border-radius:var(--radius);border:1px solid var(--border);}
.table-outer.full-height{max-height:none;}
.table-outer.limited{max-height:calc(100vh - 300px);}
table{border-collapse:collapse;font-size:11px;white-space:nowrap;}

thead th{
    position:sticky;top:0;z-index:3;
    background:var(--surface2);padding:8px 10px;
    font-size:9px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);
    border-bottom:2px solid var(--border2);border-right:1px solid var(--border);
    vertical-align:bottom;
}
/* Sticky first cols header */
thead th:nth-child(1){position:sticky;left:0;top:0;z-index:5;min-width:36px;}
thead th:nth-child(2){position:sticky;left:36px;top:0;z-index:5;min-width:150px;}
thead th:nth-child(3){position:sticky;left:186px;top:0;z-index:5;min-width:90px;}
thead th:nth-child(4){position:sticky;left:276px;top:0;z-index:5;min-width:120px;}
thead th.th-done{position:sticky;left:396px;top:0;z-index:5;min-width:70px;}

/* Question column header — rotated */
.q-header{max-width:90px;min-width:72px;padding:6px 6px 8px !important;vertical-align:bottom !important;}
.q-header-inner{writing-mode:vertical-rl;transform:rotate(180deg);max-height:90px;
    overflow:hidden;text-overflow:ellipsis;font-size:9px;line-height:1.3;color:var(--dim);padding-bottom:3px;}
.q-header.active-col .q-header-inner{color:var(--green);}
.q-header.active-col{background:rgba(61,220,104,.06) !important;}
.q-cnt-hdr{font-size:8px;color:var(--muted);display:block;margin-top:3px;
    writing-mode:horizontal-tb;transform:none;text-align:center;}

/* Body cells */
tbody td{padding:8px 10px;border-bottom:1px solid rgba(28,46,28,.5);
    border-right:1px solid rgba(28,46,28,.3);color:var(--dim);vertical-align:middle;}
tbody tr:hover td{background:rgba(61,220,104,.02);color:var(--text);}
tbody tr:hover td.sc{background:var(--surface2);}

/* Sticky body cols */
.sc{background:var(--surface);position:sticky;z-index:2;}
td.sc1{left:0;min-width:36px;text-align:center;color:var(--muted);font-size:10px;}
td.sc2{left:36px;min-width:150px;font-weight:700;color:var(--text);}
td.sc3{left:186px;min-width:90px;font-size:10px;}
td.sc4{left:276px;min-width:120px;font-size:10px;}
td.sc5{left:396px;min-width:70px;}

/* Answer cells */
.ans-cell{text-align:center;min-width:72px;}
.ans-cell.active-col{background:rgba(61,220,104,.04);}
.ans-val{display:inline-block;max-width:86px;overflow:hidden;text-overflow:ellipsis;font-size:10px;}
.ans-num{color:var(--blue);}
.ans-yes{color:var(--green);}
.ans-no{color:var(--red);}
.ans-txt{color:var(--dim);}
.ans-empty{color:var(--border2);font-size:14px;}

/* Completion bar */
.comp-wrap{display:flex;align-items:center;gap:5px;}
.comp-bar{width:38px;height:4px;background:var(--surface2);border-radius:2px;overflow:hidden;}
.comp-fill{height:100%;border-radius:2px;}

/* Legend */
.legend-row{margin-top:10px;font-size:10px;color:var(--muted);display:flex;gap:14px;flex-wrap:wrap;}

/* Empty */
.empty-state{text-align:center;padding:60px;color:var(--muted);}

@keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-track{background:var(--bg);}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px;}
</style>
</head>
<body>

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<header>
    <div class="logo">GMS<span>/</span>Questionnaire</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="grower_risk.php" class="back">🎯 Risk</a>

    <!-- Filter toggle button -->
    <button class="hbtn hbtn-green" id="filter-toggle-btn" onclick="toggleFilters()" style="display:flex;align-items:center;gap:6px;">
        ⚙ Filters
        <?php if($activeFilters>0): ?>
        <span class="filter-badge"><?=$activeFilters?></span>
        <?php endif; ?>
    </button>

    <?php if($totalGrowers>0): ?>
    <a href="?<?=http_build_query(array_merge($_GET,['export'=>'csv']))?>" class="hbtn hbtn-blue">⬇ CSV</a>
    <?php endif; ?>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>

<!-- ── Collapsible Filter Panel ─────────────────────────────────────────────── -->
<div class="filter-panel" id="filter-panel">
<form method="GET" class="filter-inner" id="filter-form">

    <div class="fi-group">
        <label class="fi-label">Season</label>
        <select name="season_id" class="gms-select" style="width:130px;">
            <?php foreach($allSeasons as $s): ?>
            <option value="<?=$s['id']?>" <?=$selSeason==$s['id']?'selected':''?>><?=htmlspecialchars($s['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="fi-group">
        <label class="fi-label">Field Officer</label>
        <select name="officer_id" class="gms-select" style="width:150px;">
            <option value="">All Officers</option>
            <?php foreach($allOfficers as $o): ?>
            <option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="fi-group">
        <label class="fi-label">Area</label>
        <select name="area" class="gms-select" style="width:120px;">
            <option value="">All Areas</option>
            <?php foreach($allAreas as $a): ?>
            <option value="<?=htmlspecialchars($a)?>" <?=$selArea===$a?'selected':''?>><?=htmlspecialchars($a)?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="fi-group">
        <label class="fi-label">Search Grower</label>
        <input type="text" name="q" class="gms-input" placeholder="Name or grower #" value="<?=htmlspecialchars($search)?>" style="width:150px;">
    </div>

    <div class="fi-divider"></div>

    <!-- Question created date range -->
    <div class="fi-group">
        <label class="fi-label fi-label-blue">🗓 Question Created</label>
        <div class="date-group">
            <input type="date" name="q_date_from" class="gms-date gms-date-blue" value="<?=htmlspecialchars($qDateFrom)?>" title="Question created from">
            <span class="date-separator">→</span>
            <input type="date" name="q_date_to" class="gms-date gms-date-blue" value="<?=htmlspecialchars($qDateTo)?>" title="Question created to">
        </div>
    </div>

    <!-- Answer date range -->
    <div class="fi-group">
        <label class="fi-label fi-label-green">✏️ Answer Date</label>
        <div class="date-group">
            <input type="date" name="a_date_from" class="gms-date gms-date-green" value="<?=htmlspecialchars($aDateFrom)?>" title="Answer date from">
            <span class="date-separator">→</span>
            <input type="date" name="a_date_to" class="gms-date gms-date-green" value="<?=htmlspecialchars($aDateTo)?>" title="Answer date to">
        </div>
    </div>

    <div class="fi-divider"></div>

    <div class="fi-actions">
        <button type="submit" class="fbtn fbtn-apply">Apply</button>
        <a href="questionnaire_pivot.php" class="fbtn fbtn-reset">Reset</a>
    </div>

</form>
</div>

<!-- ── Active filter pills ──────────────────────────────────────────────────── -->
<?php if($activeFilters>0): ?>
<div class="active-pills">
    <?php if($qDateFrom||$qDateTo): ?>
    <span class="pill pill-blue">🗓 Q created: <?=$qDateFrom?:' any'?> → <?=$qDateTo?:' any'?></span>
    <?php endif; ?>
    <?php if($aDateFrom||$aDateTo): ?>
    <span class="pill pill-green">✏️ Answered: <?=$aDateFrom?:' any'?> → <?=$aDateTo?:' any'?></span>
    <?php endif; ?>
    <?php if($selOfficer): ?>
    <span class="pill pill-amber">👤 Officer filtered</span>
    <?php endif; ?>
    <?php if($selArea): ?>
    <span class="pill pill-amber">📍 <?=htmlspecialchars($selArea)?></span>
    <?php endif; ?>
    <?php if($search): ?>
    <span class="pill pill-amber">🔍 "<?=htmlspecialchars($search)?>"</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Page ─────────────────────────────────────────────────────────────────── -->
<div class="page">

    <!-- Page title -->
    <div style="margin-bottom:14px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:900;letter-spacing:-.5px;">📋 Questionnaire Answers</div>
        <div style="font-size:11px;color:var(--muted);margin-top:3px;">Dynamic pivot · questions as columns · click a question to inspect</div>
    </div>

    <!-- KPIs -->
    <div class="kpi-row fade-up">
        <div class="kpi">
            <div class="kpi-val" style="color:var(--green);"><?=$totalGrowers?></div>
            <div class="kpi-label">Growers</div>
        </div>
        <div class="kpi">
            <div class="kpi-val" style="color:var(--blue);"><?=$totalQuestions?></div>
            <div class="kpi-label">Questions</div>
        </div>
        <div class="kpi">
            <?php
            $avgComp=$totalGrowers>0?round(array_sum($completionStats)/count($completionStats)):0;
            $compCol=$avgComp>=80?'var(--green)':($avgComp>=50?'var(--amber)':'var(--red)');
            ?>
            <div class="kpi-val" style="color:<?=$compCol?>"><?=$avgComp?>%</div>
            <div class="kpi-label">Avg Completion</div>
        </div>
        <div class="kpi">
            <div class="kpi-val" style="color:var(--green);"><?=count(array_filter($completionStats,fn($c)=>$c>=100))?></div>
            <div class="kpi-label">Fully Done</div>
        </div>
        <div class="kpi">
            <div class="kpi-val" style="color:var(--amber);"><?=count(array_filter($completionStats,fn($c)=>$c<100))?></div>
            <div class="kpi-label">Incomplete</div>
        </div>
    </div>

    <!-- Question chips -->
    <?php if(!empty($questionKeys)): ?>
    <div class="q-bar-wrap fade-up">
        <div class="q-bar-header">
            <span class="q-bar-title">Filter by question</span>
            <button type="button" class="q-bar-toggle" id="chips-toggle" onclick="toggleChips()">▲ collapse</button>
            <span style="font-size:9px;color:var(--muted);margin-left:4px;" id="chips-count-info"><?=$totalQuestions?> questions</span>
        </div>
        <div class="q-chips-outer" id="chips-outer">
        <div class="q-chips" id="chips-container">
            <!-- All chip -->
            <div class="q-chip all-chip active" id="chip-all" onclick="selectQuestion('')" data-q="">
                All
                <span class="q-cnt"><?=$totalQuestions?></span>
            </div>
            <!-- Per-question chips -->
            <?php foreach($questionKeys as $idx=>$q):
                $meta=$questions[$q];
                $qd=$meta['q_date']?date('d M',strtotime($meta['q_date'])):'';
            ?>
            <div class="q-chip" id="chip-q<?=$idx?>"
                 onclick="selectQuestion(<?=$idx?>)"
                 data-idx="<?=$idx?>">
                <?=htmlspecialchars(mb_strimwidth($q,0,26,'…'))?>
                <span class="q-cnt"><?=$meta['count']?>/<?=$totalGrowers?></span>
                <?php if($qd): ?><span class="q-chip-date">📅 <?=$qd?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Single question detail panel (JS-driven) -->
    <div id="sq-panel">
        <div class="sq-header" id="sq-title">
            <span>📋</span>
            <span id="sq-q-text"></span>
            <span id="sq-q-meta" style="font-size:11px;font-weight:400;color:var(--dim);margin-left:auto;"></span>
        </div>
        <div class="sq-body">
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:10px;">Answer Distribution</div>
            <div class="sq-dist" id="sq-dist"></div>
        </div>
    </div>

    <!-- Pivot table -->
    <?php if(empty($growerMeta)): ?>
    <div class="empty-state fade-up">
        <div style="font-size:32px;margin-bottom:10px;opacity:.4;">📋</div>
        <div>No questionnaire answers found.</div>
        <div style="margin-top:6px;font-size:10px;">Try adjusting the filters above.</div>
    </div>
    <?php else: ?>

    <div class="table-outer limited fade-up" id="pivot-table-wrap">
    <table id="pivot-table">
        <thead>
        <tr>
            <th>#</th>
            <th>Grower</th>
            <th>Area</th>
            <th>Officer</th>
            <th class="th-done" id="th-done">Done</th>
            <?php foreach($questionKeys as $idx=>$q): ?>
            <?php $qDate=$questions[$q]['q_date']??''; ?>
            <th class="q-header" id="qh-<?=$idx?>" data-idx="<?=$idx?>"
                title="<?=htmlspecialchars($q)?><?=$qDate?' · Created: '.date('d M Y',strtotime($qDate)):''?>">
                <div class="q-header-inner"><?=htmlspecialchars($q)?></div>
                <?php if($qDate): ?>
                <span class="q-hdr-date"><?=date('d M',strtotime($qDate))?></span>
                <?php endif; ?>
                <span class="q-cnt-hdr"><?=$questions[$q]['count']??0?>/<?=$totalGrowers?></span>
            </th>
            <?php endforeach; ?>
            <th>Answered</th>
        </tr>
        </thead>
        <tbody>
        <?php $rank=0; foreach($growerMeta as $gid=>$meta): $rank++;
            $comp=$completionStats[$gid];
            $compCol=$comp>=100?'var(--green)':($comp>=60?'var(--amber)':'var(--red)');
        ?>
        <tr data-gid="<?=$gid?>">
            <td class="sc sc1"><?=$rank?></td>
            <td class="sc sc2">
                <?=htmlspecialchars($meta['grower_name'])?>
                <span style="display:block;font-size:9px;color:var(--muted);font-weight:400;">#<?=htmlspecialchars($meta['grower_num'])?></span>
            </td>
            <td class="sc sc3"><?=htmlspecialchars($meta['area']??'—')?></td>
            <td class="sc sc4"><?=htmlspecialchars($meta['officer_name']??'—')?></td>
            <td class="sc sc5" id="done-<?=$gid?>">
                <div class="comp-wrap">
                    <div class="comp-bar"><div class="comp-fill" style="width:<?=$comp?>%;background:<?=$compCol?>;"></div></div>
                    <span style="font-size:9px;color:<?=$compCol?>"><?=$comp?>%</span>
                </div>
            </td>
            <?php foreach($questionKeys as $idx=>$q):
                $entry=$answerMap[$gid][$q]??null;
                $ans=$entry['val']??'';
                $ansDate=$entry['date']??'';
                $hasAns=$ans!==null&&$ans!=='';
                $cls='ans-txt';
                if($hasAns){
                    if(is_numeric($ans)) $cls='ans-num';
                    elseif(in_array(strtolower($ans),['yes','y'])) $cls='ans-yes';
                    elseif(in_array(strtolower($ans),['no','n']))  $cls='ans-no';
                }
            ?>
            <td class="ans-cell qcol-<?=$idx?>"
                data-idx="<?=$idx?>"
                title="<?=htmlspecialchars($q)?>: <?=htmlspecialchars($ans)?><?=$ansDate?' ('.$ansDate.')':''?>">
                <?php if($hasAns): ?>
                <span class="ans-val <?=$cls?>"><?=htmlspecialchars(mb_strimwidth($ans,0,16,'…'))?></span>
                <?php else: ?><span class="ans-empty">·</span><?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td style="font-size:10px;color:var(--muted);"><?=date('d M Y',strtotime($meta['last_answered']))?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="legend-row">
        <span><?=$totalGrowers?> growers · <?=$totalQuestions?> questions</span>
        <span style="color:var(--blue);">■ Numeric</span>
        <span style="color:var(--green);">■ Yes</span>
        <span style="color:var(--red);">■ No</span>
        <span style="color:var(--dim);">■ Text</span>
        <span style="color:var(--border2);">· Not answered</span>
    </div>
    <?php endif; ?>

</div><!-- /.page -->

<script>
// ── All data from PHP ────────────────────────────────────────────────────────
const QUESTIONS  = <?=$jsQuestions?>;
const Q_META     = <?=$jsQMeta?>;
const ANSWER_MAP = <?=$jsAnswerMap?>;
const GROWER_META= <?=$jsGrowerMeta?>;
const COMPLETION = <?=$jsCompletion?>;
const Q_FREQ     = <?=$jsQFreq?>;
const TOTAL_G    = <?=$totalGrowers?>;

let activeQ = '';        // '' = all, else question string
let chipsCollapsed = false;

// ── Filter panel toggle ──────────────────────────────────────────────────────
function toggleFilters(){
    const panel = document.getElementById('filter-panel');
    panel.classList.toggle('open');
    const btn = document.getElementById('filter-toggle-btn');
    btn.style.background = panel.classList.contains('open') ? '#2ab854' : '';
}

// ── Chips collapse/expand ────────────────────────────────────────────────────
function toggleChips(){
    chipsCollapsed = !chipsCollapsed;
    const outer = document.getElementById('chips-outer');
    const btn   = document.getElementById('chips-toggle');
    outer.classList.toggle('collapsed', chipsCollapsed);
    btn.textContent = chipsCollapsed ? '▼ expand' : '▲ collapse';
}

// ── Select a question chip ───────────────────────────────────────────────────
function selectQuestion(idx){
    // idx = integer index into QUESTIONS array, or '' for All

    // ── Update chip active states ────────────────────────────────────────────
    document.querySelectorAll('.q-chip').forEach(c => c.classList.remove('active'));
    if(idx === ''){
        document.getElementById('chip-all').classList.add('active');
    } else {
        const chip = document.getElementById('chip-q'+idx);
        if(chip) chip.classList.add('active');
    }

    // ── Show/hide columns by index ───────────────────────────────────────────
    QUESTIONS.forEach(function(question, i){
        const isThis = (i === idx);
        const show   = (idx === '' || isThis);

        // header th
        const th = document.getElementById('qh-'+i);
        if(th){
            th.style.display = show ? '' : 'none';
            th.classList.toggle('active-col', isThis && idx !== '');
        }

        // all answer cells in this column — class qcol-{i}
        document.querySelectorAll('.qcol-'+i).forEach(function(td){
            td.style.display = show ? '' : 'none';
            td.classList.toggle('active-col', isThis && idx !== '');
        });
    });

    // ── Completion % column ──────────────────────────────────────────────────
    document.getElementById('th-done').style.display = idx === '' ? '' : 'none';
    document.querySelectorAll('[id^="done-"]').forEach(function(td){
        td.style.display = idx === '' ? '' : 'none';
    });

    // ── Detail panel ─────────────────────────────────────────────────────────
    const panel = document.getElementById('sq-panel');
    if(idx === ''){
        panel.classList.remove('visible');
        document.getElementById('pivot-table-wrap').className = 'table-outer limited fade-up';
    } else {
        panel.classList.add('visible');
        document.getElementById('pivot-table-wrap').className = 'table-outer full-height fade-up';
        renderDetailPanel(QUESTIONS[idx]);
    }

    // ── Auto-collapse/expand chips ───────────────────────────────────────────
    if(idx !== '' && !chipsCollapsed) toggleChips();
    if(idx === '' && chipsCollapsed)  toggleChips();
}

// ── Render detail panel ──────────────────────────────────────────────────────
function renderDetailPanel(q){
    document.getElementById('sq-q-text').textContent = q;
    const freq = Q_FREQ[q] || {};
    const meta = Q_META[q] || {};
    const totalAns = Object.values(freq).reduce((a,b)=>a+b,0);
    document.getElementById('sq-q-meta').textContent =
        totalAns + ' of ' + TOTAL_G + ' answered' +
        (meta.q_date ? '  ·  📅 Created: ' + formatDate(meta.q_date) : '');

    const maxFreq = Math.max(...Object.values(freq), 1);
    const dist = document.getElementById('sq-dist');
    dist.innerHTML = '';

    if(Object.keys(freq).length === 0){
        dist.innerHTML = '<span style="color:var(--muted);font-size:11px;">No answers recorded</span>';
        return;
    }

    Object.entries(freq).forEach(([ans, cnt]) => {
        const pct  = Math.round(cnt / TOTAL_G * 100 * 10) / 10;
        const barW = Math.round(cnt / maxFreq * 100);
        const num  = !isNaN(ans) && ans.trim() !== '';
        const yes  = ['yes','y'].includes(ans.toLowerCase());
        const no   = ['no','n'].includes(ans.toLowerCase());
        const col  = num ? 'var(--blue)' : yes ? 'var(--green)' : no ? 'var(--red)' : 'var(--amber)';

        const el = document.createElement('div');
        el.className = 'sq-item';
        el.innerHTML =
            '<div class="sq-item-label" title="'+ans+'">'+
                (ans.length > 22 ? ans.substring(0,22)+'…' : ans)+
            '</div>'+
            '<div class="sq-item-bar">'+
                '<div class="sq-item-fill" style="width:'+barW+'%;background:'+col+';"></div>'+
            '</div>'+
            '<div class="sq-item-sub">'+cnt+' &nbsp;('+pct+'%)</div>';
        dist.appendChild(el);
    });
}

// Column IDs use integer indices — no md5 needed

function formatDate(d){
    if(!d) return '';
    const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const parts = d.split('-');
    if(parts.length < 3) return d;
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0];
}

// Auto-open filter panel if there are active filters
<?php if($activeFilters>0): ?>
document.getElementById('filter-panel').classList.add('open');
<?php endif; ?>
</script>
</body>
</html>
