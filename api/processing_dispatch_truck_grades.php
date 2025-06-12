<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$warehousing_grades_batchesid=0;
$processing_dispatch_truckid=$data->processing_dispatch_truckid;
$buyer_grade=$data->buyer_grade;

$found=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');






$sql = "Select * from processing_dispatch_truck_grades where grade='$buyer_grade' and processing_dispatch_truckid=$processing_dispatch_truckid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  $found=0;
 }else{

  while($row = $result->fetch_assoc()) {
     
     $found=$row["id"];
    
    }
  
 }


if ($found>0) {
  
  if ($found>0) {
       $temp=array("response"=>"Grade Already Created");
       array_push($response,$temp);
  }else{
     $temp=array("response"=>"Grade Not Found");
     array_push($response,$temp);
  }

        
}else{

  $user_sql = "INSERT INTO processing_dispatch_truck_grades(userid,seasonid,processing_dispatch_truckid,grade,created_at) VALUES ($userid,$seasonid,$processing_dispatch_truckid,'$buyer_grade','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
    
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





