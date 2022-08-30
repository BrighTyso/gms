<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$created_at="";
$found=0;


if (isset($data->name) && isset($data->created_at)){

$name=$data->name;
$created_at=$data->created_at;



$sql = "Select * from seasons where name='$name'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=1;
    
   }
 }


if ($found==0) {
   $user_sql = "INSERT INTO seasons(name,active,created_at) VALUES ('$name',1,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

    $user_sql1 = "update seasons set active=0 where id != $last_id";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {
   
     $last_id = $conn->insert_id;
     echo json_encode("success");

   }else{

    echo json_encode("failed");

   }


   }else{

    echo json_encode("failed To Update Seasons");

   }

}else{

echo json_encode("Season Already In DB");

}




}else{

	echo json_encode("field cant be empty");
}



?>





