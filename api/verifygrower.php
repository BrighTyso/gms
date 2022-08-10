<?php
require "conn.php";
require "validate.php";

$data=array();
$grower_num="";
$seasonid=0;


//http://192.168.1.190/gms/api/verifygrower.php?grower_num=12333&seasonid=1

if (isset($_GET['grower_num']) && isset($_GET['seasonid'])){


$grower_num=validate($_GET['grower_num']);
$seasonid=validate($_GET['seasonid']);

$sql = "Select * from growers where growers.grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"]);
    array_push($data,$temp);
    
   }
 }

}



echo json_encode($data);
?>





