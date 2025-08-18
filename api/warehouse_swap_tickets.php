<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$min_mass=$data->min_mass;
$max_mass=$data->max_mass;
$min_price=$data->min_price;
$max_price=$data->max_price;
$barcode=$data->barcode;


$sql = "SELECT * FROM warehousing_sold_bales_reclassification join warehousing_sold_bales on warehousing_sold_bales.id=warehousing_sold_bales_reclassification.warehousing_sold_balesid WHERE  (warehousing_sold_bales.price between $min_price and $max_price) and (warehousing_sold_bales.mass between $min_mass and $max_mass) and (warehousing_sold_bales_reclassification.buyer_grade='VX7O' or warehousing_sold_bales_reclassification.buyer_grade='VX6O') and (warehousing_sold_bales.barcode!='$barcode') ORDER BY RAND() limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
      $temp=array("response"=>"success");
      array_push($response,$temp);

  
   }

 }



}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





