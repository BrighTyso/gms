<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();

$description=$data->description;



//http://192.168.1.190/gms/api/get_seedbed.php

if ($description!="") {
  $sql = "select * from growers where grower_num='$description' or name='$description' or surname='$description' or area='$description' or province='$description' limit 2000";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }
}else{

  $sql = "select * from growers limit 2000";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
       $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1); 



?>