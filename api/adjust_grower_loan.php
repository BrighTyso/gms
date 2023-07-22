<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;
$growerid=0;
$receiptnumber="";
$productid=0;
$loanid=0;
$quantity=0;
$newquantity=0;

$loan_found=1;
$truck_to_growerid=0;
$disbursement_trucksid=0;
$disbusment_quantity=0;
$created_at="";

$data1=array();


if (isset($data->userid) && isset($data->receiptnumber) && isset($data->productid) && isset($data->growerid) && isset($data->quantity) && isset($data->newquantity) && isset($data->seasonid) && isset($data->loanid)){

$userid=$data->userid;

$growerid=$data->growerid;
$receiptnumber=$data->receiptnumber;
$productid=$data->productid;
$quantity=$data->quantity;
$newquantity=$data->newquantity;
$seasonid=$data->seasonid;
$loanid=$data->loanid;


$sql = "Select truck_to_grower.id,truck_to_grower.disbursement_trucksid,disbursement.quantity from truck_to_grower join truck_disbursment_sync_active on truck_disbursment_sync_active.disbursement_trucksid=truck_to_grower.disbursement_trucksid join disbursement on disbursement.disbursement_trucksid=truck_disbursment_sync_active.disbursement_trucksid where truck_to_grower.growerid=$growerid and truck_to_grower.productid=$productid  and truck_to_grower.quantity=$quantity and seasonid=$seasonid limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $loan_found=1;
      $truck_to_growerid=$row["id"];
      $disbursement_trucksid=$row["disbursement_trucksid"];
      $disbusment_quantity=$row["quantity"];

      
      
      
     }

   }


  if ($growerid>0 && $truck_to_growerid>0 && $disbursement_trucksid>0 && $loanid>0) {
  

      if ($quantity>$newquantity) {

        $my_quantity=$quantity-$newquantity;

        // update loan

        $user_sql1 = "update loans set quantity=$newquantity  where id=$loanid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {


               $user_sql1 = "update disbursement set quantity=quantity+$my_quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {

                    $user_sql1 = "update truck_to_grower set quantity=$newquantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid and growerid=$growerid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {


                         $insert_sql = "INSERT INTO loan_adjustments(userid,loanid,old_quantity,new_quantity,created_at) VALUES ($userid,$loanid,$quantity,$newquantity,'$created_at')";
                           //$gr = "select * from login";
                           if ($conn->query($insert_sql)===TRUE) {

                                $temp=array("response"=>"success");
                               array_push($data1,$temp);


                           }else{


                              $temp=array("response"=>$conn->error);
                           array_push($data1,$temp);

                           }

                         }else{


                            $temp=array("response"=>$conn->error);
                           array_push($data1,$temp);

                         }


                   }else{


                      $temp=array("response"=>$conn->error);
                       array_push($data1,$temp);

                   }



           }else{

                $temp=array("response"=>$conn->error);
                 array_push($data1,$temp);

           }


    

      }else{


      $my_quantity=$newquantity-$quantity;


      if ($disbusment_quantity>=$my_quantity) {
        

        // update loan

        $user_sql1 = "update loans set quantity=$newquantity  where id=$loanid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {


         $user_sql1 = "update disbursement set quantity=quantity-$my_quantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {

                    $user_sql1 = "update truck_to_grower set quantity=$newquantity  where disbursement_trucksid=$disbursement_trucksid and productid=$productid and growerid=$growerid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {

                       $insert_sql = "INSERT INTO loan_adjustments(userid,loanid,old_quantity,new_quantity,created_at) VALUES ($userid,$loanid,$quantity,$newquantity,'$created_at')";
                       //$gr = "select * from login";
                       if ($conn->query($insert_sql)===TRUE) {

                            $temp=array("response"=>"success");
                           array_push($data1,$temp);


                       }else{


                          $temp=array("response"=>$conn->error);
                          array_push($data1,$temp);

                       }

                     }else{

                          $temp=array("response"=>$conn->error);
                          array_push($data1,$temp);
                     }


                   }else{


                    $temp=array("response"=>$conn->error);
                       array_push($data1,$temp);

                   }



           }else{


            $temp=array("response"=>$conn->error);
             array_push($data1,$temp);


           }


      }else{


        $temp=array("response"=>"Out Of Stock");
        array_push($data1,$temp);

      }


        

      }


  }else{

   

    if ($disbursement_trucksid==0) {

      $temp=array("response"=>"Truck Not Found");
        array_push($data1,$temp);

    }else if ($truck_to_growerid==0) {
      
      $temp=array("response"=>"Grower Truck Not Found");
        array_push($data1,$temp);

    }else{

      $temp=array("response"=>"Grower Truck Not Found");
        array_push($data1,$temp);


    }





  }


 // $user_sql1 = "update loan_deduction_point set point=$point ";
 //   //$sql = "select * from login";
 //   if ($conn->query($user_sql1)===TRUE) {

 //    $temp=array("response"=>"success");
 //    array_push($data1,$temp);

     
 //    }





  }




echo json_encode($data1);

?>





