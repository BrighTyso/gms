<?php
require "conn.php";
require "validate.php";


$userid=0;
$grower_num="";
$sqliteid=0;
$latitude="";
$longitude="";
$description="";
$condition="";
$seasonid=0;
$image="";

$data=array();



//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"//&created_at="44-44-44"&lat="12.2223"&long="15.45555"

// if (isset($_POST['userid']) && isset($_POST['grower_num'])  && isset($_POST['latitude'])  && isset($_POST['longitude'])  //&& isset($_POST['description'])  &&  isset($_POST['condition'])  && isset($_POST['seasonid'])  && isset($_POST['image'])   //&& isset($_POST['created_at']) && isset($_POST['sqliteid'])){

$userid=$_POST['userid'];
$grower_num=$_POST['grower_num'];
$latitude=$_POST['latitude'];
$longitude=$_POST['longitude'];
$description=$_POST['description'];
$condition=$_POST['condition'];
$image=$_POST['image'];
$created_at=$_POST['created_at'];
$seasonid=$_POST['seasonid'];
$sqliteid=$_POST['sqliteid'];

$lat=$_POST['latitude'];
$long=$_POST['longitude'];

$response=0;
$growerid=0;





// checks if grower is already in database

$sql = "Select growers.id from growers join lat_long on growers.id=lat_long.growerid where  growers.grower_num=$grower_num  and lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];

 
   }

 }



if ($response==1) {

	$grower_sql = "INSERT INTO assessment_images(userid,growerid,seasonid,image,description,conditions,latitude,longitude,created_at) VALUES ($userid,$growerid,$seasonid,'$image','$description','$condition','$latitude','$longitude','$created_at')";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

      $temp=array("sqliteid"=>$sqliteid);
      array_push($data,$temp);
   
   }else{

   
   }

}else{

$temp=array("sqliteid"=>$sqliteid);
      array_push($data,$temp);

}


// }else{

// 	echo json_encode("empty");
// }


echo json_encode($data);



?>


