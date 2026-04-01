
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




$diseases_pest_control=array();
$seedling_growth_vigour=array();
$seedbed_soil_health=array();
$seedbed_management=array();
$seedbed_leafcolor=array();
$seed_germination=array();


// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


      $sql11 = "SELECT distinct  seed_germination.id, growerid, latitude, longitude, germination_percentage, seedVariety, bed_type, intendedHa, plantedDate, seed_germination.created_at,seed_germination.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seed_germination.seasonid,datetimes,username FROM seed_germination join growers on seed_germination.growerid=growers.id join users on users.id=seed_germination.userid where seed_germination.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"germination_percentage"=>$row1["germination_percentage"],"seedVariety"=>$row1["seedVariety"],"bed_type"=>$row1["bed_type"],"intendedHa"=>$row1["intendedHa"],"plantedDate"=>$row1["plantedDate"]);
              array_push($seed_germination,$temp);

         
         }
       
   }






   $sql11 = "SELECT distinct seedbed_leafcolor.id, growerid, bed_number, seedbed_leaf_color, seedbed_leafcolor.created_at,seedbed_leafcolor.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_leafcolor.seasonid,datetimes,latitude,longitude,username FROM seedbed_leafcolor join growers on seedbed_leafcolor.growerid=growers.id join users on users.id=seedbed_leafcolor.userid where seedbed_leafcolor.seasonid=$seasonid ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"bed_number"=>$row1["bed_number"],"seedbed_leaf_color"=>$row1["seedbed_leaf_color"]);
          array_push($seedbed_leafcolor,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct seedbed_management.id, growerid, seedbed_type, pocket_numbering,weeding_done, weeds_rate,fertiliser_top,fertiliser_top_date, seedbed_management.created_at,seedbed_management.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_management.seasonid,datetimes,latitude,longitude,fungi_app,fungi_app_date,pesti_app,pesti_app_date,herbi_app,herbi_app_date,username FROM seedbed_management join growers on seedbed_management.growerid=growers.id join users on users.id=seedbed_management.userid where seedbed_management.seasonid=$seasonid  ";




      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_type"=>$row1["seedbed_type"],"pocket_numbering"=>$row1["pocket_numbering"],"weeding_done"=>$row1["weeding_done"],"weeds_rate"=>$row1["weeds_rate"],"fertiliser_top"=>$row1["fertiliser_top"],"fertiliser_top_date"=>$row1["fertiliser_top_date"],"fungi_app"=>$row1["fungi_app"]
    ,"fungi_app_date"=>$row1["fungi_app_date"],"pesti_app"=>$row1["pesti_app"],"pesti_app_date"=>$row1["pesti_app_date"]
    ,"herbi_app"=>$row1["herbi_app"],"herbi_app_date"=>$row1["herbi_app_date"]);
          array_push($seedbed_management,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct seedbed_soil_health.id, growerid, seedbed_soil_drainage, seedbed_drainage_rate, seedbed_soil_structure,seedbed_structure_rate, seedbed_soil_health.created_at,seedbed_soil_health.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedbed_soil_health.seasonid,datetimes,latitude,longitude,username FROM seedbed_soil_health join growers on seedbed_soil_health.growerid=growers.id join users on users.id=seedbed_soil_health.userid where seedbed_soil_health.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_soil_drainage"=>$row1["seedbed_soil_drainage"],"seedbed_drainage_rate"=>$row1["seedbed_drainage_rate"],"seedbed_soil_structure"=>$row1["seedbed_soil_structure"],"seedbed_structure_rate"=>$row1["seedbed_structure_rate"]);
          array_push($seedbed_soil_health,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct seedling_growth_vigour.id, seedling_health, seedling_health_rate, seedling_growth, seedling_stage_date, seedling_growth_vigour.created_at,seedling_growth_vigour.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,seedling_growth_vigour.seasonid,datetimes,latitude,longitude,username FROM seedling_growth_vigour join growers on seedling_growth_vigour.growerid=growers.id join users on users.id=seedling_growth_vigour.userid where seedling_growth_vigour.seasonid=$seasonid  ";



      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedling_health"=>$row1["seedling_health"],"seedling_health_rate"=>$row1["seedling_health_rate"],"seedling_growth"=>$row1["seedling_growth"],"seedling_stage_date"=>$row1["seedling_stage_date"]);
          array_push($seedling_growth_vigour,$temp);

         
         }
       
   }





   $sql11 = "SELECT distinct diseases_pest_control.id, growerid, seedbed_pest_identified, seedbed_pesticide_applied, seedbed_pesticide_app_date, seedbed_disease_identified,seedbed_fungicide_applied,seedbed_fungicide_app_date, diseases_pest_control.created_at,diseases_pest_control.userid,grower_num,growers.name, growers.surname, id_num,area, province, phone,diseases_pest_control.seasonid,datetimes,latitude,longitude,username FROM diseases_pest_control join growers on diseases_pest_control.growerid=growers.id join users on users.id=diseases_pest_control.userid where diseases_pest_control.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"seedbed_pest_identified"=>$row1["seedbed_pest_identified"],"seedbed_pesticide_applied"=>$row1["seedbed_pesticide_applied"],"seedbed_pesticide_app_date"=>$row1["seedbed_pesticide_app_date"],"seedbed_disease_identified"=>$row1["seedbed_disease_identified"],"seedbed_fungicide_applied"=>$row1["seedbed_fungicide_applied"],"seedbed_fungicide_app_date"=>$row1["seedbed_fungicide_app_date"]);
          array_push($diseases_pest_control,$temp);

         
         }
       
   }




   $temp=array("diseases_pest_control"=>$diseases_pest_control,"seedling_growth_vigour"=>$seedling_growth_vigour,"seedbed_soil_health"=>$seedbed_soil_health,"seedbed_management"=>$seedbed_management,"seedbed_leafcolor"=>$seedbed_leafcolor,"seed_germination"=>$seed_germination);
  array_push($data1,$temp);



}

 echo json_encode($data1);


?>


