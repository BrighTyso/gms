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

//id,start_date,end_date,system_generated_weekly_visits,weekly_visits,daily_visits,no_of_visits_per_grower,created_at

$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$start_date=validate($_GET['start_date']);
$end_date=validate($_GET['end_date']);
$system_generated_weekly_visits=validate($_GET['system_generated_weekly_visits']);
$created_at=validate($_GET['created_at']);

$weekly_visits=validate($_GET['weekly_visits']);
$daily_visits=validate($_GET['daily_visits']);

$no_of_visits_per_grower=validate($_GET['no_of_visits_per_grower']);
$sqliteid=validate($_GET['sqliteid']);
$datetimes=validate($_GET['datetimes']);

 $farm_response=0;

//check farm
$sql1 = "Select id from weekly_plan  where start_date='$start_date' and  end_date='$end_date' and userid=$userid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO weekly_plan(userid,seasonid,start_date,end_date,system_generated_weekly_visits,weekly_visits,daily_visits,no_of_visits_per_grower,created_at,datetimes) VALUES ($userid,$seasonid,'$start_date','$end_date',$system_generated_weekly_visits,$weekly_visits,$daily_visits,$no_of_visits_per_grower,'$created_at','$datetimes')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success","id"=>$sqliteid);
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error,"id"=>$sqliteid);
    array_push($data,$temp);
 }

}




  

echo json_encode($data);


?>





