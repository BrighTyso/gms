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
$grower_num=$data->grower_num;
$barcode=$data->barcode;
$lot=$data->lot;
$groups=$data->groups;
$location=$data->location;
$mass=$data->mass;
$price=$data->price;
$shipment_detailsid=$data->shipment_detailsid;

$sell_date_format = new DateTime($data->sell_date);
$sell_date=$sell_date_format->format("Y-m-d");

$date = new DateTime();
$datetimes=$date->format('H:i:s');

$sql = "Select * from shipment_file where barcode='$barcode' and shipment_detailsid=$shipment_detailsid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  
$temp=array("response"=>"Already captured","barcode"=>$barcode);
array_push($response,$temp);
    
}else{

  $user_sql = "INSERT INTO shipment_file(userid,seasonid,grower_num,barcode,lot,location,mass,price,sell_date,created_at,datetimes,shipment_detailsid) VALUES ($userid,$seasonid,'$grower_num','$barcode','$lot','$location','$mass','$price','$sell_date','$created_at','$datetimes',$shipment_detailsid)";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $temp=array("response"=>"success","barcode"=>$barcode);
      array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }


}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





