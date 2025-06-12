<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));



$response=array();

if (isset($data->loanid) && isset($data->quantity) && isset($data->otp) && isset($data->userid) && isset($data->productid)){

  $userid=$data->userid;
  $loanid=$data->loanid;
  $quantity=$data->quantity;
  $productid=$data->productid;
  $otp=$data->otp;
  $disbursement_trucksid=0;
  $truck_to_growerid=0;
  $loan_payment_found=0;
  $loan_found=0;
  $processed=0;
  $created_at=date("Y-m-d");


  $security_otp_found=0;



$sql = "Select * from grower_edit_otp WHERE otp ='$otp' and growerid=$growerid
  AND created_at > NOW() - INTERVAL 30 MINUTE; ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {

   // output data of each row
   while($row = $result->fetch_assoc()) {

    $security_otp_found=$row["id"];   
    
   }
 }


if ($security_otp_found>0) {


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




          $store_to_grower=0;
          $storeitemid=0;
          $store_quantity=0;
          $storeid=0;
          $processed=0;
          $loan_quantity=0;


    $sql = "Select * from arc_product_grower join arc_products on arc_product_grower.arc_productid=arc_products.id  join loans on loans.id=arc_product_grower.loanid where arc_product_grower.loanid=$loanid";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) { 
         
         $store_to_grower=$row["id"];
         $storeitemid=$row["storeitemid"];
         $processed=$row["processed"];  
         $loan_quantity=$row["quantity"];


         }

       }




       $sql = "Select * from store_items  where productid=$productid and id=$storeitemid";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) { 
         
         $store_quantity=$row["quantity"];
         $storeid=$row["storeid"];
           
         }

       }



       if ($store_to_grower>0  && $processed==0) {
  

$user_sql = "delete from loan_adjustments where loanid=$loanid";
      
               if ($conn->query($user_sql)===TRUE) {


        $user_sql = "delete from arc_product_grower where loanid=$loanid";
      
               if ($conn->query($user_sql)===TRUE) {

  
              $user_sql = "delete from loans where id=$loanid and processed=0";
      
               if ($conn->query($user_sql)===TRUE) {

            
                $user_sql1 = "update store_items set quantity=quantity+$loan_quantity  where id=$storeitemid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {


                            $new_quantity=$store_quantity+$loan_quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeitemid,$store_quantity,$new_quantity,'$created_at')";
                                  //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $arc_products_id = $conn->insert_id;


                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$loan_quantity,$arc_products_id,'$created_at','GROWER LOAN DELETED',$loan_quantity)";
                              
                                    if ($conn->query($user_sql1)===TRUE) {

                                        $temp=array("response"=>"success");
                                       array_push($response,$temp);

                                   }else{

                                    $temp=array("response"=>$conn->error);
                                    array_push($response,$temp);

                                   }
                               
                              }else{

                              $temp=array("response"=>$conn->error);
                              array_push($response,$temp);

                              
                         

                   }

                       }else{

                        $temp=array("response"=>$conn->error);
                        array_push($response,$temp);

                       }


                   }else{


                    $temp=array("response"=>$conn->error);
                            array_push($response,$temp);
                   }

         }else{


            $temp=array("response"=>$conn->error);
                    array_push($response,$temp);
           }



     }else{


        $temp=array("response"=>$conn->error);
                array_push($response,$temp);
       }



        // if($truck_to_growerid==0){

        //   $temp=array("response"=>"Loan Not Found");
        //   array_push($response,$temp);

        // }elseif($disbursement_trucksid==0){

        //   $temp=array("response"=>"Disbursment Truck Not Found");
        //   array_push($response,$temp);

        // }elseif($processed>0){

        //   $temp=array("response"=>"Loan Already Processed");
        //   array_push($response,$temp);

        // }

        
       
      }


}



}else{
    $temp=array("response"=>"OTP Expired(Not Found)");
        array_push($response,$temp);
}

}else{

$temp=array("response"=>"Field Empty");
        array_push($response,$temp);
}







 echo json_encode($response);






?>





