<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$growerid=$data->growerid;
$seasonid=$data->seasonid;


$data1=array();

//http://192.168.1.190/gms/api/get_products.php


  
$sql = "Select grower_age,grower_sex,number_of_works,income_per_month,number_of_kids,created_at from data_collection where growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("grower_age"=>$row["grower_age"],"grower_sex"=>$row["grower_sex"],"number_of_works"=>$row["number_of_works"],"income_per_month"=>$row["income_per_month"],"number_of_kids"=>$row["number_of_kids"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }





 echo json_encode($data1); 



?>