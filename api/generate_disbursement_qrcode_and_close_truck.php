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
$truckData=array();
$productData=array();

if (isset($data->trucknumber)){


$trucknumber=$data->trucknumber;
$password=$data->password;
$distance=$data->distance;


$new_password=($password-8)+96;


$sql = "Select * from truck_destination where trucknumber='$trucknumber' and close_open=0";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];

    $temp=array("id"=>$row['id'],"name"=>$row['driver_name'],"trucknumber"=>$row['trucknumber'],"destination"=>$row['destination'],"surname"=>$row['driver_surname'],"password"=>$new_password,"distance"=>$distance,"created_at"=>$row["created_at"]);
    array_push($truckData,$temp);
    
   }
 }



 if ($found>0) {
   
  $sql = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$found";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $temp=array("id"=>$row['id'],"name"=>$row['name'],"quantity"=>$row['quantity']);
      array_push($productData,$temp);
      
     }
   }

 }else{

 }


}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}

if ($found>0) {
  $temp=array("response"=>"success","products"=>$productData,"truck"=>$truckData);
array_push($response,$temp);
}else{
  $temp=array("response"=>"Already Created or Not Found","products"=>$productData,"truck"=>$truckData);
array_push($response,$temp);
}



echo json_encode($response);



?>





