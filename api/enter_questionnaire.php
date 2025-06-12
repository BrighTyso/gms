<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$excellent=0;
$standard=0;
$average=0;
$poor=0;
$seasonid=0;
$sqliteid=0;

$data=array();
//http://192.168.1.190/gms/api/enter_seedling_quality.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&excellent=0&standard=1&average=0&poor=0&seasonid=1&sqliteid=1

if (isset($_GET['userid']) && isset($_GET['seasonid']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$type=validate($_GET['type']);
$access_point=validate($_GET['access_point']);
$question=validate($_GET['question']);
$datetimes=validate($_GET['datetimes']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);

// then insert loan
// insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value(NEW.userid,NEW.growerid,NEW.seasonid,NEW.latitude,NEW.longitude,NEW.created_at,"fertilization potassium");

   $insert_sql = "INSERT INTO questionnaires(userid,seasonid,question,type,access_point,created_at,datetimes) VALUES ($userid,$seasonid,'$question','$type','$access_point','$created_at','$datetimes')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
       
         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

   }


}




echo json_encode($data);


?>





