<?php
require_once("conn.php");
require "validate.php";


$data1=array();

//http://192.168.1.190/gms/api/get_province.php

$start_date=$_GET['start_date'];
$end_date=$_GET['end_date'];



$sql = "Select * from disburse_products_by_date join products on products.id=disburse_products_by_date.productid where end_date between '$start_date' and '$end_date'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("name"=>$row["name"],"id"=>$row["id"],"seasonid"=>$row["seasonid"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"created_at"=>$row["start_date"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);

?>





