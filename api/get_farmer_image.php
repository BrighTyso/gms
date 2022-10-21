<?php
require "conn.php";
require "validate.php";

$data=array();


$userid=0;
$growerid=0;


//http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234


if (isset($_GET['growerid'])) {


$growerid=$_GET['growerid'];


 $sql = "select image from grower_image where growerid=$growerid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    $temp=array("image"=>$row["image"]);
    array_push($data,$temp);
    
   }
 }


}





 echo json_encode($data); 



?>