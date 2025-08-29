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

//$startDate=$data->startDate;
//$endDate=$data->endDate;



//$startDate=substr($data->startDate,0,-8);
//$endDate=substr($data->endDate,0,-8);

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

$weather_data=array();

$field_officer_data=array();


$grower_details_data=array();

$number_of_visits=0;

$barn_totals=0;
$farm_totals=0;
$home_totals=0;
$seedbed_totals=0;
$allocated_growers=0;
$allocated_hectares=0;
// get grower locations

if ($userid!="") {



$sql = "Select distinct scheme_hectares.quantity,grower_field_officer.growerid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id join grower_field_officer on scheme_hectares_growers.growerid=grower_field_officer.growerid  where scheme_hectares.seasonid=$seasonid and grower_field_officer.seasonid=$seasonid ";
$result = $conn->query($sql);
 $allocated_growers=$result->num_rows;

 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $allocated_hectares+=$row["quantity"];
    
   }
 }


$sql = "select * from lat_long join grower_field_officer on lat_long.growerid=grower_field_officer.growerid where field_officerid=$fieldOfficerid and grower_field_officer.seasonid=$seasonid";
  $result = $conn->query($sql);
$home_totals=$result->num_rows;
 

 $sql = "select * from seedbed_location join grower_field_officer on seedbed_location.growerid=grower_field_officer.growerid where field_officerid=$fieldOfficerid and grower_field_officer.seasonid=$seasonid ";
  $result = $conn->query($sql);
$seedbed_totals=$result->num_rows;
 



 $sql = "select * from barn_location join grower_field_officer on barn_location.growerid=grower_field_officer.growerid where field_officerid=$fieldOfficerid and grower_field_officer.seasonid=$seasonid";
  $result = $conn->query($sql);
$barn_totals=$result->num_rows;
 




 $sql = "select * from grower_farm join grower_field_officer on grower_farm.growerid=grower_field_officer.growerid where field_officerid=$fieldOfficerid and grower_field_officer.seasonid=$seasonid ";
  $result = $conn->query($sql);
$farm_totals=$result->num_rows;



 $sql = "select distinct growers.surname,growers.name,growers.grower_num,growers.id  from lat_long join growers on growers.id=lat_long.growerid where  lat_long.seasonid=$seasonid and lat_long.userid=$fieldOfficerid  order by lat_long.created_at desc ";
$result1 = $conn->query($sql);

 if ($result1->num_rows > 0) {

  //$number_of_visits=$result->num_rows;
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

    $growerid=$row1["id"];



    $visits_data=array();
    $grower_details_data=array();

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

  $weather_data=array();

  $field_officer_data=array();
  $location_data=array();

  $field_officer_name="";
  $field_officer_surname="";



  $temp1=array("grower_num"=>$row1["grower_num"],"name"=>$row1["name"],"surname"=>$row1["surname"]);
    array_push($grower_details_data,$temp1);


  $sql = "select * from users where id=$fieldOfficerid limit 1";
  $result = $conn->query($sql);

 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $field_officer_name=$row["name"];
    $field_officer_surname=$row["surname"];

    $temp=array("name"=>$field_officer_name,"surname"=>$field_officer_surname);
    array_push($field_officer_data,$temp);

   }

 }


$b_lat="";
$b_long="";
$h_lat="";
$h_long="";
$f_lat="";
$f_long="";
$s_lat="";
$s_long="";

    
    


 $sql = "select * from lat_long where growerid=$growerid and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);

 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $latitude=$row["latitude"];
    $longitude=$row["longitude"];

    $h_lat=$latitude;
    $h_long=$longitude;




   }

 }


 $sql = "select * from seedbed_location where growerid=$growerid and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);

 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $latitude=$row["latitude"];
    $longitude=$row["longitude"];

    $s_lat=$latitude;
    $s_long=$longitude;

    

   }

 }


 $sql = "select * from barn_location where growerid=$growerid and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);

 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $latitude=$row["latitude"];
    $longitude=$row["longitude"];



    $b_lat=$latitude;
    $b_long=$longitude;

  


   }

 }




 $sql = "select * from grower_farm where growerid=$growerid and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);

 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $latitude=$row["latitude"];
    $longitude=$row["longitude"];

    //$b=$latitude.",".$longitude;

    $f_lat=$latitude;
    $f_long=$longitude;
   
  
    
   }

 }

$temp=array("barn_lat"=>$b_lat,"barn_long"=>$b_long,"home_lat"=>$h_lat,"home_long"=>$h_long,"farm_lat"=>$f_lat,"farm_long"=>$f_long,"seedbed_lat"=>$s_lat,"seedbed_long"=>$s_long);
    array_push($location_data,$temp);


// grower fieldofficer visits
  $sql = "select distinct description,latitude,longitude,growers.surname,growers.name,growers.grower_num,visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid and visits.growerid=$growerid  order by visits.created_at desc ";
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
 $sql = "select no_of_irr_beds,no_of_dry_beds,buying_seedlings_for,varieties_irr,varieties_dry,seed_beds.created_at,growers.surname,growers.name,growers.grower_num  from seed_beds join growers on growers.id=seed_beds.growerid where  seed_beds.seasonid=$seasonid and seed_beds.growerid=$growerid  order by seed_beds.created_at desc limit 1";
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

   $sql = "select excellent,standard,average,poor,seedling_quality.created_at,growers.surname,growers.name,growers.grower_num from seedling_quality join growers on growers.id=seedling_quality.growerid where seedling_quality.seasonid=$seasonid and seedling_quality.growerid=$growerid  order by seedling_quality.created_at desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $description="";

    if ($row["excellent"]==1){

      $description="excellent";

    }elseif($row["standard"]==1){
      $description="standard";

    }elseif($row["average"]==1){
      $description="average";
      
    }elseif($row["poor"]==1){

      $description="poor";
      
    }

    $temp=array("excellent"=>$row["excellent"],"standard"=>$row["standard"],"average"=>$row["average"],"poor"=>$row["poor"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($seedling_quality_data,$temp);
    
   }
 }

// planting irr

$sql = "select ha_planted_to_date,date_of_plant,crop_stand_perc,crop_unifomity_perc,plant_irrigated.created_at,growers.surname,growers.name,growers.grower_num from plant_irrigated join growers on growers.id=plant_irrigated.growerid where plant_irrigated.seasonid=$seasonid and plant_irrigated.growerid=$growerid  order by plant_irrigated.created_at desc limit 1";
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

$sql = "select ha_planted_to_date,date_of_plant,crop_stand_perc,crop_unifomity_perc,planting_dryLand.created_at,growers.surname,growers.name,growers.grower_num from planting_dryLand join growers on growers.id=planting_dryLand.growerid where planting_dryLand.seasonid=$seasonid and planting_dryLand.growerid=$growerid  order by planting_dryLand.created_at desc  limit 1";
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
  $sql = "select light_yellow,light_green,medium,heavy_and_dark,heavy,crop_development.created_at,growers.surname,growers.name,growers.grower_num from crop_development join growers on growers.id=crop_development.growerid where crop_development.seasonid=$seasonid and crop_development.growerid=$growerid  order by crop_development.created_at desc  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $description="";

    if ($row["light_yellow"]==1){

      $description="Light Yellow";

    }elseif($row["light_green"]==1){
      $description="light green";

    }elseif($row["medium"]==1){
      $description="medium";
      
    }elseif($row["heavy_and_dark"]==1){

      $description="heavy and dark";
      
    }elseif($row["heavy"]==1){

      $description="heavy";
      
    }


    $temp=array("light_yellow"=>$row["light_yellow"],"light_green"=>$row["light_green"],"medium"=>$row["medium"],"heavy_and_dark"=>$row["heavy_and_dark"],"heavy"=>$row["heavy"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($crop_development_data,$temp);
    
   }
 }



// crop growth
  $sql = "select quarter_grown_5_7,half_grown_8_12,three_quarters_13_17,full_grown_18_22,fully_developed,crop_growth.created_at,growers.surname,growers.name,growers.grower_num from crop_growth join growers on growers.id=crop_growth.growerid where crop_growth.seasonid=$seasonid and crop_growth.growerid=$growerid  order by crop_growth.created_at desc  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $description="";

    if ($row["quarter_grown_5_7"]==1){

      $description="quarter grown, 5 to 7 leaves";

    }elseif($row["half_grown_8_12"]==1){
      $description="half grown, 8 to 12 leaves";

    }elseif($row["three_quarters_13_17"]==1){
      $description="three quarter, 13 to 17 leaves";
      
    }elseif($row["full_grown_18_22"]==1){

      $description="fully grown, 18 to 22 leaves";
      
    }elseif($row["fully_developed"]==1){

      $description="fully developed";
      
    }



    $temp=array("quarter_grown_5_7"=>$row["quarter_grown_5_7"],"half_grown_8_12"=>$row["half_grown_8_12"],"three_quarters_13_17"=>$row["three_quarters_13_17"],"full_grown_18_22"=>$row["full_grown_18_22"],"fully_developed"=>$row["fully_developed"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($crop_growth_data,$temp);
    
   }
 }



// cultural_practices
  $sql = "select weed_infestation_level_perc,weed_control_method,topping_level,basal_plant_fertilisation_kg_ha,suckering,post_topping_unifomity_perc,pets_and_disease_management,cultural_practices.created_at,growers.surname,growers.name,growers.grower_num from cultural_practices join growers on growers.id=cultural_practices.growerid where cultural_practices.seasonid=$seasonid and cultural_practices.growerid=$growerid  order by cultural_practices.created_at desc limit 1 ";
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
  $sql = "select barn_not_repaired,barn_under_repair,finished_repaired,barn_working_well,barn_repair_and_maintenance.created_at,growers.surname,growers.name,growers.grower_num from barn_repair_and_maintenance join growers on growers.id=barn_repair_and_maintenance.growerid where barn_repair_and_maintenance.seasonid=$seasonid and barn_repair_and_maintenance.growerid=$growerid  order by barn_repair_and_maintenance.created_at desc  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";



    $description="";

    if ($row["barn_not_repaired"]==1){

      $description="barn not repaired";

    }elseif($row["barn_under_repair"]==1){
      $description="barn under repair";

    }elseif($row["finished_repaired"]==1){
      $description="finished repairing";
      
    }elseif($row["barn_working_well"]==1){

      $description="barn working well";
      
    }



    $temp=array("barn_not_repaired"=>$row["barn_not_repaired"],"barn_under_repair"=>$row["barn_under_repair"],"finished_repaired"=>$row["finished_repaired"],"barn_working_well"=>$row["barn_working_well"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($barn_repair_and_maintenance_data,$temp);
    
   }
 }




// grower reaping
  $sql = "select top_leaf,lugs,cutters,prime,reaping.created_at,growers.surname,growers.name,growers.grower_num from reaping join growers on growers.id=reaping.growerid where reaping.seasonid=$seasonid and reaping.growerid=$growerid  order by reaping.created_at desc limit 1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


     $description="";

    if ($row["top_leaf"]==1){

      $description="top leaf";

    }elseif($row["lugs"]==1){
      $description="lugs";

    }elseif($row["cutters"]==1){
      $description="cutters";
      
    }elseif($row["prime"]==1){

      $description="prime";
      
    }


    $temp=array("top_leaf"=>$row["top_leaf"],"lugs"=>$row["lugs"],"cutters"=>$row["cutters"],"prime"=>$row["prime"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($reaping_data,$temp);
    
   }
 }


// grower curing
  $sql = "select yellowing,leaf_drying,stem_drying,curing.created_at,growers.surname,growers.name,growers.grower_num from curing join growers on growers.id=curing.growerid where curing.seasonid=$seasonid and curing.growerid=$growerid  order by curing.created_at desc  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";



    $description="";

    if ($row["yellowing"]==1){

      $description="yellowing";

    }elseif($row["leaf_drying"]==1){
      $description="leaf drying";

    }elseif($row["stem_drying"]==1){
      $description="stem drying";
      
    }


    $temp=array("yellowing"=>$row["yellowing"],"leaf_drying"=>$row["leaf_drying"],"stem_drying"=>$row["stem_drying"],"created_at"=>$row["created_at"],"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"description"=>$description);
    array_push($curing_data,$temp);
    
   }
 }




// grower weather data
  $sql = "select temp,temp_min,temp_max,pressure,humidity,rain,clouds,wind_speed,growers.surname,growers.name,growers.grower_num from grower_weather_total join growers on growers.id=grower_weather_total.growerid where grower_weather_total.seasonid=$seasonid and grower_weather_total.growerid=$growerid  order by grower_weather_total.created_at desc  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=$row["temp"];
    $temp_min=$row["temp_min"];
    $temp_max=$row["temp_max"];
    $pressure=$row["pressure"];
    $humidity=$row["humidity"];
    $rain=$row["rain"];
    $clouds=$row["clouds"];
    $wind_speed=$row["wind_speed"];


    $temp=array("temp"=>$temp,"temp_min"=>$temp_min,"pressure"=>$pressure,"humidity"=>$humidity,"surname"=>$row["surname"],"name"=>$row["name"],"grower_num"=>$row["grower_num"],"rain"=>$rain,"clouds"=>$clouds,"wind_speed"=>$wind_speed);
    array_push($weather_data,$temp);
    
   }
 }




 $temp=array("grower_details"=>$grower_details_data,"field_officer_data"=>$field_officer_data,"location_data"=>$location_data,"barn_repair_and_maintenance_data"=>$barn_repair_and_maintenance_data,"cultural_practices_data"=>$cultural_practices_data,"crop_growth_data"=>$crop_growth_data,"crop_development_data"=>$crop_development_data,"planting_dryLand_data"=>$planting_dryLand_data,"plant_irrigated_data"=>$plant_irrigated_data,"seedling_quality_data"=>$seedling_quality_data,"seed_bed_data"=>$seed_bed_data,"reaping_data"=>$reaping_data,"curing_data"=>$curing_data,"visits_data"=>$visits_data,"num_of_visits"=>$number_of_visits,"weather_data"=>$weather_data,"barn_totals"=>$barn_totals,"farm_totals"=>$farm_totals,"home_totals"=>$home_totals,"seedbed_totals"=>$seedbed_totals,"allocated_growers"=>$allocated_growers,"allocated_hectares"=>$allocated_hectares);
    array_push($data1,$temp);


  }

}




}

 echo json_encode($data1);


?>


