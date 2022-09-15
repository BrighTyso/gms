<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$quarter_grown_5_7="";
$half_grown_8_12="";
$three_quarters_13_17="";
$full_grown_18_22="";
$fully_developed="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_crop_growth.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&quarter_grown_5_7=0&half_grown_8_12=1&three_quarters_13_17=0&full_grown_18_22=0&fully_developed=0&seasonid=1&sqliteid=1

if (isset($_GET['quarter_grown_5_7']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['half_grown_8_12']) && isset($_GET['seasonid']) && isset($_GET['three_quarters_13_17']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['full_grown_18_22']) && isset($_GET['fully_developed']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$quarter_grown_5_7=validate($_GET['quarter_grown_5_7']);
$half_grown_8_12=validate($_GET['half_grown_8_12']);
$three_quarters_13_17=validate($_GET['three_quarters_13_17']);
$full_grown_18_22=validate($_GET['full_grown_18_22']);
$fully_developed=validate($_GET['fully_developed']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  //echo json_encode($growerid);
    
   }

 }


// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO crop_growth(userid,growerid,seasonid,latitude,longitude,quarter_grown_5_7,half_grown_8_12,three_quarters_13_17,full_grown_18_22,fully_developed,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long',$quarter_grown_5_7,$half_grown_8_12,$three_quarters_13_17,$full_grown_18_22,$fully_developed,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("id"=>$sqliteid);
      array_push($data,$temp);

   }else{



   }


   }else{
      $temp=array("id"=>0);
      array_push($data,$temp);
   
   }





}




echo json_encode($data);


?>





