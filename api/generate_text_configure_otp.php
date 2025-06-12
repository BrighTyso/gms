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
$growerid=0;
$otp_production="";
$found_production=0;

$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $otp_production=$row["otp_code"];
   
   }

 }



 $sql = "Select * from sms_config_otp where  otp='$otp_production' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found_production=$row["id"];
   
   }

 }


 if ($found_production==0) {
  
   $user_sql = "INSERT INTO sms_config_otp(userid,otp) VALUES ($userid,'$otp_production')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {

     $temp=array("response"=>"success","otp"=>$otp_production);
     array_push($data1,$temp);

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

  if ($found_production>0) {
    $temp=array("response"=>"OTP Already Created");
    array_push($data1,$temp);
  }

}



}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
