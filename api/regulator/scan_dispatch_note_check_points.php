<?php
require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DispatchNote();


$userid=0;
$seasonid=0;
$description="";
$qrcode="";
$receiving_company="";
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_POST['qrcode'])){

$qrcode=$_POST['qrcode'];


$value=$datasource->encryptor("decrypt",$qrcode);




//contracted_hectares

if ($qrcode!="") {


$sql = "Select distinct dispatch_note.id,receiverid,dispatch_note_total_dispatched.mass as dispatch_mass,dispatch_note_total_dispatched.quantity as dispatch_quantity,dispatch_note_total_received.mass as received_mass,dispatch_note_total_received.quantity as received_quantity,users.name from dispatch_note join dispatch_note_total_dispatched on dispatch_note_total_dispatched.dispatch_noteid=dispatch_note.id join dispatch_note_total_received on dispatch_note_total_received.dispatch_noteid=dispatch_note.id join users on dispatch_note.receiverid=users.id where dispatch_note.id=$value and open_close=1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $receiving_company=$row["name"];
     
      
 
 
   }

 }



if ($receiving_company!="") {
  

$sql = "Select distinct dispatch_note.id,dispatch_note.driver_name,dispatch_note.driver_surname,dispatch_note.horse_num,dispatch_note.trailer_num,receiverid,dispatch_note_total_dispatched.mass as dispatch_mass,dispatch_note_total_dispatched.quantity as dispatch_quantity,dispatch_note_total_received.mass as received_mass,dispatch_note_total_received.quantity as received_quantity,users.name from dispatch_note join dispatch_note_total_dispatched on dispatch_note_total_dispatched.dispatch_noteid=dispatch_note.id join dispatch_note_total_received on dispatch_note_total_received.dispatch_noteid=dispatch_note.id join users on dispatch_note.userid=users.id where dispatch_note.id=$value and open_close=1 ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


      $id=$datasource->encryptor("encrypt",$row["id"]);

     
      $temp=array("receiving_company"=>$receiving_company,"id"=>$id,"receiverid"=>$row["receiverid"],"dispatch_quantity"=>$row["dispatch_quantity"],"received_quantity"=>$row["received_quantity"],"company_name"=>$row["name"],"dispatch_mass"=>$row["dispatch_mass"],"received_mass"=>$row["received_mass"],"horse"=>$row["horse_num"],"trailer"=>$row["trailer_num"],"name"=>$row["driver_name"],"surname"=>$row["driver_surname"]);
      array_push($data1,$temp);
 
 
   }

 }

}




}


}



echo json_encode($data1);



?>


