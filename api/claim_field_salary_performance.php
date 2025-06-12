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

$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$start_date=validate($_GET['start_date']);
$end_date=validate($_GET['end_date']);
$month=validate($_GET['month']);
$created_at=validate($_GET['created_at']);

//claimyid,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes

$claimyid=validate($_GET['claimyid']);
$days_worked=validate($_GET['days_worked']);

$hectares=validate($_GET['hectares']);
$daily_reports=validate($_GET['daily_reports']);
$grower_visits=validate($_GET['grower_visits']);

$system_based_tasks=validate($_GET['system_based_tasks']);
$bike_maintenance=validate($_GET['bike_maintenance']);
$ctl_related=validate($_GET['ctl_related']);


$training_and_demo=validate($_GET['training_and_demo']);
$datetimes=validate($_GET['datetimes']);


 $farm_response=0;

//check farm
$sql1 = "Select id from monthly_salary_claims_performance  where start_date='$start_date' and  end_date='$end_date' and userid=$userid and seasonid=$seasonid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO monthly_salary_claims_performance(userid,claimyid,seasonid,month,start_date,end_date,days_worked,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes) VALUES ($userid,$claimyid,$seasonid,'$month','$start_date','$end_date',$days_worked,$hectares,$daily_reports,$grower_visits,$system_based_tasks,$bike_maintenance,$ctl_related,$training_and_demo,'$created_at','$datetimes')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"Performance Recorded");
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

}else{

$user_sql1 = "update monthly_salary_claims_performance set days_worked=$days_worked, hectares=$hectares,daily_reports=$daily_reports,grower_visits=$grower_visits,system_based_tasks=$system_based_tasks,bike_maintenance=$bike_maintenance,ctl_related=$ctl_related,training_and_demo=$training_and_demo where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"Performance Updated");
    array_push($data,$temp);

    }

  
}




  

echo json_encode($data);


?>





