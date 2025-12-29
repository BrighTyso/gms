<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$description=$data->description;
$seasonid=$data->seasonid;


$data1=array();

//http://192.168.1.190/gms/api/get_products.php

if ($description!="") {
  
$sql = "Select distinct truck_destination.trucknumber,truck_destination.driver_name,truck_destination.driver_surname,truck_destination.destination,truck_destination.id,disbursement.storeid,disbursement.productid,disbursement.quantity ,products.name,products.units,destination,store.name as store_name,total_disbursement.quantity as total,total_disbursement.created_at from disbursement join truck_destination on truck_destination.id=disbursement.disbursement_trucksid join products on products.id=disbursement.productid join store on store.id=disbursement.storeid join total_disbursement on total_disbursement.disbursementid=disbursement.id where truck_destination.trucknumber='$description' or truck_destination.destination='$description' order by truck_destination.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $productid=$row['productid'];
    $disbursement_truckid=$row['id'];
    $product_captured=0;




    $sql1l = "Select distinct disbursed_products_grower_truck.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area,products.name as product_name,disbursed_products_grower_truck.quantity,units,package_units,disbursed_products_grower_truck.created_at, users.username,farmer_comment,adjustment_quantity,adjust from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid  and products.id=$productid order by grower_num ";
    $result1l = $conn->query($sql1l);
     
     if ($result1l->num_rows > 0) {
       // output data of each row
       while($row1l = $result1l->fetch_assoc()) {
        $product_captured+=$row1l["quantity"];
       }
     }



     $temp=array("trucknumber"=>$row["trucknumber"],"storeid"=>$row["storeid"],"productid"=>$row["productid"],"quantity"=>$row["quantity"],"name"=>$row["name"],"units"=>$row["units"],"store_name"=>$row["store_name"],"total"=>$row["total"],"created_at"=>$row["created_at"],"id"=>$row["id"],"destination"=>$row["destination"],"driver_name"=>$row["driver_name"],"driver_surname"=>$row["driver_surname"],"destination"=>$row["destination"],"product_captured"=>$product_captured);
    array_push($data1,$temp);
    
   }
 }


}else{

$sql = "Select distinct truck_destination.trucknumber,truck_destination.driver_name,truck_destination.driver_surname,truck_destination.destination,truck_destination.id,disbursement.storeid,disbursement.productid,disbursement.quantity ,products.name,products.units,destination,store.name as store_name,total_disbursement.quantity as total,total_disbursement.created_at from disbursement join truck_destination on truck_destination.id=disbursement.disbursement_trucksid join products on products.id=disbursement.productid join store on store.id=disbursement.storeid join total_disbursement on total_disbursement.disbursementid=disbursement.id  order by truck_destination.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $productid=$row['productid'];
    $disbursement_truckid=$row['id'];
    $product_captured=0;




    $sql1l = "Select distinct disbursed_products_grower_truck.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area,products.name as product_name,disbursed_products_grower_truck.quantity,units,package_units,disbursed_products_grower_truck.created_at, users.username,farmer_comment,adjustment_quantity,adjust from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid  and products.id=$productid order by grower_num ";
    $result1l = $conn->query($sql1l);
     
     if ($result1l->num_rows > 0) {
       // output data of each row
       while($row1l = $result1l->fetch_assoc()) {
        $product_captured+=$row1l["quantity"];
       }
     }



     $temp=array("trucknumber"=>$row["trucknumber"],"storeid"=>$row["storeid"],"productid"=>$row["productid"],"quantity"=>$row["quantity"],"name"=>$row["name"],"units"=>$row["units"],"store_name"=>$row["store_name"],"total"=>$row["total"],"created_at"=>$row["created_at"],"id"=>$row["id"],"destination"=>$row["destination"],"driver_name"=>$row["driver_name"],"driver_surname"=>$row["driver_surname"],"destination"=>$row["destination"],"product_captured"=>$product_captured);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1); 



?>