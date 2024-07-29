<?php
require "conn.php";
require "validate.php";

$data=array();


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


$description=$_GET["description"];

if (isset($_GET["description"])) {
 
if ($description=="") {

$sql = "select distinct name,surname,users.id,firebaseid from users left join live_location_userid on live_location_userid.userid=users.id where users.active=1 and (rightsid=8 or rightsid=9 or rightsid=14 or rightsid=7 or rightsid=1)";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"firebaseid"=>$row["firebaseid"]);
    array_push($data,$temp);
    
   }
 }


}else{

$sql = "select distinct name,surname,users.id,firebaseid from users left join live_location_userid on live_location_userid.userid=users.id where users.active=1 and (name='$description' or surname='$description' or username='$description') and  (rightsid=8 or rightsid=9 or rightsid=14 or rightsid=7 or rightsid=1)";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"id"=>$row["id"],"firebaseid"=>$row["firebaseid"]);
    array_push($data,$temp);
    
   }
 }

}

}






 echo json_encode($data); 

?>