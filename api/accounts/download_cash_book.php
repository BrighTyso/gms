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
    transactions t
WHERE
    t.debit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Hand' or sub_accounts.description = 'Cash in Bank'  limit 1)
    OR t.credit_sub_accountsid = (SELECT sub_accounts.id FROM main_accounts join sub_accounts on sub_accounts.main_accountid=main_accounts.id WHERE sub_accounts.description = 'Cash in Bank' or sub_accounts.description = 'Cash in Hand' limit 1)
ORDER BY
    t.created_at";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";


    $temp=array("date"=>$row["dates"],"cash_in"=>$row["cash_in"],"cash_out"=>$row["cash_out"],"description"=>$row["descriptions"]);
    array_push($response,$temp);
    
   }
 }


}else{




}

}

echo json_encode($response);

?>





