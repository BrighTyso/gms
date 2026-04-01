
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
      
      $sql11 = "SELECT distinct crop_population.userid, 
      growerid, 
      latitude, 
      longitude, 
      crop_population.created_at, 
      crop_population.datetimes,
      crop_population.seasonid, 
      hectarage, 
      variety, 
      crop_pop_rate, 
      num_of_plants,
      estimated_yield,grower_num,growers.name, growers.surname, id_num,area, province, phone,crop_population.userid,crop_population.datetimes,username FROM crop_population join growers on growers.id=crop_population.growerid join users on users.id=crop_population.userid where crop_population.seasonid=$seasonid";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"hectarage"=>$row1["hectarage"],"variety"=>$row1["variety"],"crop_pop_rate"=>$row1["crop_pop_rate"],"num_of_plants"=>$row1["num_of_plants"],"estimated_yield"=>$row1["estimated_yield"],"created_at"=>$row1["created_at"]);
          array_push($data1,$temp);

         
         }
       
   }



}

 echo json_encode($data1);


?>


