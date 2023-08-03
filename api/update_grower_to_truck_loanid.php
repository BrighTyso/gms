<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;

$data1=array();
// get grower locations

$chairman="";
$fieldOfficer="";
$area_manager="";
$growerid=0;

if ($userid!="") {
  


$sql11 = "Select truck_to_grower.quantity,truck_to_grower.disbursement_trucksid,growers.grower_num,receipt_number,loans.id,loans.productid,loans.growerid from  loans join truck_to_grower on truck_to_grower.growerid=loans.growerid join growers on growers.id=loans.growerid where loans.seasonid=$seasonid and truck_to_grower.productid=loans.productid and truck_to_grower.quantity=loans.quantity";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

      $loanid=$row['id'];
      $disbursement_trucksid=$row["disbursement_trucksid"];
      $productid=$row['productid'];
      $growerid=$row["growerid"];
      $quantity=$row["quantity"];

      $user_sql1 = "update truck_to_grower set loanid=$loanid where disbursement_trucksid=$disbursement_trucksid and growerid=$growerid  and productid=$productid and quantity=$quantity";
       //$sql = "select * from login";
       if ($conn->query($user_sql1)===TRUE) {

        $temp=array("response"=>"success");
        array_push($data1,$temp);

         
        }else{

          $temp=array("response"=>$conn->error);
           array_push($data1,$temp);

        }

   // $growerid=$row["growerid"];      

   $temp=array("disbursement_trucksid"=>$row["disbursement_trucksid"],"grower_num"=>$row["grower_num"],"receipt_number"=>$row["receipt_number"]);
    array_push($data1,$temp);

   
   }
 }





// $sql = "Select * from grower_managers  where  growerid=$growerid and seasonid=$seasonid limit 1";
//       $result1 = $conn->query($sql);
       
//        if ($result1->num_rows > 0) {
//          // output data of each row
//          while($row1 = $result1->fetch_assoc()) {

//           // product id
//             $chairman=$row1["chairman"];
//             $fieldOfficer=$row1["fieldOfficer"];
//             $area_manager=$row1["area_manager"];
         
          
//          }

//        }


}

 echo json_encode($data1);


?>


