<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$name="";
$surname="";
$grower_num="";
$area="";
$province="";
$phone="";
$id_num="";
$id=0;

$data1=array();


if (isset($data->userid) && isset($data->name) && isset($data->surname)    && isset($data->grower_num) && isset($data->area) && isset($data->province) && isset($data->phone) && isset($data->id_num) && isset($data->growerid)){



$name=$data->name;
$surname=$data->surname;
$grower_num=$data->grower_num;
$area=$data->area;
$province=$data->province;
$phone=$data->phone;
$id_num=$data->id_num;
 $userid=$data->userid;
 $id=$data->growerid;


 

 $user_sql1 = "update growers set name='$name',surname='$surname',grower_num='$grower_num',area='$area',province='$province',phone='$phone',id_num='$id_num' where id=$id";
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





