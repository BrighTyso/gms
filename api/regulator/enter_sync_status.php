<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$created_at="";
$found=100;


if (isset($data->userid) && isset($data->created_at)){

$userid=$data->userid;
$created_at=$data->created_at;


// status 


$sql = "Select * from regulator_sync_status limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["status"];
    
   }
 }


if ($found==100) {

   $user_sql = "INSERT INTO regulator_sync_status(userid,created_at) VALUES ($userid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

echo json_encode("success");

   }else{    

    echo json_encode("failed To Update Seasons");

   }

}else if($found==0){

  $user_sql1 = "update regulator_sync_status set status=1";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo json_encode("failed");

   }



}else{

$user_sql1 = "update regulator_sync_status set status=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo json_encode("failed");

   }

}




}else{

	echo json_encode("field cant be empty");
}



?>





