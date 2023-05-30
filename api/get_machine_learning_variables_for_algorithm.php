<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";



$data = json_decode(file_get_contents("php://input"));


$userid=0;
$seasonid=0;
$description="";



$basal_quantity=0;
$an_quantity=0;
$potasium_quantity=0;


$basal_mass=0;
$an_mass=0;
$potasium_mass=0;


$product_name="";
$ml_variable="";

$grower_num="";
$growerid=0;



$temp=0;
$temp_min=0;
$temp_max=0;
$pressure=0;
$humidity=0;
$rain=0;
$clouds=0;
$wind_speed=0;



$home_latitude="";
$home_longitude="";


$farm_latitude="";
$farm_longitude="";


$yield=0;


$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->userid)  &&  isset($data->seasonid) &&  isset($data->description) ){

$userid=$data->userid;
$description=$data->description;
$seasonid=$data->seasonid;



if ($description=="") {


// $sql = "Select growers.grower_num,growers.id from growers join contracted_hectares on growers.id=contracted_hectares.growerid join users on users.id=contracted_hectares.userid where contracted_hectares.seasonid=$seasonid limit 10000";

$sql = "Select growers.grower_num,growers.id from growers ";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $basal_quantity=0;
    $an_quantity=0;
    $potasium_quantity=0;


    $basal_mass=0;
    $an_mass=0;
    $potasium_mass=0;

    $number_of_plants=0;



    $temp=0;
    $temp_min=0;
    $temp_max=0;
    $pressure=0;
    $humidity=0;
    $rain=0;
    $clouds=0;
    $wind_speed=0;


    $product_name="";
    $ml_variable="";

    $home_latitude="";
    $home_longitude="";


    $farm_latitude="";
    $farm_longitude="";


    $grower_num=$row["grower_num"];
    $growerid=$row["id"];

 
        $sql1 = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,loans.created_at,verified, users.username,machine_learning_product_variables.description as ml_variable from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid join machine_learning_products on machine_learning_products.productid=products.id join machine_learning_product_variables on machine_learning_product_variables.id=machine_learning_products.machine_learning_product_variablesid   where loans.seasonid=$seasonid   and (grower_num='$grower_num') ";
        $result1 = $conn->query($sql1);
         
         if ($result1->num_rows > 0) {
           // output data of each row
           while($row1 = $result1->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

            if ($row1["ml_variable"]=="AN") {

              $an_quantity+=$row1["quantity"];
              $an_mass+=($row1["package_units"]*$row1["quantity"]);

            }elseif($row1["ml_variable"]=="Basal"){

              $basal_quantity+=$row1["quantity"];
              $basal_mass+=($row1["package_units"]*$row1["quantity"]);

            }elseif($row1["ml_variable"]=="Potasium"){

              $potasium_quantity+=$row1["quantity"];
              $potasium_mass+=($row1["package_units"]*$row1["quantity"]);

            }


            
           }

          
         
         }


          $sql2 = "Select no_of_plants from ploughing where growerid=$growerid and seasonid=$seasonid limit 1";
          $result2 = $conn->query($sql2);
           
           if ($result2->num_rows > 0) {
             // output data of each row
             while($row2 = $result2->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $number_of_plants=$row2["no_of_plants"];
            
              
             }
           }




          $sql3 = "Select latitude,longitude from lat_long where growerid=$growerid and seasonid=$seasonid limit 1";
          $result3 = $conn->query($sql3);
           
           if ($result3->num_rows > 0) {
             // output data of each row
             while($row3 = $result3->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $home_latitude=$row3["latitude"];
              $home_longitude=$row3["longitude"];
            
              
             }
           }





            $sql4 = "Select grower_weather_total.id,temp,temp_min,temp_max,pressure,humidity,rain,clouds,wind_speed  from grower_weather_total join growers on grower_weather_total.growerid=growers.id where grower_weather_total.seasonid=$seasonid and grower_weather_total.growerid=$growerid  limit 1";
            $result4 = $conn->query($sql4);
           
           if ($result4->num_rows > 0) {
             // output data of each row
             while($row4 = $result4->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=$row4["temp"];
              $temp_min=$row4["temp_min"];
              $temp_max=$row4["temp_max"];
              $pressure=$row4["pressure"];
              $humidity=$row4["humidity"];
              $rain=$row4["rain"];
              $clouds=$row4["clouds"];
              $wind_speed=$row4["wind_speed"];

        
              
            
              
             }
           }





            $sql5 = "Select userid,seasonid,growerid,mass from grower_total_received_kgs  where seasonid=$seasonid and growerid=$growerid  limit 1";
            $result5 = $conn->query($sql5);
           
           if ($result5->num_rows > 0) {
             // output data of each row
             while($row5 = $result5->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $yield=$row5["mass"];
              
            
              
             }
           }







         $temp=array("yield"=>$yield,"growerid"=>$growerid,"grower_num"=>$grower_num,"basal_quantity"=>$basal_quantity,"an_quantity"=>$an_quantity,"potasium_quantity"=>$potasium_quantity,"basal_mass"=>$basal_mass,"an_mass"=>$an_mass,"potasium_mass"=>$potasium_mass,"no_of_plants"=>$number_of_plants ,"home_latitude"=>$home_latitude,"home_longitude"=>$home_longitude,"temp"=>$temp,"temp_min"=>$temp_min,"temp_max"=>$temp_max,"pressure"=>$pressure,"humidity"=>$humidity ,"rain"=>$rain,"clouds"=>$clouds,"wind_speed"=>$wind_speed);
         array_push($data1,$temp);

         

    
   }

   


 }







// $sql = "Select distinct users.id,grower_num,no_of_plants,basal_plant_fertilisation_kg_ha,fertilization_potassium.kg_per_ha as p_kg_per_ha ,fertilization_ammonium.kg_per_ha as a_kg_per_ha,ha from growers join cultural_practices on growers.id=cultural_practices.growerid  join contracted_hectares on growers.id=contracted_hectares.growerid join users on users.id=contracted_hectares.userid join mapped_hectares on growers.id=mapped_hectares.growerid join ploughing on growers.id=ploughing.growerid join fertilization_potassium on growers.id=fertilization_potassium.growerid join fertilization_ammonium on growers.id=fertilization_ammonium.growerid where fertilization_ammonium.seasonid=$seasonid  and  fertilization_potassium.seasonid=$seasonid and ploughing.seasonid=$seasonid and cultural_practices.seasonid=$seasonid and contracted_hectares.seasonid=$seasonid and mapped_hectares.seasonid=$seasonid";
// $result = $conn->query($sql);
 
//  if ($result->num_rows > 0) {
//    // output data of each row
//    while($row = $result->fetch_assoc()) {

//     // product id
//   $temp=array("id"=>$row["id"],"grower_num"=>$row["grower_num"],"no_of_plants"=>$row["no_of_plants"],"basal"=>$row["basal_plant_fertilisation_kg_ha"],"potassium"=>$row["p_kg_per_ha"],"ammonium"=>$row["a_kg_per_ha"],"ha"=>$row["ha"]);
//       array_push($data1,$temp);
 
 
//    }

//  }

}else{

$sql = "Select growers.grower_num,growers.id from growers where grower_num='$description'";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $basal_quantity=0;
    $an_quantity=0;
    $potasium_quantity=0;


    $basal_mass=0;
    $an_mass=0;
    $potasium_mass=0;

    $number_of_plants=0;



    $temp=0;
    $temp_min=0;
    $temp_max=0;
    $pressure=0;
    $humidity=0;
    $rain=0;
    $clouds=0;
    $wind_speed=0;


    $product_name="";
    $ml_variable="";

    $home_latitude="";
    $home_longitude="";


    $farm_latitude="";
    $farm_longitude="";


    $grower_num=$row["grower_num"];
    $growerid=$row["id"];

 
        $sql1 = "Select distinct products.id as productid,growers.name,growers.id,growers.surname,growers.grower_num,products.name as product_name,quantity,units,package_units,loans.created_at,verified, users.username,machine_learning_product_variables.description as ml_variable from loans join growers on growers.id=loans.growerid join products on loans.productid=products.id join users on users.id=loans.userid join machine_learning_products on machine_learning_products.productid=products.id join machine_learning_product_variables on machine_learning_product_variables.id=machine_learning_products.machine_learning_product_variablesid   where loans.seasonid=$seasonid   and (grower_num='$grower_num') ";
        $result1 = $conn->query($sql1);
         
         if ($result1->num_rows > 0) {
           // output data of each row
           while($row1 = $result1->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

            if ($row1["ml_variable"]=="AN") {

              $an_quantity+=$row1["quantity"];
              $an_mass+=($row1["package_units"]*$row1["quantity"]);

            }elseif($row1["ml_variable"]=="Basal"){

              $basal_quantity+=$row1["quantity"];
              $basal_mass+=($row1["package_units"]*$row1["quantity"]);

            }elseif($row1["ml_variable"]=="Potasium"){

              $potasium_quantity+=$row1["quantity"];
              $potasium_mass+=($row1["package_units"]*$row1["quantity"]);

            }


            
           }

          
         
         }


          $sql2 = "Select no_of_plants from ploughing where growerid=$growerid and seasonid=$seasonid limit 1";
          $result2 = $conn->query($sql2);
           
           if ($result2->num_rows > 0) {
             // output data of each row
             while($row2 = $result2->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $number_of_plants=$row2["no_of_plants"];
            
              
             }
           }




          $sql3 = "Select latitude,longitude from lat_long where growerid=$growerid and seasonid=$seasonid limit 1";
          $result3 = $conn->query($sql3);
           
           if ($result3->num_rows > 0) {
             // output data of each row
             while($row3 = $result3->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $home_latitude=$row3["latitude"];
              $home_longitude=$row3["longitude"];
            
              
             }
           }





            $sql4 = "Select grower_weather_total.id,temp,temp_min,temp_max,pressure,humidity,rain,clouds,wind_speed  from grower_weather_total join growers on grower_weather_total.growerid=growers.id where grower_weather_total.seasonid=$seasonid and grower_weather_total.growerid=$growerid  limit 1";
            $result4 = $conn->query($sql4);
           
           if ($result4->num_rows > 0) {
             // output data of each row
             while($row4 = $result4->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $temp=$row4["temp"];
              $temp_min=$row4["temp_min"];
              $temp_max=$row4["temp_max"];
              $pressure=$row4["pressure"];
              $humidity=$row4["humidity"];
              $rain=$row4["rain"];
              $clouds=$row4["clouds"];
              $wind_speed=$row4["wind_speed"];
              
             }
           }





            $sql5 = "Select userid,seasonid,growerid,mass from grower_total_received_kgs  where seasonid=$seasonid and growerid=$growerid  limit 1";
            $result5 = $conn->query($sql5);
           
           if ($result5->num_rows > 0) {
             // output data of each row
             while($row5 = $result5->fetch_assoc()) {
              // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
              $yield=$row5["mass"];
              
            
              
             }
           }








         $temp=array("yield"=>$yield,"growerid"=>$growerid,"grower_num"=>$grower_num,"basal_quantity"=>$basal_quantity,"an_quantity"=>$an_quantity,"potasium_quantity"=>$potasium_quantity,"basal_mass"=>$basal_mass,"an_mass"=>$an_mass,"potasium_mass"=>$potasium_mass,"no_of_plants"=>$number_of_plants ,"home_latitude"=>$home_latitude,"home_longitude"=>$home_longitude,"temp"=>$temp,"temp_min"=>$temp_min,"temp_max"=>$temp_max,"pressure"=>$pressure,"humidity"=>$humidity ,"rain"=>$rain,"clouds"=>$clouds,"wind_speed"=>$wind_speed);
         array_push($data1,$temp);
         

    
   }

   


 }

}


}else{

	  

}


echo json_encode($data1);



?>


