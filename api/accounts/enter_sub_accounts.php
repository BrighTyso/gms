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


if (isset($data->userid) &&  isset($data->seasonid)){

  $userid=$data->userid;
  $seasonid=$data->seasonid;
  $description=$data->description;
  $main_accountid=$data->main_accountid;
 

  //$created_at=date("Y-m-d");
  $found=0;

//check farm
$sql1 = "Select id from sub_accounts  where  description='$description' and main_accountid=$main_accountid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $found=$row['id'];
   
   }

 }



 if ($found==0){

  $grower_farm_sql = "insert into sub_accounts(description,main_accountid) value('$description',$main_accountid);";
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


