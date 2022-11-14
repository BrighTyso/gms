<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select cultural_practices.id,grower_num,cultural_practices.latitude,cultural_practices.longitude,cultural_practices.seasonid,cultural_practices.userid,cultural_practices.created_at,weed_infestation_level_perc,weed_control_method,topping_level,basal_plant_fertilisation_kg_ha,suckering,post_topping_unifomity_perc,pets_and_disease_management
 ,seasons.name from cultural_practices join growers on growers.id=cultural_practices.growerid join seasons on seasons.id=cultural_practices.seasonid where cultural_practices.sync=0 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"],"weed_infestation_level_perc"=>$row["weed_infestation_level_perc"],"weed_control_method"=>$row["weed_control_method"],"basal_plant_fertilisation_kg_ha"=>$row["basal_plant_fertilisation_kg_ha"],"suckering"=>$row["suckering"] ,"post_topping_unifomity_perc"=>$row["post_topping_unifomity_perc"],"topping_level"=>$row["topping_level"],"pets_and_disease_management"=>$row["pets_and_disease_management"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>