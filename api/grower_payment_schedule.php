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

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){

  $userid=$data->userid;
  $seasonid=$data->seasonid;
  $grower_num=$data->grower_num;
  $sale_no=$data->sale_no;
  $payment_batch=$data->payment_batch;
  $payee=$data->payee;
  $nid=$data->nid;
  $bank=$data->bank;
  $branch_code=$data->branch_code;
  $zwl_acc=$data->zwl_acc;
  $fca_acc=$data->fca_acc;
  $payment_ref=$data->payment_ref;
  $usd_actual_net_paid=$data->usd_actual_net_paid;
  $zim_actual_net_paid=$data->zim_actual_net_paid;
  $date=date_create($data->sale_date);
  $floor_id=$data->floor_id;

  $sale_date=date_format($date,"Y-m-d");



  $sql = "Select * from growers WHERE grower_num='$grower_num' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      $growerid=$row["id"];   
      
     }
   }



$sql ="Select * from grower_payment_schedule where  growerid=$growerid and sale_no=$sale_no  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $payment_found=$row["id"];
   
   }

 }



 if ($payment_found==0 && $growerid>0) {
  
   $user_sql = "INSERT INTO grower_payment_schedule(userid,seasonid,growerid,sale_no,payment_batch,payee,nid,bank,branch_code,zwl_acc,fca_acc,payment_ref,usd_actual_net_paid,zim_actual_net_paid,sale_date,floor_id) VALUES ($userid,$seasonid,$growerid,$sale_no,'$payment_batch','$payee','$nid','$bank','$branch_code','$zwl_acc','$fca_acc','$payment_ref',$usd_actual_net_paid,$zim_actual_net_paid,'$sale_date','$floor_id')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {

   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

  if ($payment_found>0) {
    $temp=array("response"=>"Already created");
    array_push($data1,$temp);
  }elseif($growerid==0){
    $temp=array("response"=>"Grower Not Found");
    array_push($data1,$temp);
  }

}



}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
