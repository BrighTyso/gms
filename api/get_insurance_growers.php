<?php
require "conn.php";
require "validate.php";

$data=array();


$userid=0;
$seasonid=0;
$description=$_GET["description"];
$insuranceid=0;


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['userid']) && isset($_GET["description"]) && isset($_GET["seasonid"])) {


$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];


$sql1 = "select * from insurance_users where userid=$userid limit 1";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   $insuranceid=$row['insuranceid'];
  
  
   }

 }





if ($insuranceid>0) {

  if ($description=="" ) {
 $sql = "select grower_num,name,surname,lat_long.latitude,lat_long.longitude,growers.id,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude ,hail_strike_mapping.first_lat,hail_strike_mapping.first_long from insurance_growers left join lat_long on insurance_growers.growerid=lat_long.growerid join growers on insurance_growers.growerid=growers.id left join hail_strike_mapping  on insurance_growers.growerid=hail_strike_mapping.growerid left join grower_farm on insurance_growers.growerid=grower_farm.id where insurance_growers.seasonid=$seasonid and insuranceid=$insuranceid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"] ,"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"]);
    array_push($data,$temp);
    
   }
 }

}else{

$sql = "select grower_num,name,surname,lat_long.latitude,lat_long.longitude,growers.id,grower_farm.latitude as farm_latitude,grower_farm.longitude as farm_longitude ,hail_strike_mapping.first_lat,hail_strike_mapping.first_long from insurance_growers left join lat_long on insurance_growers.growerid=lat_long.growerid join growers on insurance_growers.growerid=growers.id left join hail_strike_mapping  on insurance_growers.growerid=hail_strike_mapping.growerid left join grower_farm on insurance_growers.growerid=grower_farm.id where insurance_growers.seasonid=$seasonid and insuranceid=$insuranceid and grower_num='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"grower_num"=>$row["grower_num"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"] ,"farm_latitude"=>$row["farm_latitude"],"farm_longitude"=>$row["farm_longitude"],"first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"]);
    array_push($data,$temp);
    
   }
 }

}
}





}





 echo json_encode($data); 



?>