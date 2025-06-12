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

$data1=array();

if (isset($data->userid)){

$userid=$data->userid;
$company_name=$data->company_name;
$address=$data->address;
$phone_1=$data->phone_1;
$phone_2=$data->phone_2;
$phone_3=$data->phone_3;
$email=$data->email;



$sql = "Select * from company_details_and_contact limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0) {
  
$user_sql = "INSERT INTO company_details_and_contact(userid,company_name,address ,phone_1 ,phone_2 ,phone_3 ,email) VALUES ($userid,'$company_name','$address' ,'$phone_1' ,'$phone_2' ,'$phone_3' ,'$email')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{
  $temp=array("response"=>"Already Created");
     array_push($data1,$temp);
}


}else{

   $temp=array("response"=>"field empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























