<?php
require "conn.php";
require "validate.php";



$response=array();
$otps=array();
$home_location=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];



$sql = "Select growers_otp.seasonid,grower_num,used,sent,otp from growers_otp join growers on growers.id=growers_otp.growerid where growers_otp.seasonid=$seasonid and grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) { 
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("seasonid"=>$row["seasonid"],"grower_num"=>$row["grower_num"],"otp"=>$row["otp"],"used"=>$row["used"],"sent"=>$row["sent"]);
    array_push($otps,$temp);
    
   }
 }



 $sql = "Select growers.grower_num, growers.name as grower_name , lat_long.latitude ,lat_long.longitude , users.username from lat_long join users on users.id=lat_long.userid join growers on growers.id=lat_long.growerid where lat_long.seasonid=$seasonid and grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("grower_name"=>$row["grower_name"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"]);
    array_push($home_location,$temp);
    
   }
 }



$temp=array("otps"=>$otps,"home_location"=>$home_location);
array_push($response,$temp);


}

 echo json_encode($response);