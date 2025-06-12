<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$phone=$data->phone;
$email=$data->email;
$otp=$data->otp;
$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

$security_otp_found=0;


$sql = "Select * from authorization_email_otp WHERE otp ='$otp' and email='$email' 
  AND created_at > NOW() - INTERVAL 30 MINUTE; ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {

    $security_otp_found=$row["id"];   
    
   }
 }


 if ($security_otp_found>0) {
   // code...
 

$sql = "Select * from operations_contacts where email='$email'  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  $found=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $found=$row["id"];
    
    }
  
 }


if ($found>0) {
  
 $temp=array("response"=>"Email Already Created");
  array_push($response,$temp);       
    
}else{

  $user_sql = "INSERT INTO operations_contacts(email,phone) VALUES ('$email','$phone')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
         $last_id = $conn->insert_id;



          $sql = "Select * from operations_contacts where  active=1";
              $result = $conn->query($sql);
               
               if ($result->num_rows > 0) {
                 // output data of each row
                 while($row = $result->fetch_assoc()) {
                  $phone=$row["phone"];
                  $contact_email=$row["email"];
                  $to = $contact_email;
                  $subject = "Warehouse Monitoring";
                  $txt = "New email ,".$email." Has been add to company Authorization and notification email list";
                  $headers = "From: warehouse@coreafricagrp.com";
                  mail($to,$subject,$txt,$headers);
                 }

               }



         $temp=array("response"=>"success");
         array_push($response,$temp);
         
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }
 }else{

  $temp=array("response"=>"OTP Expired(Not Found)");
  array_push($response,$temp);
 }


}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





