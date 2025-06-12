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
$productid=$data->productid;
$created_at=$data->created_at;
$quantity=$data->quantity;


$sql = "Select * from custom_disbursement_product_quantity where productid=$productid and created_at='$created_at' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0) {
  
$user_sql = "INSERT INTO custom_disbursement_product_quantity(userid,productid,quantity,created_at) VALUES ($userid,$productid,$quantity,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
    
      $temp=array("response"=>"success");
       array_push($data1,$temp);
       

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{

 $user_sql = "update custom_disbursement_product_quantity set quantity=$quantity where productid=$productid and created_at='$created_at'";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     $temp=array("response"=>"success");
       array_push($data1,$temp);

   }else{

     $temp=array("response"=>$conn->error);
       array_push($data1,$temp);

   }
}


}else{

   $temp=array("response"=>"field empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























