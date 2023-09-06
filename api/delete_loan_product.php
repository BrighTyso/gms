<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));



$response=array();

if (isset($data->loanid) && isset($data->quantity) && isset($data->userid) && isset($data->productid)){

  $userid=$data->userid;
  $loanid=$data->loanid;
  $quantity=$data->quantity;
  $productid=$data->productid;
  $disbursement_trucksid=0;
  $truck_to_growerid=0;
  $loan_payment_found=0;
  $loan_found=0;
  $processed=0;


 $sql = "Select truck_to_grower.id,disbursement_trucksid,processed from truck_to_grower join loans on loans.id=truck_to_grower.loanid where loanid=$loanid limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        $truck_to_growerid=$row["id"];
        $disbursement_trucksid=$row["disbursement_trucksid"];
        $processed=$row["processed"];

        
        
        
       }

     }



  


if ($truck_to_growerid>0 && $disbursement_trucksid>0 && $loanid>0 && $loan_found>0 && $processed==0) {

 

               $user_sql = "delete from truck_to_grower where id=$truck_to_growerid and disbursement_trucksid=$disbursement_trucksid and loanid=$loanid";
                 //$sql = "select * from login";
                     if ($conn->query($user_sql)===TRUE) {

                   $user_sql2 = "update disbursement set quantity=quantity+$quantity where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql2)===TRUE) {


                     $user_sql = "delete from loans where id=$loanid and processed=0";
            
                     if ($conn->query($user_sql)===TRUE) {


                      $temp=array("response"=>"success");
                      array_push($response,$temp);

                     }else{

                      $temp=array("response"=>$conn->error);
                      array_push($response,$temp);

                     }
                   
                  }
                  else{

                  $temp=array("response"=>$conn->error);
                  array_push($response,$temp);

                 }
                
        }
        else{

          $temp=array("response"=>$conn->error);
          array_push($response,$temp);

         }

             

      }else{



        if($truck_to_growerid==0){

          $temp=array("response"=>"Loan Not Found");
          array_push($response,$temp);

        }elseif($disbursement_trucksid==0){

          $temp=array("response"=>"Disbursment Truck Not Found");
          array_push($response,$temp);

        }elseif($processed>0){

          $temp=array("response"=>"Loan Already Processed");
          array_push($response,$temp);

        }

        
       
      }


}





 echo json_encode($response);






?>





