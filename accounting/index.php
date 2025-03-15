<?php
// Initialize the session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}
require_once "config.php";

// Queries and data retrieval
$consumers_sql = "SELECT * FROM consumers;";
$consumers_result = mysqli_query($link, $consumers_sql);
$consumers_total = mysqli_num_rows($consumers_result);

$paid_sql = "SELECT * FROM readings WHERE status = 1;";
$paid_result = mysqli_query($link, $paid_sql);
$paid_total = mysqli_num_rows($paid_result);

$unpaid_sql = "SELECT * FROM readings WHERE status = 0;";
$unpaid_result = mysqli_query($link, $unpaid_sql);
$unpaid_total = mysqli_num_rows($unpaid_result);
// Close connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounting Dashboard</title>
    <?php include 'includes/links.php'; ?>
    <link rel="icon" href="logo.png" type="image/icon type">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js CDN -->
    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .bg-consumer-gradient {
            background: linear-gradient(135deg, #ff4b1f, #ff9068);
            color: white;
        }
        .bg-paidbills-gradient {
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
        }
        .bg-unpaid-gradient {
            background: linear-gradient(#ff66cc 0%, #9999ff 100%);
            color: white;
        }
        .navbar-light-gradient {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
            color: linear-gradient(135deg, #f09819, #edde5d);
            border-bottom: 1px solid black !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
            height: 65px;
            
        }
        .clock {
            font-size: 1.1rem;
            font-family: 'Verdana', sans-serif;
            font-weight: 550;
            color: black;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            margin-left: 15px;
        }
        .bxs-printer, .bx-mail-send {
            color: black;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .marquee {
            overflow: hidden;
            position: relative;
            white-space: nowrap;
            box-sizing: border-box;
            height: 40px; /* Adjust the height */
            display: flex;
            align-items: center;    
        }   

        .marquee-content {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 8s linear infinite;
            font-size: 30px; /* Adjust the text size */
            color: darkblue;
        
        }

        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        .gradient-text {
            background: linear-gradient(45deg, #36d1dc, #5b86e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        #consumersChart {
            max-width: 600px;
            max-height: 650px;
           
        }

    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <section class="home-section">
        <nav class="navbar navbar-light-gradient bg-white border-bottom">
            <div class="navbar-header">
            <span class="navbar-brand mb-0 h1 d-flex align-items-center">
            <i class='bx bx-menu mr-3' style='cursor: pointer; font-size: 2rem'></i>
            <div class="marquee">
                <div class="marquee-content">Macwas Water Billing System 2.0</div>
            </div>
        </span>
            </div>
            <?php include 'includes/userMenu.php'; ?>
        </nav>
        <div class="clock" id="clock"></div>

        <div class="container-fluid py-5">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-consumer-gradient text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $consumers_total; ?></h4>
                                    <small class="mb-0">Consumers</small>
                                </div>
                                <i class='bx bx-user bx-md'></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-paidbills-gradient text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $paid_total; ?></h4>
                                    <small class="mb-0">Paid Bills</small>
                                </div>
                                <i class='bx bxs-check-circle bx-md'></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-unpaid-gradient text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $unpaid_total; ?></h4>
                                    <small class="mb-0">Unpaid Bills</small>
                                </div>
                                <i class='bx bxs-credit-card bx-md'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <br>
            <div class="mt-5">
                <h4><option>Dashboard Chart<option></h4>
                <canvas id="consumersChart" width="400" height="200"></canvas>
            </div>
    </section>

    <?php include 'includes/scripts.php'; ?>
    <script>
        // Chart.js Configuration
        $(document).ready(function() {
            var ctx = document.getElementById('consumersChart').getContext('2d');
            var consumersChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Consumers', 'Paidbills', 'Unpaid'],
                    datasets: [{
                        label: 'Data Count',
                        data: [
                            <?php echo $consumers_total; ?>,
                            <?php echo $paid_total; ?>,
                            <?php echo $unpaid_total; ?>,
                        ],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 0.2)',
                        ],
                        borderWidth: 1.5
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

       // JavaScript for Clock
            function updateClock() {
            var now = new Date();
            
            // Time
            var hours = now.getHours();
            var minutes = now.getMinutes();
            var seconds = now.getSeconds();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            var timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            
            // Date
            var day = now.getDate();
            var month = now.getMonth() + 1; // January is 0!
            var year = now.getFullYear();
            var dateString = day + '/' + month + '/' + year;

            // Set both date and time
            document.getElementById('clock').textContent = timeString + ' | ' + dateString;
        }

        setInterval(updateClock, 1000);
        updateClock();  // Initialize the clock immediately

    </script>
      <script>
        // Disable right-click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, and Ctrl+U
        document.addEventListener('keydown', function(e) {
            if (e.keyCode == 123) { // F12
                e.preventDefault();
            }
            if (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) { // Ctrl+Shift+I or J
                e.preventDefault();
            }
            if (e.ctrlKey && e.keyCode == 85) { // Ctrl+U
                e.preventDefault();
            }
        });
    </script>s  
</body>
</html>
