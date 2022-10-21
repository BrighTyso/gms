<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$insuranceid=0;
$created_at="";
$found=0;
$userid=0;
$growerid=0;
$seasonid=0;


$response=array();

if (isset($data->insuranceid) && isset($data->userid) && isset($data->created_at) && isset($data->seasonid) && isset($data->growerid)){


$userid=$data->userid;
$insuranceid=$data->insuranceid;
$created_at=$data->created_at;
$growerid=$data->growerid;
$seasonid=$data->seasonid;



$sql = "Select * from insurance_growers where growerid=$growerid and insuranceid=$insuranceid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["insuranceid"];
    
   }
 }






if ($found==0) {
  $user_sql = "INSERT INTO insurance_growers(userid,insuranceid,growerid,seasonid,created_at) VALUES ($userid,$insuranceid,$growerid,$seasonid,'$created_at')";
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

  $temp=array("response"=>"already Inserted");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





