<?php 
$username="root";
$host="localhost";
$database="regulator";
$password="";


$conn=new mysqli($host,$username,$password,$database);

if($conn->connect_error){
die("connection failed ". $conn->connect_error);
}

?>


