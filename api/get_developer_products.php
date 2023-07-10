<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;

$data1=array();

//http://192.168.1.190/gms/api/get_products.php

  
$sql = "Select product_code,units,package_units,products.name,product_type.name as product_type_name from developer_product_codes join products on products.id=developer_product_codes.productid join product_type on product_type.id=products.product_typeid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("product_code"=>$row["product_code"],"units"=>$row["units"],"package_units"=>$row["package_units"],"name"=>$row["name"],"product_type_name"=>$row["product_type_name"]);
    array_push($data1,$temp);
    
   }
 }







 echo json_encode($data1); 



?>