<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$excellent=0;
$standard=0;
$average=0;
$poor=0;
$seasonid=0;
$sqliteid=0;

$data=array();
//http://192.168.1.190/gms/api/enter_seedling_quality.php?userid=1&grower_num=777&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&excellent=0&standard=1&average=0&poor=0&seasonid=1&sqliteid=1

if (isset($_GET['userid']) && isset($_GET['seasonid']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['grower_num'])){


$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$grower_num=validate($_GET['grower_num']);
$question=validate($_GET['question']);
$comment=validate($_GET['comment']);
$next_sale_date=validate($_GET['next_sale_date']);
$question_created_at=validate($_GET['question_created_at']);
$datetimes=validate($_GET['datetimes']);
$created_at=validate($_GET['created_at']);
$sqliteid=validate($_GET['sqliteid']);



$sql = "Select * from growers where grower_num='$grower_num';";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


// then insert loan
// insert into visits(userid,growerid,seasonid,latitude,longitude,created_at,description) value(NEW.userid,NEW.growerid,NEW.seasonid,NEW.latitude,NEW.longitude,NEW.created_at,"fertilization potassium");

  if ($growerid>0) {

   $insert_sql = "INSERT INTO zero_bale_comment(userid,growerid,seasonid,question,comment,next_delivery_date,question_date,created_at,datetimes) VALUES ($userid,$growerid,$seasonid,'$question','$comment','$next_sale_date','$question_created_at','$created_at','$datetimes')";
   //$gr = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
         $temp=array("id"=>$sqliteid);
          array_push($data,$temp);

   }else{
    
   }


   }else{

   
   }


}




echo json_encode($data);


?>





