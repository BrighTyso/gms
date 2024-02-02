<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$chargeid=0;
$parameterid=0;
$userid=0;
$created_at="";
$seasonid=0;
$value=0;
$found=0;
$processed_found=0;
$response=array();




if (isset($data->chargeid) && isset($data->parameterid)  && isset($data->value) && isset($data->userid) && isset($data->seasonid) && isset($data->created_at)){

$chargeid=$data->chargeid;
$value=$data->value;
$parameterid=$data->parameterid;
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;


 $sql1 = "Select * from loans where processed=1 and seasonid=$seasonid limit 1";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $processed_found=1;
    
   }
 }



$sql = "SELECT * FROM charges_amount where chargeid=$chargeid and seasonid=$seasonid  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $found=1;
    
   }



   if ($found!=0) {

    if ($processed_found==0) {

           $sql = "UPDATE charges_amount SET parameterid = $parameterid , value=$value , created_at='$created_at' WHERE chargeid=$chargeid and seasonid=$seasonid";

         //$sql = "select * from login";
         if ($conn->query($sql)===TRUE) {
           
           $temp=array("response"=>"success");
          array_push($response,$temp);

         }else{

          $temp=array("response"=>"failed");
         array_push($response,$temp);
         }


   }else{

   $temp=array("response"=>"Cannot Update Parameter On Processed Loans");
   array_push($response,$temp);

   }

   }else{


   }
   

 }else{

if ($found==0) {

  $user_sql = "INSERT INTO charges_amount(
chargeid,seasonid,parameterid,userid,value,created_at) VALUES ($chargeid,$seasonid,$parameterid,$userid,'$value','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     $temp=array("response"=>"success");
     array_push($response,$temp);
     
   }else{

   $temp=array("response"=>"failed");
   array_push($response,$temp);

   }
}



 }



}else{

  $temp=array("response"=>"empty");
  array_push($response,$temp);

	
}


echo json_encode($response);

?>





