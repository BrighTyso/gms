<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$companyid=0;
$created_at="";
$seasonid=0;
$user_found=0;

$roll_over=0;
$value=0;




$response=array();

if (isset($data->userid) && isset($data->created_at)  && isset($data->seasonid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$grower_num=$data->grower_num;
$created_at=$data->created_at;



$sql = "Select * from growers where grower_num='$grower_num';";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }


$sql = "Select * from roll_over_grower_amount_interest where seasonid=$seasonid and growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



$sql14 = "Select * from roll_over_interest  where roll_over_interest.seasonid=$seasonid limit 1";

  $result4 = $conn->query($sql14);
   
   if ($result4->num_rows > 0) {
     // output data of each row
     while($row4 = $result4->fetch_assoc()) {

      $value=$row4["value"];
     
     }
   }



  $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

    $result4 = $conn->query($sql14);
     
     if ($result4->num_rows > 0) {
       // output data of each row
       while($row4 = $result4->fetch_assoc()) {

        $roll_over=$row4["amount"];
       
       }
     }



$amount=$roll_over*$value/100;



if ($user_found==0 && $growerid>0 && $amount>0) {
  
    $user_sql = "INSERT INTO roll_over_grower_amount_interest(userid,seasonid,growerid,amount,created_at) VALUES ($userid,$seasonid,$growerid,$amount,'$created_at')";
       //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {

            $user_sql1 = "update rollover_total set amount=amount+$amount where growerid=$growerid and seasonid=$seasonid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {

            $temp=array("response"=>"success");
            array_push($response,$temp);

             
            }else{

              $temp=array("response"=>$conn->error);
               array_push($response,$temp);

            }

       }else{

       $temp=array("response"=>$conn->error);
       array_push($response,$temp);

       }

}else{

  if ($amount==0) {
    $temp=array("response"=>"Zero RollOver Interest");
     array_push($response,$temp);
  }else if($user_found>0){
    $temp=array("response"=>"RollOver Already Calculated");
     array_push($response,$temp);
  }else{
    $temp=array("response"=>"Grower Not Found");
     array_push($response,$temp);
  }

    
 }


}else{


$temp=array("response"=>"Field Empty");
array_push($response,$temp);
  
}


echo json_encode($response);



?>





