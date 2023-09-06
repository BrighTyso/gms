<?php
require "conn.php";
require "validate.php";

$data=array();

$userid=0;


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234
if (isset($_GET['userid'])) {


$userid=$_GET['userid'];


 
$sql = "select id,userid,fieldOfficerid,created_at from exempt_user where fieldOfficerid=$userid and used=0 order by exempt_user.id desc limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("userid"=>$row["userid"],"fieldOfficerid"=>$row["fieldOfficerid"],"id"=>$row["id"],"created_at"=>$row["created_at"]);
    array_push($data,$temp);
    
   }
 }

}


 echo json_encode($data); 


?>