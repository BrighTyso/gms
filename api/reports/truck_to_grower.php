<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
#require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$disbursement_trucksid=$data->disbursement_trucksid;
#$productid=$data->productid;

$truck_number="";
$name="";
$surname="";
$destination="";
$quantity=0;
$productid=0;
$product_name="";


$data1=array();
$products=array();

//http://192.168.1.190/gms/api/get_products.php





$sql1 = "Select distinct trucknumber,driver_name,driver_surname,destination,quantity,total_disbursement.productid,name from  truck_destination  join total_disbursement on total_disbursement.disbursement_trucksid=truck_destination.id join products on total_disbursement.productid=products.id where  total_disbursement.disbursement_trucksid=$disbursement_trucksid ";
$result1 = $conn->query($sql1);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $truck_number=$row1["trucknumber"];
    $name=$row1["driver_name"];
    $surname=$row1["driver_surname"];
    $destination=$row1["destination"];
    $quantity=$row1["quantity"];
    $productid=$row1["productid"];
    $product_name=$row1["name"];




    $sql = "Select distinct grower_num,growers.name,surname,truck_to_grower.quantity,products.name as product_name from growers join truck_to_grower on growers.id=truck_to_grower.growerid join products on truck_to_grower.productid=products.id  join truck_destination on truck_destination.id=truck_to_grower.disbursement_trucksid join disbursement on disbursement.disbursement_trucksid=truck_destination.id where  truck_to_grower.disbursement_trucksid=$disbursement_trucksid and truck_to_grower.productid=$productid order by grower_num,truck_to_grower.productid";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $temp=array("grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"quantity"=>$row["quantity"] ,"product_name"=>$row["product_name"]);
        array_push($products,$temp);
        
       }
     }




$temp=array("destination"=>$destination,"name"=>$name,"surname"=>$surname,"quantity"=>$quantity ,"product_name"=>$product_name,"truck_number"=>$truck_number,"products"=>$products);
  array_push($data1,$temp);
    
   }
 }


  




 echo json_encode($data1); 



?>