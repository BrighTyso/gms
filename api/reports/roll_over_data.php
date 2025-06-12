<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

if ($userid!="") {
  
$sql112 = "Select * from seasons order by id";
$result133 = $conn->query($sql112);
 
 if ($result133->num_rows > 0) {
   // output data of each row
   while($row133 = $result133->fetch_assoc()) {

    $seasonid=$row133["id"];
    $season_name=$row133["name"];




    $sql14 = "Select * from roll_over_interest  where roll_over_interest.seasonid=$seasonid limit 1";

  $result4 = $conn->query($sql14);
   
   if ($result4->num_rows > 0) {
     // output data of each row
     while($row4 = $result4->fetch_assoc()) {

      $roll_over_percentage=$row4["value"];
     
     }
   }


    $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

    $result4 = $conn->query($sql14);
     
     if ($result4->num_rows > 0) {
       // output data of each row
       while($row4 = $result4->fetch_assoc()) {

       
       
       }
     }



  }
}

$sql1 = "Select distinct grower_num,quantity,scheme_hectares.seasonid,scheme.description,area,province,surname,name from scheme_hectares_growers join growers on scheme_hectares_growers.growerid=growers.id join scheme_hectares on scheme_hectares.id=scheme_hectares_growers.scheme_hectaresid join scheme on scheme.id=scheme_hectares.schemeid  where scheme_hectares.seasonid=$seasonid";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row = $result1->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    // array_push($data1,$temp);









   $temp=array("grower_num"=>$row["grower_num"],"quantity"=>$row["quantity"],"seasonid"=>$row["seasonid"],"description"=>$row["description"],"area"=>$row["area"],"province"=>$row["province"],"surname"=>$row["surname"],"name"=>$row["name"]);
    array_push($data1,$temp);

   
   }
 }





}

 echo json_encode($data1);


?>


