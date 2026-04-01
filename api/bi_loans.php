<?php ini_set("display_errors",1); error_reporting(E_ALL);
/**
 * bi_loans.php — GMS BI: Loans & Inputs
 * Tables: loans, products, growers, users, grower_field_officer, field_officers, seasons
 */
require "bi_shared.php";

$selSeason  = bi_filter_int('season_id', $activeSeasonId);
$selOfficer = bi_filter_int('officer_id');
$selCluster = bi_filter_str('cluster');

// Base WHERE
$where = "WHERE l.seasonid={$selSeason}";
if ($selOfficer) $where .= " AND fo.id={$selOfficer}";
if ($selCluster) $where .= " AND g.area='".$conn->real_escape_string($selCluster)."'";

// ── KPIs ──────────────────────────────────────────────────────────────────────
$kpi = ['total'=>0,'verified'=>0,'unverified'=>0,'processed'=>0,'value'=>0,'rate'=>0];
$r = $conn->query("
    SELECT COUNT(l.id) AS total,
        SUM(CASE WHEN l.verified=1 THEN 1 ELSE 0 END)   AS verified,
        SUM(CASE WHEN l.verified=0 THEN 1 ELSE 0 END)   AS unverified,
        SUM(CASE WHEN l.processed=1 THEN 1 ELSE 0 END)  AS processed,
        COALESCE(SUM(l.product_total_cost),0)            AS value
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    JOIN grower_field_officer gfo ON gfo.growerid=l.growerid AND gfo.seasonid={$selSeason}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    {$where}
");
if ($r && $row=$r->fetch_assoc()) {
    $kpi['total']      = (int)$row['total'];
    $kpi['verified']   = (int)$row['verified'];
    $kpi['unverified'] = (int)$row['unverified'];
    $kpi['processed']  = (int)$row['processed'];
    $kpi['value']      = (float)$row['value'];
    $kpi['rate']       = $kpi['total']>0 ? round($kpi['verified']/$kpi['total']*100,1) : 0;
}
$loanStatus = bi_status($kpi['rate'], 80);

// ── Season trend ──────────────────────────────────────────────────────────────
$trendLabels=[]; $trendTotal=[]; $trendVerified=[];
$tr = $conn->query("
    SELECT s.name,
        COUNT(l.id) AS total,
        SUM(CASE WHEN l.verified=1 THEN 1 ELSE 0 END) AS verified
    FROM seasons s LEFT JOIN loans l ON l.seasonid=s.id
    GROUP BY s.id,s.name ORDER BY s.id DESC LIMIT 6
");
$trows=[];
while($tr&&$row=$tr->fetch_assoc()) $trows[]=$row;
$trows=array_reverse($trows);
foreach($trows as $row){
    $trendLabels[]  =$row['name'];
    $trendTotal[]   =(int)$row['total'];
    $trendVerified[]=(int)$row['verified'];
}

// ── Loans by product ──────────────────────────────────────────────────────────
$prodLabels=[]; $prodValues=[];
$pr=$conn->query("
    SELECT p.name, COUNT(l.id) AS cnt, SUM(l.product_total_cost) AS total
    FROM loans l JOIN products p ON p.id=l.productid
    JOIN growers g ON g.id=l.growerid
    JOIN grower_field_officer gfo ON gfo.growerid=l.growerid AND gfo.seasonid={$selSeason}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    {$where} GROUP BY p.id,p.name ORDER BY total DESC LIMIT 8
");
while($pr&&$row=$pr->fetch_assoc()){$prodLabels[]=$row['name'];$prodValues[]=round($row['total'],2);}

// ── Officer league ────────────────────────────────────────────────────────────
$officerRows=[];
$or=$conn->query("
    SELECT fo.name AS officer_name,
        COUNT(l.id) AS loans,
        SUM(l.product_total_cost) AS value,
        SUM(CASE WHEN l.verified=1 THEN 1 ELSE 0 END) AS verified,
        ROUND(SUM(CASE WHEN l.verified=1 THEN 1 ELSE 0 END)/NULLIF(COUNT(l.id),0)*100,1) AS rate
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    JOIN grower_field_officer gfo ON gfo.growerid=l.growerid AND gfo.seasonid={$selSeason}
    JOIN field_officers fo ON fo.userid=gfo.field_officerid
    WHERE l.seasonid={$selSeason}
    GROUP BY fo.id,fo.name ORDER BY rate DESC
");
while($or&&$row=$or->fetch_assoc()) $officerRows[]=$row;

// ── Unverified growers (operational) ─────────────────────────────────────────
$unverifiedRows=[];
if($viewMode==='operational'){
    $uv=$conn->query("
        SELECT CONCAT(g.name,' ',g.surname) AS grower_name, g.area AS cluster,
            CONCAT(u.name,' ',u.surname) AS officer_name,
            COUNT(l.id) AS loan_count,
            SUM(l.product_total_cost) AS total_value,
            MIN(l.created_at) AS loan_date
        FROM loans l
        JOIN growers g ON g.id=l.growerid
        JOIN users u ON u.id=l.userid
        WHERE l.seasonid={$selSeason} AND l.verified=0
        " .($selOfficer?" AND u.id=(SELECT userid FROM field_officers WHERE id={$selOfficer} LIMIT 1)":"").
        ($selCluster?" AND g.area='".$conn->real_escape_string($selCluster)."'":"")."
        GROUP BY g.id,g.name,g.surname,g.area,u.name,u.surname
        ORDER BY total_value DESC LIMIT 50
    ");
    while($uv&&$row=$uv->fetch_assoc()) $unverifiedRows[]=$row;
}

$trendLabelsJson=json_encode($trendLabels);
$trendTotalJson =json_encode($trendTotal);
$trendVerJson   =json_encode($trendVerified);
$prodLabelsJson =json_encode($prodLabels);
$prodValuesJson =json_encode($prodValues);
?>
<!DOCTYPE html><html lang="en">
<head><?php bi_html_head('Loans BI');?></head>
<body>
<?php bi_topbar('loans');?>
<div class="bi-wrap">
    <div class="bi-page-header bi-animate">
        <h1>Loans &amp; Inputs</h1>
        <p>Loan issuance, verification pipeline and officer activity</p>
        <span class="bi-season-badge">📅 <?=htmlspecialchars($activeSeasonName)?></span>
    </div>
    <?php bi_filter_bar($conn,['season','officer','cluster']);?>

    <div class="bi-section-title bi-animate">Season KPIs</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <div class="bi-kpi bi-animate bi-animate-delay-1">
            <div class="bi-kpi-icon">✅</div>
            <div class="bi-kpi-label">Verification Rate</div>
            <div class="bi-kpi-value" style="color:var(--bi-primary)"><?=$kpi['rate']?>%</div>
            <div class="bi-kpi-sub"><?=$kpi['verified']?> of <?=$kpi['total']?> verified</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$loanStatus['class']?>"><?=$loanStatus['label']?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-2">
            <div class="bi-kpi-icon">💰</div>
            <div class="bi-kpi-label">Total Loan Value</div>
            <div class="bi-kpi-value" style="color:var(--bi-blue)">$<?=number_format($kpi['value'],0)?></div>
            <div class="bi-kpi-sub"><?=$kpi['total']?> loans issued</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-3">
            <div class="bi-kpi-icon">⏳</div>
            <div class="bi-kpi-label">Unverified</div>
            <div class="bi-kpi-value" style="color:var(--bi-red)"><?=$kpi['unverified']?></div>
            <div class="bi-kpi-sub">Pending verification</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$kpi['unverified']>0?'bi-badge-danger':'bi-badge-success'?>"><?=$kpi['unverified']>0?'Needs Action':'Clear'?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-4">
            <div class="bi-kpi-icon">📋</div>
            <div class="bi-kpi-label">Processed</div>
            <div class="bi-kpi-value" style="color:var(--bi-purple)"><?=$kpi['processed']?></div>
            <div class="bi-kpi-sub">of <?=$kpi['total']?> total loans</div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Trends</div>
    <div class="bi-grid-2" style="margin-bottom:28px;">
        <div class="bi-card bi-animate bi-animate-delay-1">
            <div class="bi-card-title"><span class="dot"></span>Loans Issued vs Verified — by Season</div>
            <div class="bi-chart-wrap"><canvas id="trendChart" height="220"></canvas></div>
        </div>
        <div class="bi-card bi-animate bi-animate-delay-2">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-amber)"></span>Top Products by Value</div>
            <div class="bi-chart-wrap"><canvas id="productChart" height="220"></canvas></div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Officer League</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot"></span>Loan Verification Rate by Field Officer</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>#</th><th>Field Officer</th><th>Loans</th><th>Value</th><th>Verified</th><th>Rate</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($officerRows as $i=>$row): $s=bi_status($row['rate'],80); ?>
                <tr>
                    <td class="rank"><?=$i+1?></td>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['officer_name'])?></td>
                    <td><?=$row['loans']?></td>
                    <td>$<?=number_format($row['value'],0)?></td>
                    <td style="color:var(--bi-primary);"><?=$row['verified']?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:80px;height:6px;background:var(--bi-surface2);border-radius:3px;"><div style="height:100%;width:<?=min(100,$row['rate'])?>%;background:var(--bi-primary);border-radius:3px;"></div></div>
                            <span style="font-family:var(--bi-mono);font-size:12px;"><?=$row['rate']?>%</span>
                        </div>
                    </td>
                    <td><span class="bi-badge <?=$s['class']?>"><?=$s['label']?></span></td>
                </tr>
                <?php endforeach; if(empty($officerRows)):?><tr><td colspan="7" class="bi-empty">No data</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($viewMode==='operational'): ?>
    <div class="bi-section-title bi-animate">Unverified Loans by Grower</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-red)"></span>Growers with Pending Verification</div>
        <div class="bi-table-wrap">
            <table class="bi-table">
                <thead><tr><th>Grower</th><th>Area</th><th>Officer</th><th>Loans</th><th>Value</th><th>Loan Date</th></tr></thead>
                <tbody>
                <?php foreach($unverifiedRows as $row): ?>
                <tr>
                    <td style="font-weight:600;color:var(--bi-text);"><?=htmlspecialchars($row['grower_name'])?></td>
                    <td><?=htmlspecialchars($row['cluster'])?></td>
                    <td><?=htmlspecialchars($row['officer_name'])?></td>
                    <td><?=$row['loan_count']?></td>
                    <td style="color:var(--bi-amber);font-weight:600;">$<?=number_format($row['total_value'],2)?></td>
                    <td style="font-size:12px;color:var(--bi-text-muted);"><?=date('d M Y',strtotime($row['loan_date']))?></td>
                </tr>
                <?php endforeach; if(empty($unverifiedRows)):?><tr><td colspan="6" class="bi-empty">✅ No unverified loans</td></tr><?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif;?>
</div>
<?php bi_chart_defaults();?>
<script>
new Chart(document.getElementById('trendChart'),{type:'bar',data:{labels:<?=$trendLabelsJson?>,datasets:[{label:'Total',data:<?=$trendTotalJson?>,backgroundColor:'rgba(59,130,246,0.4)',borderColor:'#3B82F6',borderWidth:1,borderRadius:4},{label:'Verified',data:<?=$trendVerJson?>,backgroundColor:'rgba(16,185,129,0.7)',borderColor:'#10B981',borderWidth:1,borderRadius:4}]},options:{responsive:true,scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.05)'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('productChart'),{type:'bar',data:{labels:<?=$prodLabelsJson?>,datasets:[{label:'Value ($)',data:<?=$prodValuesJson?>,backgroundColor:['#10B981CC','#3B82F6CC','#F59E0BCC','#EF4444CC','#8B5CF6CC','#14B8A6CC','#F97316CC','#EC4899CC'],borderRadius:6}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>'$'+v.toLocaleString()}},y:{grid:{display:false}}}}});
</script>
</body></html>
