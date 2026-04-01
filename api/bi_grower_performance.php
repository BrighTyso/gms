<?php ini_set("display_errors",1); error_reporting(E_ALL);
/**
 * bi_grower_performance.php — GMS BI: Grower Performance
 * Tables: growers, grower_field_officer, visits, field_officers, users, seasons
 */
require "bi_shared.php";

$selSeason  = bi_filter_int('season_id', $activeSeasonId);
$selOfficer = bi_filter_int('officer_id');
$selCluster = bi_filter_str('cluster');

// ── KPIs ──────────────────────────────────────────────────────────────────────
$kpi=['total'=>0,'visited'=>0,'no_visit'=>0,'avg_visits'=>0];
$r=$conn->query("
    SELECT COUNT(DISTINCT gfo.growerid)  AS total,
           COUNT(DISTINCT v.growerid)    AS visited
    FROM grower_field_officer gfo
    JOIN growers g ON g.id=gfo.growerid
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
    WHERE gfo.seasonid={$selSeason}
    " .($selOfficer?" AND gfo.field_officerid=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"").
    ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")
);
if($r&&$row=$r->fetch_assoc()){
    $kpi['total']    =(int)$row['total'];
    $kpi['visited']  =(int)$row['visited'];
    $kpi['no_visit'] =$kpi['total']-$kpi['visited'];
}

// Avg visits per grower
$r2=$conn->query("
    SELECT ROUND(AVG(vc),1) AS avg_v FROM (
        SELECT gfo.growerid, COUNT(v.id) AS vc
        FROM grower_field_officer gfo
        LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
        WHERE gfo.seasonid={$selSeason}
        GROUP BY gfo.growerid
    ) t
");
if($r2&&$row=$r2->fetch_assoc()) $kpi['avg_visits']=(float)$row['avg_v'];

// ── Area/cluster breakdown ────────────────────────────────────────────────────
$clusterRows=[];
$cr=$conn->query("
    SELECT g.area AS cluster,
        COUNT(DISTINCT gfo.growerid)  AS total,
        COUNT(DISTINCT v.growerid)    AS visited,
        COUNT(v.id)                   AS visits,
        ROUND(COUNT(DISTINCT v.growerid)/NULLIF(COUNT(DISTINCT gfo.growerid),0)*100,1) AS coverage
    FROM grower_field_officer gfo
    JOIN growers g ON g.id=gfo.growerid
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
    WHERE gfo.seasonid={$selSeason}
    " .($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
    GROUP BY g.area ORDER BY coverage DESC
");
while($cr&&$row=$cr->fetch_assoc()) $clusterRows[]=$row;

$clusterLabels  =json_encode(array_column($clusterRows,'cluster'));
$clusterCoverage=json_encode(array_column($clusterRows,'coverage'));

// ── Season trend ──────────────────────────────────────────────────────────────
$seasonLabels=[]; $seasonCounts=[];
$st=$conn->query("
    SELECT s.name, COUNT(DISTINCT gfo.growerid) AS cnt
    FROM seasons s LEFT JOIN grower_field_officer gfo ON gfo.seasonid=s.id
    GROUP BY s.id,s.name ORDER BY s.id DESC LIMIT 6
");
$srows=[];
while($st&&$row=$st->fetch_assoc()) $srows[]=$row;
$srows=array_reverse($srows);
foreach($srows as $row){$seasonLabels[]=$row['name'];$seasonCounts[]=(int)$row['cnt'];}

// ── Top visited growers ───────────────────────────────────────────────────────
$topGrowers=[];
$tg=$conn->query("
    SELECT CONCAT(g.name,' ',g.surname) AS grower_name, g.grower_num, g.area AS cluster,
        CONCAT(u.name,' ',u.surname) AS officer_name,
        COUNT(v.id) AS visit_count,
        MAX(v.created_at) AS last_visit
    FROM visits v
    JOIN growers g ON g.id=v.growerid
    JOIN users u ON u.id=v.userid
    WHERE v.seasonid={$selSeason}
    " .($selOfficer?" AND u.id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"").
    ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
    GROUP BY g.id,g.name,g.surname,g.grower_num,g.area,u.name,u.surname
    ORDER BY visit_count DESC LIMIT 20
");
while($tg&&$row=$tg->fetch_assoc()) $topGrowers[]=$row;

// ── At-risk: no visit this season (operational) ───────────────────────────────
$atRiskRows=[];
if($viewMode==='operational'){
    $ar=$conn->query("
        SELECT CONCAT(g.name,' ',g.surname) AS grower_name, g.grower_num, g.area AS cluster,
            fo.name AS officer_name,
            MAX(v2.created_at) AS last_ever_visit
        FROM grower_field_officer gfo
        JOIN growers g ON g.id=gfo.growerid
        JOIN field_officers fo ON fo.userid=gfo.field_officerid
        LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
        LEFT JOIN visits v2 ON v2.growerid=gfo.growerid
        WHERE gfo.seasonid={$selSeason} AND v.id IS NULL
        " .($selOfficer?" AND fo.id={$selOfficer}":"").
        ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
        GROUP BY g.id,g.name,g.surname,g.grower_num,g.area,fo.name
        ORDER BY last_ever_visit ASC LIMIT 50
    ");
    while($ar&&$row=$ar->fetch_assoc()) $atRiskRows[]=$row;
}

$seasonLabelsJson=json_encode($seasonLabels);
$seasonCountsJson=json_encode($seasonCounts);
?>
<!DOCTYPE html><html lang="en">
<head><?php bi_html_head('Grower Performance BI');?></head>
<body>
<?php bi_topbar('grower');?>
<div class="bi-wrap">
    <div class="bi-page-header bi-animate">
        <h1>Grower Performance</h1>
        <p>Coverage by area, visit activity and at-risk grower identification</p>
        <span class="bi-season-badge">📅 <?=htmlspecialchars($activeSeasonName)?></span>
    </div>
    <?php bi_filter_bar($conn,['season','officer','cluster']);?>

    <div class="bi-section-title bi-animate">Season KPIs</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <div class="bi-kpi bi-animate bi-animate-delay-1">
            <div class="bi-kpi-icon">🌾</div>
            <div class="bi-kpi-label">Total Growers</div>
            <div class="bi-kpi-value" style="color:var(--bi-primary)"><?=number_format($kpi['total'])?></div>
            <div class="bi-kpi-sub">Assigned this season</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-2">
            <div class="bi-kpi-icon">✅</div>
            <div class="bi-kpi-label">Visited</div>
            <div class="bi-kpi-value" style="color:var(--bi-blue)"><?=number_format($kpi['visited'])?></div>
            <div class="bi-kpi-sub"><?=$kpi['total']>0?round($kpi['visited']/$kpi['total']*100,1):0?>% of total</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-3">
            <div class="bi-kpi-icon">❌</div>
            <div class="bi-kpi-label">Not Yet Visited</div>
            <div class="bi-kpi-value" style="color:var(--bi-red)"><?=number_format($kpi['no_visit'])?></div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$kpi['no_visit']>0?'bi-badge-warning':'bi-badge-success'?>"><?=$kpi['no_visit']>0?'Action Needed':'All Visited'?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-4">
            <div class="bi-kpi-icon">📊</div>
            <div class="bi-kpi-label">Avg Visits/Grower</div>
            <div class="bi-kpi-value" style="color:var(--bi-amber)"><?=$kpi['avg_visits']?></div>
            <div class="bi-kpi-sub">This season</div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Analytics</div>
    <div class="bi-grid-2" style="margin-bottom:28px;">
        <div class="bi-card bi-animate bi-animate-delay-1">
            <div class="bi-card-title"><span class="dot"></span>Grower Count — Season Trend</div>
            <div class="bi-chart-wrap"><canvas id="seasonTrend" height="220"></canvas></div>
        </div>
        <div class="bi-card bi-animate bi-animate-delay-2">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-blue)"></span>Coverage by Area</div>
            <div class="bi-chart-wrap"><canvas id="clusterChart" height="220"></canvas></div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Area Breakdown</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-amber)"></span>Performance by Area</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>#</th><th>Area</th><th>Growers</th><th>Visited</th><th>Coverage</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($clusterRows as $i=>$row): $s=bi_status($row['coverage'],90);?>
                <tr>
                    <td class="rank"><?=$i+1?></td>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['cluster'])?></td>
                    <td><?=$row['total']?></td>
                    <td><?=$row['visited']?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:80px;height:6px;background:var(--bi-surface2);border-radius:3px;"><div style="height:100%;width:<?=min(100,$row['coverage'])?>%;background:var(--bi-primary);border-radius:3px;"></div></div>
                            <span style="font-family:var(--bi-mono);font-size:12px;"><?=$row['coverage']?>%</span>
                        </div>
                    </td>
                    <td><span class="bi-badge <?=$s['class']?>"><?=$s['label']?></span></td>
                </tr>
                <?php endforeach; if(empty($clusterRows)):?><tr><td colspan="6" class="bi-empty">No area data</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Top Visited Growers</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-purple)"></span>Most Visited This Season</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>#</th><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th><th>Visits</th><th>Last Visit</th></tr></thead>
                <tbody>
                <?php foreach($topGrowers as $i=>$row):?>
                <tr>
                    <td class="rank"><?=$i+1?></td>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['grower_name'])?></td>
                    <td style="font-family:var(--bi-mono);font-size:11px;"><?=htmlspecialchars($row['grower_num'])?></td>
                    <td><?=htmlspecialchars($row['cluster'])?></td>
                    <td><?=htmlspecialchars($row['officer_name'])?></td>
                    <td style="font-family:var(--bi-mono);color:var(--bi-primary);font-weight:600;"><?=$row['visit_count']?></td>
                    <td style="font-size:12px;color:var(--bi-text-muted);"><?=date('d M Y',strtotime($row['last_visit']))?></td>
                </tr>
                <?php endforeach; if(empty($topGrowers)):?><tr><td colspan="7" class="bi-empty">No visit data</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($viewMode==='operational'&&!empty($atRiskRows)):?>
    <div class="bi-section-title bi-animate">At-Risk Growers</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-red)"></span>No Visit This Season</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>Grower</th><th>Grower #</th><th>Area</th><th>Officer</th><th>Last Ever Visit</th></tr></thead>
                <tbody>
                <?php foreach($atRiskRows as $row):?>
                <tr>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['grower_name'])?></td>
                    <td style="font-family:var(--bi-mono);font-size:11px;"><?=htmlspecialchars($row['grower_num'])?></td>
                    <td><?=htmlspecialchars($row['cluster'])?></td>
                    <td><?=htmlspecialchars($row['officer_name'])?></td>
                    <td style="color:var(--bi-red);font-size:12px;"><?=$row['last_ever_visit']?date('d M Y',strtotime($row['last_ever_visit'])):'Never'?></td>
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
new Chart(document.getElementById('seasonTrend'),{type:'line',data:{labels:<?=$seasonLabelsJson?>,datasets:[{label:'Growers',data:<?=$seasonCountsJson?>,borderColor:'#10B981',backgroundColor:'rgba(16,185,129,0.08)',borderWidth:2,pointRadius:4,tension:0.4,fill:true}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.05)'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('clusterChart'),{type:'bar',data:{labels:<?=$clusterLabels?>,datasets:[{label:'Coverage %',data:<?=$clusterCoverage?>,backgroundColor:<?=$clusterCoverage?>.map(v=>v>=90?'rgba(16,185,129,0.7)':v>=70?'rgba(245,158,11,0.7)':'rgba(239,68,68,0.7)'),borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}});
</script>
</body></html>
