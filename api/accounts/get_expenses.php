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

if (isset($data->userid) && isset($data->description) ){

$userid=$data->userid;
$description=$data->description;

if($description==""){

$sql = "Select distinct amount,receipt_num,expenses.description as expense_description,transaction_at,created_at,sub_accounts.description,expenses.id,currency.description as currency_name from expenses join sub_accounts on expenses.sub_accountid=sub_accounts.id join currency on currency.id=expenses.currencyid";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {


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

    $temp=array("expense_description"=>$row["expense_description"],"amount"=>$row["amount"],"receipt_num"=>$row["receipt_num"],"transaction_at"=>$row["transaction_at"],"description"=>$row["description"],"id"=>$row["id"],"currency_name"=>$row["currency_name"] ,"payment_type"=>$payment_type);
    array_push($response,$temp);
    
   }
 }


}else{


$sql = "Select distinct amount,receipt_num,expenses.description as expense_description,transaction_at,created_at,sub_accounts.description,expenses.id,currency.description as currency_name from expenses join sub_accounts on expenses.sub_accountid=sub_accounts.id join currency on currency.id=expenses.currencyid where expenses.description='$description'  ";
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





