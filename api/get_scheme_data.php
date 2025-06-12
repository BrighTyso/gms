<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();


if (isset($data->userid)) {

$seasonid=$data->seasonid;
$description=$data->description;
 
 if($description==""){

    $sql = "Select scheme_hectares.quantity as hectares,scheme_hectares_products.quantity,scheme_hectares.id,scheme.description,name from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares.id=scheme_hectares_products.scheme_hectaresid join products on products.id=scheme_hectares_products.productid where scheme_hectares.seasonid=$seasonid order by scheme_hectares.id";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $temp=array("description"=>$row["description"],"name"=>$row["name"],"quantity"=>$row["quantity"],"hectares"=>$row["hectares"],"id"=>$row["id"]);
        array_push($data1,$temp);
        
       }
     }

 }else{

    $sql = "Select scheme_hectares.quantity as hectares,scheme_hectares_products.quantity,scheme_hectares.id,scheme.description,name from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares.id=scheme_hectares_products.scheme_hectaresid join products on products.id=scheme_hectares_products.productid where scheme_hectares.seasonid=$seasonid and scheme.description='$description' order by scheme_hectares.id";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

         $temp=array("description"=>$row["description"],"name"=>$row["name"],"quantity"=>$row["quantity"],"hectares"=>$row["hectares"],"id"=>$row["id"]);
        array_push($data1,$temp);
        
       }
     }

 }



}





 echo json_encode($data1);


?>





