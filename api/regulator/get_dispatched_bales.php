<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data1=array();

//http://192.168.1.190/gms/api/get_season.php

$sql = "Select sold_bales.userid,seasonid,growerid,barcode,mass,price from sold_bales join dispatch on sold_bales.id=dispatch.sold_balesid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("barcode"=>$row["barcode"],"mass"=>$row["mass"],"price"=>$row["price"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);


?>





