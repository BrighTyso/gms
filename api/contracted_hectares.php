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
$hectares=0;
$growerid=0;
$created_at="";
$found=0;



if (isset($data->name) && isset($data->userid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$hectares=$data->hectares;
$growerid=$data->growerid;
$created_at=$data->created_at;




$sql = "Select * from contracted_hectares where  seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=1;
   
    
   }

 }



if ($found==0) {
  
$user_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created_at) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo $conn->error;

    echo json_encode("failed");

   }

}


}else{

  echo json_encode("field cant be empty");
}



?>



























