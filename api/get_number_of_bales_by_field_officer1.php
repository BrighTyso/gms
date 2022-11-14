<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  &&  isset($data->seasonid) &&  isset($data->description) ){

$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;



if ($description=="") {

$sql = "Select distinct growers.id,grower_number_of_bales.id as balesid,grower_num,bales,users.name,estimate,seasons.name as season_name from lat_long join growers on lat_long.growerid=growers.id join users on lat_long.userid=users.id join grower_number_of_bales on lat_long.growerid=grower_number_of_bales.growerid join seasons on lat_long.seasonid=seasons.id join system_estimate_prediction on growers.id=system_estimate_prediction.growerid where lat_long.seasonid=$seasonid and grower_number_of_bales.seasonid=$seasonid and system_estimate_prediction.seasonid=$seasonid  and users.id=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
     $temp=array("balesid"=>$row["balesid"],"growerid"=>$row["id"],"grower_num"=>$row["grower_num"],"bales"=>$row["bales"],"name"=>$row["name"],"estimate"=>$row["estimate"],"season_name"=>$row["season_name"]);
      array_push($data1,$temp);
 
 
   }

 }


}else{

// $sql = "Select distinct growers.id,grower_num,no_of_plants,basal_plant_fertilisation_kg_ha,fertilization_potassium.kg_per_ha as p_kg_per_ha ,fertilization_ammonium.kg_per_ha as a_kg_per_ha,ha from growers  join cultural_practices on growers.id=cultural_practices.growerid join lat_long on growers.id=lat_long.growerid join users on users.id=lat_long.userid join mapped_hectares on growers.id=mapped_hectares.growerid join ploughing on growers.id=ploughing.growerid join fertilization_potassium on growers.id=fertilization_potassium.growerid join fertilization_ammonium on growers.id=fertilization_ammonium.growerid where fertilization_ammonium.seasonid=$seasonid  and  fertilization_potassium.seasonid=$seasonid and ploughing.seasonid=$seasonid and cultural_practices.seasonid=$seasonid and lat_long.seasonid=$seasonid and (grower_num='$description' or users.username='$description' or users.name='$description')";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {

//     // product id
//   $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"no_of_plants"=>$row["no_of_plants"],"basal"=>$row["basal_plant_fertilisation_kg_ha"],"potassium"=>$row["p_kg_per_ha"],"ammonium"=>$row["a_kg_per_ha"],"ha"=>$row["ha"]);
//       array_push($data1,$temp);
 
//    }

//  }

}


}else{

	  

}


echo json_encode($data1);



?>


