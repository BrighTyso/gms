<?php

require_once("conn.php");
require "validate.php";

$userid=0;
$id=0;

$data1=array();


if (isset($_POST["userid"]) && isset($_POST["id"]) && isset($_POST["price"])){


$userid=$_POST["userid"];
$id=$_POST["id"];
$price=$_POST["price"];
 

 $user_sql1 = "update bale_counting_redo set price=$price where id=$id";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success");
    array_push($data1,$temp);

     
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





