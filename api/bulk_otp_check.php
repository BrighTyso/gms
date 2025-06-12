<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$receiptnumber="";
$productid=0;
$loanid=0;
$quantity=0;
$newquantity=0;

$loan_found=1;
$truck_to_growerid=0;
$disbursement_trucksid=0;
$disbusment_quantity=0;
$created_at="";

$data1=array();


if (isset($data->userid) && isset($data->seasonid) && isset($data->otp)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$otp=$data->otp;
$security_otp_found=0;


$sql = "Select * from grower_bulk_edit_otp WHERE otp ='$otp' 
  AND created_at > NOW() - INTERVAL 30 MINUTE; ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {

    $security_otp_found=$row["id"];   
    
   }
 }


if ($security_otp_found>0) {
    // code...
    $temp=array("response"=>"success");
    array_push($data1,$temp);

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





