<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$created_at=$data->created_at;

$data1=array();

//http://192.168.1.190/gms/api/get_products.php

$sql = "Select products.name,units,package_units,products.id from products join disbursement_products on disbursement_products.productid=products.id where disbursement_products.created_at='$created_at'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"units"=>$row["units"],"package_units"=>$row["package_units"],"package_units"=>$row["package_units"],"productid"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1); 



?>