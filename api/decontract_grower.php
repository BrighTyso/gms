<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid="";
$productid="";
$variableid="";
$variable_found=0;

$response=array();

if (isset($data->userid) && isset($data->productid)  && isset($data->variableid)){


$userid=$data->userid;
$productid=$data->productid;
$variableid=$data->variableid;



$sql = "Select * from machine_learning_products where productid=$productid and machine_learning_product_variablesid=$variableid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $variable_found=$row["id"];

  
   }

 }



if ($variable_found>0) {
  

$temp=array("response"=>"Variable already Created");
 array_push($response,$temp);


}else{

$user_sql = "INSERT INTO machine_learning_products(productid,machine_learning_product_variablesid) VALUES ($productid,$variableid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

      $temp=array("response"=>"success");
      array_push($response,$temp);
     
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }

 }


}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





