<?php
require "conn.php";
require "validate.php";


$userid="";
$distance=0.0;
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;
$ha="";

$data=array();



//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) &&  isset($_GET['distance']) && isset($_GET['created_at']) && isset($_GET['sqliteid']) && isset($_GET['seasonid'])){

$userid=$_GET['userid'];
$distance=$_GET['distance'];
$created_at=$_GET['created_at'];
$seasonid=$_GET['seasonid'];
$sqliteid=$_GET['sqliteid'];



$response=0;
$farm_response=0;


// checks if grower is already in database





//check farm
$sql1 = "Select id from distance  where created_at='$created_at' and userid=$userid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $farm_response=1;
  // $growerid=$row["id"];
   
    
   }

 }



 if ($farm_response==0){

	$grower_farm_sql = "INSERT INTO distance(userid,distance,seasonid,created_at) VALUES ($userid,'$distance',$seasonid,'$created_at')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

	     $temp=array("response"=>"success","sqliteid"=>$sqliteid);
        array_push($data,$temp);

	   }else{

	    $temp=array("response"=>$conn->error,"sqliteid"=>$sqliteid);
        array_push($data,$temp);

	   }

}else{ 
	$user_sql1 = "update distance set distance=$distance where userid=$userid and created_at='$created_at'";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

    $temp=array("response"=>"success","sqliteid"=>$sqliteid);
    array_push($data,$temp);

     
    }else{

      $temp=array("response"=>$conn->error,"sqliteid"=>$sqliteid);
        array_push($data,$temp);

    }
}


}else{

$temp=array("response"=>"field Empty","sqliteid"=>$sqliteid);
array_push($data,$temp);
	
}





echo json_encode($data);



?>


