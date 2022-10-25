<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select grower_farm.id,grower_num,grower_farm.latitude,grower_farm.longitude,grower_farm.seasonid,grower_farm.userid,grower_farm.created_at,seasons.name from grower_farm join growers on growers.id=grower_farm.growerid join seasons on seasons.id=grower_farm.seasonid  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>