<?php
require "conn.php";
require "validate.php";


$userid="";
$grower_num="";
$created_at="";
$sqliteid=0;
$growerid=0;
$seasonid=0;

$grower_age=0;
$grower_sex=0;
$number_of_works=0;
$income_per_month=0;
$number_of_kids=0;

$data=array();

$lat="";
$long="";

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_GET['userid']) &&  isset($_GET['grower_num']) && isset($_GET['created_at']) && isset($_GET['grower_sex']) && isset($_GET['grower_age']) && isset($_GET['number_of_works']) && isset($_GET['income_per_month']) && isset($_GET['number_of_kids']) && isset($_GET['sqliteid'])){

$userid=$_GET['userid'];
$grower_num=$_GET['grower_num'];
$created_at=$_GET['created_at'];
$seasonid=$_GET['seasonid'];
$sqliteid=$_GET['sqliteid'];

$grower_age=$_GET['grower_age'];
$grower_sex=$_GET['grower_sex'];
$number_of_works=$_GET['number_of_works'];
$income_per_month=$_GET['income_per_month'];
$number_of_kids=$_GET['number_of_kids'];

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
$sql1 = "Select id from data_collection  where  growerid=$growerid and seasonid=$seasonid";
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

	$grower_farm_sql = "INSERT INTO data_collection(userid,seasonid,growerid,grower_age,grower_sex,number_of_works,income_per_month,number_of_kids,created_at) VALUES ($userid,$seasonid,$growerid,$grower_age,$grower_sex,$number_of_works,$income_per_month,$number_of_kids,'$created_at')";
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


