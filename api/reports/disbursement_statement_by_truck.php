<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$data1=array();




$loans_data=array();
$company_details_data=array();
$product_items_data=array();

//$sql11 = "Select growers.id from  growers join active_growers on growers.id=active_growers.growerid where active_growers.seasonid=$seasonid";


 $sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
       }
     }



if (isset($data->userid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at="";
$disbursement_truckid=$data->disbursement_truckid;

$loans_data=array();
//$company_details_data=array();
$product_items_data=array();
$location_data=array();
$season_data=array();
$receipt_number="";
$field_officer_username="";
$truck_number="";
$truck_grower_data=array();

$truck_number="";
$destination="";
$driver_name="";
$driver_surname="";
$product_name="";
$product_quantity="";
$signature_url="";
$field_officer_signature_url="";
$clerk_signature_url="";
$hectares="";
$area_manager_signature_url="";



$sql = "Select * from truck_destination join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on products.id=total_disbursement.productid where truck_destination.id=$disbursement_truckid limit 1";
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



      $sql133 = "Select distinct growerid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid order by grower_num";
      $result133 = $conn->query($sql133);
       
       if ($result133->num_rows > 0) {
         // output data of each row
         while($row133 = $result133->fetch_assoc()) {

          $truck_grower_data=array();
          $loans_data=array();
          $growerid=$row133["id"];

          $grower_name=$row133["name"];
          $grower_surname=$row133["surname"];
          $grower_num=$row133["grower_num"];
          $grower_area=$row133["area"];
          $grower_id_num=$row133["id_num"];
          $signature_url="";
          $field_officer_signature_url="";



          $sqlx = "select distinct image_location from grower_signatures_schemes join growers on growers.id=grower_signatures_schemes.growerid  where grower_num='$grower_num' and grower_signatures_schemes.seasonid=$seasonid order by grower_signatures_schemes.id desc limit 1";
          $resultx = $conn->query($sqlx);
           
           if ($resultx->num_rows > 0) {
             // output data of each row
             while($rowx = $resultx->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

            $signature_url=$rowx["image_location"];
             
             
             }
           }

      $fieldOfficerid=0;
      $sql2 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $field_officer_username=$row2["username"];
          $fieldOfficerid=$row2["field_officerid"];
         }

       }



       $sql2 = "Select * from officials_signatures  where userid=$fieldOfficerid  limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $field_officer_signature_url=$row2["image_location"];
          
         }

       }


      $verifyid=0;

       $sql2 = "Select * from disbursement_finger_print_verification_and_approval  where userid_verifier!=$fieldOfficerid and disbursement_trucksid=$disbursement_truckid and growerid=$growerid limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          //$supervisor_signature_url=$row2["image_location"];
          $verifyid=$row2["userid_verifier"];
          
         }

       }



      $sql2 = "Select * from officials_signatures  where userid=$verifyid  limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $clerk_signature_url=$row2["image_location"];
          
         }

       }





    $sql2 = "Select * from officials_signatures join users on users.id=officials_signatures.userid  where rightsid=7  limit 1";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {
         // output data of each row
         while($row2 = $result2->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $area_manager_signature_url=$row2["image_location"];
          
         }

       }




//        $field_officer_signature_url="";
// $supervisor_signature_url="";

     $sql2 = "Select scheme_hectares.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid  and scheme_hectares_growers.growerid=$growerid limit 1";
    $result2 = $conn->query($sql2);
 
     if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $hectares=$row2["quantity"];
        
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





  $temp1=array("truck_number"=>$truck_number,"grower_area"=>$row133["area"],"grower_id_num"=>$row133["id_num"],"grower_name"=>$row133["name"],"grower_surname"=>$row133["surname"],"grower_num"=>$row133["grower_num"],"created_at"=>$created_at,"locations"=>$location_data,"field_officer"=>$field_officer_username,"grower_hectares"=>$hectares,"receipt_number"=>$receipt_number);
  array_push($truck_grower_data,$temp1);


      

  
  $sql1 = "Select distinct disbursed_products_grower_truck.id as loanid, products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,id_num,area,products.name as product_name,disbursed_products_grower_truck.quantity,units,package_units,disbursed_products_grower_truck.created_at, users.username,farmer_comment,adjustment_quantity,adjust from disbursed_products_grower_truck join growers on growers.id=disbursed_products_grower_truck.growerid join products on disbursed_products_grower_truck.productid=products.id join users on users.id=disbursed_products_grower_truck.userid  where disbursed_products_grower_truck.seasonid=$seasonid and  disbursement_trucksid=$disbursement_truckid  and products.id=$productid and disbursed_products_grower_truck.growerid=$growerid order by grower_num";
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
    $receipt_number="";



        $product_id=$row1["productid"];


        $product_items_data=array();



        $sql123 = "Select distinct description,quantity,price from itemized_product join product_items on product_items.id=itemized_product.product_itemid where itemized_product.seasonid=$seasonid and itemized_product.productid=$product_id";

          $result23 = $conn->query($sql123);
           
           if ($result23->num_rows > 0) {
             // output data of each row
             while($row3 = $result23->fetch_assoc()) {

              $product_items=array("description"=>$row3["description"],"quantity"=>$row3["quantity"],"price"=>$row3["price"]);
              array_push($product_items_data,$product_items);
             
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


    




     $temp=array("grower_area"=>$row1["area"],"grower_id_num"=>$row1["id_num"],"grower_name"=>$row1["name"],"grower_surname"=>$row1["surname"],"grower_num"=>$row1["grower_num"],"package_units"=>$row1["package_units"],"productid"=>$row1["productid"],"product_name"=>$row1["product_name"],"quantity"=>$row1["quantity"],"units"=>$row1["units"],"created_at"=>$row1["created_at"],"username"=>$row1["username"],"loanid"=>$row1["loanid"],"farmer_comment"=>$row1["farmer_comment"],"adjustment_quantity"=>$row1["adjustment_quantity"],"adjust"=>$row1["adjust"],"hectares"=>$hectares,"receipt_number"=>$receipt_number);
    array_push($loans_data,$temp);
    
   }
 }



$temp=array("grower_area"=>$grower_area,"grower_id_num"=>$grower_id_num,"grower_name"=>$grower_name,"grower_surname"=>$grower_surname,"grower_num"=>$grower_num,"inputs"=>$loans_data,"company_data"=>$company_details_data,"field_officer"=>$field_officer_username,"signature_url"=>$signature_url,"hectares"=>$hectares,"receipt_number"=>$receipt_number,"truck_grower"=>$truck_grower_data,"field_officer_signature_url"=>$field_officer_signature_url,"clerk_signature_url"=>$clerk_signature_url,"area_manager_signature_url"=>$area_manager_signature_url);
array_push($data1,$temp);




 // $temp=array("truck_number"=>$truck_number,"created_at"=>$created_at,"inputs"=>$loans_data,"destination"=>$destination,"driver_name"=>$driver_name,"driver_surname"=>$driver_surname,"product_name"=>$product_name,"product_quantity"=>$product_quantity);
 //  array_push($data1,$temp);



      
     }
   }



   }
  }



}





 echo json_encode($data1);





?>





