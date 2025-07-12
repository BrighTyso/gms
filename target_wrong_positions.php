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
$days=$data->days;
$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

$sql = "Select * from target_wrong_position where seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  $found=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $found=$row["id"];
    
     }
  
 }


if ($found>0) {
  
  $user_sql1 = "update target_wrong_position set positions=$days where id=$found";
 //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

       $temp=array("response"=>"successfully updated");
        array_push($response,$temp);
       
    }
    
}else{

  $user_sql = "INSERT INTO target_wrong_position(userid,seasonid,positions,created_at) VALUES ($userid,$seasonid,$days,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

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





