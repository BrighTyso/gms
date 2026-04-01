
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



$flowering_field_management=array();
$flowering_pest_diseases=array();
$nutrient_stress=array();
$flowering_ripening_stage=array();


// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


      $sql11 = "SELECT distinct  flowering_ripening_stage.id,flowering_ripening_stage.userid,  flowering_ripening_stage.growerid,  latitude,  longitude,  flowering_ripening_stage.created_at,  flowering_ripening_stage.seasonid,  crop_topped,  crop_topping_height,crop_remaining_leaves, crop_topping_uniformity, suckers_per_plant, sucker_chem, sucker_eff,sucker_app_date, crop_maturity_stage, harvest_ready_pct, expected_first_picking,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_ripening_stage.datetimes,username FROM flowering_ripening_stage join growers on growers.id=flowering_ripening_stage.growerid join users on users.id=flowering_ripening_stage.userid where flowering_ripening_stage.seasonid=$seasonid  ";
      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_topped"=>$row1["crop_topped"],"crop_topping_height"=>$row1["crop_topping_height"],"crop_remaining_leaves"=>$row1["crop_remaining_leaves"],"crop_topping_uniformity"=>$row1["crop_topping_uniformity"],"suckers_per_plant"=>$row1["suckers_per_plant"],"sucker_chem"=>$row1["sucker_chem"]
        ,"sucker_eff"=>$row1["sucker_eff"],"sucker_app_date"=>$row1["sucker_app_date"],"crop_maturity_stage"=>$row1["crop_maturity_stage"],"harvest_ready_pct"=>$row1["harvest_ready_pct"]
,"expected_first_picking"=>$row1["expected_first_picking"]);
              array_push($flowering_ripening_stage,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct nutrient_stress.id,nutrient_stress.userid,  nutrient_stress.growerid,   latitude,  longitude,  nutrient_stress.created_at,  nutrient_stress.seasonid,  crop_stage,  nitrogen_level,  phosphorus_level, potassium_level, field_moisture, field_drought, field_drought_severity,grower_num,growers.name, growers.surname, id_num,area, province, phone, nutrient_stress.datetimes,username FROM nutrient_stress join growers on growers.id=nutrient_stress.growerid join users on users.id=nutrient_stress.userid where nutrient_stress.seasonid=$seasonid ";




      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_stage"=>$row1["crop_stage"],"nitrogen_level"=>$row1["nitrogen_level"],"phosphorus_level"=>$row1["phosphorus_level"],"potassium_level"=>$row1["potassium_level"]
    ,"field_moisture"=>$row1["field_moisture"],"field_drought"=>$row1["field_drought"]
    ,"field_drought_severity"=>$row1["field_drought_severity"]);
          array_push($nutrient_stress,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct flowering_pest_diseases.id,flowering_pest_diseases.userid,  flowering_pest_diseases.growerid,  latitude,  longitude,  flowering_pest_diseases.created_at,  flowering_pest_diseases.seasonid,  crop_stage,  pest_name,  pest_severity, pest_treated, pesticide_applied, pesticide_app_date, disease_name, disease_severity, disease_treated, sanitation_status,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_pest_diseases.datetimes,username FROM flowering_pest_diseases join growers on growers.id=flowering_pest_diseases.growerid join users on users.id=flowering_pest_diseases.userid where flowering_pest_diseases.seasonid=$seasonid  ";



      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"crop_stage"=>$row1["crop_stage"],"pest_name"=>$row1["pest_name"],"pest_severity"=>$row1["pest_severity"],"pest_treated"=>$row1["pest_treated"],"pesticide_applied"=>$row1["pesticide_applied"],"pesticide_app_date"=>$row1["pesticide_app_date"],"disease_name"=>$row1["disease_name"]
    ,"disease_severity"=>$row1["disease_severity"],"disease_treated"=>$row1["disease_treated"]
    ,"sanitation_status"=>$row1["sanitation_status"]);
          array_push($flowering_pest_diseases,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct flowering_field_management.id,flowering_field_management.userid,  flowering_field_management.growerid,   latitude,  longitude,  flowering_field_management.created_at,  flowering_field_management.seasonid,  weeding_done,  weeds_level,  herbicide, harvest_ready, ripening_percent, expected_weight, labor_availability, uniformity_score,grower_num,growers.name, growers.surname, id_num,area, province, phone, flowering_field_management.datetimes,username FROM flowering_field_management join growers on growers.id=flowering_field_management.growerid join users on users.id=flowering_field_management.userid where flowering_field_management.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"weeding_done"=>$row1["weeding_done"],"weeds_level"=>$row1["weeds_level"],"herbicide"=>$row1["herbicide"],"harvest_ready"=>$row1["harvest_ready"],"ripening_percent"=>$row1["ripening_percent"],"expected_weight"=>$row1["expected_weight"],"labor_availability"=>$row1["labor_availability"],"uniformity_score"=>$row1["uniformity_score"]);
          array_push($flowering_field_management,$temp);

         
         }
       
   }




   $temp=array("flowering_field_management"=>$flowering_field_management,"flowering_pest_diseases"=>$flowering_pest_diseases,"nutrient_stress"=>$nutrient_stress,"flowering_ripening_stage"=>$flowering_ripening_stage);
  array_push($data1,$temp);



}

 echo json_encode($data1);


?>


