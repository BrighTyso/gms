<?php
require "conn.php";

$data=array();

//http://192.168.1.190/gms/api/get_products.php

$grower_num=$_GET["grower_num"];

$userid=0;
$seasonid=0;
$rule=0;



  
$sql = "Select name,surname,grower_num,growers.userid,area,province,phone,id_num,growers.created_at,growers.seasonid from growers where grower_num='$grower_num'";
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







 echo json_encode($data); 



?>