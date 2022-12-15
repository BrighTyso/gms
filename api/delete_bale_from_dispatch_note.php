<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$dispatch_noteid=0;
$dispatchid=0;

$data1=array();


if (isset($data->dispatch_noteid) && isset($data->dispatchid)){

$dispatch_noteid=$data->dispatch_noteid;
$dispatchid=$data->dispatchid;
$canDelete=0;




    $sql = "Select id from dispatch_note_total_received  where dispatch_noteid=$dispatch_noteid and  quantity=0 ";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
          $canDelete=$row["id"];
        
       }

     }



      if ($canDelete>0) {

              $user_sql1 = "DELETE FROM dispatch where dispatch.id = $dispatchid and dispatch.dispatch_noteid=$dispatch_noteid";
         //$sql = "select * from login";
         if ($conn->query($user_sql1)===TRUE) {

                $user_sql2 = "update dispatch_note_total_dispatched set quantity=quantity-1 where dispatch_noteid=$dispatch_noteid";
               //$sql = "select * from login";
               if ($conn->query($user_sql2)===TRUE) {

                $temp=array("response"=>"success");
                array_push($data1,$temp);
                 
                }

              }
       
          }else{

                $temp=array("response"=>"Cant Delete Bale From Dispatch Note");
                array_push($data1,$temp);

          }


      }



 


echo json_encode($data1);

?>





