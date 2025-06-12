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
$latitude=validate($_GET['latitude']);
$longitude=validate($_GET['longitude']);
$created_at=validate($_GET['created_at']);
$datetimes=validate($_GET['datetimes']);

 $farm_response=0;

//check farm
$sql1 = "Select id from live_locations where userid=$userid and seasonid=$seasonid limit 1";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $farm_response=$row['id'];
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO live_locations(userid,seasonid,latitude,longitude,created_at,datetimes) VALUES ($userid,$seasonid,'$latitude','$longitude','$created_at','$datetimes')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success");
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

}else{

// $user_sql1 = "update live_locations set latitude='$latitude', longitude='$longitude',datetimes='$datetimes', created_at='$created_at' where  userid=$userid and seasonid=$seasonid and id=$farm_response";
//    //$sql = "select * from login";
//    if ($conn->query($user_sql1)===TRUE) {

//     $temp=array("response"=>"success");
//     array_push($data,$temp);

//     }






   $insert_sql = "INSERT INTO live_locations(userid,seasonid,latitude,longitude,created_at,datetimes) VALUES ($userid,$seasonid,'$latitude','$longitude','$created_at','$datetimes')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success");
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

  

  
}




  

echo json_encode($data);


?>





