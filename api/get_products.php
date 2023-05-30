<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$sql = "Select products.name,units,package_units,products.id,product_type.name as product_type_name from products join product_type on product_type.id=products.product_typeid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"units"=>$row["units"],"package_units"=>$row["package_units"],"package_units"=>$row["package_units"],"productid"=>$row["id"],"product_type_name"=>$row["product_type_name"]);
    array_push($data,$temp);
    
   }
 }



 echo json_encode($data); 



?>