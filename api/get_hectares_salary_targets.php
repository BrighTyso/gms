<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid)) {
 
$userid=$data->userid;


$sql = "Select * from salary_allocated_hectares where active=1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("id"=>$row["id"],"min_hectares"=>$row["min_hectares"],"max_hectares"=>$row["max_hectares"]);
    array_push($data1,$temp);
    
   }
 }

}






 echo json_encode($data1);





?>





