<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();

$growerid=$data->growerid;
$seasonid=$data->seasonid;



//http://192.168.1.190/gms/api/get_seedbed.php


$sql = "select * from grower_managers where growerid=$growerid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
       $temp=array("fieldOfficer"=>$row["fieldOfficer"],"area_manager"=>$row["area_manager"],"chairman"=>$row["chairman"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }






 echo json_encode($data1); 



?>