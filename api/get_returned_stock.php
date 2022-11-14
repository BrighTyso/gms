<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$description=$data->description;


$data1=array();

//http://192.168.1.190/gms/api/get_province.php

if ($description=="") {
  
$sql = "Select truck_destination.trucknumber,store.name,products.name as product_name,returned_stock.quantity,returned_stock.created_at from returned_stock join products on products.id=returned_stock.productid join store on store.id=returned_stock.storeid join truck_destination on returned_stock.disbursement_trucksid=truck_destination.id order by returned_stock.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("quantity"=>$row["quantity"],"created_at"=>$row["created_at"],"name"=>$row["name"],"product_name"=>$row["product_name"],"trucknumber"=>$row["trucknumber"]);
    array_push($data1,$temp);
    
   }
 }
}else{

$sql = "Select truck_destination.trucknumber,store.name,products.name as product_name,returned_stock.quantity,returned_stock.created_at from returned_stock join products on products.id=returned_stock.productid join store on store.id=returned_stock.storeid join truck_destination on returned_stock.disbursement_trucksid=truck_destination.id where trucknumber='$description' or store.name='$description' or products.name='$description' order by returned_stock.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("quantity"=>$row["quantity"],"created_at"=>$row["created_at"],"name"=>$row["name"],"product_name"=>$row["product_name"],"trucknumber"=>$row["trucknumber"]);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1);

?>





