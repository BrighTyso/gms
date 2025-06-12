<?php

require "conn.php";
require "validate.php";

$found_store=0;
$user_found=0;
$bale_received=0;

$response=array();

if (isset($_GET['userid'])){

$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];
$warehousing_sold_balesid=$_GET['warehousing_sold_balesid'];
$mass=$_GET['mass'];
$created_at=$_GET['created_at'];

$date = new DateTime();
$datetimes=$_GET['datetimes'];

$sql = "Select * from warehousing_storage_received_bale_mass where warehousing_sold_balesid=$warehousing_sold_balesid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $user_found=$row["id"];
  

   }

 }


if ($user_found==0) {
  
      $user_sql = "INSERT INTO warehousing_storage_received_bale_mass(userid,seasonid,warehousing_sold_balesid,mass,created_at,datetimes
) VALUES ($userid,$seasonid,$warehousing_sold_balesid,$mass,'$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $temp=array("response"=>"success");
      array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }


}




}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





