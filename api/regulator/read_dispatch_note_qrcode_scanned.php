<?php

require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DispatchNote();


$userid=0;
$seasonid=0;
$description="";
$qrcode="";
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['qrcode']) && isset($_GET['userid'])){

$qrcode=$_GET['qrcode'];
$userid=$_GET['userid'];



 $value=$datasource->encryptor("decrypt",$qrcode);




//contracted_hectares

if ($qrcode!="") {

$sql = "Select distinct dispatch_note.id,receiverid,dispatch_note_total_dispatched.quantity as dispatch_quantity,dispatch_note_total_received.quantity as received_quantity,users.name from dispatch_note join dispatch_note_total_dispatched on dispatch_note_total_dispatched.dispatch_noteid=dispatch_note.id join dispatch_note_total_received on dispatch_note_total_received.dispatch_noteid=dispatch_note.id join users on dispatch_note.userid=users.id where dispatch_note.id=$value and open_close=1 and receiverid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

     
      $temp=array("id"=>$row["id"],"receiverid"=>$row["receiverid"],"dispatch_quantity"=>$row["dispatch_quantity"],"received_quantity"=>$row["received_quantity"],"company_name"=>$row["name"]);
      array_push($data1,$temp);
 
 
   }

 }


}


}



echo json_encode($data1);



?>


