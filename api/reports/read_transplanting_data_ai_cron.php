
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
       // output data of each row.
       while($row3 = $result3->fetch_assoc()) {

        $seasonid=$row3["id"];

       
   }
 }

$data1=array();
// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct tobacco_transplanting.id ,tobacco_transplanting.seasonid,field_name, transplant_vigor, transplant_pests, transplant_diseases,transplant_weeds,transplant_survival_rate,hectares_transplanted,transplant_date,no_of_plants,tobacco_transplanting.created_at,latitude,longitude,grower_num,growers.name, growers.surname, id_num,area, province, phone,tobacco_transplanting.userid,transplant_root_health,tobacco_transplanting.datetimes,username FROM tobacco_transplanting join growers on growers.id=tobacco_transplanting.growerid join users on users.id=tobacco_transplanting.userid where tobacco_transplanting.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"field_name"=>$row1["field_name"],"transplant_vigor"=>$row1["transplant_vigor"],"transplant_pests"=>$row1["transplant_pests"],"transplant_diseases"=>$row1["transplant_diseases"],"transplant_weeds"=>$row1["transplant_weeds"]
      ,"transplant_survival_rate"=>$row1["transplant_survival_rate"],"hectares_transplanted"=>$row1["hectares_transplanted"]
      ,"transplant_date"=>$row1["transplant_date"],"no_of_plants"=>$row1["no_of_plants"]
      ,"transplant_root_health"=>$row1["transplant_root_health"]);
          array_push($data1,$temp);

         
         }
       
   }



}

 $jsonData = json_encode($data1, JSON_PRETTY_PRINT);

$prompt = "You are an expert agronomist. Analyze the following grower data in JSON format:
    
    $jsonData
    
    Please provide:
    1. A high-level summary of performance.
    2. Any growers at risk of low yield.
    3. One actionable recommendation for next week.
    Format the response in clean Markdown.";

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

?>


