<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));



$response=array();

if (isset($data->userid) && isset($data->productid)){

  $userid=$data->userid;
  $scheme_hectaresid=$data->scheme_hectaresid;
  $productid=$data->productid;
  $seasonid=$data->seasonid;

 
  $created_at=date("Y-m-d");

$found=0;

 $sql = "Select * from scheme_hectares_products where productid=$productid and scheme_hectaresid=$scheme_hectaresid  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $found=$row["id"];
 
     
   }

 }


if ($found>0) {


$user_sql = "delete from scheme_hectares_products where productid=$productid and scheme_hectaresid=$scheme_hectaresid ";
 //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {

      $temp=array("response"=>"success");
      array_push($response,$temp);

     }else{

      $temp=array("response"=>$conn->error);
      array_push($response,$temp);

     }

 }else{
    $temp=array("response"=>"Product not found");
      array_push($response,$temp);
 }
                   
                
}else{

$temp=array("response"=>"Field Empty");
array_push($response,$temp);

}







 echo json_encode($response);






?>





