<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;

$data1=array();

if (isset($data->userid)){

//$userid=$data->userid;
//$description=$data->description;

$userid=$data->userid;
$scheme_hectaresid=$data->scheme_hectaresid;
$productid=$data->productid;
$quantity=$data->quantity;

$sql = "Select * from scheme_hectares_products where  scheme_hectaresid=$scheme_hectaresid and productid=$productid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }




if ($found==0) {
  
$user_sql = "INSERT INTO scheme_hectares_products(userid,scheme_hectaresid,productid,quantity) VALUES ($userid,$scheme_hectaresid,$productid,'$quantity')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}


}else{

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























