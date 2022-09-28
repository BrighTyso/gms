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

if (isset($data->name) && isset($data->userid) && isset($data->location)){


$name=$data->name;
$userid=$data->userid;
$location=$data->location;



$sql = "Select * from store where name='$name'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {
  $user_sql = "INSERT INTO store(userid,name,location) VALUES ($userid,'$name','$location')";
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





