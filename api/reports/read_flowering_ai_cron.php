
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

$username="";

$data1=array();



$flowering_field_management=array();
$flowering_pest_diseases=array();
$nutrient_stress=array();
$flowering_ripening_stage=array();


// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


      $sql11 = "SELECT distinct  flowering_ripening_stage.id,flowering_ripening_stage.userid,  flowering_ripening_stage.growerid,  latitude,  longitude,  flowering_ripening_stage.created_at,  flowering_ripening_stage.seasonid,  crop_topped,  crop_topping_height,crop_remaining_leaves, crop_topping_uniformity, suckers_per_plant, sucker_chem, sucker_eff,sucker_app_date, crop_maturity_stage, harvest_ready_pct, expected_first_picking,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_ripening_stage.datetimes,username FROM flowering_ripening_stage join growers on growers.id=flowering_ripening_stage.growerid join users on users.id=flowering_ripening_stage.userid where flowering_ripening_stage.seasonid=$seasonid  ";
      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_topped"=>$row1["crop_topped"],"crop_topping_height"=>$row1["crop_topping_height"],"crop_remaining_leaves"=>$row1["crop_remaining_leaves"],"crop_topping_uniformity"=>$row1["crop_topping_uniformity"],"suckers_per_plant"=>$row1["suckers_per_plant"],"sucker_chem"=>$row1["sucker_chem"]
        ,"sucker_eff"=>$row1["sucker_eff"],"sucker_app_date"=>$row1["sucker_app_date"],"crop_maturity_stage"=>$row1["crop_maturity_stage"],"harvest_ready_pct"=>$row1["harvest_ready_pct"]
,"expected_first_picking"=>$row1["expected_first_picking"]);
              array_push($flowering_ripening_stage,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct nutrient_stress.id,nutrient_stress.userid,  nutrient_stress.growerid,   latitude,  longitude,  nutrient_stress.created_at,  nutrient_stress.seasonid,  crop_stage,  nitrogen_level,  phosphorus_level, potassium_level, field_moisture, field_drought, field_drought_severity,grower_num,growers.name, growers.surname, id_num,area, province, phone, nutrient_stress.datetimes,username FROM nutrient_stress join growers on growers.id=nutrient_stress.growerid join users on users.id=nutrient_stress.userid where nutrient_stress.seasonid=$seasonid ";




      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_stage"=>$row1["crop_stage"],"nitrogen_level"=>$row1["nitrogen_level"],"phosphorus_level"=>$row1["phosphorus_level"],"potassium_level"=>$row1["potassium_level"]
    ,"field_moisture"=>$row1["field_moisture"],"field_drought"=>$row1["field_drought"]
    ,"field_drought_severity"=>$row1["field_drought_severity"]);
          array_push($nutrient_stress,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct flowering_pest_diseases.id,flowering_pest_diseases.userid,  flowering_pest_diseases.growerid,  latitude,  longitude,  flowering_pest_diseases.created_at,  flowering_pest_diseases.seasonid,  crop_stage,  pest_name,  pest_severity, pest_treated, pesticide_applied, pesticide_app_date, disease_name, disease_severity, disease_treated, sanitation_status,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_pest_diseases.datetimes,username FROM flowering_pest_diseases join growers on growers.id=flowering_pest_diseases.growerid join users on users.id=flowering_pest_diseases.userid where flowering_pest_diseases.seasonid=$seasonid  ";



      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_stage"=>$row1["crop_stage"],"pest_name"=>$row1["pest_name"],"pest_severity"=>$row1["pest_severity"],"pest_treated"=>$row1["pest_treated"],"pesticide_applied"=>$row1["pesticide_applied"],"pesticide_app_date"=>$row1["pesticide_app_date"],"disease_name"=>$row1["disease_name"]
    ,"disease_severity"=>$row1["disease_severity"],"disease_treated"=>$row1["disease_treated"]
    ,"sanitation_status"=>$row1["sanitation_status"]);
          array_push($flowering_pest_diseases,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct flowering_field_management.id,flowering_field_management.userid,  flowering_field_management.growerid,   latitude,  longitude,  flowering_field_management.created_at,  flowering_field_management.seasonid,  weeding_done,  weeds_level,  herbicide, harvest_ready, ripening_percent, expected_weight, labor_availability, uniformity_score,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_field_management.datetimes,username FROM flowering_field_management join growers on growers.id=flowering_field_management.growerid join users on users.id=flowering_field_management.userid where flowering_field_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"weeding_done"=>$row1["weeding_done"],"weeds_level"=>$row1["weeds_level"],"herbicide"=>$row1["herbicide"],"harvest_ready"=>$row1["harvest_ready"],"ripening_percent"=>$row1["ripening_percent"],"expected_weight"=>$row1["expected_weight"],"labor_availability"=>$row1["labor_availability"],"uniformity_score"=>$row1["uniformity_score"]);
          array_push($flowering_field_management,$temp);

         
         }
       
   }




   $temp=array("flowering_field_management"=>$flowering_field_management,"flowering_pest_diseases"=>$flowering_pest_diseases,"nutrient_stress"=>$nutrient_stress,"flowering_ripening_stage"=>$flowering_ripening_stage);
  array_push($data1,$temp);



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


