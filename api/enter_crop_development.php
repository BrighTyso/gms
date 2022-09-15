<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$light_yellow="";
$light_green="";
$medium="";
$heavy="";
$heavy_and_dark="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_crop_development.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&light_yellow=0&light_green=1&medium=0&heavy=0&heavy_and_dark=0&seasonid=1&sqliteid=1

if (isset($_GET['light_yellow']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['light_green']) && isset($_GET['seasonid']) && isset($_GET['medium']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['heavy'])  && isset($_GET['heavy_and_dark']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$light_yellow=validate($_GET['light_yellow']);
$light_green=validate($_GET['light_green']);
$medium=validate($_GET['medium']);
$heavy=validate($_GET['heavy']);
$heavy_and_dark=validate($_GET['heavy_and_dark']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
   //echo json_encode('$growerid');
  
    
   }

 }


// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO crop_development(userid,growerid,seasonid,latitude,longitude,light_yellow,light_green,medium,heavy,heavy_and_dark,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long',$light_yellow,$light_green,$medium,$heavy,$heavy_and_dark,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("id"=>$sqliteid);
      array_push($data,$temp);

   }else{

    echo json_encode($conn->error);
   }


   }else{

   
   }





}




echo json_encode($data);


?>





