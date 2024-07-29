<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$companyid=0;
$buyer_code="";
$found=0;

$response=array();

if (isset($data->userid) && isset($data->username)  && isset($data->grower_num)){

$username=$data->username;
$grower_num=$data->grower_num;
$userid=$data->userid;
$fieldofficerid=0;
$found=0;




$sql = "Select * from users where username='$username' and active=1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $fieldofficerid=$row["id"];

  
   }

 }



 $sql = "Select * from growers  where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $growerid=$row["id"];

  
   }

 }



$sql = "Select * from grower_field_officer  where growerid=$growerid and fieldofficerid=$fieldofficerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $found=$row["id"];

  
   }

 }



if ($fieldofficerid>0) {
  

$temp=array("response"=>"Code already Created");
 array_push($response,$temp);


}else{

$user_sql = "INSERT INTO buyer(userid,companyid,description) VALUES ($userid,$companyid,'$buyer_code')";
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





