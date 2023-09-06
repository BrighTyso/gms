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
$created_at="";
$seasonid=0;
$response=0;
$fieldofficerid=0;
$chairmanid=0;
$areaManagerid=0;
$growerid=0;


$chairman="";
$fieldOfficer="";
$area_manager="";


$grower_field_officer_found=0;
$grower_chairman_found=0;
$chairman_field_officer_found=0;
$field_officer_area_manager_found=0;
$data1=array();


if (isset($data->userid) && isset($data->name)  && isset($data->surname)  && isset($data->grower_num)  && isset($data->area)  &&  isset($data->province)  && isset($data->phone)  && isset($data->id_num)   && isset($data->created_at) && isset($data->fieldofficer) && isset($data->area_manager) && isset($data->chairman) ){


$userid=$data->userid;
$name=$data->name;
$surname=$data->surname;
$grower_num=$data->grower_num;
$area=$data->area;
$province=$data->province;
$phone=$data->phone;
$id_num=$data->id_num;
$chairman=$data->chairman;
$fieldOffice=$data->fieldofficer;
$area_manager=$data->area_manager;
$created_at=$data->created_at;


$sql = "Select * from seasons where active=1 limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $found=$row["id"];
    $seasonid=$row["id"];
    
   }
 }


 $sql = "Select growers.id from growers  where  grower_num='$grower_num' limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];
   
    
   }

 }


  $sql = "Select * from grower_managers  where  growerid=$growerid and seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $grower_field_officer_found=$row["id"];
   
    
   }

 }



if ($response==0 && $seasonid>0) {

  	$grower_sql = "INSERT INTO growers(userid,name,surname,grower_num,area,province,phone,id_num,seasonid,created_at) VALUES ($userid,'$name','$surname','$grower_num','$area','$province','$phone','$id_num',$seasonid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {

   	$last_id = $conn->insert_id;

   	if ($grower_field_officer_found==0) {

   			$grower_field_sql = "INSERT INTO grower_managers(userid,growerid,seasonid,chairman,fieldOfficer,area_manager,created_at) VALUES ($userid,$last_id,$seasonid,'$chairman','$fieldOffice','$area_manager','$created_at')";
	   //$sql = "select * from login";
			  	 if ($conn->query($grower_field_sql)===TRUE) {

			  	 		$temp=array("response"=>"success");
							array_push($data1,$temp);

			  	 }else{

				   	$temp=array("response"=>$conn->error);
						array_push($data1,$temp);

			     }
   	}else{

       		$temp=array("response"=>"success");
					array_push($data1,$temp);

   	}

   

			

   }else{

   	$temp=array("response"=>$conn->error);
		array_push($data1,$temp);
   }

}else{


	if ($grower_field_officer_found==0) {

			$grower_field_sql = "INSERT INTO grower_managers(userid,growerid,seasonid,chairman,fieldOfficer,area_manager,created_at) VALUES ($userid,$growerid,$seasonid,'$chairman','$fieldOffice','$area_manager','$created_at')";
	   //$sql = "select * from login";
			  	 if ($conn->query($grower_field_sql)===TRUE) {

			  	 		$temp=array("response"=>"success");
							array_push($data1,$temp);

			  	 }else{

				   	$temp=array("response"=>$conn->error);
						array_push($data1,$temp);

			 }
	}else{

				$user_sql1 = "update grower_managers set chairman='$chairman',fieldOfficer='$fieldOffice',area_manager='$area_manager' where growerid=$growerid and seasonid=$seasonid";
		   //$sql = "select * from login";
		   if ($conn->query($user_sql1)===TRUE) {

		    $temp=array("response"=>"success");
		    array_push($data1,$temp);

		     
		    }else{

		      $temp=array("response"=>$conn->error);
		       array_push($data1,$temp);

		    }


	}

	 

 
}



}else{

$temp=array("response"=>"Field Empty");
array_push($data1,$temp);

}

	echo json_encode($data1);




?>





