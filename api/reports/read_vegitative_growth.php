
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

$landprep_discing=array();
$landprep_ploughing=array();
$landprep_ridging=array();

$field_maintanance=array();
$crop_measurement=array();
$disease_management=array();
$pest_management=array();
$crop_nutrition=array();
$soil_moisture=array();

// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct soil_moisture.id,soil_structure, moisture_state, field_irrigation, soil_moisture.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,soil_moisture.datetimes,soil_moisture.seasonid,soil_moisture.userid,latitude,longitude,username FROM soil_moisture join growers on growers.id=soil_moisture.growerid join users on users.id=soil_moisture.userid where soil_moisture.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"soil_structure"=>$row1["soil_structure"],"moisture_state"=>$row1["moisture_state"],"field_irrigation"=>$row1["field_irrigation"]);
              array_push($soil_moisture,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct crop_nutrition.id,topping_type,topping_app_rate,topping_app_date,crop_symptoms,crop_nutrition.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,crop_nutrition.datetimes,crop_nutrition.seasonid,crop_nutrition.userid,longitude,longitude,basal_type,basal_app_rate,basal_app_date,username FROM crop_nutrition join growers on growers.id=crop_nutrition.growerid join users on users.id=crop_nutrition.userid where crop_nutrition.seasonid=$seasonid ";





      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"topping_type"=>$row1["topping_type"],"topping_app_rate"=>$row1["topping_app_rate"],"topping_app_rate"=>$row1["topping_app_rate"],"crop_symptoms"=>$row1["crop_symptoms"]
    ,"crop_symptoms"=>$row1["crop_symptoms"],"basal_type"=>$row1["basal_type"]
    ,"basal_app_rate"=>$row1["basal_app_rate"],"basal_app_date"=>$row1["basal_app_date"]);
          array_push($crop_nutrition,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct pest_management.id,observed_pest,pest_effect_severity, pest_treated_group, applied_pesticide,pesticide_app_date,pest_management.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,pest_management.datetimes,pest_management.seasonid,pest_management.userid,latitude,longitude,username FROM pest_management join growers on growers.id=pest_management.growerid join users on users.id=pest_management.userid where pest_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"observed_pest"=>$row1["observed_pest"],"pest_effect_severity"=>$row1["pest_effect_severity"],"pest_treated_group"=>$row1["pest_treated_group"],"applied_pesticide"=>$row1["applied_pesticide"],"pesticide_app_date"=>$row1["pesticide_app_date"]);
          array_push($pest_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct disease_management.id,observed_disease,disease_effect_severity, disease_treated_group,applied_fungicide, fungicide_app_date, disease_management.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,disease_management.datetimes,disease_management.seasonid,disease_management.userid,latitude,longitude,username FROM disease_management join growers on growers.id=disease_management.growerid join users on users.id=disease_management.userid where disease_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"observed_disease"=>$row1["observed_disease"],"disease_effect_severity"=>$row1["disease_effect_severity"],"disease_treated_group"=>$row1["disease_treated_group"],"applied_fungicide"=>$row1["applied_fungicide"],"fungicide_app_date"=>$row1["fungicide_app_date"]
      ,"username"=>$row1["username"]);
          array_push($disease_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct crop_measurement.id,crop_age, avg_plant_height, avg_crop_vigor, avg_crop_density,crop_measurement.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,crop_measurement.datetimes,crop_measurement.seasonid,crop_measurement.userid,latitude,longitude,username FROM crop_measurement join growers on growers.id=crop_measurement.growerid join users on users.id=crop_measurement.userid where crop_measurement.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_age"=>$row1["crop_age"],"avg_plant_height"=>$row1["avg_plant_height"],"avg_crop_vigor"=>$row1["avg_crop_vigor"],"avg_crop_density"=>$row1["avg_crop_density"]);
          array_push($crop_measurement,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct field_maintanance.id,weed_level,weed_control_eff, herbicide_applied,sucker_eff, sucker_chem, gapping,population_uniformity,field_maintanance.created_at,grower_num,growers.name, growers.surname, id_num,area, province, phone,field_maintanance.datetimes,field_maintanance.seasonid,field_maintanance.userid,latitude,longitude,username FROM field_maintanance join growers on growers.id=field_maintanance.growerid join users on users.id=field_maintanance.userid where field_maintanance.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"weed_level"=>$row1["weed_level"],"weed_control_eff"=>$row1["weed_control_eff"],"herbicide_applied"=>$row1["herbicide_applied"],"sucker_eff"=>$row1["sucker_eff"],"sucker_chem"=>$row1["sucker_chem"]
      ,"gapping"=>$row1["gapping"],"population_uniformity"=>$row1["population_uniformity"]);
          array_push($field_maintanance,$temp);

         
         }
       
   }






   $temp=array("field_maintanance"=>$field_maintanance,"crop_measurement"=>$crop_measurement,"disease_management"=>$disease_management,"pest_management"=>$pest_management,"crop_nutrition"=>$crop_nutrition,"soil_moisture"=>$soil_moisture);
  array_push($data1,$temp);



}

 echo json_encode($data1);


?>


