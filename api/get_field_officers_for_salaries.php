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
$seasonid=$data->seasonid;


$sql = "Select * from users where active=1 and (rightsid=9 or rightsid=10 or rightsid=8)";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $claimyid=$row["id"];
    $allocated_hectares=0;

    $sql451 = "select distinct scheme_hectares.quantity,growers.id  from  growers join scheme_hectares_growers on growers.id=scheme_hectares_growers.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid join grower_field_officer on grower_field_officer.growerid=scheme_hectares_growers.growerid where  scheme_hectares.seasonid=$seasonid and grower_field_officer.field_officerid=$claimyid ";
$result451 = $conn->query($sql451);
   if ($result451->num_rows > 0) {
   // output data of each row
   while($result45 = $result451->fetch_assoc()) {

    $allocated_hectares+=$result45["quantity"];

   }
 }

    $temp=array("id"=>$row["id"],"name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"active"=>$row["active"],"allocated_hectares"=>$allocated_hectares);
    array_push($data1,$temp);
    
   }
 }

}






 echo json_encode($data1);





?>





