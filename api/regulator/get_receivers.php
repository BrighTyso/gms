<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;



$data1=array();

//http://192.168.1.190/gms/api/get_season.php

// $sql = "Select distinct * from sold_bales join growers on sold_bales.growerid=growers.id CROSS  join dispatch on sold_bales.id=dispatch.sold_balesid   CROSS  join received_bales_principal on dispatch.id=received_bales_principal.dispatchid join bale_tracking_rights on received_bales_principal.userid=bale_tracking_rights.receiving_userid join users on bale_tracking_rights.delivering_userid=users.id where bale_tracking_rights.receiving_userid=$userid and sold_bales.seasonid=$seasonid and bale_tracking_rights.seasonid=$seasonid";

  


$sql = "Select distinct name,users.id from users join bale_tracking_rights on users.id=bale_tracking_rights.receiving_userid where delivering_userid=$userid";


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $temp=array("name"=>$row["name"],"id"=>$row["id"]);
    array_push($data1,$temp);
    
   }
 }





 echo json_encode($data1);


?>





