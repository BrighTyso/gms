<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select distinct loans.id,growers.grower_num,products.name as product_name,loans.quantity,units,loans.created_at,latitude,longitude,verified,seasons.name,contracted_hectares.hectares from loans join growers on growers.id=loans.growerid join seasons on seasons.id=loans.seasonid join products on loans.productid=products.id join contracted_hectares on contracted_hectares.growerid=loans.growerid where verified=1 and loans.sync=0 order by growers.grower_num";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"product_name"=>$row["product_name"],"quantity"=>$row["quantity"],"units"=>$row["units"],"created_at"=>$row["created_at"],"verified"=>$row["verified"],"name"=>$row["name"] ,"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"hectares"=>$row["hectares"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>