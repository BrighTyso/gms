<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$created_at="";


if (isset($data->name) && isset($data->created_at)){

$name=$data->name;
$created_at=$data->created_at;


$user_sql = "INSERT INTO seasons(name,active,created_at) VALUES ('$name',1,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo json_encode("failed");

   }




}else{

	echo json_encode("field cant be empty");
}



?>





