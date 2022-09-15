<?php
require "conn.php";
require "validate.php";

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



$data=array();




//http://192.168.1.190/gms/api/enter_cultural_practice.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&weed_infestation_level_perc=0&weed_control_method=1&topping_level=0&basal_plant_fertilisation_kg_ha=0&suckering=0&post_topping_unifomity_perc=0&pets_and_disease_management=0&seasonid=1&sqliteid=1

if (isset($_GET['weed_infestation_level_perc']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['weed_control_method']) && isset($_GET['seasonid']) && isset($_GET['topping_level']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['basal_plant_fertilisation_kg_ha'])  && isset($_GET['suckering'])  && isset($_GET['post_topping_unifomity_perc']) && isset($_GET['pets_and_disease_management']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$weed_infestation_level_perc=validate($_GET['weed_infestation_level_perc']);
$weed_control_method=validate($_GET['weed_control_method']);
$topping_level=validate($_GET['topping_level']);
$basal_plant_fertilisation_kg_ha=validate($_GET['basal_plant_fertilisation_kg_ha']);
$suckering=validate($_GET['suckering']);
$post_topping_unifomity_perc=validate($_GET['post_topping_unifomity_perc']);
$pets_and_disease_management=validate($_GET['pets_and_disease_management']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



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

     $temp=array("id"=>$sqliteid);
      array_push($data,$temp);

   }


   }else{

   
   }





}




echo json_encode($data);


?>





