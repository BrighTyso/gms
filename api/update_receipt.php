<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$receiptnumber="";
$loanid=0;

$data1=array();


if (isset($data->userid) && isset($data->receiptnumber) && isset($data->loanid)){

 $userid=$data->userid;
 $receiptnumber=validate($data->receiptnumber);
 #$created_at=$data->created_at;
 $loanid=$data->loanid;

 $user_sql1 = "update loans set receipt_number='$receiptnumber' where id=$loanid";
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





