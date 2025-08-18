<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=$data->productid;


$chairman="";
$fieldOfficer="";
$area_manager="";
$growerid=0;

$data1=array();
// get grower locations

if ($userid!="") {
  


  $sql = "SELECT
    users.username AS field_officer,

    -- Participants Section
    SUM(CASE WHEN grower_field_loans.hectares = 0.5 THEN 1 ELSE 0 END) AS q_Issued_0_5_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 1.0 THEN 1 ELSE 0 END) AS q_Issued_1_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 1.5 THEN 1 ELSE 0 END) AS q_Issued_1_5_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 2.0 THEN 1 ELSE 0 END) AS q_Issued_2_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 2.5 THEN 1 ELSE 0 END) AS q_Issued_2_5_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 3.0 THEN 1 ELSE 0 END) AS q_Issued_3_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 3.5 THEN 1 ELSE 0 END) AS q_Issued_3_5_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 4 THEN 1 ELSE 0 END) AS q_Issued_4_hac,
    SUM(CASE WHEN grower_field_loans.hectares = 5 THEN 1 ELSE 0 END) AS q_Issued_5_hac,
    COUNT(*) AS Total_participants, -- Counts all records for the field officer

    -- Equivalent Hecterage Section
    SUM(CASE WHEN grower_field_loans.hectares = 0.5 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_0_5_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 1.0 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_1_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 1.5 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_1_5_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 2.0 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_2_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 2.5 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_2_5_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 3.0 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_3_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 3.5 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_3_5_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 4 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_4_hac_,
    SUM(CASE WHEN grower_field_loans.hectares = 5 THEN grower_field_loans.hectares ELSE 0 END) AS Issued_5_hac_,
    SUM(grower_field_loans.hectares) AS Total_hac -- Sums all hectares for the field officer

FROM
    grower_field_loans
JOIN
    users ON users.id = grower_field_loans.userid
-- Note: products table is not joined as the image summary is only by field officer, not by product.
-- If you need product details, we'll need to adjust the GROUP BY clause.
GROUP BY
    users.username
ORDER BY
    users.username;";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   //echo $row["field_officer"];
   $temp=array("field_officer"=>$row["field_officer"],"q_Issued_0_5_hac"=>$row["q_Issued_0_5_hac"],"q_Issued_1_hac"=>$row["q_Issued_1_hac"],"q_Issued_1_5_hac"=>$row["q_Issued_1_5_hac"],"q_Issued_2_hac"=>$row["q_Issued_2_hac"],"q_Issued_2_5_hac"=>$row["q_Issued_2_5_hac"],"q_Issued_3_hac"=>$row["q_Issued_3_hac"],"q_Issued_3_5_hac"=>$row["q_Issued_3_5_hac"],"q_Issued_4_hac"=>$row["q_Issued_4_hac"],"q_Issued_5_hac"=>$row["q_Issued_5_hac"],"Total_participants"=>$row["Total_participants"],"Issued_0_5_hac_"=>$row["Issued_0_5_hac_"],"Issued_1_hac_"=>$row["Issued_1_hac_"],"Issued_1_5_hac_"=>$row["Issued_1_5_hac_"],"Issued_2_hac_"=>$row["Issued_2_hac_"],"Issued_2_5_hac_"=>$row["Issued_2_5_hac_"],"Issued_3_hac_"=>$row["Issued_3_hac_"],"Issued_3_5_hac_"=>$row["Issued_3_5_hac_"],"Issued_4_hac_"=>$row["Issued_4_hac_"],"Issued_5_hac_"=>$row["Issued_5_hac_"],"Total_hac"=>$row["Total_hac"]);
    array_push($data1,$temp);
    
   }




 }





}

 echo json_encode($data1);


?>


