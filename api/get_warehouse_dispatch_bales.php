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
$bale_prices=array();
if ($userid!="") {
  


$sql11 = "Select  buyer_grade,bale_batch  from  warehousing_grades_batches   where seasonid=$seasonid";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $buyer_grade=$row["buyer_grade"];
    $mass=0;
    $bale_prices=array();


    $sql112 = "Select  id  from  warehousing_sold_bales_reclassification  where seasonid=$seasonid and buyer_grade='$buyer_grade'";
    $result1 = $conn->query($sql112);
    $bales_count=$result1->num_rows;


    $sql112 = "Select  warehousing_sold_bales_reclassification.id,mass  from  warehousing_sold_bales_reclassification join warehousing_sold_bales on warehousing_sold_bales_reclassification.warehousing_sold_balesid=warehousing_sold_bales.id  where warehousing_sold_bales_reclassification.seasonid=$seasonid and warehousing_sold_bales_reclassification.buyer_grade='$buyer_grade'";
    $result1 = $conn->query($sql112);
    if ($result1->num_rows > 0) {
   // output data of each row
     while($row1 = $result1->fetch_assoc()) {

        $mass+=$row1['mass'];
       }
     }
      

     $sql112 = "Select distinct  price  from  warehousing_sold_bales_reclassification join warehousing_sold_bales on warehousing_sold_bales_reclassification.warehousing_sold_balesid=warehousing_sold_bales.id  where warehousing_sold_bales_reclassification.seasonid=$seasonid and warehousing_sold_bales_reclassification.buyer_grade='$buyer_grade'";
    $result1 = $conn->query($sql112);
    if ($result1->num_rows > 0) {
   // output data of each row
     while($row1 = $result1->fetch_assoc()) {

        $temp=array("prices"=>$row1["price"]);
        array_push($bale_prices,$temp);

       }
     }


     $temp=array("buyer_grade"=>$row["buyer_grade"],"bale_batch"=>$row["bale_batch"],"bales_count"=>$bales_count,"mass"=>$mass,"prices"=>$bale_prices);
    array_push($data1,$temp);


   }
 }





}

 echo json_encode($data1);


?>


