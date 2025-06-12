
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$data1=array();


if (isset($data->userid) && isset($data->description)) {
 
$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$disbursement_truckid=$data->disbursement_truckid;

$loans_data=array();
$company_details_data=array();
$product_items_data=array();
$location_data=array();
$season_data=array();
$receipt_number="";
$field_officer_username="";
$truck_number="";



$sql = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$disbursement_truckid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $truck_number=$row['trucknumber'];
      
     }
   }



$sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
   }
 }




if ($description=="") {


$sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers join active_growers on active_growers.growerid=growers.id  where active_growers.seasonid=$seasonid   order by active_growers.id";

  $result1 = $conn->query($sql11);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {

  $growerid=$row1["id"];
  $grower_num=$row1["grower_num"];
  $name=$row1["name"];
  $surname=$row1["surname"];
  $id_num=$row1["id_num"];
  $area=$row1["area"];

  $loans_data=array();
  $product_items_data=array();
  $location_data=array();
  $season_data=array();


  $field_officer_username="";


    $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
  $result2 = $conn->query($sql2);
   
   if ($result2->num_rows > 0) {
     // output data of each row
     while($row2 = $result2->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $field_officer_username=$row2["username"];
      
     }

   }




  $sql2 = "Select distinct * from system_receipt_number where growerid=$growerid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
        
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $receipt_number=$row2['receipt_number'];

         }
      }




// $sql = "Select scheme_hectares_growers.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,scheme_hectares_products.quantity,units,package_units,total_disbursement.created_at, users.username,scheme_hectares.quantity as hectares, scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id 
// join growers on growers.id=scheme_hectares_growers.growerid join products on scheme_hectares_products.productid=products.id join users on users.id=scheme_hectares_growers.userid join total_disbursement on total_disbursement.productid=products.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_growers.growerid=$growerid and total_disbursement.disbursement_trucksid=$disbursement_truckid order by grower_num";


  $sql = "Select scheme_hectares_growers.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,scheme_hectares_products.quantity,units,package_units,total_disbursement.created_at, users.username,scheme_hectares.quantity as hectares, scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id 
join growers on growers.id=scheme_hectares_growers.growerid join products on scheme_hectares_products.productid=products.id join users on users.id=scheme_hectares_growers.userid join total_disbursement on total_disbursement.productid=products.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_growers.growerid=$growerid and total_disbursement.disbursement_trucksid=$disbursement_truckid order by grower_num";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("package_units"=>$row["package_units"],"productid"=>$row["productid"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"receipt_number"=>$receipt_number,"loanid"=>$row["loanid"],"farmer_comment"=>"","adjustment_quantity"=>"0","hectares"=>$row["hectares"],"adjust"=>"0");
    array_push($loans_data,$temp);
    
   }
 }



$sql = "Select distinct * from lat_long where growerid=$growerid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($location_data,$temp);
    
   }
 }


 $temp=array("truck_number"=>$truck_number,"grower_area"=>$row1["area"],"grower_id_num"=>$row1["id_num"],"grower_name"=>$row1["name"],"grower_surname"=>$row1["surname"],"grower_num"=>$row1["grower_num"],"created_at"=>$created_at,"inputs"=>$loans_data,"locations"=>$location_data,"company_data"=>$company_details_data,"field_officer"=>$field_officer_username);
  array_push($data1,$temp);

  }
}

}else{


  $sql11 = "Select distinct growers.id,growers.name,growers.surname,growers.grower_num,growers.area,growers.id_num from  growers  where  (grower_num='$description' or province='$description' or  grower_num  like '%$description') order by grower_num";

  $result1 = $conn->query($sql11);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {


  $growerid=$row1["id"];
  $grower_num=$row1["grower_num"];
  $name=$row1["name"];
  $surname=$row1["surname"];
  $id_num=$row1["id_num"];
  $area=$row1["area"];

  $loans_data=array();
  $product_items_data=array();
  $location_data=array();
  $season_data=array();



  $field_officer_username="";


    $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
  $result2 = $conn->query($sql2);
   
   if ($result2->num_rows > 0) {
     // output data of each row
     while($row2 = $result2->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $field_officer_username=$row2["username"];
      
     }

   }


  $sql2 = "Select distinct * from system_receipt_number where growerid=$growerid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
        
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $receipt_number=$row2['receipt_number'];

         }
      }


//  $sql = "Select scheme_hectares_growers.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,scheme_hectares_products.quantity,units,package_units,total_disbursement.created_at, users.username,scheme_hectares.quantity as hectares, scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id 
// join growers on growers.id=scheme_hectares_growers.growerid join products on scheme_hectares_products.productid=products.id join users on users.id=scheme_hectares_growers.userid join total_disbursement on total_disbursement.productid=products.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_growers.growerid=$growerid and total_disbursement.disbursement_trucksid=$disbursement_truckid order by grower_num";


$sql = "Select scheme_hectares_growers.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,scheme_hectares_products.quantity,units,package_units,total_disbursement.created_at, users.username,scheme_hectares.quantity as hectares, scheme_hectares_products.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id 
join growers on growers.id=scheme_hectares_growers.growerid join products on scheme_hectares_products.productid=products.id join users on users.id=scheme_hectares_growers.userid join total_disbursement on total_disbursement.productid=products.id where scheme_hectares.seasonid=$seasonid and scheme_hectares_growers.growerid=$growerid and total_disbursement.disbursement_trucksid=$disbursement_truckid order by grower_num";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("package_units"=>$row["package_units"],"productid"=>$row["productid"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"username"=>$row["username"],"receipt_number"=>$receipt_number,"loanid"=>$row["loanid"],"farmer_comment"=>"","adjustment_quantity"=>"0","hectares"=>$row["hectares"],"adjust"=>"0");
    array_push($loans_data,$temp);
    
   }
 }



 $sql = "Select distinct * from lat_long where growerid=$growerid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("latitude"=>$row["latitude"],"longitude"=>$row["longitude"]);
    array_push($location_data,$temp);
    
   }
 }


 $temp=array("truck_number"=>$truck_number,"grower_area"=>$row1["area"],"grower_id_num"=>$row1["id_num"],"grower_name"=>$row1["name"],"grower_surname"=>$row1["surname"],"grower_num"=>$row1["grower_num"],"created_at"=>$created_at,"inputs"=>$loans_data,"locations"=>$location_data,"company_data"=>$company_details_data,"field_officer"=>$field_officer_username);
  array_push($data1,$temp);


}


}

}



}





 echo json_encode($data1);





?>





