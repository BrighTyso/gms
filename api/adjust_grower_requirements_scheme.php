<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$receiptnumber="";
$productid=0;
$loanid=0;
$quantity=0;
$newquantity=0;

$loan_found=1;
$truck_to_growerid=0;
$disbursement_trucksid=0;
$disbusment_quantity=0;
$created_at="";


$found=0; 
$growerid=0; 
$already_in=0;
$scheme_hectaresid=0;



$data1=array();


if (isset($data->userid) && isset($data->grower_num) && isset($data->quantity)  && isset($data->seasonid)){

$userid=$data->userid;
$grower_num=$data->grower_num;
$growerid=0;;
$hectares=$data->quantity;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$security_otp_found=0;


    // code...


$sql = "Select * from growers where  grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $growerid=$row["id"];
   
   }

 }

$sql = "Select * from scheme_hectares where  quantity='$hectares' and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $scheme_hectaresid=$row["id"];
   
   }

 }




$sql = "Select scheme_hectares.id,scheme_hectares.quantity from scheme_hectares_growers  join scheme_hectares  on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where scheme_hectares.seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $already_in=$row["id"];
   $scheme_hectares_to_verify=$row["quantity"];
   
   }

 }




$sql = "Select * from scheme_hectares_growers where  scheme_hectaresid=$scheme_hectaresid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }




if ($found==0 && $growerid>0 && $already_in==0 && $scheme_hectaresid>0 ) {
  
$user_sql = "INSERT INTO scheme_hectares_growers(userid,scheme_hectaresid,growerid) VALUES ($userid,$scheme_hectaresid,$growerid)";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
   $temp=array("response"=>"success");
    array_push($data1,$temp);
  
   }else{

 
   }

}else{

  $user_sql1 = "update scheme_hectares_growers set scheme_hectaresid=$scheme_hectaresid where growerid=$growerid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {
   
            $temp=array("response"=>"Scheme Updated");
           array_push($data1,$temp);
       }
}


}



echo json_encode($data1);

?>
