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



$data=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($_GET['userid'])  && isset($_GET['task_url'])  && isset($_GET['duration_days'])  && isset($_GET['description']) && isset($_GET['end_at'])  && isset($_GET['created_at']) && isset($_GET['sqliteid'])  && isset($_GET['seasonid']) && isset($_GET['task_min_growers'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$task_url=validate($_GET['task_url']);
$duration_days=validate($_GET['duration_days']);
$description=validate($_GET['description']);
$end_at=validate($_GET['end_at']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);
$task_min_growers=validate($_GET['task_min_growers']);



$sql = "Select * from field_officer_task where task_url='$task_url' and userid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $taskid=$row["id"];
  
    
   }

 }


// then insert loan


  if ($taskid==0) {

   $insert_sql = "INSERT INTO field_officer_task(userid,seasonid ,task_url ,description ,duration_days ,created_at,end_at,task_min_growers) VALUES ($userid,$seasonid,'$task_url','$description',$duration_days,'$created_at','$end_at',$task_min_growers)";
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





