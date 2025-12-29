<?php
require "conn.php";
require "validate.php";

$data=array();

$username="";
$hash="";
$access_code=0000;


https://www.coreafricagrp.com/v1/2/2024-09-09/2024-09-02/1/7/locations

http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234

if (isset($_GET['truck_number']) && isset($_GET['userid'])){


$truck_num=validate($_GET['truck_number']);
$seasonid=$_GET['seasonid'];


$sql = "Select distinct * from truck_grower_qrcode_disbursed_mobile join truck_destination on truck_destination.id=truck_grower_qrcode_disbursed_mobile.disbursement_trucksid join growers on truck_grower_qrcode_disbursed_mobile.growerid=growers.id   where trucknumber='$truck_num' and truck_grower_qrcode_disbursed_mobile.seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("grower_num"=>$row["grower_num"],"receipt_num"=>$row["receipt_num"],"hectares"=>$row["hectares"],"created_at"=>$row["created_at"],"truck_number"=>$row["trucknumber"]);
    array_push($data,$temp);
    
   }
 }


 echo json_encode($data); 

}



?>