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
$userid=$data->userid;


//http://192.168.1.190/gms/api/get_seedbed.php

if ($userid!="") {
  $sql = "select quarter_grown_5_7,half_grown_8_12,three_quarters_13_17,full_grown_18_22,fully_developed,created_at from crop_growth where growerid=$growerid and seasonid=$seasonid order by created_at desc limit 1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("quarter_grown_5_7"=>$row["quarter_grown_5_7"],"half_grown_8_12"=>$row["half_grown_8_12"],"three_quarters_13_17"=>$row["three_quarters_13_17"],"full_grown_18_22"=>$row["full_grown_18_22"],"fully_developed"=>$row["fully_developed"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }
}




 echo json_encode($data1); 



?>