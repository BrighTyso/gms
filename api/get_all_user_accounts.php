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


$data1=array();
// get grower locations

if ($description=="" ) {
	

$sql = "Select users.name,surname,username,active,users.id,rights.description,created_at from users join rights on users.rightsid=rights.id where users.username!='sysadmin'";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"active"=>$row["active"] ,"id"=>$row["id"],"rights"=>$row["description"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }


}else{

  if ($description!='sysadmin'){

$sql = "Select users.name,surname,username,active,users.id,rights.description,created_at  from users join rights on users.rightsid=rights.id where username='$description' or name='$description' or surname='$description' or description='$description' ";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"active"=>$row["active"] ,"id"=>$row["id"],"rights"=>$row["description"],"created_at"=>$row["created_at"]);
    array_push($data1,$temp);
    
   }
 }

}

}

 echo json_encode($data1);

?>



