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
$payment_found=0;
$growerid=0;
$payment_details_found=0;

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){

  $userid=$data->userid;
  $seasonid=$data->seasonid;
  $currencyid=$data->currencyid;
  $bank_detailsid=$data->bank_detailsid;
  $grower_payment_scheduleid=$data->grower_payment_scheduleid;
  $account=$data->account;




  $sql ="Select * from payment_schedule_captured where  grower_payment_scheduleid=$grower_payment_scheduleid";
  $result = $conn->query($sql);
   if ($result->num_rows > 0) {
     
    $temp=array("response"=>"Already Created");
    array_push($data1,$temp);

   }else{

  $sql ="Select * from payment_schedule_grower_ecocash_details where  grower_payment_scheduleid=$grower_payment_scheduleid and bank_detailsid=$bank_detailsid and currencyid=$currencyid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
     $payment_details_found=$row["id"];
     
     }

   }



 if ($payment_details_found==0) {
  
   $user_sql = "INSERT INTO payment_schedule_grower_ecocash_details(userid,seasonid,bank_detailsid,currencyid,grower_payment_scheduleid,account) VALUES ($userid,$seasonid,$bank_detailsid,$currencyid,$grower_payment_scheduleid,'$account')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {

   
        $user_sql = "INSERT INTO payment_schedule_captured(userid,seasonid,grower_payment_scheduleid) VALUES ($userid,$seasonid,$grower_payment_scheduleid)";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
            $temp=array("response"=>"success");
           array_push($data1,$temp);
         }

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

     $temp=array("response"=>"Already created");
     array_push($data1,$temp);

}

}

}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
