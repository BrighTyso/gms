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
    distinct 
    t.id,
    t.created_at AS dates,
    t.description AS descriptions,
    CASE
        WHEN t.debit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Hand' OR sub_accounts.description = 'Cash in Bank' limit 1) THEN t.amount
        ELSE 0
    END AS cash_in,
    CASE
        WHEN t.credit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Hand' OR sub_accounts.description = 'Cash in Bank' limit 1) THEN t.amount
        ELSE 0
    END AS cash_out
FROM
    transactions as t JOIN  sub_accounts on sub_accounts.id=t.credit_sub_accountsid or sub_accounts.id=t.debit_sub_accountsid join main_accounts on sub_accounts.main_accountid=main_accounts.id
WHERE
    (t.debit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Hand' or sub_accounts.description = 'Cash in Bank'  limit 1)
    OR t.credit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Bank' or sub_accounts.description = 'Cash in Hand' limit 1))

    and  (t.created_at between '$startDate' and '$endDate')
    
ORDER BY
    t.created_at asc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $temp=array("id"=>$row["id"],"date"=>$row["dates"],"cash_in"=>$row["cash_in"],"cash_out"=>$row["cash_out"],"description"=>$row["descriptions"],"company_data"=>$company_details_data);
    array_push($response,$temp);
    
   }
 }


}

}

echo json_encode($response);

?>





