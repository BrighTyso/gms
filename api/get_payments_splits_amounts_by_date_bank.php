<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$bank=$data->bank;
$sale_date=$data->sale_date;

$data1=array();
// get grower locations

if ($userid!="") {


$sql11 = "Select distinct grower_payment_schedule.id,growerid,grower_num,name,surname,sale_no,usd_actual_net_paid,zim_actual_net_paid,sale_date,grower_payment_schedule.created_at,payment_batch,payee,floor_id,bank,payment_ref,nid,zwl_acc,fca_acc from grower_payment_schedule join growers on growers.id=grower_payment_schedule.growerid where sale_date='$sale_date' and bank='$bank' order by grower_payment_schedule.id desc";




$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"sale_no"=>$row["sale_no"],"usd_actual_net_paid"=>$row["usd_actual_net_paid"],"zim_actual_net_paid"=>$row["zim_actual_net_paid"],"sale_date"=>$row["sale_date"],"created_at"=>$row["created_at"],"payment_batch"=>$row["payment_batch"],"payee"=>$row["payee"],"floor_id"=>$row["floor_id"]
,"bank"=>$row["bank"],"payment_ref"=>$row["payment_ref"],"nid"=>$row["nid"],"zwl_acc"=>$row["zwl_acc"],"fca_acc"=>$row["fca_acc"]);
    array_push($data1,$temp);
      
   }
 }


}

 echo json_encode($data1);


?>


