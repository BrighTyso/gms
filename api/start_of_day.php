<?php
require "conn.php";
require "validate.php";

$userid=0;
$latitude="";
$longitude="";
$seasonid="";
$time="";
$created_at="";




//http://192.168.1.190/gms/api/start_of_day.php?userid=1&latitude=12.3444&longitude1.89000=&seasonid=1&time=12:30&created_at=2022-06-12

if ( isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['seasonid']) && isset($_GET['time']) && isset($_GET['created_at'])){


$userid=validate($_GET['userid']);
//$growerid=validate($_POST['growerid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$time=validate($_GET['time']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);

$sql = "INSERT INTO sod(userid,latitude,longitude,seasonid,time,created_at) VALUES ($userid,'$latitude','$longitude',$seasonid,'$time','$created_at')";

   //$gr = "select * from login";
   if ($conn->query($sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo "success";

   }else{

    echo "failure";

   }




}



?>





