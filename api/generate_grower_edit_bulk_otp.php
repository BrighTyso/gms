<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;
$security_otp_found=0;
$fetched_records=0;
$processed_records=0;
$found_production=0;
$otp_production="";

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
//$security=$data->security_otp;
$seasonid=$data->seasonid;
//$grower_num=$data->grower_num;




$sql = "Select * from users WHERE id=$userid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {

    $username=$row["username"];   
    
   }
 }







$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $otp_production=$row["otp_code"];
   
   }

 }



 $sql = "Select * from grower_bulk_edit_otp where  otp='$otp_production' and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found_production=$row["id"];
   
   }

 }


 if ($found_production==0) {
  
   $user_sql = "INSERT INTO grower_bulk_edit_otp(userid,seasonid,otp) VALUES ($userid,$seasonid,'$otp_production')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
$subject = "";
            $txt="";
            $subject="";
            $contacts=array();

            $sql = "Select * from operations_contacts where active=1";
              $result = $conn->query($sql);
               
               if ($result->num_rows > 0) {
                 // output data of each row
                 while($row = $result->fetch_assoc()) {

                  $temp1=array("phone"=>$row["phone"]);
                  array_push($contacts,$temp1);

                  $phone=$row["phone"];
                  $contact_email=$row["email"];
                  $to = $contact_email; 
                  $subject = "Grower Bulk Update";
                  $txt = "User ".$username." has requested grower bulk update \n OTP : ".$otp_production;
                  $headers = "From: gmsotp@coreafricagrp.com";
                  mail($to,$subject,$txt,$headers);
                 }

               }
   
     $temp=array("response"=>"success","message"=>$txt,"otp"=>$otp_production,"contacts"=>$contacts,"subject"=>$subject);
           array_push($data1,$temp);

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

$temp=array("response"=>"Try Again");
array_push($data1,$temp);

}



}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
