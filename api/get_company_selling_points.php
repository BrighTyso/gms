<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();

$userid=$data->userid;

//http://192.168.1.190/gms/api/get_seedbed.php

if ($userid!="") {
  $sql = "select selling_points.name,users.username,company_to_selling_point.id,company_to_selling_point.companyid from selling_points join company_to_selling_point on company_to_selling_point.selling_pointid=selling_points.id join users on users.id=company_to_selling_point.companyid where company_to_selling_point.active=1 and selling_points.id";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"username"=>$row["username"],"id"=>$row["id"],"companyid"=>$row["companyid"]);
    array_push($data1,$temp);
    
   }
 }
}




 echo json_encode($data1); 



?>