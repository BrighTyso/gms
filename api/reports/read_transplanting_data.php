
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
// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct tobacco_transplanting.id ,tobacco_transplanting.seasonid,field_name, transplant_vigor, transplant_pests, transplant_diseases,transplant_weeds,transplant_survival_rate,hectares_transplanted,transplant_date,no_of_plants,tobacco_transplanting.created_at,latitude,longitude,grower_num,growers.name, growers.surname, id_num,area, province, phone,tobacco_transplanting.userid,transplant_root_health,tobacco_transplanting.datetimes,username FROM tobacco_transplanting join growers on growers.id=tobacco_transplanting.growerid join users on users.id=tobacco_transplanting.userid where tobacco_transplanting.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"field_name"=>$row1["field_name"],"transplant_vigor"=>$row1["transplant_vigor"],"transplant_pests"=>$row1["transplant_pests"],"transplant_diseases"=>$row1["transplant_diseases"],"transplant_weeds"=>$row1["transplant_weeds"]
      ,"transplant_survival_rate"=>$row1["transplant_survival_rate"],"hectares_transplanted"=>$row1["hectares_transplanted"]
      ,"transplant_date"=>$row1["transplant_date"],"no_of_plants"=>$row1["no_of_plants"]
      ,"transplant_root_health"=>$row1["transplant_root_health"]);
          array_push($data1,$temp);

         
         }
       
   }



}

 echo json_encode($data1);


?>


