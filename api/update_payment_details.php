<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$name="";
$surname="";
$grower_num="";
$area="";
$province="";
$phone="";
$id_num="";
$id=0;

$data1=array();


if (isset($data->userid)){


$userid=$data->userid;
$id=$data->id;


$sale_no=$data->sale_no;
$payee=$data->payee;
$nid=$data->nid;
$bank=$data->bank;
$zwl_acc=$data->zwl_acc;
$fca_acc=$data->fca_acc;
$usd_actual_net_paid=$data->usd_actual_net_paid;
$zim_actual_net_paid=$data->zim_actual_net_paid;

 
 

 $user_sql1 = "update grower_payment_schedule set sale_no='$sale_no',payee='$payee',nid='$nid',bank='$bank',zwl_acc='$zwl_acc',fca_acc='$fca_acc',usd_actual_net_paid='$usd_actual_net_paid',zim_actual_net_paid='$zim_actual_net_paid' where id=$id";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success");
    array_push($data1,$temp);

     
    }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);

    }

  }else{


    $temp=array("response"=>"Field Empty");
    array_push($data1,$temp);

  }




echo json_encode($data1);

?>





