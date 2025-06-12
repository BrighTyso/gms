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

if (isset($data->seasonid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$buyer_grade=strtoupper($data->buyer_grade);
$datetimes=$data->datetimes;
$batch=$data->batch;
$bale_classified=0;



$sql = "Select * from warehousing_grades_batches where buyer_grade='$buyer_grade' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  

   $temp=array("response"=>"Grade Already Created");
   array_push($response,$temp);
        
    
}else{


    $user_sql = "INSERT INTO warehousing_grades_batches(userid,seasonid,buyer_grade,bale_batch,created_at,datetimes) VALUES ($userid,$seasonid,'$buyer_grade',$batch,'$created_at','$datetimes')";
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


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





