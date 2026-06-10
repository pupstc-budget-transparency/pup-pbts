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
        <div class="row g-0">
            <div class="col-12 col-xl-2">
                <div class="d-flex flex-column gap-4 p-3 pt-5 bg-danger"
                    style="background: linear-gradient(180deg, #6b0000 0%, #3d0000 100%); width: 180px; min-height: 100vh;">
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
                                <strong>``
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