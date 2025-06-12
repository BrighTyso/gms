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
$total_visits=validate($_GET['total_visits']);
$visits_per_day=validate($_GET['visits_per_day']);
$reward_percentage=validate($_GET['reward_percentage']);
$reward=validate($_GET['reward']);
$created_at=validate($_GET['created_at']);
$datetime=validate($_GET['datetime']);
$growers_to_visit=validate($_GET['growers_to_visit']);



 $insert_sql = "INSERT INTO gift_card(userid,seasonid,start_date,end_date,total_visits,visits_per_day,reward_percentage,reward,created_at,datetimes,growers_to_visit) VALUES ($userid,$seasonid,'$start_date','$end_date',$total_visits,$visits_per_day,$reward_percentage,'$reward','$created_at','$datetime',$growers_to_visit)";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success");
    array_push($data,$temp);
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }


  

echo json_encode($data);


?>





