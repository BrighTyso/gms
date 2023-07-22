<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();
$found=0;


if (isset($data->userid)  && isset($data->seasonid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;



$sql = "SELECT * FROM loan_deduction_point  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $found=$row['point'];
  
    
   }
 }


 if ($found>0) {
   

$sql = "select trucknumber,truck_destination.id from truck_destination join truck_disbursment_sync_active on truck_disbursment_sync_active.disbursement_trucksid=truck_destination.id join disbursement on disbursement.disbursement_trucksid=truck_destination.id where truck_disbursment_sync_active.seasonid=$seasonid and truck_destination.close_open=1 and disbursement.quantity>0";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("id"=>$row["id"],"trucknumber"=>$row["trucknumber"]);
    array_push($data1,$temp);
    
   }
 }

}

}






 echo json_encode($data1);





?>





