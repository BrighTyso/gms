
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




$diseases_pest_control=array();
$seedling_growth_vigour=array();
$seedbed_soil_health=array();
$seedbed_management=array();
$seedbed_leafcolor=array();
$seed_germination=array();


// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


      $sql11 = "SELECT distinct  seed_germination.id, growerid, latitude, longitude, germination_percentage, seedVariety, bed_type, intendedHa, plantedDate, seed_germination.created_at,seed_germination.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seed_germination.seasonid,datetimes,username FROM seed_germination join growers on seed_germination.growerid=growers.id join users on users.id=seed_germination.userid where seed_germination.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"germination_percentage"=>$row1["germination_percentage"],"seedVariety"=>$row1["seedVariety"],"bed_type"=>$row1["bed_type"],"intendedHa"=>$row1["intendedHa"],"plantedDate"=>$row1["plantedDate"]);
              array_push($seed_germination,$temp);

         
         }
       
   }






   $sql11 = "SELECT distinct seedbed_leafcolor.id, growerid, bed_number, seedbed_leaf_color, seedbed_leafcolor.created_at,seedbed_leafcolor.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_leafcolor.seasonid,datetimes,latitude,longitude,username FROM seedbed_leafcolor join growers on seedbed_leafcolor.growerid=growers.id join users on users.id=seedbed_leafcolor.userid where seedbed_leafcolor.seasonid=$seasonid ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"bed_number"=>$row1["bed_number"],"seedbed_leaf_color"=>$row1["seedbed_leaf_color"]);
          array_push($seedbed_leafcolor,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct seedbed_management.id, growerid, seedbed_type, pocket_numbering,weeding_done, weeds_rate,fertiliser_top,fertiliser_top_date, seedbed_management.created_at,seedbed_management.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_management.seasonid,datetimes,latitude,longitude,fungi_app,fungi_app_date,pesti_app,pesti_app_date,herbi_app,herbi_app_date,username FROM seedbed_management join growers on seedbed_management.growerid=growers.id join users on users.id=seedbed_management.userid where seedbed_management.seasonid=$seasonid  ";




      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_type"=>$row1["seedbed_type"],"pocket_numbering"=>$row1["pocket_numbering"],"weeding_done"=>$row1["weeding_done"],"weeds_rate"=>$row1["weeds_rate"],"fertiliser_top"=>$row1["fertiliser_top"],"fertiliser_top_date"=>$row1["fertiliser_top_date"],"fungi_app"=>$row1["fungi_app"]
    ,"fungi_app_date"=>$row1["fungi_app_date"],"pesti_app"=>$row1["pesti_app"],"pesti_app_date"=>$row1["pesti_app_date"]
    ,"herbi_app"=>$row1["herbi_app"],"herbi_app_date"=>$row1["herbi_app_date"]);
          array_push($seedbed_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct seedbed_soil_health.id, growerid, seedbed_soil_drainage, seedbed_drainage_rate, seedbed_soil_structure,seedbed_structure_rate, seedbed_soil_health.created_at,seedbed_soil_health.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_soil_health.seasonid,datetimes,latitude,longitude,username FROM seedbed_soil_health join growers on seedbed_soil_health.growerid=growers.id join users on users.id=seedbed_soil_health.userid where seedbed_soil_health.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_soil_drainage"=>$row1["seedbed_soil_drainage"],"seedbed_drainage_rate"=>$row1["seedbed_drainage_rate"],"seedbed_soil_structure"=>$row1["seedbed_soil_structure"],"seedbed_structure_rate"=>$row1["seedbed_structure_rate"]);
          array_push($seedbed_soil_health,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct seedling_growth_vigour.id, seedling_health, seedling_health_rate, seedling_growth, seedling_stage_date, seedling_growth_vigour.created_at,seedling_growth_vigour.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedling_growth_vigour.seasonid,datetimes,latitude,longitude,username FROM seedling_growth_vigour join growers on seedling_growth_vigour.growerid=growers.id join users on users.id=seedling_growth_vigour.userid where seedling_growth_vigour.seasonid=$seasonid  ";



      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedling_health"=>$row1["seedling_health"],"seedling_health_rate"=>$row1["seedling_health_rate"],"seedling_growth"=>$row1["seedling_growth"],"seedling_stage_date"=>$row1["seedling_stage_date"]);
          array_push($seedling_growth_vigour,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct diseases_pest_control.id, growerid, seedbed_pest_identified, seedbed_pesticide_applied, seedbed_pesticide_app_date, seedbed_disease_identified,seedbed_fungicide_applied,seedbed_fungicide_app_date, diseases_pest_control.created_at,diseases_pest_control.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,diseases_pest_control.seasonid,datetimes,latitude,longitude,username FROM diseases_pest_control join growers on diseases_pest_control.growerid=growers.id join users on users.id=diseases_pest_control.userid where diseases_pest_control.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_pest_identified"=>$row1["seedbed_pest_identified"],"seedbed_pesticide_applied"=>$row1["seedbed_pesticide_applied"],"seedbed_pesticide_app_date"=>$row1["seedbed_pesticide_app_date"],"seedbed_disease_identified"=>$row1["seedbed_disease_identified"],"seedbed_fungicide_applied"=>$row1["seedbed_fungicide_applied"],"seedbed_fungicide_app_date"=>$row1["seedbed_fungicide_app_date"]);
          array_push($diseases_pest_control,$temp);

         
         }
       
   }




   $temp=array("diseases_pest_control"=>$diseases_pest_control,"seedling_growth_vigour"=>$seedling_growth_vigour,"seedbed_soil_health"=>$seedbed_soil_health,"seedbed_management"=>$seedbed_management,"seedbed_leafcolor"=>$seedbed_leafcolor,"seed_germination"=>$seed_germination);
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


