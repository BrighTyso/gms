<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";


$data = json_decode(file_get_contents("php://input"));

$data1=array();


if (isset($data->userid)){

$userid=$data->userid;
$seasonid=$data->seasonid;
$grower_num=$data->grower_num;
$growerid=0;
$loan_credit_note_found=0;


$sql = "Select * from growers where grower_num='$grower_num';";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
   
   $growerid=$row["id"];
  
    
   }

 }

 $sql = "Select * from working_capital_total where  (seasonid=$seasonid  and growerid=$growerid)";
  $result = $conn->query($sql);
   
   if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
     #$verifyLoan=1;
      $loan_credit_note_found=$row["id"];
      
     }
   }



if ($growerid>0 && $loan_credit_note_found>0) {
 

  $user_sql1 = "DELETE FROM working_capital_total where seasonid=$seasonid and growerid=$growerid";
   //$sql = "select * from login";
   if ($conn->query($user_sql1)===TRUE) {

            $user_sql1 = "DELETE FROM working_capital where  seasonid=$seasonid and growerid=$growerid";
         
            if ($conn->query($user_sql1)===TRUE) {

              $temp=array("response"=>"success");
              array_push($data1,$temp);
                
            }else{
              $temp=array("response"=>$conn->error);
              array_push($data1,$temp);
            }
          
      }else{
        $temp=array("response"=>$conn->error);
        array_push($data1,$temp);
      }
   
  }

}



echo json_encode($data1);

?>





