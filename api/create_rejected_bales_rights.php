<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$companyid=0;
$useridrights=0;
$created_at="";
$seasonid=0;
$user_found=0;




$response=array();

if (isset($data->userid) && isset($data->companyid)  && isset($data->useridrights)  && isset($data->created_at)  && isset($data->seasonid)){


$userid=$data->userid;
$companyid=$data->companyid;
$useridrights=$data->useridrights;
$seasonid=$data->seasonid;
$created_at=$data->created_at;





$sql = "Select * from rejected_bales_rights where companyid=$companyid and useridrights=$useridrights and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  

$temp=array("response"=>"Rights Already Created");
 array_push($response,$temp);


}else{

$user_sql = "INSERT INTO rejected_bales_rights(userid,companyid,useridrights,seasonid,created_at) VALUES ($userid,$companyid,$useridrights,$seasonid,'$created_at')";
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


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





