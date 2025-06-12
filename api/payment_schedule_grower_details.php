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
$security_otp_found=0;
$fetched_records=0;
$processed_records=0;
$found_production=0;
$otp_production="";
$payment_found=0;
$growerid=0;

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){

  $userid=$data->userid;
  $seasonid=$data->seasonid;
  $grower_payment_scheduleid=$data->grower_payment_scheduleid;
  $payment_details_found=0;
  $name=$data->name;
  $surname=$data->surname;
  $gender=$data->gender;
  




  $sql ="Select * from payment_schedule_grower_details where  grower_payment_scheduleid=$grower_payment_scheduleid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
     $payment_details_found=$row["id"];
     
     }

   }



 if ($payment_found==0) {
  
   $user_sql = "INSERT INTO payment_schedule_grower_details(userid,seasonid,grower_payment_scheduleid,name,surname,gender) VALUES ($userid,$seasonid,$grower_payment_scheduleid,'$name','$surname','$gender')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {

   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{
    $temp=array("response"=>$conn->error);
     array_push($data1,$temp);
   }

}else{

//update details here

}



}else{

  $temp=array("response"=>"Field Empty");
array_push($data1,$temp);


}

echo json_encode($data1);

?>
