<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


require "datasource.php";


$company_code=new CompanyCode();
$warehouse_code=new CompanyWarehouseCode();


$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->selling_pointid) && isset($data->companyid)  && isset($data->userid)){


$selling_pointid=$data->selling_pointid;
$companyid=$data->companyid;
$userid=$data->userid;


$sql = "Select * from company_to_selling_point where companyid=$companyid and selling_pointid=$selling_pointid  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $user_found=$row["id"];

  
   }

 }



if ($user_found>0) {
  

$temp=array("response"=>"Selling Point already Created");
 array_push($response,$temp);


}else{

  $user_sql = "INSERT INTO company_to_selling_point(userid,companyid,selling_pointid) VALUES ($userid,$companyid,$selling_pointid)";
     //$sql = "select * from login";
     if ($conn->query($user_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $temp=array("response"=>"success");
      array_push($response,$temp);
       
     }else{

     $temp=array("response"=>$conn->error);
     array_push($response,$temp);

     }

   }


}else{


$temp=array("response"=>"Field empty");
array_push($response,$temp);
	
}


echo json_encode($response);



?>





