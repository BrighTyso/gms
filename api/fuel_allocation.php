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

if (isset($data->seasonid) && isset($data->userid) && isset($data->quantity) && isset($data->allocation_date) && isset($data->fuel_userid) && isset($data->created_at)){


$seasonid=$data->seasonid;
$userid=$data->userid;
$quantity=$data->quantity;
$created_at=$data->created_at;
$allocation_date=substr($data->allocation_date,0,-8);
$fuel_userid=$data->fuel_userid;

$sql = "Select * from fuel_allocation where allocation_date='$allocation_date' and fuel_userid=$fuel_userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {
  $user_sql = "INSERT INTO fuel_allocation(userid,seasonid,fuel_userid,quantity,allocation_date,created_at) VALUES ($userid,$seasonid,$fuel_userid,$quantity,'$allocation_date','$created_at')";
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

  $temp=array("response"=>"already allocated");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





