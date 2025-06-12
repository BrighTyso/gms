<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");


$data = json_decode(file_get_contents("php://input"));


$data1=array();
$seasonid=$data->seasonid;
$id=$data->id;
$userid=$data->userid;
$number_of_visits=0;
$daily_reports=0;

$sql1 = "Select * from seasons where active=1 limit 1";
$result1 = $conn->query($sql1);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

      $seasonid=$row1["id"];

   }

 }



$sql1 = "Select * from salary_dates_and_months where id=$id limit 1";
$result1 = $conn->query($sql1);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

        $start=$row1['start_date'];
        $end=$row1['end_date'];
  
        $sql = "Select distinct monthly_salary_claims.id,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username,bales,recovery,claimyid  from monthly_salary_claims join users on users.id=monthly_salary_claims.claimyid  where start_date='$start'  and  end_date='$end' and sync=1 order by id desc ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                $monthly_salary_claims=0;
                $claimyid=$row['claimyid'];
                $salary=0;
                $hectares=0;
                $daily_reports=0;
                $grower_visits=0;
                $system_based_tasks=0;
                $bike_maintenance=0;
                $ctl_related=0;
                $training_and_demo=0;
                $number_of_visits=0;
                $daily_reports=0;
                $hectares=0;



                $bales_reward=$row['bales'];
                $recovery_reward=$row['recovery'];

                $bales_penalty=0;
                $recovery_penalty=0;
                $hectares_penalty=0;
                $days_penalty=0;




               $sql45 = "select distinct visits.growerid,visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid and visits.userid=$claimyid and visits.created_at between '$start' and '$end'  order by visits.created_at desc ";
              $result45 = $conn->query($sql45);
              $number_of_visits=$result45->num_rows;

              $sql45 = "select distinct visits.created_at  from visits join growers on growers.id=visits.growerid where  visits.seasonid=$seasonid and visits.userid=$claimyid and visits.created_at between '$start' and '$end'  order by visits.created_at desc ";
              $result45 = $conn->query($sql45);
              $daily_reports=$result45->num_rows;


              $sql451 = "select  visits.created_at,quantity,visits.growerid  from visits join growers on growers.id=visits.growerid join scheme_hectares_growers on scheme_hectares_growers.growerid=visits.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where  visits.seasonid=$seasonid and visits.userid=$claimyid and scheme_hectares.seasonid=$seasonid and visits.created_at between '$start' and '$end'  order by visits.created_at desc ";
              $result451 = $conn->query($sql451);
              
                 if ($result451->num_rows > 0) {
                 // output data of each row
                 while($result45 = $result451->fetch_assoc()) {

                  $hectares+=$result45["quantity"];

                 }
               }

                 
              
              $sql12 = "Select distinct month,start_date,end_date,days_worked,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,username from monthly_salary_claims_performance join users on users.id=monthly_salary_claims_performance.claimyid  where start_date='$start'  and  end_date='$end' and claimyid=$claimyid order by monthly_salary_claims_performance.id desc ";
              $result12 = $conn->query($sql12);
               if ($result12->num_rows > 0) {
                 // output data of each row
                 while($row12 = $result12->fetch_assoc()) {

                    $monthly_salary_claims=$row12["month"];
                    $salary=$row12["days_worked"];
                    //$hectares=$row12["hectares"];
                    $daily_reports=$row12["daily_reports"];
                    $grower_visits=$row12["grower_visits"];
                    $system_based_tasks=$row12["system_based_tasks"];
                    $bike_maintenance=$row12["bike_maintenance"];
                    $ctl_related=$row12["ctl_related"];
                    $training_and_demo=$row12["training_and_demo"];

                 }
               }



              $sql12 = "Select distinct start_date,end_date,bales,recovery,hectares,days from monthly_salary_claims_penalty join users on users.id=monthly_salary_claims_penalty.claimyid  where start_date='$start'  and  end_date='$end' and claimyid=$claimyid order by monthly_salary_claims_penalty.id desc limit 1";
              $result12 = $conn->query($sql12);
               if ($result12->num_rows > 0) {
                 // output data of each row
                 while($row12 = $result12->fetch_assoc()) {

                    $bales_penalty=$row12["bales"];
                    $recovery_penalty=$row12["recovery"];
                    $hectares_penalty=$row12["hectares"];
                    $days_penalty=$row12["days"];
                    
                 }
               }



             $temp=array("month"=>$row["month"],"start_date"=>$row["start_date"],"end_date"=>$row["end_date"],"salary"=>$row["salary"],"hectares"=>$row["hectares"],"daily_reports"=>$row["daily_reports"],"grower_visits"=>$row["grower_visits"],"system_based_tasks"=>$row["system_based_tasks"],"bike_maintenance"=>$row["bike_maintenance"],"ctl_related"=>$row["ctl_related"]
        ,"training_and_demo"=>$row["training_and_demo"],"id"=>$row["id"],"username"=>$row["username"],
          "days_worked"=>$salary,"hectares_performance"=>$hectares,"daily_reports_performance"=>$daily_reports,"grower_visits_performance"=>$grower_visits,"system_based_tasks_performance"=>$system_based_tasks,"bike_maintenance_performance"=>$bike_maintenance,"ctl_related_performance"=>$ctl_related
        ,"training_and_demo_performance"=>$training_and_demo,"number_of_visits"=>$number_of_visits,"daily_reports_server"=>$daily_reports,"bales_penalty"=>$bales_penalty,"recovery_penalty"=>$recovery_penalty,"hectares_penalty"=>$hectares_penalty,"days_penalty"=>$days_penalty,"recovery_reward"=>$recovery_reward,"bales_reward"=>$bales_reward

          );

            array_push($data1,$temp);
         
          
             }


          }



 }

 }





 echo json_encode($data1); 

?>