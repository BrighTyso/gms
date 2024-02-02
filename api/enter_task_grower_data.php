<?php
require "conn.php";
require "validate.php";

$userid=0;
$taskid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;
$task_found=0;
$growerid=0;


$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_GET['seasonid']) && isset($_GET['userid'])  && isset($_GET['task_url'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['time_created']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$task_url=validate($_GET['task_url']);
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$created_at=validate($_GET['created_at']);
$time_created=validate($_GET['time_created']);
$grower_num=validate($_GET['grower_num']);
$sqliteid=validate($_GET['sqliteid']);



$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


$sql = "Select * from field_officer_task where task_url='$task_url'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $task_found=$row["id"];
  
  
   }

 }



$sql = "Select * from task_grower_data where task_url='$task_url' and userid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $taskid=$row["id"];
  
      
   }

 }


// then insert loan


  if ($taskid==0 && $task_found>0 && $growerid>0) {

   $insert_sql = "INSERT INTO task_grower_data(userid,seasonid,growerid,task_url,latitude,longitude,created_at,time_created) VALUES ($userid,$seasonid,$growerid,'$task_url','$latitude','$longitude','$created_at','$time_created')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

      }


   }



}




echo json_encode($data);


?>





