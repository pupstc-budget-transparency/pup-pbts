<?php

require '../includes/config.php';
require '../includes/auth.php';

$role = $_SESSION['role'];

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

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
                <div class="d-flex flex-column gap-4 p-3 pt-5 position-sticky"
                    style=" top: 0; background: linear-gradient(180deg, #6b0000 0%, #3d0000 100%); width: 180px;height: 100vh;">

                    <?php
                    if ($role == 'super_admin') {
                        ?>
                        <a href="dashboard.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Dashboard
                        </a>
                        <a href="budget-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Budget
                        </a>
                        <a href="expenditures.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Expenditures
                        </a>
                        <a href="projects.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Projects
                        </a>
                        <a href="reports.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Reports
                        </a>
                        <a href="feedback-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Feedback
                        </a>
                        <a href="voting-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Voting
                        </a>
                        <a href="users.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Users
                        </a>
                        <a href="audit-logs.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Audit Logs
                        </a>
                        <a href="announcements.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Announcements
                        </a>
                    <?php } ?>



                    <?php
                    if ($role == 'budget_officer') {
                        ?>
                        <a href="dashboard.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Dashboard
                        </a>
                        <a href="budget-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Budget
                        </a>
                        <a href="expenditures.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Expenditures
                        </a>
                        <a href="reports.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Reports
                        </a>
                    <?php } ?>



                    <?php
                    if ($role == 'project_coordinator') {
                        ?>
                        <a href="dashboard.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Dashboard
                        </a>
                        <a href="projects.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Projects
                        </a>

                        <a href="voting-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Voting
                        </a>

                        <a href="reports.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Reports
                        </a>

                    <?php } ?>



                    <?php

                    if ($role == 'student_affairs') {
                        ?>
                        <a href="dashboard.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Dashboard
                        </a>
                        <a href="users.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Students
                        </a>

                        <a href="feedback-management.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Feedback
                        </a>

                        <a href="announcements.php" class="btn rounded-pill text-dark"
                            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">
                            Announcements
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

            <div class="col-12 col-xl-7">
                <div class="row g-0">
                    <div class="col-12 col-xl-4 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Total Budget</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Utilized</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Remaining</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-12 p-2 mt-2 ">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Projects</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-12 p-2 mt-2 ">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Budget Allocation</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2 ">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Project Monitoring</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2 ">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Recent Expenditures</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>





            <div class="col-12 col-xl-3">
                <div class="row g-0">
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Notifications</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Reports</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Announcements</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow ">
                                <div class="card-body">
                                    <h6>Transparency Score</h6>
                                </div>
                            </div>
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