<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$companyid=$data->companyid;

$data1=array();

//http://192.168.1.190/gms/api/get_products.php

  
$sql = "Select * from developer join users on developer.userid=users.id where userid=$companyid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("company_code"=>$row["company_code"],"userid"=>$row["userid"],"warehouse_code"=>$row["warehouse_code"],"active"=>$row["active"],"datetime"=>$row["datetime"],"username"=>$row["username"],"name"=>$row["name"]);
    array_push($data1,$temp);
    
   }
 }







 echo json_encode($data1); 



?>