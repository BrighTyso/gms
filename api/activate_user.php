<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid="";
$rightsid=0;
$state=0;

$response=array();

if (isset($data->id)){


$userid=$data->id;


$user_sql = "update users set active=1  where id = $userid ";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
    array_push($response,$temp);
     
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }


}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





