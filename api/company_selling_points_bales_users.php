<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


require "datasource.php";


#$company_code=new CompanyCode();
#$warehouse_code=new CompanyWarehouseCode();


$data = json_decode(file_get_contents("php://input"));

$found_store=0;
$user_found=0;

$response=array();

if (isset($data->company_to_selling_pointid) && isset($data->companyid)  && isset($data->userid)){


$company_to_selling_pointid=$data->company_to_selling_pointid;
$companyid=$data->companyid;
$userid=$data->userid;
$company_userid=$data->company_userid;


$sql = "Select * from company_users_to_selling_points where companyid=$companyid and company_to_selling_pointid=$company_to_selling_pointid and company_userid=$company_userid limit 1";
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

  $user_sql = "INSERT INTO company_users_to_selling_points(userid,companyid,company_userid,company_to_selling_pointid) VALUES ($userid,$companyid,$company_userid,$company_to_selling_pointid)";
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





