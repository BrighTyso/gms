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

$hectares=0;

if (isset($data->userid)){

$userid =$data->userid;
$seasonid=$data->seasonid;

$salary_date_id=$data->salary_date_id;
$claimyid=0;
$payroll_structure="";
$hectares_amount=0;
$total_fetched=0;
$total_updated=0;


$start="";
$end="";


$id_found=0;


 $payroll_structure=0;

 $min_hectares=0;
 $max_hectares=0;

 $allocated_hectaresid=0;




$hectare_target_amount=0;
$hectare_bonus_structureid=0;
$hectare_reward=0;
$bonus_structureid=0;
$hectare_bonus_amount=0;
$hectare_penalty=0;



$bales_bonus_amount=0;
$bales_bonus_structureid=0;
$bales_reward=0;
$bales=0;
$target_bales=0;

$bales_penalty_structureid=0;
$bales_penalty_amount=0;
$bales_penalty=0;

$recovery_reward=0;




$sql = "Select distinct description from payroll_structure  where active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $payroll_structure=$row["description"];
       
   }

}


$sql = "Select distinct start_date,end_date from salary_dates_and_months  where id=$salary_date_id order by id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

       $start=$row["start_date"];
       $end=$row["end_date"];
   
   }

}



$sql = "Select distinct monthly_salary_claims.id,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username,claimyid from monthly_salary_claims join users on users.id=monthly_salary_claims.claimyid  where monthly_salary_claims.start_date='$start' and monthly_salary_claims.end_date='$end' and monthly_salary_claims.sync=0 order by id desc ";
$result = $conn->query($sql);
 $total_fetched=$result->num_rows;
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $id=$row["id"];
        $claimyid=$row["claimyid"];
        //$hectares=0;





        $sql451 = "select  visits.created_at,quantity,visits.growerid,visits.userid  from visits join growers on growers.id=visits.growerid join scheme_hectares_growers on scheme_hectares_growers.growerid=visits.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where  visits.seasonid=$seasonid and visits.userid=$claimyid and visits.created_at between '$start' and '$end'  order by visits.created_at desc ";
              $result451 = $conn->query($sql451);
              
             if ($result451->num_rows > 0) {
             // output data of each row
             while($result45 = $result451->fetch_assoc()) {

              $hectares+=$result45["quantity"];

             }
           }



           




      $sql451 = "select  min_hectares,max_hectares,id from salary_allocated_hectares where min_hectares<=$hectares and max_hectares>=$hectares order by id desc limit 1";
        $result451 = $conn->query($sql451);
           if ($result451->num_rows > 0) {
           // output data of each row
           while($result45 = $result451->fetch_assoc()) {
            //$field_officer_basic_salary=$result45["amount"];

             $min_hectares=$result45["min_hectares"];
             $max_hectares=$result45["max_hectares"];
             $allocated_hectaresid=$result45["id"];

           }
        }





        $sql451 = "select  amount,bonus_structureid from hectares_bonus_structures_values  where seasonid=$seasonid limit 1";
        $result451 = $conn->query($sql451);
           if ($result451->num_rows > 0) {
           // output data of each row
           while($result45 = $result451->fetch_assoc()) {

              $bonus_structureid=$result45["bonus_structureid"];
              $hectare_bonus_amount=$result45["amount"];
          }
        }


        if ($bonus_structureid==1) {

          if ($hectares>=$max_hectares) {
            $hectare_reward=$hectare_bonus_amount;
          }else{
            $hectare_penalty=$hectare_bonus_amount;
          }

        }else{
          
          if ($hectares>=$max_hectares) {

             $my_extra_hectares=$hectares-$max_hectares;

             $hectare_reward=$my_extra_hectares*$hectare_bonus_amount;
               
          }else{

             $my_penalty_hectares=$max_hectares-$hectares;

             $hectare_penalty=$my_penalty_hectares*$hectare_bonus_amount;

          }
        }


           // if ($payroll_structure=="" || $payroll_structure=="Standard") {
             
           // }elseif (condition) {
           //   // code...
           // }


        if ($hectare_reward>0) {
          $user_sql1 = "update monthly_salary_claims set hectares=$hectare_reward where id=$id";
         
         if ($conn->query($user_sql1)===TRUE) {

                $total_updated=$total_updated+1;
              
          }
        }
         
   
   }

}


$temp=array("response"=>"success","fetched"=>$total_fetched,"updated"=>$total_updated,"hectares"=>$hectares);
array_push($data1,$temp);


}




echo json_encode($data1);

?>





