<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="CryptoDash admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities with bitcoin dashboard.">
    <meta name="keywords" content="admin template, CryptoDash Cryptocurrency Dashboard Template, dashboard template, flat admin template, responsive admin template, web app, crypto dashboard, bitcoin dashboard">
    <meta name="author" content="ThemeSelection">
    <title>Loans</title>
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
    <link rel="stylesheet" type="text/css" href="app-assets/css/pages/wallet.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <!-- END Custom CSS-->
      <script src="https://www.gstatic.com/charts/loader.js"></script>
  </head>
  <body class="vertical-layout vertical-compact-menu content-detached-right-sidebar   menu-expanded fixed-navbar" data-open="click" data-menu="vertical-compact-menu" data-col="content-detached-right-sidebar">

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
<!--                  <li class="dropdown-menu-header">-->
<!--                    <h6 class="dropdown-header m-0"><span class="grey darken-2">Notifications</span></h6><span class="notification-tag badge badge-default badge-danger float-right m-0">5 New</span>-->
               </li>
                </ul>
              </li>
<!--              <li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label" href="loans.html"><i class="ficon icon-wallet"></i></a></li>-->
              <li class="dropdown dropdown-user nav-item"><a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">             <span class="avatar avatar-online"><img src="app-assets/images/portrait/small/avatar-s-1.png" alt="avatar"></span><span class="mr-1"><span class="user-name text-bold-700"></span></span></a>
                <div class="dropdown-menu dropdown-menu-right">            <!-- <a class="dropdown-item" href="account-profile.html"><i class="ft-award"></i>John Doe</a>-->
<!--                  <div class="dropdown-divider"></div><a class="dropdown-item" href="account-profile.html"><i class="ft-user"></i> Profile</a><a class="dropdown-item" href="loans.html"><i class="icon-wallet"></i> My Wallet</a><a class="dropdown-item" href="start_off_days.html"><i class="ft-check-square"></i> Transactions              </a>-->
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
          <li class="active"><a href="loans.php"><i class="icon-wallet"></i><span class="menu-title" data-i18n="">Loans</span></a>
          </li>
          <li class=" nav-item"><a href="start_off_days.php"><i class="icon-shuffle"></i><span class="menu-title" data-i18n="">Start-Off-Days</span></a>
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
            <h3 class="content-header-title mb-0 d-inline-block">Loan Management</h3>
            <div class="row breadcrumbs-top d-inline-block">
              <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index.html">Dashboard</a>
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </div>
              <div class="row">
                  <div class="col-md-8 col-12">
                      <div class="card pull-up">
                          <div class="card-content">
                              <div class="card-body">
                                  <div class="col-12">
                                      <div class="row">
                                          <div class="col-md-8 col-12">
                                              <p><strong>Total Loan Amonut</strong></p>
                                              <h1 id="totalLoanAmount">00.00 USD</h1>
                                              <!--<p class="mb-0">Welcome bonus <strong>+30%</strong> expires in 21 days.</p>-->
                                          </div>
                                          <div class="col-md-4 col-12 text-center text-md-right">
                                              <button type="button" class="btn-gradient-secondary mt-2">Loans Report <i class="la la-angle-right"></i></button>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4 col-12">
                      <div class="card">
                          <div class="card-content collapse show">
                              <div class="card-body">
                                  <div class="font-small-3 clearfix">
                                      <p><strong>Recovery Progress</strong></p>
                                      <span class="float-left">0%</span>
                                      <span class="float-right">100%</span>
                                  </div>
                                  <div class="progress progress-sm my-1 box-shadow-2">
                                      <div id="recoveryPercentage" class="progress-bar bg-warning" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                  </div>
                                  <div class="font-small-3 clearfix">
                                      <span class="float-left">Recovered <br> <strong id="totalRecoveredAmount">72,458.88 USD</strong></span>
                                      <span class="float-right text-right">Total Loan  <br> <strong id="totalLoanAmount1">372,458.88 USD</strong></span>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          <!--<div class="row match-height">
          <div class="col-xl-4 col-lg-12 flex">
              <div class="card card-transparent d-inline-block" >
                  <div class="card-header card-header-transparent">
                      <h6 class="card-title">Area Manager Recovery(%)</h6>
                  </div>
                  <div class="card-content">
                      <div id="myChart" style="width:200%;max-width:500px;height: 300px;display: flex;"></div>
                      <div class="card-body">
                      </div>
                      <div id="myChart2" style="width:200%;max-width:500px;height: 300px;display: flex;"></div>
                      <div class="card-body">
                      </div>
                  </div>
              </div>
          </div>
          </div>-->

          <div class="row match-height">
              <div class="col-xl-6 col-12">
                  <div class="card card-transparent">
                      <div class="card-header card-header-transparent py-20">
                          <div class="btn-group dropdown">
                              <h6>Area Manager's Recovery(%)</h6>
                          </div>
                      </div>
                      <div id="myChart" style="width:100%;max-width:600px;height: 300px;"></div>
                  </div>
              </div>
              <div class="col-xl-6 col-12">
                  <div class="card card-transparent py-20">
                      <div class="card-header card-header-transparent">
                          <h6 class="card-title">Provincial Recovery(%)</h6>
                      </div>
                      <div class="card-content">
                          <div id="myChart2" style="width:100%;max-width:600px;height: 300px;"></div>
                          <div class="card-body">
                          </div>
                      </div>
                  </div>
              </div>

          </div>

              <!--/ Recent Transactions -->
              <!-- Basic Horizontal Timeline -->
              <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                      <h6 class="my-2">F.O Performance Analysis</h6>
                      <div class="card">
                          <div class="card-content">
                              <div class="card-body">
                                  <div class="table-responsive">
                                      <table id="recent-orders" class="table table-hover table-xl mb-0">
                                          <thead>
                                          <tr>
                                              <th class="border-top-0">Field Officer</th>
                                              <th class="border-top-0">Total Ha</th>
                                              <th class="border-top-0">Total Growers</th>
                                              <th class="border-top-0">Loan Amount(USD)</th>
                                              <th class="border-top-0">Loans Paid(USD)</th>
                                              <th class="border-top-0">Recovery %</th>
                                              <th class="border-top-0">Recovery Report</th>
                                          </tr>
                                          </thead>
                                          <tbody id="tbody">
                                          <?php
require_once("../api/conn.php");

$seasonid=0;

$total_growers=0;
$contracted_ha=0;
$visited_ha=0;
$non_visited_ha=0;
$grower_visits=0;

$input_total=0;
$working_capital=0;
$roll_over=0;
$total_loan_amount=0;
$loan_payment=0;
$loan_interest=0;
$loan_balance=0;
$percantage=0;


$sql11 = "Select * from  seasons where active=1 ";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";

    $seasonid=$row["id"];

   
   }
 }


 $sql11 = "Select distinct users.id,surname,name from  users where active=1 and (rightsid=7 or rightsid=8 or rightsid=9)";

$result = $conn->query($sql11);
 
 if ($result->num_rows > 0) {
   // output data of each row
   while($row = $result->fetch_assoc()) {
    // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
        $total_growers=0;
        $contracted_ha=0;
        $visited_ha=0;
        $non_visited_ha=0;
        $grower_visits=0;
        $name=$row["name"];
        $surname=$row["surname"];
        $userid=$row['id'];
$loan_balance=0;
        $percantage=0;




         $sql132 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid";

          $result32 = $conn->query($sql132);
           
           if ($result32->num_rows > 0) {
             // output data of each row
             while($row32 = $result32->fetch_assoc()) {

                    $input_total=0;
                    $working_capital=0;
                    $roll_over=0;
                    $total_loan_amount=0;
                    $loan_payment=0;
                    $loan_interest=0;
                    
        

                   $growerid=$row32["growerid"];
                   


                    $sql12 = "Select * from inputs_total join growers on growers.id=inputs_total.growerid where inputs_total.seasonid=$seasonid and inputs_total.growerid=$growerid";

                    $result2 = $conn->query($sql12);
                     
                     if ($result2->num_rows > 0) {
                       // output data of each row
                       while($row2 = $result2->fetch_assoc()) {

                        $input_total+=$row2["amount"];
                       
                       }
                     }

                   




                     $sql14 = "Select * from rollover_total join growers on growers.id=rollover_total.growerid where rollover_total.seasonid=$seasonid and rollover_total.growerid=$growerid";

                    $result4 = $conn->query($sql14);
                     
                     if ($result4->num_rows > 0) {
                       // output data of each row
                       while($row4 = $result4->fetch_assoc()) {

                        $roll_over+=$row4["amount"];
                       
                       }
                     }



                      $sql13 = "Select * from working_capital_total join growers on growers.id=working_capital_total.growerid where working_capital_total.seasonid=$seasonid and working_capital_total.growerid=$growerid";

                    $result3 = $conn->query($sql13);
                     
                     if ($result3->num_rows > 0) {
                       // output data of each row
                       while($row3 = $result3->fetch_assoc()) {

                        $working_capital+=$row3["amount"];
                       
                       }
                     }



                   $total_loan_amount=$input_total + $working_capital + $roll_over;

                   $loan_interest=0;

                   //$loan_balance=0;



                   $sql15 = "Select parameters.name,value from charges_amount join parameters on parameters.id=charges_amount.parameterid where charges_amount.seasonid=$seasonid ";

                    $result5 = $conn->query($sql15);
                     
                     if ($result5->num_rows > 0) {
                       // output data of each row
                       while($row5 = $result5->fetch_assoc()) {

                        if ($row5["name"]=="Amount") {

                          $loan_interest+=$row5["value"];

                        }else{

                          $loan_interest+=$total_loan_amount*$row5["value"]/100;

                        }

                       
                       }
                     }

                     $loan_balance+=$total_loan_amount+$loan_interest;




                     $sql16 = "Select amount from loan_payment_total  where loan_payment_total.seasonid=$seasonid  and growerid=$growerid";

                    $result6 = $conn->query($sql16);
                     
                     if ($result6->num_rows > 0) {
                       // output data of each row
                       while($row6 = $result6->fetch_assoc()) {

                        
                          $loan_payment+=$row6["amount"];

                       
                       }
                     }


                    $balance=$loan_balance-$loan_payment;


             
             }

             $percantage+=($loan_payment/$loan_balance)*100;
           }
              
        

    
      $sql2 = "Select distinct growerid from  lat_long where userid=".$row['id']." and seasonid=$seasonid";

      $result1 = $conn->query($sql2);

      $grower_visits=$result1->num_rows;



      $sql2 = "Select distinct growerid from  visits where userid=$userid and seasonid=$seasonid ";

      $result2 = $conn->query($sql2);

      $total_growers=$result2->num_rows;



      $sql2 = "Select * from  lat_long join contracted_hectares on contracted_hectares.growerid=lat_long.growerid  where  lat_long.seasonid=$seasonid and lat_long.userid=$userid";

      $result2 = $conn->query($sql2);

      

      if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $visited_ha+=$row2["hectares"];

       
       }
     }


     $sql2 = "Select * from  contracted_hectares  where  seasonid=$seasonid ";

      $result2 = $conn->query($sql2);

      

      if ($result2->num_rows > 0) {
       // output data of each row
       while($row2 = $result2->fetch_assoc()) {

        $contracted_ha+=$row2["hectares"];
        
       }
     }



          echo "<tr>";
            echo    "<td class='text-truncate'><i class='la la-dot-circle-o success font-medium-1 mr-1'></i> ".$name." ".$surname."</td>";
             echo   "<td class='text-truncate'><a href='#'>".$contracted_ha."</a></td>";
             echo "<td class='text-truncate p-1'>".$total_growers."</td>";
            echo    "<td class='text-truncate'>$".$loan_balance."</td>";
             echo   "<td class='text-truncate'>$".$loan_payment."</td>";
             echo  " <td class='text-truncate'>";
             echo       "<a href='#' class='mb-0 btn-sm btn btn-outline-danger round'>".$percantage."%</a>";
              echo  "</td>";
             echo   "<td class='text-truncate'>";
                echo    "<a href='#' class='mb-0 btn-sm btn btn-outline-primary round'>Download</a>";
              echo  "</td>";
            echo "</tr>";

    
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
              </div>


    <footer class="footer footer-static footer-transparent">
      <p class="clearfix blue-grey lighten-2 text-sm-center mb-0 px-2"><span class="float-md-left d-block d-md-inline-block">Copyright  &copy; <script>
          document.write(new Date().getFullYear())
          </script> <a class="text-bold-800 grey darken-2" href="https://www.coreafricagrp.com/" target="_blank">Core Africa Group</a>, All rights reserved. </span><span class="float-md-right d-block d-md-inline-blockd-none d-lg-block"></i></span></p>
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


<!--          Area managers graph-->
          <script>
              google.charts.load('current', {'packages':['corechart']});
              google.charts.setOnLoadCallback(drawChart);

              function drawChart() {

// Set Data
                  const data = google.visualization.arrayToDataTable([
                      ['areaManager', 'Percentage'],
                      ['Sanya',55],
                      ['Chimhenga',49],
                      ['Tinashe',44],
                      ['Obvious',70],
                      ['Tafadzwa',28],
                      ['Bright',68],
                      ['Kudzi',85],
                      ['Marve',15],
                          ['Tonde',90]


                  ]);

// Set Options
                  const options = {
                      //title:'World Wide Wine Production'
                  };

// Draw
                  const chart = new google.visualization.BarChart(document.getElementById('myChart'));
                  chart.draw(data, options);

              }

          </script>

<!--          Provincial recovery -->

          <script>
              google.charts.load('current', {'packages':['corechart']});
              google.charts.setOnLoadCallback(drawChart);

              function drawChart() {

// Set Data
                  const data = google.visualization.arrayToDataTable([
                      ['Province', 'Percentage'],
                      ['Manicaland',54.8],
                      ['Mash East',48.6],
                      ['Mash West',44.4],
                      ['Mash Central',23.9],
                      ['Mash West',14.5]
                  ]);

// Set Options
                  const options = {
                      //title:'Area Managers Recovery',
                      is3D:true
                  };

// Draw
                  const chart = new google.visualization.PieChart(document.getElementById('myChart2'));
                  chart.draw(data, options);

              }
          </script>
          <script type="text/javascript">
            
let totalLoan=0;
let recoveredAmount=0;
function showResult(str) {
  // if (str.length==0) {
  //   document.getElementById("totalLoanAmount").innerHTML="0.00 USD";
  //   //document.getElementById("livesearch").style.border="0px";
  //   return;
  // }
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("totalLoanAmount").innerHTML=this.responseText + " USD";
      //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","contracted_amount.php",true);
  xmlhttp.send();
}

function showResult1(str) {
  // if (str.length==0) {
   document.getElementById("totalLoanAmount1").innerHTML="0.00 USD";
  //   //document.getElementById("livesearch").style.border="0px";
  //   return;
  // }
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("totalLoanAmount1").innerHTML=this.responseText + " USD";
      totalLoan=Number(this.responseText);
      console.log(totalLoan)

      
      //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","contracted_amount.php",true);
  xmlhttp.send();


}
function recoveryP(){

let b="";
     // if (str.length==0) {
   //document.getElementById("recoveryPercentage").style.width="90%";
  //   //document.getElementById("livesearch").style.border="0px";
  //   return;
  // }
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {


    document.getElementById("recoveryPercentage").style.width=this.responseText;
    b=this.responseText;


    console.log(this.responseText)
   
      //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","recovery_percentage.php",true);
  xmlhttp.send();

    // console.log(recoveredAmount)

    // var value1=(Number(recoveredAmount)/Number(totalLoan))*100

    // console.log(value1)

    
}
function recoveredResult(str) {
  // if (str.length==0) {
 document.getElementById("totalRecoveredAmount").innerHTML="0.00 USD";
  //   //document.getElementById("livesearch").style.border="0px";
  //   return;
  // }
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("totalRecoveredAmount").innerHTML=this.responseText + " USD";
      recoveredAmount=Number(this.responseText);

      console.log(totalLoan)
     
      //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","recovered_amount.php",true);
  xmlhttp.send();

   
}


function performanceResult(str) {
  // if (str.length==0) {
  //   //document.getElementById("livesearch").style.border="0px";
  //   return;
  // }
  var xmlhttp=new XMLHttpRequest();
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("tbody").innerHTML=this.responseText;
    
     
      //document.getElementById("livesearch").style.border="1px solid #A5ACB2";
    }
  }
  xmlhttp.open("GET","field_officer_loan_perfomance.php",true);
  xmlhttp.send();

   
}




showResult("bright");
showResult1("bright");
recoveredResult("bright");
//performanceResult("bright")
recoveryP()



          </script>

  </body>
</html>