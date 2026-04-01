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

// ── Officer workload query ────────────────────────────────────────────────────
$officerRows=[];
$r=$conn->query("
    SELECT
        fo.id, fo.name AS officer_name,
        COUNT(DISTINCT gfo.growerid)                        AS assigned,
        COUNT(DISTINCT v.growerid)                          AS visited,
        COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')))) AS total_visits,
        COUNT(DISTINCT CASE WHEN v.growerid IS NULL THEN gfo.growerid END) AS not_visited,
        ROUND(COALESCE(SUM(d.distance),0)/1000,1)          AS total_km,
        ROUND(COALESCE(SUM(d.distance),0)/1000
            / NULLIF(COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d')))),0),2) AS km_per_visit,
        ROUND(COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d'))))
            / NULLIF(COUNT(DISTINCT gfo.growerid),0)*100,1) AS coverage_pct,
        COUNT(DISTINCT DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))) AS active_days,
        ROUND(COUNT(DISTINCT CONCAT(v.growerid,'-',DATE(STR_TO_DATE(v.created_at,'%Y-%m-%d'))))
            / NULLIF(COUNT(DISTINCT DATE(STR_TO_DATE(d.created_at,'%Y-%m-%d'))),0),1) AS visits_per_day,
        COALESCE(SUM(l.product_total_cost),0)              AS loan_value_managed
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid=fo.userid AND gfo.seasonid={$seasonId}
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$seasonId}
    LEFT JOIN distance d ON d.userid=fo.userid AND d.seasonid={$seasonId}
    LEFT JOIN loans l ON l.growerid=gfo.growerid AND l.seasonid={$seasonId}
    GROUP BY fo.id, fo.name
    ORDER BY assigned DESC
");
while($r&&$row=$r->fetch_assoc()) $officerRows[]=$row;

$avgAssigned = count($officerRows)>0 ? round(array_sum(array_column($officerRows,'assigned'))/count($officerRows),1) : 0;
$avgVisits   = count($officerRows)>0 ? round(array_sum(array_column($officerRows,'total_visits'))/count($officerRows),1) : 0;
$maxAssigned = !empty($officerRows) ? max(array_column($officerRows,'assigned')) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Workload Balance</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080c08;--surface:#0f160f;--surface2:#162016;--border:#1c2e1c;--border2:#243824;--green:#3ddc68;--amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b47eff;--muted:#4a6b4a;--dim:#7a9e7a;--text:#c8e6c9;--radius:8px;--radius2:5px;}
html,body{font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);min-height:100vh;font-size:13px;}
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:56px;background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:900;color:var(--green);}.logo span{color:var(--muted);}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);padding:4px 10px;border-radius:var(--radius2);transition:all .2s;}.back:hover{color:var(--green);border-color:var(--green);}
.page{max-width:1400px;margin:0 auto;padding:24px 20px 60px;}
.section-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:24px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media(max-width:900px){.grid-2,.grid-3{grid-template-columns:1fr;}}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;}
.card-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--dim);margin-bottom:14px;}
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
.b-blue{background:rgba(74,158,255,.1);color:var(--blue);border:1px solid rgba(74,158,255,.2);}
.bar-wrap{width:80px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;}
.bar-fill{height:100%;border-radius:3px;}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .3s ease forwards;}
</style>
</head>
<body>
<header>
    <div class="logo">GMS<span>/</span>Workload</div>
    <a href="reports_hub.php" class="back">← Reports</a>
    <a href="grower_risk.php" class="back">🎯 Risk</a>
    <div style="margin-left:auto;font-size:10px;color:var(--muted);">Season: <?=htmlspecialchars($seasonName)?></div>
</header>
<div class="page">
    <div style="margin-bottom:20px;" class="fade-up">
        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:900;letter-spacing:-.5px;">⚖️ Officer Workload Balance</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Grower assignments · visit output · km efficiency · loan portfolio per officer</div>
        <div style="display:flex;gap:16px;margin-top:12px;font-size:10px;color:var(--dim);">
            <span>Avg assigned: <b style="color:var(--text)"><?=$avgAssigned?></b></span>
            <span>Avg visits: <b style="color:var(--text)"><?=$avgVisits?></b></span>
            <span>Officers: <b style="color:var(--text)"><?=count($officerRows)?></b></span>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2 fade-up" style="margin-bottom:24px;">
        <div class="card">
            <div class="card-title">Growers Assigned vs Visited per Officer</div>
            <canvas id="assignChart" height="220"></canvas>
        </div>
        <div class="card">
            <div class="card-title">km per Visit — Efficiency Comparison</div>
            <canvas id="effChart" height="220"></canvas>
        </div>
    </div>

    <!-- Detail table -->
    <div class="section-title fade-up">Officer Workload Detail</div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;" class="fade-up">
    <div style="overflow-x:auto;"><table>
        <thead><tr>
            <th>#</th><th>Officer</th><th>Assigned</th><th>Visited</th>
            <th>Not Visited</th><th>Coverage</th><th>Total Visits</th>
            <th>Active Days</th><th>Visits/Day</th>
            <th>Total km</th><th>km/Visit</th><th>Loan Portfolio</th><th>Load</th>
        </tr></thead>
        <tbody>
        <?php foreach($officerRows as $i=>$row):
            $cov=(float)$row['coverage_pct'];
            $barW=min(100,$cov);
            $barCol=$cov>=80?'var(--green)':($cov>=50?'var(--amber)':'var(--red)');
            $load=$row['assigned']>$avgAssigned*1.3?'overloaded':($row['assigned']<$avgAssigned*0.7?'light':'balanced');
            $loadBadge=$load==='overloaded'?'b-red':($load==='light'?'b-blue':'b-green');
            $kmV=(float)$row['km_per_visit'];
            $kmCol=$kmV>50?'var(--red)':($kmV>25?'var(--amber)':'var(--green)');
        ?>
        <tr>
            <td style="color:var(--muted);font-size:10px;"><?=$i+1?></td>
            <td style="font-weight:700;color:var(--text);"><?=htmlspecialchars($row['officer_name'])?></td>
            <td class="mono"><?=$row['assigned']?></td>
            <td class="mono" style="color:var(--green);"><?=$row['visited']?></td>
            <td class="mono" style="color:<?=$row['not_visited']>0?'var(--red)':'var(--muted)'?>"><?=$row['not_visited']?></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div class="bar-wrap"><div class="bar-fill" style="width:<?=$barW?>%;background:<?=$barCol?>"></div></div>
                    <span class="mono" style="font-size:10px;color:<?=$barCol?>"><?=$cov?>%</span>
                </div>
            </td>
            <td class="mono"><?=$row['total_visits']?></td>
            <td class="mono"><?=$row['active_days']?></td>
            <td class="mono" style="color:<?=(float)$row['visits_per_day']>=5?'var(--green)':((float)$row['visits_per_day']>=3?'var(--amber)':'var(--red)')?>"><?=$row['visits_per_day']??'—'?></td>
            <td class="mono"><?=number_format($row['total_km'],0)?></td>
            <td class="mono" style="color:<?=$kmCol?>"><?=$kmV>0?$kmV:'—'?></td>
            <td class="mono" style="color:var(--blue);">$<?=number_format($row['loan_value_managed'],0)?></td>
            <td><span class="badge <?=$loadBadge?>"><?=ucfirst($load)?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>
<?php
$names=json_encode(array_column($officerRows,'officer_name'));
$assigned=json_encode(array_column($officerRows,'assigned'));
$visited=json_encode(array_column($officerRows,'visited'));
$kmPerVisit=json_encode(array_map(fn($r)=>(float)$r['km_per_visit'],$officerRows));
?>
<script>
Chart.defaults.color='#4a6b4a';Chart.defaults.borderColor='rgba(28,46,28,.6)';
Chart.defaults.font.family="'Space Mono',monospace";Chart.defaults.font.size=10;
Chart.defaults.plugins.tooltip.backgroundColor='#162016';Chart.defaults.plugins.legend.labels.usePointStyle=true;

new Chart(document.getElementById('assignChart'),{type:'bar',data:{
    labels:<?=$names?>,
    datasets:[
        {label:'Assigned',data:<?=$assigned?>,backgroundColor:'rgba(74,158,255,.4)',borderColor:'#4a9eff',borderWidth:1,borderRadius:4},
        {label:'Visited', data:<?=$visited?>, backgroundColor:'rgba(61,220,104,.6)',borderColor:'#3ddc68',borderWidth:1,borderRadius:4}
    ]},options:{responsive:true,scales:{y:{beginAtZero:true,grid:{color:'rgba(28,46,28,.8)'}},x:{grid:{display:false}}}}
});

new Chart(document.getElementById('effChart'),{type:'bar',data:{
    labels:<?=$names?>,
    datasets:[{label:'km/visit',data:<?=$kmPerVisit?>,
        backgroundColor:<?=$kmPerVisit?>.map(v=>v>50?'rgba(232,64,64,.5)':v>25?'rgba(245,166,35,.5)':'rgba(61,220,104,.5)'),
        borderWidth:1,borderRadius:4}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(28,46,28,.8)'},ticks:{callback:v=>v+'km'}},x:{grid:{display:false}}}}
});
</script>
</body></html>
