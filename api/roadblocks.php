<?php
require "conn.php";
require "validate.php";

$userid=0;
$latitude="";
$longitude="";
$seasonid="";
$time="";
$created_at="";
$sqliteid=0;



$data=array();


//http://192.168.1.190/gms/api/roadblocks.php?userid=1&latitude=12.3444&longitude1.89000=&seasonid=1&time=12:30&created_at=2022-06-12

if (isset($_POST['data']) ){

$data_received=$_POST['data'];


$values=json_decode($data_received,true);


//echo json_encode($values);


for ($row = 0; $row < count($values); $row++) {

  $found=0;

  $userid=validate($values[$row]['userid']);
  $latitude=validate($values[$row]['latitude']);
  $longitude=validate($values[$row]['longitude']);
  $time=validate($values[$row]['time']);
  $seasonid=validate($values[$row]['seasonid']);
  $created_at=validate($values[$row]['created_at']);
  $sqliteid=validate($values[$row]["sqliteid"]);


    $sql = "Select distinct * from road_blocks where userid=$userid and latitude='$latitude' and longitude='$longitude' and seasonid=$seasonid and time='$time' and created_at='$created_at' ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row1 = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $found=1;
      
     }
   }

   if ($found==0 && $userid!=0) {


       $sql1 = "INSERT INTO road_blocks(userid,latitude,longitude,seasonid,time,created_at) VALUES ($userid,'$latitude','$longitude',$seasonid,'$time','$created_at')";


     //$gr = "select * from login";
     if ($conn->query($sql1)===TRUE) {
     
       //$last_id = $conn->insert_id;

      $temp=array("id"=>$sqliteid);
      array_push($data,$temp);


     }


   }

  
}




}


echo json_encode($data);


?>





