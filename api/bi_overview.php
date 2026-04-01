<?php ini_set("display_errors",1); error_reporting(E_ALL);
/**
 * bi_overview.php
 * GMS Business Intelligence — Master Overview Dashboard
 */
require "bi_shared.php";

// ── KPI 1: Loan Recovery (verified vs total) ──────────────────────────────────
$kpi_loans = ['total'=>0,'verified'=>0,'value'=>0,'rate'=>0];
if ($activeSeasonId) {
    $r = $conn->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN verified=1 THEN 1 ELSE 0 END) AS verified,
               SUM(product_total_cost) AS value
        FROM loans WHERE seasonid={$activeSeasonId}
    ");
    if ($r && $row=$r->fetch_assoc()) {
        $kpi_loans['total']    = (int)$row['total'];
        $kpi_loans['verified'] = (int)$row['verified'];
        $kpi_loans['value']    = (float)$row['value'];
        $kpi_loans['rate']     = $kpi_loans['total']>0 ? round($kpi_loans['verified']/$kpi_loans['total']*100,1) : 0;
    }
}
$loanStatus = bi_status($kpi_loans['rate'], 80);

// ── KPI 2: Field Coverage ─────────────────────────────────────────────────────
$kpi_cov = ['assigned'=>0,'visited'=>0,'rate'=>0];
if ($activeSeasonId) {
    $r = $conn->query("
        SELECT COUNT(DISTINCT gfo.growerid) AS assigned,
               COUNT(DISTINCT v.growerid)   AS visited
        FROM grower_field_officer gfo
        LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid={$activeSeasonId}
        WHERE gfo.seasonid={$activeSeasonId}
    ");
    if ($r && $row=$r->fetch_assoc()) {
        $kpi_cov['assigned'] = (int)$row['assigned'];
        $kpi_cov['visited']  = (int)$row['visited'];
        $kpi_cov['rate']     = $kpi_cov['assigned']>0 ? round($kpi_cov['visited']/$kpi_cov['assigned']*100,1) : 0;
    }
}
$covStatus = bi_status($kpi_cov['rate'], 90);

// ── KPI 3: Active Growers ─────────────────────────────────────────────────────
$growerCount = 0;
if ($activeSeasonId) {
    $r = $conn->query("SELECT COUNT(DISTINCT growerid) AS cnt FROM grower_field_officer WHERE seasonid={$activeSeasonId}");
    if ($r && $row=$r->fetch_assoc()) $growerCount = (int)$row['cnt'];
}

// ── KPI 4: Overdue loans (unverified) ────────────────────────────────────────
$overdueCount = 0;
if ($activeSeasonId) {
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM loans WHERE seasonid={$activeSeasonId} AND verified=0");
    if ($r && $row=$r->fetch_assoc()) $overdueCount = (int)$row['cnt'];
}

// ── Trend: loan verification rate per season ──────────────────────────────────
$trendLabels=[]; $trendRates=[]; $trendCoverage=[];
$tr = $conn->query("
    SELECT s.name,
        COUNT(l.id) AS total,
        SUM(CASE WHEN l.verified=1 THEN 1 ELSE 0 END) AS verified,
        COUNT(DISTINCT gfo.growerid) AS assigned,
        COUNT(DISTINCT v.growerid)   AS visited
    FROM seasons s
    LEFT JOIN loans l ON l.seasonid=s.id
    LEFT JOIN grower_field_officer gfo ON gfo.seasonid=s.id
    LEFT JOIN visits v ON v.growerid=gfo.growerid AND v.seasonid=s.id
    GROUP BY s.id, s.name ORDER BY s.id DESC LIMIT 6
");
$trows=[];
while($tr && $row=$tr->fetch_assoc()) $trows[]=$row;
$trows=array_reverse($trows);
foreach($trows as $row){
    $trendLabels[]  = $row['name'];
    $trendRates[]   = $row['total']>0 ? round($row['verified']/$row['total']*100,1) : 0;
    $trendCoverage[]= $row['assigned']>0 ? round($row['visited']/$row['assigned']*100,1) : 0;
}

// ── Top officer this season ───────────────────────────────────────────────────
$topOfficer = ['name'=>'N/A','visits'=>0];
if ($activeSeasonId) {
    $r = $conn->query("
        SELECT fo.name, COUNT(v.id) AS visits
        FROM visits v
        JOIN field_officers fo ON fo.userid=v.userid
        WHERE v.seasonid={$activeSeasonId}
        GROUP BY fo.id, fo.name ORDER BY visits DESC LIMIT 1
    ");
    if ($r && $row=$r->fetch_assoc()) $topOfficer=$row;
}

// ── Loans by product (top 5) ──────────────────────────────────────────────────
$productLabels=[]; $productValues=[];
if ($activeSeasonId) {
    $r = $conn->query("
        SELECT p.name, COUNT(l.id) AS cnt, SUM(l.product_total_cost) AS total
        FROM loans l JOIN products p ON p.id=l.productid
        WHERE l.seasonid={$activeSeasonId}
        GROUP BY p.id, p.name ORDER BY total DESC LIMIT 5
    ");
    while($r && $row=$r->fetch_assoc()){
        $productLabels[]=$row['name'];
        $productValues[]=round($row['total'],2);
    }
}

$trendLabelsJson  = json_encode($trendLabels);
$trendRatesJson   = json_encode($trendRates);
$trendCovJson     = json_encode($trendCoverage);
$prodLabelsJson   = json_encode($productLabels);
$prodValuesJson   = json_encode($productValues);
?>
<!DOCTYPE html>
<html lang="en">
<head><?php bi_html_head('BI Overview'); ?></head>
<body>
<?php bi_topbar('overview'); ?>
<div class="bi-wrap">
    <div class="bi-page-header bi-animate">
        <h1>Business Intelligence Overview</h1>
        <p>Cross-domain performance summary — Core Africa Group</p>
        <span class="bi-season-badge">📅 Active Season: <?=htmlspecialchars($activeSeasonName)?></span>
    </div>

    <?php if($overdueCount>0): ?>
    <div class="bi-alert bi-animate">
        <div class="dot"></div>
        <div><strong><?=$overdueCount?> unverified loan<?=$overdueCount>1?'s':''?></strong> pending verification this season. <a href="bi_loans.php" style="color:var(--bi-red);text-decoration:underline;">View Loans →</a></div>
    </div>
    <?php endif; ?>

    <div class="bi-section-title bi-animate">Season KPIs</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <div class="bi-kpi bi-animate bi-animate-delay-1">
            <div class="bi-kpi-icon">💰</div>
            <div class="bi-kpi-label">Loan Verification Rate</div>
            <div class="bi-kpi-value" style="color:var(--bi-primary)"><?=$kpi_loans['rate']?>%</div>
            <div class="bi-kpi-sub"><?=$kpi_loans['verified']?> of <?=$kpi_loans['total']?> loans verified</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$loanStatus['class']?>"><?=$loanStatus['label']?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-2">
            <div class="bi-kpi-icon">📍</div>
            <div class="bi-kpi-label">Field Coverage</div>
            <div class="bi-kpi-value" style="color:var(--bi-blue)"><?=$kpi_cov['rate']?>%</div>
            <div class="bi-kpi-sub"><?=$kpi_cov['visited']?> of <?=$kpi_cov['assigned']?> growers visited</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$covStatus['class']?>"><?=$covStatus['label']?></span></div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-3">
            <div class="bi-kpi-icon">🌱</div>
            <div class="bi-kpi-label">Active Growers</div>
            <div class="bi-kpi-value" style="color:var(--bi-purple)"><?=number_format($growerCount)?></div>
            <div class="bi-kpi-sub">Assigned this season</div>
        </div>
        <div class="bi-kpi bi-animate bi-animate-delay-4">
            <div class="bi-kpi-icon">💵</div>
            <div class="bi-kpi-label">Total Loan Value</div>
            <div class="bi-kpi-value" style="color:var(--bi-amber)">$<?=number_format($kpi_loans['value'],0)?></div>
            <div class="bi-kpi-sub"><?=$kpi_loans['total']?> loans issued</div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Multi-Season Trends</div>
    <div class="bi-grid-2" style="margin-bottom:28px;">
        <div class="bi-card bi-animate bi-animate-delay-1">
            <div class="bi-card-title"><span class="dot"></span>Loan Verification Rate — Season Trend</div>
            <div class="bi-chart-wrap"><canvas id="loanTrend" height="220"></canvas></div>
        </div>
        <div class="bi-card bi-animate bi-animate-delay-2">
            <div class="bi-card-title"><span class="dot" style="background:var(--bi-blue)"></span>Field Coverage — Season Trend</div>
            <div class="bi-chart-wrap"><canvas id="covTrend" height="220"></canvas></div>
        </div>
    </div>

    <div class="bi-section-title bi-animate">Domain Dashboards</div>
    <div class="bi-grid-4" style="margin-bottom:28px;">
        <a href="bi_grower_performance.php" class="bi-card bi-animate bi-animate-delay-1" style="text-decoration:none;display:block;cursor:pointer;">
            <div style="font-size:28px;margin-bottom:10px;">🌾</div>
            <div style="font-size:11px;font-weight:700;color:var(--bi-primary);text-transform:uppercase;letter-spacing:.7px;">Grower Performance</div>
            <div style="font-size:28px;font-weight:700;margin:6px 0;color:var(--bi-purple)"><?=number_format($growerCount)?></div>
            <div style="font-size:12px;color:var(--bi-text-muted);">Active growers · <?=$kpi_cov['rate']?>% visited</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$covStatus['class']?>"><?=$covStatus['label']?></span></div>
        </a>
        <a href="bi_field_officer.php" class="bi-card bi-animate bi-animate-delay-2" style="text-decoration:none;display:block;cursor:pointer;">
            <div style="font-size:28px;margin-bottom:10px;">📍</div>
            <div style="font-size:11px;font-weight:700;color:var(--bi-blue);text-transform:uppercase;letter-spacing:.7px;">Field Officers</div>
            <div style="font-size:28px;font-weight:700;margin:6px 0;color:var(--bi-blue)"><?=$kpi_cov['rate']?>%</div>
            <div style="font-size:12px;color:var(--bi-text-muted);">Coverage · Top: <?=htmlspecialchars($topOfficer['name'])?></div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$covStatus['class']?>"><?=$covStatus['label']?></span></div>
        </a>
        <a href="bi_loans.php" class="bi-card bi-animate bi-animate-delay-3" style="text-decoration:none;display:block;cursor:pointer;">
            <div style="font-size:28px;margin-bottom:10px;">💰</div>
            <div style="font-size:11px;font-weight:700;color:var(--bi-primary);text-transform:uppercase;letter-spacing:.7px;">Loans &amp; Inputs</div>
            <div style="font-size:28px;font-weight:700;margin:6px 0;color:var(--bi-primary)"><?=$kpi_loans['rate']?>%</div>
            <div style="font-size:12px;color:var(--bi-text-muted);">Verified · <?=$overdueCount?> pending</div>
            <div style="margin-top:10px;"><span class="bi-badge <?=$loanStatus['class']?>"><?=$loanStatus['label']?></span></div>
        </a>
        <a href="bi_grower_performance.php" class="bi-card bi-animate bi-animate-delay-4" style="text-decoration:none;display:block;cursor:pointer;">
            <div style="font-size:28px;margin-bottom:10px;">📊</div>
            <div style="font-size:11px;font-weight:700;color:var(--bi-amber);text-transform:uppercase;letter-spacing:.7px;">Visit Activity</div>
            <div style="font-size:28px;font-weight:700;margin:6px 0;color:var(--bi-amber)"><?=number_format($kpi_cov['visited'])?></div>
            <div style="font-size:12px;color:var(--bi-text-muted);">Growers visited this season</div>
        </a>
    </div>

    <div class="bi-section-title bi-animate">Top Loans by Product</div>
    <div class="bi-card bi-animate" style="margin-bottom:28px;">
        <div class="bi-card-title"><span class="dot" style="background:var(--bi-amber)"></span>Loan Value by Product — Current Season</div>
        <div class="bi-chart-wrap"><canvas id="productChart" height="200"></canvas></div>
    </div>
</div>
<?php bi_chart_defaults(); ?>
<script>
new Chart(document.getElementById('loanTrend'),{type:'line',data:{labels:<?=$trendLabelsJson?>,datasets:[{label:'Verification %',data:<?=$trendRatesJson?>,borderColor:'#10B981',backgroundColor:'rgba(16,185,129,0.08)',borderWidth:2,pointRadius:4,tension:0.4,fill:true}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('covTrend'),{type:'bar',data:{labels:<?=$trendLabelsJson?>,datasets:[{label:'Coverage %',data:<?=$trendCovJson?>,backgroundColor:'rgba(59,130,246,0.6)',borderColor:'#3B82F6',borderWidth:1,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('productChart'),{type:'bar',data:{labels:<?=$prodLabelsJson?>,datasets:[{label:'Value ($)',data:<?=$prodValuesJson?>,backgroundColor:['#10B981CC','#3B82F6CC','#F59E0BCC','#EF4444CC','#8B5CF6CC'],borderRadius:6}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{callback:v=>'$'+v.toLocaleString()}},y:{grid:{display:false}}}}});
</script>
<style>.bi-alert{display:flex;align-items:center;gap:12px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:var(--bi-radius-sm);padding:12px 16px;margin-bottom:24px;font-size:13px;}.bi-alert .dot{width:8px;height:8px;border-radius:50%;background:var(--bi-red);flex-shrink:0;animation:pulse 1.5s infinite;}@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}</style>
</body></html>
