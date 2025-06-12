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
$security_otp_found=0;
$fetched_records=0;
$processed_records=0;
$found_production=0;
$otp_production="";

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
$seasonid=$data->seasonid;
$purchasing_orderid=$data->purchasing_orderid;
$storeid=$data->storeid;

$purchase_order_products=0;
$field_officer_name="";
$store_name="";




$sql = "Select * from store WHERE id=$storeid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $store_name=$row["name"];   
    
   }
 }




$sql = "Select * from users WHERE id=$userid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $username=$row["username"];   
    
   }
 }



$sql = "Select * from purchasing_order join purchasing_order_products on purchasing_order_products.purchasing_orderid=purchasing_order.id WHERE purchasing_order.id=$purchasing_orderid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $purchase_order_products=$row["id"];   
    
   }
 }





$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $otp_production=$row["otp_code"];
   
   }

 }



 $sql = "Select * from purchase_order_otp where  otp='$otp_production' and purchasing_orderid=$purchasing_orderid and storeid=$storeid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $found_production=$row["id"];
   
   }

 }



 if ($found_production==0 && $purchasing_orderid>0 && $purchase_order_products>0) {
  
   $user_sql = "INSERT INTO purchase_order_otp(userid,seasonid,otp,purchasing_orderid,storeid) VALUES ($userid,$seasonid,'$otp_production',$purchasing_orderid,$storeid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {


            $intro = "User ".$username." has requested Purchase Order OTP With the following products\n";
            $txt="";
            $purchase_order_products="";
            $purchase_order_quantity=0;
            $product_description="";

            $subject = "";
            $txt="";
            $subject="";
            $contacts=array();

            $sql = "Select products.name,quantity from purchasing_order join purchasing_order_products on purchasing_order_products.purchasing_orderid=purchasing_order.id join products on products.id=purchasing_order_products.productid WHERE purchasing_order.id=$purchasing_orderid ";
            $result = $conn->query($sql);
             
             if ($result->num_rows > 0) {
               // output data of each row
               while($row = $result->fetch_assoc()) {

                $purchase_order_products=$row["name"];
                $purchase_order_quantity=$row["quantity"];

                $intro=$intro."\n".$purchase_order_products." ".$purchase_order_quantity;
                
               }
             }

             $txt=$intro."\nfor ".$store_name."(warehouse)"."\nOTP : ".$otp_production;


            $sql = "Select * from operations_contacts where active=1";
              $result = $conn->query($sql);
               
               if ($result->num_rows > 0) {
                 // output data of each row
                 while($row = $result->fetch_assoc()) {

                  $temp1=array("phone"=>$row["phone"]);
                  array_push($contacts,$temp1);

                  $phone=$row["phone"];
                  $contact_email=$row["email"];
                  $to = $contact_email; 
                  $subject = "Product Purchase Order OTP";

                  $headers = "From: gmsotp@coreafricagrp.com";
                  mail($to,$subject,$txt,$headers);
                 }

               }
   
     $temp=array("response"=>"success","message"=>$txt,"otp"=>$otp_production,"contacts"=>$contacts,"subject"=>$subject);
           array_push($data1,$temp);

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

  if ($found_production>0) {
    $temp=array("response"=>"OTP Already Created");
    array_push($data1,$temp);
  }elseif($purchasing_orderid==0){
    $temp=array("response"=>"Grower Not Found");
    array_push($data1,$temp);
  }else{
    $temp=array("response"=>"No Products Found");
    array_push($data1,$temp);
  }

}



}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
