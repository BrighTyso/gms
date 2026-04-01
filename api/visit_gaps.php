<?php
ob_start();
if(session_status()===PHP_SESSION_NONE) session_start();
require "conn.php";
require "validate.php";
date_default_timezone_set('Africa/Harare');
$conn->query("SET time_zone = '+02:00'");

$seasonId=0; $seasonName='—';
$r=$conn->query("SELECT id,name FROM seasons WHERE active=1 LIMIT 1");
if($r&&$row=$r->fetch_assoc()){$seasonId=(int)$row['id'];$seasonName=$row['name'];$r->free();}

$selOfficer=isset($_GET['officer_id'])&&$_GET['officer_id']!==''?(int)$_GET['officer_id']:null;
$selArea=isset($_GET['area'])&&$_GET['area']!==''?$conn->real_escape_string($_GET['area']):'';
$minDays=(int)($_GET['min_days']??14);

$allOfficers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}
$allAreas=[];
$r=$conn->query("SELECT DISTINCT area FROM growers WHERE area IS NOT NULL AND area!='' ORDER BY area");
if($r){while($row=$r->fetch_assoc()) $allAreas[]=$row['area']; $r->free();}

$officerWhere=$selOfficer?"AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"";
$areaWhere=$selArea?"AND g.area='{$selArea}'":"";

// Growers with loans but no visit since loan date
$rows=[];
$r=$conn->query("
    SELECT
        g.id, g.grower_num,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.area, fo.name AS officer_name,
        MIN(STR_TO_DATE(l.created_at,'%Y-%m-%d')) AS first_loan_date,
        COUNT(DISTINCT l.id) AS loan_count,
        SUM(l.product_total_cost) AS loan_value,
        SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END) AS unverified_value,
        MAX(v.created_at) AS last_visit_ever,
        MAX(CASE WHEN DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')) >= MIN(DATE(STR_TO_DATE(l.created_at,'%Y-%m-%d')))
            THEN v.created_at END) AS last_visit_since_loan,
        DATEDIFF(NOW(), MAX(v.created_at)) AS days_since_any_visit,
        DATEDIFF(NOW(), MIN(STR_TO_DATE(l.created_at,'%Y-%m-%d'))) AS days_since_loan
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid={$seasonId} {$officerWhere}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    LEFT JOIN visits v ON v.growerid=g.id AND v.seasonid={$seasonId}
    WHERE l.seasonid={$seasonId} {$areaWhere}
    GROUP BY g.id,g.grower_num,g.name,g.surname,g.area,fo.name
    HAVING (last_visit_since_loan IS NULL OR DATEDIFF(NOW(),last_visit_since_loan)>{$minDays})
    ORDER BY days_since_any_visit DESC
");
while($r&&$row=$r->fetch_assoc()) $rows[]=$row;

$neverVisited=count(array_filter($rows,fn($r)=>$r['last_visit_ever']===null));
$criticalGap=count(array_filter($rows,fn($r)=>($r['days_since_any_visit']??999)>60));
$totalUnverified=array_sum(array_column($rows,'unverified_value'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Visit Gaps</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;--green:#3ddc68;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;--radius:8px;--radius2:5px;}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);}.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);}
.page{max-width:1400px;margin:0 auto;padding:24px 20px 60px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;}
.kpi-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:900;margin:4px 0;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.kpi-sub{font-size:10px;color:var(--dim);margin-top:3px;}
.filter-bar{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:20px;}
.filter-bar label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:4px;}
select,input[type=number]{background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:6px 10px;font-family:'Space Mono',monospace;font-size:11px;outline:none;}
.btn{padding:6px 14px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;}
.btn-primary{background:var(--green);color:#000;}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted);}
table{width:100%;border-collapse:collapse;font-size:11px;}
th{text-align:left;padding:9px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);white-space:nowrap;}
td{padding:9px 12px;border-bottom:1px solid rgba(28,46,28,.5);color:var(--dim);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(61,220,104,.02);color:var(--text);}
.mono{font-family:'Space Mono',monospace;}
.badge{display:inline-flex;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700;}
.b-red{background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.25);}
.b-amber{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.2);}
.b-green{background:rgba(61,220,104,.1);color:var(--green);border:1px solid rgba(61,220,104,.2);}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Gaps</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="grower_risk.php" class="back">🎯 Risk</a>
    <?php if($neverVisited>0): ?>
    <span style="background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.25);padding:3px 10px;border-radius:12px;font-size:10px;">⚠ <?=$neverVisited?> never visited</span>
    <?php endif; ?>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>
<div class="page">
    <div style="margin-bottom:20px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-.5px;">🔍 Visit Gap Analysis</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Growers who received loans but have not been visited since — direct default risk indicator</div>
    </div>
    <div class="kpi-grid fade-up">
        <div class="kpi"><div class="kpi-val" style="color:var(--red);"><?=count($rows)?></div><div class="kpi-label">Growers with Gap</div><div class="kpi-sub">No visit in <?=$minDays?>+ days since loan</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);"><?=$neverVisited?></div><div class="kpi-label">Never Visited</div><div class="kpi-sub">Loan issued, zero visits ever</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--amber);"><?=$criticalGap?></div><div class="kpi-label">Critical (60d+)</div><div class="kpi-sub">No visit in over 60 days</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);">$<?=number_format($totalUnverified,0)?></div><div class="kpi-label">Unverified Exposure</div><div class="kpi-sub">In gap growers</div></div>
    </div>
    <form method="GET" class="filter-bar fade-up">
        <div><label>Field Officer</label><select name="officer_id"><option value="">All Officers</option>
        <?php foreach($allOfficers as $o): ?><option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option><?php endforeach; ?></select></div>
        <div><label>Area</label><select name="area"><option value="">All Areas</option>
        <?php foreach($allAreas as $a): ?><option value="<?=htmlspecialchars($a)?>" <?=$selArea===$a?'selected':''?>><?=htmlspecialchars($a)?></option><?php endforeach; ?></select></div>
        <div><label>Min Days Gap</label><input type="number" name="min_days" value="<?=$minDays?>" min="1" max="365" style="width:80px;"></div>
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="visit_gaps.php" class="btn btn-ghost">Reset</a>
    </form>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;" class="fade-up">
    <div style="overflow-x:auto;"><table>
        <thead><tr>
            <th>#</th><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th>
            <th>Loans</th><th>Loan Value</th><th>Unverified</th>
            <th>First Loan</th><th>Last Visit</th><th>Days Gap</th><th>Urgency</th>
        </tr></thead>
        <tbody>
        <?php foreach($rows as $i=>$row):
            $days=$row['days_since_any_visit']??($row['days_since_loan']??999);
            $urgency=$row['last_visit_ever']===null?'Never':($days>60?'Critical':($days>30?'High':'Medium'));
            $uBadge=$urgency==='Never'||$urgency==='Critical'?'b-red':($urgency==='High'?'b-amber':'b-green');
            $daysCol=$days>60?'var(--red)':($days>30?'var(--amber)':'var(--dim)');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:10px;"><?=$i+1?></td>
            <td style="font-weight:700;color:var(--text);white-space:nowrap;"><?=htmlspecialchars($row['grower_name'])?></td>
            <td class="mono" style="font-size:10px;"><?=htmlspecialchars($row['grower_num'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['area'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['officer_name'])?></td>
            <td class="mono"><?=$row['loan_count']?></td>
            <td class="mono" style="color:var(--blue);">$<?=number_format($row['loan_value'],0)?></td>
            <td class="mono" style="color:<?=$row['unverified_value']>0?'var(--amber)':'var(--muted)'?>">
                <?=$row['unverified_value']>0?'$'.number_format($row['unverified_value'],0):'—'?>
            </td>
            <td style="font-size:10px;color:var(--dim);"><?=$row['first_loan_date']?date('d M Y',strtotime($row['first_loan_date'])):'—'?></td>
            <td style="font-size:10px;color:<?=$row['last_visit_ever']?'var(--muted)':'var(--red)'?>;">
                <?=$row['last_visit_ever']?date('d M Y',strtotime($row['last_visit_ever'])):'Never'?>
            </td>
            <td class="mono" style="font-weight:700;color:<?=$daysCol?>"><?=$row['last_visit_ever']?$days.'d':'∞'?></td>
            <td><span class="badge <?=$uBadge?>"><?=$urgency?></span></td>
        </tr>
        <?php endforeach; if(empty($rows)): ?>
        <tr><td colspan="12" style="text-align:center;padding:40px;color:var(--muted);">✅ No visit gaps found — all loaned growers have been visited</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div></div>
</div>
</body></html>
