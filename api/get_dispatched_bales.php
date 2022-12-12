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
$dispatch_noteid=$data->dispatch_noteid;
$description=$data->description;


$data1=array();

//http://192.168.1.190/gms/api/get_season.php

if ($description=="") {
  
$sql = "Select barcode,sold_bales.id,mass,price,sold_bales.created_at,dispatchid,dispatch.id as dispatchedid,sold_balesid,dispatch_noteid,users.name from dispatch_note  join dispatch on dispatch_note.id=dispatch.dispatch_noteid join sold_bales on dispatch.sold_balesid=sold_bales.id  left join received_bales_principal on dispatch.id=received_bales_principal.dispatchid join users on dispatch_note.userid=users.id where dispatch.dispatch_noteid=$dispatch_noteid  and  dispatch_note.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


   $temp=array("barcode"=>$row["barcode"],"id"=>$row["id"],"mass"=>$row["mass"],"price"=>$row["price"],"created_at"=>$row["created_at"],"dispatchid"=>$row["dispatchid"],"sold_balesid"=>$row["sold_balesid"],"name"=>$row["name"],"dispatch_noteid"=>$row["dispatch_noteid"],"dispatchedid"=>$row["dispatchedid"]);
    array_push($data1,$temp);
    
   }
 }
}else{

$sql = "Select barcode,sold_bales.id,mass,price,sold_bales.created_at,dispatchid,sold_balesid,dispatch_noteid,dispatch.id as dispatchedid,users.name from dispatch_note  join dispatch on dispatch_note.id=dispatch.dispatch_noteid join sold_bales on dispatch.sold_balesid=sold_bales.id  left join received_bales_principal on dispatch.id=received_bales_principal.dispatchid join users on dispatch_note.userid=users.id where dispatch.dispatch_noteid=$dispatch_noteid  and  dispatch_note.seasonid=$seasonid and (barcode='$description' or mass='$description' or price='$description')";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


   $temp=array("barcode"=>$row["barcode"],"id"=>$row["id"],"mass"=>$row["mass"],"price"=>$row["price"],"created_at"=>$row["created_at"],"dispatchid"=>$row["dispatchid"],"sold_balesid"=>$row["sold_balesid"],"name"=>$row["name"],"dispatch_noteid"=>$row["dispatch_noteid"],"dispatchedid"=>$row["dispatchedid"]);
    array_push($data1,$temp);
    
   }
 }
}





 echo json_encode($data1);


?>





