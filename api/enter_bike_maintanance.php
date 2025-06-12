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
$field_officerid=$data->field_officerid;
$receipt_number=$data->receipt_number;
$description=$data->description;
$created_at=$data->created_at;
$maintanance_date=substr($data->maintanance_date,0,-8);
  
$user_sql = "INSERT INTO bike_maintanance(userid,seasonid,field_officerid,receipt_number,description,amount,created_at,maintanance_date) VALUES ($userid,$seasonid,$field_officerid,'$receipt_number','$description','$amount','$created_at','$maintanance_date')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }



}else{ 

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























