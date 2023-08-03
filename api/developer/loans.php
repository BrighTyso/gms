<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

//require "validate.php";


require_once("../conn.php");
//require "validate.php";

require "../dataSource.php";


$company_code=new CompanyCode();

$warehouse_code=new CompanyWarehouseCode();

$product_code=new ProductCode();


$data = json_decode(file_get_contents("php://input"));


$data1=array();

$productid=0;
$seasonid=0;
$growerid=0;
$storeid=0;
$userid=0;
$receipt_number="";
$contracted_to=0;
$old_quantity=0;
$quantity_Enough=0;
$previous_growerid=0;
$active_grower_found=0;
$created_at=date("Y-m-d");


$lat="";
$long="";


$sqliteid=0;
$verifyLoan=0;
$verifyHectares=0;
$disbursement_trucksid=0;
$disbursementid=0;
$ready=0;





//http://192.168.1.190/gms/api/enter_loans.php?userid=1&product=sadza&quantity=1&latitude=13.2222&longitude=3.33376&created_at=23-09-2022&description=12333&seasonid=1&sqliteid=1

if (isset($data->grower_num)  && isset($data->product_code) && isset($data->warehouse_code)  && isset($data->hectares) && isset($data->quantity) && isset($data->userid) && isset($data->receiptnumber)){




$company=$data->userid;
$quantity=$data->quantity;
$description=$data->grower_num;
$product=$data->product_code;
$warehouse=$data->warehouse_code;
$hectares=$data->hectares;
$receipt_number=$data->receiptnumber;



$p_code=$product_code->encryptor("decrypt",$product);

$c_code=$company_code->encryptor("decrypt",$company);

$w_code=$warehouse_code->encryptor("decrypt",$warehouse);




sleep(3);

$sql = "Select * from developer where company_code='$data->userid' and warehouse_code='$warehouse' and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $userid=$row["userid"];


    
   }

 }




$sql = "Select * from company_store where  id=$w_code and userid=$userid and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $storeid=$row["storeid"];

    
   }

 }




$sql = "Select * from developer_product_codes where  product_code='$product'  and active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $productid=$row["productid"];


    
   }

 }





$sql = "Select status from regulator_sync_status where status=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $statusid=$row["status"];



  
   }

 }




 $sql = "Select * from seasons where active=1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
   $seasonid=$row["id"];
   
    
   }

 }







 if ($statusid>0 && $seasonid>0 && $storeid>0 && $userid>0) {



$sql = "Select * from growers where grower_num='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   
   $growerid=$row["id"];

    
   
   }

 }


//  $sql = "Select * from contracted_to where growerid=$growerid and seasonid=$seasonid";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) { 
   
//    $contracted_to=$row["id"];
    
    
//    }

//  }

 // get selected  products id


$product_sql = "Select * from products  where id=$productid";
$result = $conn->query($product_sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

    // product id
  
   $productid=$row["id"];

    
   }

 }

//check if loan is there


 $sql = "Select * from loans where  (userid=$userid and loans.seasonid=$seasonid  and receipt_number='$receipt_number') ";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

     #$verifyLoan=1;
      $previous_growerid=$row["growerid"];

      
     }
   }



    $sql = "Select * from active_growers where growerid=$growerid and seasonid=$seasonid";
    $result = $conn->query($sql);
 
     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
       
       $active_grower_found=$row["id"];
      
        
       }

     }
 


 $sql = "Select * from loans where (growerid=$growerid) and (loans.seasonid=$seasonid and productid=$productid and receipt_number='$receipt_number' and userid=$userid)";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyLoan=1;

  
   
   }
 }



//checks if hectares are found
  $sql1 = "Select * from contracted_hectares where contracted_hectares.seasonid=$seasonid and growerid=$growerid";
$result = $conn->query($sql1);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   $verifyHectares=1;
   
    
   }
 }


$sql2 = "Select store.id,quantity,store_items.id as storeid from store join store_items on store.id=store_items.storeid where store.id=$storeid and productid=$productid and quantity>0 and quantity>=$quantity";
$result = $conn->query($sql2);
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) { 
   

   $storeid=$row["id"];
   $quantity_Enough=$row["storeid"];
   $old_quantity=$row["quantity"];

    
   }

 }


// then insert loan

  if (($productid>0  && $growerid>0 && $verifyLoan==0 && $productid>0 && $storeid>0) && ($previous_growerid==$growerid || $previous_growerid==0)) {


    
     $insert_sql = "INSERT INTO loans(userid,growerid,productid,seasonid,quantity,latitude,longitude,hectares,verified,created_at,receipt_number) VALUES ($userid,$growerid,$productid,$seasonid,$quantity,'$lat','$long','$hectares',1,'$created_at','$receipt_number')";
         //$gr = "select * from login";
         if ($conn->query($insert_sql)===TRUE) {
         
           $loan_id = $conn->insert_id;

         if ($verifyHectares==0) {

         $insert_sql = "INSERT INTO contracted_hectares(userid,growerid,seasonid,hectares,created) VALUES ($userid,$growerid,$seasonid,'$hectares','$created_at')";
         //$gr = "select * from login";
                if ($conn->query($insert_sql)===TRUE) {

                   $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {

                            $last_id = $conn->insert_id;
                          
                            $new_quantity=$old_quantity-$quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                                  //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                $arc_products_id = $conn->insert_id;

                                $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,loanid) VALUES ($arc_products_id,$loan_id)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                        $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                                  
                                      if ($conn->query($user_sql1)===TRUE) {


                                         if ($active_grower_found==0) {

                                      $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                        //$sql = "select * from login";
                                             if ($conn->query($user_sql)===TRUE) {

                                              $temp=array("response"=>"success","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
                                             array_push($data1,$temp);

                                             }
                                          }else{
                                              $temp=array("response"=>"success","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
                                             array_push($data1,$temp);
                                          }


                                         }
                                   }

                             }



                        
                         }

                }

          }else{

                    $user_sql1 = "update store_items set quantity=quantity-$quantity  where storeid=$storeid and productid=$productid";
                         //$sql = "select * from login";
                         if ($conn->query($user_sql1)===TRUE) {

                            $last_id = $conn->insert_id;
                          
                            $new_quantity=$old_quantity-$quantity;


                            $user_sql2 = "INSERT INTO arc_products(userid,storeitemid,old_quantity,new_quantity,created_at) VALUES ($userid,$quantity_Enough,$old_quantity,$new_quantity,'$created_at')";
                           //$sql = "select * from login";
                               if ($conn->query($user_sql2)===TRUE) {

                                     $last_id = $conn->insert_id;

                                      $user_sql2 = "INSERT INTO arc_product_grower(arc_productid,loanid) VALUES ($last_id,$loan_id)";
                                        //$sql = "select * from login";
                                       if ($conn->query($user_sql2)===TRUE) {

                                       // $last_id = $conn->insert_id;

                                        $user_sql1 = "INSERT INTO arc_store_items(userid,storeid,productid,quantity,arc_productid,created_at,description,quantity_balance) VALUES ($userid,$storeid,$productid,$quantity,$arc_products_id,'$created_at','GROWER LOAN',$quantity)";
                                  
                                      if ($conn->query($user_sql1)===TRUE) {

                                        if ($active_grower_found==0) {

                                      $user_sql = "INSERT INTO active_growers(userid,growerid,seasonid) VALUES ($userid,$growerid,$seasonid)";
                                        //$sql = "select * from login";
                                             if ($conn->query($user_sql)===TRUE) {

                                              $temp=array("response"=>"success","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
                                             array_push($data1,$temp);

                                             }
                                          }else{
                                              $temp=array("response"=>"success","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
                                             array_push($data1,$temp);
                                          }

                                         

                                         }

                                    }

                             }



                         }

          }

         }else{

          $temp=array("response"=>"failed","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
            array_push($data1,$temp);

        }

  }else{

     if ($previous_growerid!=$growerid) {

          $temp=array("response"=>"Receipt Captured for another Grower","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
          array_push($data1,$temp);

        }elseif ($productid==0) {

       $temp=array("response"=>"Product Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($userid==0 && $storeid==0){

      $temp=array("response"=>"warehouse/Userid Not Found or Not Matching","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }
    elseif ($growerid==0) {

       $temp=array("response"=>"Grower Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($verifyLoan==1){

      $temp=array("response"=>"Input Already Captured For Grower","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($storeid==0){

      $temp=array("response"=>"Store Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($verifyLoan==1){

       $temp=array("response"=>"Loan Already Captured","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }

  }

  


   }else{

    if ($previous_growerid!=$growerid) {

          $temp=array("response"=>"Receipt Captured for another Grower","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
          array_push($data1,$temp);

        }elseif ($productid==0) {

       $temp=array("response"=>"Product Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($userid==0 && $storeid==0){

      $temp=array("response"=>"warehouse/Userid Not Found or Not Matching","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }
    elseif ($growerid==0) {

       $temp=array("response"=>"Grower Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($verifyLoan==1){

      $temp=array("response"=>"Input Already Captured For Grower","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($storeid==0){

      $temp=array("response"=>"Store Not Found","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }elseif($verifyLoan==1){

       $temp=array("response"=>"Loan Already Captured","grower"=>$description,"product_code"=>$product,"warehouse_code"=>$warehouse,"receipt_number"=>$receipt_number);
      array_push($data1,$temp);

    }



   }

 }





echo json_encode($data1);


?>





