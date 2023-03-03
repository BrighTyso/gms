<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$grower_num="";
$seasonid=0;
$growerid=0;
$temp=0;
$temp_min=0;
$temp_max=0;
$pressure=0;
$humidity=0;
$rain=0;
$clouds=0;
$wind_speed=0;
$created_at="";
$city="";
$dt=0;
$count=0;
$found=0;
$weather_total=0;
$response=array();


if (isset($data->grower_num) && isset($data->seasonid) && isset($data->temp) && isset($data->temp_min) && isset($data->temp_max) && isset($data->pressure) && isset($data->humidity) && isset($data->rain) && isset($data->clouds) && isset($data->wind_speed) && isset($data->created_at) && isset($data->dt) && isset($data->city) && isset($data->count)){

$seasonid=$data->seasonid;
$grower_num=$data->grower_num;
$temp=$data->temp;
$temp_min=$data->temp_min;
$temp_max=$data->temp_max;
$pressure=$data->pressure;
$humidity=$data->humidity;
$rain=$data->rain;
$clouds=$data->clouds;
$wind_speed=$data->wind_speed;
$created_at=$data->created_at;
$city=$data->city;
$dt=$data->dt;
$count=$data->count;


$sql = "Select * from growers where grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $growerid=$row["id"];
    
   }
 }



$sql = "Select * from weather where created_at='$created_at' and growerid=$growerid and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
   }
 }



$sql = "Select * from grower_weather_total where seasonid=$seasonid and growerid=growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $weather_total=$row["id"];
    
   }
 }


 if ($growerid>0 && $found==0) {
   $user_sql = "INSERT INTO weather(seasonid,growerid,temp,temp_min,temp_max,pressure,humidity,rain ,clouds,wind_speed ,city,dt,count,created_at) VALUES ($seasonid,$growerid,$temp,$temp_min,$temp_max,$pressure,$humidity,$rain ,$clouds,$wind_speed ,'$city',$dt,$count,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     if ($weather_total==0) {

             $user_sql = "INSERT INTO grower_weather_total(seasonid,growerid,temp,temp_min,temp_max,pressure,humidity,rain ,clouds,wind_speed ,created_at) VALUES ($seasonid,$growerid,$temp,$temp_min,$temp_max,$pressure,$humidity,$rain ,$clouds,$wind_speed ,'$created_at')";
               //$sql = "select * from login";
             if ($conn->query($user_sql)===TRUE) {
             
               // $last_id = $conn->insert_id;
               $temp=array("response"=>"success");
               array_push($response,$temp);

             }else{

              $temp=array("response"=>"Failed to record weather");
              array_push($data1,$temp);

          }

     }else{

       $user_sql1 = "update grower_weather_total set temp=temp+$temp,temp_min=temp_min+$temp_min,temp_max=temp_max+$temp_max,pressure=pressure+$pressure,humidity=humidity+$humidity,rain=rain+$rain,clouds=clouds+$clouds,wind_speed=wind_speed+$wind_speed where growerid=$growerid and seasonid=$seasonid";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($data1,$temp);

           
          }else{

              $temp=array("response"=>"Failed to update weather");
              array_push($data1,$temp);

          }

     }
    

   }else{

    $temp=array("response"=>"Failed to record weather");
     array_push($response,$temp);

   }
 }else{

  if ($growerid==0) {
   
     $temp=array("response"=>"Grower Not Found");
     array_push($response,$temp);

  }else if($found>0){

  $temp=array("response"=>"Weather already recorded for this grower");
  array_push($response,$temp);

  }
   

 }





}else{

  $temp=array("response"=>"field cant be empty");
     array_push($response,$temp);

}

  echo json_encode($response);



?>





