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
//$description=$data->description;
$start_date=$data->start_date;
$end_date=$data->end_date;

if($description==""){

$sql = "SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Revenue' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Revenue'

group by sub_accounts.description

UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Cost Of Merchandise Sold' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Cost Of Merchandise Sold'

group by sub_accounts.description


UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Expenses' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Expenses'

group by sub_accounts.description

UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Expenses' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Expenses'

group by sub_accounts.description";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $temp=array("main"=>$row["main"],"description"=>$row["description"],"total_amount"=>$row["total_amount"]);
    array_push($response,$temp);
    
   }
 }


}else{




}

}

echo json_encode($response);

?>





