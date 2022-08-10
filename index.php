<?php


?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GMS</title>
</head>

<style type="text/css">

  html,body{
    margin: 0;
    
  }
  
  .grid-container{

    display: grid;
    height: 100vh;
    background: red;
    justify-content: center;
    /*align-content: center;*/
  }

  .grid-item{
   
  }

  .input-container{
    height: 3vh;
   
  }

  input{
    height: 100%;

  }

  h1,h4{
    text-align: center;
  }



</style>
<body>

  <div  class="grid-container">

    <div class="grid-item">
     <h1>
       GMS
     </h1>
     <h4>Grower Management System</h4>
      <form>
        <div class="input-container">
        <input type="text" name="username">
      </div>
      <br>
      <div class="input-container">
        <input type="password" name="password">
      </div> 
      <br>
      <div>
        <a href="#"> <label>
          Forgot password?
        </label></a>
       
      </div>

      <div>
        <input type="checkbox" name="checkbox" value="remember me ">
      </div>

      <div class="input-container">
        <input type="submit" name="submit" value="Login">  
      </div>
         
      </div>

      </form>
    
  </div>

</body>
</html>