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
$active_userid=$data->active_userid;
$amount=$data->amounts;
$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

$sql = "Select * from custom_based_salary where active_userid=$active_userid  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  $found=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $found=$row["id"];
    
    }
  
 }


if ($found>0) {
  
  $user_sql1 = "update custom_based_salary set amounts=$amount where id=$found";
 //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

       $temp=array("response"=>"successfully updated");
        array_push($response,$temp);
       
    }
    
}else{

  $user_sql = "INSERT INTO custom_based_salary(userid,seasonid,active_userid,amounts,created_at) VALUES ($userid,$seasonid,$active_userid,$amount,'$created_at')";
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





