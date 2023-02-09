<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require_once("vendor/autoload.php");
require "validate.php";
require "datasource.php";


$datasource=new DataSource();



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$code="";
$grower_number_of_balesid=0;
$created_at="";
$tags_total_found=0;
$code_found=0;
$seasonid=0;


$data1=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->code) && $data->code!="" && isset($data->userid)  && isset($data->grower_number_of_balesid)  && isset($data->created_at)){


$userid=$data->userid;
$code=validate($data->code);
$grower_number_of_balesid=$datasource->encryptor("decrypt",$data->grower_number_of_balesid);
$created_at=$data->created_at;




$sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $seasonid=$row["id"]; 

  
   }

 }

  


$sql = "Select * from bale_tags where code='$code' and seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $code_found=$row["id"]; 

  
   }

 }
 


$sql = "Select * from tags_total where grower_number_of_balesid=$grower_number_of_balesid and  tags_total>tags_generated and tags_total!=tags_generated";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $tags_total_found=$row["id"]; 

   }

 }


 if ($code_found==0 && $tags_total_found>0) {
   
    $insert_sql = "INSERT INTO bale_tags(userid,grower_number_of_balesid,code,seasonid,created_at) VALUES ($userid,$grower_number_of_balesid,'$code',$seasonid,'$created_at')";
    //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {
       
        // $last_id = $conn->insert_id;

            $user_sql1 = "update tags_total set tags_generated=tags_generated + 1 where id=$tags_total_found";
                           //$sql = "select * from login";
             if ($conn->query($user_sql1)===TRUE) {


              $barcodes='images/'.$code.'.png';


              $color=[0,0,0];
              $generator=new Picqer\Barcode\BarcodeGeneratorPNG();
              file_put_contents("$barcodes",$generator->getBarcode($code,$generator::TYPE_CODE_128,3,50,$color));


              $temp=array("response"=>"success");
              array_push($data1,$temp);

               
              }

        }

 }else{

  if ($code_found>0) {
    $temp=array("response"=>"barcode already created");
  array_push($data1,$temp);
  }else{

    $temp=array("response"=>"Out of Tags");
  array_push($data1,$temp);
  }

  

 }



 }






echo json_encode($data1);


?>





