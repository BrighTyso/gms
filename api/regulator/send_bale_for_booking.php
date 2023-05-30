<?php
require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DataSource();


$response=array();

if(isset($_POST["qrcode"]) && isset($_POST["userid"]) && isset($_POST["barcode"])&& isset($_POST["created_at"])&& isset($_POST["company_to_selling_pointid"])&& isset($_POST["company_userid"])) {


$ready_for_bookingid=0;
$qrcode=$_POST["qrcode"];
$userid=$_POST["userid"];
$barcode=$_POST["barcode"];
$created_at=$_POST["created_at"];

$company_userid=$_POST["company_userid"];
$company_to_selling_pointid=$_POST["company_to_selling_pointid"];


$grower_number_of_balesid=0;
$booked=0;
$status=0;
$bale_tagid=0;
$bale_tag_to_sold_bales=0;
$seasonid=0;
$rejected_bales_found=0;
$received_by_user=0;


 $grower_number_of_balesid=$datasource->encryptor("decrypt",$qrcode);



$sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
    // $seasonid=$row["id"]; 
   $seasonid=$row["id"];

   }

 }



$sql = "Select * from bale_tags where code='$barcode' and grower_number_of_balesid=$grower_number_of_balesid and used=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
    // $seasonid=$row["id"]; 
   $status=$row["used"];
   $bale_tagid=$row["id"];
  
   }

 }





$sql22 = "Select * from bale_receiver where bale_tagid=$bale_tagid and company_to_selling_pointid=$company_to_selling_pointid and userid=$userid";
$result = $conn->query($sql22);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
    // $seasonid=$row["id"]; 
   $received_by_user=$row["id"];
  
   }

 }




 $sql = "Select * from bale_booked where bale_tagid=$bale_tagid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
    // $seasonid=$row["id"]; 
   $booked=$row["id"];
  
   }

 }



if ($booked==0 && $bale_tagid>0 && $received_by_user>0) {

  $grower_farm_sql = "INSERT INTO bale_booked(userid,bale_tagid,created_at) VALUES ($userid,$bale_tagid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {

         $temp=array("response"=>"success");
          array_push($response,$temp);

     }else{

      $temp=array("response"=>$conn->error);
      array_push($response,$temp);

     }

}else{

  if ($booked>0) {

      $temp=array("response"=>"Bale Already Sent For Booking");
     array_push($response,$temp);

  }else if ($bale_tagid==0){

      $temp=array("response"=>"Bale Tag Not Found or Not Yet Received ");
      array_push($response,$temp);

  }else if ($received_by_user==0) {
    
      $temp=array("response"=>"Bale Tag Was Not Received By User");
      array_push($response,$temp);

  }

}

	
}else{

   $temp=array("response"=>"Field Empty");
   array_push($response,$temp);
}


echo json_encode($response);
?>