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
$destination=$data->destination;
$driver_surname=$data->driver_surname;
$driver_name=$data->driver_name;
$truck_trailer=$data->truck_trailer;
$truck_horse=$data->truck_horse;
$storeid=$data->storeid;
$store_name="";
$username="";

$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');



$sql = "Select * from store where id=$storeid limit 1";
$result = $conn->query($sql);
 if ($result->num_rows==0) {
 
 }else{

  while($row = $result->fetch_assoc()) {
     
     $store_name=$row["id"];
    
    }
  
 }



$sql = "Select * from users where id=$userid limit 1";
$result = $conn->query($sql);
 if ($result->num_rows==0) {
 
 }else{

  while($row = $result->fetch_assoc()) {
     
     $username=$row["id"];
    
    }
  
 }


$sql = "Select * from processing_dispatch_truck where truck_horse='$truck_horse' and destination='$destination' and storeid=$storeid and created_at='$created_at'  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  $found=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $found=$row["id"];
    
    }
  
 }


if ($found>0) {
  
 $temp=array("response"=>"Already Created");
  array_push($response,$temp);
        
}else{
  $user_sql = "INSERT INTO processing_dispatch_truck(userid,seasonid,storeid,truck_horse,truck_trailer,driver_name,driver_surname,destination,created_at) VALUES ($userid,$seasonid,$storeid,'$truck_horse','$truck_trailer','$driver_name','$driver_surname','$destination','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;
       $sql = "Select * from operations_contacts where active=1";
            $result = $conn->query($sql);
             
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          $phone=$row["phone"];
          $contact_email=$row["email"];
          $to = $contact_email; 
          $subject = "Dispatch Trucks";
          $txt = "User ".$username." has created a dispatch truck with truck number ".$truck_horse." from ".$store_name;
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


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





