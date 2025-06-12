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

$userid=$data->userid;
$purchasing_orderid=$data->purchasing_orderid;
$productid=$data->productid;
$quantity=$data->quantity;
$unit_price=$data->unit_price;
$created_date=$data->created_date;


$sql = "Select * from purchasing_order_products where productid=$productid and purchasing_orderid=$purchasing_orderid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0) {
  
$user_sql = "INSERT INTO purchasing_order_products(userid,purchasing_orderid,productid,quantity,unit_price,created_date) VALUES ($userid,$purchasing_orderid,$productid,$quantity,'$unit_price','$created_date')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
    
         $temp=array("response"=>"success");
          array_push($data1,$temp);
          

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{
  $temp=array("response"=>"Already Created");
     array_push($data1,$temp);
}


}else{

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























