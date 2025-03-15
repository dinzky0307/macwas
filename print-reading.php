<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }

    include_once "config.php";

    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id = trim($_GET["id"]);
        
        $sql = "SELECT *, (present - previous) as used, readings.status as reading_status FROM readings LEFT JOIN consumers ON consumers.id = readings.consumer_id WHERE readings.id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            
            mysqli_stmt_bind_param($stmt, "i", $param_id);

            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $myObj = getMinimumRates($link, $row['type']);
                    
                } else{
                    header("location: reading.php?consumer_id=$consumer_id");
                    exit();
                }
                
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        mysqli_stmt_close($stmt);

    } else {
        header("location: consumer.php");
        exit();
    }

    function getMinimumRates($link, $type){
        $myObj = new stdClass;
        $query = "Select * from minimum_rates where type=?";
        if($stmt = mysqli_prepare($link, $query)){
            mysqli_stmt_bind_param($stmt, "s", $param_type);
            $param_type = $type;
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $mrow = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $myObj->rate_x = $mrow['rate_x'];
                    $myObj->rate_y = $mrow['rate_y'];
                    $myObj->rate_z = $mrow['rate_z'];
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $myObj;
    }

    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Bill</title>
        <?php include 'includes/links.php'; ?>
    </head>
    <body>
        <div class="container pt-5">
            <div class="text-center">
                <img class="img-fluid" src="logo.png" alt="" width=150>
                <p class="text-uppercase text-center mb-0">madridejos community waterworks system</p>
                <p class="text-uppercase text-center">
                    <small class="text-muted">municipality of madridejos</small><br />
                    <small class="text-muted">madridejos, cebu</small>
                </p>
            </div>
            <p class="text-right">Due Date: <?php echo date_format(date_create($row['due_date']), 'F j, Y'); ?></p>
            <div>
                <?php
                // Check if $myObj is an object before accessing its properties
                if (is_object($myObj)) {
                    $rate_x = isset($myObj->rate_x) ? $myObj->rate_x : 160;
                    $rate_y = isset($myObj->rate_y) ? $myObj->rate_y : 0;
                    $rate_z = isset($myObj->rate_z) ? $myObj->rate_z : 0;
                } else {
                    // Handle the case where $myObj is not an object
                    $rate_x = 0;
                    $rate_y = 0;
                    $rate_z = 0;
                }

                // Set the rates based on the consumer type
                if ($row['type'] === 'Commercial') {
                    $rate_x = 200; // Rate for first 10 cubic meters
                    $rate_y = 20;  // Rate for next 10 cubic meters
                    $rate_z = 25;  // Rate for cubic meters above 20
                } else { // Residential
                    $rate_x = 160; // Rate for first 10 cubic meters
                    $rate_y = 16;  // Rate for next 10 cubic meters
                    $rate_z = 16;  // Rate for cubic meters above 20
                }
                if ($row['type'] === 'Institution') {
                    $rate_x = 0; // Rate for first 10 cubic meters
                    $rate_y = 22;  // Rate for next 10 cubic meters
                    $rate_z = 22;  // Rate for cubic meters above 20
                }

                $x = 10;
                $y = 0;
                $z = 0;

                $x_value = (float)$rate_x;
                $y_value = 0;
                $z_value = 0;

                $date_now = date("Y-m-d");
                $over_due = $row['reading_status'] == 0.00 && $row['due_date'] < $date_now ? 20 : 0;

                if ((float)$row['used'] >= 20) {
                    $y = 10;
                    $z = (float)$row['used'] - 20;
                } else if ((float)$row['used'] >= 10) {
                    $z = (float)$row['used'] - 10;
                }

                $y_value = (float)$rate_y * $y;
                $z_value = (float)$rate_z * $z;

                // Calculate total charges including overdue charge
                $total = $x_value + $y_value + $z_value + $over_due;

                
                echo '<div class="row">';
                    echo '<div class="col-md-6">';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">NAME:</small>'.$row['name'].'</p>';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">ADDRESS:</small>'.$row['barangay'].'</p>';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">ACCOUNT NO.</small>'.$row['account_num'].'</p>';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">REGISTRATION NO.</small>'.$row['registration_num'].'</p>';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">METER NO.</small>'.$row['meter_num'].'</p>';
                        echo '<p class="mb-0 flex-grow-1"><small class="text-muted mr-2">TYPE:</small>'.$row['type'].'</p>';

                        echo '<table class="mt-3 table table-bordered table-sm">';
                            echo '<thead>';
                                echo '<tr>';
                                    echo '<th class="text-center">MONTH</th>';
                                    echo '<th class="text-center" colspan="2">READING</th>';
                                    echo '<th class="text-center">USED (cu.m.)</th>';
                                echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                                echo '<tr><td></td><td class="text-center">Present</td><td class="text-center">Previous</td><td></td></tr>';
                                echo '<tr>';
                                    echo '<td class="text-center">'.date_format(date_create($row['reading_date']), "F").'</td>';
                                    echo '<td class="text-center">'.$row['present'].'</td>';
                                    echo '<td class="text-center">'.$row['previous'].'</td>';
                                    echo '<td class="text-center">'.number_format((float)$row['used'], 2, '.', '').'</td>';
                                echo '</tr>';
                            echo '</tbody>';
                        echo '</table>';
                    echo '</div>';

                    echo '<div class="col-md-6">';
                        echo '<div class="row">';
                            echo '<div class="col-md-4">First '.(float)$x.' cu.m.</div>';
                            echo '<div class="col-md-4">P'.number_format($rate_x, 2, '.', '').'/cu.m</div>';
                            echo '<div class="col-md-4 text-right">'.number_format($x_value, 2, '.', '').'</div>'; 
                        echo '</div>';
                        if($y_value > 0){
                            echo '<div class="row">';
                                echo '<div class="col-md-4">'.$y.'</div>';
                                echo '<div class="col-md-4">P'.number_format($rate_y, 2, '.', '').'/cu.m</div>';
                                echo '<div class="col-md-4 text-right">'.number_format($y_value, 2, '.', '').'</div>';
                            echo '</div>';
                        }
                        if($z_value > 0){
                            echo '<div class="row">';
                                echo '<div class="col-md-4">'.number_format((float)$z, 2, '.', '').'</div>';
                                echo '<div class="col-md-4">P'.number_format($rate_z, 2, '.', '').'/cu.m</div>';
                                echo '<div class="col-md-4 text-right">'.number_format($z_value, 2, '.', '').'</div>';
                            echo '</div>';
                        }
                        if($over_due > 0){
                            echo '<div class="row mt-1">';
                                echo '<div class="col-md-9">Overdue:</div>';
                                echo '<div class="col-md-3 text-right">'.number_format($over_due, 2, '.', '').'</div>';
                            echo '</div>';
                        }
                        echo '<div class="row mt-3">';
                            echo '<div class="col-md-9"><strong>TOTAL CURRENT CHARGES:</strong></div>';
                            echo '<div class="col-md-3 text-right">'.number_format($total, 2, '.', '').'</div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
                ?>
                <p>Paying this bill after due date will be charge P20.00. Failure to pay (15) days after the due date is subject for disconnection without prior notice.</p>
                <p><strong>Note: Authorized collector of payments will be at MCC every 17th day of the month</strong></p>
                <p class="text-right"><strong>(SGD)ENGR. GUIDO C. DELA PENA</strong></p>
                <div class="col-md-11 text-right"><strong>MACWAS</strong></div>
            </div>
        </div>
        
        <script type="text/javascript">
        // window.onload = function() { window.print(); }
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
    </script>
    </body>
    </html>
