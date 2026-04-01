<?php
// v1773916272 — fmtAmount active
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// ── Optional table checks ─────────────────────────────────────────────────────
$hasWC       = $conn->query("SHOW TABLES LIKE 'working_capital'")->num_rows > 0;
$hasRollover = $conn->query("SHOW TABLES LIKE 'rollover'")->num_rows > 0;
$hasPayments = $conn->query("SHOW TABLES LIKE 'loan_payments'")->num_rows > 0;
$hasBlocked  = $conn->query("SHOW TABLES LIKE 'blocked_growers'")->num_rows > 0;

// ── Pre-build optional subquery fragments ────────────────────────────────────
// These are set after $hasWC/$hasRollover checks and used inside SQL strings
$wcGrowerSub  = '0';
$wcOfficerSub = '0';
$wcTotalSub   = '0';
$rvGrowerSub  = '0';
$rvOfficerSub = '0';
$rvTotalSub   = '0';

// ── Amount formatter — abbreviates large numbers for KPI display ──────────────
function fmtAmount($val, $decimals=2) {
    $val = (float)$val;
    if($val >= 1000000) return '$'.number_format($val/1000000, 2).'M';
    if($val >= 100000)  return '$'.number_format($val/1000, 1).'K';
    if($val >= 10000)   return '$'.number_format($val/1000, 1).'K';
    return '$'.number_format($val, $decimals);
}
function fmtNum($val) {
    $val = (int)$val;
    if($val >= 1000000) return number_format($val/1000000,2).'M';
    if($val >= 1000)    return number_format($val/1000,1).'K';
    return number_format($val);
}

// ── Active season ─────────────────────────────────────────────────────────────
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $r->free(); }

// ── Filters ───────────────────────────────────────────────────────────────────
$filterOfficer  = isset($_GET['officer_id'])  && $_GET['officer_id']!==''  ? (int)$_GET['officer_id']  : null;
$filterSplitId  = isset($_GET['splitid'])     && $_GET['splitid']!==''     ? (int)$_GET['splitid']     : null;
$filterProduct  = isset($_GET['productid'])   && $_GET['productid']!==''   ? (int)$_GET['productid']   : null;
$filterVerified = isset($_GET['verified'])    && $_GET['verified']!==''    ? (int)$_GET['verified']    : null;
$filterScheme   = isset($_GET['schemeid'])    && $_GET['schemeid']!==''     ? (int)$_GET['schemeid']     : null;
$filterGrower   = isset($_GET['grower_q'])    && $_GET['grower_q']!==''     ? trim($_GET['grower_q'])    : null;
if($filterGrower) $filterGrower = preg_replace('/[^a-zA-Z0-9 \-]/', '', $filterGrower);
$filterDateFrom = isset($_GET['date_from'])   && $_GET['date_from']!==''   ? $_GET['date_from']        : null;
$filterDateTo   = isset($_GET['date_to'])     && $_GET['date_to']!==''     ? $_GET['date_to']          : null;
$groupBy        = isset($_GET['group_by'])    ? $_GET['group_by']          : 'officer'; // officer|product|grower|date
$viewMode       = isset($_GET['view'])        ? $_GET['view']              : 'summary'; // summary|detail

// Sanitise dates
if($filterDateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom)) $filterDateFrom = null;
if($filterDateTo   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo))   $filterDateTo   = null;

// ── Build dynamic WHERE ───────────────────────────────────────────────────────
$where = ["l.seasonid=$seasonId"];
if($filterOfficer)  $where[] = "l.userid=$filterOfficer";
if($filterProduct)  $where[] = "l.productid=$filterProduct";
if($filterVerified !== null) $where[] = "l.verified=$filterVerified";
if($filterDateFrom) $where[] = "DATE(l.created_at)>='$filterDateFrom'";
if($filterDateTo)   $where[] = "DATE(l.created_at)<='$filterDateTo'";
if($filterScheme)   $where[] = "l.growerid IN (SELECT shg.growerid FROM scheme_hectares_growers shg JOIN scheme_hectares sh ON sh.id=shg.scheme_hectaresid WHERE sh.schemeid=$filterScheme)";
if($filterGrower) {
    $gq = $conn->real_escape_string($filterGrower);
    $where[] = "l.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gq%' OR grower_num LIKE '%$gq%' OR name LIKE '%$gq%' OR surname LIKE '%$gq%')";
}
$whereStr = implode(' AND ', $where);

// ── Price subquery ────────────────────────────────────────────────────────────
$splitWhere = "seasonid=$seasonId";
if($filterSplitId) $splitWhere .= " AND splitid=$filterSplitId";
$priceSubquery = "
    SELECT pr.productid, pr.splitid, pr.amount, pr.seasonid
    FROM prices pr
    INNER JOIN (
        SELECT productid, splitid, seasonid, MAX(id) AS max_id
        FROM prices WHERE $splitWhere
        GROUP BY productid, splitid, seasonid
    ) latest ON latest.max_id = pr.id
";

// ── Dropdown data ─────────────────────────────────────────────────────────────
$allOfficers = [];
$r = $conn->query("SELECT id, userid, name FROM field_officers ORDER BY name");
if($r){ while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free(); }

$allProducts = [];
$r = $conn->query("SELECT p.id, p.name, p.units, pt.name AS type FROM products p JOIN product_type pt ON pt.id=p.product_typeid ORDER BY p.name");
if($r){ while($row=$r->fetch_assoc()) $allProducts[]=$row; $r->free(); }

$allSplits = [];
$r = $conn->query("SELECT DISTINCT splitid FROM prices WHERE seasonid=$seasonId AND splitid IS NOT NULL ORDER BY splitid");
if($r){ while($row=$r->fetch_assoc()) $allSplits[]=$row['splitid']; $r->free(); }

$allSchemes = [];
$r = $conn->query("SELECT id, description FROM scheme ORDER BY description");
if($r){ while($row=$r->fetch_assoc()) $allSchemes[]=$row; $r->free(); }

// ── Blocked growers exclusion ────────────────────────────────────────────────
$wcGrowerSub  = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.growerid=g.id AND wc2.seasonid=$seasonId), 0)" : "0";
$wcOfficerSub = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.userid=fo.userid AND wc2.seasonid=$seasonId), 0)" : "0";
$wcTotalSub   = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.seasonid=$seasonId), 0)" : "0";
$rvGrowerSub  = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.growerid=g.id AND rv2.seasonid=$seasonId), 0)" : "0";
$rvOfficerSub = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.userid=fo.userid AND rv2.seasonid=$seasonId), 0)" : "0";
$rvTotalSub   = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.seasonid=$seasonId), 0)" : "0";

$blockedClause    = $hasBlocked ? "AND l.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";
$blockedPayClause = $hasBlocked ? "AND lp.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

// ── Summary KPIs ──────────────────────────────────────────────────────────────
$kpi = [];
$r = $conn->query("
    SELECT COUNT(*) AS total_loans,
           COUNT(DISTINCT l.growerid) AS unique_growers,
           COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0)
             + $wcTotalSub
             + $rvTotalSub
           AS total_value,
           COALESCE(SUM(CASE WHEN l.processed=1 THEN COALESCE(pr.amount,0)*l.quantity ELSE 0 END),0) AS disbursed_value,
           SUM(l.verified=1) AS verified,
           SUM(l.verified=0) AS unverified,
           SUM(l.processed=1) AS processed,
           SUM(l.surrogate=1) AS surrogate,
           SUM(l.sync=0) AS pending_sync
    FROM loans l
    LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
    WHERE $whereStr $blockedClause
");
if($r && $row=$r->fetch_assoc()){ $kpi=$row; $r->free(); }

// ── Summary grouped data ──────────────────────────────────────────────────────
$summaryRows = [];

if($groupBy === 'officer') {
    $r = $conn->query("
        SELECT fo.name AS group_label,
               COUNT(DISTINCT l.id) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers,
               SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0)
                 + $wcOfficerSub
             + $rvOfficerSub
               AS total_value,
               SUM(l.verified=0) AS unverified,
               SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync,
               MAX(DATE(l.created_at)) AS last_activity
        FROM loans l
        JOIN field_officers fo ON fo.userid=l.userid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        GROUP BY l.userid, fo.name ORDER BY total_value DESC
    ");
} elseif($groupBy === 'product') {
    $r = $conn->query("
        SELECT CONCAT(p.name,' (',p.units,')') AS group_label,
               COUNT(*) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers,
               SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified,
               SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync,
               MAX(DATE(l.created_at)) AS last_activity
        FROM loans l
        JOIN products p ON p.id=l.productid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        GROUP BY l.productid, p.name, p.units ORDER BY total_value DESC
    ");
} elseif($groupBy === 'grower') {
    $r = $conn->query("
        SELECT CONCAT(g.name,' ',g.surname,' #',g.grower_num) AS group_label,
               COUNT(DISTINCT l.id) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers,
               SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0)
                 + $wcGrowerSub
             + $rvGrowerSub
               AS total_value,
               SUM(l.verified=0) AS unverified,
               SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync,
               MAX(DATE(l.created_at)) AS last_activity
        FROM loans l
        JOIN growers g ON g.id=l.growerid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        GROUP BY l.growerid, g.name, g.surname, g.grower_num ORDER BY total_value DESC
    ");
} else { // date
    $r = $conn->query("
        SELECT DATE(l.created_at) AS group_label,
               COUNT(*) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers,
               SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified,
               SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync,
               MAX(DATE(l.created_at)) AS last_activity
        FROM loans l
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        GROUP BY DATE(l.created_at) ORDER BY DATE(l.created_at) DESC
    ");
}
if($r){ while($row=$r->fetch_assoc()) $summaryRows[]=$row; $r->free(); }

// ── Detail rows ───────────────────────────────────────────────────────────────
$detailRows = [];
if($viewMode === 'detail') {
    $r = $conn->query("
        SELECT l.id, l.receipt_number,
               g.grower_num, g.name AS gname, g.surname AS gsurname,
               fo.name AS officer,
               p.name AS product, p.units,
               l.quantity,
               COALESCE(pr.amount,0) AS unit_price,
               COALESCE(pr.amount,0)*l.quantity AS line_value,
               l.hectares,
               l.verified, l.processed, l.surrogate, l.sync,
               DATE(l.created_at) AS loan_date,
               TIME(l.created_at) AS loan_time,
               l.verified_at, l.processed_at,
               l.latitude, l.longitude
        FROM loans l
        JOIN growers g ON g.id=l.growerid
        JOIN field_officers fo ON fo.userid=l.userid
        JOIN products p ON p.id=l.productid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        ORDER BY l.datetime DESC
        LIMIT 500
    ");
    if($r){ while($row=$r->fetch_assoc()) $detailRows[]=$row; $r->free(); }
}

// ── Officer drill-down loans (when officer filter is active) ──────────────────
$officerLoans = [];
$officerProductSummary = [];
if($filterOfficer) {
    // All individual loans for this officer
    $r = $conn->query("
        SELECT l.id, l.receipt_number, DATE(l.created_at) AS loan_date, TIME(l.created_at) AS loan_time,
               g.grower_num, g.name AS gname, g.surname AS gsurname,
               p.name AS product, p.units, l.quantity,
               COALESCE(pr.amount,0) AS unit_price,
               COALESCE(pr.amount,0)*l.quantity AS line_value,
               l.hectares, l.verified, l.processed, l.surrogate, l.sync,
               l.latitude, l.longitude
        FROM loans l
        JOIN growers g ON g.id=l.growerid
        JOIN products p ON p.id=l.productid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        ORDER BY l.datetime DESC
    ");
    if($r){ while($row=$r->fetch_assoc()) $officerLoans[]=$row; $r->free(); }

    // Product breakdown for this officer
    $r = $conn->query("
        SELECT p.name AS product, p.units,
               COUNT(*) AS loan_count,
               SUM(l.quantity) AS total_qty,
               COALESCE(pr.amount,0) AS unit_price,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               COUNT(DISTINCT l.growerid) AS unique_growers
        FROM loans l
        JOIN products p ON p.id=l.productid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr $blockedClause
        GROUP BY l.productid, p.name, p.units, pr.amount
        ORDER BY total_value DESC
    ");
    if($r){ while($row=$r->fetch_assoc()) $officerProductSummary[]=$row; $r->free(); }
}


// ── Build scheme subquery filter clauses ─────────────────────────────────────
// These apply the active filters to the scheme loan/payment subqueries
$schLoanExtra = "AND l2.seasonid=$seasonId";
if($filterOfficer)  $schLoanExtra .= " AND l2.userid=$filterOfficer";
if($filterProduct)  $schLoanExtra .= " AND l2.productid=$filterProduct";
if($filterVerified !== null) $schLoanExtra .= " AND l2.verified=$filterVerified";
if($filterDateFrom) $schLoanExtra .= " AND DATE(l2.created_at)>='$filterDateFrom'";
if($filterDateTo)   $schLoanExtra .= " AND DATE(l2.created_at)<='$filterDateTo'";
if($filterGrower) { $gqsc = $conn->real_escape_string($filterGrower); $schLoanExtra .= " AND l2.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqsc%' OR grower_num LIKE '%$gqsc%')"; }
$schBlockedExtra = $hasBlocked ? "AND l2.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$schPayExtra = "AND lp2.seasonid=$seasonId";
if($filterOfficer) $schPayExtra .= " AND lp2.userid=$filterOfficer";
if($filterDateFrom) $schPayExtra .= " AND DATE(lp2.datetime)>='$filterDateFrom'";
if($filterDateTo)   $schPayExtra .= " AND DATE(lp2.datetime)<='$filterDateTo'";
$schPayBlocked = $hasBlocked ? "AND lp2.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

// Also filter the price subquery for splitid
$schPriceSubquery = $priceSubquery; // already filtered by splitid via $priceSubquery

// ── Scheme summary for report ─────────────────────────────────────────────────
$schemeReport = [];
$r = $conn->query("
    SELECT s.id AS scheme_id, s.description AS scheme_name,
           COALESCE((SELECT SUM(CAST(sh2.quantity AS DECIMAL(10,2)) * (SELECT COUNT(*) FROM scheme_hectares_growers shg3 WHERE shg3.scheme_hectaresid=sh2.id)) FROM scheme_hectares sh2 WHERE sh2.schemeid=s.id),0) AS total_hectares,
           (SELECT COUNT(DISTINCT shg2.growerid) FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh2 ON sh2.id=shg2.scheme_hectaresid WHERE sh2.schemeid=s.id) AS enrolled_growers,
           (SELECT COUNT(*) FROM loans l2
            LEFT JOIN ($schPriceSubquery) pr2 ON pr2.productid=l2.productid AND pr2.splitid=l2.splitid AND pr2.seasonid=l2.seasonid
            WHERE l2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
              $schLoanExtra $schBlockedExtra) AS loan_count,
           (SELECT COALESCE(SUM(COALESCE(pr2.amount,0)*l2.quantity),0) FROM loans l2
            LEFT JOIN ($schPriceSubquery) pr2 ON pr2.productid=l2.productid AND pr2.splitid=l2.splitid AND pr2.seasonid=l2.seasonid
            WHERE l2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
              $schLoanExtra $schBlockedExtra) AS loan_value,
           (SELECT COALESCE(SUM(lp2.amount),0) FROM loan_payments lp2
            WHERE lp2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
              $schPayExtra $schPayBlocked) AS recovered
    FROM scheme s
    GROUP BY s.id, s.description ORDER BY s.description
");
if($r){ while($row=$r->fetch_assoc()) $schemeReport[]=$row; $r->free(); }

// ── Hectares queries ─────────────────────────────────────────────────────────
$totalHectares = 0;
$haBySchemOfficer = [];
$hasSchemeHa = $conn->query("SHOW TABLES LIKE 'scheme_hectares'")->num_rows > 0;
if($hasSchemeHa) {
    // Grand total
    $r = $conn->query("
        SELECT COALESCE(SUM(
               CAST(sh.quantity AS DECIMAL(10,2)) *
               (SELECT COUNT(*) FROM scheme_hectares_growers shg2 WHERE shg2.scheme_hectaresid=sh.id)
               ),0) AS total_ha
        FROM scheme_hectares sh WHERE sh.seasonid=$seasonId
    ");
    if($r && $row=$r->fetch_assoc()){ $totalHectares=(float)$row['total_ha']; $r->free(); }

    // Per officer — respects officer filter
    $haOfficerWhere = "sh.seasonid=$seasonId";
    if($filterOfficer) $haOfficerWhere .= " AND gfo.field_officerid=$filterOfficer";
    $r = $conn->query("
        SELECT fo.name AS officer,
               COALESCE(SUM(CAST(sh.quantity AS DECIMAL(10,2))),0) AS total_ha,
               COUNT(DISTINCT shg.growerid) AS growers
        FROM scheme_hectares sh
        JOIN scheme_hectares_growers shg ON shg.scheme_hectaresid=sh.id
        JOIN grower_field_officer gfo ON gfo.growerid=shg.growerid AND gfo.seasonid=$seasonId
        JOIN field_officers fo ON fo.userid=gfo.field_officerid
        WHERE $haOfficerWhere
        GROUP BY fo.userid, fo.name ORDER BY total_ha DESC
    ");
    if($r){ while($row=$r->fetch_assoc()) $haBySchemOfficer[]=$row; $r->free(); }
}
$maxHaOfficer = max(1, max(array_column($haBySchemOfficer,'total_ha') ?: [1]));

// ── Working Capital queries ──────────────────────────────────────────────────
if($hasWC):
$wcWhere2 = "wc.seasonid=$seasonId";
if($filterOfficer)  $wcWhere2 .= " AND wc.userid=$filterOfficer";
if($filterDateFrom) $wcWhere2 .= " AND DATE(wc.datetime)>='$filterDateFrom'";
if($filterDateTo)   $wcWhere2 .= " AND DATE(wc.datetime)<='$filterDateTo'";
if($filterGrower) { $gqwc = $conn->real_escape_string($filterGrower); $wcWhere2 .= " AND wc.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqwc%' OR grower_num LIKE '%$gqwc%')"; }
$wcBlockedClause2 = $hasBlocked ? "AND wc.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$wcKpiReport = ['total_wc'=>0,'wc_count'=>0,'wc_growers'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(wc.amount),0) AS total_wc,
           COUNT(*) AS wc_count,
           COUNT(DISTINCT wc.growerid) AS wc_growers
    FROM working_capital wc WHERE $wcWhere2 $wcBlockedClause2
");
if($r && $row=$r->fetch_assoc()){ $wcKpiReport=$row; $r->free(); }

// WC by group (officer or grower) for summary columns
$wcGroupData = [];
if($groupBy === 'officer') {
    $r = $conn->query("
        SELECT fo.name AS group_label, COALESCE(SUM(wc.amount),0) AS total_wc
        FROM working_capital wc
        JOIN field_officers fo ON fo.userid=wc.userid
        WHERE $wcWhere2 $wcBlockedClause2
        GROUP BY wc.userid, fo.name
    ");
} elseif($groupBy === 'grower') {
    $r = $conn->query("
        SELECT CONCAT(g.name,' ',g.surname,' #',g.grower_num) AS group_label,
               COALESCE(SUM(wc.amount),0) AS total_wc
        FROM working_capital wc
        JOIN growers g ON g.id=wc.growerid
        WHERE $wcWhere2 $wcBlockedClause2
        GROUP BY wc.growerid, g.name, g.surname, g.grower_num
    ");
} else { $r = null; }
if($r){ while($row=$r->fetch_assoc()) $wcGroupData[$row['group_label']]=(float)$row['total_wc']; $r->free(); }

endif; // hasWC

// ── Rollover queries ─────────────────────────────────────────────────────────
if($hasRollover):
$rvWhere2 = "rv.seasonid=$seasonId";
if($filterOfficer)  $rvWhere2 .= " AND rv.userid=$filterOfficer";
if($filterDateFrom) $rvWhere2 .= " AND DATE(rv.datetime)>='$filterDateFrom'";
if($filterDateTo)   $rvWhere2 .= " AND DATE(rv.datetime)<='$filterDateTo'";
if($filterGrower) { $gqrv2 = $conn->real_escape_string($filterGrower); $rvWhere2 .= " AND rv.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqrv2%' OR grower_num LIKE '%$gqrv2%')"; }
$rvBlockedClause2 = $hasBlocked ? "AND rv.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$rvKpiReport = ['total_rv'=>0,'rv_count'=>0,'rv_growers'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(rv.amount),0) AS total_rv,
           COUNT(*) AS rv_count,
           COUNT(DISTINCT rv.growerid) AS rv_growers
    FROM rollover rv WHERE $rvWhere2 $rvBlockedClause2
");
if($r && $row=$r->fetch_assoc()){ $rvKpiReport=$row; $r->free(); }

// Rollover by group
$rvGroupData = [];
if($groupBy === 'officer') {
    $r = $conn->query("
        SELECT fo.name AS group_label, COALESCE(SUM(rv.amount),0) AS total_rv
        FROM rollover rv JOIN field_officers fo ON fo.userid=rv.userid
        WHERE $rvWhere2 $rvBlockedClause2
        GROUP BY rv.userid, fo.name
    ");
} elseif($groupBy === 'grower') {
    $r = $conn->query("
        SELECT CONCAT(g.name,' ',g.surname,' #',g.grower_num) AS group_label,
               COALESCE(SUM(rv.amount),0) AS total_rv
        FROM rollover rv JOIN growers g ON g.id=rv.growerid
        WHERE $rvWhere2 $rvBlockedClause2
        GROUP BY rv.growerid, g.name, g.surname, g.grower_num
    ");
} else { $r = null; }
if($r){ while($row=$r->fetch_assoc()) $rvGroupData[$row['group_label']]=(float)$row['total_rv']; $r->free(); }

endif; // hasRollover

// ── Payment totals for recovery columns ──────────────────────────────────────
$payWhere = "lp.seasonid=$seasonId";
if($filterOfficer) $payWhere .= " AND lp.userid=$filterOfficer";
if($filterGrower) { $gq2 = $conn->real_escape_string($filterGrower); $payWhere .= " AND lp.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gq2%' OR grower_num LIKE '%$gq2%' OR name LIKE '%$gq2%' OR surname LIKE '%$gq2%')"; }

$recoveryKpi = ['total_paid'=>0,'outstanding'=>0,'recovery_pct'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(lp.amount),0) AS total_paid
    FROM loan_payments lp WHERE $payWhere $blockedPayClause
");
if($r && $row=$r->fetch_assoc()){
    $totalPaid   = (float)$row['total_paid'];
    $totalLoaned = (float)($kpi['total_value'] ?? 0); // already includes WC
    $recoveryKpi = [
        'total_paid'   => $totalPaid,
        'outstanding'  => max(0, $totalLoaned - $totalPaid),
        'recovery_pct' => $totalLoaned > 0 ? round(($totalPaid/$totalLoaned)*100,1) : 0
    ];
    $r->free();
}

// Per-group recovery — never filter by officer so ALL officers show their paid amounts
$groupPayWhere = "lp.seasonid=$seasonId";
// apply date filters if set but NOT officer filter (we need all officers for the summary)
if($filterDateFrom) $groupPayWhere .= " AND DATE(lp.datetime)>='$filterDateFrom'";
if($filterDateTo)   $groupPayWhere .= " AND DATE(lp.datetime)<='$filterDateTo'";

$groupRecovery = [];
if($groupBy === 'officer') {
    $r = $conn->query("
        SELECT fo.name AS group_label, COALESCE(SUM(lp.amount),0) AS paid
        FROM loan_payments lp
        JOIN field_officers fo ON fo.userid=lp.userid
        WHERE $groupPayWhere $blockedPayClause GROUP BY lp.userid, fo.name
    ");
} elseif($groupBy === 'grower') {
    $r = $conn->query("
        SELECT CONCAT(g.name,' ',g.surname,' #',g.grower_num) AS group_label,
               COALESCE(SUM(lp.amount),0) AS paid
        FROM loan_payments lp
        JOIN growers g ON g.id=lp.growerid
        WHERE $groupPayWhere $blockedPayClause GROUP BY lp.growerid, g.name, g.surname, g.grower_num
    ");
} else { $r = null; }
if($r){ while($row=$r->fetch_assoc()) $groupRecovery[$row['group_label']]=(float)$row['paid']; $r->free(); }

$conn->close();

$maxVal = max(1, max(array_column($summaryRows,'total_value') ?: [1]));

// ── Build export URL ──────────────────────────────────────────────────────────
$exportParams = $_GET;
unset($exportParams['export']);
$exportUrl = 'loans_report_export.php?' . http_build_query($exportParams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Loans Report</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080d08;--surface:#0f170f;--surface2:#141e14;--surface3:#192419;
  --border:#1a2a1a;--border2:#243224;
  --green:#3ddc68;--green-dim:#1a5e30;--green-glow:rgba(61,220,104,.1);
  --amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b060ff;
  --text:#c8e6c9;--muted:#4a6b4a;
  --radius:8px;--radius2:4px;
}
html,body{min-height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);font-size:12px}

/* Header */
header{display:flex;align-items:center;gap:10px;padding:0 20px;height:54px;
  background:var(--surface);border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;flex-wrap:wrap}
.logo{font-family:'Syne',sans-serif;font-size:19px;font-weight:900;color:var(--green);letter-spacing:-1px}
.logo span{color:var(--muted)}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);
  padding:3px 9px;border-radius:var(--radius2);transition:.15s}
.back:hover{color:var(--green);border-color:var(--green)}

/* Filter bar */
.filter-bar{background:var(--surface2);border-bottom:1px solid var(--border);
  padding:10px 20px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.filter-bar select,.filter-bar input{
  background:var(--bg);border:1px solid var(--border);color:var(--text);
  font-family:'Space Mono',monospace;font-size:10px;padding:5px 8px;
  border-radius:var(--radius2);outline:none;transition:.15s}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--green)}
.filter-bar label{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.filter-group{display:flex;flex-direction:column;gap:3px}
.btn{font-family:'Space Mono',monospace;font-size:10px;padding:5px 12px;
  border-radius:var(--radius2);cursor:pointer;border:1px solid;transition:.15s}
.btn-primary{background:var(--green-dim);border-color:var(--green);color:var(--green)}
.btn-primary:hover{background:var(--green);color:#000}
.btn-export{background:transparent;border-color:#2a4a2a;color:var(--muted)}
.btn-export:hover{border-color:var(--green);color:var(--green)}
.btn-clear{background:transparent;border-color:#3a1010;color:var(--red);font-size:10px}
.btn-clear:hover{background:#200808}
.filter-sep{width:1px;height:28px;background:var(--border);margin:0 4px}

/* View toggle */
.view-toggle{display:flex;border:1px solid var(--border);border-radius:var(--radius2);overflow:hidden}
.vt-btn{padding:5px 12px;font-family:'Space Mono',monospace;font-size:10px;
  cursor:pointer;border:none;background:transparent;color:var(--muted);transition:.15s}
.vt-btn.active{background:var(--green-dim);color:var(--green)}

/* Group toggle */
.group-toggle{display:flex;border:1px solid var(--border);border-radius:var(--radius2);overflow:hidden}
.gt-btn{padding:5px 10px;font-family:'Space Mono',monospace;font-size:9px;
  cursor:pointer;border:none;border-right:1px solid var(--border);
  background:transparent;color:var(--muted);transition:.15s;text-transform:uppercase}
.gt-btn:last-child{border-right:none}
.gt-btn.active{background:var(--surface3);color:var(--green)}

/* Page */
.page{padding:20px;max-width:1600px;margin:0 auto}

/* KPI strip */
.kpi-strip{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.kpi-chip{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:12px 14px;flex:1;min-width:140px;max-width:100%;overflow:hidden}
.kpi-chip-val{font-family:'Syne',sans-serif;font-size:clamp(13px,1.4vw,20px);font-weight:900;line-height:1.1;word-break:break-word}
.kpi-chip-lbl{font-size:8px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-top:4px;white-space:nowrap}

/* Section */
.section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  overflow:hidden;margin-bottom:20px}
.section-head{padding:10px 16px;border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:center;background:var(--surface2)}
.section-head h3{font-family:'Syne',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.badge{font-size:9px;color:var(--muted);border:1px solid var(--border);padding:2px 7px;border-radius:var(--radius2)}

/* Summary table */
.sum-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:11px}
.sum-table th{
  text-align:right;padding:7px 10px;font-size:8px;text-transform:uppercase;
  letter-spacing:.4px;color:var(--muted);border-bottom:1px solid var(--border);
  background:var(--surface2);white-space:nowrap;cursor:pointer;user-select:none;
  overflow:hidden}
.sum-table th:first-child{text-align:left;width:180px}
.sum-table th.col-sm{width:60px}
.sum-table th.col-md{width:100px}
.sum-table th.col-lg{width:130px}
.sum-table th.col-act{width:80px}
.sum-table th:hover{color:var(--green)}
.sum-table th.sorted{color:var(--green)}
.sum-table th .sort-arrow{margin-left:3px;opacity:.5}
.sum-table td{
  text-align:right;padding:7px 10px;border-bottom:1px solid #0d180d;
  vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sum-table td:first-child{text-align:left;font-weight:700}
.sum-table tfoot td{
  padding:7px 10px;white-space:nowrap;background:var(--surface2);
  border-top:2px solid var(--border2);font-weight:700;text-align:right}
.sum-table tfoot td:first-child{text-align:left;color:var(--green)}
.sum-table tr:last-child td{border-bottom:none}
.sum-table tr:hover td{background:rgba(61,220,104,.02)}
/* bar now sits below the value cell as a separate element */
.bar-inline{height:3px;background:var(--border);border-radius:2px;margin-top:4px}
.bar-inline-fill{height:100%;border-radius:2px;background:var(--green);transition:width .5s}
.pill{display:inline-block;padding:1px 5px;border-radius:3px;font-size:8px}
.pill-warn{background:#2a1f00;color:var(--amber);border:1px solid #3a2a00}
.pill-ok{background:#0d200d;color:var(--green);border:1px solid var(--green-dim)}
.pill-info{background:#001428;color:var(--blue);border:1px solid #002050}

/* Detail table */
.det-table{width:100%;border-collapse:collapse;font-size:10px}
.det-table th{text-align:left;padding:7px 10px;font-size:8px;text-transform:uppercase;
  letter-spacing:.4px;color:var(--muted);border-bottom:1px solid var(--border);
  background:var(--surface2);white-space:nowrap}
.det-table td{padding:7px 10px;border-bottom:1px solid #0d180d}
.det-table tr:last-child td{border-bottom:none}
.det-table tr:hover td{background:rgba(61,220,104,.02)}
.status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:3px}

/* Active filter chips */
.active-filters{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.af-chip{background:var(--surface2);border:1px solid var(--green-dim);
  color:var(--green);font-size:9px;padding:3px 8px;border-radius:10px;
  display:flex;align-items:center;gap:5px}
.af-chip a{color:var(--muted);text-decoration:none;font-size:11px}
.af-chip a:hover{color:var(--red)}

/* Empty */
.empty{padding:32px;text-align:center;color:var(--muted)}

::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-thumb{background:var(--border2)}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Loans Report</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="loans_dashboard.php" class="back">📊 Dashboard</a>
  <div style="margin-left:auto;font-size:10px;color:var(--muted)">Season <?=$seasonId?> · Active</div>
</header>

<!-- Filter Bar -->
<form method="GET" id="filter-form">
<div class="filter-bar">

  <div class="filter-group">
    <label>Field Officer</label>
    <select name="officer_id">
      <option value="">All Officers</option>
      <?php foreach($allOfficers as $o): ?>
      <option value="<?=$o['userid']?>" <?=$filterOfficer==$o['userid']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
      <?php endforeach?>
    </select>
  </div>

  <div class="filter-group">
    <label>Product</label>
    <select name="productid">
      <option value="">All Products</option>
      <?php foreach($allProducts as $p): ?>
      <option value="<?=$p['id']?>" <?=$filterProduct==$p['id']?'selected':''?>><?=htmlspecialchars($p['name'])?> (<?=$p['units']?>)</option>
      <?php endforeach?>
    </select>
  </div>

  <div class="filter-group">
    <label>Split ID</label>
    <select name="splitid">
      <option value="">All Splits</option>
      <?php foreach($allSplits as $sid): ?>
      <option value="<?=$sid?>" <?=$filterSplitId==$sid?'selected':''?>>Split #<?=$sid?></option>
      <?php endforeach?>
    </select>
  </div>

  <div class="filter-group">
    <label>Verified</label>
    <select name="verified">
      <option value="">All</option>
      <option value="1" <?=$filterVerified===1?'selected':''?>>Verified only</option>
      <option value="0" <?=$filterVerified===0?'selected':''?>>Unverified only</option>
    </select>
  </div>

  <div class="filter-group">
    <label>Scheme</label>
    <select name="schemeid">
      <option value="">All Schemes</option>
      <?php foreach($allSchemes as $sc): ?>
      <option value="<?=$sc['id']?>" <?=$filterScheme==$sc['id']?'selected':''?>><?=htmlspecialchars($sc['description'])?></option>
      <?php endforeach?>
    </select>
  </div>

  <div class="filter-group">
    <label>Grower Search</label>
    <div style="position:relative;display:flex;align-items:center">
      <input type="text" name="grower_q"
        value="<?=htmlspecialchars($filterGrower??'')?>"
        placeholder="Name or grower #"
        style="background:var(--bg);border:1px solid var(--border);color:var(--text);
               font-family:'Space Mono',monospace;font-size:10px;padding:5px 26px 5px 8px;
               border-radius:var(--radius2);outline:none;width:160px;transition:.15s"
        onfocus="this.style.borderColor='var(--green)'"
        onblur="this.style.borderColor='var(--border)'">
      <?php if($filterGrower): ?>
      <a href="?<?=http_build_query(array_diff_key($_GET,['grower_q'=>1]))?>"
         style="position:absolute;right:6px;color:var(--muted);text-decoration:none;font-size:12px;line-height:1"
         title="Clear grower search">✕</a>
      <?php endif?>
    </div>
  </div>

  <div class="filter-group">
    <label>Date From</label>
    <input type="date" name="date_from" value="<?=htmlspecialchars($filterDateFrom??'')?>">
  </div>

  <div class="filter-group">
    <label>Date To</label>
    <input type="date" name="date_to" value="<?=htmlspecialchars($filterDateTo??'')?>">
  </div>

  <div class="filter-sep"></div>

  <div class="filter-group">
    <label>Group By</label>
    <div class="group-toggle">
      <button type="button" onclick="setGroup('officer')"  class="gt-btn <?=$groupBy==='officer'?'active':''?>">Officer</button>
      <button type="button" onclick="setGroup('product')"  class="gt-btn <?=$groupBy==='product'?'active':''?>">Product</button>
      <button type="button" onclick="setGroup('grower')"   class="gt-btn <?=$groupBy==='grower'?'active':''?>">Grower</button>
      <button type="button" onclick="setGroup('date')"     class="gt-btn <?=$groupBy==='date'?'active':''?>">Date</button>
    </div>
  </div>

  <div class="filter-group">
    <label>View</label>
    <div class="view-toggle">
      <button type="button" onclick="setView('summary')" class="vt-btn <?=$viewMode==='summary'?'active':''?>">Summary</button>
      <button type="button" onclick="setView('detail')"  class="vt-btn <?=$viewMode==='detail'?'active':''?>">Detail</button>
    </div>
  </div>

  <div class="filter-sep"></div>

  <div style="display:flex;flex-direction:column;gap:4px">
    <button type="submit" class="btn btn-primary">Apply</button>
    <?php if($filterOfficer||$filterSplitId||$filterProduct||$filterVerified!==null||$filterDateFrom||$filterDateTo): ?>
    <a href="?group_by=<?=$groupBy?>&view=<?=$viewMode?>" class="btn btn-clear" style="text-align:center;text-decoration:none">✕ Clear</a>
    <?php endif?>
  </div>

  <a href="<?=htmlspecialchars($exportUrl)?>" class="btn btn-export" style="align-self:flex-end;text-decoration:none">⬇ Export Excel</a>

  <!-- hidden fallbacks - overridden by button clicks via JS -->
  <input type="hidden" name="group_by" id="hidden_group_by" value="<?=htmlspecialchars($groupBy)?>">
  <input type="hidden" name="view" id="hidden_view" value="<?=htmlspecialchars($viewMode)?>">
</div>
</form>

<div class="page">

  <!-- Active filter chips -->
  <?php
  $chipParts = [];
  if($filterOfficer){ $on = ''; foreach($allOfficers as $o) if($o['userid']==$filterOfficer){ $on=$o['name']; break; } $chipParts[] = ['Officer: '.$on, array_diff_key($_GET,['officer_id'=>1])]; }
  if($filterProduct){ $pn = ''; foreach($allProducts as $p) if($p['id']==$filterProduct){ $pn=$p['name']; break; } $chipParts[] = ['Product: '.$pn, array_diff_key($_GET,['productid'=>1])]; }
  if($filterSplitId) $chipParts[] = ['Split #'.$filterSplitId, array_diff_key($_GET,['splitid'=>1])];
  if($filterVerified!==null) $chipParts[] = [($filterVerified?'Verified':'Unverified').' only', array_diff_key($_GET,['verified'=>1])];
  if($filterDateFrom) $chipParts[] = ['From: '.$filterDateFrom, array_diff_key($_GET,['date_from'=>1])];
  if($filterDateTo)   $chipParts[] = ['To: '.$filterDateTo, array_diff_key($_GET,['date_to'=>1])];
  if($filterScheme){ $sn=''; foreach($allSchemes as $sc) if($sc['id']==$filterScheme){ $sn=$sc['description']; break; } $chipParts[] = ['Scheme: '.$sn, array_diff_key($_GET,['schemeid'=>1])]; }
  if($filterGrower) $chipParts[] = ['Grower: "'.htmlspecialchars($filterGrower).'"', array_diff_key($_GET,['grower_q'=>1])];
  if(!empty($chipParts)):
  ?>
  <div class="active-filters">
    <?php foreach($chipParts as [$label,$params]): ?>
    <div class="af-chip"><?=htmlspecialchars($label)?> <a href="?<?=http_build_query($params)?>">✕</a></div>
    <?php endforeach?>
  </div>
  <?php endif?>

  <!-- KPI Strip -->
  <div class="kpi-strip">

    <div class="kpi-chip">
      <div class="kpi-chip-val"><?=number_format($kpi['unique_growers']??0)?></div>
      <div class="kpi-chip-lbl">Unique Growers</div>
    </div>
    <div class="kpi-chip" style="border-color:#002050">
      <div class="kpi-chip-val" style="color:var(--blue)"><?=number_format($totalHectares,1)?></div>
      <div class="kpi-chip-lbl">Total Hectares</div>
    </div>
    <div class="kpi-chip">
      <div class="kpi-chip-val" style="color:var(--green)"><?=fmtAmount($kpi['total_value']??0)?></div>
      <div class="kpi-chip-lbl">Total Value</div>
    </div>

    <div class="kpi-chip">
      <div class="kpi-chip-val" style="color:var(--blue)"><?=fmtAmount($kpi['disbursed_value']??0)?></div>
      <div class="kpi-chip-lbl">Total Disbursed</div>
    </div>



    <div class="kpi-chip" style="border-color:var(--green-dim)">
      <div class="kpi-chip-val" style="color:var(--green)"><?=fmtAmount($recoveryKpi['total_paid'])?></div>
      <div class="kpi-chip-lbl">Total Recovered</div>
    </div>
    <div class="kpi-chip" style="border-color:#3a1010">
      <div class="kpi-chip-val" style="color:var(--red)"><?=fmtAmount($recoveryKpi['outstanding'])?></div>
      <div class="kpi-chip-lbl">Outstanding</div>
    </div>
    <div class="kpi-chip">
      <div class="kpi-chip-val" style="color:<?=$recoveryKpi['recovery_pct']>=70?'var(--green)':($recoveryKpi['recovery_pct']>=40?'var(--amber)':'var(--red)')?>"><?=$recoveryKpi['recovery_pct']?>%</div>
      <div class="kpi-chip-lbl">Recovery Rate</div>
    </div>
    <div class="kpi-chip" style="border-color:#002050">
      <div class="kpi-chip-val" style="color:var(--blue)"><?=fmtAmount($wcKpiReport['total_wc']??0)?></div>
      <div class="kpi-chip-lbl">Working Capital</div>
    </div>
    <div class="kpi-chip" style="border-color:#2a1f40">
      <div class="kpi-chip-val" style="color:var(--purple)"><?=fmtAmount($rvKpiReport['total_rv']??0)?></div>
      <div class="kpi-chip-lbl">Rollover</div>
    </div>
    <?php
      $totalExpRpt = ($kpi['total_value']??0); // already includes WC + rollover from KPI query
      $totalRecRpt = $recoveryKpi['total_paid']??0;
      $totalOutRpt = max(0, $totalExpRpt - $totalRecRpt);
      $totalRecPctRpt = $totalExpRpt>0 ? round(($totalRecRpt/$totalExpRpt)*100,1) : 0;
      $expRecCol = $totalRecPctRpt>=70?'var(--green)':($totalRecPctRpt>=40?'var(--amber)':'var(--red)');
    ?>
    <div class="kpi-chip">
      <div class="kpi-chip-val" style="color:var(--amber)"><?=fmtAmount($totalExpRpt)?></div>
      <div class="kpi-chip-lbl">Total Exposure</div>
    </div>
    <div class="kpi-chip">
      <div class="kpi-chip-val" style="color:<?=$expRecCol?>"><?=$totalRecPctRpt?>%</div>
      <div class="kpi-chip-lbl">Overall Recovery</div>
    </div>
  </div>


  <!-- ── Scheme Breakdown Table ── -->
  <?php if(!empty($schemeReport)): ?>
  <div class="section" style="margin-bottom:20px">
    <div class="section-head">
      <h3>🌿 Scheme Breakdown</h3>
      <span class="badge"><?=count($schemeReport)?> schemes</span>
    </div>
    <div style="overflow-x:auto">
    <table class="sum-table">
      <thead>
        <tr>
          <th>Scheme</th>
          <th>Hectares</th>
          <th>Growers</th>
          <th>Loans</th>
          <th>Loan Value</th>
          <th>Recovered</th>
          <th>Outstanding</th>
          <th>Recovery %</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php
        $schMaxVal = max(1, max(array_column($schemeReport,'loan_value') ?: [1]));
        foreach($schemeReport as $sc):
          $scOutst  = max(0, $sc['loan_value'] - $sc['recovered']);
          $scRpct   = $sc['loan_value']>0 ? round(($sc['recovered']/$sc['loan_value'])*100,1) : 0;
          $scRcol   = $scRpct>=70?'var(--green)':($scRpct>=40?'var(--amber)':'var(--red)');
          $scBarPct = round(($sc['loan_value']/$schMaxVal)*100);
          $isActive = $filterScheme == $sc['scheme_id'];
      ?>
      <tr style="<?=$isActive?'background:rgba(61,220,104,.04)':''?>">
        <td style="font-weight:700"><?=htmlspecialchars($sc['scheme_name'])?>
          <?php if($isActive): ?><span style="font-size:8px;color:var(--green);border:1px solid var(--green-dim);padding:1px 5px;border-radius:3px;margin-left:4px">Active</span><?php endif?>
        </td>
        <td><?=number_format($sc['total_hectares'],1)?></td>
        <td><?=number_format($sc['enrolled_growers'])?></td>
        <td><?=number_format($sc['loan_count'])?></td>
        <td style="text-align:right;color:var(--green);font-weight:700">
          <?=fmtAmount($sc['loan_value'])?>
          <div class="bar-inline"><div class="bar-inline-fill" style="width:<?=$scBarPct?>%"></div></div>
        </td>
        <td style="text-align:right;color:var(--blue)"><?=fmtAmount($sc['recovered'])?></td>
        <td style="text-align:right;color:var(--red)"><?=fmtAmount($scOutst)?></td>
        <td style="text-align:right;color:<?=$scRcol?>;font-weight:700"><?=$scRpct?>%</td>
        <td>
          <?php
            $schParams = array_merge($_GET, ['schemeid'=>$sc['scheme_id']]);
            unset($schParams['export']);
          ?>
          <a href="?<?=http_build_query($schParams)?>"
             style="font-size:9px;color:var(--blue);text-decoration:none;border:1px solid #002050;padding:2px 7px;border-radius:3px;white-space:nowrap">
            <?=$isActive?'✕ Clear':'Filter →'?>
          </a>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td style="font-weight:700;color:var(--green)">TOTAL</td>
          <td style="text-align:right;font-weight:700"><?=number_format(array_sum(array_column($schemeReport,'total_hectares')),1)?></td>
          <td style="text-align:right;font-weight:700"><?=number_format(array_sum(array_column($schemeReport,'enrolled_growers')))?></td>
          <td style="text-align:right;font-weight:700"><?=number_format(array_sum(array_column($schemeReport,'loan_count')))?></td>
          <td style="text-align:right;font-weight:700;color:var(--green)">$<?=number_format(array_sum(array_column($schemeReport,'loan_value')),2)?></td>
          <td style="text-align:right;font-weight:700;color:var(--blue)">$<?=number_format(array_sum(array_column($schemeReport,'recovered')),2)?></td>
          <td style="text-align:right;font-weight:700;color:var(--red)">$<?=number_format(array_sum(array_column($schemeReport,'loan_value'))-array_sum(array_column($schemeReport,'recovered')),2)?></td>
          <?php
            $schTotalLoaned    = array_sum(array_column($schemeReport,'loan_value'));
            $schTotalRecovered = array_sum(array_column($schemeReport,'recovered'));
            $schTotalRecPct    = $schTotalLoaned>0 ? round(($schTotalRecovered/$schTotalLoaned)*100,1) : 0;
            $schTotalRecCol    = $schTotalRecPct>=70?'var(--green)':($schTotalRecPct>=40?'var(--amber)':'var(--red)');
          ?>
          <td style="text-align:right;font-weight:700;color:<?=$schTotalRecCol?>"><?=$schTotalRecPct?>%</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
  <?php endif?>

  <!-- ── Hectares by Officer ── -->
  <?php if(!empty($haBySchemOfficer)): ?>
  <div class="section" style="margin-bottom:20px">
    <div class="section-head">
      <h3>📐 Hectares by Field Officer</h3>
      <span class="badge"><?=number_format($totalHectares,1)?> ha total</span>
    </div>
    <div style="overflow-x:auto">
    <table class="sum-table">
      <thead>
        <tr>
          <th>Officer</th>
          <th class="col-sm">Growers</th>
          <th class="col-lg">Hectares</th>
          <th style="width:200px">Share</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($haBySchemOfficer as $ho):
        $hpct = $totalHectares>0 ? round(($ho['total_ha']/$totalHectares)*100,1) : 0;
        $hbar = $maxHaOfficer>0  ? round(($ho['total_ha']/$maxHaOfficer)*100) : 0;
      ?>
      <tr>
        <td style="font-weight:700"><?=htmlspecialchars($ho['officer'])?></td>
        <td><?=number_format($ho['growers'])?></td>
        <td style="color:var(--blue);font-weight:700"><?=number_format($ho['total_ha'],1)?> ha</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="bar-inline" style="flex:1;margin:0">
              <div class="bar-inline-fill" style="width:<?=$hbar?>%;background:var(--blue)"></div>
            </div>
            <span style="font-size:9px;color:var(--muted);white-space:nowrap"><?=$hpct?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td>TOTAL</td>
          <td><?=number_format(array_sum(array_column($haBySchemOfficer,'growers')))?></td>
          <td style="color:var(--blue)"><?=number_format($totalHectares,1)?> ha</td>
          <td>100%</td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
  <?php endif?>

  <!-- Summary Table -->
  <div class="section">
    <div class="section-head">
      <h3>📊 Summary — Grouped by <?=ucfirst($groupBy)?></h3>
      <span class="badge"><?=count($summaryRows)?> <?=$groupBy?>s</span>
    </div>
    <?php if(empty($summaryRows)): ?>
    <div class="empty">No data for the selected filters</div>
    <?php else: ?>
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
    <table class="sum-table" id="summary-table">
      <thead>
        <tr>
          <th onclick="sortTable('summary-table',0)" class="sorted"><?=ucfirst($groupBy)?> <span class="sort-arrow">↑</span></th>
          <th onclick="sortTable('summary-table',1)" class="col-sm">Loans <span class="sort-arrow">↕</span></th>
          <th onclick="sortTable('summary-table',2)" class="col-sm">Growers <span class="sort-arrow">↕</span></th>
          <th onclick="sortTable('summary-table',3)" class="col-sm">Qty <span class="sort-arrow">↕</span></th>
          <th onclick="sortTable('summary-table',4)" class="col-lg">Value <span class="sort-arrow">↕</span></th>
          <th class="col-sm">Share</th>
          <th class="col-sm">Unverif.</th>
          <th class="col-sm">Surrogate</th>
          <th class="col-sm">Sync</th>
          <th class="col-md">Last Active</th>
          <?php if(in_array($groupBy,['officer','grower'])): ?>
          <th class="col-lg">Paid</th>
          <th class="col-lg">Outstanding</th>
          <th class="col-sm">Recov %</th>
          <th class="col-lg">Working Cap</th>
          <th class="col-lg">Rollover</th>
          <?php endif?>
          <?php if($groupBy==='officer'): ?><th class="col-act"></th><?php endif?>
        </tr>
      </thead>
      <tbody>
      <?php
      $grandTotal = array_sum(array_column($summaryRows,'total_value'));
      foreach($summaryRows as $row):
        $share = $grandTotal>0 ? round(($row['total_value']/$grandTotal)*100,1) : 0;
        $barPct = $maxVal>0 ? round(($row['total_value']/$maxVal)*100) : 0;
      ?>
      <tr>
        <td style="font-weight:700"><?=htmlspecialchars($row['group_label'])?></td>
        <td><?=number_format($row['loan_count'])?></td>
        <td><?=number_format($row['unique_growers'])?></td>
        <td><?=number_format($row['total_qty'])?></td>
        <td style="text-align:right;color:var(--green);font-weight:700"><?=fmtAmount($row['total_value'])?><div class="bar-inline"><div class="bar-inline-fill" style="width:<?=$barPct?>%"></div></div></td>
        <td style="text-align:center;color:var(--muted)"><?=$share?>%</td>
        <td><?=$row['unverified']>0 ? '<span class="pill pill-warn">'.$row['unverified'].'</span>' : '<span class="pill pill-ok">✓</span>'?></td>
        <td><?=$row['surrogate']>0 ? '<span class="pill pill-info">'.$row['surrogate'].'</span>' : '—'?></td>
        <td><?=$row['pending_sync']>0 ? '<span class="pill pill-warn">'.$row['pending_sync'].'</span>' : '—'?></td>
        <td style="text-align:right;color:var(--muted)"><?=$row['last_activity']?></td>
        <?php if(in_array($groupBy,['officer','grower'])):
          $paid = $groupRecovery[$row['group_label']] ?? 0;
          $outst = max(0, $row['total_value'] - $paid);
          $rpct = $row['total_value']>0 ? round(($paid/$row['total_value'])*100,1) : 0;
          $rcol = $rpct>=70?'var(--green)':($rpct>=40?'var(--amber)':'var(--red)');
        ?>
        <td style="text-align:right;color:var(--green)"><?=fmtAmount($paid)?></td>
        <td style="text-align:right;color:var(--red)"><?=fmtAmount($outst)?></td>
        <td style="text-align:right;color:<?=$rcol?>;font-weight:700"><?=$rpct?>%</td>
        <td style="color:var(--blue)"><?=fmtAmount($wcGroupData[$row['group_label']]??0)?></td>
        <td style="color:var(--purple)"><?=fmtAmount($rvGroupData[$row['group_label']]??0)?></td>
        <?php endif?>
        <?php if($groupBy==='officer'):
          $ouid = null;
          foreach($allOfficers as $ao){ if($ao['name']===$row['group_label']){ $ouid=$ao['userid']; break; } }
          $drillParams = array_merge($_GET, ['officer_id'=>$ouid,'group_by'=>'officer','view'=>'detail']);
        ?>
        <td>
          <a href="?<?=http_build_query($drillParams)?>" style="font-size:9px;color:var(--blue);text-decoration:none;border:1px solid #002050;padding:2px 7px;border-radius:3px;white-space:nowrap">View Loans →</a>
        </td>
        <?php endif?>
      </tr>
      <?php endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td>TOTAL</td>
          <td><?=number_format($kpi['total_loans'])?></td>
          <td><?=number_format($kpi['unique_growers'])?></td>
          <td>—</td>
          <td style="color:var(--green)"><?=fmtAmount($kpi['total_value'])?></td>
          <td>100%</td>
          <td style="color:var(--amber)"><?=number_format($kpi['unverified'])?></td>
          <td style="color:var(--purple)"><?=number_format($kpi['surrogate'])?></td>
          <td style="color:var(--amber)"><?=number_format($kpi['pending_sync'])?></td>
          <td style="color:var(--muted)">—</td>
          <?php if(in_array($groupBy,['officer','grower'])): ?>
          <td style="color:var(--green)">$<?=number_format(array_sum($groupRecovery),2)?></td>
          <td style="color:var(--red)">$<?=number_format(max(0,$kpi['total_value']-array_sum($groupRecovery)),2)?></td>
          <td style="color:<?=$recoveryKpi['recovery_pct']>=70?'var(--green)':($recoveryKpi['recovery_pct']>=40?'var(--amber)':'var(--red)')?>">
            <?=$recoveryKpi['recovery_pct']?>%
          </td>
          <td style="color:var(--blue)">$<?=number_format(array_sum($wcGroupData),2)?></td>
          <td style="color:var(--purple)">$<?=number_format(array_sum($rvGroupData),2)?></td>
          <?php endif?>
          <?php if($groupBy==='officer'): ?><td></td><?php endif?>
        </tr>
      </tfoot>
    </table>
    </div>
    <?php endif?>
  </div>


  <!-- ── Officer Drill-Down Panel ── -->
  <?php if($filterOfficer && !empty($officerLoans)):
    $oName = '';
    foreach($allOfficers as $ao){ if($ao['userid']==$filterOfficer){ $oName=$ao['name']; break; } }
  ?>
  <div class="section" style="border-color:var(--blue);margin-bottom:20px">
    <div class="section-head" style="background:#001428;border-color:#002050">
      <h3 style="color:var(--blue)">👮 <?=htmlspecialchars($oName)?> — All Loans</h3>
      <div style="display:flex;gap:8px;align-items:center">
        <span class="badge" style="color:var(--blue);border-color:#002050"><?=count($officerLoans)?> loans · $<?=number_format(array_sum(array_column($officerLoans,'line_value')),2)?></span>
        <a href="?<?=http_build_query(array_merge($_GET,['export'=>'xlsx']))?>" style="font-size:9px;color:var(--muted);border:1px solid var(--border);padding:2px 8px;border-radius:3px;text-decoration:none">⬇ Export</a>
      </div>
    </div>

    <!-- Product breakdown for this officer -->
    <?php if(!empty($officerProductSummary)): ?>
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;gap:12px;flex-wrap:wrap">
      <?php foreach($officerProductSummary as $ps): ?>
      <div style="background:var(--surface2);border:1px solid var(--border);border-radius:4px;padding:8px 12px;min-width:140px">
        <div style="font-size:10px;font-weight:700;margin-bottom:2px"><?=htmlspecialchars($ps['product'])?></div>
        <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--green)"><?=fmtAmount($ps['total_value'])?></div>
        <div style="font-size:9px;color:var(--muted)"><?=number_format($ps['total_qty'])?> <?=$ps['units']?> · <?=$ps['loan_count']?> loans · <?=$ps['unique_growers']?> growers</div>
        <div style="font-size:9px;color:var(--muted)">@ <?=fmtAmount($ps['unit_price'])?>/<?=$ps['units']?></div>
      </div>
      <?php endforeach?>
    </div>
    <?php endif?>

    <!-- Individual loan rows -->
    <div style="overflow-x:auto">
    <table class="det-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Time</th>
          <th>Grower</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Unit $</th>
          <th>Value</th>
          <th>Ha</th>
          <th>Verified</th>
          <th>Processed</th>
          <th>Surrogate</th>
          <th>Sync</th>
          <th>GPS</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($officerLoans as $i=>$ol): ?>
      <tr>
        <td style="color:var(--muted)"><?=$i+1?></td>
        <td style="color:var(--muted)"><?=$ol['loan_date']?></td>
        <td style="color:var(--muted)"><?=$ol['loan_time']?></td>
        <td>
          <span style="font-weight:700"><?=htmlspecialchars($ol['gname'].' '.$ol['gsurname'])?></span>
          <span style="color:var(--muted);font-size:9px"> #<?=$ol['grower_num']?></span>
        </td>
        <td><?=htmlspecialchars($ol['product'])?> <span style="color:var(--muted);font-size:9px">(<?=$ol['units']?>)</span></td>
        <td><?=number_format($ol['quantity'])?></td>
        <td style="text-align:right;color:var(--muted)"><?=fmtAmount($ol['unit_price'])?></td>
        <td style="text-align:right;color:var(--green);font-weight:700"><?=fmtAmount($ol['line_value'])?></td>
        <td style="color:var(--muted)"><?=$ol['hectares']?:'-'?></td>
        <td><?=$ol['verified'] ? '<span class="pill pill-ok">✓</span>' : '<span class="pill pill-warn">✗</span>'?></td>
        <td><?=$ol['processed'] ? '<span class="pill pill-ok">✓</span>' : '<span style="color:var(--muted)">—</span>'?></td>
        <td><?=$ol['surrogate'] ? '<span class="pill pill-info">S</span>' : '—'?></td>
        <td><?=$ol['sync'] ? '<span class="pill pill-ok">✓</span>' : '<span class="pill pill-warn">⏳</span>'?></td>
        <td>
          <?php if($ol['latitude'] && $ol['longitude']): ?>
          <a href="https://maps.google.com/?q=<?=$ol['latitude']?>,<?=$ol['longitude']?>" target="_blank" style="color:var(--blue);text-decoration:none">📍</a>
          <?php else: ?>—<?php endif?>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="7" style="font-weight:700;color:var(--green)">TOTAL</td>
          <td style="text-align:right;font-weight:700;color:var(--green)">$<?=number_format(array_sum(array_column($officerLoans,'line_value')),2)?></td>
          <td colspan="6"></td>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
  <?php endif?>

  <!-- Detail Table -->
  <?php if($viewMode === 'detail'): ?>
  <div class="section">
    <div class="section-head">
      <h3>📋 Loan Detail</h3>
      <span class="badge"><?=count($detailRows)?> records<?=count($detailRows)>=500?' (limit 500)':''?></span>
    </div>
    <?php if(empty($detailRows)): ?>
    <div class="empty">No records match the selected filters</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="det-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Receipt</th>
          <th>Date</th>
          <th>Time</th>
          <th>Grower</th>
          <th>Officer</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Unit $</th>
          <th>Value</th>
          <th>Ha</th>
          <th>Verified</th>
          <th>Processed</th>
          <th>Surrogate</th>
          <th>Sync</th>
          <th>GPS</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($detailRows as $i=>$row): ?>
      <tr>
        <td style="color:var(--muted)"><?=$i+1?></td>
        <td style="color:var(--muted);font-size:9px"><?=htmlspecialchars($row['receipt_number'])?></td>
        <td style="color:var(--muted)"><?=$row['loan_date']?></td>
        <td style="color:var(--muted)"><?=$row['loan_time']?></td>
        <td>
          <span style="font-weight:700"><?=htmlspecialchars($row['gname'].' '.$row['gsurname'])?></span>
          <span style="color:var(--muted);font-size:9px"> #<?=$row['grower_num']?></span>
        </td>
        <td><?=htmlspecialchars($row['officer'])?></td>
        <td><?=htmlspecialchars($row['product'])?> <span style="color:var(--muted);font-size:9px">(<?=$row['units']?>)</span></td>
        <td><?=number_format($row['quantity'])?></td>
        <td style="text-align:right;color:var(--muted)"><?=fmtAmount($row['unit_price'])?></td>
        <td style="text-align:right;color:var(--green);font-weight:700"><?=fmtAmount($row['line_value'])?></td>
        <td style="text-align:right;color:var(--muted)"><?=$row['hectares']?:'-'?></td>
        <td>
          <?php if($row['verified']): ?>
          <span class="pill pill-ok">✓</span>
          <?php else: ?>
          <span class="pill pill-warn">✗</span>
          <?php endif?>
        </td>
        <td>
          <?=$row['processed'] ? '<span class="pill pill-ok">✓</span>' : '<span style="color:var(--muted)">—</span>'?>
        </td>
        <td>
          <?=$row['surrogate'] ? '<span class="pill pill-info">S</span>' : '—'?>
        </td>
        <td>
          <?=$row['sync'] ? '<span class="pill pill-ok">✓</span>' : '<span class="pill pill-warn">⏳</span>'?>
        </td>
        <td>
          <?php if($row['latitude'] && $row['longitude']): ?>
          <a href="https://maps.google.com/?q=<?=$row['latitude']?>,<?=$row['longitude']?>" target="_blank" style="color:var(--blue);text-decoration:none;font-size:10px">📍</a>
          <?php else: ?>
          <span style="color:var(--muted)">—</span>
          <?php endif?>
        </td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    </div>
    <?php endif?>
  </div>
  <?php endif?>

</div>

<script>
// ── Client-side table sort ─────────────────────────────────────────────────────
const sortState = {};
function sortTable(tableId, colIndex) {
  const table = document.getElementById(tableId);
  const tbody = table.querySelector('tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr'));
  const key   = tableId + '_' + colIndex;
  const asc   = !sortState[key];
  sortState[key] = asc;

  rows.sort((a, b) => {
    const aText = a.cells[colIndex]?.textContent.trim().replace(/[$,%]/g,'') || '';
    const bText = b.cells[colIndex]?.textContent.trim().replace(/[$,%]/g,'') || '';
    const aNum  = parseFloat(aText.replace(/,/g,''));
    const bNum  = parseFloat(bText.replace(/,/g,''));
    if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
    return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
  });

  rows.forEach(r => tbody.appendChild(r));

  // Update sort arrows
  table.querySelectorAll('th').forEach((th, i) => {
    th.classList.toggle('sorted', i === colIndex);
    const arrow = th.querySelector('.sort-arrow');
    if(arrow) arrow.textContent = i === colIndex ? (asc ? '↑' : '↓') : '↕';
  });
}

// ── Group by / View toggle helpers ───────────────────────────────────────────
function setGroup(val) {
  document.getElementById('hidden_group_by').value = val;
  document.getElementById('filter-form').submit();
}
function setView(val) {
  document.getElementById('hidden_view').value = val;
  document.getElementById('filter-form').submit();
}

// ── Auto-submit date inputs ───────────────────────────────────────────────────
document.querySelectorAll('input[type=date]').forEach(el => {
  el.addEventListener('change', () => document.getElementById('filter-form').submit());
});
document.querySelectorAll('select').forEach(el => {
  el.addEventListener('change', () => document.getElementById('filter-form').submit());
});
</script>
</body>
</html>
