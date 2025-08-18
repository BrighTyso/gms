<?php

require_once("conn.php");
require "validate.php";


$data1=array();

$seasonid=$_GET['seasonid'];
$userid=$_GET['userid'];
$rightsid=0;
$payroll_structure=0;
$field_officer_basic_salary=0;

if (isset($userid)) {


$sql = "Select id,rightsid from users  where id=$userid and active=1  order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $rightsid=$row["rightsid"];

 }
}


if ($rightsid>0) {
  // code...

$sql = "Select id from payroll_structure  where active=1  order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $payroll_structure=$row["id"];

 }
}



if ($payroll_structure==1 || $payroll_structure==0) {
  // standard salary

    $sql451 = "select  amount  from basic_salary_amounts join users on users.rightsid=basic_salary_amounts.rightsid where seasonid=$seasonid and users.id=$userid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $field_officer_basic_salary=$result45["amount"];

     }
   }

  
}else if($payroll_structure==2){
  // Hectares salary

  $sql451 = "select  amounts  from salary_allocated_hectares_basic_salary join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares_basic_salary.salary_allocated_hectaresid  where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officerid=$userid  and active=1 limit 1";
  $result451 = $conn->query($sql451);

     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

      $field_officer_basic_salary=$result45["amounts"];

     }
   }

}else{


    $sql451 = "select  amounts  from custom_based_salary join users on users.id=custom_based_salary.active_userid  where custom_based_salary.seasonid=$seasonid and active_userid=$userid  and users.active=1  limit 1";
      $result451 = $conn->query($sql451);

         if ($result451->num_rows > 0) {
         // output data of each row
         while($result45 = $result451->fetch_assoc()) {

          $field_officer_basic_salary=$result45["amounts"];

         }
       }
}


$sql = "Select userid,seasonid,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes from salary_variables order by id desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("seasonid"=>$row["seasonid"],"hectares"=>$row["hectares"],"daily_reports"=>$row["daily_reports"],"grower_visits"=>$row["grower_visits"],"system_based_tasks"=>$row["system_based_tasks"],"bike_maintenance"=>$row["bike_maintenance"],"ctl_related"=>$row["ctl_related"],"training_and_demo"=>$row["training_and_demo"],"created_at"=>$row["created_at"],"salary"=>$field_officer_basic_salary,"rightsid"=>$rightsid);
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