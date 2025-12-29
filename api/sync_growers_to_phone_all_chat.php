<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$username=$_GET["username"];
$userid=0;
$seasonid=0;
$rule=0;
$file_name="";



if ($username!="") {
  

$sql = "Select * from users where  username='$username' and  active=1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $userid=$row["id"];

   }
 }




$sql = "Select * from seasons where active=1 limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $seasonid=$row["id"];

   }
 }



 $sql = "Select rule from download_growers_rule  limit 1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $rule=$row["rule"];

   }
 }


if ($userid>0) {


 $sql = "Select name,surname,grower_num,growers.userid,area,province,phone,id_num,growers.created_at,growers.seasonid from growers ";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"userid"=>$row["userid"]
              ,"area"=>$row["area"],"province"=>$row["province"],"phone"=>$row["phone"],"id_num"=>$row["id_num"],"created_at"=>$row["created_at"],"seasonid"=>$row["seasonid"]);
            array_push($data,$temp);
            
           }
         }



}



$file_name=$userid."-growers-".time().".txt";
$path="../images/".$file_name;

file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), FILE_APPEND);


}


$url_d=array();

$temp=array("file_url"=>$file_name,"data"=>$data,"time"=>time(),"username"=>$username,"userid"=>$userid,"seasonid"=>$seasonid,"file_url1"=>$file_name,"file_url2"=>$file_name,"file_url3"=>$file_name,"file_url4"=>$file_name,"file_url5"=>$file_name,"file_url6"=>$file_name,);
array_push($url_d,$temp);

  

echo json_encode($url_d); 



?>