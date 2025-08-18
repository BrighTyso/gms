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

if (isset($data->userid) && isset($data->productid)){



$userid=$data->userid;
$productid=$data->productid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$start_date=$data->created_at;

$date=new DateTime($created_at);
$date->modify('+14 days');


$end_date=$date->format("Y-m-d");


$sql = "Select * from disburse_products_by_date where (end_date between '$start_date' and '$end_date') and productid=$productid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found==0) {

  $user_sql = "INSERT INTO disburse_products_by_date(userid,productid,seasonid,created_at,start_date,end_date) VALUES ($userid,$productid,$seasonid,'$created_at','$start_date','$end_date')";
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

  $temp=array("response"=>"Already In Process");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





