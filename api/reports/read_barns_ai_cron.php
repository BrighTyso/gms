
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

$barn_fire_safety=array();
$barn_heatsource=array();
$barn_humidity=array();
$barn_loading_capacity=array();
$barn_structure=array();
$barn_ventilation=array();

// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_fire_safety.created_at, 
barn_fire_safety.seasonid, 
barn_number, 
firebreak_cleared, 
chimney_safety, 
heat_source_outside,
fire_fighting,
electric_wiring_safe,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_fire_safety.userid,barn_fire_safety.datetimes,username FROM barn_fire_safety join growers on growers.id=barn_fire_safety.growerid join users on users.id=barn_fire_safety.userid where barn_fire_safety.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"firebreak_cleared"=>$row1["firebreak_cleared"],"chimney_safety"=>$row1["chimney_safety"],"heat_source_outside"=>$row1["heat_source_outside"],"fire_fighting"=>$row1["fire_fighting"]
      ,"electric_wiring_safe"=>$row1["electric_wiring_safe"],"created_at"=>$row1["created_at"]
      );
          array_push($barn_fire_safety,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_heatsource.created_at, 
barn_heatsource.datetimes,
barn_heatsource.seasonid, 
barn_number, 
heat_source, 
flue_intact, 
smoke_leakages,
temperature_control,
thermometer,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_heatsource.userid,barn_heatsource.datetimes,username FROM barn_heatsource join growers on growers.id=barn_heatsource.growerid join users on users.id=barn_heatsource.userid where barn_heatsource.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"heat_source"=>$row1["heat_source"],"flue_intact"=>$row1["flue_intact"],"smoke_leakages"=>$row1["smoke_leakages"],"temperature_control"=>$row1["temperature_control"]
      ,"thermometer"=>$row1["thermometer"],"created_at"=>$row1["created_at"]);
          array_push($barn_heatsource,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_humidity.created_at, 
barn_humidity.seasonid, 
barn_number, 
humidity_retain, 
moisture_release, 
roof_leaks,
dripping,
drainange,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_humidity.userid,barn_humidity.datetimes,username FROM barn_humidity join growers on growers.id=barn_humidity.growerid join users on users.id=barn_humidity.userid where barn_humidity.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"humidity_retain"=>$row1["humidity_retain"],"moisture_release"=>$row1["moisture_release"],"roof_leaks"=>$row1["roof_leaks"],"dripping"=>$row1["dripping"]
      ,"drainange"=>$row1["drainange"],"created_at"=>$row1["created_at"]);
          array_push($barn_humidity,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_loading_capacity.created_at, 
barn_loading_capacity.seasonid, 
barn_number, 
tiers_poles_condition, 
tier_spacing, 
barn_capacity,
overloading_risk,
unloading_safety,
barn_ha_capacity,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_loading_capacity.userid,barn_loading_capacity.datetimes,username FROM barn_loading_capacity join growers on growers.id=barn_loading_capacity.growerid join users on users.id=barn_loading_capacity.userid where barn_loading_capacity.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"tiers_poles_condition"=>$row1["tiers_poles_condition"],"tier_spacing"=>$row1["tier_spacing"],"barn_capacity"=>$row1["barn_capacity"],"overloading_risk"=>$row1["overloading_risk"]
      ,"unloading_safety"=>$row1["unloading_safety"],"barn_ha_capacity"=>$row1["barn_ha_capacity"]
      );
          array_push($barn_loading_capacity,$temp);

         
         } 
   }






$sql11 = "SELECT distinct  growerid, latitude, longitude,barn_structure.created_at, barn_structure.seasonid, barn_number, barn_type, 
barn_roof, barn_walls, barn_doors,barn_stability,barn_floor,barn_termite_rot,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_structure.userid,barn_structure.datetimes,username FROM barn_structure join growers on growers.id=barn_structure.growerid join users on users.id=barn_structure.userid where barn_structure.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"barn_type"=>$row1["barn_type"],"barn_roof"=>$row1["barn_roof"],"barn_walls"=>$row1["barn_walls"],"barn_doors"=>$row1["barn_doors"]
      ,"barn_stability"=>$row1["barn_stability"],"barn_floor"=>$row1["barn_floor"]
      ,"barn_termite_rot"=>$row1["barn_termite_rot"],"created_at"=>$row1["created_at"]
     );
          array_push($barn_structure,$temp);

         
         } 
   }






$sql11 = "SELECT distinct  
growerid, latitude, longitude, barn_ventilation.created_at,barn_ventilation.seasonid, barn_number, vent_type, vent_condition, vent_count,vent_ease,airflow_obstruction,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_ventilation.userid,barn_ventilation.datetimes,username FROM barn_ventilation join growers on growers.id=barn_ventilation.growerid join users on users.id=barn_ventilation.userid where barn_ventilation.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"vent_type"=>$row1["vent_type"],"vent_ease"=>$row1["vent_ease"],"vent_count"=>$row1["vent_count"],"airflow_obstruction"=>$row1["airflow_obstruction"]
      ,"created_at"=>$row1["created_at"]);
          array_push($barn_ventilation,$temp);

         
         } 
   }





$temp=array("barn_fire_safety"=>$barn_fire_safety,"barn_heatsource"=>$barn_heatsource,"barn_humidity"=>$barn_humidity,"barn_loading_capacity"=>$barn_loading_capacity

,"barn_structure"=>$barn_structure,"barn_ventilation"=>$barn_ventilation
);
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


