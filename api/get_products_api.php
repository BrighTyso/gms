<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$productid=0;
$amount="";
$seasonid=0;
$created_at="";
$found=0;
$processed_found=0;
$response=array();

if (isset($data->userid)){

$userid=$data->userid;

$sql = "Select products.name,units,package_units,products.id,product_type.name as product_type_name from products join product_type on product_type.id=products.product_typeid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"units"=>$row["units"],"package_units"=>$row["package_units"],"package_units"=>$row["package_units"],"productid"=>$row["id"],"product_type_name"=>$row["product_type_name"]);
    array_push($response,$temp);
    
   }
 }


}



echo json_encode($response);

?>





