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

$start="";
$end="";


$sql = "Select id,seasonid,start_date,end_date,month from salary_dates_and_months order by id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $start=$row["start_date"];
    $end=$row["end_date"];

    //  $temp=array("seasonid"=>$row["seasonid"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"month"=>$row["month"],"id"=>$row["id"]);
    // array_push($data1,$temp);
   
    
   }
   
}



if ($description=="") {
  $sql = "Select distinct monthly_salary_claims.id,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username,bales,recovery from monthly_salary_claims join users on users.id=monthly_salary_claims.claimyid where start_date='$start' and end_date='$end' order by id desc ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $temp=array("month"=>$row["month"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"salary"=>$row["salary"],"hectares"=>$row["hectares"],"daily_reports"=>$row["daily_reports"],"grower_visits"=>$row["grower_visits"],"system_based_tasks"=>$row["system_based_tasks"],"bike_maintenance"=>$row["bike_maintenance"],"ctl_related"=>$row["ctl_related"]
  ,"training_and_demo"=>$row["training_and_demo"],"id"=>$row["id"],"username"=>$row["username"],"bales"=>$row["bales"],"recovery"=>$row["recovery"]);
      array_push($data1,$temp);
   
    
   }


}

}else{

$sql = "Select distinct monthly_salary_claims.id,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username,bales,recovery from monthly_salary_claims join users on users.id=monthly_salary_claims.claimyid where (users.username='$description' or users.name='$description' or  users.surname='$description' or  month='$description') and start_date='$start' and end_date='$end' order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $temp=array("month"=>$row["month"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"salary"=>$row["salary"],"hectares"=>$row["hectares"],"daily_reports"=>$row["daily_reports"],"grower_visits"=>$row["grower_visits"],"system_based_tasks"=>$row["system_based_tasks"],"bike_maintenance"=>$row["bike_maintenance"],"ctl_related"=>$row["ctl_related"]
  ,"training_and_demo"=>$row["training_and_demo"],"id"=>$row["id"],"username"=>$row["username"],"bales"=>$row["bales"],"recovery"=>$row["recovery"]);
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