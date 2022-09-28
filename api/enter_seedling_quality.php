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

if (isset($_GET['excellent']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['standard']) && isset($_GET['seasonid']) && isset($_GET['average']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['poor']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$excellent=validate($_GET['excellent']);
$standard=validate($_GET['standard']);
$average=validate($_GET['average']);
$poor=validate($_GET['poor']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);




$sql = "Select * from growers where grower_num='$grower_num';";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO seedling_quality(userid,growerid,seasonid,latitude,longitude,excellent,standard,average,poor,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long',$excellent,$standard,$average,$poor,'$created_at')";
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





