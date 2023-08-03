<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$name="";
$userid=0;
$surname="";
$created_at="";
$found=0;
$response=array();


if (isset($data->name) && isset($data->surname) && isset($data->userid)  && isset($data->created_at)){

$name=$data->name;
$userid=$data->userid;
$surname=$data->surname;
$created_at=$data->created_at;




$sql = "SELECT * FROM fieldoffice where name='$name' and surname='$surname'  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $found=$row['id'];
  
    
   }
 }


if ($found==0) {

  $insert_sql = "INSERT INTO fieldoffice(userid,name,surname,created_at) VALUES ($userid,'$name','$surname','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

      $temp=array("response"=>"success");
      array_push($response,$temp);

   }else{
   

    $temp=array("response"=>$conn->error);
    array_push($response,$temp);

   }


}else{

$temp=array("response"=>"Already Created");
 array_push($response,$temp);

}



}else{

  $temp=array("response"=>"field empty");
  array_push($response,$temp);

	
}


echo json_encode($response);

?>





