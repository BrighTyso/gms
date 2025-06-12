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

$sql = "Select * from shipment_details order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

  
   $temp=array("shipment"=>$row['shipment'],"mass"=>$row['mass'],"bales"=>$row['bales'],"location"=>$row['location'],"dispatch_date"=>$row['dispatch_date'],"id"=>$row['id']);
    array_push($response,$temp);

  
   }

 }

}

echo json_encode($response);


?>