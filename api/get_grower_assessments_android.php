<?php
require "conn.php";
require "validate.php";

$data=array();


$seasonid=0;
$userid=0;
$growerid=0;
$description="";



//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['userid']) && isset($_GET["growerid"]) && isset($_GET["seasonid"])) {


$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];
$growerid=$_GET['growerid'];
$description=$_GET['description'];



if ($description=="") {
 
$sql = "select visits.latitude,visits.longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,visits.description,visits.growerid,visits.created_at,growers.name,growers.surname,username from sod join visits on sod.created_at=visits.created_at join growers on growers.id=visits.growerid join users on users.id=visits.userid left join grower_farm on visits.growerid=grower_farm.growerid left join barn_location on visits.growerid=barn_location.growerid where visits.seasonid=$seasonid and visits.growerid=$growerid and visits.userid=$userid and visits.seasonid=barn_location.seasonid and visits.seasonid=grower_farm.seasonid  order by visits.id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"barn_latitude"=>$row["barn_latitude"],"barn_longitude"=>$row["barn_longitude"],"description"=>$row["description"],"created_at"=>$row["created_at"],"name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"]);
    array_push($data,$temp);
    
   }
 }
}else{

$sql = "select visits.latitude,visits.longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,visits.description,visits.growerid,visits.created_at,growers.name,growers.surname,username from sod join visits on sod.created_at=visits.created_at join growers on growers.id=visits.growerid join users on users.id=visits.userid left join grower_farm on visits.growerid=grower_farm.growerid left join barn_location on visits.growerid=barn_location.growerid where visits.seasonid=$seasonid and visits.growerid=$growerid and visits.userid=$userid where description='$description'  order by visits.id desc limit 100";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"barn_latitude"=>$row["barn_latitude"],"barn_longitude"=>$row["barn_longitude"],"description"=>$row["description"],"created_at"=>$row["created_at"],"name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"]);
    array_push($data,$temp);
    
   }
 }


}



}



 echo json_encode($data); 


?>