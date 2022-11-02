<?php
require "conn.php";
require "validate.php";


$userid=0;
$grower_num="";
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;
$estimate=0;
$varieties="";

$data=array();






//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid'])  && isset($_GET['grower_num']) && isset($_GET['created_at']) && isset($_GET['sqliteid'])  && isset($_GET['varieties'])  && isset($_GET['estimate'])){

$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];
$created_at=$_GET['created_at'];
$seasonid=$_GET['seasonid'];
$sqliteid=$_GET['sqliteid'];
$estimate=$_GET['estimate'];
$varieties=$_GET['varieties'];








$response=0;
$farm_response=0;


// checks if grower is already in database

$sql = "Select growers.id from growers  where  grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];

   
   
    
   }

 }




//check farm
$sql1 = "Select id from estimates  where  growerid=$growerid and seasonid=$seasonid and varieties='$varieties'";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $farm_response=1;
  // $growerid=$row["id"];


   
    
   }

 }



 if ($response==1 && $farm_response==0){

	$grower_farm_sql = "INSERT INTO estimates(userid,seasonid,growerid,varieties,estimate,created_at) VALUES ($userid,$seasonid,$growerid,'$varieties','$estimate','$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("sqliteid"=>$sqliteid);
        array_push($data,$temp);

	   }else{

	    echo $conn->error;

	   }

}


}else{

	
}


echo json_encode($data);



?>


