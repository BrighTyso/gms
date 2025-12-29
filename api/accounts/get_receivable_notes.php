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
$seasonid=$data->seasonid;
$description=$data->description;

if($description==""){

$sql = "Select distinct accounts_receivable_notes.id,accounts_receivable_notes.seasonid,growers.name,growers.surname,growers.grower_num,currency.description as currency_name,original_amount,note_date,due_date,accounts_receivable_notes.created_at,accounts_receivable_notes.description from accounts_receivable_notes  join customers on customers.id=accounts_receivable_notes.customer_id join growers on customers.growerid=growers.id join currency on currency.id=accounts_receivable_notes.currencyid where accounts_receivable_notes.seasonid=$seasonid limit 20";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   

    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"currency_name"=>$row["currency_name"],"original_amount"=>$row["original_amount"],"id"=>$row["id"],"note_date"=>$row["note_date"] ,"created_at"=>$row["created_at"],"description"=>$row["description"],"due_date"=>$row["due_date"],"seasonid"=>$row["seasonid"]);
    array_push($response,$temp);

    
   }
 }


}else{


$sql = "Select distinct accounts_receivable_notes.id,accounts_receivable_notes.seasonid,growers.name,growers.surname,growers.grower_num,currency.description as currency_name,original_amount,note_date,due_date,accounts_receivable_notes.created_at,accounts_receivable_notes.description from accounts_receivable_notes  join customers on customers.id=accounts_receivable_notes.customer_id join growers on customers.growerid=growers.id join currency on currency.id=accounts_receivable_notes.currencyid where (growers.grower_num='$description' or growers.name='$description' or growers.surname='$description') and accounts_receivable_notes.seasonid=$seasonid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $temp=array("name"=>$row["name"],"surname"=>$row["surname"],"grower_num"=>$row["grower_num"],"currency_name"=>$row["currency_name"],"original_amount"=>$row["original_amount"],"id"=>$row["id"],"note_date"=>$row["note_date"] ,"created_at"=>$row["created_at"],"description"=>$row["description"],"due_date"=>$row["due_date"],"seasonid"=>$row["seasonid"]);
    array_push($response,$temp);
    
   }
 }

}


}



echo json_encode($response);

?>





