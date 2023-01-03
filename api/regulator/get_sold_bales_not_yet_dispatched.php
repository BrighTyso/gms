<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;



$data1=array();

//http://192.168.1.190/gms/api/get_season.php

$sql = "Select * from sold_bales left join dispatch on sold_bales.id=dispatch.sold_balesid join growers on sold_bales.growerid=growers.id  where sold_bales.userid=$userid and sold_bales.seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("barcode"=>$row["barcode"],"id"=>$row["id"],"mass"=>$row["mass"],"price"=>$row["price"],"grower_num"=>$row["grower_num"],"created_at"=>$row["created_at"],"sold_balesid"=>$row["sold_balesid"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);


?>





