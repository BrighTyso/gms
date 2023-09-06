<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

#$description=$data->description;
#$seasonid=$data->seasonid;



$sql = "Select distinct growers.id, growers.name as grower_name , growers.surname as grower_surname, growers.grower_num, growers.id_num, area, province from growers ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_name"=>validate($row["grower_name"]),"grower_num"=>validate($row["grower_num"]),"grower_surname"=>$row["grower_surname"],"id_num"=>$row["id_num"],"area"=>validate($row["area"]),"province"=>$row["province"]);
    array_push($data1,$temp);
    
   }
 }




 echo json_encode($data1); 

?>