<?php
require "conn.php";
require "validate.php";

$data=array();

$username="";
$hash="";
$access_code=0000;


http://192.168.1.190/gms/api/get_user.php?username=brightkaponda&hash=brightkaponda&access_code=1234

if (isset($_GET['username']) && isset($_GET['hash'])){


$username=validate($_GET['username']);
$hash=md5($_GET['hash']);
$access_code=validate($_GET['access_code']);


$sql = "Select * from users where username='$username' and hash='$hash' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


     $fo_name=$row["name"]." ".$row["surname"];
     $fo_id=$row["id"];
     $phone="0784428797";



    $sql1 = "Select * from field_officers where userid=$fo_id limit 1";
  $result1 = $conn->query($sql1);
   
   if ($result1->num_rows > 0) {
     // output data of each row
     while($row1 = $result1->fetch_assoc()) { 
     
       
     }

   }else{

        $grower_farm_sql = "INSERT INTO field_officers(name, phone, company_code, status,userid) VALUES ('$fo_name','$phone','$access_code','active',$fo_id)";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {

         }else{
         
         }

     }





    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"username"=>$row["username"],"created_at"=>$row["created_at"],"userid"=>$row["id"],"rightsid"=>$row["rightsid"],"active"=>$row["active"],"access_code"=>$row["access_code"],"hash"=>$row["hash"]);
    array_push($data,$temp);
    
   }
 }


 echo json_encode($data); 

}



?>