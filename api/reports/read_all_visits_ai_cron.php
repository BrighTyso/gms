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

// ── 1. Get active season ──────────────────────────────────────
$seasonid = 0;

$result3 = $conn->query("SELECT id FROM seasons WHERE active = 1 LIMIT 1");
if ($result3 && $result3->num_rows > 0) {
    $seasonid = $result3->fetch_assoc()["id"];
}

if ($seasonid == 0) {
    error_log("Visits Cron: No active season found.");
    echo json_encode(["error" => "No active season found"]);
    exit;
}

// ── 2. Date range — last 30 days ──────────────────────────────
$end   = date("Y-m-d");
$start = (new DateTime())->modify('-30 days')->format('Y-m-d');

// ── 3. Single optimised query — NO N+1 ───────────────────────
// Replaces the two nested queries with one JOIN
$stmt = $conn->prepare("
    SELECT
        users.username,
        users.id                  AS userid,
        visits.created_at,
        growers.grower_num,
        growers.surname,
        growers.name,
        visits.description,
        visits.latitude,
        visits.longitude,
        visits.times
    FROM visits
    JOIN growers ON growers.id   = visits.growerid
    JOIN users   ON users.id     = visits.userid
    WHERE visits.seasonid = ?
      AND visits.created_at BETWEEN ? AND ?
    ORDER BY users.username ASC, visits.created_at DESC
");

if (!$stmt) {
    error_log("Visits Cron: Prepare failed — " . $conn->error);
    echo json_encode(["error" => "Query prepare failed"]);
    exit;
}

$stmt->bind_param("iss", $seasonid, $start, $end);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// ── 4. Group results by username + date ───────────────────────
$grouped = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $username   = $row["username"];
        $created_at = $row["created_at"];
        $key        = $username . "_" . $created_at;

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                "username"   => $username,
                "created_at" => $created_at,
                "visits"     => [],
            ];
        }

        $grouped[$key]["visits"][] = [
            "grower_num"  => $row["grower_num"],
            "surname"     => $row["surname"],
            "name"        => $row["name"],
            "latitude"    => $row["latitude"],
            "longitude"   => $row["longitude"],
            "created_at"  => $row["created_at"],
            "description" => $row["description"],
            "times"       => $row["times"],
        ];
    }
}

// Re-index array
$data1 = array_values($grouped);

// ── 5. Guard — no data ────────────────────────────────────────
if (empty($data1)) {
    error_log("Visits Cron: No visit data found for season $seasonid between $start and $end");
    echo json_encode(["error" => "No visit data found"]);
    exit;
}

// ── 6. Build Gemini prompt ────────────────────────────────────
$jsonData = json_encode($data1, JSON_PRETTY_PRINT);

$prompt = "You are an expert agronomist. Analyze the following field officer visit data in JSON format:

$jsonData

The data covers visits made between $start and $end for season $seasonid.

Please provide:
1. A high-level summary of field officer performance and visit coverage.
2. Growers who have NOT been visited recently and may be at risk.
3. Field officers with low visit counts who need follow-up.
4. One actionable recommendation for management next week.

Format the response in clean Markdown suitable for a management report.";

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
        $mail->Subject = "GMS Field Visit Report — Season $seasonid";
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