<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$productid=0;
$amount="";
$seasonid=0;
$created_at="";

if (isset($data->userid) && isset($data->productid)  && isset($data->amount)  && isset($data->seasonid) && isset($data->created_at)){

$userid=$data->userid;
$productid=$data->productid;
$amount=$data->amount;
$seasonid=$data->seasonid;
$created_at=$data->created_at;


$user_sql = "INSERT INTO prices(userid,productid,amount,seasonid,created_at) VALUES ($userid,$productid,'$amount',$seasonid,'$created_at')";
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





