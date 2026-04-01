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


$sql13 = "Select * from seasons where active=1 limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $seasonid=$row3["id"];

       
   }
 }



// ── Fetch joined data ─────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        users.username,
        CONCAT(users.name, ' ', users.surname)     AS field_officer,
        growers.grower_num,
        CONCAT(growers.name, ' ', growers.surname) AS grower_name,
        growers.area,
        growers.province,
        visits.description                         AS last_visit_notes,
        visits.created_at                          AS last_visit_date,
        products.name                              AS product_name,
        loans.quantity,
        loans.product_amount,
        loans.product_total_cost,
        loans.receipt_number,
        loans.hectares,
        loans.verified,
        loans.processed,
        loans.created_at                           AS loan_date,
        CASE
            WHEN loans.verified = 1 AND loans.processed = 1 THEN 'Fully Processed'
            WHEN loans.verified = 1 AND loans.processed = 0 THEN 'Verified Awaiting Processing'
            WHEN loans.verified = 0 AND loans.processed = 0 THEN 'Pending Verification'
            ELSE 'Unknown'
        END                                        AS loan_status,
        DATEDIFF(CURDATE(), visits.created_at)     AS days_since_visit,
        SUM(loans.product_total_cost)
            OVER (PARTITION BY growers.id)         AS total_loan_value
    FROM loans
    JOIN growers  ON growers.id  = loans.growerid
    JOIN users    ON users.id    = loans.userid
    JOIN products ON products.id = loans.productid
    LEFT JOIN visits ON visits.growerid = loans.growerid
        AND visits.id = (
            SELECT id FROM visits v2
            WHERE v2.growerid = loans.growerid
              AND v2.seasonid = loans.seasonid
            ORDER BY v2.created_at DESC
            LIMIT 1
        )
    WHERE loans.seasonid = ?
    ORDER BY
        loans.verified ASC,
        days_since_visit DESC,
        loans.product_total_cost DESC
");

$stmt->bind_param("i", $seasonid);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// ── Structure data for AI ─────────────────────────────────────
$data = [];

// Summary counters
$totalLoans       = 0;
$totalLoanValue   = 0;
$pendingLoans     = 0;
$unvisited30days  = 0;
$highRiskGrowers  = [];

while ($row = $result->fetch_assoc()) {
    $totalLoans++;
    $totalLoanValue  += $row["product_total_cost"];

    if ($row["loan_status"] === "Pending Verification") {
        $pendingLoans++;
    }

    if ($row["days_since_visit"] > 30 || $row["days_since_visit"] === null) {
        $unvisited30days++;
        $highRiskGrowers[] = $row["grower_num"];
    }

    $data[] = $row;
}

$jsonData = json_encode($data, JSON_PRETTY_PRINT);

// ── AI Prompt ─────────────────────────────────────────────────
$prompt = "You are a senior agricultural finance and agronomy advisor 
specializing in tobacco recovery for African farming operations.

Analyze the following tobacco grower loan and field visit data for Season $seasonid:

$jsonData

SUMMARY STATISTICS:
- Total loan records    : $totalLoans
- Total loan value      : $" . number_format($totalLoanValue, 2) . "
- Pending verification  : $pendingLoans loans
- Not visited in 30 days: $unvisited30days growers

Using this data, produce a TOBACCO RECOVERY MANAGEMENT REPORT covering:

1. LOAN PORTFOLIO OVERVIEW
   - Total value at risk broken down by loan status
     (Pending / Verified / Fully Processed)
   - Which field officers have the most unprocessed loans
   - Flag any growers with large loan values still unverified

2. GROWER VISIT COMPLIANCE
   - Field officers who are not visiting their growers regularly
   - Growers who have received loans but have NOT been visited 
     in over 20 days — these are HIGH RISK for non-recovery
   - Correlation between visit frequency and loan processing rate

3. HIGH RISK RECOVERY CASES
   - List growers where:
     a) Loan is unverified AND not visited in 14+ days
     b) Large loan value (top 20%) with pending status
     c) No visit notes recorded
   - Rank them by recovery risk: CRITICAL / HIGH / MEDIUM

4. FIELD OFFICER PERFORMANCE
   - Rank field officers by:
     a) Number of loans verified
     b) Visit frequency
     c) Total loan value managed
   - Flag underperforming officers who need management attention

5. PRODUCT DISTRIBUTION ANALYSIS
   - Which products are most distributed
   - Which products have the highest unverified loan rate
   - Recommend any product distribution adjustments

6. RECOVERY ACTION PLAN FOR NEXT 7 DAYS
   - List the top 10 growers to visit urgently this week
   - Specify which field officer should visit each grower
   - Give a specific reason for each urgent visit
   - Suggest what the field officer should verify on arrival

Format as a professional management report with:
- Clear section headings
- Risk levels in CAPS (CRITICAL / HIGH / MEDIUM / LOW)
- Priority action tables where relevant
- Executive summary at the top (max 5 sentences)
- Use simple language suitable for non-technical farm managers";

// ── 7. Call Gemini ────────────────────────────────────────────
$gemini_response = analyzeGrowerData($prompt, $data1);

if (empty(trim($gemini_response))) {
    error_log("Visits Cron: Gemini returned empty response for season $seasonid");
    echo json_encode(["error" => "AI analysis failed — empty response"]);
    exit;
}

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