<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$description=$data->description;

$data1=array();
// get grower locations

if ($userid!="") {
  

$sql11 = "Select verify_sold_bales.barcode as verify_barcode,warehousing_sold_bales.barcode,price,mass,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date from  verify_sold_bales join warehousing_sold_bales on warehousing_sold_bales.barcode=verify_sold_bales.barcode where warehousing_sold_bales.seasonid=$seasonid and warehousing_sold_bales.sell_date='$description'";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    

     $temp=array("barcode"=>$row["barcode"],"sell_date"=>$row["sell_date"],"price"=>$row["price"],"mass"=>$row["mass"]);
    array_push($data1,$temp);


   
   }
 }


}

 echo json_encode($data1);


?>


