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
$grower_num="";
$sell_date="";
$lot="";



$sql = "Select * from warehousing_sold_bales where barcode='$barcode' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $grower_num=$row["grower_num"];
   $sell_date=$row["sell_date"];
   $lot=$row["lot"];

  
   }

 }


 if ($grower_num=="") {
   $sql = "Select * from warehousing_sold_bales order by rand() limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $grower_num=$row["grower_num"];
     $sell_date=$row["sell_date"];
   $lot=$row["lot"];

  
   }

 }
 }

 

 $user_sql1 = "update bale_counting_redo set barcode='$new_barcode',old_barcode='$barcode',swapped=1 where barcode='$barcode'";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success","barcode"=>$new_barcode,"old_barcode"=>$barcode,"grower_num"=>$grower_num,"lot"=>$lot,"sell_date"=>$sell_date);
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





