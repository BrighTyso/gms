<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));

$description=$data->description;
//$seasonid=$data->seasonid;
$userid=$data->userid;

$data1=array();
// get grower locations

if ($description!="" && $userid!="") {
  

$sql = "Select growers.grower_num, growers.name as grower_name , visits.latitude ,visits.longitude,visits.description  from visits join users on users.id=visits.userid join growers on growers.id=visits.growerid where visits.created_at='$description'  and visits.userid=$userid";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("grower_name"=>$row["grower_name"],"latitude"=>$row["latitude"] ,"longitude"=>$row["longitude"],"grower_num"=>$row["grower_num"] ,"description"=>$row["description"]);
    array_push($data1,$temp);
    
   }
 }


}


 echo json_encode($data1);

?>



