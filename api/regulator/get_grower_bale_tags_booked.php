<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();



$data = json_decode(file_get_contents("php://input"));

$userid=0;
$code="";
$grower_number_of_balesid=0;
$created_at="";
$tags_total_found=0;
$code_found=0;


$data1=array();




//http://192.168.1.190/gms/api/enter_hail_strike.php?userid=1&grower_num=V123456&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&percentage_strike=12333&strike_date=12333&seasonid=1&sqliteid=1

if (isset($data->userid)  && isset($data->grower_number_of_balesid)){


$userid=$data->userid;
$grower_number_of_balesid=$datasource->encryptor("decrypt",$data->grower_number_of_balesid);



  


$sql = "Select code,used,bale_tags.id,bale_tag_to_sold_bale.bale_tagid,bale_tags.created_at from bale_tags join bale_booked on bale_booked.bale_tagid=bale_tags.id left join bale_tag_to_sold_bale on bale_tag_to_sold_bale.bale_tagid=bale_tags.id  where grower_number_of_balesid=$grower_number_of_balesid and bale_booked.userid=$userid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $temp=array("code"=>$row["code"],"used"=>$row["used"],"id"=>$row["id"],"bale_tagid"=>$row["bale_tagid"],"created_at"=>$row["created_at"]);
   array_push($data1,$temp);

  
   }

 }
 


 }






echo json_encode($data1);


?>





