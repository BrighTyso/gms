<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$sql = "Select distinct description,purchasing_order.id,order_number from purchasing_order join purchasing_order_shipment on purchasing_order_shipment.purchasing_orderid=purchasing_order.id join purchasing_order_products on purchasing_order_products.purchasing_orderid=purchasing_order.id  where active=1 and quantity>quantity_received order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("description"=>$row["description"],"id"=>$row["id"],"order_number"=>$row["order_number"]);
    array_push($data,$temp);
    
   }
 }



 echo json_encode($data); 



?>