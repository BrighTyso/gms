
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



$data1=array();

$landprep_discing=array();
$landprep_ploughing=array();
$landprep_ridging=array();

$field_maintanance=array();
$crop_measurement=array();
$disease_management=array();
$pest_management=array();
$crop_nutrition=array();
$soil_moisture=array();

// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct soil_moisture.id,soil_structure, moisture_state, field_irrigation, soil_moisture.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,soil_moisture.datetimes,soil_moisture.seasonid,soil_moisture.userid,latitude,longitude,username FROM soil_moisture join growers on growers.id=soil_moisture.growerid join users on users.id=soil_moisture.userid where soil_moisture.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"soil_structure"=>$row1["soil_structure"],"moisture_state"=>$row1["moisture_state"],"field_irrigation"=>$row1["field_irrigation"]);
              array_push($soil_moisture,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct crop_nutrition.id,topping_type,topping_app_rate,topping_app_date,crop_symptoms,crop_nutrition.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,crop_nutrition.datetimes,crop_nutrition.seasonid,crop_nutrition.userid,longitude,longitude,basal_type,basal_app_rate,basal_app_date,username FROM crop_nutrition join growers on growers.id=crop_nutrition.growerid join users on users.id=crop_nutrition.userid where crop_nutrition.seasonid=$seasonid ";





      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"topping_type"=>$row1["topping_type"],"topping_app_rate"=>$row1["topping_app_rate"],"topping_app_rate"=>$row1["topping_app_rate"],"crop_symptoms"=>$row1["crop_symptoms"]
    ,"crop_symptoms"=>$row1["crop_symptoms"],"basal_type"=>$row1["basal_type"]
    ,"basal_app_rate"=>$row1["basal_app_rate"],"basal_app_date"=>$row1["basal_app_date"]);
          array_push($crop_nutrition,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct pest_management.id,observed_pest,pest_effect_severity, pest_treated_group, applied_pesticide,pesticide_app_date,pest_management.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,pest_management.datetimes,pest_management.seasonid,pest_management.userid,latitude,longitude,username FROM pest_management join growers on growers.id=pest_management.growerid join users on users.id=pest_management.userid where pest_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"observed_pest"=>$row1["observed_pest"],"pest_effect_severity"=>$row1["pest_effect_severity"],"pest_treated_group"=>$row1["pest_treated_group"],"applied_pesticide"=>$row1["applied_pesticide"],"pesticide_app_date"=>$row1["pesticide_app_date"]);
          array_push($pest_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct disease_management.id,observed_disease,disease_effect_severity, disease_treated_group,applied_fungicide, fungicide_app_date, disease_management.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,disease_management.datetimes,disease_management.seasonid,disease_management.userid,latitude,longitude,username FROM disease_management join growers on growers.id=disease_management.growerid join users on users.id=disease_management.userid where disease_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"observed_disease"=>$row1["observed_disease"],"disease_effect_severity"=>$row1["disease_effect_severity"],"disease_treated_group"=>$row1["disease_treated_group"],"applied_fungicide"=>$row1["applied_fungicide"],"fungicide_app_date"=>$row1["fungicide_app_date"]
      ,"username"=>$row1["username"]);
          array_push($disease_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct crop_measurement.id,crop_age, avg_plant_height, avg_crop_vigor, avg_crop_density,crop_measurement.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,crop_measurement.datetimes,crop_measurement.seasonid,crop_measurement.userid,latitude,longitude,username FROM crop_measurement join growers on growers.id=crop_measurement.growerid join users on users.id=crop_measurement.userid where crop_measurement.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_age"=>$row1["crop_age"],"avg_plant_height"=>$row1["avg_plant_height"],"avg_crop_vigor"=>$row1["avg_crop_vigor"],"avg_crop_density"=>$row1["avg_crop_density"]);
          array_push($crop_measurement,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct field_maintanance.id,weed_level,weed_control_eff, herbicide_applied,sucker_eff, sucker_chem, gapping,population_uniformity,field_maintanance.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,field_maintanance.datetimes,field_maintanance.seasonid,field_maintanance.userid,latitude,longitude,username FROM field_maintanance join growers on growers.id=field_maintanance.growerid join users on users.id=field_maintanance.userid where field_maintanance.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"weed_level"=>$row1["weed_level"],"weed_control_eff"=>$row1["weed_control_eff"],"herbicide_applied"=>$row1["herbicide_applied"],"sucker_eff"=>$row1["sucker_eff"],"sucker_chem"=>$row1["sucker_chem"]
      ,"gapping"=>$row1["gapping"],"population_uniformity"=>$row1["population_uniformity"]);
          array_push($field_maintanance,$temp);

         
         }
       
   }






   $temp=array("field_maintanance"=>$field_maintanance,"crop_measurement"=>$crop_measurement,"disease_management"=>$disease_management,"pest_management"=>$pest_management,"crop_nutrition"=>$crop_nutrition,"soil_moisture"=>$soil_moisture);
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


