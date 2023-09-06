<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$disbursement_trucksid=$data->disbursement_trucksid;
$productid=$data->productid;


$data1=array();

//http://192.168.1.190/gms/api/get_products.php


  
$sql = "Select distinct grower_num,growers.name,surname,truck_to_grower.quantity,products.name as product_name,receipt_number,loans.id from growers join truck_to_grower on growers.id=truck_to_grower.growerid join products on truck_to_grower.productid=products.id  join truck_destination on truck_destination.id=truck_to_grower.disbursement_trucksid join disbursement on disbursement.disbursement_trucksid=truck_destination.id join loans on truck_to_grower.loanid=loans.id where truck_to_grower.productid=$productid and truck_to_grower.disbursement_trucksid=$disbursement_trucksid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"quantity"=>$row["quantity"] ,"product_name"=>$row["product_name"],"receipt_number"=>$row["receipt_number"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }





 echo json_encode($data1); 



?>