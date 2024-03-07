 <?php

require_once("../api/conn.php");

?>


<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="CryptoDash admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities with bitcoin dashboard.">
    <meta name="keywords" content="admin template, CryptoDash Cryptocurrency Dashboard Template, dashboard template, flat admin template, responsive admin template, web app, crypto dashboard, bitcoin dashboard">
    <meta name="author" content="ThemeSelection">
    <title>Start-Off-Days</title>
    <link rel="apple-touch-icon" href="app-assets/images/ico/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/images/ico/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i|Comfortaa:300,400,500,700" rel="stylesheet">
    <!-- BEGIN VENDOR CSS-->
    <link rel="stylesheet" type="text/css" href="app-assets/css/vendors.css">
    <!-- END VENDOR CSS-->
    <!-- BEGIN MODERN CSS-->
    <link rel="stylesheet" type="text/css" href="app-assets/css/app.css">
    <!-- END MODERN CSS-->
    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" type="text/css" href="app-assets/css/core/menu/menu-types/vertical-compact-menu.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/cryptocoins/cryptocoins.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/pages/transactions.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <!-- END Custom CSS-->
  </head>
  <body class="vertical-layout vertical-compact-menu 2-columns   menu-expanded fixed-navbar" data-open="click" data-menu="vertical-compact-menu" data-col="2-columns">

    <!-- fixed-top-->
    <nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-light navbar-bg-color">
      <div class="navbar-wrapper">
        <div class="navbar-header d-md-none">
          <ul class="nav navbar-nav flex-row">
            <li class="nav-item mobile-menu d-md-none mr-auto"><a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="ft-menu font-large-1"></i></a></li>
            <li class="nav-item d-md-none"><a class="navbar-brand" href="index.html"><img class="brand-logo d-none d-md-block" alt="CryptoDash admin logo" src="app-assets/images/logo/logo.png"><img class="brand-logo d-sm-block d-md-none" alt="CryptoDash admin logo sm" src="app-assets/images/logo/logo-sm.png"></a></li>
            <li class="nav-item d-md-none"><a class="nav-link open-navbar-container" data-toggle="collapse" data-target="#navbar-mobile"><i class="la la-ellipsis-v">   </i></a></li>
          </ul>
        </div>
        <div class="navbar-container">
          <div class="collapse navbar-collapse" id="navbar-mobile">
            <ul class="nav navbar-nav mr-auto float-left">
              <li class="nav-item d-none d-md-block"><a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="ft-menu">         </i></a></li>
              <li class="nav-item nav-search"><a class="nav-link nav-link-search" href="#"><i class="ficon ft-search"></i></a>
                <div class="search-input">
                  <input class="input" type="text" placeholder="Explore CryptoDash...">
                </div>
              </li>
            </ul>
            <ul class="nav navbar-nav float-right">
                <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                </ul>
              </li>
<!--              <li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label" href="loans.html"><i class="ficon icon-wallet"></i></a></li>-->
              <li class="dropdown dropdown-user nav-item"><a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">             <span class="avatar avatar-online"><img src="app-assets/images/portrait/small/avatar-s-1.png" alt="avatar"></span><span class="mr-1"><span class="user-name text-bold-700"></span></span></a>
                <div class="dropdown-menu dropdown-menu-right">             <!--<a class="dropdown-item" href="account-profile.html"><i class="ft-award"></i>John Doe</a>-->
<!--                  <div class="dropdown-divider"></div><a class="dropdown-item" href="account-profile.html"><i class="ft-user"></i> Profile</a><a class="dropdown-item" href="loans.html"><i class="icon-wallet"></i> My Wallet</a><a class="dropdown-item" href="start_off_days.html"><i class="ft-check-square"></i> Start-Off-Days</a>-->
                  <div class="dropdown-divider"></div><a class="dropdown-item" href="login.html"><i class="ft-power"></i> Logout</a>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- ////////////////////////////////////////////////////////////////////////////-->


    <div class="main-menu menu-fixed menu-dark menu-bg-default rounded menu-accordion menu-shadow">
      <div class="main-menu-content"><a class="navigation-brand d-none d-md-block d-lg-block d-xl-block" href="index.php"><img class="brand-logo" alt="CryptoDash admin logo" src="app-assets/images/logo/logo.png"/></a>
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
          <li class=" nav-item"><a href="index.php"><i class="icon-grid"></i><span class="menu-title" data-i18n="">Dashboard</span></a>
          </li>
          <li class=" nav-item"><a href="performance.php"><i class="icon-layers"></i><span class="menu-title" data-i18n="">Performance</span></a>
          </li>
          <li class=" nav-item"><a href="loans.php"><i class="icon-wallet"></i><span class="menu-title" data-i18n="">Loans</span></a>
          </li>
          <li class="active"><a href="start_off_days.php"><i class="icon-shuffle"></i><span class="menu-title" data-i18n="">Start-Off-Days</span></a>
          </li>
<!--          <li class=" nav-item"><a href="faq.html"><i class="icon-support"></i><span class="menu-title" data-i18n="">FAQ</span></a>-->
<!--          </li>-->
<!--          <li class=" nav-item"><a href="#"><i class="icon-user-following"></i><span class="menu-title" data-i18n="">Account</span></a>-->
<!--            <ul class="menu-content">-->
<!--              <li><a class="menu-item" href="account-profile.html">Profile</a>-->
<!--              </li>-->
<!--              <li><a class="menu-item" href="account-login-history.html">Login History</a>-->
<!--              </li>-->
<!--              <li class="navigation-divider"></li>-->
<!--              <li><a class="menu-item" href="#">Misc</a>-->
<!--                <ul class="menu-content">-->
<!--                  <li><a class="menu-item" href="login.html">Login</a>-->
<!--                  </li>-->
<!--                  <li><a class="menu-item" href="account-register.html">Register</a>-->
<!--                  </li>-->
<!--                </ul>-->
<!--              </li>-->
<!--            </ul>-->
<!--          </li>-->
        </ul>
      </div>
    </div>

    <div class="app-content content">
      <div class="content-wrapper">
        <div class="content-header row">
          <div class="content-header-left col-md-8 col-12 mb-2 breadcrumb-new">
            <h3 class="content-header-title mb-0 d-inline-block">F.O Start-Off-Days</h3>
            <div class="row breadcrumbs-top d-inline-block">
              <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.html">Dashboard</a>
                  </li>
                  <!--<li class="breadcrumb-item active">Transactions
                  </li>-->
                </ol>
              </div>
            </div>
          </div>
          <!--<div class="content-header-right col-md-4 col-12 d-none d-md-inline-block">
            <div class="btn-group float-md-right"><a class="btn-gradient-secondary btn-sm white" href="loans.html">Buy now</a></div>
          </div>-->
        </div>

          <!--/ ICO Token balance & sale progress -->

          <div class="row">
              <div class="col-12">
                  <div class="card pull-up">
                      <div class="card-content collapse show">
                          <div class="card-body">
                              <div class="form-horizontal form-purchase-token row" >
                                  <div class="col-md-3 col-12">
                                      <fieldset class="form-label-group mb-0">
                                          <select id="selectfieldOfficers" type="text" class="form-control" required="" autofocus="">

                                             <?php 




                                                        echo "<option value=0>Select Field-Officer</option>";


                                                        $sql2 = "Select  * from  users  where active=1 and (rightsid=7 or rightsid=8 or rightsid=9)";

                                                        $result1 = $conn->query($sql2);

                                                         if ($result1->num_rows > 0) {
                                                           // output data of each row
                                                           while($row = $result1->fetch_assoc()) {
                                                            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

                                                            $name=$row["name"];
                                                            $surname=$row["surname"];
                                                            $id=$row["id"];

                                                            echo "<option value=$id>".$name." ".$surname."</option>";

                                                           
                                                           }
                                                         }


                                                ?>
                                             
                                          </select>
                                          <!--                                            <label for="ico-token">Field Officer</label>-->
                                      </fieldset>
                                  </div>
                                  <div class="col-md-1 col-12 text-center">
                                      <span class="la la-arrow-right"></span>
                                  </div>
                                  <div class="col-md-3 col-12">
                                      <fieldset class="form-label-group mb-0">
                                          <input type="date" class="form-control" id="startDate" value="" required="" autofocus="">
                                          <label for="selected-crypto">FROM</label>
                                      </fieldset>
                                  </div>
                                  <div class="col-md-3 col-12">
                                      <fieldset class="form-label-group mb-0">
                                          <input type="date" class="form-control" id="endDate" value="" required="" autofocus="">
                                          <label for="selected-crypto">TO</label>
                                      </fieldset>
                                  </div>
                                  <!--                                    <div class="col-md-4 col-12 mb-1">-->
                                  <!--                                        <fieldset class="form-label-group mb-0">-->
                                  <!--                                            <input type="text" class="form-control" id="wallet-address" value="0xe834a970619218d0a7db4ee5a3c87022e71e177f" required="" autofocus="">-->
                                  <!--                                            <label for="wallet-address">Wallet address</label>-->
                                  <!--                                        </fieldset>-->
                                  <!--                                    </div>-->
                                  <div class="col-md-2 col-12 text-center">
                                      <button onclick="startOfDayResult()" id="searchBtn" class="btn-gradient-secondary">Search</button>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Recent Transactions -->
          <div class="row">
              <div id="recent-transactions" class="col-12">
                  <!--<h6 class="my-2">Field Officer Start-off-Days</h6>-->
                  <div class="card">                      <div class="card-content">
                          <div class="table-responsive">
                              <table id="recent-orders" class="table table-hover table-xl mb-0">
                                  <thead>
                                  <tr>
                                      <th class="border-top-0">Field Officer</th>
                                      <th class="border-top-0">From-Date</th>
                                      <th class="border-top-0">To-Date</th>
                                      <th class="border-top-0">Growers Visited</th>
                                      <th class="border-top-0">Distance Covered(kms)</th>
                                      <th class="border-top-0">Hrs Done</th>
                                      <th class="border-top-0">Report</th>
                                  </tr>
                                  </thead>
                                  <tbody id="tbody">
                                  <?php

require_once("../api/conn.php");

$seasonid=0;
$userid=0;
$startDate="";
$endDate="";
$hours_worked=0;
$id=0;



if(isset($_GET['start']) && isset($_GET['end']) && isset($_GET['id'])){
$id=$_GET['id'];
$startDate=date_format(date_create($_GET['start']),"Y-m-d");
$endDate=date_format(date_create($_GET['end']),"Y-m-d");
}




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



      $kms=$distance/1000;



        echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
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




      $kms=$distance/1000;



        echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
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




      $kms=$distance/1000;



        echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$row["created_at"]."</a></td>";
             echo "<td class='text-truncate p-1'>".$row["time"]."</td>";
            echo    "<td class='text-truncate'>".$visited_growers."</td>";
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

                                  </tbody>
                              </table>
                          </div>
                      </div>
                  </div>
              </div>
          </div>


        <!--<div class="content-body"><div id="transactions">
    <div class="transactions-table-th d-none d-md-block">
        <div class="col-12">
            <div class="row px-1">
                <div class="col-md-2 col-12 py-1">
                    <p class="mb-0">Date</p>
                </div>
                <div class="col-md-2 col-12 py-1">
                    <p class="mb-0">Type</p>
                </div>
                <div class="col-md-2 col-12 py-1">
                    <p class="mb-0">Amount</p>
                </div>
                <div class="col-md-1 col-12 py-1">
                    <p class="mb-0">Currency</p>
                </div>
                <div class="col-md-2 col-12 py-1">
                    <p class="mb-0">Tokens (CIC)</p>
                </div>
                <div class="col-md-3 col-12 py-1">
                    <p class="mb-0">Details</p>
                </div>
            </div>
        </div>
    </div>
    <div class="transactions-table-tbody">
        <section class="card pull-up">
            <div class="card-content">
                <div class="card-body">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Date: </span>2018-01-03</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <span class="d-inline-block d-md-none text-bold-700">Type: </span> <span class="d-inline-block d-md-none text-bold-700">Type: </span>  <a href="#" class="mb-0 btn-sm btn btn-outline-warning round">Deposit</a>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Amount: </span>  5.34111 </p>
                            </div>
                            <div class="col-md-1 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Currency: </span> <i class="cc ETH-alt"></i> ETH</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Tokens: </span> - </p>
                            </div>
                            <div class="col-md-3 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Details: </span> Deposit to your Balance <i class="la la-thumbs-up warning float-right"></i></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card pull-up">
            <div class="card-content">
                <div class="card-body">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Date:</span> 2018-01-03</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <span class="d-inline-block d-md-none text-bold-700">Type: </span>  <a href="#" class="mb-0 btn-sm btn btn-outline-success round">Deposit</a>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Amount: </span>  5.34111 </p>
                            </div>
                            <div class="col-md-1 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Currency: </span> <i class="cc ETH-alt"></i> ETH</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Tokens: </span> 3,258</p>
                            </div>
                            <div class="col-md-3 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Details: </span> Tokens Purchase</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card pull-up">
            <div class="card-content">
                <div class="card-body">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Date:</span> 2018-01-03</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <span class="d-inline-block d-md-none text-bold-700">Type: </span>  <a href="#" class="mb-0 btn-sm btn btn-outline-info round">Referral</a>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Amount: </span>  - </p>
                            </div>
                            <div class="col-md-1 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Currency: </span>  - </p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Tokens: </span> 200.88</p>
                            </div>
                            <div class="col-md-3 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Details: </span> Referral Promo Bonus</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card pull-up">
            <div class="card-content">
                <div class="card-body">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Date:</span> 2018-01-21</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <span class="d-inline-block d-md-none text-bold-700">Type: </span>  <a href="#" class="mb-0 btn-sm btn btn-outline-danger round">Withdrawal</a>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Amount: </span>  - </p>
                            </div>
                            <div class="col-md-1 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Currency: </span>  - </p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Tokens: </span> - 3,458.88</p>
                            </div>
                            <div class="col-md-3 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Details: </span> Tokens withdrawn <i class="la la-dollar warning float-right"></i></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card pull-up">
            <div class="card-content">
                <div class="card-body">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Date:</span> 2018-01-25</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <span class="d-inline-block d-md-none text-bold-700">Type: </span>  <a href="#" class="mb-0 btn-sm btn btn-outline-warning round">Deposit</a>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Amount: </span> 0.8791 </p>
                            </div>
                            <div class="col-md-1 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Currency: </span> <i class="cc BTC-alt"></i> BTC</p>
                            </div>
                            <div class="col-md-2 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Tokens: </span>  - </p>
                            </div>
                            <div class="col-md-3 col-12 py-1">
                                <p class="mb-0"><span class="d-inline-block d-md-none text-bold-700">Details: </span> Deposit to your Balance <i class="la la-thumbs-up warning float-right"></i></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center pagination-separate pagination-flat">
                <li class="page-item">
                    <a class="page-link" href="#" aria-label="Previous">
                        <span aria-hidden="true">« Prev</span>
                        <span class="sr-only">Previous</span>
                    </a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">4</a></li>
                <li class="page-item"><a class="page-link" href="#">5</a></li>
                <li class="page-item">
                    <a class="page-link" href="#" aria-label="Next">
                        <span aria-hidden="true">Next »</span>
                        <span class="sr-only">Next</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>
        </div>
      </div>
    </div>
    <!-- ////////////////////////////////////////////////////////////////////////////-->
          <footer class="footer footer-static footer-transparent">
              <p class="clearfix blue-grey lighten-2 text-sm-center mb-0 px-2"><span class="float-md-left d-block d-md-inline-block">Copyright  &copy; <script>
          document.write(new Date().getFullYear())
          </script> <a class="text-bold-800 grey darken-2" href="https://www.coreafricagrp.com/" target="_blank">Core Africa Group </a>, All rights reserved. </span><span class="float-md-right d-block d-md-inline-blockd-none d-lg-block"></span></p>
          </footer>

    <!-- BEGIN VENDOR JS-->
    <script src="app-assets/vendors/js/vendors.min.js" type="text/javascript"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN MODERN JS-->
    <script src="app-assets/js/core/app-menu.js" type="text/javascript"></script>
    <script src="app-assets/js/core/app.js" type="text/javascript"></script>
    <!-- END MODERN JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <!-- END PAGE LEVEL JS-->

    <script type="text/javascript">

                function getFieldOfficers() {
            // if (str.length==0) {
            //   //document.getElementById("livesearch").style.border="0px";
            //   return;
            // }
            var xmlhttp=new XMLHttpRequest();
            xmlhttp.onreadystatechange=function() {
              if (this.readyState==4 && this.status==200) {
                document.getElementById("selectfieldOfficers").innerHTML=this.responseText;
              
               
                //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
              }
            }
            xmlhttp.open("GET","get_field_officers.php",true);
            xmlhttp.send();

             
          }
      

          function startOfDayResult() {
            // if (str.length==0) {
            //   //document.getElementById("livesearch").style.border="0px";
            //   return;
            // }

            let startDate=document.getElementById("startDate").value
            let endDate=document.getElementById("endDate").value
            let fieldOfficerid=document.getElementById("selectfieldOfficers").value
            
            var xmlhttp=new XMLHttpRequest();
            xmlhttp.onreadystatechange=function() {
              if (this.readyState==4 && this.status==200) {
                document.getElementById("tbody").innerHTML=this.responseText;
              
               
                //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
              }
            }
            xmlhttp.open("GET","get_start_of_days.php?id="+fieldOfficerid+"&start="+startDate+"&end="+endDate,true);
            xmlhttp.send();

             
          }
          //getFieldOfficers()
          //startOfDayResult()


         // document.getElementById("searchBtn").onclick = startOfDayResult;

    </script>
  </body>
</html>