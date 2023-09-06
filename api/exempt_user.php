<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid="";
$created_at="";
$userid=0;
$sub_accountid=0;
$name="";
$serial_number="";
$location="";
$description="";

$data1=array();


//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->fieldOfficerid)){

  $fieldOfficerid=$data->fieldOfficerid;
  $userid=$data->userid;
 
  $created_at=date("Y-m-d");

  $found=0;


//check farm
$sql1 = "Select id from exempt_user where  fieldOfficerid=$fieldOfficerid and created_at='$created_at'";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $found=1;
  // $growerid=$row["id"];
   
    
   }

 }



 if ($found==0){

  $grower_farm_sql = "INSERT INTO exempt_user(userid,fieldOfficerid,created_at) VALUES ($userid,$fieldOfficerid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $temp=array("response"=>"success");
            array_push($data1,$temp);

     }else{

      $temp=array("response"=>$conn->error);
            array_push($data1,$temp);

     }

}else{

 $temp=array("response"=>"Already Captured");
  array_push($data1,$temp);

}

}else{

  $temp=array("response"=>"Field Empty");
  array_push($data1,$temp);


}








echo json_encode($data1);



?>


