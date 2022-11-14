<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select fertilization_potassium.id,grower_num,fertilization_potassium.latitude,fertilization_potassium.longitude,kg_per_ha ,fertilization_potassium.seasonid,fertilization_potassium.userid,fertilization_potassium.created_at,seasons.name from fertilization_potassium join growers on growers.id=fertilization_potassium.growerid join seasons on seasons.id=fertilization_potassium.seasonid where fertilization_potassium.sync=0 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"],"kg_per_ha"=>$row["kg_per_ha"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>