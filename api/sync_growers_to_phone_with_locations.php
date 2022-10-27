<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php
$seasonid=0;
$season="";

if (isset($_GET['season'])) {

$season=$_GET['season'];


$sql1 = "Select * from seasons where name='$season'";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $seasonid=$row['id'];
    
   }

 }







if ($seasonid>0) {
 
$sql = "Select name,surname,grower_num,lat_long.userid,area,province,phone,id_num,growers.created_at,lat_long.seasonid,latitude,longitude from growers left join lat_long on growers.id=lat_long.growerid where lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"userid"=>$row["userid"]
      ,"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"created_at"=>$row["created_at"],"seasonid"=>$row["seasonid"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data,$temp);
    
   }
 }

}


}






 echo json_encode($data); 



?>