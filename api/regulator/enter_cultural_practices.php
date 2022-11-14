<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$weed_infestation_level_perc="";
$weed_control_method="";
$topping_level="";
$basal_plant_fertilisation_kg_ha="";
$suckering="";
$post_topping_unifomity_perc="";
$pets_and_disease_management="";
$seasonid=0;
$sqliteid=0;
$statusid=0;



$data1=array();




//http://192.168.1.190/gms/api/enter_cultural_practice.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&weed_infestation_level_perc=0&weed_control_method=1&topping_level=0&basal_plant_fertilisation_kg_ha=0&suckering=0&post_topping_unifomity_perc=0&pets_and_disease_management=0&seasonid=1&sqliteid=1

if (isset($data->weed_infestation_level_perc) && isset($data->userid)  && isset($data->latitude)  && isset($data->longitude)  && isset($data->weed_control_method) && isset($data->season) && isset($data->topping_level) && isset($data->created_at) && isset($data->sqliteid) && isset($data->basal_plant_fertilisation_kg_ha)  && isset($data->suckering)  && isset($data->post_topping_unifomity_perc) && isset($data->pets_and_disease_management) && isset($data->grower_num)){


$userid=validate($data->userid);
$season=validate($data->season);
$lat=validate($data->latitude);
$long=validate($data->longitude);
$grower_num=validate($data->grower_num);
$weed_infestation_level_perc=validate($data->weed_infestation_level_perc);
$weed_control_method=validate($data->weed_control_method);
$topping_level=validate($data->topping_level);
$basal_plant_fertilisation_kg_ha=validate($data->basal_plant_fertilisation_kg_ha);
$suckering=validate($data->suckering);
$post_topping_unifomity_perc=validate($data->post_topping_unifomity_perc);
$pets_and_disease_management=validate($data->pets_and_disease_management);
$created_at=validate($data->created_at);
$sqliteid=validate($data->sqliteid);




$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];
   
    
   }

 }





 $sql = "Select * from seasons where name='$season' and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }



if ($statusid>0 && $seasonid>0) {



$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO cultural_practices(userid,growerid,seasonid,latitude,longitude,weed_infestation_level_perc,weed_control_method,topping_level,basal_plant_fertilisation_kg_ha,suckering,post_topping_unifomity_perc,pets_and_disease_management,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long','$weed_infestation_level_perc','$weed_control_method',$topping_level,'$basal_plant_fertilisation_kg_ha',$suckering,'$post_topping_unifomity_perc','$pets_and_disease_management','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("sqliteid"=>$sqliteid);
      array_push($data1,$temp);

   }


   }else{

   
   }


  }

}




echo json_encode($data1);


?>





