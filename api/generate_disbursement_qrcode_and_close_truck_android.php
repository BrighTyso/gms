<?php
require_once("conn.php");
require "validate.php";



$trucknumber="";


$response=array();
$truckData=array();
$productData=array();
$company_details_data=array();

if (isset($_GET['trucknumber'])){


$trucknumber=$_GET['trucknumber'];
$password=$_GET['password'];
$distance=$_GET['distance'];
$found=0;

$new_password=($password-8)+96;


$sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);

     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);

   }
 }





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

      $temp=array("id"=>$row['id'],"name"=>$row['name'],"quantity"=>$row['quantity'],"units"=>$row['units'],"package_units"=>$row['package_units']);
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
  $temp=array("response"=>"success","products"=>$productData,"truck"=>$truckData,"company_details_data"=>$company_details_data);
array_push($response,$temp);
}else{
  $temp=array("response"=>"Already Created or Not Found","products"=>$productData,"truck"=>$truckData,"company_details_data"=>$company_details_data);
array_push($response,$temp);
}



echo json_encode($response);



?>





