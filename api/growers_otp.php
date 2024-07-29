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
$quantity_in_store=0;
$product="";
$store="";
$location="";

$data1=array();
$storeData=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
$growerid=$data->growerid;
$seasonid=$data->seasonid;

$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $otp=$row["otp_code"];
   
   }

 }





$sql = "Select * from growers_otp where  otp='$otp' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }


 if ($found==0) {
  
   $user_sql = "INSERT INTO growers_otp(userid,seasonid,growerid,otp) VALUES ($userid,$seasonid,$growerid,'$otp')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success","otp"=>$otp);
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{

$temp=array("response"=>"Could not Generate OTP");
array_push($data1,$temp);

}




}

echo json_encode($data1);

?>
