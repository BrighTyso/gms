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

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) &&  isset($_GET['grower_num']) && isset($_GET['created_at']) && isset($_GET['username']) && isset($_GET['trucknumber']) && isset($_GET['sqliteid'])){

$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];
$username=$_GET['username'];
$trucknumber=$_GET['trucknumber'];
$seasonid=$_GET['seasonid'];
$sqliteid=$_GET['sqliteid'];
$datetimes=$_GET['datetimes'];
$created_at=$_GET['created_at'];
$verifierid=0;
$trucknumberid=0;


$response=0;
$farm_response=0;
$disbursement_finger_print_verification_and_approval=0;
$growerid=0;
$verifierid=0;
$trucknumberid=0;

// checks if grower is already in database

$sql = "Select growers.id from growers  where  grower_num='$grower_num'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id

   $growerid=$row["id"];
   
    
   }

 }


$sql = "Select id from users  where  username='$username'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  $verifierid=$row["id"];
   
    
   }

 }




//check farm
$sql1 = "Select id from truck_destination  where  trucknumber='$trucknumber'";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $trucknumberid=$row["id"];
  // $growerid=$row["id"];
   
    
   }

 }




$sql1 = "Select id from disbursement_finger_print_verification_and_approval  where  disbursement_trucksid=$trucknumberid and userid_verifier=$verifierid and growerid=$growerid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    //product id
   $disbursement_finger_print_verification_and_approval=$row["id"];
    //$growerid=$row["id"];
   
    
   }

 }





 if ($growerid>0 && $trucknumberid>0 && $verifierid>0 && $disbursement_finger_print_verification_and_approval==0){

	$grower_farm_sql = "INSERT INTO disbursement_finger_print_verification_and_approval(userid,seasonid,growerid,disbursement_trucksid,userid_verifier,created_at,datetimes) VALUES ($userid,$seasonid,$growerid,$trucknumberid,$verifierid,'$created_at','$datetimes')";
	   //$sql = "select * from login";
	   if ($conn->query($grower_farm_sql)===TRUE) {
	   
	     $last_id = $conn->insert_id;

	     //$sqlitegrowerid=0;

       $temp=array("id"=>$sqliteid);
        array_push($data,$temp);

    

	   }else{

	    $temp=array("id"=>$conn->error);
        array_push($data,$temp);

	   }

}else{



}


}else{

 $temp=array("response"=>"already created");
  array_push($data,$temp);
	
}


echo json_encode($data);



?>


