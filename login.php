<?php
// Initialize the session
session_start();

// Include config file
require_once 'config.php';

// Define variables and initialize with empty values
$meter_num = $password = "";
$meter_num_err = $password_err = $login_err = "";

// Check if the user is locked out
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    $remaining_time = $_SESSION['lockout_time'] - time();
    if ($remaining_time > 0) {
        $login_err = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } else {
        // Reset the login attempts and lockout time after lockout period
        unset($_SESSION['login_attempts']);
        unset($_SESSION['lockout_time']);
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Check if email is empty
    if (empty(trim($_POST["meter_num"]))) {
        $meter_num_err = "Please enter your email.";
    } else {
        $meter_num = trim($_POST["meter_num"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Verify reCAPTCHA
    if (empty($email_err) && empty($password_err) && empty($login_err)) {
        $recaptcha_secret = '6LfCwZYqAAAAAEbhh9M53gxnfqgwP2-Rkg7rnD5j'; // Replace with your reCAPTCHA v3 secret key
        $recaptcha_response = $_POST['recaptcha_response'];

        // Verify the reCAPTCHA response
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $response_keys = json_decode($response, true);

        if (!$response_keys['success'] || $response_keys['score'] < 0.5) {
            $login_err = "CAPTCHA verification failed. Please try again.";
        } else {
            // Prepare a select statement
            $sql = "SELECT id, status, password, is_approved FROM consumers WHERE meter_num = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_meter_num);
                $param_meter_num = $meter_num;

                // Execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    // Check if email exists, if yes then verify password
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $status, $hashed_password, $is_approved);
                        if (mysqli_stmt_fetch($stmt)) {
                            if (password_verify($password, $hashed_password)) {
                                if ($is_approved == 0) {
                                    $login_err = "Your account is awaiting approval. Please contact the system administrator.";
                                } elseif ($status === 'inactive') {
                                    $login_err = "Your account is inactive. Please contact the system administrator.";
                                } else {
                                    // Reset login attempts on successful login
                                    unset($_SESSION['login_attempts']);
                                    unset($_SESSION['lockout_time']);

                                    // Regenerate session ID for security
                                    session_regenerate_id();

                                    // Set session variables
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["meter_num"] = $meter_num;

                                    // Redirect user to the dashboard
                                    header("location: index");
                                    exit;
                                }
                            } else {
                                $login_err = "Invalid email or password.";
                            }
                        }
                    } else {
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    echo '<script>
                    Swal.fire({
                        title: "Error!",
                        text: "Oops! Something went wrong. Please try again later.",
                        icon: "error",
                        toast: true,
                        position: "top-right",
                        showConfirmButton: false,
                        timer: 3000
                    });
                    </script>';
                }

                // Increment the failed attempts
                if (!isset($_SESSION['login_attempts'])) {
                    $_SESSION['login_attempts'] = 0;
                }
                $_SESSION['login_attempts']++;

                // Lockout the user after 3 failed attempts
                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['lockout_time'] = time() + 15 * 60; // 15-minute lockout
                    $login_err = "Too many failed attempts. Please try again in 15 minutes.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
    mysqli_close($link);
}

        // Security headers
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(self), microphone=()");
        ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Consumer Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style2.css">
        <link rel="icon" href="logo.png" type="image/icon type">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <style>
            body {
                background-image: url("tank.jpg");
                background-repeat: no-repeat;
                background-position: center;
                background-attachment: fixed;
                background-size: cover;
                font-family: 'Georgia', serif;
            }
            .card {
                border-radius: 25px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                background-color: rgba(173, 216, 230, 0.2);
                padding: 20px;
                backdrop-filter: blur(3px);
            }
            .container {
                max-width: 550px;
                margin-left: auto;
                margin-right: auto;
            }
            .form-control {
                border-radius: 20px;
            }
            .btn {
                border-radius: 30px;
                font-weight: 600;
            }
            .recaptcha-container {
            display: flex;
            justify-content: center;
            align-items: center;
            }
            .g-recaptcha {
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <section class="vh-100 d-flex align-items-center justify-content-center">
            <div class="container">
                <div class="card">
                    <div class="card-body text-center">
                        <?php 
                        if (!empty($login_err)) {
                            echo '<script>
                            Swal.fire({
                                title: "Error!",
                                text: "' . htmlspecialchars($login_err) . '",
                                icon: "error",
                                toast: true,
                                position: "top-right",
                                showConfirmButton: false,
                                timer: 3000
                            });
                            </script>';
                        }        
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <p class="text-center mb-4">
                                <img src="logo.png" alt="Logo" style="max-width: 200px; height: auto;">
                            </p>   
                            <p class="text-center h1 fw-bold mb-4 mx-1 mx-md-3 mt-3">
                                <img src="users.png" alt="User Icon" style="width: 60px; height: 60px;">
                            </p>

                            <div class="form-outline mb-4">
                                <label class="form-label" for="login_meter_num"><strong>M eter Number</strong></label>
                                <input type="meter-num" id="login_meter_num" class="form-control py-3 <?php echo (!empty($meter_num_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($meter_num); ?>" name="meter_num" autocomplete="off" placeholder="Enter your meter number" required>
                                <span class="invalid-feedback"><?php echo $meter_num_err; ?></span>
                            </div>

                            <div class="form-outline mb-4">
                                <label class="form-label" for="login_password"><strong>Password</strong></label>
                                <div class="input-group">
                                    <input type="password" id="login_password" class="form-control py-3 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" name="password" autocomplete="off" placeholder="Enter your password" required>
                                    <span class="input-group-text" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="toggle-icon"></i>
                                    </span>
                                </div>
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>

                            <!-- Add reCAPTCHA widget -->
                            <!--<div class="g-recaptcha mb-3" data-sitekey="6LeNVYIqAAAAAD8moza5cF_4G7YsCSUZjy4ZMzZi"></div>-->

                            <div class="d-grid mb-3">
                                <input type="submit" value="Login" name="login" class="btn btn-primary text-light py-3">
                            </div>
                        </form>
                        <p class="text-center"><strong>Don't have an account? <a href="signup" class="text-primary">Sign up here</a></strong></p>
                        <p class="text-center"><strong>Forgot your password? <a href="forgot_password" class="text-primary">Click here</a></strong></p>
                        <p class="text-center"> <strong>Please call this number to approve your account.<a href="tel:09755319049"style="color: red;">09755319049</a></strong>
                    </div>
                </div>
            </div>
        </section>

        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.min.js"></script>
        <script src="https://www.google.com/recaptcha/api.js?render=6LfCwZYqAAAAAJ8wBxWCzCwsgeFpTdSYTagAmnwL"></script>

        <script>
            // Toggle password visibility
            function togglePasswordVisibility() {
                const passwordInput = document.getElementById('login_password');
                const toggleIcon = document.getElementById('toggle-icon');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye'); 
                }
            }
        </script>
        <script>
            grecaptcha.ready(function() {
            grecaptcha.execute('6LfCwZYqAAAAAJ8wBxWCzCwsgeFpTdSYTagAmnwL', { action: 'login' }).then(function(token) {
            const recaptchaResponseField = document.createElement('input');
            recaptchaResponseField.setAttribute('type', 'hidden');
            recaptchaResponseField.setAttribute('name', 'recaptcha_response');
            recaptchaResponseField.setAttribute('value', token);
            document.querySelector('form').appendChild(recaptchaResponseField);
        });
    });
</script>

    </body>
    </html>
