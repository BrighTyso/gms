<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$trucknumber="";


$response=array();

if (isset($data->trucknumber)){


$trucknumber=$data->trucknumber;



$sql = "Select * from truck_destination where trucknumber='$trucknumber'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }


if ($found!=0) {
 
       $user_sql1 = "update truck_destination set close_open=0  where id = $found ";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {
         
        $last_id = $conn->insert_id;
           $temp=array("response"=>"success");
           array_push($response,$temp);

         }else{
          echo $conn->error;

          //$last_id = $conn->insert_id;
           $temp=array("response"=>"Failed To Update");
           array_push($response,$temp);

         }

}else{

  $temp=array("response"=>"Truck Not Found");
  array_push($response,$temp);

}



}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





