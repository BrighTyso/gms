<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$shipment=$data->shipment;

$data1=array();
// get grower locations

if ($userid!="") {


$sql11 = "Select warehousing_sold_bales.barcode,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date,shipment_details.shipment ,warehousing_sold_bales.mass,warehousing_sold_bales.price,shipment_file.id as shipment_fileid from  warehousing_sold_bales join shipment_file on shipment_file.barcode=warehousing_sold_bales.barcode join shipment_details on shipment_file.shipment_detailsid=shipment_details.id where warehousing_sold_bales.seasonid=$seasonid and shipment_details.shipment='$shipment' or shipment_details.id=$shipment";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $shipment_fileid=$row["shipment_fileid"];
    $bale_received=0;
    
    //  $temp=array("shipment"=>$row["shipment"],"barcode"=>$row["barcode"],"mass"=>$row["mass"],"price"=>$row["price"],"sell_date"=>$row["sell_date"],"created_at"=>$row["created_at"]);
    // array_push($data1,$temp);


$sql111 = "Select warehousing_sold_bales.barcode,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date,warehousing_sold_bales.mass,warehousing_sold_bales.price from  warehousing_sold_bales join warehousing_storage_received_bales on warehousing_storage_received_bales.warehousing_sold_balesid=warehousing_sold_bales.id where warehousing_sold_bales.seasonid=$seasonid and shipment_fileid=$shipment_fileid";

$result1 = $conn->query($sql111);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {
    
 $bale_received=1;
   
  
   }
 }

       if ($bale_received==1) {
          $temp=array("shipment"=>$row["shipment"],"barcode"=>$row["barcode"],"mass"=>$row["mass"],"price"=>$row["price"],"sell_date"=>$row["sell_date"],"created_at"=>$row["created_at"],"received"=>"Yes");
          array_push($data1,$temp);
       }else{
       $temp=array("shipment"=>$row["shipment"],"barcode"=>$row["barcode"],"mass"=>$row["mass"],"price"=>$row["price"],"sell_date"=>$row["sell_date"],"created_at"=>$row["created_at"],"received"=>"No");
          array_push($data1,$temp);
       }

 
  
   }
 }





}

 echo json_encode($data1);


?>


