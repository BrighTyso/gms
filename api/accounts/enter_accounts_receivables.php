<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");
//require "validate.php";



$data = json_decode(file_get_contents("php://input"));

$userid="";
$created_at="";
$userid=0;
$sub_accountid=0;
$name="";
$serial_number="";
$location="";
$description="";

$data1=array();


if (isset($data->userid) &&  isset($data->seasonid) && isset($data->currencyid)  && isset($data->customer_id) && isset($data->note_date) && isset($data->due_date)){

  //$created_at=date("Y-m-d");
  $found=0;

  $userid=$data->userid;
  $seasonid=$data->userid;
  $currencyid=$data->currencyid;
  $customer_id=$data->customer_id;
  $note_date=$data->note_date;
  $due_date=$data->due_date;
  $original_amount=$data->original_amount;
  $outstanding_amount=$data->outstanding_amount;
  $description=$data->description;
  $status=$data->status;
  $created_at=$data->created_at;
  $payment_typeid=$data->payment_typeid;


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




 if ($found==0){

  $grower_farm_sql = "INSERT INTO accounts_receivable_notes(userid,seasonid,currencyid,customer_id,note_date,due_date,original_amount,outstanding_amount,description,status,created_at) VALUES ($userid,$seasonid,$currencyid,$customer_id,'$note_date','$due_date',$original_amount,$original_amount,'$description','$status','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,receivable_note_id,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'$description','$receipt_num','$amount',$sub_accountid,$payment_typeid,$last_id,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($credit_sql)===TRUE) {

           $temp=array("response"=>"success");
            array_push($data1,$temp);
            
          }

     }

}else{

 $temp=array("response"=>"Already Captured");
  array_push($data1,$temp);

}

}else{

  $temp=array("response"=>"Field Empty");
  array_push($data1,$temp);

}



echo json_encode($data1);



?>


