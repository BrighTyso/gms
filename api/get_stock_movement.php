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
  
$sql = "Select store.name,products.name as product_name,arc_products.old_quantity,arc_products.new_quantity,arc_products.created_at,arc_store_items.description,arc_store_items.arc_productid,arc_store_items.quantity from arc_products join store_items on arc_products.storeitemid=store_items.id join products on products.id=store_items.productid join store on store.id=store_items.storeid join arc_store_items on arc_store_items.arc_productid=arc_products.id order by arc_products.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("old_quantity"=>$row["old_quantity"],"created_at"=>$row["created_at"],"name"=>$row["name"],"product_name"=>$row["product_name"],"new_quantity"=>$row["new_quantity"],"description"=>$row["description"],"arc_productid"=>$row["arc_productid"],"quantity"=>$row["quantity"]);
    array_push($data1,$temp);
    
   }
 }

}else{

$sql = "Select store.name,products.name as product_name,arc_products.old_quantity,arc_products.new_quantity,arc_products.created_at,arc_store_items.description,arc_store_items.arc_productid,arc_store_items.quantity from arc_products join store_items on arc_products.storeitemid=store_items.id join products on products.id=store_items.productid join store on store.id=store_items.storeid join arc_store_items on arc_store_items.arc_productid=arc_products.id where products.name='$description' or store.name='$description' order by arc_products.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("old_quantity"=>$row["old_quantity"],"created_at"=>$row["created_at"],"name"=>$row["name"],"product_name"=>$row["product_name"],"new_quantity"=>$row["new_quantity"],"description"=>$row["description"],"arc_productid"=>$row["arc_productid"],"quantity"=>$row["quantity"]);
    array_push($data1,$temp);
    
    
   }
 }

}





 echo json_encode($data1);

?>





