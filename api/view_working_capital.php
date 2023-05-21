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
$seasonid=$data->seasonid;


if ($description=="") {
 
$sql = "Select distinct working_capital.id,amount,working_capital.created_at,growers.grower_num,growers.name,growers.surname,working_capital.receipt_number from working_capital join growers on growers.id=working_capital.growerid where working_capital.seasonid=$seasonid order by working_capital.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("id"=>$row["id"],"amount"=>$row["amount"],"grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"created_at"=>$row["created_at"],"receipt_number"=>$row["receipt_number"]);
    array_push($data1,$temp);
   
    
   }


}

}else{

// $sql = "Select distinct loan_payments.id,amount,mass,loan_payments.created_at,growers.grower_num,growers.name,growers.surname from loan_payments join growers on growers.id=loan_payments.growerid join lat_long on lat_long.growerid=growers.id join users on users.id=lat_long.userid where  (grower_num='$description' or province='$description' or username='$description') and loan_payments.seasonid='$seasonid'";

$sql = "Select distinct working_capital.id,amount,working_capital.created_at,growers.grower_num,growers.name,growers.surname,working_capital.receipt_number from working_capital join growers on growers.id=working_capital.growerid where  (grower_num='$description' or province='$description' or receipt_number='$description') and working_capital.seasonid=$seasonid order by working_capital.id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $temp=array("id"=>$row["id"],"amount"=>$row["amount"],"grower_num"=>$row["grower_num"],"name"=>$row["name"],"surname"=>$row["surname"],"created_at"=>$row["created_at"],"receipt_number"=>$row["receipt_number"]);
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