<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;
$farm_response=0;
$claimyid=0;
$salary=0;

$fetched=0;
$processed=0;


$response=array();

if (isset($data->rightsid)  && isset($data->userid)){


$rightsid=$data->rightsid;
$seasonid=$data->seasonid;
$userid=$data->userid;
$month=$data->month;
$start_date=$data->start_date;
$end_date=$data->end_date;
$created_at=$data->created_at;
$salary_date_found=0;
$payroll_structure=0;

$date = new DateTime();
$datetimes=$date->format('H:i:s');


$sql = "Select id from payroll_structure  where active=1  order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $payroll_structure=$row["id"];

 }
}



$sql = "Select id from salary_dates_and_months  where start_date='$start_date' and end_date='$end_date' limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $salary_date_found=$row["id"];
 }
}


if ($salary_date_found==0) {

      $insert_sql = "INSERT INTO salary_dates_and_months(userid,seasonid,start_date,end_date,month,created_at) VALUES ($userid,$seasonid,'$start_date','$end_date','$month','$created_at')";
     //$gr = "select * from login";
     if ($conn->query($insert_sql)===TRUE) {
        
     }else{

     }

}




$sql = "Select distinct users.id,rightsid,username from  users where rightsid=$rightsid and users.active=1";
$result = $conn->query($sql);
 $fetched=$result->num_rows;
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $claimyid=$row["id"];
   
   if ($payroll_structure==1 || $payroll_structure==0) {
  // standard salary

    $sql451 = "select  amount  from basic_salary_amounts join users on users.rightsid=basic_salary_amounts.rightsid where seasonid=$seasonid and users.id=$claimyid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        //$field_officer_basic_salary=$result45["amount"];
        $salary=$result45["amount"];

     }
   }

  
}else if($payroll_structure==2){
  // Hectares salary

  $sql451 = "select  amounts  from salary_allocated_hectares_basic_salary join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares_basic_salary.salary_allocated_hectaresid  where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officerid=$claimyid  and active=1 limit 1";
  $result451 = $conn->query($sql451);

     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

      //$field_officer_basic_salary=$result45["amounts"];
      $salary=$result45["amounts"];

     }
   }

}else{


    $sql451 = "select  amounts  from custom_based_salary join users on users.id=custom_based_salary.active_userid  where custom_based_salary.seasonid=$seasonid and active_userid=$claimyid  and users.active=1  limit 1";
      $result451 = $conn->query($sql451);

         if ($result451->num_rows > 0) {
         // output data of each row
         while($result45 = $result451->fetch_assoc()) {

          //$field_officer_basic_salary=$result45["amounts"];
          $salary=$result45["amounts"];

         }
       }


}

   


   $farm_response=0;

//check farm
$sql13 = "Select distinct id from monthly_salary_claims  where start_date='$start_date' and end_date='$end_date' and claimyid=$claimyid and seasonid=$seasonid limit 1";
$result1 = $conn->query($sql13);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO monthly_salary_claims(userid,claimyid,seasonid,month,start_date,end_date,salary,created_at,datetimes) VALUES ($userid,$claimyid,$seasonid,'$month','$start_date','$end_date',$salary,'$created_at','$datetimes')";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
    $processed+=1;
    
 }else{

  
 }

}else{
// update amount here
}


   }

 }

 $temp=array("fetched"=>$fetched,"processed"=>$processed);
array_push($response,$temp);



}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





