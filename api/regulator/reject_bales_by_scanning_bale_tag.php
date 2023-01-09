<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();


$response=array();

if(isset($_POST["qrcode"]) && isset($_POST["userid"]) && isset($_POST["barcode"]) && isset($_POST["companyid"])) {


$ready_for_bookingid=0;
$qrcode=$_POST["qrcode"];
$userid=$_POST["userid"];
$barcode=$_POST["barcode"];
$companyid=$_POST["companyid"];
$grower_number_of_balesid=0;
$user_found=0;
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

	
$sql = "Select * from bale_tags where code=$barcode and grower_number_of_balesid=$grower_number_of_balesid and used=1";
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





if ($user_found>0 && $bale_tag_to_sold_bales==0 && $bale_tagid>0 ) {

 			$user_sql1 = "update grower_number_of_bales set bales=bales + 1 where id=$grower_number_of_balesid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {

             $user_sql1 = "update bale_tags set used=0 where id=$bale_tagid";
               //$sql = "select * from login";
               if ($conn->query($user_sql1)===TRUE) {

                $temp=array("response"=>"success");
                array_push($response,$temp);

           
                }
             
           }

}else{


$temp=array("response"=>"You Have No Rights To Reject Bales");
array_push($response,$temp);

}


}else{

$temp=array("response"=>"You Have No Rights For This Action");
array_push($response,$temp);

}

	
}


echo json_encode($response);
?>