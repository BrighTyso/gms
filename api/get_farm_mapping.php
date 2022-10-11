<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$growerid=$data->growerid;
$seasonid=$data->seasonid;
$description=$data->description;


if (isset($growerid)  && isset($description) && isset($seasonid) ) {

  if ($description=="") {
    $sql = "Select  farm_mapping.id,grower_num,farm_mapping.seasonid,farm_mapping.userid,farm_mapping.created_at,first_lat,first_long,second_lat,second_long,third_lat,third_long,forth_lat,forth_long from farm_mapping join growers on growers.id=farm_mapping.growerid where growerid=$growerid and farm_mapping.seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"],"second_lat"=>$row["second_lat"],"second_long"=>$row["second_long"],"third_lat"=>$row["third_lat"],"third_long"=>$row["third_long"],"forth_lat"=>$row["forth_lat"],"forth_long"=>$row["forth_long"]);
    array_push($data1,$temp);
   
    
   }


}
  }else{


$sql = "Select  farm_mapping.id,grower_num,farm_mapping.seasonid,farm_mapping.userid,farm_mapping.created_at,first_lat,first_long,second_lat,second_long,third_lat,third_long,forth_lat,forth_long from farm_mapping join growers on growers.id=farm_mapping.growerid where growerid=$growerid and farm_mapping.seasonid=$seasonid and grower_num='$description' or area='$description' or province='$description' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("first_lat"=>$row["first_lat"],"first_long"=>$row["first_long"],"second_lat"=>$row["second_lat"],"second_long"=>$row["second_long"],"third_lat"=>$row["third_lat"],"third_long"=>$row["third_long"],"forth_lat"=>$row["forth_lat"],"forth_long"=>$row["forth_long"]);
    array_push($data1,$temp);
   
    
   }


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