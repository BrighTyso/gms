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
$product_found=0;
$captured_by_id=0;


$description=$data->description;
$grower_num=$data->grower_num;
$seasonid=$data->seasonid;
$userid=$data->userid;
$created_at=$data->created_at;

$data1=array();


$sql11 = "Select distinct * from  growers where  (grower_num='$grower_num')";
  $result1 = $conn->query($sql11);
 
 if ($result1->num_rows > 0) {
   // output data of each row
   while($row1 = $result1->fetch_assoc()) {

      $growerid=$row1["id"];
    
   }
 }


$sql91 = "Select distinct scheme_hectares.quantity as hectares,scheme_hectares_products.quantity,scheme_hectares.id,scheme.description,name,products.id as productid from scheme join scheme_hectares on scheme_hectares.schemeid=scheme.id join scheme_hectares_products on scheme_hectares.id=scheme_hectares_products.scheme_hectaresid join products on products.id=scheme_hectares_products.productid join scheme_hectares_growers on scheme_hectares_growers.scheme_hectaresid=scheme_hectares.id where scheme_hectares.seasonid=$seasonid and scheme.description='$description'  order by scheme_hectares.id";
    $result91 = $conn->query($sql91);

     
     if ($result91->num_rows > 0) {
       // output data of each row
       while($row91 = $result91->fetch_assoc()) {

        $productid=$row91["productid"];
        $scheme_quantity=$row91["quantity"]; 



        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        //  $temp=array("description"=>$row["description"],"name"=>$row["name"],"quantity"=>$row["quantity"],"hectares"=>$row["hectares"],"id"=>$row["id"]);
        // array_push($data1,$temp);
             $sql99 = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,loans.created_at,verified, users.username,receipt_number,product_amount,product_total_cost,loans.id as loanid,loans.userid  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid and  processed=0 and loans.growerid=$growerid and loans.productid=$productid order by product_amount limit 1";
             $result99 = $conn->query($sql99);
             if ($result99->num_rows > 0) {
               // output data of each row
               while($row99 = $result99->fetch_assoc()) {

                $product_found=$row99["id"];
                $receiptnumber=$row99["receipt_number"];
                $productid=$row99["productid"];
                $quantity=$row99["quantity"];
                $newquantity=$scheme_quantity+$quantity;
                $loanid=$row99["loanid"];
                $captured_by_id=$row99["userid"];

              
               }
             }

        
    
if ($product_found>0){


$sql = "Select truck_to_grower.id,truck_to_grower.disbursement_trucksid,disbursement.quantity from truck_to_grower join truck_disbursment_sync_active on truck_disbursment_sync_active.disbursement_trucksid=truck_to_grower.disbursement_trucksid join disbursement on disbursement.disbursement_trucksid=truck_disbursment_sync_active.disbursement_trucksid where truck_to_grower.growerid=$growerid and truck_to_grower.productid=$productid  and truck_to_grower.quantity=$quantity and seasonid=$seasonid and truck_to_grower.loanid=$loanid  limit 1";
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


    $store_to_grower=0;
    $storeitemid=0;
    $store_quantity=0;
    $storeid=0;

    $storeid_found=0;



    $sql = "Select * from user_to_store  where loan_userid=$captured_by_id ";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) { 
         
         //$store_to_grower=$row["id"];
         //$storeitemid=$row["storeitemid"];
         $storeid_found=$row["id"];

            
         }

       }


      //  $sql = "Select * from arc_product_grower join arc_products on arc_product_grower.arc_productid=arc_products.id where arc_product_grower.loanid=$loanid ";
      // $result = $conn->query($sql);
       
      //  if ($result->num_rows > 0) {
      //    // output data of each row
      //    while($row = $result->fetch_assoc()) { 
         
      //    $store_to_grower=$row["id"];
      //    $storeitemid=$row["storeitemid"];

            
      //    }

      //  }


       $sql = "Select * from store_items  where productid=$productid and storeid=$storeid_found";
      $result = $conn->query($sql);
       
       if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) { 
         
         $store_quantity=$row["quantity"];
         $storeid=$row["storeid"];
         $storeitemid=$row["id"];


           
         }

       }


       if ($storeid>0) {
          

       if ($quantity>$newquantity) {

        $my_quantity=$quantity-$newquantity;

        // update loan

        $user_sql1 = "update loans set quantity=$newquantity  where id=$loanid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {


               $user_sql1 = "update store_items set quantity=quantity+$my_quantity  where id=$storeitemid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {

                   
                         $insert_sql = "INSERT INTO loan_adjustments(userid,loanid,old_quantity,new_quantity,created_at) VALUES ($userid,$loanid,$quantity,$newquantity,'$created_at')";
                           //$gr = "select * from login";
                           if ($conn->query($insert_sql)===TRUE) {


                            $new_quantity=$store_quantity+$my_quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeitemid,$store_quantity,$new_quantity,'$created_at')";
                                  //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $arc_products_id = $conn->insert_id;


                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$my_quantity,$arc_products_id,'$created_at','GROWER LOAN ADJUSTMENT',$quantity)";
                              
                                    if ($conn->query($user_sql1)===TRUE) {

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

                $temp=array("response"=>$conn->error);
                 array_push($data1,$temp);

           }


    

      }else{


      $my_quantity=$newquantity-$quantity;


      if ($store_quantity>=$my_quantity) {
        

        // update loan

        $user_sql1 = "update loans set quantity=$newquantity  where id=$loanid";
           //$sql = "select * from login";
           if ($conn->query($user_sql1)===TRUE) {


               $user_sql1 = "update store_items set quantity=quantity-$my_quantity  where id=$storeitemid and productid=$productid";
                   //$sql = "select * from login";
                   if ($conn->query($user_sql1)===TRUE) {

                   
                         $insert_sql = "INSERT INTO loan_adjustments(userid,loanid,old_quantity,new_quantity,created_at) VALUES ($userid,$loanid,$quantity,$newquantity,'$created_at')";
                           //$gr = "select * from login";
                           if ($conn->query($insert_sql)===TRUE) {


                            $new_quantity=$store_quantity-$my_quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$storeitemid,$store_quantity,$new_quantity,'$created_at')";
                                  //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $arc_products_id = $conn->insert_id;


                                    $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$my_quantity,$arc_products_id,'$created_at','GROWER LOAN ADJUSTMENT',$quantity)";
                              
                                    if ($conn->query($user_sql1)===TRUE) {

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


            $temp=array("response"=>$conn->error);
             array_push($data1,$temp);


           }


      }else{


        $temp=array("response"=>"Out Of Stock");
        array_push($data1,$temp);

      }


        

      }



       }else{

         $temp=array("response"=>"Loan Not Found");
        array_push($data1,$temp);
       }

   

    // if ($disbursement_trucksid==0) {

    //   $temp=array("response"=>"Truck Not Found");
    //     array_push($data1,$temp);

    // }else if ($truck_to_growerid==0) {
      
    //   $temp=array("response"=>"Grower Truck Not Found");
    //     array_push($data1,$temp);

    // }else{

    //   $temp=array("response"=>"Grower Truck Not Found");
    //     array_push($data1,$temp);


    // }





  }


 // $user_sql1 = "update loan_deduction_point set point=$point ";
 //   //$sql = "select * from login";
 //   if ($conn->query($user_sql1)===TRUE) {

 //    $temp=array("response"=>"success");
 //    array_push($data1,$temp);

     
 //    }



  }

  }
}


echo json_encode($data1);

?>





