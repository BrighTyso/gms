<?php  

  require "conn.php";

    $id=0;
   
    $data1=array();

      $id=$_GET["id"];
   

       $user_sql = "update exempt_user set used=1 where id=$id";
         //$sql = "select * from login";
         if ($conn->query($user_sql)===TRUE) {
         
           $temp=array("response"=>"success");
            array_push($data1,$temp);

         }


   echo json_encode($data1);


?>