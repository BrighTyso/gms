<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$seasonid=0;
$found=0;

$data1=array();

if (isset($data->userid)){

$userid=$data->userid;
$start_range=$data->start_range;
$end_range=$data->end_range;
$seasonid=$data->seasonid;

$fetched_records=0;
$inserted_records=0;

for ($i=$start_range; $i <=$end_range; $i++) { 
  
      $fetched_records+=1;
      $found=0;
      $sql = "Select * from ministry_of_agricalture_numbers where  seasonid=$seasonid and description='$i' limit 1";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {

          // product id
         $found=$row["id"];
         
         }

       }



      if ($found==0) {
        
      $user_sql = "INSERT INTO ministry_of_agricalture_numbers(userid,seasonid,description) VALUES ($userid,$seasonid,'$i')";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
            $inserted_records+=1;

           

         }else{

      

         }

      }else{
        
      }


      }
  
}

 $temp=array("fetched_records"=>$fetched_records,"processed"=>$inserted_records);
   array_push($data1,$temp);




echo json_encode($data1)

?>



























