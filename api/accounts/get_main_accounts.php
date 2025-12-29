<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;

$response=array();

#$userid=$data->userid;
$sql = "Select main_accounts.description,main_accounts.id from main_accounts";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
    $temp=array("description"=>$row["description"],"id"=>$row["id"]);
    array_push($response,$temp);
    
   }
 }


echo json_encode($response);

?>





