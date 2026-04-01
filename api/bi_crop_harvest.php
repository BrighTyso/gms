<?php ini_set("display_errors",1); error_reporting(E_ALL);
/**
 * bi_crop_harvest.php — GMS BI: Crop Activity
 * Uses tobacco_transplanting, visits, growers, grower_field_officer, field_officers
 * No harvest_records/curing_data — uses actual GMS tables
 */
require "bi_shared.php";

$selSeason  = bi_filter_int('season_id', $activeSeasonId);
$selOfficer = bi_filter_int('officer_id');
$selCluster = bi_filter_str('cluster');

// ── KPIs from transplanting data ──────────────────────────────────────────────
$kpi=['transplanted'=>0,'growers'=>0,'avg_hectares'=>0,'avg_survival'=>0];
$r=$conn->query("
    SELECT COUNT(*) AS cnt,
           COUNT(DISTINCT growerid) AS growers,
           ROUND(AVG(hectares_transplanted),2) AS avg_ha,
           ROUND(AVG(transplant_survival_rate),1) AS avg_survival
    FROM tobacco_transplanting
    WHERE seasonid={$selSeason}
    " .($selCluster?" AND growerid IN (SELECT id FROM growers WHERE area='".$conn->real_escape_string($selCluster)."')":"")
);
if($r&&$row=$r->fetch_assoc()){
    $kpi['transplanted'] =(int)$row['cnt'];
    $kpi['growers']      =(int)$row['growers'];
    $kpi['avg_hectares'] =(float)$row['avg_ha'];
    $kpi['avg_survival'] =(float)$row['avg_survival'];
}

// ── Vigor distribution ────────────────────────────────────────────────────────
$vigorDist=['Good'=>0,'Fair'=>0,'Poor'=>0];
$vr=$conn->query("
    SELECT transplant_vigor, COUNT(*) AS cnt
    FROM tobacco_transplanting WHERE seasonid={$selSeason}
    GROUP BY transplant_vigor
");
while($vr&&$row=$vr->fetch_assoc()){
    $v=ucfirst(strtolower(trim($row['transplant_vigor'])));
    if(isset($vigorDist[$v])) $vigorDist[$v]=(int)$row['cnt'];
    else $vigorDist['Fair']+=(int)$row['cnt'];
}

// ── Area breakdown ────────────────────────────────────────────────────────────
$areaRows=[];
$ar=$conn->query("
    SELECT g.area,
        COUNT(DISTINCT tt.growerid) AS growers,
        ROUND(SUM(tt.hectares_transplanted),2) AS total_ha,
        ROUND(AVG(tt.transplant_survival_rate),1) AS avg_survival
    FROM tobacco_transplanting tt
    JOIN growers g ON g.id=tt.growerid
    WHERE tt.seasonid={$selSeason}
    " .($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
    GROUP BY g.area ORDER BY total_ha DESC
");
while($ar&&$row=$ar->fetch_assoc()) $areaRows[]=$row;

// ── Season trend (transplanting) ──────────────────────────────────────────────
$trendLabels=[]; $trendGrowers=[]; $trendHa=[];
$tr=$conn->query("
    SELECT s.name,
        COUNT(DISTINCT tt.growerid) AS growers,
        ROUND(SUM(tt.hectares_transplanted),2) AS total_ha
    FROM seasons s LEFT JOIN tobacco_transplanting tt ON tt.seasonid=s.id
    GROUP BY s.id,s.name ORDER BY s.id DESC LIMIT 6
");
$trows=[];
while($tr&&$row=$tr->fetch_assoc()) $trows[]=$row;
$trows=array_reverse($trows);
foreach($trows as $row){
    $trendLabels[]=$row['name'];
    $trendGrowers[]=(int)$row['growers'];
    $trendHa[]=(float)$row['total_ha'];
}

// ── Growers with pest/disease issues (operational) ────────────────────────────
$issueRows=[];
if($viewMode==='operational'){
    $ir=$conn->query("
        SELECT CONCAT(g.name,' ',g.surname) AS grower_name, g.grower_num, g.area,
            fo.name AS officer_name,
            tt.transplant_pests, tt.transplant_diseases,
            tt.transplant_vigor, tt.transplant_survival_rate,
            tt.created_at
        FROM tobacco_transplanting tt
        JOIN growers g ON g.id=tt.growerid
        JOIN grower_field_officer gfo ON gfo.growerid=tt.growerid AND gfo.seasonid={$selSeason}
        JOIN field_officers fo ON fo.userid=gfo.field_officerid
        WHERE tt.seasonid={$selSeason}
          AND (tt.transplant_pests IS NOT NULL AND tt.transplant_pests != ''
            OR tt.transplant_diseases IS NOT NULL AND tt.transplant_diseases != '')
        " .($selOfficer?" AND fo.id={$selOfficer}":"").
        ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
        ORDER BY tt.transplant_survival_rate ASC LIMIT 30
    ");
    while($ir&&$row=$ir->fetch_assoc()) $issueRows[]=$row;
}

$vigorLabels=json_encode(array_keys($vigorDist));
$vigorValues=json_encode(array_values($vigorDist));
$trendLabelsJson=json_encode($trendLabels);
$trendGrowersJson=json_encode($trendGrowers);
$trendHaJson=json_encode($trendHa);
?>
<!DOCTYPE html><html lang="en">
<head><?php bi_html_head('Crop Activity BI');?></head>
<body>
<?php bi_topbar('crop');?>
<div class="bi-wrap">
    <div class="bi-page-header bi-animate">
        <h1>Crop Activity</h1>
        <p>Transplanting performance, hectarage and crop health by area</p>
        <span class="bi-season-badge">📅 <?=htmlspecialchars($activeSeasonName)?></span>
    </div>
    <?php bi_filter_bar($conn,['season','officer','cluster']);?>

    <div class="bi-section-title bi-animate">Season KPIs</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <div class="bi-kpi bi-animate bi-animate-delay-1">
            <div class="bi-kpi-icon">🌱</div>
            <div class="bi-kpi-label">Transplanting Records</div>
            <div class="bi-kpi-value" style="color:var(--bi-primary)"><?=number_format($kpi['transplanted'])?></div>
            <div class="bi-kpi-sub"><?=$kpi['growers']?> growers with data</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-2">
            <div class="bi-kpi-icon">🌍</div>
            <div class="bi-kpi-label">Avg Hectares</div>
            <div class="bi-kpi-value" style="color:var(--bi-blue)"><?=$kpi['avg_hectares']?> <span style="font-size:16px;font-weight:400;">ha</span></div>
            <div class="bi-kpi-sub">Per transplanting record</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-3">
            <div class="bi-kpi-icon">📈</div>
            <div class="bi-kpi-label">Avg Survival Rate</div>
            <div class="bi-kpi-value" style="color:var(--bi-amber)"><?=$kpi['avg_survival']?>%</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$kpi['avg_survival']>=75?'bi-badge-success':($kpi['avg_survival']>=50?'bi-badge-warning':'bi-badge-danger')?>"><?=$kpi['avg_survival']>=75?'On Track':($kpi['avg_survival']>=50?'At Risk':'Critical')?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-4">
            <div class="bi-kpi-icon">🌿</div>
            <div class="bi-kpi-label">Good Vigor</div>
            <div class="bi-kpi-value" style="color:var(--bi-purple)"><?=$vigorDist['Good']?></div>
            <div class="bi-kpi-sub">of <?=array_sum($vigorDist)?> records</div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Trends</div>
    <div class="bi-grid-2" style="margin-bottom:28px;">
        <div class="bi-card bi-animate bi-animate-delay-1">
            <div class="bi-card-title"><span class="dot"></span>Growers Transplanting — Season Trend</div>
            <div class="bi-chart-wrap"><canvas id="trendChart" height="220"></canvas></div>
        </div>
        <div class="bi-card bi-animate bi-animate-delay-2">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-amber)"></span>Transplant Vigor Distribution</div>
            <div class="bi-chart-wrap" style="max-height:260px;"><canvas id="vigorDonut" height="240"></canvas></div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Area Breakdown</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-blue)"></span>Transplanting by Area</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>#</th><th>Area</th><th>Growers</th><th>Total Ha</th><th>Avg Survival</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($areaRows as $i=>$row): $s=bi_status($row['avg_survival'],75);?>
                <tr>
                    <td class="rank"><?=$i+1?></td>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['area'])?></td>
                    <td><?=$row['growers']?></td>
                    <td style="font-family:var(--bi-mono);"><?=$row['total_ha']?> ha</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:80px;height:6px;background:var(--bi-surface2);border-radius:3px;"><div style="height:100%;width:<?=min(100,$row['avg_survival'])?>%;background:var(--bi-primary);border-radius:3px;"></div></div>
                            <span style="font-family:var(--bi-mono);font-size:12px;"><?=$row['avg_survival']?>%</span>
                        </div>
                    </td>
                    <td><span class="bi-badge <?=$s['class']?>"><?=$s['label']?></span></td>
                </tr>
                <?php endforeach; if(empty($areaRows)):?><tr><td colspan="6" class="bi-empty">No transplanting data this season</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($viewMode==='operational'&&!empty($issueRows)):?>
    <div class="bi-section-title bi-animate">Growers with Pest/Disease Issues</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-red)"></span>Reported Pest or Disease Problems</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th><th>Pests</th><th>Diseases</th><th>Vigor</th><th>Survival %</th></tr></thead>
                <tbody>
                <?php foreach($issueRows as $row):?>
                <tr>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['grower_name'])?></td>
                    <td style="font-family:var(--bi-mono);font-size:11px;"><?=htmlspecialchars($row['grower_num'])?></td>
                    <td><?=htmlspecialchars($row['area'])?></td>
                    <td><?=htmlspecialchars($row['officer_name'])?></td>
                    <td style="color:var(--bi-amber);"><?=htmlspecialchars($row['transplant_pests'])?:'-'?></td>
                    <td style="color:var(--bi-red);"><?=htmlspecialchars($row['transplant_diseases'])?:'-'?></td>
                    <td><?=htmlspecialchars($row['transplant_vigor'])?></td>
                    <td style="font-family:var(--bi-mono);"><?=$row['transplant_survival_rate']?>%</td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif;?>
</div>
<?php bi_chart_defaults();?>
<script>
new Chart(document.getElementById('trendChart'),{type:'bar',data:{labels:<?=$trendLabelsJson?>,datasets:[{label:'Growers',data:<?=$trendGrowersJson?>,backgroundColor:'rgba(16,185,129,0.6)',borderColor:'#10B981',borderWidth:1,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.05)'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('vigorDonut'),{type:'doughnut',data:{labels:<?=$vigorLabels?>,datasets:[{data:<?=$vigorValues?>,backgroundColor:['#10B981','#F59E0B','#EF4444'],borderColor:'#111827',borderWidth:3,hoverOffset:8}]},options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{padding:14}}}}});
</script>
</body></html>
