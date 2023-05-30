<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
#require_once("data.php");

#$post=new ApiData($conn);

$data = json_decode(file_get_contents("php://input"));

$username=$data->username;
$hash=md5($data->hash);

$data1=array();
// get grower locations

if ($username!="" && $hash!="") {
	

$sql = "Select selling_points.id as selling_pointid,users.name,surname,username,users.id,rightsid,company_userid,company_users_to_selling_points.companyid,selling_points.name as selling_point_name,company_users_to_selling_points.company_to_selling_pointid from users join company_users_to_selling_points on company_users_to_selling_points.company_userid=users.id join company_to_selling_point on company_to_selling_point.id=company_users_to_selling_points.company_to_selling_pointid join selling_points on selling_points.id=company_to_selling_point.selling_pointid where hash='$hash' and  username='$username' and  users.active=1 and company_users_to_selling_points.active=1";

$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

  
       
      $id=$row['companyid'];

      $sql1 = "Select users.name,surname,username from users where id=$id ";

      $result1 = $conn->query($sql1);
       
             if ($result1->num_rows > 0) {
               // output data of each row
               while($row1 = $result1->fetch_assoc()) {
                // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                 $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["companyid"],"rights"=>$row["rightsid"],"company_userid"=>$row["company_userid"],"selling_point_name"=>$row["selling_point_name"],"company_name"=>$row1["name"],"company_to_selling_pointid"=>$row["company_to_selling_pointid"],"company_username"=>$row1["username"]);
                array_push($data1,$temp);
                
             }
         }
    
   }
 }


}

 echo json_encode($data1);

?>



