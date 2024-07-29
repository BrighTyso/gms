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

$data1=array();

$otp_data=array();

$contact_data=array();

if (isset($data->userid)){
$otp="";
$userid=$data->userid;
$security=$data->otp;
$seasonid=$data->seasonid;


$sql = "Select * from grower_inputs_distribution_otp WHERE otp ='$security' and blocked=0 
  AND created_at > NOW() - INTERVAL 10 MINUTE; ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {

    $security_otp_found=$row["id"];   
    
   }
 }


if ($security_otp_found>0) {
  // code...


 $sql1 = "Select distinct growers_otp.growerid,grower_num,phone,otp,name,surname from scheme_hectares_growers join growers on growers.id=scheme_hectares_growers.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid join growers_otp on growers_otp.growerid=growers.id  where  scheme_hectares.seasonid=$seasonid and growers_otp.seasonid=$seasonid";
$result1 = $conn->query($sql1);

 //$fetched_records=$result1->num_rows;

 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

     $contact_data=array();

     $temp=array("to"=>$row1["phone"]);
     array_push($contact_data,$temp);


      $temp=array("grower_num"=>$row1["grower_num"],"name"=>$row1["name"],"surname"=>$row1["surname"],"otp"=>$row1["otp"],"contacts"=>$contact_data);
      array_push($data1,$temp);

      }

     }




   }else{

   }


}

echo json_encode($data1);

?>
