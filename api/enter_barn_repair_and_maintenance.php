<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$barn_not_repaired="";
$barn_under_repair="";
$finished_repaired="";
$barn_working_well="";
$seasonid=0;
$sqliteid=0;



$data=array();



//http://192.168.1.190/gms/api/enter_barn_repair_and_maintenance.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&barn_not_repaired=0&barn_under_repair=1&finished_repaired=0&barn_working_well=0&seasonid=1&sqliteid=1

if (isset($_GET['barn_not_repaired']) && isset($_GET['userid'])  && isset($_GET['latitude'])  && isset($_GET['longitude'])  && isset($_GET['barn_under_repair']) && isset($_GET['seasonid']) && isset($_GET['finished_repaired']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['barn_working_well'])  && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$lat=validate($_GET['latitude']);
$long=validate($_GET['longitude']);
$grower_num=validate($_GET['grower_num']);
$barn_not_repaired=validate($_GET['barn_not_repaired']);
$barn_under_repair=validate($_GET['barn_under_repair']);
$finished_repaired=validate($_GET['finished_repaired']);
$barn_working_well=validate($_GET['barn_working_well']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



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

   $insert_sql = "INSERT INTO barn_repair_and_maintenance(userid,growerid,seasonid,latitude,longitude,barn_not_repaired,barn_under_repair,finished_repaired,barn_working_well,created_at) VALUES ($userid,$growerid,$seasonid,'$lat','$long',$barn_not_repaired,$barn_under_repair,$finished_repaired,$barn_working_well,'$created_at')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
    // $last_id = $conn->insert_id;

     $insert_sql = "insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value($userid,$growerid,$seasonid,'$lat','$long','$created_at','Barn Repair And Maintenance');";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

       }

   }else{

// $temp=array("id"=>$conn->error);
// array_push($data,$temp);


   }


   }else{

   
   }





}




echo json_encode($data);


?>





