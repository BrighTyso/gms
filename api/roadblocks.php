<?php
require "conn.php";
require "validate.php";

$userid=0;
$latitude="";
$longitude="";
$seasonid="";
$time="";
$created_at="";
$sqliteid=0;

$data=array();


//http://192.168.1.190/gms/api/roadblocks.php?userid=1&latitude=12.3444&longitude1.89000=&seasonid=1&time=12:30&created_at=2022-06-12

if ( isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['seasonid']) && isset($_GET['time']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])){


$userid=validate($_GET['userid']);
//$growerid=validate($_POST['growerid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$time=validate($_GET['time']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET["sqliteid"]);


$sql = "INSERT INTO road_blocks(userid,latitude,longitude,seasonid,time,created_at) VALUES ($userid,'$latitude','$longitude',$seasonid,'$time','$created_at')";


   //$gr = "select * from login";
   if ($conn->query($sql)===TRUE) {
   
     //$last_id = $conn->insert_id;

    $temp=array("id"=>$sqliteid);
    array_push($data,$temp);


   }



}


echo json_encode($data);


?>





