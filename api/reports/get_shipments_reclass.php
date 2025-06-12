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



$sql11 = "Select warehousing_sold_bales.id,warehousing_sold_bales.barcode,price,mass,warehousing_sold_bales.created_at,warehousing_sold_bales.sell_date from  warehousing_sold_bales  join warehousing_storage_received_bales on  warehousing_storage_received_bales.warehousing_sold_balesid=warehousing_sold_bales.id where warehousing_sold_bales.seasonid=$seasonid ";

$result = $conn->query($sql11);
if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    
      $buyer_grade="";
      $sold_baleid=$row['id'];
  
      $sql111 = "Select * from  warehousing_sold_bales_reclassification  where warehousing_sold_bales_reclassification.warehousing_sold_balesid=$sold_baleid and warehousing_sold_bales_reclassification.seasonid=$seasonid ";

      $result1 = $conn->query($sql111);
      
      if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          
           $buyer_grade=$row1['buyer_grade'];

         }
       }

   
      $temp=array("barcode"=>$row['barcode'],"price"=>$row['price'],"buyer_grade"=>$buyer_grade,"floor_grades"=>$row['buyer_grade']);
      array_push($data1,$temp);

   }
 }




}

 echo json_encode($data1);


?>


