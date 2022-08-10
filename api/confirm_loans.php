<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$productid=0;
$quantity=0;
$growerid=0;
$userid=0;
$created_at="";
$seasonid=0;
$last_id=0;
$response=array();


$loanid=0;
$dbseasonid=0;



if (isset($data->productid) && isset($data->quantity)  && isset($data->growerid) && isset($data->userid) && isset($data->seasonid) && isset($data->created_at)){

$productid=$data->productid;
$quantity=$data->quantity;
$growerid=$data->growerid;
$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;









$sql = "SELECT loans.id,loans.seasonid FROM loans where loans.productid=$productid and loans.quantity=$quantity and loans.seasonid=$seasonid and loans.growerid=$growerid limit 1";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
   
    $loanid=$row['id'];
    $dbseasonid=['seasonid'];
    
   }
 }


if ($loanid!=0 && $dbseasonid!=0) {

  $insert_sql = "INSERT INTO confirmed_loans(loansid,seasonid) VALUES ($loanid,$dbseasonid)";
   //$sql = "select * from login";
   if ($conn->query($insert_sql)===TRUE) {
   
     $last_id = $conn->insert_id;

     if ($last_id==0) {
       // code...
      
      $temp=array("response"=>$conn->error);
      array_push($response,$temp);

     }else{

           $sql = "INSERT INTO confirm_user(userid,confirmid,created_at) VALUES ($userid,$last_id,'$created_at')";
         //$sql = "select * from login";
         if ($conn->query($sql)===TRUE) {
          
           $temp=array("response"=>"success");
           array_push($response,$temp);

         }else{

          $temp=array("response"=>$conn->error);
          array_push($response,$temp);

        
         }
           

     }



   }else{
   

    $temp=array("response"=>$conn->error);
    array_push($response,$temp);

   }


}else{

$temp=array("response"=>"Not found");
 array_push($response,$temp);

}



}else{

  $temp=array("response"=>"empty");
  array_push($response,$temp);

	
}


echo json_encode($response);

?>





