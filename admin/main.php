<?php


require '../includes/auth.php';

$role = $_SESSION['role'];

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
        width: 200px;
        height: 100vh;
        background: linear-gradient(180deg, #6b0000, #3d0000);
        overflow: hidden;
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
        <div class="row g-0">
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5 position-sticky" style=" top: 0; height: 100vh;">

                    <?php

                    if ($role == 'super_admin') {
                        ?>

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

                    <?php } ?>



                    <?php
                    if ($role == 'budget_officer') {
                        ?>
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

                        <a href="reports.php" class="sidebar-btn">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Reports</span>
                        </a>

                    <?php } ?>



                    <?php
                    if ($role == 'project_coordinator') {
                        ?>
                        <a href="dashboard.php" class="sidebar-btn ">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="projects.php" class="sidebar-btn">
                            <i class="bi bi-kanban"></i>
                            <span>Projects</span>
                        </a>
                        <a href="voting-management.php" class="sidebar-btn">
                            <i class="bi bi-hand-index-thumb"></i>
                            <span> Voting</span>
                        </a>

                        <a href="reports.php" class="sidebar-btn">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Reports</span>
                        </a>

                    <?php } ?>



                    <?php
                    if ($role == 'student_affairs') {
                        ?>
                        <a href="dashboard.php" class="sidebar-btn ">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="users.php" class="sidebar-btn">
                            <i class="bi bi-mortarboard"></i>
                            <span> Students</span>
                        </a>

                        <a href="feedback-management.php" class="sidebar-btn">
                            <i class="bi bi-envelope-paper"></i>
                            <span>Feedback</span>
                        </a>

                        <a href="announcements.php" class="sidebar-btn">
                            <i class="bi bi-megaphone-fill"></i>
                            <span>Announcements</span>
                        </a>

                    <?php } ?>

                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">

                            Logout

                        </a>
                    </div>
                </div>
            </div>


            <div class="col-12 col-xl-10">
                <div class="flex-grow-1">
                    <div class="card shadow border-0">
                        <div class="card-body">
                            <h2>
                                Welcome,
                                <?php echo $_SESSION['fullname']; ?>
                            </h2>
                            <p class="text-muted">
                                Current Role:
                                <strong>
                                    <?php echo strtoupper(str_replace('_', ' ', $role)); ?>
                                </strong>
                            </p>

                            <h5>Role Permissions</h5>

                            <?php if ($role == 'super_admin') { ?>
                                <div class="alert alert-success">
                                    Full System Access
                                </div>
                            <?php } ?>

                            <?php if ($role == 'budget_officer') { ?>
                                <div class="alert alert-primary">
                                    Budget & Expenditure Management Access
                                </div>
                            <?php } ?>

                            <?php if ($role == 'project_coordinator') { ?>
                                <div class="alert alert-warning">
                                    Project Monitoring Access
                                </div>
                            <?php } ?>

                            <?php if ($role == 'student_affairs') { ?>
                                <div class="alert alert-info">
                                    Student & Feedback Management Access
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>