<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

if ($userid!="") {
  


$sql11 = "Select distinct id,shipment,mass,bales,location from  shipment_details  where seasonid=$seasonid";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


    $shipment_detaitsid=$row["id"];
    $mass_count=0;

    $sql112 = "Select  warehousing_storage_received_bales.id,mass,barcode,price from  warehousing_storage_received_bales join warehousing_sold_bales on warehousing_sold_bales.id=warehousing_storage_received_bales.warehousing_sold_balesid  where shipment_detailsid=$shipment_detaitsid ";
    $result1 = $conn->query($sql112);
    $bales_count=$result1->num_rows;

      if ($result1->num_rows > 0) {
     // output data of each row
      while($row1 = $result1->fetch_assoc()) {
        $mass_count+=$row1['mass'];
       }

     }


    

     $temp=array("shipment"=>$row["shipment"],"shipment_mass"=>$row["mass"],"shipment_bales"=>$row["bales"],"mass"=>$mass_count,"bales"=>$bales_count);
    array_push($data1,$temp);

   
   }
 }





}

 echo json_encode($data1);


?>


