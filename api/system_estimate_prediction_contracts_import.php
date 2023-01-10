<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";
$growerid=0;
$grower_bales=0;
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  &&  isset($data->seasonid) &&  isset($data->description)){

$userid=$data->userid;
$description=$data->description;
$created_at=$data->created_at;
$seasonid=$data->seasonid;
//$sqliteid=$data->sqliteid;
$estimate=$data->estimate;
$bales=$data->bales;


$response=0;
$farm_response=0;
$contractorid=0;



  if ($seasonid>0) {

  // checks if grower is already in database

  $sql = "Select growers.id from growers  where  grower_num='$description'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
     $response=1;
     $growerid=$row["id"];

   
     }

   }




  $sql = "Select userid from contracted_hectares  where  growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
     
     $contractorid=$row["userid"];


     }

   }



  //check farm
  $sql1 = "Select id from system_estimate_prediction  where  growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql1);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

      // product id
     $farm_response=1;
    // $growerid=$row["id"];

         


     }

   }



  $sql1 = "Select id from grower_number_of_bales  where  growerid=$growerid and seasonid=$seasonid";
  $result = $conn->query($sql1);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // product id
      $grower_bales=1;

           

     }

   }



 if ($response==1 && $farm_response==0 && $grower_bales==0 && $contractorid>0){

	$grower_farm_sql = "INSERT INTO system_estimate_prediction(userid,seasonid,growerid,estimate,created_at) VALUES ($contractorid,$seasonid,$growerid,'$estimate','$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   

	     $grower_bales = "INSERT INTO grower_number_of_bales(userid,seasonid,growerid,bales,created_at) VALUES ($contractorid,$seasonid,$growerid,$bales,'$created_at')";
     
     if ($conn->query($grower_bales)===TRUE) {

        $last_id = $conn->insert_id;
     

        $grower_bales1 = "INSERT INTO grower_number_of_bales_total(grower_number_of_balesid,bales,created_at) VALUES ($last_id,$bales,'$created_at')";
     
     if ($conn->query($grower_bales1)===TRUE) {


      $insert_sql = "INSERT INTO tags_total(userid,grower_number_of_balesid,tags_total) VALUES ($contractorid,$last_id,$bales)";
    //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {

       $temp=array("response"=>"success");
       array_push($data1,$temp);

      }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);
    
     }

     }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);
     }

     }else{

      $temp=array("response"=>$conn->error);
       array_push($data1,$temp);
     }

	   }else{

     $temp=array("response"=>$conn->error);
       array_push($data1,$temp);
     }

}else{

if ($grower_bales==1) {

  $temp=array("response"=>"grower found number of bales");
  array_push($data1,$temp);
  
  
}else if ($farm_response==1) {

  $temp=array("response"=>"grower found estimates");
   array_push($data1,$temp);

}

}

}else{

  $temp=array("response"=>"Season not found");
   array_push($data1,$temp);

}


}else{

	  $temp=array("response"=>"Field Empty");
    array_push($data1,$temp);

}


echo json_encode($data1);



?>


