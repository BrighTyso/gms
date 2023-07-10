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

$response=array();

if (isset($data->truck_userid) && isset($data->userid) && isset($data->disbursement_trucksid) && isset($data->seasonid)){


$truck_userid=$data->truck_userid;
$userid=$data->userid;
$disbursement_trucksid=$data->disbursement_trucksid;
$seasonid=$data->seasonid;



$sql = "Select * from user_to_truck_disbursment where disbursement_trucksid=$disbursement_trucksid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {
  $user_sql = "INSERT INTO user_to_truck_disbursment(userid,disbursement_trucksid,truck_userid,seasonid) VALUES ($userid,$disbursement_trucksid,$truck_userid,$seasonid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

      $user_sql1 = "update truck_disbursment_sync_active set active=1 where disbursement_trucksid=$disbursement_trucksid";
     //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

     $temp=array("response"=>"success");
       array_push($response,$temp);

       
      }
    
     
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }
}else{

  $temp=array("response"=>"already Created");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





