<?php

require_once("conn.php");
require "validate.php";

$userid=0;
$id=0;

$data1=array();


if (isset($_POST["barcode"]) && isset($_POST["grower_num"]) && isset($_POST["mass"])){


$barcode=$_POST["barcode"];
$grower_num=$_POST["grower_num"];
$mass=$_POST["mass"];
$lot=$_POST["lot"];
$sell_date=$_POST["sell_date"];
$batch_no=$_POST["batch_no"];
 

 $user_sql1 = "update bale_counting_redo set new_mass=$mass,grower_num='$grower_num',lot='$lot',sell_date='$sell_date',batch_no=$batch_no where barcode='$barcode'";
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





