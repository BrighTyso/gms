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
 
$sql = "select visits.latitude,visits.longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,visits.description,visits.growerid,visits.created_at from visits left join grower_farm on visits.growerid=grower_farm.growerid left join barn_location on visits.growerid=barn_location.growerid where visits.seasonid=$seasonid and visits.growerid=$growerid and visits.userid=$userid  and grower_farm.seasonid=$seasonid and grower_farm.growerid=$growerid and grower_farm.userid=$userid   and barn_location.seasonid=$seasonid and barn_location.growerid=$growerid and barn_location.userid=$userid order by visits.id desc limit";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"description"=>$row["description"],"created_at"=>$row["created_at"]);
    array_push($data,$temp);
    
   }
 }
}else{

$sql = "select visits.latitude,visits.longitude,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude,barn_location.latitude as barn_latitude,barn_location.longitude as barn_longitude,visits.description,visits.growerid,visits.created_at from growers join visits on growers.id=visits.growerid join seasons on seasons.id=visits.seasonid left join grower_farm on visits.growerid=grower_farm.growerid left join barn_location on visits.growerid=barn_location.growerid where visits.seasonid=$seasonid and visits.growerid=$growerid and visits.userid=$userid  order by visits.id desc limit 100";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"barn_latitude"=>$row["barn_latitude"],"barn_longitude"=>$row["barn_longitude"],"description"=>$row["description"],"created_at"=>$row["created_at"]);
    array_push($data,$temp);
    
   }
 }


}



}



 echo json_encode($data); 


?>