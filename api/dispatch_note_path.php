<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$qrcode=$data->qrcode;



$data1=array();

//http://192.168.1.190/gms/api/get_season.php

// $sql = "Select distinct * from sold_bales join growers on sold_bales.growerid=growers.id CROSS  join dispatch on sold_bales.id=dispatch.sold_balesid   CROSS  join received_bales_principal on dispatch.id=received_bales_principal.dispatchid join bale_tracking_rights on received_bales_principal.userid=bale_tracking_rights.receiving_userid join users on bale_tracking_rights.delivering_userid=users.id where bale_tracking_rights.receiving_userid=$userid and sold_bales.seasonid=$seasonid and bale_tracking_rights.seasonid=$seasonid";

  
$sql = "Select distinct * from dispatch_note join dispatch on dispatch_note.id=dispatch.dispatch_noteid where dispatch_note.id=$qrcode  limit 1";


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("dispatch_latitude"=>$row["latitude"],"dispatch_longitude"=>$row["longitude"]);
    array_push($data1,$temp);
    
   }
 }





$sql = "Select distinct * from dispatch_note join dispatch_note_total_dispatched on dispatch_note.id=dispatch_note_total_dispatched.dispatch_noteid join users on dispatch_note.userid=users.id join dispatch_note_check_point on dispatch_note_check_point.dispatch_noteid=dispatch_note.id where dispatch_note.id=$qrcode";


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("id"=>$row["id"],"horse_num"=>$row["horse_num"],"trailer_num"=>$row["trailer_num"],"created_at"=>$row["datetime"],"name"=>$row["name"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($data1,$temp);
    
   }
 }






 echo json_encode($data1);


?>





