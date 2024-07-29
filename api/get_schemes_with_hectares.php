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

$seasonid=$data->seasonid;
 

$sql = "Select scheme_hectares.quantity,scheme_hectares.id,description from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id where scheme_hectares.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("description"=>$row["description"],"quantity"=>$row["quantity"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }

}





 echo json_encode($data1);


?>





