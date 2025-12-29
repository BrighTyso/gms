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
  $currencyid=1;
  $receipt_num="";
  $grower_num=$data->grower_num;
  $ref=$data->ref;
  $mass=$data->mass;
  $bales=$data->bales;
  $loan_found=0;
  $growerid=0;
  $loan_payment_found=0;
  $original_amount=$data->amount;

  $found=0;
  $name="";
  $surname="";
  $phone="";
  $email="";
  $area="";
  $province="";
  $customerid=0;
  //$original_amount=0;
  //$receipt_num=$row_loan["receipt_number"];

  // $date = new DateTime();
  // $datetimes=$date->format('H:i:s');

  $sql = "Select * from growers where grower_num='$grower_num' or grower_num like '%$grower_num' limit 1";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

      $growerid=$row["id"];
      $name=$row["name"];
      $surname=$row["surname"];
      $phone=$row["phone"];
      $email="";
      $area=$row["area"];
      $province=$row["province"];


      
     }

   }




    $sql = "Select id from accounts_branch limit 1";
    $result = $conn->query($sql);
     
     $branch_id_count=$result->num_rows;

     if ($result->num_rows > 0) {
       // output data of each row
       while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
        // array_push($response,$temp);

        $account_branchid=$row['id'];

       }
     }




     $sql1 = "Select id from customers  where  name='$name' and growerid=$growerid";
     $result = $conn->query($sql1);
   
    if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

         $found=$row['id'];
         $customerid=$row['id'];
     
         }

       }






 if ($found==0){

  $grower_farm_sql = "INSERT INTO customers(userid,seasonid,name,email,phone,address,created_at,growerid,contact_person) VALUES ($userid,$seasonid,'$name','$email','$phone','$area','$created_at',$growerid,'')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;
       $customerid= $conn->insert_id;

     }else{

    
     }


  if ($customerid>0 && $account_receivableid==0) {
    
    $grower_farm_sql = "INSERT INTO accounts_receivable_notes(userid,seasonid,currencyid,customer_id,note_date,due_date,original_amount,outstanding_amount,description,status,created_at) VALUES ($userid,$seasonid,$currencyid,$customerid,'$created_at','$created_at',0,0,'$description','Open','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
      
     }

  }


}



$sql = "Select main_accounts.description as main_account,main_accounts.id,balancing_side.description as balance_side,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id from main_accounts join sub_accounts on main_accounts.id=sub_accounts.main_accountid join main_account_balancing_side on main_account_balancing_side.main_accountid=main_accounts.id join balancing_side on main_account_balancing_side.balancing_sideid=balancing_side.id where sub_accounts.description='Cash In Bank' order by main_accounts.id limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
    // array_push($response,$temp);

    $payment_typeid=$row['sub_account_id'];

   }
 }





 $sql = "Select main_accounts.description as main_account,main_accounts.id,balancing_side.description as balance_side,sub_accounts.description as sub_acc , sub_accounts.id as sub_account_id from main_accounts join sub_accounts on main_accounts.id=sub_accounts.main_accountid join main_account_balancing_side on main_account_balancing_side.main_accountid=main_accounts.id join balancing_side on main_account_balancing_side.balancing_sideid=balancing_side.id where sub_accounts.description='Accounts Receivable' order by main_accounts.id limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
    // $temp=array("main_account"=>$row["main_account"],"sub_acc"=>$row["sub_acc"],"balance_side"=>$row["balance_side"],"main_account_id"=>$row["id"],"sub_account_id"=>$row["sub_account_id"]);
    // array_push($response,$temp);

    $sub_accountid=$row['sub_account_id'];

   }
 }



  $account_receivableid=0;

  $sql1 = "Select id from accounts_receivable_notes  where  customer_id=$customerid and seasonid=$seasonid";
   $result = $conn->query($sql1);
 
  if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {

   $account_receivableid=$row['id'];
   
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




     if ($loan_found==0 && $customerid>0 && $growerid>0) {


        $user_sql = "INSERT INTO loan_payments(userid,seasonid,growerid,amount,mass,created_at,description,receipt_number,reference_num,payment_date,bales) VALUES ($userid,$seasonid,$growerid,'$amount','$mass','$created_at','Recovery','R1111','$ref','$payment_date',$bales)";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {

          $last_id = $conn->insert_id;

          if ($loan_payment_found==0) {
            
         $user_sql = "INSERT INTO loan_payment_total(userid,seasonid,growerid,amount,mass,bales,created_at) VALUES ($userid,$seasonid,$growerid,'$amount','$mass',$bales,'$created_at')";
           //$sql = "select * from login";
               if ($conn->query($user_sql)===TRUE) {


                $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,receivable_note_id,receivable_note_paymentsid,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'Recovery','$receipt_num',$original_amount,$payment_typeid,$sub_accountid,$account_receivableid,$last_id,'$created_at')";
                 //$sql = "select * from login";
                 if ($conn->query($credit_sql)===TRUE) {


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

              $user_sql2 = "update loan_payment_total set amount=amount+$amount , mass=mass+$mass , bales=bales+$bales  where growerid = $growerid and seasonid=$seasonid";
             //$sql = "select * from login";
             if ($conn->query($user_sql2)===TRUE) {



              $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,receivable_note_id,receivable_note_paymentsid,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'Recovery','$receipt_num',$original_amount,$payment_typeid,$sub_accountid,$account_receivableid,$last_id,'$created_at')";
                 //$sql = "select * from login";
                 if ($conn->query($credit_sql)===TRUE) {


                    $temp=array("response"=>"success update");
                     array_push($response,$temp);
                    
                  }else{
                    $temp=array("response"=>$conn->error);
                    array_push($response,$temp);
                  }

             
                

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

     }else{

    $temp=array("response"=>"Missing Grower Account");
    array_push($response,$temp);

     }



}else{
   $temp=array("response"=>"field empty");
    array_push($response,$temp);

 
}


 echo json_encode($response);
?>





