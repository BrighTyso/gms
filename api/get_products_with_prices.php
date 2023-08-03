<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$productid=0;
$amount="";
$seasonid=0;
$created_at="";
$found=0;
$processed_found=0;
$response=array();

if (isset($data->userid) && isset($data->seasonid) ){

$userid=$data->userid;
$seasonid=$data->seasonid;




$sql = "Select products.id,products.name from prices join products on products.id=prices.productid where  seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"id"=>$row["id"]);
    array_push($response,$temp);
    
   }
 }


}



echo json_encode($response);

?>





