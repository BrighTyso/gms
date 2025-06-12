<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;


if ($seasonid!=""){

$sql = "Select growers.id,questionnaires_answers_by_grower.latitude,questionnaires_answers_by_grower.longitude, users.username, growers.surname as grower_surname, growers.grower_num, questionnaires_answers_by_grower.created_at from questionnaires_answers_by_grower join users on users.id=questionnaires_answers_by_grower.userid  join growers on growers.id=questionnaires_answers_by_grower.growerid where  users.id=$userid and questionnaires_answers_by_grower.created_at='$created_at'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"created_at"=>$row["created_at"],"username"=>$row["username"]);
    array_push($data1,$temp);
   
    
   }
 }

}


 echo json_encode($data1); 

?>