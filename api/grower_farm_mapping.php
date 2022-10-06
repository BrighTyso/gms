<?php
require "conn.php";
require "validate.php";


$userid="";
$grower_num="";
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;

$data=array();

$first_lat="";
$first_long="";

$second_lat="";
$second_long="";

$third_lat="";
$third_long="";

$forth_lat="";
$forth_long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) &&  isset($_GET['grower_num']) && isset($_GET['created_at']) && isset($_GET['lat']) && isset($_GET['long']) && isset($_GET['sqliteid'])){

$userid=$_GET['userid'];

$grower_num=$_GET['grower_num'];
$created_at=$_GET['created_at'];

$seasonid=$_GET['seasonid'];
$sqliteid=$_GET['sqliteid'];

$first_lat=$_GET['first_lat'];
$first_long=$_GET['first_long'];

$second_lat=$_GET['second_lat'];
$second_long=$_GET['second_long'];

$third_lat=$_GET['third_lat'];
$third_long=$_GET['third_long'];

$forth_lat=$_GET['forth_lat'];
$forth_long=$_GET['forth_long'];

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
$sql1 = "Select id from farm_mapping  where  growerid=$growerid and seasonid=$seasonid";
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

	$grower_farm_sql = "INSERT INTO farm_mapping(userid,seasonid,growerid,first_lat,first_long,second_lat,second_long,third_lat,third_long,forth_lat,forth_long,created_at) VALUES ($userid,$seasonid,$growerid,'$first_lat','$first_long','$second_lat','$second_long','$third_lat','$third_long','$forth_lat','$forth_long','$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("sqliteid"=>$sqliteid);
        array_push($data,$temp);

	   }else{

	    

	   }

}


}else{

	
}


echo json_encode($data);



?>


