<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->userid)){

$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;
$grower_num=$data->grower_num;
$barcode=$data->barcode;
$lot=$data->lot;
$groups=$data->groups;
$buyer_grade=$data->buyer_grade;
$timb_grade=$data->timb_grade;
$buyer_mark=$data->buyer_mark;
$location=$data->location;
$mass=$data->mass;
$price=$data->price;

$sell_date_format = new DateTime($data->sell_date);
$sell_date=$sell_date_format->format("Y-m-d");

$date = new DateTime();
$datetimes=$date->format('H:i:s');




$sql = "Select * from total_sold_received where seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  
  $user_sql = "INSERT INTO total_sold_received(userid,seasonid) VALUES ($userid,$seasonid)";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
     }else{

     }

 }




$sql = "Select * from location_sell_date where sell_date='$sell_date' and location='$location' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows==0) {
  
  $user_sql = "INSERT INTO location_sell_date(userid,seasonid,sell_date,location,created_at,datetimes) VALUES ($userid,$seasonid,'$sell_date','$location','$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
     }else{

     }

 }



$sql = "Select * from warehousing_sold_bales where barcode='$barcode' and mass=0 and price=0 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  
  $user_sql1 = "update warehousing_sold_bales set grower_num='$grower_num',mass=$mass,price=$price,lot=$lot,buyer_grade='$buyer_grade',location='$location',timb_grade='$timb_grade' where seasonid=$seasonid and id=$user_found";
 //$sql = "select * from login";
     if ($conn->query($user_sql1)===TRUE) {

       $temp=array("response"=>"successfully updated","barcode"=>$barcode);
        array_push($response,$temp);
       
    }
    
}else{

  $user_sql = "INSERT INTO warehousing_sold_bales(userid,seasonid,grower_num,barcode,lot,buyer_grade,timb_grade,buyer_mark,location,mass,price,sell_date,created_at,datetimes) VALUES ($userid,$seasonid,'$grower_num','$barcode','$lot','$buyer_grade','$timb_grade','$buyer_mark','$location','$mass','$price','$sell_date','$created_at','$datetimes')";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $bale_value=$mass*$price;


       $user_sql1 = "update total_sold_received set sold_mass=sold_mass+$mass,sold_bales=sold_bales+1,sold_total_value=sold_total_value+$bale_value where seasonid=$seasonid";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

         $temp=array("response"=>"success","barcode"=>$barcode);
          array_push($response,$temp);
         
        }

      
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }


}else{


$temp=array("response"=>"Field empty","barcode"=>$barcode);
array_push($response,$temp);
	
}


echo json_encode($response);



?>





