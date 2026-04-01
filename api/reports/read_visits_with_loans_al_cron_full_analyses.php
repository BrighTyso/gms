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


error_log("=== Recovery Cron Started: " . date('Y-m-d H:i:s') . " ===");

// ── 1. Active season ──────────────────────────────────────────
$seasonid = 0;
$result3  = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if ($result3 && $result3->num_rows > 0) {
    $seasonid = (int)$result3->fetch_assoc()["id"];
}
if ($seasonid === 0) {
    error_log("Recovery Cron: No active season found.");
    echo json_encode(["error" => "No active season found"]);
    exit;
}

error_log("Recovery Cron: Running for season $seasonid");

// ── 2. Main recovery query ────────────────────────────────────
$sql = "
SELECT * FROM (

    SELECT
        users.username,
        CONCAT(users.name, ' ', users.surname)             AS field_officer,
        growers.grower_num,
        CONCAT(growers.name, ' ', growers.surname)         AS grower_name,
        growers.area,
        growers.province,

        visits.description                                 AS last_visit_notes,
        visits.created_at                                  AS last_visit_date,
        DATEDIFF(CURDATE(), visits.created_at)             AS days_since_visit,

        COUNT(DISTINCT loans.id)                           AS total_loans,
        COALESCE(SUM(loans.product_total_cost), 0)         AS total_loan_value,

        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN loans.product_total_cost ELSE 0 END) AS pending_loan_value,

        SUM(CASE WHEN loans.verified = 1
                 AND loans.processed = 1
                 THEN loans.product_total_cost ELSE 0 END) AS processed_loan_value,

        COALESCE(SUM(loans.hectares), 0)                   AS total_hectares,

        SUM(CASE WHEN loans.verified = 1
                 AND loans.processed = 1
                 THEN 1 ELSE 0 END)                        AS loans_fully_processed,

        SUM(CASE WHEN loans.verified = 1
                 AND loans.processed = 0
                 THEN 1 ELSE 0 END)                        AS loans_awaiting_processing,

        SUM(CASE WHEN loans.verified = 0
                 AND loans.processed = 0
                 THEN 1 ELSE 0 END)                        AS loans_pending_verification,

        COUNT(DISTINCT wc.id)                              AS total_wc_transactions,
        COALESCE(SUM(DISTINCT wc.amount), 0)               AS total_working_capital,
        MAX(wc.created_at)                                 AS last_wc_date,

        COUNT(DISTINCT ca.id)                              AS total_charge_entries,
        COALESCE(SUM(DISTINCT ca.value), 0)                AS total_interest,
        MAX(ca.created_at)                                 AS last_charge_date,

        COUNT(DISTINCT ro.id)                              AS total_rollovers,
        COALESCE(SUM(DISTINCT ro.amount), 0)               AS total_rollover_amount,
        MAX(ro.rollover_seasonid)                          AS last_rollover_season,

        COALESCE(SUM(loans.product_total_cost), 0)
        + COALESCE(SUM(DISTINCT ro.amount), 0)             AS total_loan_plus_rollover,

        COALESCE(SUM(loans.product_total_cost), 0)
        + COALESCE(SUM(DISTINCT ro.amount), 0)
        + COALESCE(SUM(DISTINCT ca.value), 0)              AS total_owed,

        COALESCE(SUM(loans.product_total_cost), 0)
        + COALESCE(SUM(DISTINCT ro.amount), 0)
        + COALESCE(SUM(DISTINCT wc.amount), 0)
        + COALESCE(SUM(DISTINCT ca.value), 0)              AS total_exposure,

        CASE
            WHEN (COALESCE(SUM(loans.product_total_cost), 0)
                + COALESCE(SUM(DISTINCT ro.amount), 0)) > 0
            THEN ROUND(
                COALESCE(SUM(DISTINCT ca.value), 0)
                / (COALESCE(SUM(loans.product_total_cost), 0)
                +  COALESCE(SUM(DISTINCT ro.amount), 0)) * 100, 2)
            ELSE 0
        END                                                AS interest_rate_pct,

        CASE
            WHEN (COALESCE(SUM(loans.product_total_cost), 0)
                + COALESCE(SUM(DISTINCT ro.amount), 0)
                + COALESCE(SUM(DISTINCT wc.amount), 0)
                + COALESCE(SUM(DISTINCT ca.value),  0)) > 0
            THEN ROUND(
                COALESCE(SUM(DISTINCT ro.amount), 0)
                / (COALESCE(SUM(loans.product_total_cost), 0)
                +  COALESCE(SUM(DISTINCT ro.amount), 0)
                +  COALESCE(SUM(DISTINCT wc.amount), 0)
                +  COALESCE(SUM(DISTINCT ca.value),  0)) * 100, 2)
            ELSE 0
        END                                                AS rollover_pct_of_exposure,

        CASE
            WHEN SUM(CASE WHEN loans.verified = 0
                          AND loans.processed = 0
                          THEN 1 ELSE 0 END) > 0
                 THEN 'Has Pending Loans'
            WHEN SUM(CASE WHEN loans.verified = 1
                          AND loans.processed = 0
                          THEN 1 ELSE 0 END) > 0
                 THEN 'Awaiting Processing'
            WHEN SUM(CASE WHEN loans.verified = 1
                          AND loans.processed = 1
                          THEN 1 ELSE 0 END) > 0
                 THEN 'Fully Processed'
            ELSE 'Unknown'
        END                                                AS overall_loan_status,

        (
            SUM(CASE WHEN loans.verified = 0
                     AND loans.processed = 0
                     THEN 1 ELSE 0 END) * 30
            + COALESCE(DATEDIFF(CURDATE(), visits.created_at), 60) * 2
            + CASE WHEN COALESCE(SUM(DISTINCT ro.amount), 0) > 0
                   THEN 40 ELSE 0 END
            + CASE
                WHEN COALESCE(SUM(loans.product_total_cost), 0)
                   + COALESCE(SUM(DISTINCT ro.amount), 0)
                   + COALESCE(SUM(DISTINCT ca.value),  0) > 5000 THEN 50
                WHEN COALESCE(SUM(loans.product_total_cost), 0)
                   + COALESCE(SUM(DISTINCT ro.amount), 0)
                   + COALESCE(SUM(DISTINCT ca.value),  0) > 1000 THEN 20
                ELSE 0
              END
        )                                                  AS recovery_risk_score

    FROM loans
    JOIN growers  ON growers.id  = loans.growerid
    JOIN users    ON users.id    = loans.userid
    JOIN products ON products.id = loans.productid

    LEFT JOIN visits ON visits.growerid = loans.growerid
        AND visits.id = (
            SELECT v2.id FROM visits v2
            WHERE v2.growerid = loans.growerid
              AND v2.seasonid = $seasonid
            ORDER BY v2.created_at DESC
            LIMIT 1
        )

    LEFT JOIN working_capital wc
        ON  wc.growerid = loans.growerid
        AND wc.seasonid = $seasonid

    LEFT JOIN charges_amount ca
        ON  ca.userid   = loans.userid
        AND ca.seasonid = $seasonid

    LEFT JOIN rollover ro
        ON  ro.growerid = loans.growerid
        AND ro.seasonid = $seasonid

    WHERE loans.seasonid = $seasonid

    GROUP BY
        growers.grower_num,
        growers.id,
        users.username,
        users.name,
        users.surname,
        growers.name,
        growers.surname,
        growers.area,
        growers.province,
        visits.description,
        visits.created_at

) AS recovery_report

ORDER BY
    recovery_risk_score         DESC,
    loans_pending_verification  DESC,
    days_since_visit            DESC,
    total_exposure              DESC
";

// ── 3. Execute query ──────────────────────────────────────────
$result = $conn->query($sql);

if (!$result) {
    error_log("Recovery Cron: Query failed — " . $conn->error);
    echo json_encode(["error" => $conn->error]);
    exit;
}

// ── 4. Process results ────────────────────────────────────────
$data            = [];
$totalExposure   = 0;
$totalWC         = 0;
$totalLoanValue  = 0;
$totalInterest   = 0;
$totalOwed       = 0;
$totalRollover   = 0;
$pendingCount    = 0;
$rolloverGrowers = [];
$criticalList    = [];

while ($row = $result->fetch_assoc()) {
    $totalExposure  += (float)$row["total_exposure"];
    $totalWC        += (float)$row["total_working_capital"];
    $totalLoanValue += (float)$row["total_loan_value"];
    $totalInterest  += (float)$row["total_interest"];
    $totalOwed      += (float)$row["total_owed"];
    $totalRollover  += (float)$row["total_rollover_amount"];

    if ((int)$row["loans_pending_verification"] > 0) {
        $pendingCount++;
    }

    // Track rollover growers
    if ((float)$row["total_rollover_amount"] > 0) {
        $rolloverGrowers[] = [
            "grower_num"      => $row["grower_num"],
            "grower_name"     => $row["grower_name"],
            "username"        => $row["username"],
            "rollover_amount" => $row["total_rollover_amount"],
            "rollover_pct"    => $row["rollover_pct_of_exposure"],
            "total_owed"      => $row["total_owed"],
            "last_season"     => $row["last_rollover_season"],
        ];
    }

    // Track critical growers
    if (
        (int)$row["loans_pending_verification"] > 0 &&
        ((int)$row["days_since_visit"] > 14 || $row["days_since_visit"] === null)
    ) {
        $criticalList[] = [
            "grower_num"     => $row["grower_num"],
            "grower_name"    => $row["grower_name"],
            "field_officer"  => $row["field_officer"],
            "username"       => $row["username"],
            "total_owed"     => $row["total_owed"],
            "total_rollover" => $row["total_rollover_amount"],
            "total_interest" => $row["total_interest"],
            "total_exposure" => $row["total_exposure"],
            "risk_score"     => $row["recovery_risk_score"],
            "days_unvisited" => $row["days_since_visit"] ?? "Never visited",
        ];
    }

    $data[] = $row;
}

// ── 5. Guard — no data ────────────────────────────────────────
if (empty($data)) {
    error_log("Recovery Cron: No data found for season $seasonid");
    echo json_encode(["error" => "No data found for season $seasonid"]);
    exit;
}

error_log("Recovery Cron: Found " . count($data) . " grower records");

// ── 6. Build Gemini prompt ────────────────────────────────────
$jsonData  = json_encode($data,            JSON_PRETTY_PRINT);
$critical  = json_encode($criticalList,    JSON_PRETTY_PRINT);
$rollovers = json_encode($rolloverGrowers, JSON_PRETTY_PRINT);

$prompt = "You are a senior tobacco recovery advisor for an African agricultural company.

Analyze this grower loan, working capital, interest, rollover and visit data for Season $seasonid:

FULL GROWER DATA:
$jsonData

CRITICAL CASES (pending loans AND not visited in 14+ days):
$critical

GROWERS WITH ROLLOVER DEBT FROM PREVIOUS SEASONS:
$rollovers

PORTFOLIO SUMMARY:
- Total growers             : " . count($data) . "
- Total loan value          : $" . number_format($totalLoanValue, 2) . "
- Total rollover carried    : $" . number_format($totalRollover,  2) . "
- Total interest charged    : $" . number_format($totalInterest,  2) . "
- Total owed (all combined) : $" . number_format($totalOwed,      2) . "
- Total working capital     : $" . number_format($totalWC,        2) . "
- Total exposure            : $" . number_format($totalExposure,  2) . "
- Growers pending verify    : $pendingCount
- Growers with rollovers    : " . count($rolloverGrowers) . "
- Critical cases            : " . count($criticalList) . "

Produce a TOBACCO RECOVERY REPORT for management covering:

1. EXECUTIVE SUMMARY (5 sentences max)
   - Total portfolio exposure including rollovers and interest
   - Percentage pending vs processed
   - Top 3 urgent actions this week

2. ROLLOVER DEBT ANALYSIS
   - List all growers carrying rollover debt
   - Flag where rollover_pct_of_exposure exceeds 50%
   - Recommend whether to continue lending or suspend inputs

3. INTEREST AND DEBT ANALYSIS
   - Growers with highest interest burden
   - Flag where interest_rate_pct exceeds 20%
   - Total interest at risk if unrecovered

4. GROWER RISK RANKING
   - Rank ALL growers: CRITICAL / HIGH / MEDIUM / LOW
   - Based on recovery_risk_score, total_owed, days_since_visit
   - Flag growers with working capital but unverified loans

5. FIELD OFFICER ACCOUNTABILITY
   - Officers with most unverified loans
   - Officers not visiting growers in 14+ days
   - Name specific officers for management review

6. WORKING CAPITAL RISK
   - Growers receiving working capital but loans still unverified
   - Flag total_exposure above safe threshold
   - Recommend holds on further working capital for high risk cases

7. 7-DAY RECOVERY ACTION PLAN
   - Top 10 priority grower visits ranked by recovery_risk_score
   - Assigned field officer username for each
   - What to verify on arrival
   - Expected recovery amount per grower if action taken

Format as a professional management report.
Use clear section headings.
Risk levels must be in CAPS: CRITICAL / HIGH / MEDIUM / LOW.
End with a priority action table sorted by recovery_risk_score.
Use simple language suitable for non-technical farm managers.";

// ── 7. Call Gemini ────────────────────────────────────────────
error_log("Recovery Cron: Calling Gemini API...");

$gemini_response = analyzeGrowerData($prompt, $data);

if (empty(trim($gemini_response))) {
    error_log("Recovery Cron: Gemini returned empty response");
    echo json_encode(["error" => "AI analysis failed — empty response"]);
    exit;
}

error_log("Recovery Cron: Gemini response received — " . strlen($gemini_response) . " chars");

// ── 8. Format email body ──────────────────────────────────────
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

// ── Final result ──────────────────────────────────────────────
error_log("GMS Cron: Done — Sent: $sentCount | Failed: $failCount");
echo json_encode([
    "status"        => "success",
    "season"        => $seasonid,
    "emails_sent"   => $sentCount,
    "emails_failed" => $failCount,
]);