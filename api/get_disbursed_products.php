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
  
$sql = "Select distinct truck_destination.trucknumber,truck_destination.id,disbursement.storeid,disbursement.productid,disbursement.quantity ,products.name,products.units,destination,store.name as store_name,total_disbursement.quantity as total,total_disbursement.created_at from disbursement join truck_destination on truck_destination.id=disbursement.disbursement_trucksid join products on products.id=disbursement.productid join store on store.id=disbursement.storeid join total_disbursement on total_disbursement.disbursementid=disbursement.id where truck_destination.trucknumber='$description' or truck_destination.destination='$description' order by truck_destination.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
     $temp=array("trucknumber"=>$row["trucknumber"],"storeid"=>$row["storeid"],"productid"=>$row["productid"],"quantity"=>$row["quantity"],"name"=>$row["name"],"units"=>$row["units"],"store_name"=>$row["store_name"],"total"=>$row["total"],"created_at"=>$row["created_at"],"id"=>$row["id"],"destination"=>$row["destination"]);
    array_push($data1,$temp);
    
   }
 }


}else{

$sql = "Select distinct truck_destination.trucknumber,truck_destination.id,disbursement.storeid,disbursement.productid,disbursement.quantity ,products.name,products.units,destination,store.name as store_name,total_disbursement.quantity as total,total_disbursement.created_at from disbursement join truck_destination on truck_destination.id=disbursement.disbursement_trucksid join products on products.id=disbursement.productid join store on store.id=disbursement.storeid join total_disbursement on total_disbursement.disbursementid=disbursement.id  order by truck_destination.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("trucknumber"=>$row["trucknumber"],"storeid"=>$row["storeid"],"productid"=>$row["productid"],"quantity"=>$row["quantity"],"name"=>$row["name"],"units"=>$row["units"],"store_name"=>$row["store_name"],"total"=>$row["total"],"created_at"=>$row["created_at"],"id"=>$row["id"],"destination"=>$row["destination"]);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1); 



?>