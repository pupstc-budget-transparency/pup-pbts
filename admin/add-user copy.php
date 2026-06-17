<?php

require '../includes/auth.php';
require '../includes/config.php';


$role = $_SESSION['role'];

$message = "";

if (isset($_POST['add_user'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $check = mysqli_query(
        $conn,
        "SELECT user_id
         FROM users
         WHERE email = '$email'"
    );

    if (empty($fullname)) {

        $message = "Full Name is required.";
    } elseif (mysqli_num_rows($check) > 0) {

        $message = "Email already exists.";
    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";
    } elseif ($password != $confirm_password) {

        $message = "Passwords do not match.";
    } else {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users
            (
                fullname,
                email,
                password,
                role,
                status
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $fullname,
            $email,
            $hashed_password,
            $role,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {

            $new_user_id = mysqli_insert_id($conn);

            // Audit Log
            mysqli_query(
                $conn,
                "INSERT INTO audit_logs
                (
                    user_id,
                    action,
                    table_name,
                    record_id
                )
                VALUES
                (
                    {$_SESSION['user_id']},
                    'Added User',
                    'users',
                    $new_user_id
                )"
            );

            header("Location: users.php");
            exit();
        } else {

            $message = "Failed to add user.";
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<style>
    .sidebar-logo {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .sidebar-title {
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
    }

    .sidebar-subtitle {
        font-size: 10px;
        line-height: 1.2;
        color: rgba(255, 255, 255, .9);
    }

    .sidebar-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        border-radius: 30px;
        text-decoration: none;
        color: white;
        background: transparent;
        transition: all .3s ease;
    }

    .sidebar-btn span {
        opacity: 1;
        transform: translateX(0);
        transition: .3s;
    }

    .sidebar-btn:hover {
        background: white;
        color: #7a0000;
        transform: translateX(20px);
        border-radius: 30px 0 0 30px;
    }

    .sidebar-btn.active {
        background: white;
        color: #7a0000;
        transform: translateX(20px);
        border-radius: 30px 0 0 30px;
    }

    .sidebar-btn {
        position: relative;
    }

    .sidebar-btn:hover::after,
    .sidebar-btn.active::after {
        content: '';
        position: absolute;
        top: 0;
        right: -40px;
        width: 40px;
        height: 100%;
        background: white;
    }

    .sidebar {
        position: sticky;
        top: 52px;
        left: 0;

        width: 200px;
        height: calc(100vh - 52px);

        overflow-y: auto;
        overflow-x: hidden;

        background: linear-gradient(180deg, #6b0000, #3d0000);
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .sidebar {
        overflow-y: hidden;
    }

    .sidebar:hover {
        overflow-y: auto;
    }

    .sidebar::-webkit-scrollbar {
        display: none;
    }

    .form-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .section-title .icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #fff1f1;
        color: #800000;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-title h5 {
        margin: 0;
        color: #800000;
        font-weight: 600;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        min-height: 52px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #800000;
        box-shadow: 0 0 0 .15rem rgba(128, 0, 0, .15);
    }

    .create-btn {
        background: #800000;
        border: none;
        border-radius: 10px;
        padding: 10px 25px;
    }

    .create-btn:hover {
        background: #650000;
    }

    .cancel-btn {
        border-radius: 10px;
        padding: 10px 25px;
    }

    .role-card {
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, .08);
        padding: 20px;
    }

    .role-access-card {
        background: #fff;
        border-radius: 18px;
        padding: 22px 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 35px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
        margin-bottom: 25px;
    }

    .role-box {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .icon-circle {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .role-icon {
        background: #fff2f2;
        color: #8B0000;
    }

    .access-icon {
        background: #edf9f1;
        color: #198754;
    }

    .label-text {
        color: #777;
        font-size: 13px;
    }

    .role-value {
        font-size: 22px;
        font-weight: 700;
        color: #8B0000;
    }

    .access-value {
        font-size: 22px;
        font-weight: 700;
        color: #198754;
    }

    .divider {
        width: 1px;
        height: 55px;
        background: #e5e5e5;
    }
</style>

<body>

    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color: rgb(134,9,9);">
        <div class="container-xl py-3 d-flex justify-content-between">
            <h6 class="mb-0">
                PUPSTC Participatory Budget Transparency System
            </h6>
            <span>
                <strong><?php echo $_SESSION['fullname']; ?></strong>
            </span>
        </div>
    </div>
    <div class="container-fluid px-0">
        <div class="row  g-0">
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5">

                    <div class="sidebar-header text-white mb-3">
                        <div class="d-flex align-items-center">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/Polytechnic_University_of_the_Philippines.svg/960px-Polytechnic_University_of_the_Philippines.svg.png"
                                alt="PUP Logo" class="sidebar-logo">
                            <div class="ms-2">
                                <div class="sidebar-title">PUPSTC</div>

                                <div class="sidebar-subtitle">
                                    Participatory Budget<br>
                                    Transparency System
                                </div>
                            </div>
                        </div>
                        <hr class="sidebar-divider">
                    </div>

                    <a href="dashboard.php" class="sidebar-btn ">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="budget-management.php" class="sidebar-btn">
                        <i class="bi bi-wallet2"></i>
                        <span>Budget</span>
                    </a>

                    <a href="expenditures.php" class="sidebar-btn">
                        <i class="bi bi-wallet2"></i>
                        <span>Expenditures</span>
                    </a>
                    <a href="projects.php" class="sidebar-btn">
                        <i class="bi bi-kanban"></i>
                        <span>Projects</span>
                    </a>

                    <a href="reports.php" class="sidebar-btn">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Reports</span>
                    </a>
                    <a href="feedback-management.php" class="sidebar-btn">
                        <i class="bi bi-envelope-paper"></i>
                        <span>Feedback</span>
                    </a>
                    <a href="voting-management.php" class="sidebar-btn">
                        <i class="bi bi-hand-index-thumb"></i>
                        <span> Voting</span>
                    </a>

                    <a href="users.php" class="sidebar-btn active">
                        <i class="bi bi-person-lines-fill"></i>
                        <span> Users</span>
                    </a>

                    <a href="audit-logs.php" class="sidebar-btn">
                        <i class="bi bi-clock-history"></i>
                        <span>Audit Logs</span>
                    </a>

                    <a href="announcements.php" class="sidebar-btn">
                        <i class="bi bi-megaphone-fill"></i>
                        <span>Announcements</span>
                    </a>

                    <hr class="sidebar-divider">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-10">
                <div class="row g-0">

                    <div class="col-12 col-xl-11 p-2 mt-3 ">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <div>
                                    <h2 class="fw-bold">
                                        Add New Budget Allocation
                                    </h2>
                                    <h5>budget Management</h5>
                                </div>

                                <div class="role-access-card">

                                    <div class="role-box">

                                        <div class="icon-circle role-icon">
                                            <i class="bi bi-shield-lock"></i>
                                        </div>

                                        <div>
                                            <small class="label-text">
                                                Current Role
                                            </small>

                                            <div class="role-value">
                                                <?php echo strtoupper(str_replace('_', ' ', $role)); ?>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="divider"></div>
                                    <div class="role-box">
                                        <div class="icon-circle access-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div>
                                            <small class="label-text">
                                                Access Level
                                            </small>

                                            <div class="access-value">

                                                <?php
                                                if ($role == 'super_admin') {
                                                    echo "Full System Access";
                                                } elseif ($role == 'budget_officer') {
                                                    echo "Budget & Expenditure Access";
                                                } elseif ($role == 'project_coordinator') {
                                                    echo "Project Monitoring Access";
                                                } elseif ($role == 'student_affairs') {
                                                    echo "Student & Feedback Access";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-11 p-2">
                        <div class="flex-grow-1">
                            <div class="card shadow border border-gray" style="border-radius: 16px;">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="card form-card">
                                            <div class="card-body p-4">
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-person"></i>
                                                    </div>

                                                    <h5>
                                                        1. User Information
                                                    </h5>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">
                                                            Full Name
                                                        </label>

                                                        <input type="text" name="fullname" class="form-control"
                                                            placeholder="Enter full name" required>
                                                    </div>

                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">
                                                            Email Address
                                                        </label>

                                                        <input type="email" name="email" class="form-control"
                                                            placeholder="Enter email address" required>
                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-lock"></i>
                                                    </div>

                                                    <h5>
                                                        2. Security Information
                                                    </h5>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">
                                                            Password
                                                        </label>

                                                        <input type="password" name="password" class="form-control"
                                                            placeholder="Enter password" required>

                                                        <small class="text-muted">
                                                            Password must be at least 8 characters.
                                                        </small>
                                                    </div>

                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">
                                                            Confirm Password
                                                        </label>
                                                        <input type="password" name="confirm_password"
                                                            class="form-control" placeholder="Confirm password"
                                                            required>

                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-person-gear"></i>
                                                    </div>

                                                    <h5>
                                                        3. Access Settings
                                                    </h5>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Role
                                                        </label>

                                                        <select name="role" class="form-select" required>
                                                            <option value="">
                                                                -- Select Role --
                                                            </option>

                                                            <option value="student">
                                                                Student
                                                            </option>

                                                            <option value="super_admin">
                                                                Super Admin
                                                            </option>

                                                            <option value="budget_officer">
                                                                Budget Officer
                                                            </option>

                                                            <option value="project_coordinator">
                                                                Project Coordinator
                                                            </option>

                                                            <option value="student_affairs">
                                                                Student Affairs
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">
                                                            Status
                                                        </label>

                                                        <select name="status" class="form-select" required>
                                                            <option value="">
                                                                -- Select Status --
                                                            </option>

                                                            <option value="active">
                                                                Active
                                                            </option>

                                                            <option value="inactive">
                                                                Inactive
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end gap-3 mt-4">
                                                    <a href="users.php" class="btn btn-light border cancel-btn">

                                                        <i class="bi bi-x-lg"></i>
                                                        Cancel

                                                    </a>

                                                    <button type="submit" name="add_user"
                                                        class="btn text-white create-btn">

                                                        <i class="bi bi-person-plus"></i>
                                                        Create User

                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="modal fade" id="errorModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        Validation Error
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white"
                                                        data-bs-dismiss="modal">
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    <?php echo $message; ?>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($message)) { ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {

                var myModal = new bootstrap.Modal(
                    document.getElementById("errorModal")
                );

                myModal.show();

            });
        </script>

    <?php } ?>
</body>

</html>