<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$grower_num="";
$lat="";
$long="";
$percentage_strike="";
$strike_date="";
$seasonid=0;
$sqliteid=0;

$data1=array();
//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

$userid=$data->userid;
$seasonid=$data->seasonid;
$start_date="";
$end_date="";
$created_at=$data->created_at;
$username=$data->username;
$salary_dateid=$data->salary_dateid;
$bales=$data->bales;
$recovery=$data->recovery;



$farm_response=0;
$salary_date_found=0;


$field_officer_basic_salary=0;
$payroll_structure=0;

$min_hectares=0;
$max_hectares=0;

$allocated_hectaresid=0;
$allocated_hectares=0;



$hectare_target_amount=0;
$hectare_bonus_structureid=0;
$hectare_reward=0;
$bonus_structureid=0;
$hectare_bonus_amount=0;
$hectare_penalty=0;



$bales_bonus_amount=0;
$bales_bonus_structureid=0;
$bales_reward=0;

$target_bales=0;

$bales_penalty_structureid=0;
$bales_penalty_amount=0;
$bales_penalty=0;

$recovery_reward=0;
$claimyid=0;


$sql = "Select id from users where username='$username' order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $claimyid=$row["id"];
 }
}




$sql = "Select id,start_date,end_date from salary_dates_and_months  where id=$salary_dateid order by id desc limit 1";
$result = $conn->query($sql);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // product id
   $salary_date_found=$row["id"];
   $start_date=$row["start_date"];
   $end_date=$row["end_date"];
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




        $sql4511 = "select * from salary_recovery_target  where seasonid=$seasonid and salary_allocated_hectaresid=$allocated_hectaresid limit 1";
       $result4511 = $conn->query($sql4511);
       if ($result4511->num_rows > 0) {
       // output data of each row
       while($result4512 = $result4511->fetch_assoc()) {

        $target_recovery=$result4512["recovery"];
        
        }
      }


     }
  }



if ($allocated_hectaresid>0) {


$sql451 = "select  amount,bonus_structureid from recovery_bonus_structures_values  where seasonid=$seasonid limit 1";
  $result451 = $conn->query($sql451);
     if ($result451->num_rows > 0) {
     // output data of each row
     while($result45 = $result451->fetch_assoc()) {


        $recovery_bonus_structureid=$result45["bonus_structureid"];
        $recovery_bonus_amount=$result45["amount"];

    }
  }

if ($recovery_bonus_structureid==1) {

  if ($recovery>=$target_recovery) {
    $recovery_reward=$recovery_bonus_amount;
  }else{
    $recovery_penalty=$recovery_bonus_amount;
  }

}else{


  if ($recovery>=$target_recovery) {
    
           $my_extra_recovery=$recovery-$target_recovery;
           $recovery_reward=$my_extra_recovery*$recovery_bonus_amount;      
     
    }else{

          $my_penalty_recovery=$target_recovery-$recovery;
          $recovery_penalty=$my_penalty_recovery*$recovery_bonus_amount;

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






$user_sql1 = "update monthly_salary_claims set bales=$bales_reward,recovery=$recovery_reward where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    // $temp=array("response"=>"Amount Updated");
    // array_push($data,$temp);

      $user_sql1 = "update monthly_salary_claims_penalty set bales=$bales_penalty,recovery=$recovery_penalty where start_date='$start_date' and end_date='$end_date' and  claimyid=$claimyid and sync=0";
     //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {



      $temp=array("response"=>"Amount Updated","bales_penalty"=>$bales_penalty,"recovery_penalty"=>$recovery_penalty,"bales_reward"=>$bales_reward,"recovery_reward"=>$recovery_reward);
      array_push($data1,$temp);

      }


    }






  

echo json_encode($data1);


?>





