<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

 
$sql = "select data_collection.id,grower_num,grower_age,grower_sex,number_of_works,income_per_month,number_of_kids,data_collection.seasonid,data_collection.userid,data_collection.created_at,seasons.name from data_collection join growers on growers.id=data_collection.growerid join seasons on seasons.id=data_collection.seasonid  where data_collection.sync=0";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"grower_age"=>$row["grower_age"],"grower_sex"=>$row["grower_sex"],"number_of_works"=>$row["number_of_works"],"income_per_month"=>$row["income_per_month"],"number_of_kids"=>$row["number_of_kids"],"seasonid"=>$row["seasonid"],"userid"=>$row["userid"],"created_at"=>$row["created_at"],"name"=>$row["name"]);
    array_push($data1,$temp);
   
   }

}



 echo json_encode($data1); 

?>