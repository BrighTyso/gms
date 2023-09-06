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


if (isset($data->userid) &&  isset($data->sub_accountid) && isset($data->description)  && isset($data->name) && isset($data->serial_number) && isset($data->location)){

  $sub_accountid=$data->sub_accountid;
  $name=$data->name;
  $serial_number=$data->serial_number;
  $location=$data->location;
  $description=$data->description;
  $userid=$data->userid;


  $created_at=date("Y-m-d");


  $found=0;



//check farm
$sql1 = "Select id from assets  where  sub_accountid=$sub_accountid and name='$name' and serial_number='$serial_number'";
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

  $grower_farm_sql = "INSERT INTO assets(userid,sub_accountid,name,serial_number,location,description) VALUES ($userid,$sub_accountid,'$name','$serial_number','$location','$description')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       //$sqlitegrowerid=0;

       $temp=array("response"=>"success");
        array_push($data1,$temp);

     }else{

      

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


