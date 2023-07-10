<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$username="";
$rightsid=0;
$userid=0;

$data1=array();

if (isset($data->rightsid) && isset($data->username) && isset($data->userid)){

$userid=$data->userid;
$username=$data->username;
$rightsid=$data->rightsid;
$verifyid=0;



$sql = "Select * from users where username='$username'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyid=$row["id"];
    
   }
 }


if ($verifyid!=0) {
$user_sql = "update users set rightsid=$rightsid where id=$verifyid";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     $temp=array("response"=>"success");
       array_push($data1,$temp);

   }else{

     $temp=array("response"=>$conn->error);
       array_push($data1,$temp);

   }
}else{
   $temp=array("response"=>"Username Not Found");
       array_push($data1,$temp);
 
}


}else{

	$temp=array("response"=>"Field Empty");
  array_push($data1,$temp);
}



echo json_encode($data1); 

?>





