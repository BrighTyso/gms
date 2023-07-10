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

//http://192.168.1.190/gms/api/get_products.php

if ($description!="") {
  
$sql = "Select store_hold_area.store_itemid,products.id as productid,store.name,store.location,products.name as product_name,store_hold_area.quantity,products.units,store_items.created_at from store join store_items on store.id=store_items.storeid join products on products.id=store_items.productid join store_hold_area on store_hold_area.store_itemid=store_items.id where products.name='$description' or store.name='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("productid"=>$row["productid"],"store_itemid"=>$row["store_itemid"],"name"=>$row["name"],"location"=>$row["location"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }


}else{


$sql = "Select store_hold_area.store_itemid,products.id as productid,store.name,store.location,products.name as product_name,store_hold_area.quantity,products.units,store_items.created_at from store join store_items on store.id=store_items.storeid join products on products.id=store_items.productid join store_hold_area on store_hold_area.store_itemid=store_items.id ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("productid"=>$row["productid"],"store_itemid"=>$row["store_itemid"],"name"=>$row["name"],"location"=>$row["location"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1); 



?>