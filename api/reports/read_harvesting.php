
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid1=$data->userid;
$seasonid=$data->seasonid;

$username="";

$data1=array();



$harvesting_quality=array();
$harvesting_weather=array();
$harvesting_sanitation=array();
$harvesting_labour=array();
$harvesting_method=array();
$harvesting_readiness=array();
$harvesting_maturity=array();



// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


      $sql11 = "SELECT distinct  harvesting_maturity.id,harvesting_maturity.userid,growerid, latitude, longitude, harvesting_maturity.created_at,harvesting_maturity.seasonid, MaturityStage, LeafColor, LeafTexture,LeafThickness,LeafElasticity,LeafGloss,MidribThickness,PercentageMature,grower_num,growers.name, growers.surname, id_num,area, province, phone, harvesting_maturity.datetimes,username  FROM harvesting_maturity join growers on growers.id=harvesting_maturity.growerid join users on users.id=harvesting_maturity.userid where harvesting_maturity.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"MaturityStage"=>$row1["MaturityStage"],"LeafColor"=>$row1["LeafColor"],"LeafTexture"=>$row1["LeafTexture"],"LeafThickness"=>$row1["LeafThickness"],"LeafElasticity"=>$row1["LeafElasticity"],"LeafGloss"=>$row1["LeafGloss"]
        ,"MidribThickness"=>$row1["MidribThickness"],"PercentageMature"=>$row1["PercentageMature"]);
              array_push($harvesting_maturity,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct harvesting_readiness.id,harvesting_readiness.userid, harvesting_readiness.growerid, latitude, longitude, harvesting_readiness.created_at,harvesting_readiness.seasonid, harvest_readiness, harvest_date, harvest_action,delay_reason,quality_expected,grower_num,growers.name, growers.surname, id_num,area, province, phone,username FROM harvesting_readiness join growers on growers.id=harvesting_readiness.growerid join users on users.id=harvesting_readiness.userid where harvesting_readiness.seasonid=$seasonid ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"harvest_readiness"=>$row1["harvest_readiness"],"harvest_date"=>$row1["harvest_date"],"harvest_action"=>$row1["harvest_action"],"delay_reason"=>$row1["delay_reason"]
    ,"quality_expected"=>$row1["quality_expected"]);
          array_push($harvesting_readiness,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct harvesting_method.id,harvesting_method.userid, harvesting_method.growerid, latitude, longitude, harvesting_method.created_at,harvesting_method.seasonid, harvest_method, leaf_type, harvest_sequence,handling_method,damage_observed, leaves_per_harvest,harvest_interval,leaf_loss_est,grower_num,growers.name, growers.surname, id_num,area, province, phone,harvesting_method.datetimes,username  FROM harvesting_method join growers on growers.id=harvesting_method.growerid join users on users.id=harvesting_method.userid where harvesting_method.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"harvest_method"=>$row1["harvest_method"],"leaf_type"=>$row1["leaf_type"],"harvest_sequence"=>$row1["harvest_sequence"],"handling_method"=>$row1["handling_method"],"damage_observed"=>$row1["damage_observed"],"leaves_per_harvest"=>$row1["leaves_per_harvest"],"harvest_interval"=>$row1["harvest_interval"]
    ,"leaf_loss_est"=>$row1["leaf_loss_est"]);
          array_push($harvesting_method,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct harvesting_labour.id,harvesting_labour.userid, harvesting_labour.growerid, latitude, longitude, harvesting_labour.created_at,harvesting_labour.seasonid, labour_availability, workers_experienced, harvest_speed,time_of_harvest,transport_readiness,grower_num,growers.name, growers.surname, id_num,area, province, phone, harvesting_labour.datetimes,username FROM harvesting_labour join growers on growers.id=harvesting_labour.growerid join users on users.id=harvesting_labour.userid where harvesting_labour.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"labour_availability"=>$row1["labour_availability"],"workers_experienced"=>$row1["workers_experienced"],"harvest_speed"=>$row1["harvest_speed"],"time_of_harvest"=>$row1["time_of_harvest"],"transport_readiness"=>$row1["transport_readiness"]);
          array_push($harvesting_labour,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct harvesting_sanitation.id,harvesting_sanitation.userid, harvesting_sanitation.growerid, latitude, longitude, harvesting_sanitation.created_at,harvesting_sanitation.seasonid, field_cleanliness, diseased_leaves, pest_infestation,tools_sanitation,grower_num,growers.name, growers.surname, id_num,area, province, phone, harvesting_sanitation.datetimes,username FROM harvesting_sanitation join growers on growers.id=harvesting_sanitation.growerid join users on users.id=harvesting_sanitation.userid where harvesting_sanitation.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"field_cleanliness"=>$row1["field_cleanliness"],"diseased_leaves"=>$row1["diseased_leaves"],"pest_infestation"=>$row1["pest_infestation"],"tools_sanitation"=>$row1["tools_sanitation"]);
          array_push($harvesting_sanitation,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct harvesting_weather.id,harvesting_weather.userid, harvesting_weather.growerid, latitude, longitude, harvesting_weather.created_at,harvesting_weather.seasonid, weather_condition, temperature, humidity,rain_risk,grower_num,growers.name, growers.surname, id_num,area, province, phone, harvesting_weather.datetimes,username FROM harvesting_weather join growers on growers.id=harvesting_weather.growerid join users on users.id=harvesting_weather.userid where harvesting_weather.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"weather_condition"=>$row1["weather_condition"],"temperature"=>$row1["temperature"],"humidity"=>$row1["humidity"],"rain_risk"=>$row1["rain_risk"]);
          array_push($harvesting_weather,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct harvesting_quality.id,harvesting_quality.userid, harvesting_quality.growerid, latitude, longitude, harvesting_quality.created_at,harvesting_quality.seasonid, bruising_risk, overheating, uneven_curing,expected_grade,grower_num,growers.name, growers.surname, id_num,area, province, phone, harvesting_quality.datetimes,username FROM harvesting_quality join growers on growers.id=harvesting_quality.growerid join users on users.id=harvesting_quality.userid where harvesting_quality.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          
         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"bruising_risk"=>$row1["bruising_risk"],"overheating"=>$row1["overheating"],"uneven_curing"=>$row1["uneven_curing"],"expected_grade"=>$row1["expected_grade"]);
          array_push($harvesting_quality,$temp);

         
         }
       
   }




   $temp=array("harvesting_quality"=>$harvesting_quality,"harvesting_weather"=>$harvesting_weather,"harvesting_sanitation"=>$harvesting_sanitation,"harvesting_labour"=>$harvesting_labour,"harvesting_method"=>$harvesting_method,"harvesting_readiness"=>$harvesting_readiness,"harvesting_maturity"=>$harvesting_maturity);
  array_push($data1,$temp);



}

 echo json_encode($data1);


?>


