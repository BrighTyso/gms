<?php
require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DataSource();


$response=array();

if(isset($_POST["qrcode"]) && isset($_POST["userid"]) && isset($_POST["barcode"])) {


$ready_for_bookingid=0;
$qrcode=$_POST["qrcode"];
$userid=$_POST["userid"];
$barcode=$_POST["barcode"];
$companyid=0;
// description , company name 
$grower_number_of_balesid=0;
$user_found=0;
$booked=0;
$status=0;
$bale_tagid=0;
$bale_tag_to_sold_bales=0;
$seasonid=0;
$rejected_bales_found=0;
$booking_company=0;
$bale_receiverid=0;

$created_at=date("Y-m-d");

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



	
$sql = "Select * from bale_tags where code='$barcode' and grower_number_of_balesid=$grower_number_of_balesid and used=1 and seasonid=$seasonid";
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



$sql = "Select * from bale_receiver where bale_tagid=$bale_tagid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $companyid=$row["userid"];
   $bale_receiverid=$row["id"];

  
   }

 }



 $sql = "Select * from rejected_bales_rights where companyid=$companyid and useridrights=$userid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"]; 

  
   }

 }



 if ($user_found>0) {



$sql = "Select * from bale_booked where bale_tagid=$bale_tagid  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $booked=$row["id"]; 
   $booking_company=$row["userid"];
  
   }

 }






$sql = "Select * from bale_tag_to_sold_bale where bale_tagid=$bale_tagid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
    // $seasonid=$row["id"]; 
   $bale_tag_to_sold_bales=$row["id"];
	
  
   }

 }





if ($user_found>0 && $bale_tag_to_sold_bales==0 && $bale_tagid>0 && $booked>0 && $bale_receiverid>0) {


  $user_sql1 = "DELETE FROM bale_booked where id = $booked";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $user_sql1 = "DELETE FROM bale_receiver where id = $bale_receiverid";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

 			      $user_sql1 = "update grower_number_of_bales set bales=bales + 1 where id=$grower_number_of_balesid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {

             $user_sql1 = "update bale_tags set used=0 where id=$bale_tagid";
               //$sql = "select * from login";
               if ($conn->query($user_sql1)===TRUE) {


                $insert_sql = "INSERT INTO bale_rejected(userid,bale_tagid,created_at) VALUES ($userid,$bale_tagid,'$created_at')";
                  //$gr = "select * from login";
                     if ($conn->query($insert_sql)===TRUE) {

                        $temp=array("response"=>"success");
                        array_push($response,$temp);

                     }
           
                }

            }


         }
       }

}else{

  if ($bale_tag_to_sold_bales>0) {

        $temp=array("response"=>"This Action is not allowed on this bale tag .Bale Already Sold.");
        array_push($response,$temp);

  }else if ($bale_tagid==0){

        $temp=array("response"=>"Bale Tag Not Found.");
        array_push($response,$temp);

  }else if ($booked==0) {

       $temp=array("response"=>"This Bale Tag Was Never Booked.Barcode Cant Be Rejected.");
        array_push($response,$temp);

  }else if ($user_found==0) {

      $temp=array("response"=>"User Have No Right To Reject This Bale.");
        array_push($response,$temp);

  }


}


}else{

$temp=array("response"=>"User Have No Right To Reject This Bale.");
array_push($response,$temp);

}

	
}else{

  $temp=array("response"=>"Field Empty.");
  array_push($response,$temp);
  
}


echo json_encode($response);




?>