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
$growerid=$data->growerid;
$productid=$data->productid;
$quantity=$data->quantity;
$loanid=0;
$disbursement_trucksid=0;
$truck_to_growerid=0;
$loan_payment_found=0;
$loan_found=0;

$response=array();

if (isset($data->seasonid) && isset($data->productid) && isset($data->userid) && isset($data->growerid) && isset($data->quantity)){


 $sql = "Select id,disbursement_trucksid from truck_to_grower where growerid=$growerid and productid=$productid  and quantity=$quantity limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        $truck_to_growerid=$row["id"];
        $disbursement_trucksid=$row["disbursement_trucksid"];
        
        
       }

     }



    $sql = "Select id from loans where growerid=$growerid and productid=$productid and seasonid=$seasonid and quantity=$quantity limit 1";
    $result = $conn->query($sql);
     
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        $loanid=$row["id"];

        

        
       }

     }






if ($growerid>0 && $truck_to_growerid>0 && $disbursement_trucksid>0 && $loanid>0) {

            
         $user_sql = "delete from truck_to_grower where id=$truck_to_growerid and disbursement_trucksid=$disbursement_trucksid";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {

             $user_sql2 = "update disbursement set quantity=quantity+$quantity where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
             //$sql = "select * from login";
             if ($conn->query($user_sql2)===TRUE) {

                $user_sql = "delete from loans where id=$loanid";
            
               if ($conn->query($user_sql)===TRUE) {

                $temp=array("response"=>"success");
                array_push($response,$temp);

               }
             
            }
                
        }

             

      }else{

        $temp=array("response"=>"field cant be empty");
        array_push($response,$temp);
       
      }


}





 echo json_encode($response);






?>





