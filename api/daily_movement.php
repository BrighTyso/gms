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

if (isset($_POST['userid']) ){

$found=0;

$userid=$_POST['userid'];
$latitude=$_POST['latitude'];
$longitude=$_POST['longitude'];
$time=$_POST['datetime'];
$seasonid=$_POST['seasonid'];
$created_at=$_POST['created_at'];
$sqliteid=$_POST["sqliteid"];


//$values=json_decode($data_received,true);


//echo json_encode($values);

    $sql = "Select distinct * from daily_movement where userid=$userid and latitude='$latitude' and longitude='$longitude' and seasonid=$seasonid and datetimes='$time' and created_at='$created_at' ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row1 = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $found=1;
      
     }
   }

   if ($found==0 && $userid!=0) {


       $sql1 = "INSERT INTO daily_movement(userid,latitude,longitude,seasonid,datetimes,created_at) VALUES ($userid,'$latitude','$longitude',$seasonid,'$time','$created_at')";


     //$gr = "select * from login";
     if ($conn->query($sql1)===TRUE) {
     
       //$last_id = $conn->insert_id;

      $temp=array("id"=>$sqliteid);
      array_push($data,$temp);


     }


   }

}


echo json_encode($data);


?>





