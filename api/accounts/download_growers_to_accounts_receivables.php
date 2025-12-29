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




// $company_details_data=array();
// $sql13 = "Select * from company_details_and_contact limit 1";

//     $result3 = $conn->query($sql13);
     
//      if ($result3->num_rows > 0) {
//        // output data of each row
//        while($row3 = $result3->fetch_assoc()) {

//         $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

//           array_push($company_details_data,$loans);
       
//        }
//      }



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
    $found=0;



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




    $sql1 = "Select customers.id from customers join growers on growers.id=customers.growerid  where  grower_num='$grower_number' and growerid=$growerid limit 1";
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

    $sql1 = "Select accounts_receivable_notes.id from accounts_receivable_notes  where  customer_id=$customerid and seasonid=$seasonid limit 1";
     $result = $conn->query($sql1);
   
    if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {

     $account_receivableid=$row['id'];
     
     }

   }


$transaction_description="";
//check farm
$sql = "Select sub_accounts.description,sub_accounts.id from sub_accounts where id=$sub_accountid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
      $transaction_description=$row['description'];
    
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

       $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,receivable_note_id,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'$transaction_description','$receipt_num',$original_amount,$sub_accountid,$payment_typeid,$last_id,'$created_at')";
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
      
        $grower_farm_sql = "update accounts_receivable_notes set currencyid=$currencyid,original_amount=$original_amount where id=$account_receivableid";
         //$sql = "select * from login";
         if ($conn->query($grower_farm_sql)===TRUE) {
         
           $grower_farm_sql1 = "update transactions set currencyid=$currencyid,amount=$original_amount,receipt_num='$receipt_num',debit_sub_accountsid=$sub_accountid,credit_sub_accountsid=$payment_typeid,description='$transaction_description' where receivable_note_id=$account_receivableid";
         //$sql = "select * from login";
             if ($conn->query($grower_farm_sql1)===TRUE) {

               $temp=array("response"=>"successfully updated","grower_num"=>$grower_number);
                array_push($response,$temp);
            }else{

              $temp=array("response"=>$conn->error,"grower_num"=>$grower_number);
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


}


}



echo json_encode($response);

?>





