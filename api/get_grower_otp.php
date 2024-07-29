<?php
require "conn.php";
require "validate.php";



$data=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])){

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];

$sql = "Select growers_otp.seasonid,grower_num,used,sent,otp from growers_otp join growers on growers.id=growers_otp.growerid where growers_otp.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("seasonid"=>$row["seasonid"],"grower_num"=>$row["grower_num"],"otp"=>$row["otp"],"used"=>$row["used"],"sent"=>$row["sent"]);
    array_push($data,$temp);
    
   }
 }


}

 echo json_encode($data);