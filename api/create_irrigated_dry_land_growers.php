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

//$userid=$data->userid;
//$description=$data->description;

$userid=$data->userid;
$grower_num=$data->grower_num;
$seasonid=$data->seasonid;
$land_irrigation_growers_typeid=$data->land_irrigation_growers_typeid;
$created_at=$data->created_at;
$growerid=0;


$sql = "Select * from growers where  grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $growerid=$row["id"];
   
   }

 }

$sql = "Select * from land_irrigation_growers where  growerid=$growerid and seasonid=$seasonid ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=$row["id"];
   
   }

 }



if ($found==0 && $growerid>0) {
  
$user_sql = "INSERT INTO land_irrigation_growers(userid,growerid,seasonid,land_irrigation_growers_typeid,created_at) VALUES ($userid,$growerid,$seasonid,$land_irrigation_growers_typeid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $temp=array("response"=>"success");
     array_push($data1,$temp);

   }else{

   $temp=array("response"=>$conn->error);
   array_push($data1,$temp);

   }

}else{

$temp=array("response"=>"Already created");
array_push($data1,$temp);

}


}else{

   $temp=array("response"=>"field cant be empty");
   array_push($data1,$temp);

}




echo json_encode($data1)

?>



























