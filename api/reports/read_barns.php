
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

$barn_fire_safety=array();
$barn_heatsource=array();
$barn_humidity=array();
$barn_loading_capacity=array();
$barn_structure=array();
$barn_ventilation=array();

// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_fire_safety.created_at, 
barn_fire_safety.seasonid, 
barn_number, 
firebreak_cleared, 
chimney_safety, 
heat_source_outside,
fire_fighting,
electric_wiring_safe,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_fire_safety.userid,barn_fire_safety.datetimes,username FROM barn_fire_safety join growers on growers.id=barn_fire_safety.growerid join users on users.id=barn_fire_safety.userid where barn_fire_safety.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"firebreak_cleared"=>$row1["firebreak_cleared"],"chimney_safety"=>$row1["chimney_safety"],"heat_source_outside"=>$row1["heat_source_outside"],"fire_fighting"=>$row1["fire_fighting"]
      ,"electric_wiring_safe"=>$row1["electric_wiring_safe"],"created_at"=>$row1["created_at"]
      );
          array_push($barn_fire_safety,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_heatsource.created_at, 
barn_heatsource.datetimes,
barn_heatsource.seasonid, 
barn_number, 
heat_source, 
flue_intact, 
smoke_leakages,
temperature_control,
thermometer,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_heatsource.userid,barn_heatsource.datetimes,username FROM barn_heatsource join growers on growers.id=barn_heatsource.growerid join users on users.id=barn_heatsource.userid where barn_heatsource.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"heat_source"=>$row1["heat_source"],"flue_intact"=>$row1["flue_intact"],"smoke_leakages"=>$row1["smoke_leakages"],"temperature_control"=>$row1["temperature_control"]
      ,"thermometer"=>$row1["thermometer"],"created_at"=>$row1["created_at"]);
          array_push($barn_heatsource,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_humidity.created_at, 
barn_humidity.seasonid, 
barn_number, 
humidity_retain, 
moisture_release, 
roof_leaks,
dripping,
drainange,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_humidity.userid,barn_humidity.datetimes,username FROM barn_humidity join growers on growers.id=barn_humidity.growerid join users on users.id=barn_humidity.userid where barn_humidity.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"humidity_retain"=>$row1["humidity_retain"],"moisture_release"=>$row1["moisture_release"],"roof_leaks"=>$row1["roof_leaks"],"dripping"=>$row1["dripping"]
      ,"drainange"=>$row1["drainange"],"created_at"=>$row1["created_at"]);
          array_push($barn_humidity,$temp);

         
         } 
   }





$sql11 = "SELECT distinct  
growerid, 
latitude, 
longitude, 
barn_loading_capacity.created_at, 
barn_loading_capacity.seasonid, 
barn_number, 
tiers_poles_condition, 
tier_spacing, 
barn_capacity,
overloading_risk,
unloading_safety,
barn_ha_capacity,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_loading_capacity.userid,barn_loading_capacity.datetimes,username FROM barn_loading_capacity join growers on growers.id=barn_loading_capacity.growerid join users on users.id=barn_loading_capacity.userid where barn_loading_capacity.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"tiers_poles_condition"=>$row1["tiers_poles_condition"],"tier_spacing"=>$row1["tier_spacing"],"barn_capacity"=>$row1["barn_capacity"],"overloading_risk"=>$row1["overloading_risk"]
      ,"unloading_safety"=>$row1["unloading_safety"],"barn_ha_capacity"=>$row1["barn_ha_capacity"]
      );
          array_push($barn_loading_capacity,$temp);

         
         } 
   }






$sql11 = "SELECT distinct  growerid, latitude, longitude,barn_structure.created_at, barn_structure.seasonid, barn_number, barn_type, 
barn_roof, barn_walls, barn_doors,barn_stability,barn_floor,barn_termite_rot,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_structure.userid,barn_structure.datetimes,username FROM barn_structure join growers on growers.id=barn_structure.growerid join users on users.id=barn_structure.userid where barn_structure.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"barn_type"=>$row1["barn_type"],"barn_roof"=>$row1["barn_roof"],"barn_walls"=>$row1["barn_walls"],"barn_doors"=>$row1["barn_doors"]
      ,"barn_stability"=>$row1["barn_stability"],"barn_floor"=>$row1["barn_floor"]
      ,"barn_termite_rot"=>$row1["barn_termite_rot"],"created_at"=>$row1["created_at"]
     );
          array_push($barn_structure,$temp);

         
         } 
   }






$sql11 = "SELECT distinct  
growerid, latitude, longitude, barn_ventilation.created_at,barn_ventilation.seasonid, barn_number, vent_type, vent_condition, vent_count,vent_ease,airflow_obstruction,grower_num,growers.name, growers.surname, id_num,area, province, phone,barn_ventilation.userid,barn_ventilation.datetimes,username FROM barn_ventilation join growers on growers.id=barn_ventilation.growerid join users on users.id=barn_ventilation.userid where barn_ventilation.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"barn_number"=>$row1["barn_number"],"vent_type"=>$row1["vent_type"],"vent_ease"=>$row1["vent_ease"],"vent_count"=>$row1["vent_count"],"airflow_obstruction"=>$row1["airflow_obstruction"]
      ,"created_at"=>$row1["created_at"]);
          array_push($barn_ventilation,$temp);

         
         } 
   }





$temp=array("barn_fire_safety"=>$barn_fire_safety,"barn_heatsource"=>$barn_heatsource,"barn_humidity"=>$barn_humidity,"barn_loading_capacity"=>$barn_loading_capacity

,"barn_structure"=>$barn_structure,"barn_ventilation"=>$barn_ventilation
);
  array_push($data1,$temp);





}

 echo json_encode($data1);


?>


