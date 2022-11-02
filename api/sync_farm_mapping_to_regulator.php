<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select farm_mapping.id,grower_num,farm_mapping.first_lat,farm_mapping.first_long,farm_mapping.second_lat,farm_mapping.second_long,farm_mapping.third_lat,farm_mapping.third_long,farm_mapping.forth_lat,farm_mapping.forth_long,farm_mapping.seasonid,farm_mapping.userid,farm_mapping.created_at,seasons.name from farm_mapping join growers on growers.id=farm_mapping.growerid join seasons on seasons.id=farm_mapping.seasonid where farm_mapping.sync=0 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"],"second_lat"=>$row["second_lat"],"second_long"=>$row["second_long"],"third_lat"=>$row["third_lat"],"third_long"=>$row["third_long"],"forth_lat"=>$row["forth_lat"],"forth_long"=>$row["forth_long"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>