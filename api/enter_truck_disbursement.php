<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$trucknumber="";
$driver_name="";
$driver_surname="";
$destination="";
$created_at="";
$found=0;

$response=array();

if (isset($data->trucknumber) && isset($data->userid) && isset($data->destination) && isset($data->name) && isset($data->surname) && isset($data->seasonid)){


$trucknumber=$data->trucknumber;
$driver_name=$data->name;
$driver_surname=$data->surname;
$destination=$data->destination;
$created_at=$data->created_at;
$userid=$data->userid;
$seasonid=$data->seasonid;



$sql = "Select * from truck_destination where trucknumber='$trucknumber'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {
  $user_sql = "INSERT INTO truck_destination(userid,trucknumber,driver_name,driver_surname,destination,created_at) VALUES ($userid,'$trucknumber','$driver_name','$driver_surname','$destination','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     $user_sql = "INSERT INTO truck_disbursment_sync_active(userid,disbursement_trucksid,seasonid) VALUES ($userid,$last_id,$seasonid)";
   //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
           
           $temp=array("response"=>"success");
           array_push($response,$temp);
           
         }else{

         $temp=array("response"=>$conn->error);
         array_push($response,$temp);

         }
           
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }
}else{

  $temp=array("response"=>"already Inserted");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





