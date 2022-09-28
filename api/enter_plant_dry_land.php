<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$ha_planted_to_date="";
$ha="";
$date_of_plant="";
$crop_stand_perc="";
$crop_unifomity_perc="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_plant_dry_land.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&ha_planted_to_date=0&ha=1&date_of_plant=0&crop_stand_perc=0&crop_unifomity_perc=0&seasonid=1&sqliteid=1

if (isset($_GET['ha_planted_to_date']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['date_of_plant']) && isset($_GET['seasonid']) && isset($_GET['crop_stand_perc']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['crop_unifomity_perc']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$ha_planted_to_date=validate($_GET['ha_planted_to_date']);
//$ha=validate($_GET['ha']);
$date_of_plant=validate($_GET['date_of_plant']);
$crop_stand_perc=validate($_GET['crop_stand_perc']);
$crop_unifomity_perc=validate($_GET['crop_unifomity_perc']);
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

   $insert_sql = "INSERT INTO planting_dryLand(userid,growerid,seasonid,latitude,longitude,ha_planted_to_date,ha,date_of_plant,crop_stand_perc,crop_unifomity_perc,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long','$ha_planted_to_date','$ha','$date_of_plant','$crop_stand_perc','$crop_unifomity_perc','$created_at')";
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





