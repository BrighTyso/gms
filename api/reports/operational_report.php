<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$fieldOfficerid=$data->fieldOfficerid;

#$startDate=$data->startDate;
#$endDate=$data->endDate;



$startDate=substr($data->startDate,0,-8);
$endDate=substr($data->endDate,0,-8);

$data1=array();

$visits_data=array();

$seed_bed_data=array();
$seedling_quality_data=array();

$plant_irrigated_data=array();
$planting_dryLand_data=array();


$crop_development_data=array();
$crop_growth_data=array();


$cultural_practices_data=array();
$barn_repair_and_maintenance_data=array();


$reaping_data=array();
$curing_data=array();


$number_of_visits=0;
// get grower locations

if ($userid!="") {

// grower fieldofficer visits
  $sql = "select description,latitude,longitude,growers.surname,growers.name,growers.grower_num,visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid and visits.userid=$fieldOfficerid and (visits.created_at between '$startDate' and '$endDate') order by visits.created_at desc ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

  $number_of_visits=$result->num_rows;
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("description"=>$row["description"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"created_at"=>$row["created_at"]);
    array_push($visits_data,$temp);
    
   }
 }
  

// get seedbeds
 $sql = "select no_of_irr_beds,no_of_dry_beds,buying_seedlings_for,varieties_irr,varieties_dry,seed_beds.created_at,growers.surname,growers.name,growers.grower_num  from seed_beds join growers on growers.id=seed_beds.growerid where  seed_beds.seasonid=$seasonid and seed_beds.userid=$fieldOfficerid and (seed_beds.created_at between '$startDate' and '$endDate') order by seed_beds.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("no_of_irr_beds"=>$row["no_of_irr_beds"],"no_of_dry_beds"=>$row["no_of_dry_beds"],"buying_seedlings_for"=>$row["buying_seedlings_for"],"varieties_irr"=>$row["varieties_irr"],"varieties_dry"=>$row["varieties_dry"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($seed_bed_data,$temp);
    
   }
 }


// seed quality

   $sql = "select excellent,standard,average,poor,seedling_quality.created_at,growers.surname,growers.name,growers.grower_num from seedling_quality join growers on growers.id=seedling_quality.growerid where seedling_quality.seasonid=$seasonid and seedling_quality.userid=$fieldOfficerid and (seedling_quality.created_at between '$startDate' and '$endDate') order by seedling_quality.created_at desc ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("excellent"=>$row["excellent"],"standard"=>$row["standard"],"average"=>$row["average"],"poor"=>$row["poor"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($seedling_quality_data,$temp);
    
   }
 }

// planting irr

$sql = "select ha_planted_to_date,date_of_plant,crop_stand_perc,crop_unifomity_perc,plant_irrigated.created_at,growers.surname,growers.name,growers.grower_num from plant_irrigated join growers on growers.id=plant_irrigated.growerid where plant_irrigated.seasonid=$seasonid and plant_irrigated.userid=$fieldOfficerid and (plant_irrigated.created_at between '$startDate' and '$endDate') order by plant_irrigated.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("ha_planted_to_date"=>$row["ha_planted_to_date"],"date_of_plant"=>$row["date_of_plant"],"crop_stand_perc"=>$row["crop_stand_perc"],"crop_unifomity_perc"=>$row["crop_unifomity_perc"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($plant_irrigated_data,$temp);
    
   }
 }

 //planting dry

$sql = "select ha_planted_to_date,date_of_plant,crop_stand_perc,crop_unifomity_perc,planting_dryLand.created_at,growers.surname,growers.name,growers.grower_num from planting_dryLand join growers on growers.id=planting_dryLand.growerid where planting_dryLand.seasonid=$seasonid and planting_dryLand.userid=$fieldOfficerid and (planting_dryLand.created_at between '$startDate' and '$endDate') order by planting_dryLand.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("ha_planted_to_date"=>$row["ha_planted_to_date"],"date_of_plant"=>$row["date_of_plant"],"crop_stand_perc"=>$row["crop_stand_perc"],"crop_unifomity_perc"=>$row["crop_unifomity_perc"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($planting_dryLand_data,$temp);
    
   }
 }


// crop development
  $sql = "select light_yellow,light_green,medium,heavy_and_dark,heavy,crop_development.created_at,growers.surname,growers.name,growers.grower_num from crop_development join growers on growers.id=crop_development.growerid where crop_development.seasonid=$seasonid and crop_development.userid=$fieldOfficerid and (crop_development.created_at between '$startDate' and '$endDate') order by crop_development.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("light_yellow"=>$row["light_yellow"],"light_green"=>$row["light_green"],"medium"=>$row["medium"],"heavy_and_dark"=>$row["heavy_and_dark"],"heavy"=>$row["heavy"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($crop_development_data,$temp);
    
   }
 }



// crop growth
  $sql = "select quarter_grown_5_7,half_grown_8_12,three_quarters_13_17,full_grown_18_22,fully_developed,crop_growth.created_at,growers.surname,growers.name,growers.grower_num from crop_growth join growers on growers.id=crop_growth.growerid where crop_growth.seasonid=$seasonid and crop_growth.userid=$fieldOfficerid and (crop_growth.created_at between '$startDate' and '$endDate') order by crop_growth.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("quarter_grown_5_7"=>$row["quarter_grown_5_7"],"half_grown_8_12"=>$row["half_grown_8_12"],"three_quarters_13_17"=>$row["three_quarters_13_17"],"full_grown_18_22"=>$row["full_grown_18_22"],"fully_developed"=>$row["fully_developed"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($crop_growth_data,$temp);
    
   }
 }



// cultural_practices
  $sql = "select weed_infestation_level_perc,weed_control_method,topping_level,basal_plant_fertilisation_kg_ha,suckering,post_topping_unifomity_perc,pets_and_disease_management,cultural_practices.created_at,growers.surname,growers.name,growers.grower_num from cultural_practices join growers on growers.id=cultural_practices.growerid where cultural_practices.seasonid=$seasonid and cultural_practices.userid=$fieldOfficerid and (cultural_practices.created_at between '$startDate' and '$endDate') order by cultural_practices.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("weed_infestation_level_perc"=>$row["weed_infestation_level_perc"],"weed_control_method"=>$row["weed_control_method"],"topping_level"=>$row["topping_level"],"basal_plant_fertilisation_kg_ha"=>$row["basal_plant_fertilisation_kg_ha"]
      ,"suckering"=>$row["suckering"],"post_topping_unifomity_perc"=>$row["post_topping_unifomity_perc"],"pets_and_disease_management"=>$row["pets_and_disease_management"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($cultural_practices_data,$temp);
    
   }
 }



// barn repairs
  $sql = "select barn_not_repaired,barn_under_repair,finished_repaired,barn_working_well,barn_repair_and_maintenance.created_at,growers.surname,growers.name,growers.grower_num from barn_repair_and_maintenance join growers on growers.id=barn_repair_and_maintenance.growerid where barn_repair_and_maintenance.seasonid=$seasonid and barn_repair_and_maintenance.userid=$fieldOfficerid and (barn_repair_and_maintenance.created_at between '$startDate' and '$endDate') order by barn_repair_and_maintenance.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("barn_not_repaired"=>$row["barn_not_repaired"],"barn_under_repair"=>$row["barn_under_repair"],"finished_repaired"=>$row["finished_repaired"],"barn_working_well"=>$row["barn_working_well"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($barn_repair_and_maintenance_data,$temp);
    
   }
 }




// grower reaping
  $sql = "select top_leaf,lugs,cutters,prime,reaping.created_at,growers.surname,growers.name,growers.grower_num from reaping join growers on growers.id=reaping.growerid where reaping.seasonid=$seasonid and reaping.userid=$fieldOfficerid and (reaping.created_at between '$startDate' and '$endDate') order by reaping.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("top_leaf"=>$row["top_leaf"],"lugs"=>$row["lugs"],"cutters"=>$row["cutters"],"prime"=>$row["prime"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($reaping_data,$temp);
    
   }
 }


// grower curing
  $sql = "select yellowing,leaf_drying,stem_drying,curing.created_at,growers.surname,growers.name,growers.grower_num from curing join growers on growers.id=curing.growerid where curing.seasonid=$seasonid and curing.userid=$fieldOfficerid and (curing.created_at between '$startDate' and '$endDate') order by curing.created_at desc  ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("yellowing"=>$row["yellowing"],"leaf_drying"=>$row["leaf_drying"],"stem_drying"=>$row["stem_drying"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"]);
    array_push($curing_data,$temp);
    
   }
 }


 $temp=array("barn_repair_and_maintenance_data"=>$barn_repair_and_maintenance_data,"cultural_practices_data"=>$cultural_practices_data,"crop_growth_data"=>$crop_growth_data,"crop_development_data"=>$crop_development_data,"planting_dryLand_data"=>$planting_dryLand_data,"plant_irrigated_data"=>$plant_irrigated_data,"seedling_quality_data"=>$seedling_quality_data,"seed_bed_data"=>$seed_bed_data,"reaping_data"=>$reaping_data,"curing_data"=>$curing_data,"visits_data"=>$visits_data,"num_of_visits"=>$number_of_visits);
    array_push($data1,$temp);




}

 echo json_encode($data1);


?>


