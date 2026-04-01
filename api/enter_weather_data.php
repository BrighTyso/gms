<?php

require_once("conn.php");

$seasonid=1;
$userid1=1;

$username="";

$response1=Array();

$sql13 = "Select * from seasons where active=1 limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $seasonid=$row3["id"];

       
   }
 }


function getWeatherData(float $latitude, float $longitude): array
{
    $apiKey = "76f070642ab7856047ed13246375c8f2";
    $url    = "https://api.openweathermap.org/data/2.5/weather"
            . "?lat={$latitude}&lon={$longitude}"
            . "&appid={$apiKey}&units=metric";

    // ── cURL request ─────────────────────────────────────────────
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ["Accept: application/json"],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // ── Guard: cURL failed ────────────────────────────────────────
    if ($curlErr) {
        error_log("Weather API cURL error: $curlErr");
        return ["error" => "Connection failed: $curlErr"];
    }

    // ── Guard: bad HTTP response ──────────────────────────────────
    if ($httpCode !== 200) {
        error_log("Weather API HTTP $httpCode for lat=$latitude lon=$longitude");
        return ["error" => "API returned HTTP $httpCode"];
    }

    // ── Parse JSON ────────────────────────────────────────────────
    $res = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Weather API JSON parse error: " . json_last_error_msg());
        return ["error" => "Invalid JSON response"];
    }

    // ── Extract fields (mirrors your Python try/except blocks) ────

    // Rain — try 3h first, fall back to 1h (matches Python logic)
    $rain = null;
    if (isset($res["rain"]["3h"])) {
        $rain = $res["rain"]["3h"];
    } elseif (isset($res["rain"]["1h"])) {
        $rain = $res["rain"]["1h"];
    }

    if($rain==null){
        $rain=0;
    }

    // Clouds
    $clouds = $res["clouds"]["all"] ?? null;

    // Main block
    $main     = $res["main"]         ?? [];
    $pressure = $main["pressure"]    ?? null;
    $temp     = $main["temp"]        ?? null;
    $temp_min = $main["temp_min"]    ?? null;
    $temp_max = $main["temp_max"]    ?? null;
    $humidity = $main["humidity"]    ?? null;

    // Other fields
    $city    = $res["name"]          ?? null;
    $wind    = $res["wind"]["speed"] ?? null;
    $weather = $res["weather"]       ?? [];   // array of condition objects
    $sys     = $res["sys"]           ?? [];   // country, sunrise, sunset
    $dt      = $res["dt"]            ?? null; // Unix timestamp

    // ── Return structured result ──────────────────────────────────
    return [
        "city"        => $city,
        "dt"          => $dt,
        "dt_readable" => $dt ? date("Y-m-d H:i:s", $dt) : null,
        "temp"        => $temp,
        "temp_min"    => $temp_min,
        "temp_max"    => $temp_max,
        "humidity"    => $humidity,
        "pressure"    => $pressure,
        "wind_speed"  => $wind,
        "clouds"      => $clouds,
        "rain"        => $rain,
        "weather"     => $weather[0]["description"] ?? null,  // e.g. "light rain"
        "weather_icon"=> $weather[0]["icon"]         ?? null,
        "country"     => $sys["country"]             ?? null,
        "sunrise"     => isset($sys["sunrise"]) ? date("H:i", $sys["sunrise"]) : null,
        "sunset"      => isset($sys["sunset"])  ? date("H:i", $sys["sunset"])  : null,
    ];
}


// ================================================================
// USAGE — loop over your growers array (mirrors your Python loop)
// ================================================================

// $growers = [
//     ["grower_num" => "GRW001", "latitude" => -17.8252, "longitude" => 31.0335],
//     ["grower_num" => "GRW002", "latitude" => -18.9707, "longitude" => 32.6709],
//     ["grower_num" => "GRW003", "latitude" => -20.0630, "longitude" => 30.8644],
// ];

$weatherResults = [];

$sql = "Select distinct growers.grower_num, growers.name as grower_name , lat_long.latitude ,lat_long.longitude , users.username from lat_long join users on users.id=lat_long.userid join growers on growers.id=lat_long.growerid where lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($grower = $result->fetch_assoc()) {
//foreach ($growers as $grower) {

    $weather = getWeatherData($grower["latitude"], $grower["longitude"]);

    if (isset($weather["error"])) {
        error_log("Weather fetch failed for {$grower['grower_num']}: {$weather['error']}");
        continue;
    }

    $weatherResults[] = [
        "grower_num"  => $grower["grower_num"],
        "latitude"    => $grower["latitude"],
        "longitude"   => $grower["longitude"],
        "weather"     => $weather,
    ];

    // Avoid hitting API rate limit on free tier (60 calls/min)
    usleep(200000); // 0.2 second pause between calls

//}
}
}

// ── Output ────────────────────────────────────────────────────────
echo json_encode($weatherResults, JSON_PRETTY_PRINT);

// ── Example: access individual fields ────────────────────────────
foreach ($weatherResults as $result) {
    $w = $result["weather"];
    echo "\n{$result['grower_num']} — {$w['city']}, {$w['country']}";
    echo "\n  Temp     : {$w['temp']}°C (min {$w['temp_min']}°C / max {$w['temp_max']}°C)";
    echo "\n  Humidity : {$w['humidity']}%";
    echo "\n  Pressure : {$w['pressure']} hPa";
    echo "\n  Wind     : {$w['wind_speed']} m/s";
    echo "\n  Clouds   : {$w['clouds']}%";
    echo "\n  Rain     : " . ($w['rain'] !== null ? $w['rain'] . " mm" : "None");
    echo "\n  Condition: {$w['weather']}";
    echo "\n  Sunrise  : {$w['sunrise']} / Sunset: {$w['sunset']}";
    echo "\n";


$grower_num=$result['grower_num'];
$temp=$w['temp'];
$temp_min=$w['temp_min'];
$temp_max=$w['temp_max'];
$pressure=$w['pressure'];
$humidity=$w['humidity'];
$rain=$w['rain'];
$clouds=$w['clouds'];
$wind_speed=$w['wind_speed'];

$date=new DateTime();
//$start=$date->format('Y-m-d');
$created_at=$date->format('Y-m-d');
$city=$w['city'];
$dt=$w['dt'];
//$count=$w['count'];
$count=1;


$growerid=0;
$found=0;
$weather_total=0;


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



$sql = "Select * from grower_weather_total where seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $weather_total=$row["id"];
    
   }
 }


 if ($growerid>0 && $found==0) {
   $user_sql = "INSERT INTO weather(seasonid,growerid,temp,temp_min,temp_max,pressure,humidity,rain ,clouds,wind_speed ,city,dt,created_at) VALUES ($seasonid,$growerid,$temp,$temp_min,$temp_max,$pressure,$humidity,$rain ,$clouds,$wind_speed,'$city',$dt,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     if ($weather_total==0) {

             $user_sql = "INSERT INTO grower_weather_total(seasonid,growerid,temp,temp_min,temp_max,pressure,humidity,rain ,clouds,wind_speed ,created_at) VALUES ($seasonid,$growerid,$temp,$temp_min,$temp_max,$pressure,$humidity,$rain ,$clouds,$wind_speed ,'$created_at')";
               //$sql = "select * from login";
             if ($conn->query($user_sql)===TRUE) {
             
               // $last_id = $conn->insert_id;
               $temp=array("response"=>"success");
               array_push($response1,$temp);

             }else{

              $temp=array("response"=>"Failed to record weather");
              array_push($response1,$temp);

          }

     }else{

       $user_sql1 = "update grower_weather_total set temp=temp+$temp,temp_min=temp_min+$temp_min,temp_max=temp_max+$temp_max,pressure=pressure+$pressure,humidity=humidity+$humidity,rain=rain+$rain,clouds=clouds+$clouds,wind_speed=wind_speed+$wind_speed where growerid=$growerid and seasonid=$seasonid";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"success");
          array_push($response1,$temp);

           
          }else{

              $temp=array("response"=>"Failed to update weather");
              array_push($response1,$temp);

          }

     }
    

   }else{

    echo $conn->error;

    $temp=array("response"=>"Failed to record weather");
     array_push($response1,$temp);

   }
 }else{

  if ($growerid==0) {
   
     $temp=array("response"=>"Grower Not Found");
     array_push($response1,$temp);

  }else if($found>0){

  $temp=array("response"=>"Weather already recorded(".$grower_num.")");
  array_push($response1,$temp);

  }
   

 }



}


if (count($response1) > 0) {

    $date=new DateTime();
    //$start=$date->format('Y-m-d');
    $created_at=$date->format('Y-m-d');
    

    $sql = "Select * from operations_contacts where  active=1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        $phone=$row["phone"];
        $contact_email=$row["email"];
        $to = $contact_email;
        $subject = "GMS Weather Data";
        $txt = $created_at." Daily Weather Data Fetched ";
        $headers = "From: weather@coreafricagrp.com";
        mail($to,$subject,$txt,$headers);
       }

     }
}