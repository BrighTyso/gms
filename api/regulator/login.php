<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));

$username=$data->username;
$hash=$data->hash;

$data1=array();
// get grower locations

if ($username!="" && $hash!="") {
	

$sql = "Select * from users where hash='$hash' and  username='$username' and  active=1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
    array_push($data1,$temp);
    
   }
 }


}

 echo json_encode($data1);

?>



