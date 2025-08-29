<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$userid=0;

$response=array();

if (isset($data->userid)){

$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;
$currencyid=$data->currencyid;
//$payment_type=$data->payment_type;
$created_at=$data->created_at;
$interest_amount=0;
$sub_accountid=0;
$found=0;

$note_date=date("Y-m-d");
$due_date=date("Y-m-d");

if($description==""){

$sql = "SELECT
    grower_num,name,surname,phone,growerid,area,province,receipt_number,
    SUM(product_amount * quantity) AS total_product_loan, -- Weighted average price considering mass
    COUNT(*) AS total_records
FROM
    loans -- Replace 'your_table_name' with the actual name of your table
    join growers on growers.id=loans.growerid
    where `processed`=1 and loans.seasonid=$seasonid
GROUP BY
    grower_num
ORDER BY
    grower_num;";
$result_loan = $conn->query($sql);
 
 if ($result_loan->num_rows > 0) {
   // output data of each row
   while($row_loan = $result_loan->fetch_assoc()) {


    $growerid=$row_loan["growerid"];
    $grower_number=$row_loan["grower_num"];
    $name=$row_loan["name"];
    $surname=$row_loan["surname"];
    $phone=$row_loan["phone"];
    $email="";
    $area=$row_loan["area"];
    $province=$row_loan["province"];
    $customerid=0;
    $original_amount=$row_loan["total_product_loan"];
    $receipt_num=$row_loan["receipt_number"];



    $sql = "Select id from accounts_branch";
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




     $sql1 = "Select id from customers  where  name='$grower_number' and growerid=$growerid";
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



   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid and chargeid=1 limit 1 ";

    $result5 = $conn->query($sql15);
     
     if ($result5->num_rows > 0) {
       // output data of each row
       while($row5 = $result5->fetch_assoc()) {

        $interest_amount=$row5["value"];
       
       }
     }


     if ($interest_amount>0) {

         //echo $original_amount."first \n";

         $original_amount+=$original_amount*($interest_amount/100);

        // echo $original_amount."\n";
     }







if ($customerid>0 && $account_receivableid==0) {
    
    $grower_farm_sql = "INSERT INTO accounts_receivable_notes(userid,seasonid,currencyid,customer_id,note_date,due_date,original_amount,outstanding_amount,description,status,created_at) VALUES ($userid,$seasonid,$currencyid,$customerid,'$note_date','$due_date',$original_amount,$original_amount,'$description','Open','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,receivable_note_id,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'$description','$receipt_num',$original_amount,$sub_accountid,$payment_typeid,$last_id,'$created_at')";
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
    // update here
    // ,sub_accountid=$sub_accountid,payment_typeid=$payment_typeid

    if ($customerid>0) {
      
        $grower_farm_sql = "update accounts_receivable_notes set currencyid=$currencyid,amount='$original_amount' where id=$account_receivableid";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {
         
           $grower_farm_sql1 = "update transactions set currencyid=$currencyid,original_amount='$original_amount',receipt_num='$receipt_num',debit_sub_accountsid=$sub_accountid,credit_sub_accountsid=$payment_typeid where id=$account_receivableid";
         //$sql = "select * from login";
             if ($conn->query($grower_farm_sql1)===TRUE) {

               $temp=array("response"=>"success");
                array_push($response,$temp);
            }

             }else{

                  $temp=array("response"=>$conn->error);
                  array_push($response,$temp);
            }


        }
    // code...
    }
   }
 }


}else{


$sql = "SELECT
    grower_num,name,surname,phone,growerid,
    SUM(product_amount * quantity) AS total_product_loan, -- Weighted average price considering mass
    COUNT(*) AS total_records
FROM
    loans -- Replace 'your_table_name' with the actual name of your table
    join growers on growers.id=loans.growerid
    where `processed`=1 and loans.seasonid=$seasonid
GROUP BY
    grower_num
ORDER BY
    grower_num;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $expenseid=$row["id"];
    $payment_type="";
   


     $sql1 = "Select sub_accounts.description,expenses.id from expenses join sub_accounts on expenses.payment_typeid=sub_accounts.id where expenses.id=$expenseid";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row1 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $payment_type=$row1["description"];
        
       }
     }


    $temp=array("expense_description"=>$row["expense_description"],"amount"=>$row["amount"],"receipt_num"=>$row["receipt_num"],"transaction_at"=>$row["transaction_at"],"description"=>$row["description"],"id"=>$row["id"],"currency_name"=>$row["currency_name"],"payment_type"=>$payment_type);
    array_push($response,$temp);
    
   }
 }

}


}



echo json_encode($response);

?>





