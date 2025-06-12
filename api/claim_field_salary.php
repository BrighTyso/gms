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

//claimyid,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes

$claimyid=validate($_GET['claimyid']);
$salary=validate($_GET['salary']);

$hectares=validate($_GET['hectares']);
$daily_reports=validate($_GET['daily_reports']);
$grower_visits=validate($_GET['grower_visits']);

$system_based_tasks=validate($_GET['system_based_tasks']);
$bike_maintenance=validate($_GET['bike_maintenance']);
$ctl_related=validate($_GET['ctl_related']);


$training_and_demo=validate($_GET['training_and_demo']);
$datetimes=validate($_GET['datetimes']);


 $farm_response=0;
 $salary_date_found=0;


 $field_officer_basic_salary=0;
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


   // $sql451 = "select  min_hectares,max_hectares  from salary_allocated_hectares join salary_allocated_hectares_basic_salary on salary_allocated_hectares_basic_salary.salary_allocated_hectaresid=salary_allocated_hectares.id join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares.id where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officer_to_salary_allocated_hectares.field_officerid=$claimyid  limit 1";

 // $sql451 = "select  min_hectares,max_hectares from salary_allocated_hectares join salary_allocated_hectares_basic_salary on salary_allocated_hectares_basic_salary.salary_allocated_hectaresid=salary_allocated_hectares.id join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares.id where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officer_to_salary_allocated_hectares.field_officerid=$claimyid  limit 1";
 //  $result451 = $conn->query($sql451);
 //     if ($result451->num_rows > 0) {
 //     // output data of each row
 //     while($result45 = $result451->fetch_assoc()) {

 //        //$field_officer_basic_salary=$result45["amount"];
 //       $min_hectares=$result45["min_hectares"];
 //       $max_hectares=$result45["max_hectares"];

 //     }
 //   }

$sql451 = "select  visits.created_at  from visits  where  visits.seasonid=$seasonid and visits.userid=$claimyid and  visits.created_at between '$start_date' and '$end_date'  order by visits.created_at desc ";
$result451 = $conn->query($sql451);
$no_of_days_worked=$result451->num_rows;

$target_days=0;
$days_penalty_structureid=0;
$days_worked_penalty_amount=0;
$days_worked_penalty=0;


$allocated_hectares=0;
$target_bales=0;
$days_worked_penalty_amount=0;
$days_penalty_structureid=0;

$sql451 = "select  days from target_working_days  where seasonid=$seasonid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $target_days=$result45["days"];
       
    }
}


$sql451 = "select  amount,bonus_structureid from days_worked_penalty_structures_values  where seasonid=$seasonid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $days_penalty_structureid=$result45["bonus_structureid"];
        $days_worked_penalty_amount=$result45["amount"];
       
    }
}


if ($days_penalty_structureid==1) {

  if ($no_of_days_worked<$target_days) {
    $days_worked_penalty=$days_worked_penalty_amount;
  }

}else{

  if ($no_of_days_worked<$target_days) {


      $my_less_days=$target_days-$no_of_days_worked;

      $days_worked_penalty=$my_less_days*$hectare_bonus_amount;

    
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




$sql451 = "select  scheme_hectares.quantity  from  growers join scheme_hectares_growers on growers.id=scheme_hectares_growers.growerid join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid join grower_field_officer on grower_field_officer.growerid=scheme_hectares_growers.growerid where  scheme_hectares.seasonid=$seasonid and grower_field_officer.field_officerid=$claimyid ";
$result451 = $conn->query($sql451);
   if ($result451->num_rows > 0) {
   // output data of each row
   while($result45 = $result451->fetch_assoc()) {

    $allocated_hectares+=$result45["quantity"];

   }
 }



$sql451 = "select  min_hectares,max_hectares,id from salary_allocated_hectares where min_hectares<=$allocated_hectares and max_hectares>=$allocated_hectares order by id desc limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {
      //$field_officer_basic_salary=$result45["amount"];

     

       $min_hectares=$result45["min_hectares"];
       $max_hectares=$result45["max_hectares"];
       $allocated_hectaresid=$result45["id"];



       $sql4511 = "select * from salary_target_bales  where seasonid=$seasonid and salary_allocated_hectaresid=$allocated_hectaresid limit 1";
       $result4511 = $conn->query($sql4511);
       if ($result4511->num_rows > 0) {
       // output data of each row
       while($result4512 = $result4511->fetch_assoc()) {


          $target_bales=$result4512["bales"];
          
          }
        }


     }
  }




if ($allocated_hectaresid>0) {
  // code...

// hectares rewards

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

  if ($hectares>=$min_hectares) {
    $hectare_reward=$hectare_bonus_amount;
  }else{
    $hectare_penalty=$hectare_bonus_amount;
  }

}else{
  
  if ($hectares>=$allocated_hectares) {

     $my_extra_hectares=$hectares-$allocated_hectares;

     $hectare_reward=$my_extra_hectares*$hectare_bonus_amount;
       
  }else{

     $my_penalty_hectares=$allocated_hectares-$hectares;

     $hectare_penalty=$my_penalty_hectares*$hectare_bonus_amount;

  }
}





$sql451 = "select  bales from loan_payments  where seasonid=$seasonid and created_at between '$start_date' and '$end_date'";
$result451 = $conn->query($sql451);
if ($result451->num_rows > 0) {
     // output data of each row
   while($result45 = $result451->fetch_assoc()) {

      $bales+=$result45["bales"];
    
    }
}



$sql451 = "select  amount,bonus_structureid from bales_bonus_structures_values  where seasonid=$seasonid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $bales_bonus_structureid=$result45["bonus_structureid"];
        $bales_bonus_amount=$result45["amount"];

    }
  }




if ($bales_bonus_structureid==1) {

  if ($bales>=$target_bales) {
    $bales_reward=$bales_bonus_amount;
  }else{
    $bales_penalty=$bales_bonus_amount;
  }

}else{


  if ($bales>=$target_bales) {
    
           $my_extra_bales=$bales-$target_bales;
           $bales_reward=$my_extra_bales*$bales_bonus_amount;      
     
    }else{

          $my_penalty_bales=$target_bales-$bales;
          $bales_penalty=$my_penalty_bales*$bales_bonus_amount;

  }
    
}


}



if ($payroll_structure==1 || $payroll_structure==0) {
  // standard salary

    $sql451 = "select  amount  from basic_salary_amounts join users on users.rightsid=basic_salary_amounts.rightsid where seasonid=$seasonid and users.id=$claimyid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

        $field_officer_basic_salary=$result45["amount"];

     }
   }

  
}else if($payroll_structure==2){
  // Hectares salary

  $sql451 = "select  amounts  from salary_allocated_hectares_basic_salary join field_officer_to_salary_allocated_hectares on field_officer_to_salary_allocated_hectares.salary_allocated_hectaresid=salary_allocated_hectares_basic_salary.salary_allocated_hectaresid  where field_officer_to_salary_allocated_hectares.seasonid=$seasonid and field_officerid=$claimyid  and active=1 limit 1";
  $result451 = $conn->query($sql451);

     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {

      $field_officer_basic_salary=$result45["amounts"];

     }
   }

}else{




    $sql451 = "select  amounts  from custom_based_salary join users on users.id=custom_based_salary.active_userid  where custom_based_salary.seasonid=$seasonid and active_userid=$claimyid  and users.active=1  limit 1";
      $result451 = $conn->query($sql451);

         if ($result451->num_rows > 0) {
         // output data of each row
         while($result45 = $result451->fetch_assoc()) {

          $field_officer_basic_salary=$result45["amounts"];

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

   $insert_sql = "INSERT INTO monthly_salary_claims(userid,claimyid,seasonid,month,start_date,end_date,salary,hectares,daily_reports,grower_visits,system_based_tasks,bike_maintenance,ctl_related,training_and_demo,created_at,datetimes,bales,recovery) VALUES ($userid,$claimyid,$seasonid,'$month','$start_date','$end_date',$field_officer_basic_salary,$hectare_reward,$daily_reports,$grower_visits,$system_based_tasks,$bike_maintenance,$ctl_related,$training_and_demo,'$created_at','$datetimes',$bales_reward,$recovery_reward)";
 //$gr = "select * from login";
 if ($conn->query($insert_sql)===TRUE) {
 
   // $temp=array("response"=>"success");
   //  array_push($data,$temp);


         $insert_sql = "INSERT INTO monthly_salary_claims_penalty(userid,claimyid,seasonid,month,start_date,end_date,days,hectares,bales,created_at,datetimes) VALUES ($userid,$claimyid,$seasonid,'$month','$start_date','$end_date',$days_worked_penalty,$hectare_penalty,$bales_penalty,'$created_at','$datetimes')";
 //$gr = "select * from login";
           if ($conn->query($insert_sql)===TRUE) {
           
             $temp=array("response"=>"success");
              array_push($data,$temp);
              
           }else{

            $temp=array("response"=>$conn->error);
              array_push($data,$temp);
           }

    
 }else{

  $temp=array("response"=>$conn->error);
    array_push($data,$temp);
 }

}else{

$user_sql1 = "update monthly_salary_claims set salary=$field_officer_basic_salary,hectares=$hectare_reward,daily_reports=$daily_reports,grower_visits=$grower_visits,system_based_tasks=$system_based_tasks,bike_maintenance=$bike_maintenance,ctl_related=$ctl_related,training_and_demo=$training_and_demo,bales=$bales_reward,recovery=$recovery_reward where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    // $temp=array("response"=>"Amount Updated");
    // array_push($data,$temp);

      $user_sql1 = "update monthly_salary_claims_penalty set days=$days_worked_penalty,hectares=$hectare_penalty,bales=$bales_penalty where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
     //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

      $temp=array("response"=>"Amount Updated");
      array_push($data,$temp);

      }


    }

  
}




  

echo json_encode($data);


?>





