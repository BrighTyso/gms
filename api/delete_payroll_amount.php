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
$farm_response=0;
$claimyid=0;
$salary=0;


$response=array();

if (isset($data->id)  && isset($data->userid)){

$id=$data->id;
$userid=$data->userid;
$created_at=$data->created_at;


$sql1 = "Select id from monthly_salary_claims  where id=$id limit 1";
$result1 = $conn->query($sql1);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==1) {

      $user_sql = "delete from monthly_salary_claims where id=$id";
      //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {

            $temp=array("response"=>"success");
          array_push($response,$temp);
         }

      }else{

        $temp=array("response"=>"not found");
          array_push($response,$temp);
      }




}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





