<?php
require "conn.php";
require "validate.php";

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;

$data=array();
//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

$userid=validate($_GET['userid']);
$seasonid=validate($_GET['seasonid']);
$start_date=validate($_GET['start_date']);
$end_date=validate($_GET['end_date']);
$month=validate($_GET['month']);
$created_at=validate($_GET['created_at']);
$claimyid=validate($_GET['claimyid']);
$daily_reports=validate($_GET['daily_reports']);
$datetimes=validate($_GET['datetimes']);


 $farm_response=0;
 $salary_date_found=0;


 $field_officer_basic_salary=0;
 $payroll_structure=0;

 $min_hectares=0;
 $max_hectares=0;



$sql = "Select id from salary_dates_and_months  where start_date='$start_date' and end_date='$end_date' order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $salary_date_found=$row["id"];
 }
}





$sql = "Select id from payroll_structure  where active=1  order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $payroll_structure=$row["id"];

 }
}




$sql451 = "select  visits.created_at,quantity,visits.growerid  from visits join growers on growers.id=visits.growerid join scheme_hectares_growers on scheme_hectares_growers.growerid=visits.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid where  visits.seasonid=$seasonid and visits.userid=$claimyid and scheme_hectares.seasonid=$seasonid and visits.created_at between '$start_date' and '$end_date'  order by visits.created_at desc ";
$result451 = $conn->query($sql451);

   if ($result451->num_rows > 0) {
   // output data of each row
   while($result45 = $result451->fetch_assoc()) {

    $hectares+=$result45["quantity"];

   }
 }

// hectares rewards
$hectare_target_amount=0;
$hectare_bonus_structureid=0;
$hectare_reward=0;



$sql451 = "select  amount,bonus_structureid from hectares_bonus_structures_values  where seasonid=$seasonid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $bonus_structureid=$result45["bonus_structureid"];
        $hectare_target_amount=$result45["amount"];

    }
  }


 $sql451 = "select  min_hectares,max_hectares  from salary_allocated_hectares join salary_allocated_hectares_basic_salary on salary_allocated_hectares_basic_salary.salary_allocated_hectaresid=salary_allocated_hectares.id join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares.id where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officer_to_salary_allocated_hectares.field_officerid=$claimyid  limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        //$field_officer_basic_salary=$result45["amount"];
       $min_hectares=$result45["min_hectares"];
       $max_hectares=$result45["max_hectares"];


     }
   }



if ($hectare_bonus_structureid==1) {

  if ($hectares>=$min_hectares) {
    $hectare_reward=$hectare_target_amount;
  }

}else{

  if ($hectares>=$min_hectares) {

      if ($hectares>$min_hectares) {

           $my_extra_hectares=$hectares-$min_hectares;

           $hectare_reward=$my_extra_hectares*$hectare_target_amount;
      }
     
    }
}


$bales_target_amount=0;
$bales_bonus_structureid=0;
$bales_reward=0;
$bales=0;
$target_bales=0;

$bales_penalty_structureid=0;
$bales_penalty_amount=0;
$bales_penalty=0;


$sql451 = "select  amount,bonus_structureid from salary_target_bales  where seasonid=$seasonid and salary_allocated_hectaresid=$salary_allocated_hectaresid limit 1";
$result451 = $conn->query($sql451);
   if ($result451->num_rows > 0) {
   // output data of each row
   while($result45 = $result451->fetch_assoc()) {

      $salary_allocated_hectaresid=$result45["salary_allocated_hectaresid"];
      $target_bales=$result45["bales"];

  }
}





$sql451 = "select  bales from loan_payments where seasonid=$seasonid and created_at between '$start_date' and '$end_date'";
$result451 = $conn->query($sql451);
if ($result451->num_rows > 0) {
     // output data of each row
   while($result45 = $result451->fetch_assoc()) {

      $bales+=$result45["bales"];
    
    }
}



$sql451 = "select  amount,bonus_structureid from bales_penalty_structures_values  where seasonid=$seasonid limit 1";
$result451 = $conn->query($sql451);
   if ($result451->num_rows > 0) {
   // output data of each row
   while($result45 = $result451->fetch_assoc()) {

      $bales_penalty_structureid=$result45["bonus_structureid"];
      $bales_penalty_amount=$result45["amount"];

  }
}



if ($bales_penalty_structureid==1) {

  if ($bales>=$target_bales) {
    $bales_reward=$bales_target_amount;
  }else{
    $bales_penalty=$bales_penalty_amount;
  }

}else{

  if ($bales>=$target_bales) {

      if ($bales>$target_bales) {

           $my_extra_bales=$bales-$target_bales;

           $bales_reward=$my_extra_bales*$bales_target_amount;

      }else{

        $my_penalty_bales=$target_bales-$bales;

        $bales_penalty=$my_penalty_bales*$bales_target_amount;

      }
     
    }  
}









if ($salary_date_found==0) {

      $insert_sql = "INSERT INTO salary_dates_and_months(userid,seasonid,start_date,end_date,month,created_at) VALUES ($userid,$seasonid,'$start_date','$end_date','$month','$created_at')";
     //$gr = "select * from login";
     if ($conn->query($insert_sql)===TRUE) {
        
     }else{

     }

}




//check farm
$sql1 = "Select id from monthly_salary_claims  where start_date='$start_date' and  end_date='$end_date'  and userid=$userid and seasonid=$seasonid limit 1";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $farm_response=1;
  
   }

 }


if ($farm_response==0) {

   $insert_sql = "INSERT INTO monthly_salary_claims_penalty(userid,claimyid,seasonid,month,start_date,end_date,days,hectares,bales,recovery,created_at,datetimes) VALUES ($userid,$claimyid,$seasonid,'$month','$start_date','$end_date',$field_officer_basic_salary,$hectare_reward,$daily_reports,$grower_visits,$system_based_tasks,$bike_maintenance,$ctl_related,$training_and_demo,'$created_at','$datetimes',$bales_reward,$recovery_reward)";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   $temp=array("response"=>"success");
    array_push($data,$temp);
    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

}else{

$user_sql1 = "update monthly_salary_claims_penalty set hectares=$hectare_reward,daily_reports=$daily_reports,grower_visits=$grower_visits,system_based_tasks=$system_based_tasks,bike_maintenance=$bike_maintenance,ctl_related=$ctl_related,training_and_demo=$training_and_demo,bales=$bales_reward,recovery=$recovery_reward where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"Amount Updated");
    array_push($data,$temp);

    }

  
}




  

echo json_encode($data);


?>





