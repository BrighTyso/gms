<?php 

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$data1=array();
$response=array();

if (isset($data->userid) && isset($data->seasonid)) {
 
$userid=$data->userid;
$seasonid=$data->seasonid;
$field_officer=$data->description;
$found=0;
$growerid=0;
$fetched_records=0;
$inserted_records=0;
$found_rollover=0;
$found_working_capital=0;
$field_officerid=0;

$sql2 = "Select users.id from grower_field_officer join users on users.id=grower_field_officer.field_officerid where username='$field_officer' or name='$field_officer' or surname='$field_officer' limit 1";
  $result2 = $conn->query($sql2);
   
   if ($result2->num_rows > 0) {
     // output data of each row
     while($row2 = $result2->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $field_officerid=$row2["id"];
      
     }

   }


  $sql = "Select distinct loans.id as loanid,products.id as productid,quantity from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join prices on prices.productid=loans.productid join grower_field_officer on grower_field_officer.growerid=growers.id where loans.seasonid=$seasonid and field_officerid=$field_officerid and processed=0  order by growers.grower_num limit 5000";

  $result = $conn->query($sql);
 
  if ($result->num_rows > 0) {
   // output data of each row
     $fetched_records=$result->num_rows;
     $count=0;
     
   while($row = $result->fetch_assoc()) {

   		 
		  $loanid=$row["loanid"];
		  $quantity=$row["quantity"];
		  $productid=$row["productid"];
		  $disbursement_trucksid=0;
		  $truck_to_growerid=0;
		  $loan_payment_found=0;
		  $loan_found=0;
		  $processed=0;
		  $created_at=date("Y-m-d");


 $sql12 = "Select truck_to_grower.id,disbursement_trucksid,processed from truck_to_grower join loans on loans.id=truck_to_grower.loanid where loanid=$loanid limit 1";
    $result1 = $conn->query($sql12);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row1 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

        $loan_found=1;
        $truck_to_growerid=$row1["id"];
        $disbursement_trucksid=$row1["disbursement_trucksid"];
        $processed=$row1["processed"];

        
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

                     $inserted_records+=1;
                     

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


    $sql12 = "Select * from arc_product_grower join arc_products on arc_product_grower.arc_productid=arc_products.id  join loans on loans.id=arc_product_grower.loanid where arc_product_grower.loanid=$loanid";
      $result1 = $conn->query($sql12);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) { 
         
         $store_to_grower=$row1["id"];
         $storeitemid=$row1["storeitemid"];
         $processed=$row1["processed"];  
         $loan_quantity=$row1["quantity"];


         }

       }




       $sql12 = "Select * from store_items  where productid=$productid and id=$storeitemid";
      $result1 = $conn->query($sql12);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) { 
         
         $store_quantity=$row1["quantity"];
         $storeid=$row1["storeid"];
           
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



                                    	$inserted_records+=1;

                                       

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


   }
 }
}

  $temp=array("fetched_records"=>$fetched_records,"processed"=>$inserted_records);
   array_push($data1,$temp);



 echo json_encode($data1);


?>