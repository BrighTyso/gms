<?php
require "conn.php";
require "validate.php";


$data=array();


$userid=0;
$growerid=0;
$latitude="";
$longitude="";
$description="";
$number=0;
$seasonid=0;
$sqliteid=0;
$created_at="";

//http://192.168.1.190/gms/api/roadblocks.php?userid=1&latitude=12.3444&longitude1.89000=&seasonid=1&time=12:30&created_at=2022-06-12

if ( isset($_GET['userid'])  && isset($_GET['latitude']) && isset($_GET['longitude'])  && isset($_GET['description'])  && isset($_GET['seasonid']) && isset($_GET['sqliteid'])){


$userid=validate($_GET['userid']);
$growerid=validate($_GET['growerid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$description=validate($_GET['description']);
$number=validate($_GET['number']);
$seasonid=validate($_GET['seasonid']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET["sqliteid"]);


$sql = "INSERT INTO quantitative_assessments(userid,growerid,latitude,longitude,description,number,seasonid,created_at) VALUES ($userid,$growerid,'$latitude','$longitude','$description',$number,$seasonid,'$created_at')";


   //$gr = "select * from login";
   if ($conn->query($sql)===TRUE) {
   
     //$last_id = $conn->insert_id;

    $temp=array("id"=>$sqliteid);
    array_push($data,$temp);


   }



}


echo json_encode($data);


?>





