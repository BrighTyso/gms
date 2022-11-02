<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select mapped_hectares.id,grower_num,mapped_hectares.ha,mapped_hectares.seasonid,mapped_hectares.userid,mapped_hectares.created_at,seasons.name from mapped_hectares join growers on growers.id=mapped_hectares.growerid join seasons on seasons.id=mapped_hectares.seasonid  where mapped_hectares.sync=0";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"ha"=>$row["ha"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>