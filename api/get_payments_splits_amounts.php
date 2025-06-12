<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
//$shipment=$data->shipment;

$data1=array();
// get grower locations

if ($userid!="") {


$sql11 = "Select distinct grower_payment_schedule.id,growerid,grower_num,sale_no,usd_actual_net_paid,zim_actual_net_paid,sale_date,grower_payment_schedule.created_at from grower_payment_schedule join growers on growers.id=grower_payment_schedule.growerid ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"sale_no"=>$row["sale_no"],"usd_actual_net_paid"=>$row["usd_actual_net_paid"],"zim_actual_net_paid"=>$row["zim_actual_net_paid"],"sale_date"=>$row["sale_date"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
      
   }
 }


}

 echo json_encode($data1);


?>


