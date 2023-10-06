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
$startDate=substr($data->startDate,0,-8);
$endDate=substr($data->endDate,0,-8);



if ($description=="All" || $description=="all") {
  $sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and  (sod.created_at between '$startDate' and '$endDate') order by created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"],"userid"=>$row["userid"],"seasonid"=>$row["seasonid"],"time"=>$row["time"],"eod"=>$row["eod"],"created_at"=>$row["created_at"],"eod_created_at"=>$row["eod_created_at"],"username"=>$row["username"],"time"=>$row["time"]);
    array_push($data1,$temp);
   
    
   }


}

}else{

$sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and username='$description' and (sod.created_at between '$startDate' and '$endDate')  order by created_at desc" ;
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("longitude"=>$row["longitude"],"latitude"=>$row["latitude"],"userid"=>$row["userid"],"seasonid"=>$row["seasonid"],"time"=>$row["time"],"eod"=>$row["eod"],"created_at"=>$row["created_at"],"eod_created_at"=>$row["eod_created_at"],"username"=>$row["username"],"time"=>$row["time"]);
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