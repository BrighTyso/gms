<?php

require_once("conn.php");
//require "validate.php";

$userid=0;
$id=0;

$data1=array();


if (isset($_POST["userid"]) && isset($_POST["new_barcode"]) && isset($_POST["barcode"])){


$userid=$_POST["userid"];
$new_barcode=$_POST["new_barcode"];
$barcode=$_POST["barcode"];
 

 $user_sql1 = "update bale_counting_redo set barcode='$new_barcode',swapped=1 where barcode='$barcode'";
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





