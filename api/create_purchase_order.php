<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;
$order_number="";
$data1=array();

if (isset($data->userid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$supplierid=$data->supplierid;
$description=$data->description;
$order_number="";
$created_date=$data->created_date;



$sql = "Select * from purchasing_order order by id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   $order_number=$row["order_number"];

   }

 }

if ($found==0) {

 $order_number=100;

}else{

  $order_number+=1;

}
  
$user_sql = "INSERT INTO purchasing_order(userid,seasonid,supplierid,description,order_number,created_date) VALUES ($userid,$seasonid,$supplierid,'$description',$order_number,'$created_date')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }



}else{

   $temp=array("response"=>"Field Empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























