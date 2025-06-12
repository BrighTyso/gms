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
$reward=validate($_GET['reward']);
$created_at=validate($_GET['created_at']);

$claimyid=validate($_GET['claimyid']);
$gift_card_created_at=validate($_GET['gift_card_created_at']);

$total_visits_percentage=validate($_GET['total_visits_percentage']);
$daily_visits_percentage=validate($_GET['daily_visits_percentage']);
$grower_percentage=validate($_GET['grower_percentage']);

 $farm_response=0;

//check farm
$sql1 = "Select id from claim_reward  where start_date='$start_date' and  end_date='$end_date'  and userid=$userid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO claim_reward(userid,claimyid,seasonid,start_date,end_date,reward,gift_card_created_at,created_at,total_visits_percentage,daily_visits_percentage,grower_percentage) VALUES ($userid,$claimyid,$seasonid,'$start_date','$end_date','$reward','$gift_card_created_at','$created_at',$total_visits_percentage,$daily_visits_percentage,$grower_percentage)";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success");
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

}else{
  $temp=array("response"=>"already claimed");
    array_push($data,$temp);
}




  

echo json_encode($data);


?>





