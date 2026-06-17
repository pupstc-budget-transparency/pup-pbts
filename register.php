<?php

session_start();
require 'includes/config.php';

$message = "";

if (isset($_POST['register'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);


    if ($password != $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email=?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );

        mysqli_stmt_execute($check);

        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "Email already exists.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "student";

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users(fullname,email,password,role)
                 VALUES(?,?,?,?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $fullname,
                $email,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Registration successful! You can now login.";

            } else {

                $message = "Registration failed.";

            }

        }

    }

}
?>


<!DOCTYPE html>
<html>

<head>

    <title>Student Registration</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body style="background-image: url('https://upload.wikimedia.org/wikipedia/commons/e/e7/PUP_Santo_Tomas_New_Building.JPG'); 
    background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh;">
    <div style="position: fixed; inset: 0; backdrop-filter: blur(4px); background: rgba(0,0,0,0.2); z-index: 0;"></div>

    <!-- header -->
    <div class="container-fluid text-white shadow-sm sticky-top"
        style="background-color: rgb(134, 9, 9); z-index: 10; position: relative;">
        <div class="container-xl">
            <div class="py-3">
                <h5 class="mb-0">
                    PUPSTC Participatory Budget Transparency System
                </h5>
            </div>
        </div>
    </div>

    <div class="container mt-5" style="position: relative; z-index: 1;">

        <div class="row justify-content-center">

            <div class="col-md-6">

                <div class="card shadow border-0 rounded-4"
                    style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">

                    <div class="card-header border-0 rounded-top-4" style="background-color: rgb(134, 9, 9);">
                        <h4 class="text-white mb-0">Student Registration</h4>
                    </div>

                    <div class="card-body p-5">

                        <?php if ($message): ?>
                            <div class="alert alert-info">
                                    <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="text-white">Full Name</label>
                                <input type="text" name="fullname"
                                    class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="text-white">Email</label>
                                <input type="email" name="email"
                                    class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="text-white">Password</label>
                                <input type="password" name="password"
                                    class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="text-white">Confirm Password</label>
                                <input type="password" name="confirm_password"
                                    class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                    required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" name="register"
                                    class="btn btn-lg rounded-pill py-3 fw-bold text-white"
                                    style="background-color: rgb(134,9,9);">
                                    Register
                                </button>
                            </div>

                        </form>

                        <hr class="border-light opacity-25 mt-4">

                        <p class="text-center text-white mb-1" style="font-size:12px;">
                            A platform built to empower students
                            and promote transparency in every peso
                            of school funds.
                        </p>

                        <p class="text-center fst-italic mt-2" style="color:#FFD700; font-size:13px;">
                            "Mula Sa 'Yo Para sa Bayan"
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>