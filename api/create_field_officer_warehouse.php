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
$field_officerid=$data->field_officerid;
$storeid=$data->storeid;
$created_at=$data->created_at;

$date = new DateTime();
$datetimes=$date->format('H:i:s');

$sql = "Select * from field_officer_warehouse where field_officerid=$field_officerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  

   $user_sql1 = "update field_officer_warehouse set storeid=$storeid where field_officerid=$field_officerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

              $temp=array("response"=>"successfully updated");
              array_push($response,$temp);

         
        }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

    
}else{



  $user_sql = "INSERT INTO field_officer_warehouse(userid,seasonid,field_officerid,storeid,created_at,datetimes) VALUES ($userid,$seasonid,$field_officerid,$storeid,'$created_at','$datetimes')";
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


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





