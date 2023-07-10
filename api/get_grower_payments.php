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

//http://192.168.1.190/gms/api/get_products.php

  
$sql = "Select * from loan_payments join growers on growers.id=loan_payments.growerid where loan_payments.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"amount"=>$row["amount"],"mass"=>$row["mass"],"description"=>$row["description"],"receipt_number"=>$row["receipt_number"]);
    array_push($data1,$temp);
    
   }
 }







 echo json_encode($data1); 



?>