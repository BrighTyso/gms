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


$field_officer_data=array();


$number_of_visits=0;
// get grower locations

if ($userid!="") {

  $field_officer_name="";
  $field_officer_surname="";


  $sql = "select * from users where id=$fieldOfficerid";
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






// grower fieldofficer visits
  $sql = "select distinct description,growers.grower_num,visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid and visits.userid=$fieldOfficerid  order by visits.created_at desc ";
$result = $conn->query($sql);


$number_of_visits=$result->num_rows;


$temp=array("total_growers"=>$number_of_visits);
array_push($visits_data,$temp);
 

// get seedbeds



// seed quality

   $sql = "select excellent,standard,average,poor,seedling_quality.created_at,growers.surname,growers.name,growers.grower_num from seedling_quality join growers on growers.id=seedling_quality.growerid where seedling_quality.seasonid=$seasonid and seedling_quality.userid=$fieldOfficerid  order by seedling_quality.created_at desc ";
$result = $conn->query($sql);


$excellent=0;
$standard=0;
$average=0;
$poor=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $description="";

    if ($row["excellent"]==1){

      $excellent+=1;

    }elseif($row["standard"]==1){
      $standard+=1;

    }elseif($row["average"]==1){
      $average+=1;
      
    }elseif($row["poor"]==1){

      $poor+=1;
      
    }

    
    
   }

   $temp=array("excellent"=>$excellent,"standard"=>$standard,"average"=>$average,"poor"=>$poor);
    array_push($seedling_quality_data,$temp);
 }else{
$temp=array("excellent"=>$excellent,"standard"=>$standard,"average"=>$average,"poor"=>$poor);
    array_push($seedling_quality_data,$temp);
  
 }

// planting irr



 //planting dry




// crop development
  $sql = "select light_yellow,light_green,medium,heavy_and_dark,heavy,crop_development.created_at,growers.surname,growers.name,growers.grower_num from crop_development join growers on growers.id=crop_development.growerid where crop_development.seasonid=$seasonid and crop_development.userid=$fieldOfficerid  order by crop_development.created_at desc  limit 1";
$result = $conn->query($sql);


$light_yellow=0;
$light_green=0;
$medium=0;
$heavy_and_dark=0;
$heavy=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $description="";

    if ($row["light_yellow"]==1){

      $light_yellow+=1;
      
    }elseif($row["light_green"]==1){
      $light_green+=1;

    }elseif($row["medium"]==1){
      $medium+=1;
      
    }elseif($row["heavy_and_dark"]==1){

      $heavy_and_dark+=1;
      
    }elseif($row["heavy"]==1){

      $heavy+=1;
      
    }

   }

   $temp=array("light_yellow"=>$light_yellow,"light_green"=>$light_green,"medium"=>$medium,"heavy_and_dark"=>$heavy_and_dark,"heavy"=>$heavy);
    array_push($crop_development_data,$temp);
 }else{
$temp=array("light_yellow"=>$light_yellow,"light_green"=>$light_green,"medium"=>$medium,"heavy_and_dark"=>$heavy_and_dark,"heavy"=>$heavy);
    array_push($crop_development_data,$temp);

 }



// crop growth
  $sql = "select quarter_grown_5_7,half_grown_8_12,three_quarters_13_17,full_grown_18_22,fully_developed,crop_growth.created_at,growers.surname,growers.name,growers.grower_num from crop_growth join growers on growers.id=crop_growth.growerid where crop_growth.seasonid=$seasonid and crop_growth.userid=$fieldOfficerid  order by crop_growth.created_at desc ";
$result = $conn->query($sql);


$quarter_grown_5_7=0;
$half_grown_8_12=0;
$three_quarters_13_17=0;
$full_grown_18_22=0;
$fully_developed=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $description="";

    if ($row["quarter_grown_5_7"]==1){

      $quarter_grown_5_7+=1;

    }elseif($row["half_grown_8_12"]==1){
      $half_grown_8_12+=1;

    }elseif($row["three_quarters_13_17"]==1){
      $three_quarters_13_17+=1;
      
    }elseif($row["full_grown_18_22"]==1){

      $full_grown_18_22+=1;
      
    }elseif($row["fully_developed"]==1){

      $fully_developed+=1;
      
    }



   
    
   }

    $temp=array("quarter_grown_5_7"=>$quarter_grown_5_7,"half_grown_8_12"=>$half_grown_8_12,"three_quarters_13_17"=>$three_quarters_13_17,"full_grown_18_22"=>$full_grown_18_22,"fully_developed"=>$fully_developed);
    array_push($crop_growth_data,$temp);
 }else{
   $temp=array("quarter_grown_5_7"=>$quarter_grown_5_7,"half_grown_8_12"=>$half_grown_8_12,"three_quarters_13_17"=>$three_quarters_13_17,"full_grown_18_22"=>$full_grown_18_22,"fully_developed"=>$fully_developed);
    array_push($crop_growth_data,$temp);
 }



// cultural_practices
 


// barn repairs
  $sql = "select barn_not_repaired,barn_under_repair,finished_repaired,barn_working_well,barn_repair_and_maintenance.created_at,growers.surname,growers.name,growers.grower_num from barn_repair_and_maintenance join growers on growers.id=barn_repair_and_maintenance.growerid where barn_repair_and_maintenance.seasonid=$seasonid and barn_repair_and_maintenance.userid=$fieldOfficerid  order by barn_repair_and_maintenance.created_at desc  ";
$result = $conn->query($sql);


$barn_not_repaired=0;
$barn_under_repair=0;
$finished_repaired=0;
$barn_working_well=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";



    $description="";

    if ($row["barn_not_repaired"]==1){

      $barn_not_repaired+=1;

    }elseif($row["barn_under_repair"]==1){
      $barn_under_repair+=1;

    }elseif($row["finished_repaired"]==1){
      $finished_repaired+=1;
      
    }elseif($row["barn_working_well"]==1){

      $barn_working_well+=1;
      
    }



    
    
   }


   $temp=array("barn_not_repaired"=>$barn_not_repaired,"barn_under_repair"=>$barn_under_repair,"finished_repaired"=>$finished_repaired,"barn_working_well"=>$barn_working_well);
    array_push($barn_repair_and_maintenance_data,$temp);
 }else{
  $temp=array("barn_not_repaired"=>$barn_not_repaired,"barn_under_repair"=>$barn_under_repair,"finished_repaired"=>$finished_repaired,"barn_working_well"=>$barn_working_well);
    array_push($barn_repair_and_maintenance_data,$temp);
 }




// grower reaping
  $sql = "select top_leaf,lugs,cutters,prime,reaping.created_at,growers.surname,growers.name,growers.grower_num from reaping join growers on growers.id=reaping.growerid where reaping.seasonid=$seasonid and reaping.userid=$fieldOfficerid  order by reaping.created_at desc  ";
$result = $conn->query($sql);


$top_leaf=0;
$lugs=0;
$cutters=0;
$prime=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


     $description="";

    if ($row["top_leaf"]==1){

      $top_leaf+=1;
    
    }elseif($row["lugs"]==1){
      $lugs+=1;

    }elseif($row["cutters"]==1){

      $cutters+=1;
      
    }elseif($row["prime"]==1){

      $prime+=1;
      
    }
   }

   $temp=array("top_leaf"=>$top_leaf,"lugs"=>$lugs,"cutters"=>$cutters,"prime"=>$prime);
    array_push($reaping_data,$temp);
 }else{
   $temp=array("top_leaf"=>$top_leaf,"lugs"=>$lugs,"cutters"=>$cutters,"prime"=>$prime);
    array_push($reaping_data,$temp);
 }


// grower curing
  $sql = "select yellowing,leaf_drying,stem_drying,curing.created_at,growers.surname,growers.name,growers.grower_num from curing join growers on growers.id=curing.growerid where curing.seasonid=$seasonid and curing.userid=$fieldOfficerid  order by curing.created_at desc ";
$result = $conn->query($sql);

$yellowing=0;
$leaf_drying=0;
$stem_drying=0;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $description="";

    if ($row["yellowing"]==1){

      $yellowing+=1;

    }elseif($row["leaf_drying"]==1){
      $leaf_drying+=1;

    }elseif($row["stem_drying"]==1){
      $stem_drying+=1;
      
    }


   
    
   }

    $temp=array("yellowing"=>$yellowing,"leaf_drying"=>$leaf_drying,"stem_drying"=>$stem_drying);
    array_push($curing_data,$temp);
 }else{
  $temp=array("yellowing"=>$yellowing,"leaf_drying"=>$leaf_drying,"stem_drying"=>$stem_drying);
    array_push($curing_data,$temp);
 }


 

  }



  $temp=array("field_officer_data"=>$field_officer_data,"barn_repair_and_maintenance_data"=>$barn_repair_and_maintenance_data,"cultural_practices_data"=>$cultural_practices_data,"crop_growth_data"=>$crop_growth_data,"crop_development_data"=>$crop_development_data,"planting_dryLand_data"=>$planting_dryLand_data,"plant_irrigated_data"=>$plant_irrigated_data,"seedling_quality_data"=>$seedling_quality_data,"seed_bed_data"=>$seed_bed_data,"reaping_data"=>$reaping_data,"curing_data"=>$curing_data,"num_of_visits"=>$number_of_visits);
    array_push($data1,$temp);

 echo json_encode($data1);


?>


