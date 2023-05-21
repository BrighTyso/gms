<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=$data->userid;
$seasonid=$data->seasonid;
$growerid=0;
$loan_found=0;
$grower_num=$data->grower_num;
$amount=$data->amount;
$created_at=$data->created_at;
$mass=$data->mass;
$loan_payment_found=0;

$response=array();

if (isset($data->seasonid) && isset($data->userid) && isset($data->grower_num) && isset($data->amount) && isset($data->created_at)){


  $sql = "Select * from growers where grower_num='$grower_num'";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      
     }

   }


    $sql = "Select * from loan_payments where growerid=$growerid and seasonid=$seasonid and created_at='$created_at' and mass='$mass' and amount='$amount' limit 1";
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




if ($growerid>0 && $loan_found==0) {

      $user_sql = "INSERT INTO loan_payments(userid,seasonid,growerid,amount,mass,description,receipt_number,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$mass','Recovery','R1111','$created_at')";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
            if ($loan_payment_found==0) {
            
                 $user_sql = "INSERT INTO loan_payment_total(userid,seasonid,growerid,amount,mass,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$mass','$created_at')";
                   //$sql = "select * from login";
                       if ($conn->query($user_sql)===TRUE) {

                            $temp=array("response"=>"success");
                            array_push($response,$temp);
                        
                       }

                  }else{

                      $user_sql2 = "update loan_payment_total set amount=amount+$amount , mass=mass+$mass  where growerid = $growerid and seasonid=$seasonid";
                     //$sql = "select * from login";
                     if ($conn->query($user_sql2)===TRUE) {
                     
                        $temp=array("response"=>"success");
                       array_push($response,$temp);

                     }else{

                      //$last_id = $conn->insert_id;
                       $temp=array("response"=>"Failed To Update");
                       array_push($response,$temp);

                     }

                  }

         }else{

          $temp=array("response"=>"Failed ");
               array_push($response,$temp);

         }




      }else{

        $temp=array("response"=>"field cant be empty");
               array_push($response,$temp);

       
      }


}





 echo json_encode($response);






?>





