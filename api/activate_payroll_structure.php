<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$receiptnumber="";
$loanid=0;

$data1=array();


if (isset($data->userid) && isset($data->description)){

 $userid=$data->userid;
 $description=validate($data->description);

 $user_sql1 = "update payroll_structure set active=0 ";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

          $user_sql11 = "update payroll_structure set active=1 where description='$description'";
         //$sql = "select * from login";
         if ($conn->query($user_sql11)===TRUE) {

              $temp=array("response"=>"success");
              array_push($data1,$temp); 
          }

     
    }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);

    }

  }else{


    $temp=array("response"=>"Field Empty");
    array_push($data1,$temp);

  }




echo json_encode($data1);

?>





