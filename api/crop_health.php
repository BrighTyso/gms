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
$selVigor=$_GET['vigor']??'';

$allOfficers=[];
$r=$conn->query("SELECT id,name FROM field_officers ORDER BY name");
if($r){while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free();}
$allAreas=[];
$r=$conn->query("SELECT DISTINCT area FROM growers WHERE area IS NOT NULL AND area!='' ORDER BY area");
if($r){while($row=$r->fetch_assoc()) $allAreas[]=$row['area']; $r->free();}

$officerWhere=$selOfficer?"AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"";
$areaWhere=$selArea?"AND g.area='{$selArea}'":"";
$vigorWhere=$selVigor?"AND tt.transplant_vigor LIKE '%{$selVigor}%'":"";

// Crop health data
$rows=[];
$r=$conn->query("
    SELECT
        g.id, g.grower_num,
        CONCAT(g.name,' ',g.surname) AS grower_name,
        g.area, fo.name AS officer_name,
        tt.transplant_vigor AS vigor,
        tt.transplant_survival_rate AS survival,
        tt.transplant_pests AS pests,
        tt.transplant_diseases AS diseases,
        tt.transplant_weeds AS weeds,
        tt.hectares_transplanted AS hectares,
        tt.transplant_date,
        tt.created_at,
        MAX(v.created_at) AS last_visit,
        DATEDIFF(NOW(),MAX(v.created_at)) AS days_since_visit,
        ll.latitude, ll.longitude
    FROM tobacco_transplanting tt
    JOIN growers g ON g.id=tt.growerid
    JOIN grower_field_officer gfo ON gfo.growerid=g.id AND gfo.seasonid={$seasonId} {$officerWhere}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    LEFT JOIN visits v ON v.growerid=g.id AND v.seasonid={$seasonId}
    LEFT JOIN lat_long ll ON ll.growerid=g.id
    WHERE tt.seasonid={$seasonId} {$areaWhere} {$vigorWhere}
    GROUP BY g.id,g.grower_num,g.name,g.surname,g.area,fo.name,
        tt.transplant_vigor,tt.transplant_survival_rate,tt.transplant_pests,
        tt.transplant_diseases,tt.transplant_weeds,tt.hectares_transplanted,
        tt.transplant_date,tt.created_at,ll.latitude,ll.longitude
    ORDER BY tt.transplant_survival_rate ASC
");
while($r&&$row=$r->fetch_assoc()) $rows[]=$row;

// Summary stats
$poorCount=count(array_filter($rows,fn($r)=>(float)$r['survival']<75));
$goodCount=count(array_filter($rows,fn($r)=>(float)$r['survival']>=90));
$issueCount=count(array_filter($rows,fn($r)=>!empty($r['pests'])||!empty($r['diseases'])));
$avgSurvival=count($rows)>0?round(array_sum(array_column($rows,'survival'))/count($rows),1):0;

// Vigor distribution
$vigorDist=[];
foreach($rows as $row){
    $v=strtolower(trim($row['vigor']??'unknown'));
    $vigorDist[$v]=($vigorDist[$v]??0)+1;
}

// Area health summary
$areaHealth=[];
$r=$conn->query("
    SELECT g.area,
        COUNT(*) AS growers,
        ROUND(AVG(tt.transplant_survival_rate),1) AS avg_survival,
        SUM(tt.hectares_transplanted) AS total_ha,
        SUM(CASE WHEN tt.transplant_pests IS NOT NULL AND tt.transplant_pests!='' THEN 1 ELSE 0 END) AS has_pests,
        SUM(CASE WHEN tt.transplant_diseases IS NOT NULL AND tt.transplant_diseases!='' THEN 1 ELSE 0 END) AS has_diseases
    FROM tobacco_transplanting tt JOIN growers g ON g.id=tt.growerid
    WHERE tt.seasonid={$seasonId}
    GROUP BY g.area ORDER BY avg_survival ASC
");
while($r&&$row=$r->fetch_assoc()) $areaHealth[]=$row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Crop Health</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;--green:#3ddc68;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;--radius:8px;--radius2:5px;}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);}.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);}
.page{max-width:1400px;margin:0 auto;padding:24px 20px 60px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;}
.kpi-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:900;margin:4px 0;}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);}
.filter-bar{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-bottom:20px;}
.filter-bar label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:4px;}
select{background:var(--surface2);border:1px solid var(--border2);color:var(--text);border-radius:var(--radius2);padding:6px 10px;font-family:'Space Mono',monospace;font-size:11px;outline:none;}
.btn{padding:6px 14px;border-radius:var(--radius2);border:none;cursor:pointer;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;}
.btn-primary{background:var(--green);color:#000;}.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;}
.card-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--dim);margin-bottom:14px;}
.section-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:24px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border);}
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
.bar-wrap{width:70px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;}
.bar-fill{height:100%;border-radius:3px;}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Crop</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="grower_risk.php" class="back">🎯 Risk</a>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>
<div class="page">
    <div style="margin-bottom:20px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-.5px;">🌱 Transplanting Health Dashboard</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Survival rates · vigor · pests &amp; diseases · area health breakdown</div>
    </div>
    <div class="kpi-grid fade-up">
        <div class="kpi"><div class="kpi-val" style="color:var(--blue);"><?=count($rows)?></div><div class="kpi-label">Records</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--amber);"><?=$avgSurvival?>%</div><div class="kpi-label">Avg Survival</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);"><?=$poorCount?></div><div class="kpi-label">Poor (&lt;75%)</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--green);"><?=$goodCount?></div><div class="kpi-label">Good (≥90%)</div></div>
        <div class="kpi"><div class="kpi-val" style="color:var(--red);"><?=$issueCount?></div><div class="kpi-label">Pest/Disease Issues</div></div>
    </div>
    <form method="GET" class="filter-bar fade-up">
        <div><label>Field Officer</label><select name="officer_id"><option value="">All Officers</option>
        <?php foreach($allOfficers as $o): ?><option value="<?=$o['id']?>" <?=$selOfficer==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option><?php endforeach; ?></select></div>
        <div><label>Area</label><select name="area"><option value="">All Areas</option>
        <?php foreach($allAreas as $a): ?><option value="<?=htmlspecialchars($a)?>" <?=$selArea===$a?'selected':''?>><?=htmlspecialchars($a)?></option><?php endforeach; ?></select></div>
        <div><label>Vigor</label><select name="vigor"><option value="">All</option>
            <option value="good" <?=$selVigor==='good'?'selected':''?>>Good</option>
            <option value="fair" <?=$selVigor==='fair'?'selected':''?>>Fair</option>
            <option value="poor" <?=$selVigor==='poor'?'selected':''?>>Poor</option>
        </select></div>
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="crop_health.php" class="btn btn-ghost">Reset</a>
    </form>
    <div class="grid-2 fade-up">
        <div class="card"><div class="card-title">Survival Rate by Area</div><canvas id="areaChart" height="220"></canvas></div>
        <div class="card"><div class="card-title">Transplant Vigor Distribution</div><canvas id="vigorChart" height="220"></canvas></div>
    </div>
    <div class="section-title fade-up">Area Health Summary</div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px;" class="fade-up">
    <div style="overflow-x:auto;"><table>
        <thead><tr><th>Area</th><th>Growers</th><th>Avg Survival</th><th>Total Ha</th><th>Pest Issues</th><th>Disease Issues</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($areaHealth as $row):
            $s=(float)$row['avg_survival'];
            $st=$s<75?'b-red':($s<90?'b-amber':'b-green');
            $sl=$s<75?'Poor':($s<90?'Fair':'Good');
        ?>
        <tr>
            <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['area'])?></td>
            <td class="mono"><?=$row['growers']?></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="bar-wrap"><div class="bar-fill" style="width:<?=$s?>%;background:<?=$s<75?'var(--red)':($s<90?'var(--amber)':'var(--green)')?>"></div></div>
                    <span class="mono" style="font-size:10px"><?=$s?>%</span>
                </div>
            </td>
            <td class="mono"><?=number_format($row['total_ha'],1)?> ha</td>
            <td class="mono" style="color:<?=$row['has_pests']>0?'var(--amber)':'var(--muted)'?>"><?=$row['has_pests']?></td>
            <td class="mono" style="color:<?=$row['has_diseases']>0?'var(--red)':'var(--muted)'?>"><?=$row['has_diseases']?></td>
            <td><span class="badge <?=$st?>"><?=$sl?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <div class="section-title fade-up">Grower Detail — Worst Survival First</div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;" class="fade-up">
    <div style="overflow-x:auto;"><table>
        <thead><tr><th>#</th><th>Grower</th><th>Area</th><th>Officer</th><th>Survival</th><th>Vigor</th><th>Hectares</th><th>Pests</th><th>Diseases</th><th>Last Visit</th></tr></thead>
        <tbody>
        <?php foreach($rows as $i=>$row):
            $s=(float)$row['survival'];
            $sCol=$s<75?'var(--red)':($s<90?'var(--amber)':'var(--green)');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:10px;"><?=$i+1?></td>
            <td style="font-weight:700;color:var(--text);white-space:nowrap;"><?=htmlspecialchars($row['grower_name'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['area'])?></td>
            <td style="font-size:10px;"><?=htmlspecialchars($row['officer_name'])?></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="bar-wrap"><div class="bar-fill" style="width:<?=$s?>%;background:<?=$sCol?>"></div></div>
                    <span class="mono" style="font-size:10px;color:<?=$sCol?>"><?=$s?>%</span>
                </div>
            </td>
            <td><span class="badge <?=$row['vigor']&&stripos($row['vigor'],'good')!==false?'b-green':(stripos($row['vigor']??'','poor')!==false?'b-red':'b-amber')?>"><?=htmlspecialchars($row['vigor']??'—')?></span></td>
            <td class="mono"><?=number_format($row['hectares'],1)?> ha</td>
            <td style="font-size:10px;color:<?=!empty($row['pests'])?'var(--amber)':'var(--muted)'?>"><?=htmlspecialchars(substr($row['pests']??'—',0,25))?></td>
            <td style="font-size:10px;color:<?=!empty($row['diseases'])?'var(--red)':'var(--muted)'?>"><?=htmlspecialchars(substr($row['diseases']??'—',0,25))?></td>
            <td style="font-size:10px;color:<?=($row['days_since_visit']??99)>30?'var(--red)':'var(--muted)'?>"><?=$row['last_visit']?date('d M Y',strtotime($row['last_visit'])):'Never'?></td>
        </tr>
        <?php endforeach; if(empty($rows)): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--muted);">No transplanting data this season</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div></div>
</div>
<?php
$areaNames=json_encode(array_column($areaHealth,'area'));
$areaSurv=json_encode(array_column($areaHealth,'avg_survival'));
$vLabels=json_encode(array_keys($vigorDist));
$vValues=json_encode(array_values($vigorDist));
?>
<script>
Chart.defaults.color='#4a6b4a';Chart.defaults.borderColor='rgba(28,46,28,.6)';
Chart.defaults.font.family="'Space Mono',monospace";Chart.defaults.font.size=10;
new Chart(document.getElementById('areaChart'),{type:'bar',data:{labels:<?=$areaNames?>,datasets:[{
    label:'Avg Survival %',data:<?=$areaSurv?>,
    backgroundColor:<?=$areaSurv?>.map(v=>v<75?'rgba(232,64,64,.6)':v<90?'rgba(245,166,35,.6)':'rgba(61,220,104,.6)'),
    borderWidth:1,borderRadius:4}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:'rgba(28,46,28,.8)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}
});
new Chart(document.getElementById('vigorChart'),{type:'doughnut',data:{
    labels:<?=$vLabels?>,datasets:[{data:<?=$vValues?>,backgroundColor:['#3ddc68','#f5a623','#e84040','#4a9eff','#b47eff'],borderColor:'#0f160f',borderWidth:3,hoverOffset:8}]},
    options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{padding:14}}}}
});
</script>
</body></html>
