
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("../conn.php");



$data = json_decode(file_get_contents("php://input"));


$userid1=$data->userid;
$seasonid=$data->seasonid;

$username="";

$data1=array();

$landprep_discing=array();
$landprep_ploughing=array();
$landprep_ridging=array();

// get grower locations

if ($userid1!="") { 
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
      
      $sql11 = "SELECT distinct landprep_discing.id,landprep_discing.userid,growerid,latitude,  longitude,  landprep_discing.created_at,  landprep_discing.seasonid,discingOptions,dateOfDiscing,grower_num,growers.name, growers.surname, id_num,area, province, phone,landprep_discing.datetimes,username FROM landprep_discing join growers on landprep_discing.growerid=growers.id join users on users.id=landprep_discing.userid where landprep_discing.seasonid=$seasonid  ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

           $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"discingOptions"=>$row1["discingOptions"],"dateOfDiscing"=>$row1["dateOfDiscing"]);
              array_push($landprep_discing,$temp);

         
         }
       
   }







   $sql11 = "SELECT distinct landprep_ploughing.id ,landprep_ploughing.userid,  growerid,  latitude,  longitude,  landprep_ploughing.created_at,  landprep_ploughing.seasonid,grower_num,growers.name, growers.surname, id_num,area, province, phone,landprep_ploughing.datetimes,PloughingOptions,dateOfPloughing,username FROM landprep_ploughing join growers on landprep_ploughing.growerid=growers.id join users on users.id=landprep_ploughing.userid where landprep_ploughing.seasonid=$seasonid ";


      $result1 = $conn->query($sql11);
       
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"PloughingOptions"=>$row1["PloughingOptions"],"dateOfPloughing"=>$row1["dateOfPloughing"]);
          array_push($landprep_ploughing,$temp);

         
         }
       
   }




   $sql11 = "SELECT distinct landprep_ridging.id,landprep_ridging.userid,latitude,  longitude,  landprep_ridging.created_at,  landprep_ridging.seasonid,  ridging_done,  ridging_done_date,inter_rowspacing_done,  inter_rowspacing_measurement,  inrow_spacing_done,inrow_spacing_measurement,  inrow_pockets_done,  inrow_pockets_count,grower_num,growers.name, growers.surname, id_num,area, province, phone,username FROM landprep_ridging join growers on landprep_ridging.growerid=growers.id join users on users.id=landprep_ridging.userid where landprep_ridging.seasonid=$seasonid  ";

      $result1 = $conn->query($sql11);  
       if ($result1->num_rows > 0) {
         // output data of each row
         while($row1 = $result1->fetch_assoc()) {
          // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

          //  $temp=array("name"=>$row["name"],"surname"=>$row["surname"] ,"username"=>$row["username"] ,"id"=>$row["id"],"rights"=>$row["rightsid"]);
          // array_push($data1,$temp);

         $temp=array("surname"=>$row1["surname"],"name"=>$row1["name"],"grower_num"=>$row1["grower_num"],"username"=>$row1["username"],"ridging_done"=>$row1["ridging_done"],"ridging_done_date"=>$row1["ridging_done_date"],"inter_rowspacing_done"=>$row1["inter_rowspacing_done"],"inter_rowspacing_measurement"=>$row1["inter_rowspacing_measurement"],"inrow_spacing_done"=>$row1["inrow_spacing_done"]
      ,"inrow_spacing_measurement"=>$row1["inrow_spacing_measurement"],"inrow_pockets_done"=>$row1["inrow_pockets_done"]
      ,"inrow_pockets_count"=>$row1["inrow_pockets_count"]);
          array_push($landprep_ridging,$temp);

         
         }
       
   }





   $temp=array("landprep_ridging"=>$landprep_ridging,"landprep_ploughing"=>$landprep_ploughing,"landprep_discing"=>$landprep_discing);
  array_push($data1,$temp);



}

 echo json_encode($data1);


?>


