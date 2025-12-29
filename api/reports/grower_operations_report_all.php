<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$fieldOfficerid=0;

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

 $sql = "select distinct name,surname,id from users where rightsid=9 or rightsid=8";
$result1 = $conn->query($sql);

 if ($result1->num_rows > 0) {

  //$number_of_visits=$result->num_rows;
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {



    $number_of_visits=0;

    $barn_totals=0;
    $farm_totals=0;
    $home_totals=0;
    $seedbed_totals=0;
    $allocated_growers=0;
    $allocated_hectares=0;


    $fieldOfficerid=$row1["id"];
    $field_officer_name=$row1["name"];
    $field_officer_surname=$row1["surname"];



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




  $sql1 = "Select distinct scheme_hectares.quantity,grower_field_officer.growerid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id join grower_field_officer on scheme_hectares_growers.growerid=grower_field_officer.growerid  where scheme_hectares.seasonid=$seasonid and grower_field_officer.seasonid=$seasonid and grower_field_officer.field_officerid=$fieldOfficerid";
$result = $conn->query($sql1);
 $allocated_growers=$result->num_rows;

 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $allocated_hectares+=$row["quantity"];
    
   }
 }


$sql1 = "select * from lat_long  where userid=$fieldOfficerid and lat_long.seasonid=$seasonid";
  $result = $conn->query($sql1);
$home_totals=$result->num_rows;
 

 $sql1 = "select * from seedbed_location  where userid=$fieldOfficerid and seedbed_location.seasonid=$seasonid";
  $result = $conn->query($sql1);
$seedbed_totals=$result->num_rows;
 


$sql1 = "select * from barn_location  where userid=$fieldOfficerid and barn_location.seasonid=$seasonid";
  $result = $conn->query($sql1);
$barn_totals=$result->num_rows;
 



$sql1 = "select * from grower_farm where userid=$fieldOfficerid and grower_farm.seasonid=$seasonid ";
  $result = $conn->query($sql1);
$farm_totals=$result->num_rows;




 



$temp=array("name"=>$field_officer_name,"surname"=>$field_officer_surname);
array_push($field_officer_data,$temp);



$sql = "select distinct growers.grower_num,visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid  order by visits.created_at desc ";
$result = $conn->query($sql);
$number_of_visits=$result->num_rows;



 $temp=array("grower_details"=>$grower_details_data,"field_officer_data"=>$field_officer_data,"location_data"=>$location_data,"barn_repair_and_maintenance_data"=>$barn_repair_and_maintenance_data,"cultural_practices_data"=>$cultural_practices_data,"crop_growth_data"=>$crop_growth_data,"crop_development_data"=>$crop_development_data,"planting_dryLand_data"=>$planting_dryLand_data,"plant_irrigated_data"=>$plant_irrigated_data,"seedling_quality_data"=>$seedling_quality_data,"seed_bed_data"=>$seed_bed_data,"reaping_data"=>$reaping_data,"curing_data"=>$curing_data,"visits_data"=>$visits_data,"num_of_visits"=>$number_of_visits,"weather_data"=>$weather_data,"barn_totals"=>$barn_totals,"farm_totals"=>$farm_totals,"home_totals"=>$home_totals,"seedbed_totals"=>$seedbed_totals,"allocated_growers"=>$allocated_growers,"allocated_hectares"=>$allocated_hectares);
    array_push($data1,$temp);


  }

}




}

 echo json_encode($data1);


?>


