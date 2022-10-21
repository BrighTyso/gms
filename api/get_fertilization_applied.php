<?php
require "conn.php";
require "validate.php";

$data=array();


$seasonid=0;
$userid=0;
$growerid=0;



//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['seasonid']) && isset($_GET["growerid"])) {


$userid=$_GET['userid'];
$seasonid=$_GET['seasonid'];
$growerid=$_GET['growerid'];


 $sql = "select fertilization_potassium.kg_per_ha as p_kg_per_ha,fertilization_ammonium.kg_per_ha as a_kg_per_ha from growers left join fertilization_ammonium on growers.id=fertilization_ammonium.growerid left join fertilization_potassium on growers.id=fertilization_potassium.growerid where growers.id=$growerid and (fertilization_potassium.seasonid=$seasonid or fertilization_ammonium.seasonid=$seasonid)  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("a_kg_per_ha"=>$row["a_kg_per_ha"],"p_kg_per_ha"=>$row["p_kg_per_ha"]);
    array_push($data,$temp);
    
   }
 }




}





 echo json_encode($data); 



?>