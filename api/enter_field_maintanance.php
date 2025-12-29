<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;



$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude']) && isset($_GET['seasonid']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])  && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);


$weed_level=$_GET['weed_level'];
$weed_control_eff=$_GET['weed_control_eff'];
$herbicide_applied=$_GET['herbicide_applied'];
$sucker_eff=$_GET['sucker_eff'];
$sucker_chem=$_GET['sucker_chem'];
$gapping=$_GET['gapping'];
$population_uniformity=$_GET['population_uniformity'];


$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);

$datetimes=validate($_GET['datetime']);

$name=$_GET['name'];
$surname=$_GET['surname'];
$phone=$_GET['phone'];
$id_num=$_GET['id_num'];
$area=$_GET['area'];
$province=$_GET['province'];




$sql = "Select * from growers where grower_num='$grower_num' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) { 
     
     $growerid=$row["id"];
   
       
     }

   }else{

        $grower_farm_sql = "INSERT INTO growers(userid,seasonid,grower_num,name,surname,phone,id_num,area,province,created_at) VALUES ($userid,$seasonid,'$grower_num','$name','$surname','$phone','$id_num','$area','$province','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {

         }else{
          $temp=array("response"=>$conn->error,"hh"=>"kkk");
          array_push($data,$temp);
         }

     }




$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// weed_level,weed_control_eff, herbicide_applied,sucker_eff, sucker_chem, gapping,population_uniformity,created_at
// then insert loan


  if ($growerid>0) {

   $insert_sql = "INSERT INTO field_maintanance(userid,  growerid,  latitude,  longitude,  created_at,  seasonid,  weed_level, weed_control_eff, herbicide_applied, sucker_eff, sucker_chem, gapping, datetimes, population_uniformity) VALUES ($userid,  $growerid,  '$latitude',  '$longitude',  '$created_at',  $seasonid, '$weed_level', '$weed_control_eff', '$herbicide_applied', '$sucker_eff', '$sucker_chem', '$gapping', '$datetimes', '$population_uniformity')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $insert_sql = "insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value($userid,$growerid,$seasonid,'$latitude','$longitude','$created_at','Field Maintanance');";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

       }

   }else{

      $temp=array("id"=>$conn->error);
      array_push($data,$temp);

   }


   }else{

   
   }



}




echo json_encode($data);


?>





