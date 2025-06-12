<?php

require_once("conn.php");
require "validate.php";


$data1=array();

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];


if (isset($userid)) {

$sql1 = "Select seasonid,rightsid,amount from basic_salary_amounts where  rightsid=8 or rightsid=9 order by rightsid desc limit 1";
$result1 = $conn->query($sql1);
 
if ($result1->num_rows > 0) {
while($row1 = $result1->fetch_assoc()) {

 
        $sql = "Select userid,seasonid,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes from salary_variables order by id desc";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

             $temp=array("seasonid"=>$row["seasonid"],"hectares"=>$row["hectares"],"daily_reports"=>$row["daily_reports"],"grower_visits"=>$row["grower_visits"],"system_based_tasks"=>$row["system_based_tasks"],"bike_maintenance"=>$row["bike_maintenance"],"ctl_related"=>$row["ctl_related"],"training_and_demo"=>$row["training_and_demo"],"created_at"=>$row["created_at"],"salary"=>$row1["amount"],"rightsid"=>$row1["rightsid"]);
            array_push($data1,$temp);
           
            
           }
           
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