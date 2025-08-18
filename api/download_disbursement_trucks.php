<?php
require_once("conn.php");
require "validate.php";



$trucknumber="";


$response=array();
$truckData=array();
$productData=array();
$company_details_data=array();

$trucknumber="";
$password=1996;
$distance=200;
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



$sql = "Select * from truck_destination where close_open=0 order by id desc limit 11";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $truckData=array();
    $productData=array();
    $company_details_data=array();


    $found=$row["id"];


    $sql1 = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$found";
    $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $temp=array("id"=>$row1['id'],"name"=>$row1['name'],"quantity"=>$row1['quantity'],"units"=>$row1['units'],"package_units"=>$row1['package_units']);
      array_push($productData,$temp);
      
     }
   }



    $sql1 = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$found";
    $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $temp=array("id"=>$row1['id'],"name"=>$row1['name'],"quantity"=>$row1['quantity'],"units"=>$row1['units'],"package_units"=>$row1['package_units']);
      array_push($productData,$temp);
      
     }
   }


    $temp=array("id"=>$row['id'],"name"=>$row['driver_name'],"trucknumber"=>$row['trucknumber'],"destination"=>$row['destination'],"surname"=>$row['driver_surname'],"password"=>$new_password,"distance"=>$distance,"created_at"=>$row["created_at"]);
    array_push($truckData,$temp);




    $temp=array("products"=>$productData,"truck"=>$truckData,"company_details_data"=>$company_details_data);
    array_push($response,$temp);
    
   }
 }





echo json_encode($response);



?>





