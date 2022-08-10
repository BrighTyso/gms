<?php
require "conn.php";
require "validate.php";


$userid="";
$name="";
$surname="";
$grower_num="";
$area="";
$province="";
$phone="";
$id_num="";
$created_at="";
$sqlitegrowerid=0;
$lat_longid=0;

$data=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) && isset($_GET['name'])  && isset($_GET['surname'])  && isset($_GET['grower_num'])  && isset($_GET['area'])  &&  isset($_GET['province'])  && isset($_GET['phone'])  && isset($_GET['id_num'])   && isset($_GET['created_at']) && isset($_GET['lat']) && isset($_GET['long']) && isset($_GET['sqlitegrowerid']) && isset($_GET['sqlitelat_longid'])){

$userid=$_GET['userid'];
$name=$_GET['name'];
$surname=$_GET['surname'];
$grower_num=$_GET['grower_num'];
$area=$_GET['area'];
$province=$_GET['province'];
$phone=$_GET['phone'];
$id_num=$_GET['id_num'];
$created_at=$_GET['created_at'];
$seasonid=$_GET['seasonid'];
$sqlitegrowerid=$_GET['sqlitegrowerid'];
$lat_longid=$_GET['sqlitelat_longid'];



$lat=$_GET['lat'];
$long=$_GET['long'];

$response=0;
$growerid=0;


// checks if grower is already in database

$sql = "Select growers.id from growers join lat_long on growers.id=lat_long.growerid where name='$name' and surname='$surname'  and grower_num='$grower_num' and id_num='$id_num' and lat_long.seasonid=$seasonid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $response=1;
   $growerid=$row["id"];
   
    
   }

 }



if ($response==0) {


	$grower_sql = "INSERT INTO growers(userid,name,surname,grower_num,area,province,phone,id_num,seasonid,created_at) VALUES ($userid,'$name','$surname','$grower_num','$area','$province','$phone','$id_num',$seasonid,'$created_at')";
   //$sql = "select * from login";
   if ($conn->query($grower_sql)===TRUE) {
   
     $last_id = $conn->insert_id;
     

	$lat_long_sql = "INSERT INTO lat_long(userid,growerid,latitude,longitude,seasonid,created_at) VALUES ($userid,$last_id,'$lat','$long',$seasonid,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($lat_long_sql)===TRUE) {
	   
	    // $last_id = $conn->insert_id;
	     //echo $last_id;

	   	    $temp=array("growerid"=>$sqlitegrowerid,"lat_longid"=>$lat_longid);
           array_push($data,$temp);

	   }



   }

}else if ($response==1){

	$lat_long_sql = "INSERT INTO lat_long(userid,growerid,latitude,longitude,seasonid,created_at) VALUES ($userid,$growerid,'$lat','$long',$seasonid,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($lat_long_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("growerid"=>$sqlitegrowerid,"lat_longid"=>$lat_longid);
        array_push($data,$temp);

	   }else{

	    

	   }

}


}else{

	
}


echo json_encode($data);



?>


