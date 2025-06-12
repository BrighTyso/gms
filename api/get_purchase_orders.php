<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$sql = "Select * from purchasing_order  where active=0 order by id desc";
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