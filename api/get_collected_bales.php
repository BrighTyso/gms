<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$description=$data->description;
$userid=$data->userid;
$seasonid=$data->seasonid;

if ($description=="") {
  $sql = "Select distinct questionnaires_bales_answers_by_grower.id,question,bales,questionnaires_bales_answers_by_grower.latitude,questionnaires_bales_answers_by_grower.longitude,question_created_at,questionnaires_bales_answers_by_grower.created_at,questionnaires_bales_answers_by_grower.datetimes,questionnaires_bales_answers_by_grower.datetime_sync,grower_num,growers.name,growers.surname,username,area,province from questionnaires_bales_answers_by_grower join users on users.id=questionnaires_bales_answers_by_grower.userid join growers on growers.id=questionnaires_bales_answers_by_grower.growerid where questionnaires_bales_answers_by_grower.sync=0 order by questionnaires_bales_answers_by_grower.created_at desc limit 60";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $temp=array("question"=>$row["question"],"bales"=>$row["bales"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"question_created_at"=>$row["question_created_at"],"created_at"=>$row["created_at"],"datetime_sync"=>$row["datetime_sync"],"grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"username"=>$row["username"],"area"=>$row["area"],"province"=>$row["province"]);
      array_push($data1,$temp);
   
    
   }


}

}else{

$sql = "Select distinct questionnaires_bales_answers_by_grower.id,question,bales,questionnaires_bales_answers_by_grower.latitude,questionnaires_bales_answers_by_grower.longitude,question_created_at,questionnaires_bales_answers_by_grower.created_at,questionnaires_bales_answers_by_grower.datetimes,questionnaires_bales_answers_by_grower.datetime_sync,grower_num,growers.name,growers.surname,username,area,province from questionnaires_bales_answers_by_grower join users on users.id=questionnaires_bales_answers_by_grower.userid join growers on growers.id=questionnaires_bales_answers_by_grower.growerid where (username='$description' or grower_num='$description') and questionnaires_bales_answers_by_grower.sync=0 order by questionnaires_bales_answers_by_grower.created_at desc limit 60";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $temp=array("question"=>$row["question"],"bales"=>$row["bales"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"question_created_at"=>$row["question_created_at"],"created_at"=>$row["created_at"],"datetime_sync"=>$row["datetime_sync"],"grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"username"=>$row["username"],"area"=>$row["area"],"province"=>$row["province"]);
      array_push($data1,$temp);
   
    
   }

}
}



// else if ($description=="" && $seasonid!=""){

// $sql = "Select grower_visits.id,grower_visits.latitude,grower_visits.longitude,grower_visits.description,grower_visits.conditions,grower_visits.other, users.username , growers.name as grower_name , growers.surname as grower_surname , growers.grower_num  , grower_visits.created_at from grower_visits join users on users.id=grower_visits.userid  join growers on growers.id=grower_visits.growerid where  grower_visits.seasonid='$seasonid'";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {
//     // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

//     $temp=array("id"=>$row["id"],"latitude"=>$row["latitude"],"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"],"grower_surname"=>$row["grower_surname"],"grower_name"=>$row["grower_name"],"created_at"=>$row["created_at"],"description"=>$row["description"] ,"conditions"=>$row["conditions"],"other"=>$row["other"],"username"=>$row["username"]);
//     array_push($data1,$temp);
    
//    }
//  }

// }


 echo json_encode($data1); 

?>