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
$sortBy=$_GET['sort']??'net_exposure';

$allOfficers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}
$allAreas=[];
$r=$conn->query("SELECT DISTINCT area FROM growers WHERE area IS NOT NULL AND area!='' ORDER BY area");
if($r){while($row=$r->fetch_assoc()) $allAreas[]=$row['area']; $r->free();}

$officerWhere=$selOfficer?"AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"";
$areaWhere=$selArea?"AND g.area='{$selArea}'":"";

// ── Main exposure query ────────────────────────────────────────────────────────
$rows=[];
$r=$conn->query("
    SELECT
        g.id, g.grower_num,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.area, fo.name AS officer_name,
        COALESCE(SUM(l.product_total_cost),0)       AS total_loans,
        COALESCE(SUM(ca.value),0)                   AS total_charges,
        COALESCE(MAX(ro.amount),0)                  AS rollover,
        COALESCE(MAX(wc.amount),0)                  AS working_capital,
        COALESCE(SUM(l.product_total_cost),0)
            + COALESCE(SUM(ca.value),0)
            + COALESCE(MAX(ro.amount),0)            AS gross_exposure,
        COALESCE(SUM(l.product_total_cost),0)
            + COALESCE(SUM(ca.value),0)
            + COALESCE(MAX(ro.amount),0)
            - COALESCE(MAX(wc.amount),0)            AS net_exposure,
        ROUND(
            (COALESCE(SUM(l.product_total_cost),0)+COALESCE(SUM(ca.value),0)+COALESCE(MAX(ro.amount),0))
            / NULLIF(COALESCE(MAX(wc.amount),0),0) * 100
        ,1)                                         AS exposure_pct,
        COUNT(DISTINCT l.id)                        AS loan_count,
        SUM(CASE WHEN l.verified=0 THEN l.product_total_cost ELSE 0 END) AS unverified
    FROM growers g
    JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid={$seasonId} {$officerWhere}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    LEFT JOIN loans l ON l.growerid=g.id AND l.seasonid={$seasonId}
    LEFT JOIN charges_amount ca ON ca.seasonid={$seasonId}
        AND ca.chargeid IN (SELECT id FROM charges WHERE growerid=g.id)
    LEFT JOIN rollover ro ON ro.growerid=g.id AND ro.seasonid={$seasonId}
    LEFT JOIN working_capital wc ON wc.growerid=g.id AND wc.seasonid={$seasonId}
    WHERE 1=1 {$areaWhere}
    GROUP BY g.id,g.grower_num,g.name,g.surname,g.area,fo.name
    HAVING total_loans>0 OR rollover>0
    ORDER BY {$sortBy} DESC
");
while($r&&$row=$r->fetch_assoc()) $rows[]=$row;

// Totals
$totLoans=array_sum(array_column($rows,'total_loans'));
$totCharges=array_sum(array_column($rows,'total_charges'));
$totRollover=array_sum(array_column($rows,'rollover'));
$totWC=array_sum(array_column($rows,'working_capital'));
$totGross=array_sum(array_column($rows,'gross_exposure'));
$totNet=array_sum(array_column($rows,'net_exposure'));
$criticalCount=count(array_filter($rows,fn($r)=>(float)$r['net_exposure']>500||(float)($r['exposure_pct']??0)>150));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Loan Exposure</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;--green:#3ddc68;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;--radius:8px;--radius2:5px;}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);}.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);transition:all .2s;}.back:hover{color:var(--green);border-color:var(--green);}
.page{max-width:1400px;margin:0 auto;padding:24px 20px 60px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;}
.kpi-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:900;margin:4px 0;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.filter-bar{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:20px;}
.filter-bar label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:4px;}
select{background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:6px 10px;font-family:'Space Mono',monospace;font-size:11px;outline:none;}
.btn{padding:6px 14px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;}
.btn-primary{background:var(--green);color:#000;}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted);}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;font-size:11px;}
th{text-align:left;padding:9px 12px;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2);cursor:pointer;white-space:nowrap;}
th:hover{color:var(--green);}
td{padding:9px 12px;border-bottom:1px solid rgba(28,46,28,.5);color:var(--dim);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(61,220,104,.02);color:var(--text);}
.mono{font-family:'Space Mono',monospace;}
.badge{display:inline-flex;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700;}
.b-red{background:rgba(232,64,64,.12);color:var(--red);border:1px solid rgba(232,64,64,.25);}
.b-amber{background:rgba(245,166,35,.1);color:var(--amber);border:1px solid rgba(245,166,35,.2);}
.b-green{background:rgba(61,220,104,.1);color:var(--green);border:1px solid rgba(61,220,104,.2);}
.exp-bar{width:60px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;}
.exp-fill{height:100%;border-radius:3px;}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Exposure</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="grower_risk.php" class="back">🎯 Risk</a>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>
<div class="page">
    <div style="margin-bottom:20px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-.5px;">💳 Loan Exposure vs Working Capital</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Total debt (loans + charges + rollover) vs available working capital per grower</div>
    </div>
    <div class="kpi-grid fade-up">
        <div class="kpi"><div class="kpi-val" style="color:var(--blue);">$<?=number_format($totLoans,0)?></div><div class="kpi-label">Total Loans</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--amber);">$<?=number_format($totCharges,0)?></div><div class="kpi-label">Total Charges</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);">$<?=number_format($totRollover,0)?></div><div class="kpi-label">Total Rollover</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--green);">$<?=number_format($totWC,0)?></div><div class="kpi-label">Working Capital</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);">$<?=number_format($totGross,0)?></div><div class="kpi-label">Gross Exposure</div></div>
        <div class="kpi"><div class="kpi-val" style="color:<?=$totNet>0?'var(--red)':'var(--green)'?>;">$<?=number_format($totNet,0)?></div><div class="kpi-label">Net Exposure</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);"><?=$criticalCount?></div><div class="kpi-label">Critical Growers</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--muted);"><?=count($rows)?></div><div class="kpi-label">Total with Loans</div></div>
    </div>
    <form method="GET" class="filter-bar fade-up">
        <div><label>Field Officer</label>
        <select name="officer_id"><option value="">All Officers</option>
        <?php foreach($allOfficers as $o): ?><option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option><?php endforeach; ?>
        </select></div>
        <div><label>Area</label>
        <select name="area"><option value="">All Areas</option>
        <?php foreach($allAreas as $a): ?><option value="<?=htmlspecialchars($a)?>" <?=$selArea===$a?'selected':''?>><?=htmlspecialchars($a)?></option><?php endforeach; ?>
        </select></div>
        <div><label>Sort By</label>
        <select name="sort">
            <option value="net_exposure" <?=$sortBy==='net_exposure'?'selected':''?>>Net Exposure</option>
            <option value="gross_exposure" <?=$sortBy==='gross_exposure'?'selected':''?>>Gross Exposure</option>
            <option value="exposure_pct" <?=$sortBy==='exposure_pct'?'selected':''?>>Exposure %</option>
            <option value="rollover" <?=$sortBy==='rollover'?'selected':''?>>Rollover Amount</option>
        </select></div>
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="loan_exposure.php" class="btn btn-ghost">Reset</a>
    </form>
    <div class="card fade-up"><div style="overflow-x:auto;"><table>
        <thead><tr>
            <th>#</th><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th>
            <th>Loans</th><th>Charges</th><th>Rollover</th><th>Working Cap</th>
            <th>Gross Exp.</th><th>Net Exp.</th><th>Exposure %</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php foreach($rows as $i=>$row):
            $exp=(float)$row['exposure_pct'];
            $net=(float)$row['net_exposure'];
            $status=$exp>150||$net>500?'critical':($exp>100||$net>200?'warning':'ok');
            $barW=min(100,max(0,$exp/2));
            $barCol=$status==='critical'?'var(--red)':($status==='warning'?'var(--amber)':'var(--green)');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:10px;"><?=$i+1?></td>
            <td style="font-weight:700;color:var(--text);white-space:nowrap;"><?=htmlspecialchars($row['grower_name'])?></td>
            <td class="mono" style="font-size:10px;"><?=htmlspecialchars($row['grower_num'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['area'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['officer_name'])?></td>
            <td class="mono">$<?=number_format($row['total_loans'],0)?></td>
            <td class="mono" style="color:<?=$row['total_charges']>0?'var(--amber)':'var(--muted)'?>">$<?=number_format($row['total_charges'],0)?></td>
            <td class="mono" style="color:<?=$row['rollover']>0?'var(--red)':'var(--muted)'?>">$<?=number_format($row['rollover'],0)?></td>
            <td class="mono" style="color:var(--green);">$<?=number_format($row['working_capital'],0)?></td>
            <td class="mono" style="font-weight:700;">$<?=number_format($row['gross_exposure'],0)?></td>
            <td class="mono" style="font-weight:700;color:<?=$net>0?'var(--red)':'var(--green)'?>;">$<?=number_format($net,0)?></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="exp-bar"><div class="exp-fill" style="width:<?=$barW?>%;background:<?=$barCol?>;"></div></div>
                    <span class="mono" style="font-size:10px;color:<?=$barCol?>"><?=$exp?>%</span>
                </div>
            </td>
            <td><?php if($status==='critical'): ?><span class="badge b-red">Critical</span>
            <?php elseif($status==='warning'): ?><span class="badge b-amber">Warning</span>
            <?php else: ?><span class="badge b-green">OK</span><?php endif; ?></td>
        </tr>
        <?php endforeach; if(empty($rows)): ?>
        <tr><td colspan="13" style="text-align:center;padding:40px;color:var(--muted);">No loan data found</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div></div>
</div>
</body></html>
