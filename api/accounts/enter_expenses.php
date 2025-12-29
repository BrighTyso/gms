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


//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid) &&  isset($data->sub_accountid) && isset($data->description)  && isset($data->currencyid) && isset($data->payment_typeid) && isset($data->amount)  && isset($data->receipt_num) && isset($data->transaction_at) && isset($data->created_at) && isset($data->seasonid)){

  $sub_accountid=$data->sub_accountid;
  $description=$data->description;
  $userid=$data->userid;
  $currencyid=$data->currencyid;
  $payment_typeid=$data->payment_typeid;
  $amount=$data->amount;
  $receipt_num=$data->receipt_num;
   //$trans_date=strtotime($data->transaction_at);
  //$transaction_at=date("Y-m-d",$trans_date); this is for python tkinter
  $transaction_at=substr($data->transaction_at,0,-8);
  $created_at=$data->created_at;
  $seasonid=$data->seasonid;
  $account_branchid=1;

  //$created_at=date("Y-m-d");


  $found=0;


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



 if ($found==0){

  $grower_farm_sql = "INSERT INTO expenses(userid,seasonid,currencyid,sub_accountid,payment_typeid,amount,receipt_num,description,transaction_at,created_at) VALUES ($userid,$seasonid,$currencyid,$sub_accountid,$payment_typeid,'$amount','$receipt_num','$description','$transaction_at','$created_at')";
     //$sql = "select * from login";
     if ($conn->query($grower_farm_sql)===TRUE) {
     
       $last_id = $conn->insert_id;

       $debit_sql = "INSERT INTO expenses_debit(userid,expensesid,sub_accountid,created_at) VALUES ($userid,$last_id,$sub_accountid,'$created_at')";
     //$sql = "select * from login";
     if ($conn->query($debit_sql)===TRUE) {
     
          $credit_sql = "INSERT INTO expenses_credit(userid,expensesid,sub_accountid,created_at) VALUES ($userid,$last_id,$payment_typeid,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($credit_sql)===TRUE) {
         

          $credit_sql = "INSERT INTO transactions(userid,account_branchid,seasonid,currencyid,description,receipt_num,amount,debit_sub_accountsid,credit_sub_accountsid,expensesid,created_at) VALUES ($userid,$account_branchid,$seasonid,$currencyid,'$transaction_description','$receipt_num','$amount',$sub_accountid,$payment_typeid,$last_id,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($credit_sql)===TRUE) {

           $temp=array("response"=>"success");
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

 $temp=array("response"=>"Already Captured");
  array_push($data1,$temp);

}

}else{

  $temp=array("response"=>"Field Empty");
  array_push($data1,$temp);


}








echo json_encode($data1);



?>


