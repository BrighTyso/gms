<?php
// v1773916272 — fmtAmount active
ob_start();
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

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

// Active season
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $r->free(); }

// ── Check which optional tables exist ────────────────────────────────────────
$hasWC       = $conn->query("SHOW TABLES LIKE 'working_capital'")->num_rows > 0;
$hasRollover = $conn->query("SHOW TABLES LIKE 'rollover'")->num_rows > 0;
$hasPayments = $conn->query("SHOW TABLES LIKE 'loan_payments'")->num_rows > 0;
$hasBlocked  = $conn->query("SHOW TABLES LIKE 'blocked_growers'")->num_rows > 0;
$hasScheme   = $conn->query("SHOW TABLES LIKE 'scheme'")->num_rows > 0;

// ── Pre-build optional subquery fragments ────────────────────────────────────
$wcGrowerSub  = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.growerid=g.id AND wc2.seasonid=$seasonId), 0)" : "0";
$wcOfficerSub = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.userid=fo.userid AND wc2.seasonid=$seasonId), 0)" : "0";
$wcTotalSub   = $hasWC       ? "COALESCE((SELECT SUM(wc2.amount) FROM working_capital wc2 WHERE wc2.seasonid=$seasonId), 0)" : "0";
$rvGrowerSub  = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.growerid=g.id AND rv2.seasonid=$seasonId), 0)" : "0";
$rvOfficerSub = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.userid=fo.userid AND rv2.seasonid=$seasonId), 0)" : "0";
$rvTotalSub   = $hasRollover ? "COALESCE((SELECT SUM(rv2.amount) FROM rollover rv2 WHERE rv2.seasonid=$seasonId), 0)" : "0";

// ── Filters from GET ──────────────────────────────────────────────────────────
$filterSplitId = isset($_GET['splitid'])    && $_GET['splitid']!==''    ? (int)$_GET['splitid']    : null;
$filterOfficer = isset($_GET['officer_id']) && $_GET['officer_id']!=='' ? (int)$_GET['officer_id'] : null;
$filterScheme  = isset($_GET['schemeid'])   && $_GET['schemeid']!==''   ? (int)$_GET['schemeid']   : null;
$filterGrower  = isset($_GET['grower_q'])   && $_GET['grower_q']!==''   ? trim($_GET['grower_q'])  : null;
if($filterGrower) $filterGrower = preg_replace('/[^a-zA-Z0-9 \-]/', '', $filterGrower);

// WHERE clause applied to all loans queries
$loanWhere = "l.seasonid=$seasonId";
if($filterOfficer) $loanWhere .= " AND l.userid=$filterOfficer";
if($filterScheme)  $loanWhere .= " AND l.growerid IN (SELECT shg.growerid FROM scheme_hectares_growers shg JOIN scheme_hectares sh ON sh.id=shg.scheme_hectaresid WHERE sh.schemeid=$filterScheme)";
if($filterGrower) {
    $gq = $conn->real_escape_string($filterGrower);
    $loanWhere .= " AND l.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gq%' OR grower_num LIKE '%$gq%' OR name LIKE '%$gq%' OR surname LIKE '%$gq%')";
}

// Splitid filter for prices subquery
$splitWhere = "seasonid=$seasonId";
if($filterSplitId) $splitWhere .= " AND splitid=$filterSplitId";

// ── Load available splitids for dropdown ──────────────────────────────────────
$splitIds = [];
$r = $conn->query("SELECT DISTINCT splitid FROM prices WHERE seasonid=$seasonId AND splitid IS NOT NULL ORDER BY splitid");
if($r){ while($row=$r->fetch_assoc()) $splitIds[]=$row['splitid']; $r->free(); }

// ── Load all field officers for dropdown ──────────────────────────────────────
$allOfficers = [];
$r = $conn->query("SELECT id, userid, name FROM field_officers ORDER BY name");
if($r){ while($row=$r->fetch_assoc()) $allOfficers[]=$row; $r->free(); }

// ── Load schemes for dropdown ─────────────────────────────────────────────────
$allSchemes = [];
$r = $conn->query("SELECT id, description FROM scheme ORDER BY description");
if($r){ while($row=$r->fetch_assoc()) $allSchemes[]=$row; $r->free(); }

// ── Prices subquery — latest price per productid+splitid for active season ────
$priceSubquery = "
    SELECT pr.productid, pr.splitid, pr.amount, pr.seasonid
    FROM prices pr
    INNER JOIN (
        SELECT productid, splitid, seasonid, MAX(id) AS max_id
        FROM prices
        WHERE $splitWhere
        GROUP BY productid, splitid, seasonid
    ) latest ON latest.max_id = pr.id
";


// ── Blocked growers exclusion ────────────────────────────────────────────────
// Any grower in blocked_growers for the active season is excluded from ALL calcs
$blockedClause    = $hasBlocked ? "AND l.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";
$blockedPayClause = $hasBlocked ? "AND lp.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

// ── KPI Totals — value = prices.amount * loans.quantity ──────────────────────
$kpi = ['total_loans'=>0,'total_value'=>0,'unverified'=>0,'unverified_value'=>0,'unprocessed'=>0,'synced'=>0,'surrogate'=>0];
$r = $conn->query("
    SELECT
        COUNT(*)                                                          AS total_loans,
        COALESCE(SUM(COALESCE(pr.amount,0) * l.quantity), 0)            AS total_value,
        SUM(l.verified=0)                                                AS unverified,
        COALESCE(SUM(CASE WHEN l.verified=0 THEN COALESCE(pr.amount,0)*l.quantity ELSE 0 END),0)
                                                                         AS unverified_value,
        SUM(l.processed=0)                                               AS unprocessed,
        SUM(l.sync=1)                                                    AS synced,
        SUM(l.surrogate=1)                                               AS surrogate
    FROM loans l
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE $loanWhere $blockedClause
");
if($r && $row=$r->fetch_assoc()){ $kpi=$row; $r->free(); }

// ── Loans by product ─────────────────────────────────────────────────────────
$byProduct = [];
$r = $conn->query("
    SELECT p.name AS product, pt.name AS type,
           COUNT(*) AS cnt,
           SUM(l.quantity) AS qty, p.units,
           COALESCE(pr.amount, 0) AS unit_price,
           COALESCE(SUM(COALESCE(pr.amount,0) * l.quantity), 0) AS total_cost
    FROM loans l
    JOIN products p ON p.id=l.productid
    JOIN product_type pt ON pt.id=p.product_typeid
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE $loanWhere $blockedClause
    GROUP BY l.productid, p.name, pt.name, pr.amount, p.units
    ORDER BY total_cost DESC
");
if($r){ while($row=$r->fetch_assoc()) $byProduct[]=$row; $r->free(); }

// ── Weekly trend (last 12 weeks) ─────────────────────────────────────────────
$weeklyTrend = [];
$r = $conn->query("
    SELECT YEARWEEK(l.datetime,1) AS yw,
           MIN(DATE(l.datetime))  AS week_start,
           COUNT(*)               AS cnt,
           COALESCE(SUM(COALESCE(pr.amount,0) * l.quantity), 0) AS val
    FROM loans l
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE $loanWhere $blockedClause AND l.datetime >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
    GROUP BY yw ORDER BY yw
");
if($r){ while($row=$r->fetch_assoc()) $weeklyTrend[]=$row; $r->free(); }

// ── Top 10 growers by loan value ─────────────────────────────────────────────
$topGrowers = [];
$r = $conn->query("
    SELECT g.grower_num, g.name AS gname, g.surname AS gsurname,
           COUNT(DISTINCT l.id) AS loan_count,
           COALESCE(SUM(COALESCE(pr.amount,0) * l.quantity), 0)
             + $wcGrowerSub
             + $rvGrowerSub
           AS total_value
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE $loanWhere $blockedClause
    GROUP BY l.growerid, g.grower_num, g.name, g.surname
    ORDER BY total_value DESC LIMIT 10
");
if($r){ while($row=$r->fetch_assoc()) $topGrowers[]=$row; $r->free(); }

// ── Loans per field officer ───────────────────────────────────────────────────
$byOfficer = [];
$r = $conn->query("
    SELECT fo.name AS officer, fo.userid AS officer_userid,
           COUNT(DISTINCT l.id) AS cnt,
           COALESCE(SUM(COALESCE(pr.amount,0) * l.quantity), 0)
             + $wcOfficerSub
             + $rvOfficerSub
           AS total_value,
           SUM(l.verified=0)   AS unverified,
           SUM(l.surrogate=1)  AS surrogate
    FROM loans l
    JOIN field_officers fo ON fo.userid=l.userid
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE $loanWhere $blockedClause
    GROUP BY l.userid, fo.name, fo.userid ORDER BY total_value DESC
");
if($r){ while($row=$r->fetch_assoc()) $byOfficer[]=$row; $r->free(); }

// ── Pipeline: avg days to verify ─────────────────────────────────────────────
$pipelineStats = ['avg_verify_days'=>0,'overdue_count'=>0,'pending_sync'=>0];
$r = $conn->query("
    SELECT
        ROUND(AVG(TIMESTAMPDIFF(DAY, datetime, STR_TO_DATE(verified_at,'%Y-%m-%d %H:%i:%s'))),1) AS avg_verify_days,
        SUM(verified=0 AND TIMESTAMPDIFF(DAY, datetime, NOW()) > 3) AS overdue_count,
        SUM(sync=0) AS pending_sync
    FROM loans WHERE seasonid=$seasonId AND growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)
");
if($r && $row=$r->fetch_assoc()){ $pipelineStats=$row; $r->free(); }

// ── Growers with no loan this season ─────────────────────────────────────────
$noLoanCount = 0;
$r = $conn->query("
    SELECT COUNT(*) AS cnt FROM growers g
    WHERE g.id NOT IN (SELECT growerid FROM loans WHERE seasonid=$seasonId)
      AND g.id NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)
");
if($r && $row=$r->fetch_assoc()){ $noLoanCount=(int)$row['cnt']; $r->free(); }

// ── Prices per product this season (display chips) ────────────────────────────
$prices = [];
$r = $conn->query("
    SELECT p.name AS product, p.units, pr.amount, pr.splitid
    FROM ($priceSubquery) pr
    JOIN products p ON p.id=pr.productid
    ORDER BY p.name
");
if($r){ while($row=$r->fetch_assoc()) $prices[]=$row; $r->free(); }

// ── Recent unverified loans ───────────────────────────────────────────────────
$unverifiedLoans = [];
$r = $conn->query("
    SELECT l.id, g.grower_num, g.name AS gname, g.surname AS gsurname,
           p.name AS product, l.quantity, p.units,
           COALESCE(pr.amount, 0)                              AS unit_price,
           COALESCE(pr.amount, 0) * l.quantity                 AS computed_value,
           fo.name AS officer,
           l.datetime,
           TIMESTAMPDIFF(DAY, l.datetime, NOW()) AS days_pending
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    JOIN products p ON p.id=l.productid
    JOIN field_officers fo ON fo.userid=l.userid
    LEFT JOIN ($priceSubquery) pr
           ON pr.productid = l.productid
          AND pr.splitid   = l.splitid
          AND pr.seasonid  = l.seasonid
    WHERE l.verified=0 AND $loanWhere $blockedClause
    ORDER BY l.datetime ASC LIMIT 20
");
if($r){ while($row=$r->fetch_assoc()) $unverifiedLoans[]=$row; $r->free(); }

// ── Payment / Recovery queries ────────────────────────────────────────────────
if($hasPayments):
// Apply same officer filter to payments
$payWhere = "lp.seasonid=$seasonId";
if($filterOfficer) $payWhere .= " AND lp.userid=$filterOfficer";
if($filterGrower) { $gq2 = $conn->real_escape_string($filterGrower); $payWhere .= " AND lp.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gq2%' OR grower_num LIKE '%$gq2%' OR name LIKE '%$gq2%' OR surname LIKE '%$gq2%')"; }

// Recovery summary KPIs
$recovery = ['total_paid'=>0,'total_mass'=>0,'payment_count'=>0,'growers_paid'=>0,'surrogate_payments'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(lp.amount),0)           AS total_paid,
           COALESCE(SUM(lp.mass),0)             AS total_mass,
           COUNT(*)                             AS payment_count,
           COUNT(DISTINCT lp.growerid)          AS growers_paid,
           SUM(lp.surrogate=1)                  AS surrogate_payments
    FROM loan_payments lp
    WHERE $payWhere $blockedPayClause
");
if($r && $row=$r->fetch_assoc()){ $recovery=$row; $r->free(); }

// Outstanding balance = (loan value + working capital) - total paid
$loanTotalForBalance = ($kpi['total_value'] ?? 0) + ($hasWC ? ($wcKpi['total_wc'] ?? 0) : 0) + ($hasRollover ? ($rvKpi['total_rv'] ?? 0) : 0);
$outstanding = max(0, $loanTotalForBalance - ($recovery['total_paid'] ?? 0));
$recoveryPct = $loanTotalForBalance > 0 ? round(($recovery['total_paid'] / $loanTotalForBalance) * 100, 1) : 0;

// Recovery by officer
$recoveryByOfficer = [];
$r = $conn->query("
    SELECT fo.name AS officer,
           COALESCE(SUM(lp.amount),0)  AS paid,
           COALESCE(SUM(lp.mass),0)    AS mass,
           COUNT(*)                    AS payment_count,
           COUNT(DISTINCT lp.growerid) AS growers_paid
    FROM loan_payments lp
    JOIN field_officers fo ON fo.userid=lp.userid
    WHERE $payWhere $blockedPayClause
    GROUP BY lp.userid, fo.name ORDER BY paid DESC
");
if($r){ while($row=$r->fetch_assoc()) $recoveryByOfficer[]=$row; $r->free(); }

// Weekly payment trend (last 12 weeks)
$paymentTrend = [];
$r = $conn->query("
    SELECT YEARWEEK(lp.datetime,1) AS yw,
           MIN(DATE(lp.datetime))  AS week_start,
           COUNT(*)                AS cnt,
           COALESCE(SUM(lp.amount),0) AS paid
    FROM loan_payments lp
    WHERE $payWhere $blockedPayClause AND lp.datetime >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
    GROUP BY yw ORDER BY yw
");
if($r){ while($row=$r->fetch_assoc()) $paymentTrend[]=$row; $r->free(); }

// Aging buckets — growers with outstanding loans grouped by days since last payment
$agingBuckets = ['current'=>0,'30'=>0,'60'=>0,'90plus'=>0];
$r = $conn->query("
    SELECT
        SUM(CASE WHEN DATEDIFF(NOW(), COALESCE(last_pay.last_paid, l.datetime)) <= 30 THEN loan_val ELSE 0 END) AS current_amt,
        SUM(CASE WHEN DATEDIFF(NOW(), COALESCE(last_pay.last_paid, l.datetime)) BETWEEN 31 AND 60 THEN loan_val ELSE 0 END) AS days30_amt,
        SUM(CASE WHEN DATEDIFF(NOW(), COALESCE(last_pay.last_paid, l.datetime)) BETWEEN 61 AND 90 THEN loan_val ELSE 0 END) AS days60_amt,
        SUM(CASE WHEN DATEDIFF(NOW(), COALESCE(last_pay.last_paid, l.datetime)) > 90 THEN loan_val ELSE 0 END) AS days90_amt
    FROM (
        SELECT l.growerid,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS loan_val
        FROM loans l
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $loanWhere $blockedClause
        GROUP BY l.growerid
    ) loan_totals
    JOIN loans l ON l.growerid = loan_totals.growerid AND l.seasonid=$seasonId
    LEFT JOIN (
        SELECT growerid, MAX(datetime) AS last_paid
        FROM loan_payments WHERE seasonid=$seasonId
        GROUP BY growerid
    ) last_pay ON last_pay.growerid = loan_totals.growerid
");
if($r && $row=$r->fetch_assoc()){
    $agingBuckets = ['current'=>$row['current_amt'],'30'=>$row['days30_amt'],'60'=>$row['days60_amt'],'90plus'=>$row['days90_amt']];
    $r->free();
}

// Top 10 growers by payment amount
$topPayers = [];
$r = $conn->query("
    SELECT g.grower_num, g.name AS gname, g.surname AS gsurname,
           COALESCE(SUM(lp.amount),0) AS total_paid,
           COALESCE(SUM(lp.mass),0)   AS total_mass,
           COUNT(*)                   AS payment_count
    FROM loan_payments lp
    JOIN growers g ON g.id=lp.growerid
    WHERE $payWhere $blockedPayClause
    GROUP BY lp.growerid, g.grower_num, g.name, g.surname
    ORDER BY total_paid DESC LIMIT 10
");
if($r){ while($row=$r->fetch_assoc()) $topPayers[]=$row; $r->free(); }

// Recent payments list
$recentPayments = [];
$r = $conn->query("
    SELECT lp.reference_num, lp.receipt_number, lp.description,
           lp.amount, lp.mass,
           g.name AS gname, g.surname AS gsurname, g.grower_num,
           fo.name AS officer,
           DATE(lp.datetime) AS pay_date,
           TIME(lp.datetime) AS pay_time,
           lp.surrogate
    FROM loan_payments lp
    JOIN growers g ON g.id=lp.growerid
    JOIN field_officers fo ON fo.userid=lp.userid
    WHERE $payWhere $blockedPayClause
    ORDER BY lp.datetime DESC LIMIT 20
");
if($r){ while($row=$r->fetch_assoc()) $recentPayments[]=$row; $r->free(); }


// ── Scheme subquery filter clauses ───────────────────────────────────────────
$schLoanExtra = "AND l2.seasonid=$seasonId";
if($filterOfficer) $schLoanExtra .= " AND l2.userid=$filterOfficer";
if($filterGrower) { $gqsc = $conn->real_escape_string($filterGrower); $schLoanExtra .= " AND l2.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqsc%' OR grower_num LIKE '%$gqsc%')"; }
$schBlockedExtra = $hasBlocked ? "AND l2.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$schPayExtra  = "AND lp2.seasonid=$seasonId";
if($filterOfficer) $schPayExtra .= " AND lp2.userid=$filterOfficer";
$schPayBlocked = $hasBlocked ? "AND lp2.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$schPriceSubquery = $priceSubquery;

// ── Scheme breakdown queries ─────────────────────────────────────────────────
$schemeData = [];
$r = $conn->query("
    SELECT
        s.id AS scheme_id,
        s.description AS scheme_name,
        -- Hectares: sum of scheme_hectares.quantity for this scheme (subquery avoids join duplication)
        COALESCE((SELECT SUM(CAST(sh2.quantity AS DECIMAL(10,2)) * (SELECT COUNT(*) FROM scheme_hectares_growers shg3 WHERE shg3.scheme_hectaresid=sh2.id)) FROM scheme_hectares sh2 WHERE sh2.schemeid=s.id),0) AS total_hectares,
        -- Growers enrolled (subquery with DISTINCT at scheme level to prevent duplicate counting)
        (SELECT COUNT(DISTINCT shg2.growerid) FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh2 ON sh2.id=shg2.scheme_hectaresid WHERE sh2.schemeid=s.id) AS enrolled_growers,
        -- Products allocated: sum of scheme_hectares_products.quantity per scheme
        (SELECT COALESCE(SUM(CAST(shp2.quantity AS DECIMAL(10,2))),0)
         FROM scheme_hectares_products shp2
         JOIN scheme_hectares sh2 ON sh2.id=shp2.scheme_hectaresid
         WHERE sh2.schemeid=s.id) AS total_product_qty,
        -- Loans issued to enrolled growers
        (SELECT COUNT(*)
         FROM loans l2
         LEFT JOIN ($schPriceSubquery) pr2 ON pr2.productid=l2.productid AND pr2.splitid=l2.splitid AND pr2.seasonid=l2.seasonid
         WHERE l2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
           $schLoanExtra $schBlockedExtra) AS loan_count,
        -- Loan value
        (SELECT COALESCE(SUM(COALESCE(pr2.amount,0)*l2.quantity),0)
         FROM loans l2
         LEFT JOIN ($schPriceSubquery) pr2 ON pr2.productid=l2.productid AND pr2.splitid=l2.splitid AND pr2.seasonid=l2.seasonid
         WHERE l2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
           $schLoanExtra $schBlockedExtra) AS loan_value,
        -- Recovery
        (SELECT COALESCE(SUM(lp2.amount),0)
         FROM loan_payments lp2
         WHERE lp2.growerid IN (SELECT shg2.growerid FROM scheme_hectares_growers shg2 JOIN scheme_hectares sh3 ON sh3.id=shg2.scheme_hectaresid WHERE sh3.schemeid=s.id)
           $schPayExtra $schPayBlocked) AS recovered
    FROM scheme s
    GROUP BY s.id, s.description
    ORDER BY s.description
");
if($r){ while($row=$r->fetch_assoc()) $schemeData[]=$row; $r->free(); }

// Product breakdown per scheme
$schemeProducts = [];
$r = $conn->query("
    SELECT s.id AS scheme_id, s.description AS scheme_name,
           p.name AS product, p.units,
           COALESCE(SUM(CAST(shp.quantity AS DECIMAL(10,2))),0) AS allocated_qty
    FROM scheme s
    JOIN scheme_hectares sh ON sh.schemeid=s.id
    JOIN scheme_hectares_products shp ON shp.scheme_hectaresid=sh.id
    JOIN products p ON p.id=shp.productid
    GROUP BY s.id, s.description, p.id, p.name, p.units
    ORDER BY s.description, p.name
");
if($r){ while($row=$r->fetch_assoc()) $schemeProducts[$row['scheme_id']][]=$row; $r->free(); }

// ── Hectares queries ─────────────────────────────────────────────────────────
// Grand total hectares from scheme_hectares for active season
$totalHectares = 0;
$hasSchemeHa = $conn->query("SHOW TABLES LIKE 'scheme_hectares'")->num_rows > 0;
if($hasSchemeHa) {
    $r = $conn->query("
        SELECT COALESCE(SUM(
               CAST(sh.quantity AS DECIMAL(10,2)) *
               (SELECT COUNT(*) FROM scheme_hectares_growers shg2 WHERE shg2.scheme_hectaresid=sh.id)
               ),0) AS total_ha
        FROM scheme_hectares sh
        JOIN scheme s ON s.id=sh.schemeid
        WHERE sh.seasonid=$seasonId
    ");
    if($r && $row=$r->fetch_assoc()){ $totalHectares=(float)$row['total_ha']; $r->free(); }
}

// Hectares per officer — sum of scheme_hectares for growers assigned to each officer
$haBySchemOfficer = [];
if($hasSchemeHa) {
    $r = $conn->query("
        SELECT fo.name AS officer,
               COALESCE(SUM(CAST(sh.quantity AS DECIMAL(10,2))),0) AS total_ha,
               COUNT(DISTINCT shg.growerid) AS growers
        FROM scheme_hectares sh
        JOIN scheme_hectares_growers shg ON shg.scheme_hectaresid=sh.id
        JOIN grower_field_officer gfo ON gfo.growerid=shg.growerid AND gfo.seasonid=$seasonId
        JOIN field_officers fo ON fo.userid=gfo.field_officerid
        WHERE sh.seasonid=$seasonId
        GROUP BY fo.userid, fo.name ORDER BY total_ha DESC
    ");
    if($r){ while($row=$r->fetch_assoc()) $haBySchemOfficer[]=$row; $r->free(); }
}

$maxHaOfficer = max(1, max(array_column($haBySchemOfficer,'total_ha') ?: [1]));

$maxSchemeVal = max(1, max(array_column($schemeData,'loan_value') ?: [1]));

// ── Working Capital queries ───────────────────────────────────────────────────
if($hasWC):
$wcWhere = "wc.seasonid=$seasonId";
if($filterOfficer) $wcWhere .= " AND wc.userid=$filterOfficer";
if($filterGrower) { $gqwc = $conn->real_escape_string($filterGrower); $wcWhere .= " AND wc.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqwc%' OR grower_num LIKE '%$gqwc%')"; }
$wcBlockedClause = $hasBlocked ? "AND wc.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

// WC KPI totals
$wcKpi = ['total_wc'=>0,'wc_count'=>0,'wc_growers'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(wc.amount),0) AS total_wc,
           COUNT(*)                   AS wc_count,
           COUNT(DISTINCT wc.growerid) AS wc_growers
    FROM working_capital wc
    WHERE $wcWhere $wcBlockedClause
");
if($r && $row=$r->fetch_assoc()){ $wcKpi=$row; $r->free(); }

// WC by officer
$wcByOfficer = [];
$r = $conn->query("
    SELECT fo.name AS officer,
           COALESCE(SUM(wc.amount),0)   AS total_wc,
           COUNT(*)                     AS wc_count,
           COUNT(DISTINCT wc.growerid)  AS wc_growers
    FROM working_capital wc
    JOIN field_officers fo ON fo.userid=wc.userid
    WHERE $wcWhere $wcBlockedClause
    GROUP BY wc.userid, fo.name ORDER BY total_wc DESC
");
if($r){ while($row=$r->fetch_assoc()) $wcByOfficer[]=$row; $r->free(); }

// WC weekly trend
$wcTrend = [];
$r = $conn->query("
    SELECT YEARWEEK(wc.datetime,1) AS yw,
           MIN(DATE(wc.datetime))  AS week_start,
           COUNT(*)                AS cnt,
           COALESCE(SUM(wc.amount),0) AS val
    FROM working_capital wc
    WHERE $wcWhere $wcBlockedClause AND wc.datetime >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
    GROUP BY yw ORDER BY yw
");
if($r){ while($row=$r->fetch_assoc()) $wcTrend[]=$row; $r->free(); }

// Top growers by WC
$wcTopGrowers = [];
$r = $conn->query("
    SELECT g.grower_num, g.name AS gname, g.surname AS gsurname,
           COALESCE(SUM(wc.amount),0) AS total_wc,
           COUNT(*) AS wc_count
    FROM working_capital wc
    JOIN growers g ON g.id=wc.growerid
    WHERE $wcWhere $wcBlockedClause
    GROUP BY wc.growerid, g.grower_num, g.name, g.surname
    ORDER BY total_wc DESC LIMIT 10
");
if($r){ while($row=$r->fetch_assoc()) $wcTopGrowers[]=$row; $r->free(); }

// Recent WC entries
$recentWC = [];
$r = $conn->query("
    SELECT wc.receipt_number, wc.amount,
           g.name AS gname, g.surname AS gsurname, g.grower_num,
           fo.name AS officer,
           DATE(wc.datetime) AS wc_date, TIME(wc.datetime) AS wc_time
    FROM working_capital wc
    JOIN growers g ON g.id=wc.growerid
    JOIN field_officers fo ON fo.userid=wc.userid
    WHERE $wcWhere $wcBlockedClause
    ORDER BY wc.datetime DESC LIMIT 20
");
if($r){ while($row=$r->fetch_assoc()) $recentWC[]=$row; $r->free(); }

$maxWcOfficerVal = max(1, max(array_column($wcByOfficer,'total_wc') ?: [1]));
endif; // hasWC

// Combined totals (loans + working capital) for overall exposure
// ── Rollover queries ─────────────────────────────────────────────────────────
if($hasRollover):
$rvWhere = "rv.seasonid=$seasonId";
if($filterOfficer) $rvWhere .= " AND rv.userid=$filterOfficer";
if($filterGrower) { $gqrv = $conn->real_escape_string($filterGrower); $rvWhere .= " AND rv.growerid IN (SELECT id FROM growers WHERE CONCAT(name,' ',surname) LIKE '%$gqrv%' OR grower_num LIKE '%$gqrv%')"; }
$rvBlockedClause = $hasBlocked ? "AND rv.growerid NOT IN (SELECT growerid FROM blocked_growers WHERE seasonid=$seasonId)" : "";

$rvKpi = ['total_rv'=>0,'rv_count'=>0,'rv_growers'=>0];
$r = $conn->query("
    SELECT COALESCE(SUM(rv.amount),0)       AS total_rv,
           COUNT(*)                         AS rv_count,
           COUNT(DISTINCT rv.growerid)      AS rv_growers
    FROM rollover rv
    WHERE $rvWhere $rvBlockedClause
");
if($r && $row=$r->fetch_assoc()){ $rvKpi=$row; $r->free(); }

$rvByOfficer = [];
$r = $conn->query("
    SELECT fo.name AS officer,
           COALESCE(SUM(rv.amount),0)  AS total_rv,
           COUNT(*)                    AS rv_count,
           COUNT(DISTINCT rv.growerid) AS rv_growers
    FROM rollover rv
    JOIN field_officers fo ON fo.userid=rv.userid
    WHERE $rvWhere $rvBlockedClause
    GROUP BY rv.userid, fo.name ORDER BY total_rv DESC
");
if($r){ while($row=$r->fetch_assoc()) $rvByOfficer[]=$row; $r->free(); }

endif; // hasRollover

$totalExposure   = ($kpi['total_value'] ?? 0) + ($hasWC ? ($wcKpi['total_wc'] ?? 0) : 0) + ($hasRollover ? ($rvKpi['total_rv'] ?? 0) : 0);
$totalRecovered  = ($recovery['total_paid'] ?? 0);
$totalOutstanding = max(0, $totalExposure - $totalRecovered);
$totalRecoveryPct = $totalExposure > 0 ? round(($totalRecovered / $totalExposure) * 100, 1) : 0;

$maxRecoveryVal = max(1, max(array_column($recoveryByOfficer,'paid') ?: [1]));
endif; // hasPayments

$maxOfficerVal = max(1, max(array_column($byOfficer,'total_value') ?: [1]));
$maxProductVal = max(1, max(array_column($byProduct,'total_cost') ?: [1]));
$maxGrowerVal  = max(1, max(array_column($topGrowers,'total_value') ?: [1]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GMS · Loans Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080d08;--surface:#0f170f;--surface2:#141e14;
  --border:#1a2a1a;--border2:#243224;
  --green:#3ddc68;--green-dim:#1a5e30;--green-glow:rgba(61,220,104,.12);
  --amber:#f5a623;--red:#e84040;--blue:#4a9eff;--purple:#b060ff;
  --text:#c8e6c9;--muted:#4a6b4a;--muted2:#2d4a2d;
  --radius:8px;--radius2:4px;
}
html,body{min-height:100%;font-family:'Space Mono',monospace;background:var(--bg);color:var(--text);font-size:13px}

/* ── Header ── */
header{display:flex;align-items:center;gap:12px;padding:0 24px;height:58px;
  background:var(--surface);border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:200;flex-wrap:wrap}
.logo{font-family:'Syne',sans-serif;font-size:20px;font-weight:900;color:var(--green);letter-spacing:-1px}
.logo span{color:var(--muted)}
.back{font-size:10px;color:var(--muted);text-decoration:none;border:1px solid var(--border);
  padding:4px 10px;border-radius:var(--radius2);transition:.15s}
.back:hover{color:var(--green);border-color:var(--green)}
.season-badge{margin-left:auto;font-size:10px;color:var(--green);
  border:1px solid var(--green-dim);padding:3px 10px;border-radius:var(--radius2)}

/* ── Layout ── */
.page{padding:24px;max-width:1600px;margin:0 auto}
.section-title{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;
  text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:14px;
  display:flex;align-items:center;gap:8px}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}

/* ── KPI Grid ── */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:28px}
.kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:16px 18px;position:relative;overflow:hidden;transition:.2s;min-width:0}
.kpi::before{content:'';position:absolute;inset:0;background:var(--green-glow);opacity:0;transition:.2s}
.kpi:hover::before{opacity:1}
.kpi-icon{font-size:18px;margin-bottom:6px}
.kpi-val{font-family:'Syne',sans-serif;font-size:clamp(14px,1.6vw,22px);font-weight:900;line-height:1.1;margin-bottom:4px;word-break:break-word}
.kpi-label{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.kpi-sub{font-size:10px;color:var(--muted);margin-top:6px;word-break:break-word}
.kpi.warn .kpi-val{color:var(--amber)}
.kpi.danger .kpi-val{color:var(--red)}
.kpi.good .kpi-val{color:var(--green)}
.kpi.info .kpi-val{color:var(--blue)}

/* ── Two col ── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px}
@media(max-width:1100px){.two-col,.three-col{grid-template-columns:1fr}}

/* ── Card ── */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.card-head{padding:12px 16px;border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:center;background:var(--surface2)}
.card-head h3{font-family:'Syne',sans-serif;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.card-head .badge{font-size:9px;color:var(--muted);border:1px solid var(--border);
  padding:2px 8px;border-radius:var(--radius2)}
.card-body{padding:16px}

/* ── Bar rows ── */
.bar-row{margin-bottom:10px}
.bar-label{display:flex;justify-content:space-between;font-size:10px;margin-bottom:4px}
.bar-label .name{color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:55%}
.bar-label .val{color:var(--muted);text-align:right}
.bar-track{height:5px;background:var(--border);border-radius:3px}
.bar-fill{height:100%;border-radius:3px;transition:width .6s cubic-bezier(.4,0,.2,1)}

/* ── Officer rows ── */
.officer-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)}
.officer-row:last-child{border-bottom:none}
.officer-avatar{width:30px;height:30px;border-radius:50%;background:var(--green-dim);
  display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
  color:var(--green);flex-shrink:0;font-family:'Syne',sans-serif}
.officer-info{flex:1;min-width:0}
.officer-name{font-size:11px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.officer-meta{font-size:9px;color:var(--muted);margin-top:2px}
.officer-val{font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:var(--green);white-space:nowrap}
.pill{display:inline-block;padding:1px 6px;border-radius:10px;font-size:9px;margin-left:4px}
.pill.warn{background:#2a1f00;color:var(--amber);border:1px solid #4a3500}
.pill.danger{background:#200a0a;color:var(--red);border:1px solid #4a1010}
.pill.info{background:#001428;color:var(--blue);border:1px solid #002050}

/* ── Pipeline ── */
.pipeline{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border)}
.pipe-step{background:var(--surface);padding:16px;text-align:center}
.pipe-num{font-family:'Syne',sans-serif;font-size:32px;font-weight:900;line-height:1}
.pipe-lbl{font-size:9px;text-transform:uppercase;color:var(--muted);margin-top:4px;letter-spacing:.5px}

/* ── Table ── */
.data-table{width:100%;border-collapse:collapse;font-size:10px}
.data-table th{text-align:left;padding:8px 12px;font-size:8px;text-transform:uppercase;
  letter-spacing:.5px;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface2)}
.data-table td{padding:8px 12px;border-bottom:1px solid #0d180d}
.data-table tr:last-child td{border-bottom:none}
.data-table tr:hover td{background:rgba(61,220,104,.02)}
.overdue{color:var(--red)}
.pending{color:var(--amber)}

/* ── Chart container ── */
.chart-wrap{position:relative;height:220px;padding:8px}

/* ── Price chips ── */
.price-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;padding:16px}
.price-chip{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius2);padding:10px 12px}
.pc-name{font-size:10px;font-weight:700;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pc-amount{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--green)}
.pc-unit{font-size:9px;color:var(--muted)}

/* ── Geo warning ── */
.geo-bar{background:#1a1000;border:1px solid #3a2500;border-radius:var(--radius2);
  padding:8px 14px;font-size:10px;color:var(--amber);margin-bottom:24px;display:flex;align-items:center;gap:8px}

/* ── Empty ── */
.empty{padding:24px;text-align:center;color:var(--muted);font-size:11px}

/* ── Scroll ── */
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-thumb{background:var(--border2)}
</style>
</head>
<body>
<header>
  <div class="logo">GMS<span>/</span>Loans</div>
  <a href="reports_hub.php" class="back">← Reports</a>
  <a href="cluster_performance.php" class="back">🗺 Clusters</a>
  <?php if($pipelineStats['pending_sync']>0): ?>
  <div style="font-size:10px;color:var(--amber);border:1px solid #3a2500;padding:3px 10px;border-radius:4px">
    ⚠ <?=$pipelineStats['pending_sync']?> loans pending sync
  </div>
  <?php endif?>

  <!-- ── Filters ── -->
  <form method="GET" id="filter-form" style="display:flex;align-items:center;gap:8px;margin-left:12px;flex-wrap:wrap">
    <!-- Split ID -->
    <select name="splitid" onchange="this.form.submit()" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:10px;padding:4px 8px;border-radius:4px">
      <option value="">All Splits</option>
      <?php foreach($splitIds as $sid): ?>
      <option value="<?=$sid?>" <?=$filterSplitId==$sid?'selected':''?>>Split #<?=$sid?></option>
      <?php endforeach?>
    </select>
    <!-- Field Officer -->
    <select name="officer_id" onchange="this.form.submit()" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:10px;padding:4px 8px;border-radius:4px">
      <option value="">All Officers</option>
      <?php foreach($allOfficers as $o): ?>
      <option value="<?=$o['userid']?>" <?=$filterOfficer==$o['userid']?'selected':''?>><?=htmlspecialchars($o['name'])?></option>
      <?php endforeach?>
    </select>
    <!-- Scheme -->
    <select name="schemeid" onchange="this.form.submit()" style="background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:10px;padding:4px 8px;border-radius:4px">
      <option value="">All Schemes</option>
      <?php foreach($allSchemes as $sc): ?>
      <option value="<?=$sc['id']?>" <?=$filterScheme==$sc['id']?'selected':''?>><?=htmlspecialchars($sc['description'])?></option>
      <?php endforeach?>
    </select>
    <!-- Group by officer toggle -->
    <label style="display:flex;align-items:center;gap:5px;font-size:10px;color:var(--muted);cursor:pointer;border:1px solid var(--border);padding:4px 8px;border-radius:4px;<?=isset($_GET['group_officer'])?'border-color:var(--green);color:var(--green)':''?>">
      <input type="checkbox" name="group_officer" value="1" <?=isset($_GET['group_officer'])?'checked':''?> onchange="this.form.submit()" style="accent-color:var(--green)">
      Group by Officer
    </label>
    <!-- Grower search -->
    <div style="position:relative;display:flex;align-items:center">
      <input type="text" name="grower_q"
        value="<?=htmlspecialchars($filterGrower??'')?>"
        placeholder="🔍 Grower name or #"
        style="background:var(--surface2);border:1px solid var(--border);color:var(--text);
               font-family:'Space Mono',monospace;font-size:10px;padding:4px 26px 4px 8px;
               border-radius:4px;outline:none;width:170px;transition:.15s"
        onfocus="this.style.borderColor='var(--green)'"
        onblur="this.style.borderColor='var(--border)'"
        form="filter-form">
      <?php if($filterGrower): ?>
      <a href="?<?=http_build_query(array_diff_key($_GET,['grower_q'=>1]))?>"
         style="position:absolute;right:6px;color:var(--muted);text-decoration:none;font-size:12px"
         title="Clear">✕</a>
      <?php endif?>
    </div>

    <?php if($filterSplitId || $filterOfficer || $filterScheme || $filterGrower || isset($_GET['group_officer'])): ?>
    <a href="?" style="font-size:10px;color:var(--red);border:1px solid #3a1010;padding:4px 8px;border-radius:4px;text-decoration:none">✕ Clear all</a>
    <?php endif?>
  </form>

  <?php if($filterGrower): ?>
  <div style="font-size:10px;color:var(--green);border:1px solid var(--green-dim);padding:3px 10px;border-radius:4px">
    👤 "<?=htmlspecialchars($filterGrower)?>"
  </div>
  <?php endif?>
  <div class="season-badge" style="margin-left:auto">Season <?=$seasonId?> · Active</div>
</header>

<div class="page">

  <!-- ── KPIs ── -->
  <div class="section-title">Overview</div>
  <div class="kpi-grid">
    <div class="kpi good">
      <div class="kpi-icon">📦</div>
      <div class="kpi-val"><?=number_format($kpi['total_loans'])?></div>
      <div class="kpi-label">Total Loans Issued</div>
      <div class="kpi-sub">This season</div>
    </div>
    <div class="kpi good">
      <div class="kpi-icon">💰</div>
      <div class="kpi-val"><?=fmtAmount($kpi['total_value'])?></div>
      <div class="kpi-label">Total Loan Value</div>
      <div class="kpi-sub">Loans · excl. working capital</div>
    </div>
    <div class="kpi <?=$kpi['unverified']>0?'warn':''?>">
      <div class="kpi-icon">🔍</div>
      <div class="kpi-val"><?=number_format($kpi['unverified'])?></div>
      <div class="kpi-label">Unverified Loans</div>
      <div class="kpi-sub"><?=fmtAmount($kpi['unverified_value'])?> at risk</div>
    </div>
    <div class="kpi <?=$kpi['unprocessed']>0?'warn':''?>">
      <div class="kpi-icon">⏳</div>
      <div class="kpi-val"><?=number_format($kpi['unprocessed'])?></div>
      <div class="kpi-label">Unprocessed</div>
      <div class="kpi-sub">Pending processing</div>
    </div>
    <div class="kpi <?=$pipelineStats['overdue_count']>0?'danger':''?>">
      <div class="kpi-icon">🚨</div>
      <div class="kpi-val"><?=number_format($pipelineStats['overdue_count'])?></div>
      <div class="kpi-label">Overdue &gt;3 Days</div>
      <div class="kpi-sub">Unverified past deadline</div>
    </div>
    <div class="kpi info">
      <div class="kpi-icon">⚡</div>
      <div class="kpi-val"><?=$pipelineStats['avg_verify_days']??'—'?></div>
      <div class="kpi-label">Avg Days to Verify</div>
      <div class="kpi-sub">Verification turnaround</div>
    </div>
    <div class="kpi <?=$kpi['surrogate']>0?'warn':''?>">
      <div class="kpi-icon">👥</div>
      <div class="kpi-val"><?=number_format($kpi['surrogate'])?></div>
      <div class="kpi-label">Surrogate Loans</div>
      <div class="kpi-sub">Captured on behalf</div>
    </div>
    <div class="kpi <?=$noLoanCount>0?'danger':''?>">
      <div class="kpi-icon">🌾</div>
      <div class="kpi-val"><?=number_format($noLoanCount)?></div>
      <div class="kpi-label">Growers No Loan</div>
      <div class="kpi-sub">Not yet received inputs</div>
    </div>
    <div class="kpi info">
      <div class="kpi-icon">📐</div>
      <div class="kpi-val"><?=number_format($totalHectares,1)?></div>
      <div class="kpi-label">Total Hectares</div>
      <div class="kpi-sub">Across all schemes</div>
    </div>
    <div class="kpi info">
      <div class="kpi-icon">💼</div>
      <div class="kpi-val"><?=fmtAmount($wcKpi['total_wc']??0)?></div>
      <div class="kpi-label">Working Capital</div>
      <div class="kpi-sub"><?=number_format($wcKpi['wc_count']??0)?> entries · <?=number_format($wcKpi['wc_growers']??0)?> growers</div>
    </div>
    <div class="kpi">
      <div class="kpi-icon">🔄</div>
      <div class="kpi-val"><?=fmtAmount($rvKpi['total_rv']??0)?></div>
      <div class="kpi-label">Rollover</div>
      <div class="kpi-sub"><?=number_format($rvKpi['rv_count']??0)?> entries · <?=number_format($rvKpi['rv_growers']??0)?> growers</div>
    </div>
    <div class="kpi">
      <div class="kpi-icon">📊</div>
      <div class="kpi-val"><?=fmtAmount($totalExposure)?></div>
      <div class="kpi-label">Total Exposure</div>
      <div class="kpi-sub">Loans + WC + Rollover</div>
    </div>
    <div class="kpi <?=$totalRecoveryPct>=70?'good':($totalRecoveryPct>=40?'warn':'danger')?>">
      <div class="kpi-icon">📈</div>
      <div class="kpi-val"><?=$totalRecoveryPct?>%</div>
      <div class="kpi-label">Overall Recovery</div>
      <div class="kpi-sub"><?=fmtAmount($totalOutstanding)?> outstanding</div>
    </div>
  </div>

  <!-- ── Trend + Pipeline ── -->
  <div class="two-col" style="margin-bottom:24px">
    <div class="card">
      <div class="card-head">
        <h3>📈 Weekly Loan Trend</h3>
        <span class="badge">Last 12 weeks</span>
      </div>
      <div class="chart-wrap">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-head">
        <h3>🔄 Verification Pipeline</h3>
        <span class="badge">Current season</span>
      </div>
      <div class="pipeline">
        <div class="pipe-step">
          <div class="pipe-num" style="color:var(--green)"><?=number_format($kpi['total_loans'])?></div>
          <div class="pipe-lbl">Captured</div>
        </div>
        <div class="pipe-step">
          <div class="pipe-num" style="color:var(--amber)"><?=number_format($kpi['unverified'])?></div>
          <div class="pipe-lbl">Unverified</div>
        </div>
        <div class="pipe-step">
          <div class="pipe-num" style="color:var(--red)"><?=number_format($kpi['unprocessed'])?></div>
          <div class="pipe-lbl">Unprocessed</div>
        </div>
      </div>
      <div style="padding:14px 16px;border-top:1px solid var(--border)">
        <div class="bar-row">
          <div class="bar-label">
            <span class="name">Verified</span>
            <span class="val"><?=($kpi['total_loans']>0?round((($kpi['total_loans']-$kpi['unverified'])/$kpi['total_loans'])*100):0)?>%</span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=($kpi['total_loans']>0?round((($kpi['total_loans']-$kpi['unverified'])/$kpi['total_loans'])*100):0)?>%;background:var(--green)"></div></div>
        </div>
        <div class="bar-row">
          <div class="bar-label">
            <span class="name">Processed</span>
            <span class="val"><?=($kpi['total_loans']>0?round((($kpi['total_loans']-$kpi['unprocessed'])/$kpi['total_loans'])*100):0)?>%</span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=($kpi['total_loans']>0?round((($kpi['total_loans']-$kpi['unprocessed'])/$kpi['total_loans'])*100):0)?>%;background:var(--blue)"></div></div>
        </div>
        <div class="bar-row">
          <div class="bar-label">
            <span class="name">Synced to server</span>
            <span class="val"><?=($kpi['total_loans']>0?round(($kpi['synced']/$kpi['total_loans'])*100):0)?>%</span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=($kpi['total_loans']>0?round(($kpi['synced']/$kpi['total_loans'])*100):0)?>%;background:var(--purple)"></div></div>
        </div>
      </div>
    </div>
  </div>


  <!-- ── Recovery Section ── -->
  <div class="section-title" style="margin-top:8px">💳 Loan Recovery</div>

  <!-- Recovery KPI cards -->
  <div class="recovery-grid">
    <div class="rec-card">
      <div class="rec-val" style="color:var(--green)"><?=fmtAmount($recovery['total_paid']??0)?></div>
      <div class="rec-lbl">Total Recovered</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--amber)"><?=fmtAmount($outstanding)?></div>
      <div class="rec-lbl">Outstanding Balance</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:<?=$recoveryPct>=70?'var(--green)':($recoveryPct>=40?'var(--amber)':'var(--red)')?>"><?=$recoveryPct?>%</div>
      <div class="rec-lbl">Recovery Rate</div>
      <div class="recovery-bar"><div class="recovery-bar-fill" style="width:<?=min(100,$recoveryPct)?>%"></div></div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--blue)"><?=number_format($recovery['payment_count']??0)?></div>
      <div class="rec-lbl">Total Payments</div>
    </div>
    <div class="rec-card">
      <div class="rec-val"><?=number_format($recovery['growers_paid']??0)?></div>
      <div class="rec-lbl">Growers Paid</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--blue)"><?=number_format($recovery['total_mass']??0,1)?> kg</div>
      <div class="rec-lbl">Total Mass Paid</div>
    </div>
  </div>

  <!-- Recovery trend + Aging -->
  <div class="two-col" style="margin-bottom:24px">
    <div class="card">
      <div class="card-head">
        <h3>📈 Weekly Payment Trend</h3>
        <span class="badge">Last 12 weeks</span>
      </div>
      <div class="chart-wrap">
        <canvas id="paymentTrendChart"></canvas>
      </div>
    </div>
    <div class="card">
      <div class="card-head">
        <h3>⏳ Loan Aging</h3>
        <span class="badge">Days since last payment</span>
      </div>
      <div class="aging-grid">
        <div class="aging-cell">
          <div class="aging-val" style="color:var(--green)"><?=fmtAmount($agingBuckets['current']??0,0)?></div>
          <div class="aging-lbl">0–30 days</div>
        </div>
        <div class="aging-cell">
          <div class="aging-val" style="color:var(--amber)"><?=fmtAmount($agingBuckets['30']??0,0)?></div>
          <div class="aging-lbl">31–60 days</div>
        </div>
        <div class="aging-cell">
          <div class="aging-val" style="color:var(--red)"><?=fmtAmount($agingBuckets['60']??0,0)?></div>
          <div class="aging-lbl">61–90 days</div>
        </div>
        <div class="aging-cell">
          <div class="aging-val" style="color:#ff2020"><?=fmtAmount($agingBuckets['90plus']??0,0)?></div>
          <div class="aging-lbl">90+ days</div>
        </div>
      </div>
      <div style="padding:14px 16px;border-top:1px solid var(--border)">
        <?php
          $agingTotal = array_sum($agingBuckets);
          $agingColors = ['var(--green)','var(--amber)','var(--red)','#ff2020'];
          $agingLabels = ['0–30d','31–60d','61–90d','90+d'];
          foreach($agingBuckets as $i=>$bval):
            $bpct = $agingTotal>0 ? round(($bval/$agingTotal)*100) : 0;
            $bcol = $agingColors[array_search($i,array_keys($agingBuckets))];
        ?>
        <div class="bar-row" style="margin-bottom:8px">
          <div class="bar-label">
            <span class="name"><?=$agingLabels[array_search($i,array_keys($agingBuckets))]?></span>
            <span class="val"><?=$bpct?>%</span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=$bpct?>%;background:<?=$bcol?>"></div></div>
        </div>
        <?php endforeach?>
      </div>
    </div>
  </div>

  <!-- Recovery by officer + Top payers -->
  <div class="two-col" style="margin-bottom:24px">
    <div class="card">
      <div class="card-head">
        <h3>👮 Recovery by Officer</h3>
        <span class="badge"><?=count($recoveryByOfficer)?> officers</span>
      </div>
      <div class="card-body" style="padding:8px 16px">
        <?php if(empty($recoveryByOfficer)): ?>
        <div class="empty">No payments recorded yet</div>
        <?php else:
          $maxRec = max(1, max(array_column($recoveryByOfficer,'paid')));
          foreach($recoveryByOfficer as $ro):
            $rpct = round(($ro['paid']/$maxRec)*100);
        ?>
        <div class="officer-row">
          <div class="officer-avatar"><?=strtoupper(substr($ro['officer'],0,1))?></div>
          <div class="officer-info">
            <div class="officer-name"><?=htmlspecialchars($ro['officer'])?></div>
            <div class="officer-meta"><?=$ro['payment_count']?> payments · <?=$ro['growers_paid']?> growers · <?=number_format($ro['mass'],1)?> kg</div>
            <div class="bar-track" style="margin-top:4px"><div class="bar-fill" style="width:<?=$rpct?>%;background:var(--green)"></div></div>
          </div>
          <div class="officer-val"><?=fmtAmount($ro['paid'],0)?></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <h3>🏆 Top Paying Growers</h3>
        <span class="badge">By amount</span>
      </div>
      <div class="card-body">
        <?php if(empty($topPayers)): ?>
        <div class="empty">No payments recorded yet</div>
        <?php else:
          $maxPay = max(1, max(array_column($topPayers,'total_paid')));
          foreach($topPayers as $i=>$tp):
            $tpct = round(($tp['total_paid']/$maxPay)*100);
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span class="name">
              <?php if($i===0):?>🥇 <?php elseif($i===1):?>🥈 <?php elseif($i===2):?>🥉 <?php endif?>
              <?=htmlspecialchars($tp['gname'].' '.$tp['gsurname'])?> <span style="color:var(--muted);font-size:9px">#<?=$tp['grower_num']?></span>
            </span>
            <span class="val"><?=$tp['payment_count']?> pmts · <?=fmtAmount($tp['total_paid'])?></span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=$tpct?>%;background:var(--green)"></div></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>
  </div>

  <!-- Recent payments table -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-head">
      <h3>💳 Recent Payments</h3>
      <span class="badge"><?=count($recentPayments)?> shown</span>
    </div>
    <?php if(empty($recentPayments)): ?>
    <div style="padding:24px;text-align:center;color:var(--muted)">No payments recorded yet</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr>
        <th>Date</th><th>Grower</th><th>Officer</th><th>Reference</th><th>Receipt</th><th>Description</th><th style="text-align:right">Amount</th><th style="text-align:right">Mass (kg)</th><th>Surrogate</th>
      </tr></thead>
      <tbody>
      <?php foreach($recentPayments as $p): ?>
      <tr>
        <td style="color:var(--muted);white-space:nowrap"><?=$p['pay_date']?> <?=$p['pay_time']?></td>
        <td><b><?=htmlspecialchars($p['gname'].' '.$p['gsurname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$p['grower_num']?></span></td>
        <td><?=htmlspecialchars($p['officer'])?></td>
        <td style="color:var(--muted);font-size:9px"><?=htmlspecialchars($p['reference_num'])?></td>
        <td style="color:var(--muted);font-size:9px"><?=htmlspecialchars($p['receipt_number'])?></td>
        <td style="color:var(--muted)"><?=htmlspecialchars($p['description'])?></td>
        <td style="text-align:right;color:var(--green);font-weight:700"><?=fmtAmount($p['amount'])?></td>
        <td style="text-align:right;color:var(--muted)"><?=number_format($p['mass'],1)?></td>
        <td style="text-align:center"><?=$p['surrogate']?'<span style="color:var(--blue);font-size:9px">S</span>':'—'?></td>
      </tr>
      <?php endforeach?>
      </tbody>
    </table>
    </div>
    <?php endif?>
  </div>


  <!-- ── Schemes Section ── -->
  <div class="section-title" style="margin-top:8px">🌿 Scheme Breakdown</div>

  <?php if(empty($schemeData)): ?>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;text-align:center;color:var(--muted);margin-bottom:24px">No schemes configured yet</div>
  <?php else: ?>
  <div style="display:grid;gap:16px;margin-bottom:24px">
    <?php foreach($schemeData as $sc):
      $scRecPct = $sc['loan_value']>0 ? round(($sc['recovered']/$sc['loan_value'])*100,1) : 0;
      $scValPct = $maxSchemeVal>0 ? round(($sc['loan_value']/$maxSchemeVal)*100) : 0;
      $scRecCol = $scRecPct>=70?'var(--green)':($scRecPct>=40?'var(--amber)':'var(--red)');
      $scProds  = $schemeProducts[$sc['scheme_id']] ?? [];
      $isActive = $filterScheme == $sc['scheme_id'];
    ?>
    <div class="card" style="<?=$isActive?'border-color:var(--green)':''?>">
      <div class="card-head" style="<?=$isActive?'background:rgba(61,220,104,.06)':''?>">
        <h3 style="display:flex;align-items:center;gap:8px">
          🌿 <?=htmlspecialchars($sc['scheme_name'])?>
          <?php if($isActive): ?><span style="font-size:9px;color:var(--green);border:1px solid var(--green-dim);padding:1px 6px;border-radius:3px">Active Filter</span><?php endif?>
        </h3>
        <div style="display:flex;gap:8px;align-items:center">
          <a href="?schemeid=<?=$sc['scheme_id']?><?=$filterOfficer?"&officer_id=$filterOfficer":''?><?=$filterSplitId?"&splitid=$filterSplitId":''?>"
             style="font-size:9px;color:var(--blue);border:1px solid #002050;padding:2px 8px;border-radius:3px;text-decoration:none">
            <?=$isActive?'✕ Clear':'Filter →'?>
          </a>
        </div>
      </div>

      <!-- Scheme KPI row -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1px;background:var(--border)">
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800"><?=number_format($sc['total_hectares'],1)?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Hectares</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800"><?=number_format($sc['enrolled_growers'])?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Growers</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--blue)"><?=number_format($sc['total_hectares'],1)?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Hectares</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800"><?=number_format($sc['loan_count'])?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Loans Issued</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--green)"><?=fmtAmount($sc['loan_value'])?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Loan Value</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--blue)"><?=fmtAmount($sc['recovered'])?></div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Recovered</div>
        </div>
        <div style="background:var(--surface);padding:12px 14px">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:<?=$scRecCol?>"><?=$scRecPct?>%</div>
          <div style="font-size:8px;text-transform:uppercase;color:var(--muted);margin-top:2px">Recovery Rate</div>
          <div style="height:4px;background:var(--border);border-radius:2px;margin-top:6px">
            <div style="height:100%;width:<?=min(100,$scRecPct)?>%;background:<?=$scRecCol?>;border-radius:2px;transition:width .6s"></div>
          </div>
        </div>
      </div>

      <!-- Product allocations for this scheme -->
      <?php if(!empty($scProds)): ?>
      <div style="padding:10px 14px;border-top:1px solid var(--border);display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span style="font-size:8px;text-transform:uppercase;color:var(--muted);letter-spacing:.4px">Products:</span>
        <?php foreach($scProds as $sp): ?>
        <span style="background:var(--surface2);border:1px solid var(--border);border-radius:3px;padding:3px 8px;font-size:9px">
          <?=htmlspecialchars($sp['product'])?>
          <span style="color:var(--green);margin-left:4px"><?=number_format($sp['allocated_qty'],1)?> <?=htmlspecialchars($sp['units'])?></span>
        </span>
        <?php endforeach?>
        <span style="font-size:9px;color:var(--muted);margin-left:4px">Total allocated: <?=number_format($sc['total_product_qty'],1)?> units</span>
      </div>
      <?php endif?>

    </div>
    <?php endforeach?>
  </div>
  <?php endif?>

  <!-- ── Working Capital Section ── -->
  <div class="section-title" style="margin-top:8px">💼 Working Capital</div>

  <div class="recovery-grid">
    <div class="rec-card">
      <div class="rec-val" style="color:var(--blue)"><?=fmtAmount($wcKpi['total_wc']??0)?></div>
      <div class="rec-lbl">Total Working Capital</div>
    </div>
    <div class="rec-card">
      <div class="rec-val"><?=number_format($wcKpi['wc_count']??0)?></div>
      <div class="rec-lbl">WC Entries</div>
    </div>
    <div class="rec-card">
      <div class="rec-val"><?=number_format($wcKpi['wc_growers']??0)?></div>
      <div class="rec-lbl">Growers Funded</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--amber)"><?=fmtAmount($totalExposure)?></div>
      <div class="rec-lbl">Total Exposure</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:<?=$totalRecoveryPct>=70?'var(--green)':($totalRecoveryPct>=40?'var(--amber)':'var(--red)')?>"><?=$totalRecoveryPct?>%</div>
      <div class="rec-lbl">Overall Recovery</div>
      <div class="recovery-bar"><div class="recovery-bar-fill" style="width:<?=min(100,$totalRecoveryPct)?>%"></div></div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--red)"><?=fmtAmount($totalOutstanding)?></div>
      <div class="rec-lbl">Total Outstanding</div>
    </div>
    <div class="rec-card">
      <div class="rec-val" style="color:var(--purple)"><?=fmtAmount($rvKpi['total_rv']??0)?></div>
      <div class="rec-lbl">Rollover Amount</div>
    </div>
  </div>

  <!-- Rollover by Officer -->
  <?php if(!empty($rvByOfficer)): ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-head">
      <h3>🔄 Rollover by Field Officer</h3>
      <span class="badge"><?=count($rvByOfficer)?> officers</span>
    </div>
    <div class="card-body" style="padding:8px 16px">
      <?php
        $maxRvOfficer = max(1, max(array_column($rvByOfficer,'total_rv')));
        foreach($rvByOfficer as $ro):
          $rpct = round(($ro['total_rv']/$maxRvOfficer)*100);
      ?>
      <div class="officer-row">
        <div class="officer-avatar"><?=strtoupper(substr($ro['officer'],0,1))?></div>
        <div class="officer-info">
          <div class="officer-name"><?=htmlspecialchars($ro['officer'])?></div>
          <div class="officer-meta"><?=$ro['rv_count']?> entries · <?=$ro['rv_growers']?> growers</div>
          <div class="bar-track" style="margin-top:4px"><div class="bar-fill" style="width:<?=$rpct?>%;background:var(--purple)"></div></div>
        </div>
        <div class="officer-val" style="color:var(--purple)"><?=fmtAmount($ro['total_rv'])?></div>
      </div>
      <?php endforeach?>
    </div>
  </div>
  <?php endif?>

  <div class="two-col" style="margin-bottom:24px">
    <!-- WC Weekly Trend -->
    <div class="card">
      <div class="card-head">
        <h3>💼 Weekly WC Trend</h3>
        <span class="badge">Last 12 weeks</span>
      </div>
      <div class="chart-wrap">
        <canvas id="wcTrendChart"></canvas>
      </div>
    </div>

    <!-- WC by Officer -->
    <div class="card">
      <div class="card-head">
        <h3>👮 WC by Field Officer</h3>
        <span class="badge"><?=count($wcByOfficer)?> officers</span>
      </div>
      <div class="card-body" style="padding:8px 16px">
        <?php if(empty($wcByOfficer)): ?>
        <div class="empty">No working capital recorded yet</div>
        <?php else: foreach($wcByOfficer as $wo):
          $wpct = round(($wo['total_wc']/$maxWcOfficerVal)*100);
        ?>
        <div class="officer-row">
          <div class="officer-avatar"><?=strtoupper(substr($wo['officer'],0,1))?></div>
          <div class="officer-info">
            <div class="officer-name"><?=htmlspecialchars($wo['officer'])?></div>
            <div class="officer-meta"><?=$wo['wc_count']?> entries · <?=$wo['wc_growers']?> growers</div>
            <div class="bar-track" style="margin-top:4px"><div class="bar-fill" style="width:<?=$wpct?>%;background:var(--blue)"></div></div>
          </div>
          <div class="officer-val" style="color:var(--blue)"><?=fmtAmount($wo['total_wc'])?></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>
  </div>

  <!-- Top WC Growers + Recent WC -->
  <div class="two-col" style="margin-bottom:24px">
    <div class="card">
      <div class="card-head">
        <h3>🌾 Top Growers by WC</h3>
        <span class="badge">By amount</span>
      </div>
      <div class="card-body">
        <?php if(empty($wcTopGrowers)): ?>
        <div class="empty">No working capital recorded yet</div>
        <?php else:
          $maxWcG = max(1, max(array_column($wcTopGrowers,'total_wc')));
          foreach($wcTopGrowers as $i=>$wg):
            $wpct = round(($wg['total_wc']/$maxWcG)*100);
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span class="name">
              <?php if($i===0):?>🥇 <?php elseif($i===1):?>🥈 <?php elseif($i===2):?>🥉 <?php endif?>
              <?=htmlspecialchars($wg['gname'].' '.$wg['gsurname'])?> <span style="color:var(--muted);font-size:9px">#<?=$wg['grower_num']?></span>
            </span>
            <span class="val"><?=$wg['wc_count']?> entries · <?=fmtAmount($wg['total_wc'])?></span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=$wpct?>%;background:var(--blue)"></div></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <h3>💼 Recent WC Entries</h3>
        <span class="badge"><?=count($recentWC)?> shown</span>
      </div>
      <?php if(empty($recentWC)): ?>
      <div style="padding:24px;text-align:center;color:var(--muted)">No working capital recorded yet</div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="data-table">
        <thead><tr>
          <th>Date</th><th>Grower</th><th>Officer</th><th>Receipt</th><th style="text-align:right">Amount</th>
        </tr></thead>
        <tbody>
        <?php foreach($recentWC as $wc): ?>
        <tr>
          <td style="color:var(--muted);white-space:nowrap"><?=$wc['wc_date']?> <?=$wc['wc_time']?></td>
          <td><b><?=htmlspecialchars($wc['gname'].' '.$wc['gsurname'])?></b> <span style="color:var(--muted);font-size:9px">#<?=$wc['grower_num']?></span></td>
          <td><?=htmlspecialchars($wc['officer'])?></td>
          <td style="color:var(--muted);font-size:9px"><?=htmlspecialchars($wc['receipt_number']??'—')?></td>
          <td style="text-align:right;color:var(--blue);font-weight:700"><?=fmtAmount($wc['amount'])?></td>
        </tr>
        <?php endforeach?>
        </tbody>
      </table>
      </div>
      <?php endif?>
    </div>
  </div>

  <!-- ── Products + Officers ── -->
  <div class="two-col">
    <div class="card">
      <div class="card-head">
        <h3>🌱 Loans by Product</h3>
        <span class="badge"><?=count($byProduct)?> products</span>
      </div>
      <div class="card-body">
        <?php if(empty($byProduct)): ?>
        <div class="empty">No product data</div>
        <?php else: foreach($byProduct as $p):
          $pct = round(($p['total_cost']/$maxProductVal)*100);
          $col = 'var(--green)';
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span class="name"><?=htmlspecialchars($p['product'])?> <span style="color:var(--muted);font-size:9px">(<?=htmlspecialchars($p['type'])?>)</span></span>
            <span class="val"><?=number_format($p['qty'])?> <?=htmlspecialchars($p['units'])?> · <?=fmtAmount($p['unit_price'])?>/unit · <?=fmtAmount($p['total_cost'])?></span>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%;background:<?=$col?>"></div></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <h3>👮 Loans by Field Officer</h3>
        <span class="badge"><?=count($byOfficer)?> officers</span>
      </div>
      <div class="card-body" style="padding:8px 16px">
        <?php if(empty($byOfficer)): ?>
        <div class="empty">No officer data</div>
        <?php else: foreach($byOfficer as $o):
          $initials = strtoupper(substr($o['officer'],0,1));
        ?>
        <div class="officer-row">
          <div class="officer-avatar"><?=$initials?></div>
          <div class="officer-info">
            <div class="officer-name"><?=htmlspecialchars($o['officer'])?>
              <?php if($o['unverified']>0): ?><span class="pill warn"><?=$o['unverified']?> unverified</span><?php endif?>
              <?php if($o['surrogate']>0): ?><span class="pill info"><?=$o['surrogate']?> surrogate</span><?php endif?>
            </div>
            <div class="officer-meta"><?=number_format($o['cnt'])?> loans</div>
          </div>
          <div class="officer-val"><?=fmtAmount($o['total_value'],0)?></div>
        </div>
        <?php endforeach; endif?>
      </div>
    </div>
  </div>

  <!-- ── Grouped by Officer (conditional) ── -->
  <?php if(isset($_GET['group_officer'])): ?>
  <div class="section-title" style="margin-top:24px">Loans Grouped by Field Officer</div>
  <?php
    // Build officer groups from byOfficer + their growers
    $officerGroups = [];
    foreach($byOfficer as $o) $officerGroups[$o['officer']] = $o;
  ?>
  <div style="display:grid;gap:16px;margin-bottom:24px">
    <?php foreach($officerGroups as $oName => $oData): ?>
    <div class="card">
      <div class="card-head" style="background:var(--surface2)">
        <h3 style="display:flex;align-items:center;gap:8px">
          <span style="width:28px;height:28px;border-radius:50%;background:var(--green-dim);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--green);font-family:'Syne',sans-serif"><?=strtoupper(substr($oName,0,1))?></span>
          <?=htmlspecialchars($oName)?>
        </h3>
        <div style="display:flex;gap:12px;font-size:10px">
          <span style="color:var(--green)"><?=fmtAmount($oData['total_value'])?></span>
          <span style="color:var(--muted)"><?=number_format($oData['cnt'])?> loans</span>
          <?php if($oData['unverified']>0): ?><span style="color:var(--amber)"><?=$oData['unverified']?> unverified</span><?php endif?>
          <?php if($oData['surrogate']>0): ?><span style="color:var(--blue)"><?=$oData['surrogate']?> surrogate</span><?php endif?>
        </div>
      </div>
      <?php
        // Fetch this officer's loans grouped by product
        $oid = null;
        foreach($allOfficers as $ao){ if($ao['name']===$oName){ $oid=$ao['userid']; break; } }
        if($oid):
          $oLoans = [];
          $oQ = $conn->query("
            SELECT p.name AS product, p.units,
                   COUNT(*) AS cnt, SUM(l.quantity) AS qty,
                   COALESCE(pr.amount,0) AS unit_price,
                   COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total
            FROM loans l
            JOIN products p ON p.id=l.productid
            LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.productid AND pr.seasonid=l.seasonid
            WHERE l.userid=$oid AND l.seasonid=$seasonId
            GROUP BY l.productid, p.name, p.units, pr.amount
            ORDER BY total DESC
          ");
          if($oQ){ while($oRow=$oQ->fetch_assoc()) $oLoans[]=$oRow; $oQ->free(); }
        endif;
      ?>
      <?php if(!empty($oLoans)): ?>
      <table class="data-table">
        <thead><tr><th>Product</th><th>Loans</th><th>Qty</th><th>Unit Price</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php foreach($oLoans as $ol): ?>
        <tr>
          <td><?=htmlspecialchars($ol['product'])?></td>
          <td><?=$ol['cnt']?></td>
          <td><?=number_format($ol['qty'])?> <?=htmlspecialchars($ol['units'])?></td>
          <td style="color:var(--muted)"><?=fmtAmount($ol['unit_price'])?></td>
          <td style="color:var(--green)"><?=fmtAmount($ol['total'])?></td>
        </tr>
        <?php endforeach?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty">No loans found</div>
      <?php endif?>
    </div>
    <?php endforeach?>
  </div>
  <?php endif?>

  <!-- ── Hectares by Officer ── -->
  <?php if(!empty($haBySchemOfficer)): ?>
  <div class="card" style="margin-bottom:24px">
    <div class="card-head">
      <h3>📐 Hectares by Field Officer</h3>
      <span class="badge"><?=number_format($totalHectares,1)?> ha total</span>
    </div>
    <div class="card-body" style="padding:8px 16px">
      <?php foreach($haBySchemOfficer as $ho):
        $hpct = $maxHaOfficer>0 ? round(($ho['total_ha']/$maxHaOfficer)*100) : 0;
      ?>
      <div class="officer-row">
        <div class="officer-avatar"><?=strtoupper(substr($ho['officer'],0,1))?></div>
        <div class="officer-info">
          <div class="officer-name"><?=htmlspecialchars($ho['officer'])?></div>
          <div class="officer-meta"><?=number_format($ho['growers'])?> growers</div>
          <div class="bar-track" style="margin-top:4px">
            <div class="bar-fill" style="width:<?=$hpct?>%;background:var(--blue)"></div>
          </div>
        </div>
        <div class="officer-val" style="color:var(--blue)"><?=number_format($ho['total_ha'],1)?> ha</div>
      </div>
      <?php endforeach?>
    </div>
  </div>
  <?php endif?>

  <!-- ── Top Growers ── -->
  <div class="section-title" style="margin-top:24px">Top Growers by Loan Value</div>
  <div class="card" style="margin-bottom:24px">
    <div class="card-body">
      <?php if(empty($topGrowers)): ?>
      <div class="empty">No grower data</div>
      <?php else: foreach($topGrowers as $i=>$g):
        $pct = round(($g['total_value']/$maxGrowerVal)*100);
        $col = $i===0?'var(--amber)':'var(--green)';
      ?>
      <div class="bar-row">
        <div class="bar-label">
          <span class="name">
            <?php if($i===0): ?>🥇 <?php elseif($i===1): ?>🥈 <?php elseif($i===2): ?>🥉 <?php endif?>
            <?=htmlspecialchars($g['gname'].' '.$g['gsurname'])?> <span style="color:var(--muted);font-size:9px">#<?=$g['grower_num']?></span>
          </span>
          <span class="val"><?=$g['loan_count']?> loans · <?=fmtAmount($g['total_value'])?></span>
        </div>
        <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%;background:<?=$col?>"></div></div>
      </div>
      <?php endforeach; endif?>
    </div>
  </div>

  <!-- ── Current Prices + Unverified Queue ── -->
  <div class="two-col">
    <div class="card">
      <div class="card-head">
        <h3>💲 Current Product Prices</h3>
        <span class="badge">Season <?=$seasonId?></span>
      </div>
      <?php if(empty($prices)): ?>
      <div class="empty">No prices set for this season</div>
      <?php else: ?>
      <div class="price-grid">
        <?php foreach($prices as $pr): ?>
        <div class="price-chip">
          <div class="pc-name"><?=htmlspecialchars($pr['product'])?></div>
          <div class="pc-amount"><?=fmtAmount($pr['amount'])?></div>
          <div class="pc-unit">per <?=htmlspecialchars($pr['units'])?>
            <?php if($pr['splitid']): ?> · split #<?=$pr['splitid']?><?php endif?>
          </div>
        </div>
        <?php endforeach?>
      </div>
      <?php endif?>
    </div>

    <div class="card">
      <div class="card-head">
        <h3>🚨 Unverified Queue</h3>
        <span class="badge" style="color:var(--amber)"><?=$kpi['unverified']?> pending</span>
      </div>
      <?php if(empty($unverifiedLoans)): ?>
      <div class="empty" style="color:var(--green)">✅ All loans verified</div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr>
            <th>Grower</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Value</th><th>Officer</th><th>Days Pending</th>
          </tr></thead>
          <tbody>
          <?php foreach($unverifiedLoans as $u): ?>
          <tr>
            <td><b><?=htmlspecialchars($u['gname'].' '.$u['gsurname'])?></b><br><span style="color:var(--muted)">#<?=$u['grower_num']?></span></td>
            <td><?=htmlspecialchars($u['product'])?></td>
            <td><?=number_format($u['quantity'])?> <?=htmlspecialchars($u['units'])?></td>
            <td style="color:var(--muted)"><?=fmtAmount($u['unit_price'])?></td>
            <td style="color:var(--green)"><?=fmtAmount($u['computed_value'])?></td>
            <td><?=htmlspecialchars($u['officer'])?></td>
            <td class="<?=$u['days_pending']>3?'overdue':($u['days_pending']>1?'pending':'')?>">
              <?=$u['days_pending']?>d <?=$u['days_pending']>3?'⚠':''?>
            </td>
          </tr>
          <?php endforeach?>
          </tbody>
        </table>
      </div>
      <?php endif?>
    </div>
  </div>

</div>

<script>
// ── Weekly trend chart ────────────────────────────────────────────────────────
const trendData = <?=json_encode(array_values($weeklyTrend))?>;
const labels = trendData.map(w => {
  const d = new Date(w.week_start);
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'});
});

new Chart(document.getElementById('trendChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      {
        label: 'Loans',
        data: trendData.map(w => w.cnt),
        backgroundColor: 'rgba(61,220,104,.25)',
        borderColor: '#3ddc68',
        borderWidth: 1,
        borderRadius: 3,
        yAxisID: 'y'
      },
      {
        label: 'Value ($)',
        data: trendData.map(w => parseFloat(w.val)),
        type: 'line',
        borderColor: '#4a9eff',
        backgroundColor: 'rgba(74,158,255,.08)',
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: '#4a9eff',
        tension: 0.4,
        fill: true,
        yAxisID: 'y2'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {mode:'index',intersect:false},
    plugins:{
      legend:{labels:{color:'#4a6b4a',font:{family:'Space Mono',size:9},boxWidth:10}},
      tooltip:{
        backgroundColor:'#111a11',
        borderColor:'#1f2e1f',
        borderWidth:1,
        titleColor:'#c8e6c9',
        bodyColor:'#4a6b4a',
        titleFont:{family:'Space Mono',size:10},
        bodyFont:{family:'Space Mono',size:9}
      }
    },
    scales:{
      x:{ticks:{color:'#4a6b4a',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}},
      y:{ticks:{color:'#3ddc68',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'},title:{display:true,text:'Loans',color:'#4a6b4a',font:{size:9}}},
      y2:{position:'right',ticks:{color:'#4a9eff',font:{family:'Space Mono',size:8},callback:v=>'$'+v.toLocaleString()},grid:{display:false},title:{display:true,text:'Value',color:'#4a6b4a',font:{size:9}}}
    }
  }
});

// ── Payment trend chart ───────────────────────────────────────────────────────
const payData = <?=json_encode(array_values($paymentTrend))?>;
const payLabels = payData.map(w => {
  const d = new Date(w.week_start);
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'});
});

new Chart(document.getElementById('paymentTrendChart'), {
  type: 'bar',
  data: {
    labels: payLabels,
    datasets: [
      {
        label: 'Payments',
        data: payData.map(w => w.cnt),
        backgroundColor: 'rgba(74,158,255,.25)',
        borderColor: '#4a9eff',
        borderWidth: 1,
        borderRadius: 3,
        yAxisID: 'y'
      },
      {
        label: 'Amount ($)',
        data: payData.map(w => parseFloat(w.paid)),
        type: 'line',
        borderColor: '#3ddc68',
        backgroundColor: 'rgba(61,220,104,.08)',
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: '#3ddc68',
        tension: 0.4,
        fill: true,
        yAxisID: 'y2'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {mode:'index',intersect:false},
    plugins:{
      legend:{labels:{color:'#4a6b4a',font:{family:'Space Mono',size:9},boxWidth:10}},
      tooltip:{backgroundColor:'#111a11',borderColor:'#1f2e1f',borderWidth:1,titleColor:'#c8e6c9',bodyColor:'#4a6b4a',titleFont:{family:'Space Mono',size:10},bodyFont:{family:'Space Mono',size:9}}
    },
    scales:{
      x:{ticks:{color:'#4a6b4a',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}},
      y:{ticks:{color:'#4a9eff',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'},title:{display:true,text:'Payments',color:'#4a6b4a',font:{size:9}}},
      y2:{position:'right',ticks:{color:'#3ddc68',font:{family:'Space Mono',size:8},callback:v=>'$'+v.toLocaleString()},grid:{display:false},title:{display:true,text:'Amount',color:'#4a6b4a',font:{size:9}}}
    }
  }
});


// ── Working Capital trend chart ───────────────────────────────────────────────
const wcData = <?=json_encode(array_values($wcTrend))?>;
const wcLabels = wcData.map(w => {
  const d = new Date(w.week_start);
  return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short'});
});
if(document.getElementById('wcTrendChart') && wcData.length) {
  new Chart(document.getElementById('wcTrendChart'), {
    type: 'bar',
    data: {
      labels: wcLabels,
      datasets: [
        {
          label: 'Entries',
          data: wcData.map(w => w.cnt),
          backgroundColor: 'rgba(74,158,255,.25)',
          borderColor: '#4a9eff',
          borderWidth: 1,
          borderRadius: 3,
          yAxisID: 'y'
        },
        {
          label: 'Amount ($)',
          data: wcData.map(w => parseFloat(w.val)),
          type: 'line',
          borderColor: '#b060ff',
          backgroundColor: 'rgba(176,96,255,.08)',
          borderWidth: 2,
          pointRadius: 3,
          pointBackgroundColor: '#b060ff',
          tension: 0.4,
          fill: true,
          yAxisID: 'y2'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {mode:'index',intersect:false},
      plugins:{
        legend:{labels:{color:'#4a6b4a',font:{family:'Space Mono',size:9},boxWidth:10}},
        tooltip:{backgroundColor:'#111a11',borderColor:'#1f2e1f',borderWidth:1,
          titleColor:'#c8e6c9',bodyColor:'#4a6b4a',
          titleFont:{family:'Space Mono',size:10},bodyFont:{family:'Space Mono',size:9}}
      },
      scales:{
        x:{ticks:{color:'#4a6b4a',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}},
        y:{ticks:{color:'#4a9eff',font:{family:'Space Mono',size:8}},grid:{color:'#1a2a1a'}},
        y2:{position:'right',ticks:{color:'#b060ff',font:{family:'Space Mono',size:8},
          callback:v=>'$'+v.toLocaleString()},grid:{display:false}}
      }
    }
  });
}

</script>
<?php $conn->close(); ?>
</body>
</html>
