<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
//$description=$data->description;

$received_mass=0;
$sold_mass=0;


$received_bales=0;
$sold_bales=0;

$data1=array();
// get grower locations

if ($userid!="") {
  

$sql11 = "Select verify_sold_bales.barcode as verify_barcode,warehousing_sold_bales.barcode,price,mass,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date from  verify_sold_bales join warehousing_sold_bales on warehousing_sold_bales.barcode=verify_sold_bales.barcode where warehousing_sold_bales.seasonid=$seasonid ";

$result = $conn->query($sql11);
$sold_bales=$result->num_rows;
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    
    
    $sold_mass+=$row["mass"];

    


   
   }
 }


$sql11 = "Select warehousing_sold_bales.barcode,price,mass,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date from  warehousing_sold_bales  join warehousing_storage_received_bales on  warehousing_storage_received_bales.warehousing_sold_balesid=warehousing_sold_bales.id where warehousing_sold_bales.seasonid=$seasonid ";

$result = $conn->query($sql11);
$received_bales=$result->num_rows;
if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    
    $received_mass+=$row["mass"];
   
   }
 }


 $temp=array("sold_mass"=>$sold_mass,"sold_bales"=>$sold_bales,"received_bales"=>$received_bales,"received_mass"=>$received_mass);
    array_push($data1,$temp);

}

 echo json_encode($data1);


?>


