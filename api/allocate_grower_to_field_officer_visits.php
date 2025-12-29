
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";

$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$created_at=$data->created_at;
$grower_found=0;

$data1=array();

$sql11 = "Select * from visits where description!='Grower Requirements Loan' and seasonid=$seasonid";
  $result11 = $conn->query($sql11);
   
   if ($result11->num_rows > 0) {
     // output data of each row
     while($row11 = $result11->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      $growerid= $row11["growerid"];
      $field_officerid=$row11["userid"];

      

	$sql = "Select * from grower_field_officer where growerid=$growerid and seasonid=$seasonid";
	  $result = $conn->query($sql);
	   
	   if ($result->num_rows > 0) {
	     // output data of each row
	     while($row = $result->fetch_assoc()) {
	      // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
	      $grower_found=$row["id"];
	      
	     }

	  }


   if ($grower_found==0 && $growerid>0) {

     $user_sql = "INSERT INTO grower_field_officer(userid,seasonid,growerid,field_officerid,created_at) VALUES ($userid,$seasonid,$growerid,$userid,'$created_at')";
                   //$sql = "select * from login";
       if ($conn->query($user_sql)===TRUE) {
       		$temp=array("response"=>"success");
            array_push($data1,$temp);
       }else{
          
       }
       
   }else{

   	 $user_sql1 = "update grower_field_officer set field_officerid=$field_officerid where growerid=$growerid and seasonid=$seasonid";
     if ($conn->query($user_sql1)===TRUE) {

          $temp=array("response"=>"updated");
          array_push($data1,$temp);
      }

   }



 }

}


   echo json_encode($data1);

?>