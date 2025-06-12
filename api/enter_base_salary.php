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

if (isset($data->userid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$amount=$data->amount;
$rights=$data->rights;
$rightsid=0;


$sql = "Select * from rights where description='$rights'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $rightsid=$row["id"];
   
   }

 }



$sql = "Select * from base_pay where seasonid=$seasonid and rightsid=$rightsid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0 and $rightsid>0) {
  
$user_sql = "INSERT INTO base_pay(userid,seasonid,amount,rightsid) VALUES ($userid,$seasonid,$amount,$rightsid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{
     $temp=array("response"=>"already created");
     array_push($data1,$temp);
}


}else{ 

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























