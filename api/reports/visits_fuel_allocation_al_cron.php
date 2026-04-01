<?php

// ── PHPMailer — choose whichever matches your install ────────
// If installed via composer:
require_once '/home/coreafri/public_html/vendor/autoload.php';

// If uploaded manually:
// require_once '/home/coreafri/public_html/PHPMailer/PHPMailer-master/src/Exception.php';
// require_once '/home/coreafri/public_html/PHPMailer/PHPMailer-master/src/PHPMailer.php';
// require_once '/home/coreafri/public_html/PHPMailer/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once("conn.php");
require_once("Gemini_al_analyses.php");
require_once("Format_gemini_data.php");


$seasonid=1;
$userid1=1;

$username="";


error_log("=== Fuel & Performance Cron Started: " . date('Y-m-d H:i:s') . " ===");

// ── 1. Active season ──────────────────────────────────────────
$seasonid = 1;
$result3  = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if ($result3 && $result3->num_rows > 0) {
    $seasonid = (int)$result3->fetch_assoc()["id"];
}
if ($seasonid === 0) {
    error_log("Fuel Cron: No active season found.");
    echo json_encode(["error" => "No active season found"]);
    exit;
}

$end      = date("Y-m-d");
$start    = (new DateTime())->modify('-7 days')->format('Y-m-d');
$seasonid = (int)$seasonid;

error_log("Fuel Cron: Season $seasonid | $start to $end");

// ── 2. Daily activity query ───────────────────────────────────
$sql = "
SELECT * FROM (

    SELECT
        users.id                                               AS userid,
        users.username,
        CONCAT(users.name, ' ', users.surname)                 AS field_officer,
        visits.created_at                                      AS visit_date,

        COUNT(DISTINCT visits.id)                              AS total_visits,
        COUNT(DISTINCT visits.growerid)                        AS unique_growers_visited,

        GROUP_CONCAT(DISTINCT growers.grower_num
            ORDER BY growers.grower_num
            SEPARATOR ', ')                                    AS growers_visited,

        GROUP_CONCAT(DISTINCT
            CONCAT(growers.grower_num, ': ', visits.description)
            ORDER BY growers.grower_num
            SEPARATOR ' | ')                                   AS visit_notes,

        COALESCE(SUM(d.distance), 0)                           AS total_distance_km,

        CASE
            WHEN COALESCE(SUM(d.distance), 0) > 0
            THEN ROUND(
                COUNT(DISTINCT visits.growerid)
                / COALESCE(SUM(d.distance), 0) * 100, 2)
            ELSE 0
        END                                                    AS growers_per_100km,

        COUNT(DISTINCT loans.id)                               AS loans_in_visited_growers,
        COALESCE(SUM(loans.product_total_cost), 0)             AS visited_growers_loan_value,

        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN 1 ELSE 0 END)                            AS visited_pending_loans,

        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN loans.product_total_cost
                 ELSE 0 END)                                   AS visited_pending_value,

        COUNT(DISTINCT CASE WHEN loans.id IS NOT NULL
                            THEN visits.growerid END)          AS growers_with_loans_visited,

        -- How many allocated growers were visited that day
        COUNT(DISTINCT CASE WHEN gfo.growerid IS NOT NULL
                            THEN visits.growerid END)          AS allocated_growers_visited,

        CASE
            WHEN COUNT(DISTINCT visits.id) = 0
             AND COALESCE(SUM(d.distance), 0) > 0
            THEN 'SUSPICIOUS'
            WHEN COUNT(DISTINCT visits.id) = 0
             AND COALESCE(SUM(d.distance), 0) = 0
            THEN 'NO ACTIVITY'
            WHEN COUNT(DISTINCT visits.id) > 0
             AND COALESCE(SUM(d.distance), 0) = 0
            THEN 'NO DISTANCE LOGGED'
            ELSE 'NORMAL'
        END                                                    AS activity_flag

    FROM visits
    JOIN users   ON users.id   = visits.userid
    JOIN growers ON growers.id = visits.growerid

    LEFT JOIN distance d
        ON  d.userid     = visits.userid
        AND d.created_at = visits.created_at
        AND d.seasonid   = visits.seasonid

    LEFT JOIN loans
        ON  loans.growerid = visits.growerid
        AND loans.seasonid = $seasonid

    -- Join allocation to check if visited growers are allocated to this officer
    LEFT JOIN grower_field_officer gfo
        ON  gfo.growerid      = visits.growerid
        AND gfo.field_officerid = visits.userid
        AND gfo.seasonid      = $seasonid

    WHERE visits.seasonid   = $seasonid
      AND visits.created_at BETWEEN '$start' AND '$end'

    GROUP BY
        users.id,
        users.username,
        users.name,
        users.surname,
        visits.created_at

) AS daily_activity

ORDER BY
    visit_date        DESC,
    total_visits      DESC,
    total_distance_km DESC
";

$result = $conn->query($sql);

if (!$result) {
    error_log("Fuel Cron: Daily query failed — " . $conn->error);
    echo json_encode(["error" => $conn->error]);
    exit;
}

// ── 3. Process daily data ─────────────────────────────────────
$dailyData      = [];
$officerSummary = [];

while ($row = $result->fetch_assoc()) {
    $username    = $row["username"];
    $dailyData[] = $row;

    if (!isset($officerSummary[$username])) {
        $officerSummary[$username] = [
            "username"                    => $username,
            "field_officer"               => $row["field_officer"],
            "total_visits"                => 0,
            "total_growers"               => 0,
            "total_distance_km"           => 0,
            "days_active"                 => 0,
            "days_no_activity"            => 0,
            "suspicious_days"             => 0,
            "total_loans_in_visits"       => 0,
            "total_visited_loan_value"    => 0,
            "total_pending_loans"         => 0,
            "total_pending_value"         => 0,
            "growers_with_loans_visited"  => 0,
            "allocated_growers_visited"   => 0,
            "daily_breakdown"             => [],
        ];
    }

    $officerSummary[$username]["total_visits"]              += (int)$row["total_visits"];
    $officerSummary[$username]["total_growers"]             += (int)$row["unique_growers_visited"];
    $officerSummary[$username]["total_distance_km"]         += (float)$row["total_distance_km"];
    $officerSummary[$username]["total_loans_in_visits"]     += (int)$row["loans_in_visited_growers"];
    $officerSummary[$username]["total_visited_loan_value"]  += (float)$row["visited_growers_loan_value"];
    $officerSummary[$username]["total_pending_loans"]       += (int)$row["visited_pending_loans"];
    $officerSummary[$username]["total_pending_value"]       += (float)$row["visited_pending_value"];
    $officerSummary[$username]["growers_with_loans_visited"]+= (int)$row["growers_with_loans_visited"];
    $officerSummary[$username]["allocated_growers_visited"] += (int)$row["allocated_growers_visited"];

    if (in_array($row["activity_flag"], ["NORMAL", "NO DISTANCE LOGGED"])) {
        $officerSummary[$username]["days_active"]++;
    }
    if ($row["activity_flag"] === "NO ACTIVITY") {
        $officerSummary[$username]["days_no_activity"]++;
    }
    if ($row["activity_flag"] === "SUSPICIOUS") {
        $officerSummary[$username]["suspicious_days"]++;
    }

    $officerSummary[$username]["daily_breakdown"][] = [
        "date"                    => $row["visit_date"],
        "visits"                  => $row["total_visits"],
        "distance_km"             => $row["total_distance_km"],
        "growers_visited"         => $row["growers_visited"],
        "allocated_visited"       => $row["allocated_growers_visited"],
        "pending_loans"           => $row["visited_pending_loans"],
        "pending_value"           => $row["visited_pending_value"],
        "flag"                    => $row["activity_flag"],
    ];
}

// ── 4. Get full allocation per officer ────────────────────────
$allocationSql = "
    SELECT
        fo.username                                        AS officer_username,
        CONCAT(fo.name, ' ', fo.surname)                   AS field_officer,
        COUNT(DISTINCT gfo.growerid)                       AS total_allocated_growers,

        -- How many allocated growers have loans
        COUNT(DISTINCT CASE WHEN loans.id IS NOT NULL
                            THEN gfo.growerid END)         AS allocated_with_loans,

        SUM(CASE WHEN loans.id IS NOT NULL
                 THEN loans.product_total_cost
                 ELSE 0 END)                               AS allocated_total_loan_value,

        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN loans.product_total_cost
                 ELSE 0 END)                               AS allocated_pending_value,

        -- Growers allocated but NOT visited this week
        COUNT(DISTINCT CASE WHEN visits_week.id IS NULL
                            THEN gfo.growerid END)         AS allocated_not_visited,

        -- Growers allocated AND visited this week
        COUNT(DISTINCT CASE WHEN visits_week.id IS NOT NULL
                            THEN gfo.growerid END)         AS allocated_and_visited,

        -- Visit coverage %
        CASE
            WHEN COUNT(DISTINCT gfo.growerid) > 0
            THEN ROUND(
                COUNT(DISTINCT CASE WHEN visits_week.id IS NOT NULL
                                    THEN gfo.growerid END)
                / COUNT(DISTINCT gfo.growerid) * 100, 2)
            ELSE 0
        END                                                AS visit_coverage_pct,

        GROUP_CONCAT(DISTINCT
            CONCAT(
                growers.grower_num, ' (',
                growers.name, ' ', growers.surname,
                CASE WHEN visits_week.id IS NULL
                     THEN ' — NOT VISITED'
                     ELSE ' — visited'
                END, ')'
            )
            ORDER BY growers.grower_num
            SEPARATOR ' | ')                               AS allocation_summary

    FROM grower_field_officer gfo
    JOIN users   fo      ON fo.id      = gfo.field_officerid
    JOIN growers         ON growers.id = gfo.growerid

    LEFT JOIN loans
        ON  loans.growerid = gfo.growerid
        AND loans.seasonid = $seasonid

    -- Check if visited this week
    LEFT JOIN visits visits_week
        ON  visits_week.growerid  = gfo.growerid
        AND visits_week.userid    = gfo.field_officerid
        AND visits_week.seasonid  = $seasonid
        AND visits_week.created_at BETWEEN '$start' AND '$end'

    WHERE gfo.seasonid = $seasonid

    GROUP BY
        gfo.field_officerid,
        fo.username,
        fo.name,
        fo.surname

    ORDER BY
        allocated_not_visited DESC,
        allocated_pending_value DESC
";

$allocationResult = $conn->query($allocationSql);

$allocationData      = [];
$totalAllocated      = 0;
$totalNotVisited     = 0;
$totalCoverageValue  = 0;

if ($allocationResult && $allocationResult->num_rows > 0) {
    while ($row = $allocationResult->fetch_assoc()) {
        $totalAllocated     += (int)$row["total_allocated_growers"];
        $totalNotVisited    += (int)$row["allocated_not_visited"];
        $totalCoverageValue += (float)$row["allocated_pending_value"];

        // Attach to officer summary
        $username = $row["officer_username"];
        if (isset($officerSummary[$username])) {
            $officerSummary[$username]["allocation"] = $row;
        }

        $allocationData[] = $row;
    }
}

// ── 5. Unvisited allocated growers with loans ─────────────────
$unvisitedSql = "
    SELECT
        fo.username                                        AS officer_username,
        CONCAT(fo.name, ' ', fo.surname)                   AS field_officer,
        growers.grower_num,
        CONCAT(growers.name, ' ', growers.surname)         AS grower_name,
        growers.area,
        growers.province,
        COUNT(DISTINCT loans.id)                           AS total_loans,
        SUM(loans.product_total_cost)                      AS total_loan_value,
        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN loans.product_total_cost
                 ELSE 0 END)                               AS pending_loan_value,
        MAX(v_all.created_at)                              AS last_visit_date,
        DATEDIFF(CURDATE(), MAX(v_all.created_at))         AS days_since_last_visit

    FROM grower_field_officer gfo
    JOIN users   fo      ON fo.id      = gfo.field_officerid
    JOIN growers         ON growers.id = gfo.growerid

    LEFT JOIN loans
        ON  loans.growerid = gfo.growerid
        AND loans.seasonid = $seasonid

    -- Last visit ever by this officer
    LEFT JOIN visits v_all
        ON  v_all.growerid = gfo.growerid
        AND v_all.userid   = gfo.field_officerid
        AND v_all.seasonid = $seasonid

    -- Not visited this week
    LEFT JOIN visits v_week
        ON  v_week.growerid   = gfo.growerid
        AND v_week.userid     = gfo.field_officerid
        AND v_week.seasonid   = $seasonid
        AND v_week.created_at BETWEEN '$start' AND '$end'

    WHERE gfo.seasonid  = $seasonid
      AND v_week.id     IS NULL       -- NOT visited this week
      AND loans.id      IS NOT NULL   -- HAS a loan

    GROUP BY
        gfo.field_officerid,
        gfo.growerid,
        fo.username,
        fo.name,
        fo.surname,
        growers.grower_num,
        growers.name,
        growers.surname,
        growers.area,
        growers.province

    ORDER BY
        pending_loan_value    DESC,
        days_since_last_visit DESC
";

$unvisitedResult  = $conn->query($unvisitedSql);
$unvisitedGrowers = [];
$totalUnvisitedValue = 0;

if ($unvisitedResult && $unvisitedResult->num_rows > 0) {
    while ($row = $unvisitedResult->fetch_assoc()) {
        $totalUnvisitedValue += (float)$row["pending_loan_value"];
        $unvisitedGrowers[]   = $row;
    }
}

error_log("Fuel Cron: Unvisited allocated growers with loans: " . count($unvisitedGrowers));

// ── 6. Fuel calculations ──────────────────────────────────────
$fuelPer100km    = 10;
$fuelPricePerLtr = 1.50;

foreach ($officerSummary as $username => &$officer) {
    $distanceKm   = $officer["total_distance_km"];
    $fuelUsedLtr  = ($distanceKm / 100) * $fuelPer100km;
    $fuelCostUsd  = $fuelUsedLtr * $fuelPricePerLtr;
    $nextWeekFuel = $fuelUsedLtr;

    // Adjust based on coverage and activity
    $coverage = $officer["allocation"]["visit_coverage_pct"] ?? 0;

    if ($officer["days_no_activity"] > 2) {
        $nextWeekFuel = $fuelUsedLtr * 0.70;       // -30% poor activity
    } elseif ($coverage < 50) {
        $nextWeekFuel = $fuelUsedLtr * 0.80;       // -20% low coverage
    } elseif ($officer["total_visits"] > 10 && $coverage > 80) {
        $nextWeekFuel = $fuelUsedLtr * 1.10;       // +10% high performer
    }

    if ($officer["days_no_activity"] > 3) {
        $rating = "POOR";
    } elseif ($officer["suspicious_days"] > 0) {
        $rating = "REVIEW";
    } elseif ($officer["days_no_activity"] > 1 || $coverage < 50) {
        $rating = "AVERAGE";
    } elseif ($officer["total_visits"] >= 5 && $coverage >= 70) {
        $rating = "GOOD";
    } elseif ($officer["total_visits"] >= 8 && $coverage >= 90) {
        $rating = "EXCELLENT";
    } else {
        $rating = "AVERAGE";
    }

    $officer["fuel_used_ltr"]      = round($fuelUsedLtr,  2);
    $officer["fuel_cost_usd"]      = round($fuelCostUsd,  2);
    $officer["next_week_fuel_ltr"] = round($nextWeekFuel, 2);
    $officer["next_week_cost_usd"] = round($nextWeekFuel * $fuelPricePerLtr, 2);
    $officer["performance_rating"] = $rating;
    $officer["visit_coverage_pct"] = $coverage;
    $officer["growers_per_100km"]  = $distanceKm > 0
        ? round(($officer["total_growers"] / $distanceKm) * 100, 2)
        : 0;

    // Attach unvisited allocated growers for this officer
    $officer["unvisited_allocated"] = array_values(array_filter(
        $unvisitedGrowers,
        fn($g) => $g["officer_username"] === $username
    ));
}
unset($officer);

$officerSummary = array_values($officerSummary);

// ── 7. Portfolio totals ───────────────────────────────────────
$totalDistance  = array_sum(array_column($officerSummary, "total_distance_km"));
$totalFuelUsed  = array_sum(array_column($officerSummary, "fuel_used_ltr"));
$totalFuelCost  = array_sum(array_column($officerSummary, "fuel_cost_usd"));
$totalNextFuel  = array_sum(array_column($officerSummary, "next_week_fuel_ltr"));
$totalNextCost  = array_sum(array_column($officerSummary, "next_week_cost_usd"));
$totalVisits    = array_sum(array_column($officerSummary, "total_visits"));
$poorOfficers   = array_filter($officerSummary, fn($o) => $o["performance_rating"] === "POOR");
$reviewOfficers = array_filter($officerSummary, fn($o) => $o["performance_rating"] === "REVIEW");

// ── 8. Build Gemini prompt ────────────────────────────────────
$jsonDaily      = json_encode($dailyData,        JSON_PRETTY_PRINT);
$jsonOfficer    = json_encode($officerSummary,   JSON_PRETTY_PRINT);
$jsonAllocation = json_encode($allocationData,   JSON_PRETTY_PRINT);
$jsonUnvisited  = json_encode($unvisitedGrowers, JSON_PRETTY_PRINT);

$prompt = "You are a senior agricultural operations manager specializing in
tobacco farming field operations and cost control in Zimbabwe.

Analyze field officer activity, allocations, distance, loans and
grower visit data for the week of $start to $end (Season $seasonid):

DAILY ACTIVITY DATA:
$jsonDaily

WEEKLY OFFICER SUMMARY (fuel + allocation + unvisited):
$jsonOfficer

OFFICER GROWER ALLOCATION VS VISIT COVERAGE:
$jsonAllocation

ALLOCATED GROWERS WITH LOANS NOT VISITED THIS WEEK:
$jsonUnvisited

PORTFOLIO TOTALS:
- Total officers              : " . count($officerSummary) . "
- Total allocated growers     : $totalAllocated
- Allocated not visited       : $totalNotVisited
- Unvisited pending loan value: \$" . number_format($totalUnvisitedValue, 2) . "
- Total visits this week      : $totalVisits
- Total distance              : " . number_format($totalDistance, 2) . " km
- Fuel used                   : " . number_format($totalFuelUsed,  2) . " litres
- Fuel cost                   : \$" . number_format($totalFuelCost,  2) . "
- Next week fuel budget       : " . number_format($totalNextFuel,  2) . " litres
- Next week cost              : \$" . number_format($totalNextCost,  2) . "
- Officers rated POOR         : " . count($poorOfficers) . "
- Officers flagged REVIEW     : " . count($reviewOfficers) . "
- Fuel price                  : \$$fuelPricePerLtr/litre
- Consumption rate            : {$fuelPer100km}L/100km

Produce a FIELD OFFICER PERFORMANCE, ALLOCATION AND FUEL REPORT:

1. EXECUTIVE SUMMARY (5 sentences max)
   - Allocation coverage this week
   - Loan value at risk from unvisited allocated growers
   - Top 3 urgent actions

2. ALLOCATION COVERAGE REPORT
   - For EACH officer show:
     Allocated growers | Visited | Not Visited | Coverage % | Loan Value at Risk
   - Flag officers below 50% coverage as CRITICAL
   - Flag officers between 50-70% as HIGH risk
   - Flag officers visiting NON-ALLOCATED growers (wasting fuel)

3. UNVISITED ALLOCATED GROWERS WITH LOANS
   - Full list ranked by pending_loan_value
   - Officer responsible for each
   - Days since last visit
   - Risk level: CRITICAL (14+ days) / HIGH (7-14 days) / MEDIUM (this week)

4. FUEL ALLOCATION NEXT WEEK
   - Based on: coverage %, visit performance, unvisited allocated growers
   - Officers below 50% coverage get REDUCED fuel
   - Officers visiting non-allocated growers get REDUCED fuel
   - Officers above 80% coverage with good loan visits get FULL fuel
   - Table: Officer | Coverage % | Allocated Not Visited | Next Week Fuel | Cost

5. OFFICER PERFORMANCE RANKING
   - Rank: EXCELLENT / GOOD / AVERAGE / POOR
   - Based on: coverage %, visits, distance efficiency, loan growers visited

6. COST CUTTING RECOMMENDATIONS
   - Officers visiting non-allocated growers (wasted km)
   - Officers with low coverage but high distance (inefficient routing)
   - Estimated savings from reallocation or route optimization

7. SUSPICIOUS ACTIVITY
   - Distance logged but no visits
   - Visiting non-allocated growers only
   - Immediate investigation recommended

8. NEXT WEEK PRIORITY LIST
   - Top 10 allocated growers to visit urgently per officer
   - Ranked by pending loan value
   - Fuel approval table for management sign-off

Risk levels in CAPS: CRITICAL / HIGH / MEDIUM / LOW.
End with FUEL APPROVAL TABLE and PRIORITY VISIT LIST.";

// ── 9. Call Gemini ────────────────────────────────────────────
error_log("Fuel Cron: Calling Gemini...");
$gemini_response = analyzeGrowerData($prompt, $officerSummary);

if (empty(trim($gemini_response))) {
    error_log("Fuel Cron: Gemini empty response");
    echo json_encode(["error" => "AI analysis failed"]);
    exit;
}

// ── 10. Format + send ─────────────────────────────────────────
$formattedEmail = formatAgronomicAnalysis($gemini_response);

if (empty(trim($formattedEmail))) {
    error_log("Visits Cron: formatAgronomicAnalysis returned empty for season $seasonid");
    echo json_encode(["error" => "Email formatting failed"]);
    exit;
}





// ── Your cPanel SMTP credentials ─────────────────────────────
$smtpHost     = 'mail.coreafricagrp.com'; // from cPanel Connect Devices
$smtpUser     = 'reports@coreafricagrp.com';
$smtpPass     = 'Bhorabhora@9996';     // ← cPanel email password
$smtpPort     = 465;                       // 465 for SSL / 587 for TLS
$smtpFromName = 'GMS Reports';

// ── Fetch contacts ────────────────────────────────────────────
$contacts = $conn->query("SELECT email FROM operations_contacts WHERE active = 1");

if (!$contacts || $contacts->num_rows === 0) {
    error_log("GMS Cron: No active contacts found");
    echo json_encode(["error" => "No active contacts"]);
    exit;
}

// ── Auto-generate plain text from HTML ───────────────────────
$plainText = trim(preg_replace('/\n{3,}/', "\n\n",
    strip_tags(str_replace(
        ['<br>', '<br/>', '<br />', '</p>', '</tr>', '</li>'],
        "\n",
        $formattedEmail
    ))
));

$sentCount = 0;
$failCount = 0;

while ($row = $contacts->fetch_assoc()) {

    $to = trim($row["email"]);

    // Validate
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("GMS Cron: Invalid email skipped — $to");
        continue;
    }

    $mail = new PHPMailer(true);

    try {
        // ── SMTP setup ────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->Port       = $smtpPort;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;

        // Port 465 = SSL, Port 587 = TLS — match your cPanel setting
        if ($smtpPort === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // ── From / To ─────────────────────────────────────────
        $mail->setFrom($smtpUser, $smtpFromName);
        $mail->addReplyTo($smtpUser, $smtpFromName);
        $mail->addAddress($to);

        // ── Content ───────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = "GMS RISK Analyses, loan and field visit";
        $mail->Body    = $formattedEmail;  // your HTML
        $mail->AltBody = $plainText;       // plain text fallback

        $mail->send();

        $sentCount++;
        error_log("GMS Cron: Email sent → $to");

    } catch (Exception $e) {
        $failCount++;
        error_log("GMS Cron: Email FAILED → $to | " . $mail->ErrorInfo);
    }
}

// ── 11. Final output ──────────────────────────────────────────
error_log("Fuel Cron: Complete — Sent: $sentCount | Failed: $failCount");
error_log("=== Fuel Cron Ended: " . date('Y-m-d H:i:s') . " ===");

echo json_encode([
    "status"                   => "success",
    "season"                   => $seasonid,
    "week"                     => "$start to $end",
    "officers_analyzed"        => count($officerSummary),
    "total_allocated_growers"  => $totalAllocated,
    "allocated_not_visited"    => $totalNotVisited,
    "unvisited_loan_value"     => number_format($totalUnvisitedValue, 2),
    "total_visits"             => $totalVisits,
    "total_distance_km"        => number_format($totalDistance, 2),
    "fuel_used_ltr"            => number_format($totalFuelUsed,  2),
    "fuel_cost_usd"            => number_format($totalFuelCost,  2),
    "next_week_fuel_ltr"       => number_format($totalNextFuel,  2),
    "next_week_cost_usd"       => number_format($totalNextCost,  2),
    "poor_officers"            => count($poorOfficers),
    "emails_sent"              => $sentCount,
    "emails_failed"            => $failCount,
    "sent_to"                  => $sentList,
    "failed_for"               => $failedList,
]);
?>