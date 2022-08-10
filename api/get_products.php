<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$sql = "Select * from products ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"units"=>$row["units"],"productid"=>$row["id"]);
    array_push($data,$temp);
    
   }
 }



 echo json_encode($data); 



?>