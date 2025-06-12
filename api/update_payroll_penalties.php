<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$id="";

$data1=array();


if (isset($data->id)){

$id=$data->id;
$userid =$data->userid;
$hectares=$data->hectares;
$daily_reports=$data->daily_reports;
$grower_visits=$data->grower_visits;
$system_based_tasks=$data->system_based_tasks;
$bike_maintenance=$data->bike_maintenance;
$ctl_related=$data->ctl_related;
$training_and_demo=$data->training_and_demo;

$bales=$data->bales;
$recovery=$data->recovery;

$id_found=0;


$sql = "Select distinct monthly_salary_claims.id,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username from monthly_salary_claims join users on users.id=monthly_salary_claims.claimyid  where monthly_salary_claims.id=$id and monthly_salary_claims.sync=0 order by id desc ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $id_found=1;
   
   }

}


if ($id_found>0) {

      $user_sql1 = "update monthly_salary_claims set hectares=$hectares,daily_reports=$daily_reports,grower_visits=$grower_visits,system_based_tasks=$system_based_tasks,bike_maintenance=$bike_maintenance,ctl_related=$ctl_related,training_and_demo=$training_and_demo ,bales=$bales,recovery=$recovery where id=$id";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

              $temp=array("response"=>"success");
              array_push($data1,$temp);

         
        }

}else{
  $temp=array("response"=>"Cannot update");
array_push($data1,$temp);
}




  }




echo json_encode($data1);

?>





