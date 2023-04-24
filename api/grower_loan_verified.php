<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$seasonid=0;
$variable_found=0;

$response=array();

if (isset($data->userid) && isset($data->growerid)  && isset($data->seasonid)){


$userid=$data->userid;
$growerid=$data->growerid;
$seasonid=$data->seasonid;



$sql = "Select * from grower_loan_verified where growerid=$growerid and seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $variable_found=$row["id"];

  
   }

 }



if ($variable_found>0) {
  

$temp=array("response"=>"grower already Verified");
 array_push($response,$temp);


}else{

$user_sql = "INSERT INTO grower_loan_verified(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
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


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





