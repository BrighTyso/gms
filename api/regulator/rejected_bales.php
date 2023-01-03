<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();

if(isset($_GET("qrcode")) && isset($_GET("userid")) && isset($_GET("bales")) && isset($_GET("latitude")) && isset($_GET("longitude"))  && isset($_GET("sell_date")) && isset($_GET("companyid")) && isset($_GET("created_at"))) {


$ready_for_bookingid=0;
$qrcode=$_GET("qrcode");
$userid=$_GET("userid");
$bales=$_GET("bales");
$latitude=$_GET("latitude");
$longitude=$_GET("longitude");
$sell_date=$_GET("sell_date");
$companyid=$_GET("companyid");
$created_at=$_GET("created_at");
$grower_number_of_balesid=0;
$user_found=0;
$seasonid=0;
$rejected_bales_found=0;


 $grower_number_of_balesid=$datasource->encryptor("decrypt",$qrcode);


$sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
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
	
	//check if sold for that date
	 $sql = "Select * from ready_for_booking where grower_number_of_balesid=$grower_number_of_balesid  and  sell_date='$sell_date' limit 1";
	$result = $conn->query($sql);
	 
	 if ($result->num_rows > 0) {
	   // output data of each row
	   while($row = $result->fetch_assoc()) {

	    // product id
	  
	   $ready_for_bookingid=$row["id"];

	  
	   }

	 }


		if ($ready_for_bookingid>0) {
		
				// check if is already rejected
				 $sql = "Select * from rejected_bales where companyid=$companyid and useridrights=$userid and seasonid=$seasonid and ready_for_bookingid='$ready_for_bookingid' limit 1";
				$result = $conn->query($sql);
				 
				 if ($result->num_rows > 0) {
				   // output data of each row
				   while($row = $result->fetch_assoc()) {

				    // product id
				  
				   $rejected_bales_found=$row["id"];

				  
				   }

				 }


				 if ($rejected_bales_found==0) {
				 	
						$user_sql = "INSERT INTO rejected_bales_rights(userid,companyid,useridrights,seasonid,created_at) VALUES ($userid,$companyid,$useridrights,$seasonid,'$created_at')";
						   //$sql = "select * from login";
						   if ($conn->query($user_sql)===TRUE) {
						   
						     $last_id = $conn->insert_id;
						       $user_sql1 = "update grower_number_of_bales set bales=bales + 1 where id=$grower_number_of_balesid";
							   //$sql = "select * from login";
							   if ($conn->query($user_sql1)===TRUE) {

							    $temp=array("response"=>"success");
							    array_push($data1,$temp);

							     
							    }

						   }else{

						   $temp=array("response"=>$conn->error);
						   array_push($response,$temp);

						   }

				 }else{

				 	$temp=array("response"=>"Already Rejected");
					array_push($response,$temp);
				 }






	}else{

			$temp=array("response"=>"Grower Did Not Sell For That Date");
			array_push($response,$temp);

	}



}else{


$temp=array("response"=>"You Have No Rights To Reject Bales");
array_push($response,$temp);

}




	
}
?>