<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();

$growerid=$data->growerid;
$seasonid=$data->seasonid;
$userid=$data->userid;




//http://192.168.1.190/gms/api/get_seedbed.php

if ($userid!="") {
  $sql = "select no_of_irr_beds,no_of_dry_beds,buying_seedlings_for,varieties_irr,varieties_dry,created_at  from seed_beds where growerid=$growerid and seasonid=$seasonid order by created_at desc limit 1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("no_of_irr_beds"=>$row["no_of_irr_beds"],"no_of_dry_beds"=>$row["no_of_dry_beds"],"buying_seedlings_for"=>$row["buying_seedlings_for"],"varieties_irr"=>$row["varieties_irr"],"varieties_dry"=>$row["varieties_dry"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }
}




 echo json_encode($data1); 



?>