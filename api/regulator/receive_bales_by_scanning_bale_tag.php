<?php


require_once("conn.php");
require "validate.php";
require "datasource.php";

$datasource=new DataSource();



$userid=0;
$seasonid=0;
$barcode="";
$qrcode="";
$barcode_found=0;
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($_POST['barcode']) && isset($_POST['qrcode']) && isset($_POST['userid'])  && isset($_POST['latitude']) && isset($_POST['longitude'])){

$barcode=$_POST['barcode'];
$qrcode=$_POST['qrcode'];
$userid=$_POST['userid'];
$latitude=$_POST['latitude'];
$longitude=$_POST['longitude'];

$value=$datasource->encryptor("decrypt",$qrcode);

if ($barcode!="" && $value!="") {

$sql = "Select distinct * from bale_tags  where code='$barcode' and grower_number_of_balesid=$value and used=0  limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

     $barcode_found=$row["id"];

      // $temp=array("id"=>$row["id"],"code"=>$row["code"],"created_at"=>$row["created_at"],"used"=>$row["used"]);
      // array_push($data1,$temp);
 
 
   }

 }



if ($barcode_found>0) {

  $insert_sql = "INSERT INTO bale_receiver(userid,bale_tagid,latitude,longitude) VALUES ($userid,$barcode_found,'$latitude','$longitude')";
       //$gr = "select * from login";
       if ($conn->query($insert_sql)===TRUE) {


      $user_sql1 = "update grower_number_of_bales set bales=bales - 1 where id=$value";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {

            
             $user_sql1 = "update bale_tags set used=1 where id=$barcode_found";
               //$sql = "select * from login";
               if ($conn->query($user_sql1)===TRUE) {

                $temp=array("response"=>"success");
                array_push($data1,$temp);

           
                }
             
            }
          }

}else{

  if ($barcode_found==0) {
     $temp=array("response"=>"Barcode not found or is not registered in your company or already used");
      array_push($data1,$temp);
  }

 

}


}


}



echo json_encode($data1);



?>


