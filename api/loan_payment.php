<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));


$response=array();
$growerid=0;
if (isset($data->seasonid) && isset($data->userid)  && isset($data->amount) && isset($data->mass) && isset($data->grower_num) && isset($data->created_at) && isset($data->bales)){


  $userid=$data->userid;
  $seasonid=$data->seasonid;
  $amount=$data->amount;
  $created_at=$data->created_at;
 
  $date=new DateTime($data->payment_date);
  $payment_date= $date->format("Y-m-d");
  
  $grower_num=$data->grower_num;
  $ref=$data->ref;
  $mass=$data->mass;
  $bales=$data->bales;
  $loan_found=0;
  $growerid=0;
  $loan_payment_found=0;



// $date = new DateTime();
// $datetimes=$date->format('H:i:s');


  $sql = "Select * from growers where grower_num='$grower_num' or grower_num like '%$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }


  $sql = "Select * from loan_payments where growerid=$growerid and seasonid=$seasonid and payment_date='$payment_date' and mass='$mass' limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        
       }

     }



    $sql = "Select * from loan_payment_total where growerid=$growerid and seasonid=$seasonid limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_payment_found=1;
        
       }

     }




     if ($loan_found==0) {


        $user_sql = "INSERT INTO loan_payments(userid,seasonid,growerid,amount,mass,created_at,description,receipt_number,reference_num,payment_date,bales) VALUES ($userid,$seasonid,$growerid,'$amount','$mass','$created_at','Recovery','R1111','$ref','$payment_date',$bales)";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {


          if ($loan_payment_found==0) {
            
         $user_sql = "INSERT INTO loan_payment_total(userid,seasonid,growerid,amount,mass,bales,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$mass',$bales,'$created_at')";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {

                    $temp=array("response"=>"success");
                    array_push($response,$temp);
                
               }else{
                $temp=array("response"=>$conn->error);
                    array_push($response,$temp);
               }

          }else{

              $user_sql2 = "update loan_payment_total set amount=amount+$amount , mass=mass+$mass , bales=bales+$bales  where growerid = $growerid and seasonid=$seasonid";
             //$sql = "select * from login";
             if ($conn->query($user_sql2)===TRUE) {
             
                $temp=array("response"=>"success");
               array_push($response,$temp);

             }else{

              //$last_id = $conn->insert_id;
               $temp=array("response"=>$conn->error);
               array_push($response,$temp);

             }

          }
        

         }else{

           $temp=array("response"=>$conn->error);
               array_push($response,$temp);

         }

     }



}else{
   $temp=array("response"=>"field empty");
               array_push($response,$temp);

 
}


 echo json_encode($response);
?>





