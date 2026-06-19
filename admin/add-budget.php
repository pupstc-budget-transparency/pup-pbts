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

    .powerbi-wrapper {
        width: 100%;
        height: 900px;
        /* pick whatever fits your dashboard rhythm */
    }

    .powerbi-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
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
        <div class="row g-0">
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
                    <?php

                    if ($role == 'super_admin') {
                    ?>

                        <a href="dashboard.php" class="sidebar-btn active">
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

                        <a href="users.php" class="sidebar-btn">
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
                        <a href="dashboard.php" class="sidebar-btn active">
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
                        <a href="dashboard.php" class="sidebar-btn active">
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
                        <a href="dashboard.php" class="sidebar-btn active">
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
                    <hr class="sidebar-divider">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">

                            Logout

                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-10 col-sm-10">
                <div class="row g-0">
                    <div class="col-12 col-xl-12 col-sm-12 p-2 mt-2">
                        <div class="flex-grow-1">
                            <div class="card shadow">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h2 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['fullname']); ?>
                                            </h2>
                                            <h5 class="text-muted">Project Monitoring</h5>
                                        </div>

                                        <div class="role-access-card">
                                            <div class="role-box">
                                                <div class="icon-circle role-icon"><i class="bi bi-shield-lock"></i>
                                                </div>
                                                <div>
                                                    <small class="label-text">Current Role</small>
                                                    <div class="role-value">
                                                        <?= strtoupper(str_replace('_', ' ', $role)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="divider"></div>
                                            <div class="role-box">
                                                <div class="icon-circle access-icon"><i class="bi bi-shield-check"></i>
                                                </div>
                                                <div>
                                                    <small class="label-text">Access Level</small>
                                                    <div class="access-value">
                                                        <?php
                                                        if ($role == 'super_admin')
                                                            echo "Full System Access";
                                                        elseif ($role == 'budget_officer')
                                                            echo "Budget & Expenditure Access";
                                                        elseif ($role == 'project_coordinator')
                                                            echo "Project Monitoring Access";
                                                        elseif ($role == 'student_affairs')
                                                            echo "Student & Feedback Access";
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <h6>Total Budget</h6>
                                    <div class="powerbi-wrapper">
                                        <iframe title="PBTS"
                                            src="https://app.powerbi.com/reportEmbed?reportId=9794adaf-7ca2-4f65-ae4b-090a6c022173&autoAuth=true&ctid=4da98571-dcea-4839-8fb1-0bdd5dc969f9"
                                            frameborder="0" allowFullScreen="true">
                                        </iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>