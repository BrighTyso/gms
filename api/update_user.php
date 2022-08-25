<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$username="";
$rightsid=0;
$userid=0;


if (isset($data->rightsid) && isset($data->username) && isset($data->userid)){

$userid=$data->userid;
$username=$data->username;
$rightsid=$data->rightsid;



$user_sql = "update users set rightsid=$rightsid where username='$username'";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo json_encode($conn->error);

   }




}else{

	echo json_encode("field cant be empty");
}



?>





