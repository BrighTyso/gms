<?php
/**
 * visit_schedule.php
 * GMS — Weekly Visit Schedule
 * Shows: scheduled visits (visit_schedules) + actual visits (visits table)
 *        + planned route growers (fuel_request_growers) in one weekly grid
 */
ob_start();
if(session_status()===PHP_SESSION_NONE) session_start();
require "conn.php";
require "validate.php";
date_default_timezone_set('Africa/Harare');
$conn->query("SET time_zone = '+02:00'");

// ── Season ─────────────────────────────────────────────────────────────────────
$seasonId=0; $seasonName='—';
$r=$conn->query("SELECT id,name FROM seasons WHERE active=1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$seasonName=$row['name'];$r->free();}

// ── Week navigation ────────────────────────────────────────────────────────────
$weekOffset=(int)($_GET['week']??0);
$monday    = date('Y-m-d',strtotime("monday this week +{$weekOffset} week"));
$sunday    = date('Y-m-d',strtotime("sunday this week +{$weekOffset} week"));
$today     = date('Y-m-d');

// Days Mon–Sun
$weekDays=[];
for($i=0;$i<7;$i++) $weekDays[]=date('Y-m-d',strtotime($monday." +{$i} days"));

// Day label to fuel_request_growers planned_day mapping
$dayToPlanned=['Mon'=>'MON','Tue'=>'TUE','Wed'=>'WED','Thu'=>'THU','Fri'=>'FRI','Sat'=>'SAT','Sun'=>'SUN'];

// ── POST handlers ──────────────────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    if($action==='add'){
        $oid=(int)$_POST['officer_id'];
        $gid=(int)$_POST['grower_id'];
        $dt =$conn->real_escape_string($_POST['schedule_date']);
        $nt =$conn->real_escape_string($_POST['note']??'');
        $conn->query("INSERT IGNORE INTO visit_schedules (officer_id,grower_id,schedule_date,note,status)
                      VALUES({$oid},{$gid},'{$dt}','{$nt}','scheduled')");
    } elseif($action==='remove'){
        $id=(int)$_POST['id'];
        $conn->query("DELETE FROM visit_schedules WHERE id={$id}");
    } elseif($action==='mark_complete'){
        $id=(int)$_POST['id'];
        $conn->query("UPDATE visit_schedules SET status='completed' WHERE id={$id}");
    }
    header("Location: visit_schedule.php?week={$weekOffset}");
    exit;
}

// ── Officers ───────────────────────────────────────────────────────────────────
$officers=[];
$r=$conn->query("
    SELECT fo.id, fo.name, fo.userid
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid=fo.userid AND gfo.seasonid={$seasonId}
    GROUP BY fo.id,fo.name,fo.userid ORDER BY fo.name
");
if($r){while($row=$r->fetch_assoc()) $officers[]=$row; $r->free();}

// ── Scheduled visits this week (visit_schedules) ──────────────────────────────
// [officer_id][date] => [{id, grower_name, grower_num, note, status, completed}]
$scheduled=[];
$r=$conn->query("
    SELECT vs.id, vs.officer_id, vs.schedule_date, vs.note, vs.status,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.grower_num
    FROM visit_schedules vs
    JOIN growers g ON g.id=vs.grower_id
    WHERE vs.schedule_date BETWEEN '{$monday}' AND '{$sunday}'
    ORDER BY vs.schedule_date, g.name
");
while($r&&$row=$r->fetch_assoc()){
    $scheduled[$row['officer_id']][$row['schedule_date']][]=$row;
}

// ── Actual visits this week (visits table) ────────────────────────────────────
// [officer_userid][date] => [{grower_name, grower_num}] - distinct by grower+day
$actual=[];
$r=$conn->query("
    SELECT v.userid, DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) AS visit_day,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.grower_num, g.id AS grower_id
    FROM visits v
    JOIN growers g ON g.id=v.growerid
    WHERE DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) BETWEEN '{$monday}' AND '{$sunday}'
      AND v.seasonid={$seasonId}
    GROUP BY v.userid, visit_day, g.id, g.name, g.surname, g.grower_num
    ORDER BY visit_day, g.name
");
while($r&&$row=$r->fetch_assoc()){
    $actual[$row['userid']][$row['visit_day']][]=$row;
}

// ── Planned route growers from fuel_request_growers ───────────────────────────
// [userid][planned_day_abbr] => [{grower_name, grower_num, leg_distance_km, visit_order}]
$planned=[];
$r=$conn->query("
    SELECT frg.userid, frg.planned_day, frg.leg_distance_km, frg.visit_order,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.grower_num, g.id AS grower_id
    FROM fuel_request_growers frg
    JOIN growers g ON g.id=frg.growerid
    JOIN fuel_requests fr ON fr.field_officer_id=frg.userid
        AND fr.seasonid=frg.seasonid
        AND STR_TO_DATE(fr.week_start_date,'%Y-%m-%d')='{$monday}'
    WHERE frg.seasonid={$seasonId}
    ORDER BY frg.visit_order ASC
");
while($r&&$row=$r->fetch_assoc()){
    $planned[$row['userid']][$row['planned_day']][]=$row;
}

// ── Weekly summary per officer ─────────────────────────────────────────────────
$summary=[];
foreach($officers as $o){
    $oid=$o['id']; $uid=$o['userid'];
    $totalSched=0; $totalActual=0; $totalPlanned=0; $totalDone=0; $totalMissed=0;
    foreach($weekDays as $d){
        $schItems=$scheduled[$oid][$d]??[];
        $actItems=$actual[$uid][$d]??[];
        $totalSched+=count($schItems);
        $totalActual+=count($actItems);
        foreach($schItems as $s){
            if($s['status']==='completed') $totalDone++;
            elseif($d<$today&&$s['status']==='scheduled') $totalMissed++;
        }
        $dayLabel=date('D',strtotime($d));
        $plannedKey=$dayToPlanned[$dayLabel]??'';
        $totalPlanned+=count($planned[$uid][$plannedKey]??[]);
    }
    $summary[$oid]=['scheduled'=>$totalSched,'actual'=>$totalActual,'planned'=>$totalPlanned,'done'=>$totalDone,'missed'=>$totalMissed];
}

// ── Growers list for modal ─────────────────────────────────────────────────────
$growers=[];
$r=$conn->query("SELECT id,grower_num,name,surname FROM growers ORDER BY name,surname LIMIT 500");
if($r){while($row=$r->fetch_assoc()) $growers[]=$row; $r->free();}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Visit Schedule</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;
    --green:#3ddc68;--green-dim:rgba(61,220,104,.12);--amber:#f5a623;--red:#e84040;
    --blue:#4a9eff;--purple:#b47eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;
    --radius:6px;--radius2:4px;
}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:12px;}
header{display:flex;align-items:center;gap:8px;padding:0 16px;height:52px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:17px;font-weight:900;color:var(--green);}.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:3px 8px;border-radius:var(--radius2);transition:all .2s;}.back:hover{color:var(--green);border-color:var(--green);}
.btn{padding:5px 12px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:10px;font-weight:700;transition:all .2s;}
.btn-green{background:var(--green);color:#000;}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted);}.btn-ghost:hover{border-color:var(--green);color:var(--green);}
.content{padding:16px 20px 60px;max-width:100%;}

/* Week nav */
.week-nav{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.week-nav h2{font-family:'Syne',sans-serif;font-size:15px;font-weight:800;}
.nav-btn{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:4px 10px;border-radius:var(--radius2);cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;transition:all .2s;}.nav-btn:hover{border-color:var(--green);color:var(--green);}
.legend{display:flex;gap:14px;align-items:center;margin-left:auto;font-size:9px;color:var(--muted);}
.legend-dot{width:8px;height:8px;border-radius:2px;display:inline-block;margin-right:4px;}

/* Officer summary cards */
.officer-cards{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.oc{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;min-width:150px;}
.oc-name{font-size:10px;font-weight:700;color:var(--text);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;}
.oc-stats{display:flex;gap:10px;font-size:9px;}
.oc-stat{display:flex;flex-direction:column;align-items:center;}
.oc-val{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;line-height:1;}
.oc-lbl{color:var(--muted);font-size:8px;text-transform:uppercase;letter-spacing:.4px;margin-top:2px;}
.oc-bar{height:3px;background:var(--surface2);border-radius:2px;margin-top:8px;overflow:hidden;}
.oc-bar-fill{height:100%;border-radius:2px;}

/* Schedule grid */
.grid-wrap{overflow-x:auto;border-radius:var(--radius);border:1px solid var(--border);}
.sched-grid{display:grid;min-width:900px;border-collapse:collapse;}
.grid-header-row{display:contents;}
.gh{background:var(--surface2);padding:8px 10px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);border-right:1px solid var(--border);white-space:nowrap;}
.gh.today{color:var(--green);background:rgba(61,220,104,.05);}
.gh:first-child{position:sticky;left:0;z-index:2;min-width:120px;}

.grid-row{display:contents;}
.go{background:var(--surface);padding:10px;border-bottom:1px solid var(--border);border-right:1px solid var(--border);position:sticky;left:0;z-index:1;min-width:120px;}
.go-name{font-size:10px;font-weight:700;color:var(--text);}
.go-sub{font-size:9px;color:var(--muted);margin-top:2px;}

.gc{background:var(--surface);padding:6px 8px;border-bottom:1px solid var(--border);border-right:1px solid var(--border);min-height:70px;vertical-align:top;}
.gc.today-col{background:rgba(61,220,104,.03);}

/* Tags */
.tag{display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:3px;font-size:9px;margin-bottom:3px;white-space:nowrap;overflow:hidden;max-width:100%;cursor:default;}
.tag-actual{background:rgba(61,220,104,.15);color:var(--green);border:1px solid rgba(61,220,104,.3);}
.tag-planned{background:rgba(74,158,255,.1);color:var(--blue);border:1px solid rgba(74,158,255,.2);}
.tag-planned.visited{background:rgba(61,220,104,.08);color:var(--dim);border:1px solid rgba(61,220,104,.15);text-decoration:line-through;opacity:.7;}
.tag-sched{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.2);}
.tag-sched.done{background:rgba(61,220,104,.1);color:var(--green);border-color:rgba(61,220,104,.2);}
.tag-sched.missed{background:rgba(232,64,64,.1);color:var(--red);border-color:rgba(232,64,64,.2);}
.tag-km{font-size:8px;color:var(--muted);margin-left:auto;flex-shrink:0;}
.rm-btn{background:none;border:none;cursor:pointer;color:var(--muted);font-size:9px;padding:0 2px;flex-shrink:0;}.rm-btn:hover{color:var(--red);}
.add-cell-btn{width:100%;margin-top:4px;padding:2px;border:1px dashed var(--border);background:transparent;color:var(--muted);border-radius:3px;cursor:pointer;font-size:9px;transition:all .2s;text-align:center;}.add-cell-btn:hover{border-color:var(--green);color:var(--green);}

/* Section separator inside cell */
.cell-divider{border-top:1px dashed var(--border);margin:4px 0;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);padding:22px;width:380px;max-width:95vw;}
.modal h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--green);margin-bottom:16px;}
.form-row{margin-bottom:12px;}
.form-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:5px;}
.form-row select,.form-row input{width:100%;background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:7px 10px;font-family:'Space Mono',monospace;font-size:11px;outline:none;}
.form-row select:focus,.form-row input:focus{border-color:var(--green);}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;}
.modal-cancel{background:transparent;border:1px solid var(--border);color:var(--muted);cursor:pointer;padding:5px 12px;border-radius:var(--radius2);font-family:'Space Mono',monospace;font-size:10px;}

/* Insight bar */
.insight-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;display:flex;gap:24px;flex-wrap:wrap;font-size:10px;color:var(--dim);}
.insight-item b{color:var(--text);}

::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border2);}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Schedule</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="visit_backlog.php" class="back">📋 Backlog</a>
    <a href="route_planner.php" class="back">🗺 Route</a>
    <a href="fuel_dashboard.php" class="back">⛽ Fuel</a>
    <button class="btn btn-green" onclick="openAddModal()" style="margin-left:8px;">+ Schedule Visit</button>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>

<div class="content">

    <!-- Week nav -->
    <div class="week-nav">
        <button class="nav-btn" onclick="location.href='?week=<?=$weekOffset-1?>'">◀</button>
        <h2>Week of <?=date('d M Y',strtotime($monday))?></h2>
        <button class="nav-btn" onclick="location.href='?week=<?=$weekOffset+1?>'">▶</button>
        <?php if($weekOffset!==0): ?><a href="?week=0" class="back">This Week</a><?php endif; ?>
        <div class="legend">
            <span><span class="legend-dot" style="background:rgba(61,220,104,.5)"></span>Actual visit</span>
            <span><span class="legend-dot" style="background:rgba(74,158,255,.4)"></span>Planned route</span>
            <span><span class="legend-dot" style="background:rgba(245,166,35,.4)"></span>Scheduled</span>
        </div>
    </div>

    <!-- Officer summary cards -->
    <div class="officer-cards">
    <?php foreach($officers as $o):
        $s=$summary[$o['id']];
        $pct=$s['scheduled']>0?min(100,round($s['done']/$s['scheduled']*100)):0;
        $col=$pct>=80?'var(--green)':($pct>=50?'var(--amber)':'var(--red)');
        $routeMatch=$s['planned']>0?min(100,round($s['actual']/$s['planned']*100)):0;
    ?>
    <div class="oc">
        <div class="oc-name" title="<?=htmlspecialchars($o['name'])?>"><?=htmlspecialchars($o['name'])?></div>
        <div class="oc-stats">
            <div class="oc-stat"><div class="oc-val" style="color:var(--green)"><?=$s['actual']?></div><div class="oc-lbl">Actual</div></div>
            <div class="oc-stat"><div class="oc-val" style="color:var(--blue)"><?=$s['planned']?></div><div class="oc-lbl">Planned</div></div>
            <div class="oc-stat"><div class="oc-val" style="color:var(--amber)"><?=$s['scheduled']?></div><div class="oc-lbl">Sched</div></div>
            <div class="oc-stat"><div class="oc-val" style="color:var(--red)"><?=$s['missed']?></div><div class="oc-lbl">Missed</div></div>
        </div>
        <div class="oc-bar"><div class="oc-bar-fill" style="width:<?=$routeMatch?>%;background:var(--blue);"></div></div>
        <div style="font-size:8px;color:var(--muted);margin-top:3px;"><?=$routeMatch?>% of planned route visited</div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Insight bar -->
    <?php
    $totalPlannedWeek=array_sum(array_column($summary,'planned'));
    $totalActualWeek =array_sum(array_column($summary,'actual'));
    $totalSchedWeek  =array_sum(array_column($summary,'scheduled'));
    $totalMissedWeek =array_sum(array_column($summary,'missed'));
    $routeMatchPct   =$totalPlannedWeek>0?round($totalActualWeek/$totalPlannedWeek*100):0;
    ?>
    <div class="insight-bar">
        <span>📍 <b><?=$totalActualWeek?></b> actual visits</span>
        <span>🗺 <b><?=$totalPlannedWeek?></b> planned (fuel route)</span>
        <span>📋 <b><?=$totalSchedWeek?></b> supervisor-scheduled</span>
        <span>❌ <b><?=$totalMissedWeek?></b> missed</span>
        <span>⚡ <b style="color:<?=$routeMatchPct>=80?'var(--green)':($routeMatchPct>=50?'var(--amber)':'var(--red)')?>"><?=$routeMatchPct?>%</b> of planned routes executed</span>
    </div>

    <!-- Main schedule grid -->
    <div class="grid-wrap">
    <div class="sched-grid" style="grid-template-columns: 130px repeat(7, 1fr);">

        <!-- Header row -->
        <div class="gh">Officer</div>
        <?php foreach($weekDays as $d):
            $isToday=$d===$today;
            $label=date('D d M',strtotime($d));
        ?>
        <div class="gh <?=$isToday?'today':''?>"><?=$label?><?=$isToday?' ★':''?></div>
        <?php endforeach; ?>

        <!-- Officer rows -->
        <?php foreach($officers as $o):
            $oid=$o['id']; $uid=$o['userid'];
        ?>
        <div class="go">
            <div class="go-name"><?=htmlspecialchars($o['name'])?></div>
            <div class="go-sub"><?=$summary[$oid]['actual']?> visits · <?=$summary[$oid]['planned']?> planned</div>
        </div>

        <?php foreach($weekDays as $d):
            $isToday=$d===$today;
            $dayLabel=date('D',strtotime($d)); // Mon, Tue etc
            $plannedKey=$dayToPlanned[$dayLabel]??'';

            $schItems =$scheduled[$oid][$d]??[];
            $actItems =$actual[$uid][$d]??[];
            $planItems=$planned[$uid][$plannedKey]??[];

            // Build set of actually visited grower IDs for cross-referencing
            $visitedIds=array_column($actItems,'grower_id');
        ?>
        <div class="gc <?=$isToday?'today-col':''?>">

            <!-- ACTUAL VISITS (green) — from visits table -->
            <?php foreach($actItems as $v): ?>
            <div class="tag tag-actual" title="Actual visit logged">
                ✅ <?=htmlspecialchars(substr($v['grower_name'],0,12))?>
                <span style="font-size:8px;color:var(--muted);margin-left:auto;">#<?=htmlspecialchars($v['grower_num'])?></span>
            </div>
            <?php endforeach; ?>

            <!-- PLANNED ROUTE (blue) — from fuel_request_growers, strike through if visited -->
            <?php if(!empty($planItems)): ?>
            <?php if(!empty($actItems)): ?><div class="cell-divider"></div><?php endif; ?>
            <?php foreach($planItems as $p):
                $wasVisited=in_array($p['grower_id'],$visitedIds);
            ?>
            <div class="tag tag-planned <?=$wasVisited?'visited':''?>"
                 title="<?=$wasVisited?'Visited ✓':'Planned — not yet visited'?>">
                🗺 <?=htmlspecialchars(substr($p['grower_name'],0,10))?>
                <?php if($p['leg_distance_km']>0): ?>
                <span class="tag-km"><?=number_format($p['leg_distance_km'],1)?>km</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- SCHEDULED VISITS (amber) — from visit_schedules -->
            <?php if(!empty($schItems)): ?>
            <?php if(!empty($actItems)||!empty($planItems)): ?><div class="cell-divider"></div><?php endif; ?>
            <?php foreach($schItems as $s):
                $isDone   =$s['status']==='completed';
                $isMissed =$s['status']==='scheduled'&&$d<$today;
                $cls=$isDone?'done':($isMissed?'missed':'');
                $icon=$isDone?'✅':($isMissed?'❌':'📋');
            ?>
            <div class="tag tag-sched <?=$cls?>" title="<?=htmlspecialchars($s['note']??'Scheduled visit')?>">
                <?=$icon?> <?=htmlspecialchars(substr($s['grower_name'],0,10))?>
                <?php if(!$isDone): ?>
                <form method="POST" style="display:inline;margin-left:auto;" onsubmit="return confirm('Remove?')">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="id" value="<?=$s['id']?>">
                    <input type="hidden" name="week" value="<?=$weekOffset?>">
                    <button type="submit" class="rm-btn">✕</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Add button -->
            <button class="add-cell-btn" onclick="openAddModal(<?=$oid?>,'<?=$d?>')">+</button>

        </div>
        <?php endforeach; // days ?>
        <?php endforeach; // officers ?>

    </div><!-- /.sched-grid -->
    </div><!-- /.grid-wrap -->

    <!-- Planned Route vs Actual Summary Table -->
    <div style="margin-top:24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
        <div style="padding:12px 16px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--dim);border-bottom:1px solid var(--border);">
            🗺 Fuel Route Execution — Planned vs Visited This Week
        </div>
        <div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead><tr>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Officer</th>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Planned Growers</th>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Visited</th>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Skipped</th>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Route Completion</th>
                <th style="text-align:left;padding:8px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);">Extra Visits (unplanned)</th>
            </tr></thead>
            <tbody>
            <?php foreach($officers as $o):
                $oid=$o['id']; $uid=$o['userid'];
                // Count unique planned growers this week
                $plannedGrowers=[];
                foreach($weekDays as $d){
                    $dl=date('D',strtotime($d));
                    $pk=$dayToPlanned[$dl]??'';
                    foreach($planned[$uid][$pk]??[] as $p) $plannedGrowers[$p['grower_id']]=true;
                }
                // Count actual visits this week
                $actualGrowers=[];
                foreach($weekDays as $d){
                    foreach($actual[$uid][$d]??[] as $a) $actualGrowers[$a['grower_id']]=true;
                }
                $pCount=count($plannedGrowers);
                $aCount=count($actualGrowers);
                $visitedPlanned=count(array_intersect_key($plannedGrowers,$actualGrowers));
                $skipped=$pCount-$visitedPlanned;
                $extra=$aCount-$visitedPlanned;
                $pct=$pCount>0?round($visitedPlanned/$pCount*100):0;
                $barCol=$pct>=80?'var(--green)':($pct>=50?'var(--amber)':'var(--red)');
            ?>
            <tr>
                <td style="padding:9px 12px;font-weight:700;color:var(--text);border-bottom:1px solid rgba(28,46,28,.5);"><?=htmlspecialchars($o['name'])?></td>
                <td style="padding:9px 12px;font-family:'Space Mono',monospace;color:var(--blue);border-bottom:1px solid rgba(28,46,28,.5);"><?=$pCount?></td>
                <td style="padding:9px 12px;font-family:'Space Mono',monospace;color:var(--green);border-bottom:1px solid rgba(28,46,28,.5);"><?=$visitedPlanned?></td>
                <td style="padding:9px 12px;font-family:'Space Mono',monospace;color:<?=$skipped>0?'var(--red)':'var(--muted)'?>;border-bottom:1px solid rgba(28,46,28,.5);"><?=$skipped?></td>
                <td style="padding:9px 12px;border-bottom:1px solid rgba(28,46,28,.5);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:80px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?=$pct?>%;background:<?=$barCol?>;border-radius:3px;"></div>
                        </div>
                        <span style="font-family:'Space Mono',monospace;font-size:10px;color:<?=$barCol?>"><?=$pct?>%</span>
                    </div>
                </td>
                <td style="padding:9px 12px;font-family:'Space Mono',monospace;color:<?=$extra>0?'var(--amber)':'var(--muted)'?>;border-bottom:1px solid rgba(28,46,28,.5);"><?=$extra>0?'+'.$extra:'—'?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>

</div><!-- /.content -->

<!-- Add Schedule Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h3>Schedule a Visit</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="week" value="<?=$weekOffset?>">
            <div class="form-row">
                <label class="form-label">Field Officer</label>
                <select name="officer_id" id="modal-officer" required>
                    <option value="">— Select Officer —</option>
                    <?php foreach($officers as $o): ?>
                    <option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label class="form-label">Date</label>
                <input type="date" name="schedule_date" id="modal-date" value="<?=$today?>" required>
            </div>
            <div class="form-row">
                <label class="form-label">Grower</label>
                <select name="grower_id" required>
                    <option value="">— Select Grower —</option>
                    <?php foreach($growers as $g): ?>
                    <option value="<?=$g['id']?>">#<?=$g['grower_num']?> — <?=htmlspecialchars($g['name'].' '.$g['surname'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label class="form-label">Note (optional)</label>
                <input type="text" name="note" placeholder="e.g. Check curing barn">
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-green">Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal(officerId, date){
    document.getElementById('addModal').classList.add('open');
    if(officerId) document.getElementById('modal-officer').value=officerId;
    if(date)      document.getElementById('modal-date').value=date;
}
function closeModal(){ document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click',function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
