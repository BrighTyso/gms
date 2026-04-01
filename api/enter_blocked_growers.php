<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$found=0;
$disbusment_quantity=0;

$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num)){


$seasonid=$data->seasonid;
$userid=$data->userid;
$grower_num=$data->grower_num;
$growerid=0;
$created_at=$data->created_at;

$otp_found=0;

$grower_id=0;


$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];
      
   }

 }



$sql = "Select * from blocked_growers where  seasonid=$seasonid and growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $found=$row["id"];
   
   }

 }

   if ($found==0 && $growerid>0) {

     $user_sql = "INSERT INTO blocked_growers(userid,seasonid,growerid,created_at) VALUES ($userid,$seasonid,$growerid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;


       $temp=array("response"=>"success");
       array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }


   }else{

    $temp=array("response"=>"Not found/Already blocked");
    array_push($response,$temp);

   }



}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





