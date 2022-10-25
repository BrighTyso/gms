<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data1=array();

//http://192.168.1.190/gms/api/get_season.php

$sql = "Select * from seasons where active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("name"=>$row["name"],"id"=>$row["id"],"active"=>$row["active"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);


?>





