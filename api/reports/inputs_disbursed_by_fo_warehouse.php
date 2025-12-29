<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid=$data->userid;
$seasonid=$data->seasonid;
$productid=0;


$chairman="";
$fieldOfficer="";
$area_manager="";
$growerid=0;

$data1=array();
// get grower locations

if ($userid!="") {


$products=array();

$total_products=array();
$sql111 = "Select distinct products.name as product_name  from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid  where loans.seasonid=$seasonid order by products.name ";
$result111 = $conn->query($sql111);
if ($result111->num_rows > 0) {
   // output data of each row
   while($row111 = $result111->fetch_assoc()) {

    $temp=array("product_name"=>$row111['product_name']);
    array_push($products,$temp);

   }
 }



 $sql1 = "Select distinct username,users.id from grower_field_officer join users on users.id=grower_field_officer.field_officerid  where grower_field_officer.seasonid=$seasonid ";
    $result1 = $conn->query($sql1);
     
     if ($result1->num_rows > 0) {
       // output data of each row
       while($row1 = $result1->fetch_assoc()) {
        // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $fieldOfficer=$row1["username"];
        $fieldOfficerid=$row1["id"];
        $product_name="";
        $total_products=array();


        $sql111 = "Select distinct products.name as product_name,products.id  from loans  join products on loans.productid=products.id   where loans.seasonid=$seasonid order by products.name ";
        $result111 = $conn->query($sql111);
        if ($result111->num_rows > 0) {
           // output data of each row
           while($row111 = $result111->fetch_assoc()) {

          $productid=$row111["id"];
          $product_name=$row111["product_name"];
          
          
          $sql = "SELECT
            users.username AS field_officer,

            SUM(quantity) AS quantities,
           
            COUNT(*) AS Total_participants -- Counts all records for the field officer

            -- Equivalent Hecterage Section
        FROM
            loans
        JOIN
            users ON users.id = loans.userid

            join products on  loans.productid=products.id

            join grower_field_officer

            on loans.growerid=grower_field_officer.growerid

            where loans.seasonid=$seasonid and loans.productid=$productid and 

             grower_field_officer.field_officerid=$fieldOfficerid
        -- Note: products table is not joined as the image summary is only by field officer, not by product.
        -- If you need product details, we'll need to adjust the GROUP BY clause.
        GROUP BY
            users.username
        ORDER BY
            users.username;";
        $result = $conn->query($sql);
         
         if ($result->num_rows > 0) {
           // output data of each row
           while($row456 = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

           //echo $row["field_officer"];
           $temp=array("product_name"=>$product_name,"quantity"=>$row456["quantities"]);
            array_push($total_products,$temp);
            
           }

         }




          }
         }


         $temp=array("products"=>$products,"fieldOfficer"=>$fieldOfficer,"total_products"=>$total_products);
         array_push($data1,$temp);




        
       }

    }





}

 echo json_encode($data1);


?>


