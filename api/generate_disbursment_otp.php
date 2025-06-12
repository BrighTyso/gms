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
$quantity_in_store=0;
$product="";
$store="";
$location="";
$contact_email="";
$data1=array();
$storeData=array();
$contact_data=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
$storeid=$data->storeid;
$productid=$data->productid;
$quantity=$data->quantity;

$sql = "SELECT FLOOR(RAND() * 1000000) AS otp_code;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $otp=$row["otp_code"];
   
   }

 }




 $sql = "Select quantity,store.name as store_name,location,products.name as product_name from store_items join products on products.id=store_items.productid join store on store.id=store_items.storeid WHERE store_items.productid=$productid AND store_items.storeid =$storeid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $quantity_in_store=$row["quantity"];
    $product=$row["product_name"];
    $store=$row["store_name"];
    $location=$row["location"];


    $temp=array("quantity_in_store"=>$quantity_in_store,"product"=>$product,"store"=>$store,"location"=>$location);
    array_push($storeData,$temp);
    
   }
 }



 $sql = "Select * from disbursement_otp where  otp='$otp' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }


 


 if ($found==0) {
  
   $user_sql = "INSERT INTO disbursement_otp(userid,storeid,productid,quantity,otp) VALUES ($userid,$storeid,$productid,$quantity,'$otp')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
       
       $subject = "";
            $txt="";
            $subject="";
            $contacts=array();
       $sql = "Select * from operations_contacts where  active=1";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {

          $temp1=array("phone"=>$row["phone"]);
                  array_push($contacts,$temp1);

          $phone=$row["phone"];
          $contact_email=$row["email"];

          $to = $contact_email;
          $subject = "Disbursement OTP";
          $txt = "Disbursement Product Details\n\n\n"."Store:".$store."\nProduct:".$product."\n"."Quantity:".$quantity."\nOTP:".$otp;
          $headers = "From: gmsotp@coreafricagrp.com";

          mail($to,$subject,$txt,$headers);

          
          // $temp=array("to"=>$phone);
          //  array_push($contact_data,$temp);
         
         }

       }
      
      

    
   
    $temp=array("response"=>"success","message"=>$txt,"otp"=>$otp_production,"contacts"=>$contacts,"subject"=>$subject);
           array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{

$temp=array("response"=>"Could not Generate OTP");
array_push($data1,$temp);

}




}

echo json_encode($data1);

?>
