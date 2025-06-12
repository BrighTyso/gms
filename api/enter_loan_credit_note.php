<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$found=0;
$disbusment_quantity=0;

$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num) && isset($data->otp)){


$seasonid=$data->seasonid;
$userid=$data->userid;
$grower_num=$data->grower_num;
$growerid=0;
$created_at=$data->created_at;
$productid=$data->productid;
$quantity=$data->quantity;

$otp=$data->otp;
$otp_found=0;

$grower_id=0;


$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];
      
   }

 }





$sql = "Select * from grower_edit_otp where  otp='$otp' and growerid=$growerid  AND created_at > NOW() - INTERVAL 30 MINUTE limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $otp_found=$row["id"];
   
   }

 }

 if ($otp_found>0) {






 $sql = "Select * from loans where  (loans.seasonid=$seasonid  and growerid=$growerid and processed=1 and productid=$productid)";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
     #$verifyLoan=1;
      $disbusment_quantity+=$row["quantity"];
      
     }
   }

  $credit_note_quantity=0;

  $sql = "Select * from loan_credit_note join products on products.id=loan_credit_note.productid where  (loan_credit_note.seasonid=$seasonid  and loan_credit_note.growerid=$growerid and loan_credit_note.productid=$productid)";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
     #$verifyLoan=1;
      $credit_note_quantity+=$row["quantity"];
      
     }
   }


   $q_data=$disbusment_quantity-$credit_note_quantity;


   if ($q_data>=$quantity && $disbusment_quantity>0 && $quantity>0) {

     $user_sql = "INSERT INTO loan_credit_note(userid,seasonid,growerid,productid,quantity,created_at) VALUES ($userid,$seasonid,$growerid,$productid,$quantity,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;



            $sql = "Select * from operations_contacts where active=1";
              $result = $conn->query($sql);
               
               if ($result->num_rows > 0) {
                 // output data of each row
                 while($row = $result->fetch_assoc()) {
                  $phone=$row["phone"];
                  $contact_email=$row["email"];
                  $to = $contact_email; 
                  $subject = $grower_num." Credit Note recorded";
                  $txt = "User ".$username." Created credit note for grower (".$grower_num.")\n product(".$product_name.") \n with otp : ".$otp;
                  $headers = "From: warehouse@coreafricagrp.com";
                  mail($to,$subject,$txt,$headers);
                 }

               }



       $temp=array("response"=>"success");
       array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }


   }else{

    $temp=array("response"=>"Returning more than received");
    array_push($response,$temp);

   }



}else{
  $temp=array("response"=>"OTP Expired(Not Found)");
  array_push($response,$temp);
}
}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





