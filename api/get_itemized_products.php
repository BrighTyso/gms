<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$userid=$data->userid;
$productid=$data->productid;
$seasonid=$data->seasonid;

//http://192.168.1.190/gms/api/get_province.php

$sql = "Select itemized_product.id,product_items.description,itemized_product.quantity,products.name,price,itemized_product.created_at from itemized_product join products on products.id=itemized_product.productid join product_items on product_items.id=itemized_product.product_itemid where itemized_product.productid=$productid and itemized_product.seasonid=$seasonid  order by products.name";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("created_at"=>$row["created_at"],"id"=>$row["id"],"description"=>$row["description"],"quantity"=>$row["quantity"],"product_name"=>$row["name"],"price"=>$row["price"]);
    array_push($data1,$temp);
    
   }
 }



 echo json_encode($data1);

?>





