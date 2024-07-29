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

if (isset($_GET['seasonid']) && isset($_GET['userid'])  && isset($_GET['battery_level']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['time'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$battery_level=validate($_GET['battery_level']);
$created_at=validate($_GET['created_at']);
$time=validate($_GET['time']);
$sqliteid=validate($_GET['sqliteid']);





$sql = "Select * from battery_level_report where created_at='$created_at' and userid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $task_found=$row["id"];
  
  
   }

 }


// then insert loan


  if ($task_found==0 ) {

   $insert_sql = "INSERT INTO battery_level_report(userid,seasonid,battery_level,time_created,created_at) VALUES ($userid,$seasonid,'$battery_level','$time','$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

      }else{

        // $temp=array("id"=>$conn->error);
        //   array_push($data,$temp);
      }


   }else{

    // if ($growerid==0) {
    //   $temp=array("growerid"=>$sqliteid);
    //   array_push($data,$temp);
    // }else if ($task_found==0) {
    //   $temp=array("task_found"=>$sqliteid);
    //   array_push($data,$temp);
    // }elseif($taskid>0){
    //   $temp=array("taskid"=>$sqliteid);
    //   array_push($data,$temp);
    // }
   
   }



}




echo json_encode($data);


?>





