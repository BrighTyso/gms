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

$company_details_data=array();
$sql13 = "Select * from company_details_and_contact limit 1";

    $result3 = $conn->query($sql13);
     
     if ($result3->num_rows > 0) {
       // output data of each row
       while($row3 = $result3->fetch_assoc()) {

        $loans=array("name"=>$row3["company_name"],"address"=>$row3["address"],"phone_1"=>$row3["phone_1"],"phone_2"=>$row3["phone_2"],"phone_3"=>$row3["phone_3"],"email"=>$row3["email"]);

          array_push($company_details_data,$loans);
       
       }
     }


if (isset($data->userid)  && isset($data->startDate) && isset($data->endDate)){

$userid=$data->userid;
$description="";
$startDate=substr($data->startDate,0,-8);
$endDate=substr($data->endDate,0,-8);


if($description==""){

$sql = "SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Revenue' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Revenue' and  (T.created_at between '$startDate' and '$endDate')

group by sub_accounts.description

UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Cost Of Merchandise Sold' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Cost Of Merchandise Sold' and  (T.created_at between '$startDate' and '$endDate')

group by sub_accounts.description


UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Expenses' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Expenses' and  (T.created_at between '$startDate' and '$endDate')

group by sub_accounts.description

UNION all

SELECT
  main_accounts.description as main,sub_accounts.description,
  SUM(CASE
    WHEN main_accounts.description = 'Expenses' THEN T.amount
    ELSE 0
  END) AS total_amount
FROM transactions AS T
JOIN  sub_accounts on sub_accounts.id=T.credit_sub_accountsid or sub_accounts.id=T.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id WHERE main_accounts.description = 'Expenses' and  (T.created_at between '$startDate' and '$endDate')

group by sub_accounts.description";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $temp=array("main"=>$row["main"],"description"=>$row["description"],"total_amount"=>$row["total_amount"],"company_data"=>$company_details_data);
    array_push($response,$temp);
    
   }
 }


}else{




}

}

echo json_encode($response);

?>





