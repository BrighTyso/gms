<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$data1=array();

$grower=$data->grower;
$name=$data->name;
$surname=$data->surname;
$n_id=$data->n_id;
$phone=$data->phone;
$area_manager=$data->area_manager;
$chairman=$data->chairman;
$ha=$data->ha;
$an_c=$data->an_c;
$an_quantity=$data->an_quantity;
$an_total=$data->an_total;
$comp_c=$data->comp_c;
$comp_quantity=$data->comp_quantity;
$comp_total=$data->comp_total;
$seed_c=$data->seed_c;
$seed_quantity=$data->seed_quantity;
$seed_total=$data->seed_total;
$type="";

$response=0;
$growerid=0;



$type=$data->type;

  if ($type=="fert") {
    // code...




// checks if grower is already in database

$sql = "Select growers.id from voeselLoans  where  grower='$grower'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];
   
    
   }

 }



if ($response==0) {

 $grower_sql = "INSERT INTO voeselLoans(grower,name,surname,n_id,phone,area_manager,chairman,ha,an_c,an_quantity,an_total,comp_c,comp_quantity,comp_total) VALUES ($grower,$name,$surname,$n_id,$phone,$area_manager,$chairman,$ha,$an_c,$an_quantity,$an_total,$comp_c,$comp_quantity,$comp_total)";
	
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     

	   	$temp=array("response"=>"success");
      array_push($data1,$temp);


   }else{

    $temp=array("response"=>$conn->error);
      array_push($data1,$temp);

   }

 }else{

  $temp=array("response"=>"grower Already in database");
  array_push($data1,$temp);

 }



}else{


  // checks if grower is already in database

$sql = "Select growers.id from voeselLoans  where  grower='$grower'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];
   
    
   }

 }


 if ($response==0) {


  $grower_sql = "INSERT INTO voeselLoans(grower,name,surname,n_id,phone,area_manager,chairman,ha,seed_c ,seed_quantity,seed_total) VALUES ($grower,$name,$surname,$n_id,$phone,$area_manager,$chairman,$seed_c ,$seed_quantity,$seed_total)";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     

      $temp=array("response"=>"success");
      array_push($data1,$temp);


   }else{

    $temp=array("response"=>$conn->error);
      array_push($data1,$temp);

   }

 }else{

   $user_sql1 = "update voeselLoans set seed_c=$seed_c,seed_quantity=$seed_quantity,seed_total=$seed_total where id=$growerid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success");
    array_push($data1,$temp);

     
    }

 }




}





echo json_encode($data1);



?>


