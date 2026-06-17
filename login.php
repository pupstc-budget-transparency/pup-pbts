<?php
session_start();
require 'includes/config.php';

$error = "";

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'student') {
        header("Location: student/dashboard.php");
        exit();
    } else {
        header("Location: admin/main.php");
        exit();
    }
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $portal = $_POST['portal'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            if ($portal == "student" && $user['role'] != "student") {
                $error = "This account is not a student account.";
            } elseif ($portal == "admin" && $user['role'] == "student") {
                $error = "Student accounts cannot access the Admin Portal.";
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == 'student') {
                    header("Location: student/dashboard.php");
                    exit();
                } else {
                    header("Location: admin/main.php");
                    exit();
                }
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC Participatory Budget Transparency System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body
    style="background-image: url('https://upload.wikimedia.org/wikipedia/commons/e/e7/PUP_Santo_Tomas_New_Building.JPG'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh;">

    <!-- Header -->
    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color: rgb(134, 9, 9);">
        <div class="container-xl">
            <div class="py-3">
                <h5 class="mb-0">PUPSTC Participatory Budget Transparency System</h5>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center g-4">

            <!-- Left: Sign-In Card -->
            <div class="col-lg-6">
                <div class="card bg-white bg-opacity-50 shadow-lg rounded-4 border border-light"
                    style="backdrop-filter: blur(5px);">
                    <div class="card-body p-5 text-center">

                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/Polytechnic_University_of_the_Philippines.svg/960px-Polytechnic_University_of_the_Philippines.svg.png"
                            class="img-fluid mb-4" style="max-width: 250px;" alt="PUP Logo">

                        <p class="mb-3 text-uppercase text-black-50 small" style="letter-spacing: 1px;">
                            Sign-In as
                        </p>

                        <div class="d-grid gap-3">
                            <button class="btn btn-lg py-3 fw-bold text-white" style="background-color: rgb(134, 9, 9);"
                                data-bs-toggle="modal" data-bs-target="#studentModal">
                                STUDENT
                            </button>
                            <button class="btn btn-lg py-3 fw-bold text-white" style="background-color: rgb(134, 9, 9);"
                                data-bs-toggle="modal" data-bs-target="#adminModal">
                                ADMIN
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Right: Info Card -->
            <div class="col-lg-5">
                <div class="card shadow-lg rounded-4 border border-light h-100"
                    style="background-color: rgba(150, 12, 12, 0.5); backdrop-filter: blur(6px);">
                    <div class="card-body p-5">

                        <h3 class="text-white fw-bold">
                            PUP-STC Participatory Budget Transparency System
                        </h3>

                        <p class="text-white mt-3">
                            A platform that empowers PUP STO. TOMAS CAMPUS students and administrators to
                            collaboratively track, propose, and monitor the allocation of school funds —
                            promoting accountability and transparency in every peso spent.
                        </p>

                        <p class="text-white-50 mt-5 mb-1 text-uppercase small" style="letter-spacing: 0.5px;">
                            What's Inside
                        </p>

                        <ul class="list-unstyled mt-0">
                            <li class="text-white mb-2 small">○ View dashboard summaries and budget overviews</li>
                            <li class="text-white mb-2 small">○ Browse and track student projects</li>
                            <li class="text-white mb-2 small">○ Access financial reports and allocations</li>
                            <li class="text-white mb-2 small">○ Monitor expenditures and fund usage</li>
                            <li class="text-white mb-2 small">○ Participate in voting and decision-making</li>
                            <li class="text-white mb-2 small">○ Submit feedback and suggestions</li>
                            <li class="text-white small">○ Stay updated via notifications and announcements</li>
                        </ul>

                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- End Main Content -->


    <!-- Student Login Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg"
                style="background: rgba(255,255,255,0.2); backdrop-filter: blur(16px);">
                <div class="modal-body p-5">

                    <form method="POST">
                        <input type="hidden" name="portal" value="student">

                        <div class="mb-3">
                            <input type="email" name="email"
                                class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                placeholder="Student Email" required>
                        </div>

                        <div class="mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                placeholder="Password" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-lg rounded-pill py-3 fw-bold text-white"
                                style="background-color: rgb(134, 9, 9);">
                                SIGN IN
                            </button>
                        </div>
                    </form>

                    <div class="text-center mb-4">
                        <a href="register.php" class="text-white fw-bold">Create Account</a>
                    </div>

                    <hr class="border-light opacity-25">

                    <p class="text-center text-white mb-1 small">
                        A platform built to empower students and promote transparency in every peso of school funds.
                    </p>
                    <p class="text-center fst-italic mt-2 small" style="color: #FFD700;">
                        "Mula Sa 'Yo Para sa Bayan"
                    </p>

                </div>
            </div>
        </div>
    </div>


    <!-- Admin Login Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg"
                style="background: rgba(255,255,255,0.2); backdrop-filter: blur(16px);">
                <div class="modal-body p-5">

                    <form method="POST">
                        <input type="hidden" name="portal" value="admin">

                        <div class="mb-3">
                            <input type="email" name="email"
                                class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                placeholder="Admin Email" required>
                        </div>

                        <div class="mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-lg rounded-pill border-0 bg-white bg-opacity-75"
                                placeholder="Password" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-lg rounded-pill py-3 fw-bold text-white"
                                style="background-color: rgb(134, 9, 9);">
                                SIGN IN
                            </button>
                        </div>
                    </form>

                    <div class="text-center mb-4">
                        <a href="#" class="fw-bold text-decoration-none text-secondary small">
                            FORGOT PASSWORD?
                        </a>
                    </div>

                    <hr class="border-light opacity-25">

                    <p class="text-center text-white mb-1 small">
                        Authorized personnel only. All actions are logged and monitored.
                    </p>
                    <p class="text-center fst-italic mt-2 small" style="color: #FFD700;">
                        "Mula Sa 'Yo Para sa Bayan"
                    </p>

                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>