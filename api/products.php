<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$unit="";
$found=0;
$response=array();


if (isset($data->name) && isset($data->unit)){

$name=$data->name;
$unit=$data->unit;




$sql = "Select * from products where name='$name'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=1;
    
   }
 }

 if ($found==0) {
   $user_sql = "INSERT INTO products(name,units) VALUES ('$name','$unit')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     $temp=array("response"=>"success");
     array_push($response,$temp);

   }else{

    $temp=array("response"=>"Failed To Insert");
     array_push($response,$temp);

   }
 }else{
     $temp=array("response"=>"Product Already In DB");
     array_push($response,$temp);

 }





}else{

  $temp=array("response"=>"field cant be empty");
     array_push($response,$temp);

}

  echo json_encode($response);



?>





