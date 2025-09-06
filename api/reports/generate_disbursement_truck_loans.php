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

  
  $sql1 = "Select distinct disbursed_products_grower_truck.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area,products.name as product_name,disbursed_products_grower_truck.quantity,units,package_units,disbursed_products_grower_truck.created_at, users.username,farmer_comment,adjustment_quantity,adjust from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid  and products.id=$productid order by grower_num";
$result1 = $conn->query($sql1);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $growerid=$row1["id"];
    $grower_num=$row1["grower_num"];
    $name=$row1["name"];
    $surname=$row1["surname"];
    $id_num=$row1["id_num"];
    $area=$row1["area"];
    $hectares="";
    
    $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
    $result2 = $conn->query($sql2);
     
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $field_officer_username=$row2["username"];
        
       }

     }



     $sql2 = "Select scheme_hectares.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid  and scheme_hectares_growers.growerid=$growerid limit 1";
    $result2 = $conn->query($sql2);
 
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $hectares=$row2["quantity"];
        
       }
     }


     $temp=array("grower_area"=>$row1["area"],"grower_id_num"=>$row1["id_num"],"grower_name"=>$row1["name"],"grower_surname"=>$row1["surname"],"grower_num"=>$row1["grower_num"],"package_units"=>$row1["package_units"],"productid"=>$row1["productid"],"product_name"=>$row1["product_name"],"quantity"=>$row1["quantity"],"units"=>$row1["units"],"created_at"=>$row1["created_at"],"username"=>$row1["username"],"loanid"=>$row1["loanid"],"farmer_comment"=>$row1["farmer_comment"],"adjustment_quantity"=>$row1["adjustment_quantity"],"adjust"=>$row1["adjust"],"hectares"=>$hectares);
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





