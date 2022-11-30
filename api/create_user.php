<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$username="";
$name="";
$surname="";
$hash="";
$rightsid=0;
$active=0;
$found_store=0;
$user_found=0;

$response=array();

if (isset($data->username) && isset($data->hash)  && isset($data->name)  && isset($data->surname)  && isset($data->rightsid) && isset($data->active)){


$username=$data->username;
$name=$data->name;
$surname=$data->surname;
$hash=$data->hash;
$rightsid=$data->rightsid;
$active=$data->active;
$created_at=$data->created_at;
$userid=$data->userid;




$sql = "Select * from users where username='$username' and name='$name' and surname='$surname' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  

$temp=array("response"=>"User already Created");
 array_push($response,$temp);


}else{

$user_sql = "INSERT INTO users(name,surname,username,hash,rightsid,active,access_code,created_at) VALUES ('$name','$surname','$username','$hash',$rightsid,$active,1234,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     if ($rightsid==14) {

     // creating store for company

                $sql = "Select * from store where name='$name'";
                $result = $conn->query($sql);
                 
                 if ($result->num_rows > 0) {
                   // output data of each row
                   while($row = $result->fetch_assoc()) {
                    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                    $found_store=$row["id"];
                    
                   }
                 }


                if ($found_store==0) {
                  $user_sql = "INSERT INTO store(userid,name,location,created_at) VALUES ($userid,'$name','$name','$created_at')";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql)===TRUE) {
                   
                     $last_id = $conn->insert_id;
                     $temp=array("response"=>"success");
                     array_push($response,$temp);
                     
                   }else{

                   $temp=array("response"=>$conn->error);
                   array_push($response,$temp);

                   }
                }else{

                  $temp=array("response"=>"already Inserted");
                  array_push($response,$temp);

                }


      //end here

       
     }else{

     $temp=array("response"=>"success");
     array_push($response,$temp);

     }
     
   }else{

   $temp=array("response"=>$conn->error);
   array_push($response,$temp);

   }

 }


}else{


$temp=array("response"=>"failed");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





