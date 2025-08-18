<?php

require_once("conn.php");
//require "validate.php";

$userid=0;
$id=0;

$data1=array();


if (isset($_GET["id"]) && isset($_GET["mass"]) && isset($_GET["lot"])){


$userid=$_GET["userid"];
$id=$_GET["id"];
$mass=$_GET["mass"];
$lot=$_GET["lot"];
 

 $user_sql1 = "update truck_to_processing_bales set mass='$mass',lot='$lot' where warehousing_sold_bales_reclassificationid=$id";
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





