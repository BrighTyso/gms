<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at="";
$disbursement_truckid=$data->disbursement_truckid;

$loans_data=array();
$company_details_data=array();
$product_items_data=array();
$location_data=array();
$season_data=array();
$receipt_number="";
$field_officer_username="";
$truck_number="";


$truck_number="";
$destination="";
$driver_name="";
$driver_surname="";
$product_name="";
$product_quantity="";



$sql = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$disbursement_truckid ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $truck_number=$row['trucknumber'];
      $destination=$row['destination'];
      $driver_name=$row['driver_name'];
      $driver_surname=$row['driver_surname'];
      $product_name=$row['name'];
      $product_quantity=$row['quantity'];
      $productid=$row['productid'];
      $created_at=$row['created_at'];

      $field_officer_username="";

      $loans_data=array();
      $product_items_data=array();
      $location_data=array();
      $season_data=array();

  
  $sql = "Select distinct disbursed_products_grower_truck.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area,products.name as product_name,disbursed_products_grower_truck.quantity,units,package_units,disbursed_products_grower_truck.created_at, users.username,farmer_comment,adjustment_quantity,adjust from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid order by grower_num";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $growerid=$row["id"];
    $grower_num=$row["grower_num"];
    $name=$row["name"];
    $surname=$row["surname"];
    $id_num=$row["id_num"];
    $area=$row["area"];

    
    $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $field_officer_username=$row2["username"];
        
       }

     }


     $temp=array("grower_area"=>$row["area"],"grower_id_num"=>$row["id_num"],"grower_name"=>$row["name"],"grower_surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"package_units"=>$row["package_units"],"productid"=>$row["productid"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"loanid"=>$row["loanid"],"farmer_comment"=>$row["farmer_comment"],"adjustment_quantity"=>$row["adjustment_quantity"],"adjust"=>$row["adjust"]);
    array_push($loans_data,$temp);
    
   }
 }


 $temp=array("truck_number"=>$truck_number,"created_at"=>$created_at,"inputs"=>$loans_data,"destination"=>$destination,"driver_name"=>$driver_name,"driver_surname"=>$driver_surname,"product_name"=>$product_name,"product_quantity"=>$product_quantity);
  array_push($data1,$temp);



      
     }
   }






}





 echo json_encode($data1);





?>





