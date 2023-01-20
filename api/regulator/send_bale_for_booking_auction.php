<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();


$response=array();

if(isset($_POST["qrcode"]) && isset($_POST["userid"]) && isset($_POST["barcode"])&& isset($_POST["created_at"])) {


$ready_for_bookingid=0;
$qrcode=$_POST["qrcode"];
$userid=$_POST["userid"];
$barcode=$_POST["barcode"];
$created_at=$_POST["created_at"];
$grower_number_of_balesid=0;
$booked=0;
$status=0;
$bale_tagid=0;
$bale_tag_to_sold_bales=0;
$seasonid=0;
$rejected_bales_found=0;


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



if ($booked==0 && $bale_tagid>0) {

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

  }

}

	
}else{

   $temp=array("response"=>"Field Empty");
   array_push($response,$temp);
}


echo json_encode($response);
?>