<?php
require_once("conn.php");
require "validate.php";


$data1=array();

//http://192.168.1.190/gms/api/get_season.php

$sql = "Select distinct sale_date from grower_payment_schedule order by id desc limit 100";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("sale_date"=>$row["sale_date"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);


?>





