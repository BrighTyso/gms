<?php

require_once("../api/conn.php");

$seasonid=0;
$userid=0;
$startDate="";
$endDate="";
$hours_worked=0;
$start_battery_level=0;
$end_battery_level=0;
$battery_level_report=0;

$id=$_GET['id'];
$startDate=date_format(date_create($_GET['start']),"Y-m-d");
$endDate=date_format(date_create($_GET['end']),"Y-m-d");




$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }


 if ($id==0) {
 	

$sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username,name,surname from sod join users on users.id=sod.userid where sod.seasonid=$seasonid order by created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   

    		$name=$row["name"];
		    $surname=$row["surname"];
		    $userid=$row['userid'];
		    $created_at=$row['created_at'];
		    $distance=0;
		    $hours_worked=0;

		    $start_battery_level=0;
       $end_battery_level=0;
       $battery_level_report=0;






		    $sql1 = "Select distinct growerid from visits where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result1 = $conn->query($sql1);
			 
			$visited_growers=$result1->num_rows;


			$sql2 = "Select distinct * from distance where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$distance+=$row2['distance'];

			   	

			   }
			}




			$sql2 = "Select distinct * from hours_worked where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$hours_worked+=$row2['hours'];

			   	

			   }
			}


			 $sql2 = "Select distinct * from start_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $start_battery_level+=$row2['battery_level'];

         }
      }



      $sql2 = "Select distinct * from end_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $end_battery_level+=$row2['battery_level'];

         }
      }



		$sql2 = "Select distinct * from battery_level_report where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $battery_level_report+=$row2['battery_level'];

         }
      }



			$kms=$distance/1000;



    		echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
            echo    "<td class='text-truncate'>".$start_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$end_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$battery_level_report."%</td>";
             echo   "<td class='text-truncate'>".$kms." Kms</td>";
             echo  " <td class='text-truncate'>";
             echo       "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>".$hours_worked." hrs</a>";
              echo  "</td>";
             echo   "<td class='text-truncate'>";
                echo    "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>Report</a>";
              echo  "</td>";
            echo "</tr>";



  
   
    
   }
}


 }else if(($endDate!="" || $startDate!="") && $id>0){




  $sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username,name,surname from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and userid=$id and (sod.created_at between '$startDate' and '$endDate')  order by created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   

    		$name=$row["name"];
		    $surname=$row["surname"];
		    $userid=$row['userid'];
		    $created_at=$row['created_at'];
		    $distance=0;


				$start_battery_level=0;
				$end_battery_level=0;
				$battery_level_report=0;





		    $sql1 = "Select distinct growerid from visits where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result1 = $conn->query($sql1);
			 
			$visited_growers=$result1->num_rows;


			$sql2 = "Select distinct * from distance where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$distance+=$row2['distance'];

			   	

			   }
			}


			$sql2 = "Select distinct * from hours_worked where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$hours_worked+=$row2['hours'];

			   	

			   }
			}


			$sql2 = "Select distinct * from start_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $start_battery_level+=$row2['battery_level'];

         }
      }



      $sql2 = "Select distinct * from end_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $end_battery_level+=$row2['battery_level'];

         }
      }



$sql2 = "Select distinct * from battery_level_report where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $battery_level_report+=$row2['battery_level'];

         }
      }


			$kms=$distance/1000;



    		echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
             echo    "<td class='text-truncate'>".$start_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$end_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$battery_level_report."%</td>";
             echo   "<td class='text-truncate'>".$kms." Kms</td>";
             echo  " <td class='text-truncate'>";
             echo       "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>".$hours_worked." hrs</a>";
              echo  "</td>";
             echo   "<td class='text-truncate'>";
                echo    "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>Report</a>";
              echo  "</td>";
            echo "</tr>";



  
   
    
   }
}



 }else if(($endDate=="" || $startDate=="") && $id>0){


$sql = "Select userid,latitude,longitude,sod.seasonid,time,eod,sod.created_at,eod_created_at,username,name,surname from sod join users on users.id=sod.userid where sod.seasonid=$seasonid and userid=$id   order by created_at desc";
$result = $conn->query($sql);
 
 if ($result->num_rows > 0) {


   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

   

    		$name=$row["name"];
		    $surname=$row["surname"];
		    $userid=$row['userid'];
		    $created_at=$row['created_at'];
		    $distance=0;


$start_battery_level=0;
$end_battery_level=0;
$battery_level_report=0;





		    $sql1 = "Select distinct growerid from visits where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result1 = $conn->query($sql1);
			 
			$visited_growers=$result1->num_rows;


			$sql2 = "Select distinct * from distance where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$distance+=$row2['distance'];

			   	

			   }
			}


			$sql2 = "Select distinct * from hours_worked where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
			$result2 = $conn->query($sql2);
			 
			 if ($result2->num_rows > 0) {


			   // output data of each row
			   while($row2 = $result2->fetch_assoc()) {

			   	$hours_worked+=$row2['hours'];

			   	

			   }
			}


$sql2 = "Select distinct * from start_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $start_battery_level+=$row2['battery_level'];

         }
      }



      $sql2 = "Select distinct * from end_battery_level where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $end_battery_level+=$row2['battery_level'];

         }
      }



      $sql2 = "Select distinct * from battery_level_report where created_at='$created_at' and userid=$userid and seasonid=$seasonid";
      $result2 = $conn->query($sql2);
       
       if ($result2->num_rows > 0) {


         // output data of each row
         while($row2 = $result2->fetch_assoc()) {

          $battery_level_report+=$row2['battery_level'];

         }
      }


			$kms=$distance/1000;



    		echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
             echo    "<td class='text-truncate'>".$start_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$end_battery_level."%</td>";
            echo    "<td class='text-truncate'>".$battery_level_report."%</td>";
             echo   "<td class='text-truncate'>".$kms." Kms</td>";
             echo  " <td class='text-truncate'>";
             echo       "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>".$hours_worked." hrs</a>";
              echo  "</td>";
             echo   "<td class='text-truncate'>";
                echo    "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>Report</a>";
              echo  "</td>";
            echo "</tr>";



  
   
    
   }
}


 }




?>