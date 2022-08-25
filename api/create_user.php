<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$username="";
$name="";
$surname="";
$hash="";
$rightsid=0;
$active=0;

$response=array();

if (isset($data->username) && isset($data->hash)  && isset($data->name)  && isset($data->surname)  && isset($data->rightsid) && isset($data->active)){


$username=$data->username;
$name=$data->name;
$surname=$data->surname;
$hash=$data->hash;
$rightsid=$data->rightsid;
$active=$data->active;
$created_at=$data->created_at;


$user_sql = "INSERT INTO users(name,surname,username,hash,rightsid,active,access_code,created_at) VALUES ('$name','$surname','$username','$hash',$rightsid,$active,1234,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     $temp=array("response"=>"success");
     array_push($response,$temp);
     
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }


}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





