<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require "dataSource.php";

$datasource=new DispatchNote();

$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;
$company_to_selling_pointid=$data->company_to_selling_pointid;


$data1=array();

//http://192.168.1.190/gms/api/get_season.php

// $sql = "Select distinct * from sold_bales join growers on sold_bales.growerid=growers.id CROSS  join dispatch on sold_bales.id=dispatch.sold_balesid   CROSS  join received_bales_principal on dispatch.id=received_bales_principal.dispatchid join bale_tracking_rights on received_bales_principal.userid=bale_tracking_rights.receiving_userid join users on bale_tracking_rights.delivering_userid=users.id where bale_tracking_rights.receiving_userid=$userid and sold_bales.seasonid=$seasonid and bale_tracking_rights.seasonid=$seasonid";
if ($description=="") {
  


$sql = "Select distinct dispatch_note.id,note,horse_num,trailer_num,dispatch_note_total_dispatched.quantity as dispatch_quantity,dispatch_note_total_received.quantity as received_bales,name from dispatch_note join dispatch_note_total_dispatched on dispatch_note.id=dispatch_note_total_dispatched.dispatch_noteid join dispatch_note_total_received on dispatch_note.id=dispatch_note_total_received.dispatch_noteid join users on dispatch_note.receiverid=users.id where dispatch_note.seasonid=$seasonid and dispatch_note.userid=$userid and company_to_selling_pointid=$company_to_selling_pointid";


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $value=$datasource->encryptor("encrypt",$row["id"]);
    $temp=array("note"=>$row["note"],"horse_num"=>$row["horse_num"],"trailer_num"=>$row["trailer_num"],"dispatch_quantity"=>$row["dispatch_quantity"],"received_bales"=>$row["received_bales"],"name"=>$row["name"],"id"=>$row["id"],"encryptid"=>$value);
    array_push($data1,$temp);
    
   }
 }

}else{


  // "Select distinct barcode,sold_bales.id,mass,price,sold_bales.created_at,name,sold_balesid,dispatchid from bale_tracking_rights join sold_bales on bale_tracking_rights.delivering_userid=sold_bales.userid join users on sold_bales.userid=users.id left join dispatch on sold_bales.id=dispatch.sold_balesid left join received_bales_principal on dispatch.id=received_bales_principal.dispatchid where  sold_bales.seasonid=$seasonid and bale_tracking_rights.seasonid=$seasonid and (name='$description' or surname='$description' or barcode='$description' or username='$description') order by dispatchid , sold_balesid desc "

$sql = "Select distinct dispatch_note.id,note,horse_num,trailer_num,dispatch_note_total_dispatched.quantity as dispatch_quantity,dispatch_note_total_received.quantity as received_bales,name from dispatch_note join dispatch_note_total_dispatched on dispatch_note.id=dispatch_note_total_dispatched.dispatch_noteid join dispatch_note_total_received on dispatch_note.id=dispatch_note_total_received.dispatch_noteid join users on dispatch_note.receiverid=users.id where dispatch_note.seasonid=$seasonid and dispatch_note.userid=$userid and (trailer_num='$description' or horse_num='$description' or note='$description' or name='$description' or surname='$description'  or username='$description' or dispatch_note.id='$description')  and company_to_selling_pointid=$company_to_selling_pointid" ;


$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


   $value=$datasource->encryptor("encrypt",$row["id"]);
    $temp=array("note"=>$row["note"],"horse_num"=>$row["horse_num"],"trailer_num"=>$row["trailer_num"],"dispatch_quantity"=>$row["dispatch_quantity"],"received_bales"=>$row["received_bales"],"name"=>$row["name"],"id"=>$row["id"],"encryptid"=>$value);
    array_push($data1,$temp);
    
   }
 }

}




 echo json_encode($data1);


?>





