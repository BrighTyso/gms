<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=$data->productid;


$chairman="";
$fieldOfficer="";
$area_manager="";
$growerid=0;

$data1=array();
// get grower locations

if ($productid>0) {
  
  $sql = "Select distinct growers.phone,growers.id_num,area,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid  and loans.productid=$productid order by growers.grower_num,product_amount ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


            $growerid=$row["id"];
            $fieldOfficer="";

            $sql1 = "Select * from grower_managers  where  growerid=$growerid and seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1);
           
           if ($result1->num_rows > 0) {
             // output data of each row
             while($row1 = $result1->fetch_assoc()) {

              // product id
                $chairman=$row1["chairman"];
              //  $fieldOfficer=$row1["fieldOfficer"];
                $area_manager=$row1["area_manager"];
             
             }

           }



           $sql1 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid and seasonid=$seasonid limit 1";
            $result1 = $conn->query($sql1);
             
             if ($result1->num_rows > 0) {
               // output data of each row
               while($row1 = $result1->fetch_assoc()) {
                // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
                $fieldOfficer=$row1["username"];
                
               }

            }




   $temp=array("id_num"=>$row["id_num"],"phone"=>$row["phone"],"area"=>$row["area"],"chairman"=>$chairman,"fieldOfficer"=>$fieldOfficer,"area_manager"=>$area_manager,"productid"=>$row["productid"],"name"=>$row["name"],"id"=>$row["id"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"]);
    array_push($data1,$temp);
    
   }


 }





}else{

$growerid_found=0;
$inputs=array();
$response=array();
$products=array();
$hectares="";



$sql111 = "Select distinct products.name as product_name  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid order by growers.grower_num,products.name ";
$result111 = $conn->query($sql111);
if ($result111->num_rows > 0) {
   // output data of each row
   while($row111 = $result111->fetch_assoc()) {

    $temp=array("product_name"=>$row111['product_name']);
    array_push($products,$temp);

   }
 }



$sql111 = "Select distinct growers.phone,growers.id_num,area,growers.name,growers.id,growers.surname,growers.grower_num  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid order by growers.grower_num,products.name ";
$result111 = $conn->query($sql111);
if ($result111->num_rows > 0) {
   // output data of each row
   while($row111 = $result111->fetch_assoc()) {

    $growerid_found=$row111['id'];
    $hectares="";
    $inputs=array();
    $sql = "Select distinct growers.phone,growers.id_num,area,products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,loans.created_at,verified, users.username,receipt_number,product_amount,product_total_cost  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid and growerid=$growerid_found order by growers.grower_num,product_amount ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


        $growerid=$row["id"];
        $fieldOfficer="";

        $temp=array("productid"=>$row["productid"],"id"=>$row["id"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"username"=>$row["username"],"receipt_number"=>$row["receipt_number"],"product_amount"=>$row["product_amount"],"product_total_cost"=>$row["product_total_cost"]);
        array_push($inputs,$temp);

      
    
   }


 }


  $sql1 = "Select * from grower_managers  where  growerid=$growerid_found and seasonid=$seasonid limit 1";
          $result1 = $conn->query($sql1);
           
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) {

      // product id
        $chairman=$row1["chairman"];
      //  $fieldOfficer=$row1["fieldOfficer"];
        $area_manager=$row1["area_manager"];
     
     }

   }



   $sql1 = "Select * from grower_field_officer join users on users.id=grower_field_officer.field_officerid where growerid=$growerid_found and seasonid=$seasonid limit 1";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row1 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $fieldOfficer=$row1["username"];
        
       }

    }




    $sql = "Select scheme_hectares.quantity from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id  join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid  and scheme_hectares_growers.growerid=$growerid limit 1";
    $result = $conn->query($sql);
 
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $hectares=$row["quantity"];
        
       }
     }



 $temp=array("id_num"=>$row111["id_num"],"phone"=>$row111["phone"],"area"=>$row111["area"],"chairman"=>$chairman,"fieldOfficer"=>$fieldOfficer,"area_manager"=>$area_manager,"name"=>$row111["name"],"id"=>$row111["id"],"surname"=>$row111["surname"],"grower_num"=>$row111["grower_num"],"hectares"=>$hectares,"inputs"=>$inputs);
    array_push($data1,$temp);


   }
 }



$temp=array("products"=>$products,"data"=>$data1);
array_push($response,$temp);



}







 echo json_encode($response);


?>


