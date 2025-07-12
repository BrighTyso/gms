<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

if ($userid!="") {
  


$sql1 = "Select scheme_hectares_products.quantity as products_quantity,description,scheme_hectares.quantity as hectares,products.name from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares_products.scheme_hectaresid=scheme_hectares.id join products on products.id=scheme_hectares_products.productid where scheme_hectares.seasonid=$seasonid order by scheme.description";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);

   $temp=array("products_quantity"=>$row["products_quantity"],"description"=>$row["description"],"hectares"=>$row["hectares"],"name"=>$row["name"]);
    array_push($data1,$temp);

   
   }
 }


}

 echo json_encode($data1);


?>


