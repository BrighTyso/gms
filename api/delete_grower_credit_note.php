<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();


if (isset($data->productid) && isset($data->userid) && isset($data->otp)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=$data->productid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;
$growerid=0;
$loan_credit_note_found=0;
$product_name="";


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




 $sql = "Select * from loan_credit_note join products on products.id=loan_credit_note.productid where  (loan_credit_note.seasonid=$seasonid  and loan_credit_note.growerid=$growerid and loan_credit_note.productid=$productid)";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
     #$verifyLoan=1;
      $loan_credit_note_found=$row["id"];
      $product_name=$row["name"];
      
     }
   }



if ($growerid>0 && $loan_credit_note_found>0) {
 

  $user_sql1 = "DELETE FROM loan_credit_note where productid=$productid and seasonid=$seasonid and growerid=$growerid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {


            $sql = "Select * from operations_contacts where active=1";
              $result = $conn->query($sql);
               
               if ($result->num_rows > 0) {
                 // output data of each row
                 while($row = $result->fetch_assoc()) {
                  $phone=$row["phone"];
                  $contact_email=$row["email"];
                  $to = $contact_email; 
                  $subject = $grower_num." Credit Note Deleted";
                  $txt = "User ".$username." deleted credit note for grower (".$grower_num.")\n product(".$product_name.") \n with otp : ".$otp;
                  $headers = "From: warehouse@coreafricagrp.com";
                  mail($to,$subject,$txt,$headers);
                 }

               }



        $temp=array("response"=>"success");
        array_push($data1,$temp);
          
      }else{
        $temp=array("response"=>$conn->error);
        array_push($data1,$temp);
      }
   
  }else{
    $temp=array("response"=>"Credit Not Found");
  array_push($data1,$temp);
  }

}else{
  $temp=array("response"=>"OTP Expired(Not Found)");
  array_push($data1,$temp);
}

}else{
  $temp=array("response"=>"Field Empty");
  array_push($data1,$temp);
}



echo json_encode($data1);

?>





