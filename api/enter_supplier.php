<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$created_at="";
$found=0;

$response=array();

if (isset($data->userid) && isset($data->name) && isset($data->email) && isset($data->address) && isset($data->phone) && isset($data->created_at)){



$userid=$data->userid;
$name=$data->name;
$email=$data->email;
$address=$data->address;
$created_at=$data->created_at;
$phone=$data->phone;


$sql = "Select * from supplier where name='$name'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    
    
   }
 }




if ($found==0) {
   $user_sql = "INSERT INTO supplier(userid,name,location,email,phone,created_at) VALUES ($userid,'$name','$address','$email','$phone','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($user_sql)===TRUE) {
   
        $last_id = $conn->insert_id;

         $temp=array("response"=>"success");
          array_push($response,$temp);


   }else{

     $temp=array("response"=>"failed");
     array_push($response,$temp);

   }

}else{

    $temp=array("response"=>"Supplier Already Created");
     array_push($response,$temp);

}
 



}else{

	 $temp=array("response"=>"Field Empty");
     array_push($response,$temp);
  
}


echo json_encode($response);



?>





