<?php ini_set("display_errors",1); error_reporting(E_ALL);
/**
 * bi_field_officer.php — GMS BI: Field Officer Activity
 * Tables: visits, field_officers, grower_field_officer, growers, users, seasons
 */
require "bi_shared.php";

$selSeason  = bi_filter_int('season_id', $activeSeasonId);
$selOfficer = bi_filter_int('officer_id');
$selCluster = bi_filter_str('cluster');

// ── KPIs ──────────────────────────────────────────────────────────────────────
$kpi=['officers'=>0,'assigned'=>0,'visited'=>0,'coverage'=>0,'total_visits'=>0];
$r=$conn->query("
    SELECT COUNT(DISTINCT fo.id) AS officers,
           COUNT(DISTINCT gfo.growerid) AS assigned,
           COUNT(DISTINCT v.growerid)   AS visited,
           COUNT(v.id)                  AS total_visits
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid=fo.userid AND gfo.seasonid={$selSeason}
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
    " .($selOfficer?" WHERE fo.id={$selOfficer}":"")
);
if($r&&$row=$r->fetch_assoc()){
    $kpi['officers']    =(int)$row['officers'];
    $kpi['assigned']    =(int)$row['assigned'];
    $kpi['visited']     =(int)$row['visited'];
    $kpi['total_visits']=(int)$row['total_visits'];
    $kpi['coverage']    =$kpi['assigned']>0 ? round($kpi['visited']/$kpi['assigned']*100,1) : 0;
}
$covStatus=bi_status($kpi['coverage'],90);

// ── Officer performance table ─────────────────────────────────────────────────
$officerRows=[];
$or=$conn->query("
    SELECT fo.id, fo.name AS officer_name,
        COUNT(DISTINCT gfo.growerid)  AS assigned,
        COUNT(DISTINCT v.growerid)    AS visited,
        COUNT(v.id)                   AS total_visits,
        ROUND(COUNT(DISTINCT v.growerid)/NULLIF(COUNT(DISTINCT gfo.growerid),0)*100,1) AS coverage,
        MAX(v.created_at)             AS last_visit
    FROM field_officers fo
    JOIN grower_field_officer gfo ON gfo.field_officerid=fo.userid AND gfo.seasonid={$selSeason}
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
    " .($selOfficer?" WHERE fo.id={$selOfficer}":"")."
    GROUP BY fo.id,fo.name ORDER BY coverage DESC
");
while($or&&$row=$or->fetch_assoc()) $officerRows[]=$row;

// ── Season trend ──────────────────────────────────────────────────────────────
$trendLabels=[]; $trendCov=[]; $trendVisits=[];
$tr=$conn->query("
    SELECT s.name,
        COUNT(DISTINCT gfo.growerid) AS assigned,
        COUNT(DISTINCT v.growerid)   AS visited,
        COUNT(v.id)                  AS visits
    FROM seasons s
    LEFT JOIN grower_field_officer gfo ON gfo.seasonid=s.id
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid=s.id
    GROUP BY s.id,s.name ORDER BY s.id DESC LIMIT 6
");
$trows=[];
while($tr&&$row=$tr->fetch_assoc()) $trows[]=$row;
$trows=array_reverse($trows);
foreach($trows as $row){
    $trendLabels[]=$row['name'];
    $trendCov[]   =$row['assigned']>0?round($row['visited']/$row['assigned']*100,1):0;
    $trendVisits[]=(int)$row['visits'];
}

// ── Unvisited growers (operational) ──────────────────────────────────────────
$unvisitedRows=[];
if($viewMode==='operational'){
    $uv=$conn->query("
        SELECT CONCAT(g.name,' ',g.surname) AS grower_name, g.area AS cluster,
            fo.name AS officer_name,
            MAX(v2.created_at) AS last_visit,
            DATEDIFF(NOW(), MAX(v2.created_at)) AS days_since
        FROM grower_field_officer gfo
        JOIN growers g ON g.id=gfo.growerid
        JOIN field_officers fo ON fo.userid=gfo.field_officerid
        LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
        LEFT JOIN visits v2 ON v2.growerid=gfo.growerid
        WHERE gfo.seasonid={$selSeason}
        " .($selOfficer?" AND fo.id={$selOfficer}":"").
        ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
        GROUP BY gfo.growerid,g.name,g.surname,g.area,fo.name
        HAVING v.id IS NULL OR days_since > 30
        ORDER BY days_since DESC LIMIT 50
    ");
    while($uv&&$row=$uv->fetch_assoc()) $unvisitedRows[]=$row;
}

// ── Visit frequency buckets ───────────────────────────────────────────────────
$freqData=[0,0,0,0,0];
$fr=$conn->query("
    SELECT gfo.growerid, COUNT(v.id) AS vc
    FROM grower_field_officer gfo
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$selSeason}
    WHERE gfo.seasonid={$selSeason}
    GROUP BY gfo.growerid
");
while($fr&&$row=$fr->fetch_assoc()){
    $vc=(int)$row['vc'];
    if($vc===0) $freqData[0]++;
    elseif($vc<=2) $freqData[1]++;
    elseif($vc<=5) $freqData[2]++;
    elseif($vc<=10) $freqData[3]++;
    else $freqData[4]++;
}

$trendLabelsJson=json_encode($trendLabels);
$trendCovJson   =json_encode($trendCov);
$freqDataJson   =json_encode($freqData);
?>
<!DOCTYPE html><html lang="en">
<head><?php bi_html_head('Field Officer BI');?></head>
<body>
<?php bi_topbar('officer');?>
<div class="bi-wrap">
    <div class="bi-page-header bi-animate">
        <h1>Field Officer Activity</h1>
        <p>Coverage, visit frequency and officer performance analysis</p>
        <span class="bi-season-badge">📅 <?=htmlspecialchars($activeSeasonName)?></span>
    </div>
    <?php bi_filter_bar($conn,['season','officer','cluster']);?>

    <div class="bi-section-title bi-animate">Season KPIs</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <div class="bi-kpi bi-animate bi-animate-delay-1">
            <div class="bi-kpi-icon">📍</div>
            <div class="bi-kpi-label">Field Coverage</div>
            <div class="bi-kpi-value" style="color:var(--bi-blue)"><?=$kpi['coverage']?>%</div>
            <div class="bi-kpi-sub"><?=$kpi['visited']?> of <?=$kpi['assigned']?> growers visited</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$covStatus['class']?>"><?=$covStatus['label']?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-2">
            <div class="bi-kpi-icon">👤</div>
            <div class="bi-kpi-label">Active Officers</div>
            <div class="bi-kpi-value" style="color:var(--bi-primary)"><?=$kpi['officers']?></div>
            <div class="bi-kpi-sub">This season</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-3">
            <div class="bi-kpi-icon">🗓️</div>
            <div class="bi-kpi-label">Total Visits</div>
            <div class="bi-kpi-value" style="color:var(--bi-purple)"><?=number_format($kpi['total_visits'])?></div>
            <div class="bi-kpi-sub">All growers combined</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-4">
            <div class="bi-kpi-icon">❌</div>
            <div class="bi-kpi-label">Not Yet Visited</div>
            <div class="bi-kpi-value" style="color:var(--bi-red)"><?=$kpi['assigned']-$kpi['visited']?></div>
            <div class="bi-kpi-sub">Growers with 0 visits</div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Trends</div>
    <div class="bi-grid-2" style="margin-bottom:28px;">
        <div class="bi-card bi-animate bi-animate-delay-1">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-blue)"></span>Coverage % — Season Trend</div>
            <div class="bi-chart-wrap"><canvas id="covTrend" height="220"></canvas></div>
        </div>
        <div class="bi-card bi-animate bi-animate-delay-2">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-purple)"></span>Visit Frequency Distribution</div>
            <div class="bi-chart-wrap"><canvas id="freqChart" height="220"></canvas></div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Officer League</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot"></span>Coverage &amp; Visits by Officer</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>#</th><th>Officer</th><th>Assigned</th><th>Visited</th><th>Total Visits</th><th>Coverage</th><th>Last Visit</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($officerRows as $i=>$row): $s=bi_status($row['coverage'],90);?>
                <tr>
                    <td class="rank"><?=$i+1?></td>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['officer_name'])?></td>
                    <td><?=$row['assigned']?></td>
                    <td><?=$row['visited']?></td>
                    <td style="font-family:var(--bi-mono);"><?=$row['total_visits']?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:80px;height:6px;background:var(--bi-surface2);border-radius:3px;"><div style="height:100%;width:<?=min(100,$row['coverage'])?>%;background:var(--bi-blue);border-radius:3px;"></div></div>
                            <span style="font-family:var(--bi-mono);font-size:12px;"><?=$row['coverage']?>%</span>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--bi-text-muted);"><?=$row['last_visit']?date('d M Y',strtotime($row['last_visit'])):'Never'?></td>
                    <td><span class="bi-badge <?=$s['class']?>"><?=$s['label']?></span></td>
                </tr>
                <?php endforeach; if(empty($officerRows)):?><tr><td colspan="8" class="bi-empty">No data</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($viewMode==='operational'):?>
    <div class="bi-section-title bi-animate">Unvisited / Overdue Growers</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-amber)"></span>Not visited this season or 30+ days overdue</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>Grower</th><th>Area</th><th>Officer</th><th>Last Visit</th><th>Days Since</th></tr></thead>
                <tbody>
                <?php foreach($unvisitedRows as $row): $d=$row['days_since']??null; $dStyle=$d===null||$d>60?'color:var(--bi-red);font-weight:600;':'color:var(--bi-amber);';?>
                <tr>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['grower_name'])?></td>
                    <td><?=htmlspecialchars($row['cluster'])?></td>
                    <td><?=htmlspecialchars($row['officer_name'])?></td>
                    <td style="font-size:12px;color:var(--bi-text-muted);"><?=$row['last_visit']?date('d M Y',strtotime($row['last_visit'])):'—'?></td>
                    <td style="font-family:var(--bi-mono);<?=$dStyle?>"><?=$d===null?'Never':$d.'d'?></td>
                </tr>
                <?php endforeach; if(empty($unvisitedRows)):?><tr><td colspan="5" class="bi-empty">✅ All growers visited</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif;?>
</div>
<?php bi_chart_defaults();?>
<script>
new Chart(document.getElementById('covTrend'),{type:'line',data:{labels:<?=$trendLabelsJson?>,datasets:[{label:'Coverage %',data:<?=$trendCovJson?>,borderColor:'#3B82F6',backgroundColor:'rgba(59,130,246,0.08)',borderWidth:2,pointRadius:4,tension:0.4,fill:true}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('freqChart'),{type:'doughnut',data:{labels:['0 visits','1–2','3–5','6–10','10+'],datasets:[{data:<?=$freqDataJson?>,backgroundColor:['#EF4444','#F59E0B','#3B82F6','#10B981','#8B5CF6'],borderColor:'#111827',borderWidth:3,hoverOffset:8}]},options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{padding:14}}}}});
</script>
</body></html>
