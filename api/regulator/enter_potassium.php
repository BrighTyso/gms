<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$kg_per_ha="";
$seasonid=0;
$sqliteid=0;
$statusid=0;


$data1=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->kg_per_ha) && isset($data->userid)  && isset($data->latitude)  && isset($data->longitude)   && isset($data->season) && isset($data->created_at) && isset($data->sqliteid)  && isset($data->grower_num)){


$userid=validate($data->userid);
$season=validate($data->season);
$lat=validate($data->latitude);
$long=validate($data->longitude);
$grower_num=validate($data->grower_num);
$kg_per_ha=validate($data->kg_per_ha);
$created_at=validate($data->created_at);
$sqliteid=validate($data->sqliteid);




$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];
   
    
   }

 }




 $sql = "Select * from seasons where name='$season'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"];
   
    
   }

 }


 if ($statusid>0 && $seasonid>0) {


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

   $insert_sql = "INSERT INTO fertilization_potassium(userid,seasonid,growerid,latitude,longitude,kg_per_ha,created_at) VALUES ($userid,$seasonid,$growerid,'$lat','$long','$kg_per_ha','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $temp=array("sqliteid"=>$sqliteid);
      array_push($data1,$temp);

      }


   }

 }



}




echo json_encode($data1);


?>





