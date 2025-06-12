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

$data1=array();

if (isset($data->userid) && isset($data->text_message_display) && isset($data->otp)){

$userid=$data->userid;
$text_message_display=$data->text_message_display;
$country_code=$data->country_code;
$created_at=$data->created_at;
$otp=$data->otp;
$found_production=0;




 $sql = "Select * from sms_config_otp where  otp='$otp' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found_production=$row["id"];
   
   }

 }



$sql = "Select * from phone_country_code limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0 && $found_production>0) {
  
$user_sql = "INSERT INTO phone_country_code(userid,text_message_display,country_code,created_at) VALUES ($userid,'$text_message_display','$country_code' ,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{
  if ($found_production==0) {
    $temp=array("response"=>"OTP Not Found");
     array_push($data1,$temp);
  }else{
    $temp=array("response"=>"Already Created");
     array_push($data1,$temp);
  }
  
}


}else{

   $temp=array("response"=>"field empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























