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

if (isset($data->id)  && isset($data->userid)){


$id=$data->id;
$seasonid=$data->seasonid;
$userid=$data->userid;

$sql = "Select * from salary_dates_and_months where id=$id limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id

        $start=$row['start_date'];
        $end=$row['end_date'];
  
         $user_sql1 = "update monthly_salary_claims set sync=0 where start_date='$start' and end_date='$end'";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"successfully Reversed");
          array_push($response,$temp);

           
          }
   }

 }



}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





