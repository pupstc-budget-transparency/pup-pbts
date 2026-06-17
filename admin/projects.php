<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];



if (isset($_POST['delete_project'])) {

    $project_id = (int) $_POST['project_id'];

    mysqli_query(
        $conn,
        "UPDATE projects
         SET record_status='archived'
         WHERE project_id = $project_id"
    );

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
             'Archived Project',
             'projects',
             $project_id
         )"
    );

    header("Location: projects.php");
    exit();
}



if (isset($_POST['update_project'])) {

    $project_id = (int) $_POST['project_id'];

    $project_title = trim($_POST['project_title']);
    $project_description = trim($_POST['project_description']);
    $allocated_budget = $_POST['allocated_budget'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $budget_id = $_POST['budget_id'];

    if ($allocated_budget <= 0) {

        echo "
        <script>
            alert('Allocated budget must be greater than zero.');
            window.history.back();
        </script>";
        exit();
    }

    if ($end_date < $start_date) {

        echo "
        <script>
            alert('End date cannot be earlier than start date.');
            window.history.back();
        </script>";
        exit();
    }

    if ($start_date == $end_date) {

        echo "
        <script>
            alert('Project must have a valid timeline.');
            window.history.back();
        </script>";
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE projects
         SET
            project_title=?,
            project_description=?,
            budget_id=?,
            allocated_budget=?,
            start_date=?,
            end_date=?,
            status=?
         WHERE project_id=?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssidsssi",
        $project_title,
        $project_description,
        $budget_id,
        $allocated_budget,
        $start_date,
        $end_date,
        $status,
        $project_id
    );

    mysqli_stmt_execute($stmt);

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
            'Updated Project',
            'projects',
            $project_id
        )"
    );

    header("Location: projects.php");
    exit();
}

$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );
}

$limit = 10;

$page = max(1, (int) ($_GET['page'] ?? 1));

$offset = ($page - 1) * $limit;


$count_sql = "
SELECT COUNT(*) AS total
FROM projects
WHERE record_status='active'
AND
(
    project_title LIKE '%$search%'
    OR project_code LIKE '%$search%'
)
";

$count_query = mysqli_query($conn, $count_sql);

$total_records = mysqli_fetch_assoc($count_query)['total'];

$total_pages = max(1, ceil($total_records / $limit));


$sql = "
SELECT
    projects.*,
    budgets.budget_title,
    users.fullname
FROM projects

LEFT JOIN budgets
    ON projects.budget_id = budgets.budget_id

LEFT JOIN users
    ON projects.created_by = users.user_id

WHERE
    projects.record_status='active'

AND
(
    projects.project_title LIKE '%$search%'
    OR projects.project_code LIKE '%$search%'
)

ORDER BY projects.project_id DESC

LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);


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
    body {
        background: #f5f7fb;
    }

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

    /* Hover Effect */
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


    @media (max-width: 1199.98px) {
        .sidebar {
            position: fixed;
            top: 0;
            left: -220px;
            height: 100vh;
            width: 200px;
            z-index: 1045;
            transition: left .3s ease;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            z-index: 1044;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }

    @media (max-width: 991.98px) {
        .page-header-row {
            flex-direction: column;
            align-items: stretch !important;
            gap: 15px;
        }

        .role-access-card {
            flex-direction: column;
            gap: 15px;
            padding: 16px;
            width: 100%;
        }

        .divider {
            width: 80%;
            height: 1px;
        }

        .role-value,
        .access-value {
            font-size: 16px;
        }
    }

    @media (max-width: 575.98px) {
        .header-system-name {
            display: none;
        }

        .pagination-wrap {
            flex-direction: column;
            gap: 12px;
            text-align: center;
        }

        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    @media (max-width: 767.98px) {
        .expenditure-search-form {
            flex-direction: column !important;
            width: 100%;
        }

        .expenditure-search-form input,
        .expenditure-search-form button,
        .expenditure-search-form a {
            width: 100%;
        }
    }

    @media (max-width: 768px) {

        .users-table thead {
            display: none;
        }

        .users-table,
        .users-table tbody,
        .users-table tr,
        .users-table td {
            display: block;
            width: 100%;
        }

        .users-table tr {
            background: white;
            margin-bottom: 15px;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            border: none;
        }

        .users-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: right;
            padding: 10px 5px;
            border: none !important;
            border-bottom: 1px solid #eee !important;
        }

        .users-table td:last-child {
            border-bottom: none !important;
        }

        .users-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: rgb(134, 9, 9);
            text-align: left;
        }

        .users-table td[data-label="Actions"] {
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .users-table td[data-label="Actions"]::before {
            display: none;
        }
    }


    .page-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
    }

    .stats-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        transition: .3s;
    }

    .stats-card:hover {
        transform: translateY(-4px);
    }

    .users-table {
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 14px;
    }

    .users-table thead {
        background: linear-gradient(90deg, #1f2937, #111827);
        color: white;
    }

    .users-table th {
        border: none !important;
        padding: 16px;
        font-weight: 600;
    }

    .users-table td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1px solid #ececec;
    }

    .users-table tbody tr {
        transition: .3s;
    }

    .users-table tbody tr:hover {
        background: #f8f9fa;
    }

    .role-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
    }

    .role-admin {
        background: #ede9fe;
        color: #6d28d9;
    }

    .role-student {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .role-affairs {
        background: #fef3c7;
        color: #b45309;
    }

    .role-budget {
        background: #fce7f3;
        color: #be185d;
    }

    .role-project {
        background: #d1fae5;
        color: #047857;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 13px;
    }

    .action-btn {
        width: 35px;
        height: 35px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
    }

    .search-box {
        border-radius: 12px;
        padding: 10px 15px;
    }

    .stats-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
        transition: .3s;
    }

    .stats-card:hover {
        transform: translateY(-4px);
    }

    .stats-card .rounded-circle {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pagination .page-link {
        border: none;
        color: #6b0000;
        margin: 0 3px;
        border-radius: 8px;
    }

    .pagination .page-item.active .page-link {
        background: #8B0000;
        border-color: #8B0000;
        color: white;
    }

    .pagination .page-link:hover {
        background: #f1f1f1;
    }

    .pup-header {
        background-color: rgb(134, 9, 9);
        color: white;
    }

    .pup-header th {
        background-color: rgb(134, 9, 9) !important;
        color: white !important;
        border-right: 1px solid grey !important;
        border-bottom: 1px solid grey !important;
    }

    .users-table tbody td {
        border-right: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .users-table tbody td:last-child {
        border-right: none !important;
    }

    .pup-header th:last-child {
        border-right: none !important;
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
        box-shadow:
            0 8px 25px rgba(0, 0, 0, .06);

        margin-bottom: 25px;
    }

    .role-box {
        display: flex align-items: center;
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

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .section-title .icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(134, 9, 9, .1);
        color: rgb(134, 9, 9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
    }

    .modal-content {
        border-radius: 16px;
    }

    .modal-body {
        background: #f8f9fa;
    }
</style>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color: rgb(134,9,9);">
        <div class="container-xl py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm text-white d-xl-none border-0 p-0 me-2" onclick="toggleSidebar()"
                    style="font-size:22px; line-height:1;">
                    <i class="bi bi-list"></i>
                </button>

                <h6 class="mb-0 header-system-name">
                    PUPSTC Participatory Budget Transparency System
                </h6>
                <h6 class="mb-0 d-sm-none">PUPSTC</h6>
            </div>
            <span>
                <strong><?php echo $_SESSION['fullname']; ?></strong>
            </span>
        </div>
    </div>
    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5" id="mainSidebar">
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
                    <a href="projects.php" class="sidebar-btn active">
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

                    <a href="users.php" class="sidebar-btn ">
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
                    <div class="col-12 col-xl-12 p-2 mt-3 ">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-4 me-3 page-header-row">

                                <div>
                                    <h2 class="fw-bold">
                                        <?php echo $_SESSION['fullname']; ?>

                                    </h2>
                                    <h5>Project Management</h5>
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

                    <div class="col-12 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">


                                <div class="me-3">
                                    <div class="rounded-circle bg-danger-subtle p-3">
                                        <i class="bi bi-people text-danger fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <?php
                                    $total_budgets = mysqli_query(
                                        $conn,
                                        "SELECT COUNT(*) total FROM budgets"
                                    );
                                    $total_budgets = mysqli_fetch_assoc($total_budgets)['total'];
                                    ?>

                                    <small class="text-muted">Total Budgets</small>
                                    <h3 class="mb-0">
                                        <?= $total_budgets; ?>
                                    </h3>
                                    <small class="text-muted">
                                        Budget records
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-cash-stack text-success fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <?php
                                    $total_amount = mysqli_query(
                                        $conn,
                                        "SELECT SUM(total_amount) total FROM budgets"
                                    );
                                    $total_amount = mysqli_fetch_assoc($total_amount)['total'];
                                    ?>

                                    <small class="text-muted">Total Budget Amount</small>
                                    <h3 class="mb-0">
                                        ₱<?= number_format($total_amount, 2); ?>
                                    </h3>
                                    <small class="text-muted">
                                        Allocated funds
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>


                    <div class="col-12 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-warning-subtle p-3">
                                        <i class="bi bi-calendar-check text-warning fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <?php
                                    $current_year = date('Y');

                                    $current_budgets = mysqli_query(
                                        $conn,
                                        "SELECT COUNT(*) total
                                         FROM budgets
                                         WHERE fiscal_year = '$current_year'"
                                    );

                                    $current_budgets = mysqli_fetch_assoc($current_budgets)['total'];
                                    ?>

                                    <small class="text-muted">Current Year</small>
                                    <h3 class="mb-0">
                                        <?= $current_budgets; ?>
                                    </h3>
                                    <small class="text-muted">
                                        FY <?= $current_year ?>
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-primary-subtle p-3">
                                        <i class="bi bi-shield-check text-primary fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <?php
                                    $largest_budget = mysqli_query(
                                        $conn,
                                        "SELECT MAX(total_amount) total
                                         FROM budgets"
                                    );

                                    $largest_budget = mysqli_fetch_assoc($largest_budget)['total'];
                                    ?>

                                    <small class="text-muted">Largest Budget</small>
                                    <h3 class="mb-0">
                                        ₱<?= number_format($largest_budget, 2); ?>
                                    </h3>
                                    <small class="text-muted">
                                        Highest allocation
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-0">
                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <div class="d-flex justify-content-start ">
                            <a href="add-projects.php" class="btn text-light px-4 py-2 shadow border border-gray"
                                style="background-color: rgb(134,9,9);font-size:16px; font-weight:600; border-radius:10px;">

                                <i class="bi bi-plus-lg"></i>
                                Add Project

                            </a>

                        </div>
                    </div>

                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center me-3 ">
                            <div class="text-muted">
                                Showing
                                <?= mysqli_num_rows($query); ?>
                                users
                            </div>
                            <form method="GET" class="d-flex gap-2 expenditure-search-form">

                                <input type="text" name="search" class="form-control" placeholder="Search project..."
                                    value="<?= htmlspecialchars($search) ?>" style="width:250px;">

                                <button type="submit" class="btn text-light" style="background-color: rgb(134,9,9);">

                                    <i class="bi bi-search"></i>
                                    Search

                                </button>
                                <a href="projects.php" class="btn btn-secondary">
                                    Clear
                                </a>
                            </form>


                        </div>
                    </div>

                    <div class="row g-0">
                        <div class="col-12 col-xl-12 p-2">
                            <div class="d-flex justify-content-between mb-3 me-3">
                                <div class="flex-grow-1">
                                    <div class="card page-card shadow border border-gray">

                                        <div class="card-body">
                                            <div class="table-responsive ">
                                                <table class="table users-table align-middle table-striped ">

                                                    <thead class="pup-header text-center">
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Project Code</th>
                                                            <th>Project Title</th>
                                                            <th>Budget Allocation</th>
                                                            <th>Start Date</th>
                                                            <th>End Date</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>

                                                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>

                                                            <tr>

                                                                <td data-label="ID"><?= $row['project_id']; ?></td>
                                                                <td data-label="Project Code"><?= htmlspecialchars($row['project_code']); ?></td>
                                                                <td data-label="Project Title"><?= htmlspecialchars($row['project_title']); ?></td>
                                                                <td data-label="Budget Allocation"><?= htmlspecialchars($row['budget_title']); ?></td>
                                                                <td data-label="Start Date">
                                                                    <?= date('M d, Y', strtotime($row['start_date'])); ?>
                                                                </td>
                                                                <td data-label="End Date">
                                                                    <?= date('M d, Y', strtotime($row['end_date'])); ?>
                                                                </td>
                                                                <td data-label="Status">
                                                                    <?php if ($row['status'] == 'Planning') { ?>
                                                                        <span class="badge bg-secondary">Planning</span>
                                                                    <?php } elseif ($row['status'] == 'Ongoing') { ?>
                                                                        <span class="badge bg-primary">Ongoing</span>
                                                                    <?php } elseif ($row['status'] == 'Completed') { ?>
                                                                        <span class="badge bg-success">Completed</span>
                                                                    <?php } else { ?>
                                                                        <span class="badge bg-danger">Cancelled</span>
                                                                    <?php } ?>
                                                                </td>

                                                                <td data-label="Actions">
                                                                    <div class="btn-group">

                                                                        <button class="btn action-btn text-dark me-2"
                                                                            style="background-color:#FFC72C;"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editModal<?= $row['project_id']; ?>">

                                                                            <i class="bi bi-pencil"></i>

                                                                        </button>

                                                                        <a href="project-monitoring.php?project_id=<?= $row['project_id']; ?>"
                                                                            class="btn btn-success action-btn me-2">

                                                                            <i class="bi bi-bar-chart-line"></i>

                                                                        </a>



                                                                        <button class="btn action-btn text-light"
                                                                            style="background-color:rgb(134,9,9);"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteModal<?= $row['project_id']; ?>">

                                                                            <i class="bi bi-trash"></i>

                                                                        </button>

                                                                    </div>
                                                                </td>

                                                            </tr>

                                                        <?php } ?>

                                                    </tbody>
                                                </table>

                                                <?php
                                                mysqli_data_seek($query, 0);

                                                while ($row = mysqli_fetch_assoc($query)) {
                                                ?>

                                                    <div class="modal fade" id="deleteModal<?= $row['project_id']; ?>"
                                                        tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">

                                                                <div class="modal-header bg-danger text-white">

                                                                    <h5 class="modal-title">
                                                                        Archive Project
                                                                    </h5>

                                                                    <button type="button" class="btn-close btn-close-white"
                                                                        data-bs-dismiss="modal">
                                                                    </button>
                                                                </div>

                                                                <div class="modal-body text-center">

                                                                    <i
                                                                        class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>

                                                                    <p class="mt-3">
                                                                        Are you sure you want to archive
                                                                        <strong><?= htmlspecialchars($row['project_title']); ?></strong>?
                                                                    </p>

                                                                    <small class="text-muted">
                                                                        This project will be hidden from active records but
                                                                        can be restored later.
                                                                    </small>

                                                                </div>

                                                                <div class="modal-footer">

                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">

                                                                        Cancel

                                                                    </button>

                                                                    <form method="POST">

                                                                        <input type="hidden" name="project_id"
                                                                            value="<?= $row['project_id']; ?>">

                                                                        <button type="submit" name="delete_project"
                                                                            class="btn btn-danger">

                                                                            Archive Project

                                                                        </button>

                                                                    </form>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </div>
                                                    <div class="modal fade" id="editModal<?= $row['project_id']; ?>"
                                                        tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">

                                                        <div class="modal-dialog modal-xl modal-dialog-centered">

                                                            <div class="modal-content border-0 shadow"
                                                                style="border-radius:16px; overflow:hidden;">

                                                                <div class="modal-header text-white"
                                                                    style="background-color:rgb(134,9,9);">

                                                                    <h5 class="modal-title fw-bold">
                                                                        <i class="bi bi-pencil-square me-2"></i>
                                                                        Edit Project
                                                                    </h5>

                                                                </div>

                                                                <form method="POST">

                                                                    <input type="hidden" name="project_id"
                                                                        value="<?= $row['project_id']; ?>">

                                                                    <div class="modal-body p-4">

                                                                        <div class="card form-card border-0">

                                                                            <div class="card-body">

                                                                                <!-- SECTION 1 -->
                                                                                <div class="section-title">

                                                                                    <div class="icon">
                                                                                        <i class="bi bi-cash-stack"></i>
                                                                                    </div>

                                                                                    <h5>1. Project Information</h5>

                                                                                </div>

                                                                                <div class="row">

                                                                                    <div class="col-md-6 mb-4">
                                                                                        <label class="form-label">
                                                                                            Project Code
                                                                                        </label>

                                                                                        <input type="text"
                                                                                            class="form-control"
                                                                                            value="<?= htmlspecialchars($row['project_code']); ?>"
                                                                                            readonly>
                                                                                    </div>

                                                                                    <div class="col-md-6 mb-4">

                                                                                        <label class="form-label">
                                                                                            Project Title
                                                                                        </label>

                                                                                        <input type="text"
                                                                                            name="project_title"
                                                                                            class="form-control"
                                                                                            value="<?= htmlspecialchars($row['project_title']); ?>"
                                                                                            required>

                                                                                    </div>


                                                                                    <div class="col-md-6 mb-4">
                                                                                        <label class="form-label">
                                                                                            Project Description
                                                                                        </label>

                                                                                        <textarea name="project_description"
                                                                                            class="form-control" rows="3"
                                                                                            required><?= htmlspecialchars($row['project_description']); ?>
                                                                                                </textarea>
                                                                                    </div>

                                                                                </div>

                                                                                <hr>

                                                                                <!-- SECTION 2 -->
                                                                                <div class="section-title">

                                                                                    <div class="icon">
                                                                                        <i class="bi bi-wallet2"></i>
                                                                                    </div>

                                                                                    <h5>
                                                                                        2. Budget Allocation
                                                                                    </h5>

                                                                                </div>

                                                                                <div class="row">

                                                                                    <div class="col-md-6 mb-4">

                                                                                        <label class="form-label">
                                                                                            Budget Allocation
                                                                                        </label>

                                                                                        <select name="budget_id"
                                                                                            class="form-select" required>

                                                                                            <?php
                                                                                            $budgets = mysqli_query(
                                                                                                $conn,
                                                                                                "SELECT budget_id, budget_title
                                                                                                 FROM budgets
                                                                                                 WHERE record_status='active'"
                                                                                            );

                                                                                            while ($budget = mysqli_fetch_assoc($budgets)) {
                                                                                            ?>

                                                                                                <option
                                                                                                    value="<?= $budget['budget_id']; ?>"
                                                                                                    <?= ($row['budget_id'] == $budget['budget_id']) ? 'selected' : ''; ?>>

                                                                                                    <?= htmlspecialchars($budget['budget_title']); ?>

                                                                                                </option>

                                                                                            <?php } ?>
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="col-md-12 mb-4">

                                                                                        <label class="form-label">
                                                                                            Total Budget Amount
                                                                                        </label>

                                                                                        <input type="number" step="0.01"
                                                                                            min="0" name="allocated_budget"
                                                                                            class="form-control"
                                                                                            value="<?= $row['allocated_budget']; ?>"
                                                                                            required>

                                                                                        <small class="text-muted">
                                                                                            Enter the allocated amount.
                                                                                        </small>

                                                                                    </div>

                                                                                </div>

                                                                                <hr>

                                                                                <!-- SECTION 3 -->
                                                                                <div class="section-title">

                                                                                    <div class="icon">
                                                                                        <i
                                                                                            class="bi bi-file-earmark-text"></i>
                                                                                    </div>

                                                                                    <h5>3. Timeline & Status</h5>

                                                                                </div>

                                                                                <div class="row">

                                                                                    <div class="col-md-6 mb-4">

                                                                                        <label class="form-label">
                                                                                            Start Date
                                                                                        </label>

                                                                                        <input type="date" name="start_date"
                                                                                            class="form-control"
                                                                                            value="<?= $row['start_date']; ?>"
                                                                                            required>

                                                                                    </div>

                                                                                    <div class="col-md-6 mb-4">

                                                                                        <label class="form-label">
                                                                                            End Date
                                                                                        </label>

                                                                                        <input type="date" name="end_date"
                                                                                            class="form-control"
                                                                                            value="<?= $row['end_date']; ?>"
                                                                                            required>

                                                                                    </div>

                                                                                    <div class="col-md-12 mb-4">

                                                                                        <label class="form-label">
                                                                                            Project Status
                                                                                        </label>

                                                                                        <select name="status"
                                                                                            class="form-select" required>

                                                                                            <option value="Planning"
                                                                                                <?= ($row['record_status'] == 'Planning') ? 'selected' : ''; ?>>
                                                                                                Planning
                                                                                            </option>

                                                                                            <option value="Ongoing"
                                                                                                <?= ($row['record_status'] == 'Ongoing') ? 'selected' : ''; ?>>
                                                                                                Ongoing
                                                                                            </option>

                                                                                            <option value="Completed"
                                                                                                <?= ($row['record_status'] == 'Completed') ? 'selected' : ''; ?>>
                                                                                                Completed
                                                                                            </option>

                                                                                            <option value="Cancelled"
                                                                                                <?= ($row['record_status'] == 'Cancelled') ? 'selected' : ''; ?>>
                                                                                                Cancelled
                                                                                            </option>

                                                                                        </select>

                                                                                    </div>

                                                                                </div>

                                                                            </div>

                                                                        </div>

                                                                    </div>

                                                                    <div class="modal-footer">

                                                                        <button type="button" class="btn btn-light border"
                                                                            data-bs-dismiss="modal">

                                                                            <i class="bi bi-x-lg"></i>
                                                                            Cancel

                                                                        </button>

                                                                        <button type="submit" name="update_project"
                                                                            class="btn text-white"
                                                                            style="background-color:rgb(134,9,9);">

                                                                            <i class="bi bi-check-circle"></i>
                                                                            Save Changes

                                                                        </button>

                                                                    </div>

                                                                </form>

                                                            </div>

                                                        </div>

                                                    </div>
                                                <?php } ?>


                                                <div class="d-flex justify-content-between align-items-center mt-5 pagination-wrap">

                                                    <small class="text-muted">

                                                        Showing
                                                        <?= $offset + 1 ?>
                                                        to
                                                        <?= min($offset + $limit, $total_records) ?>
                                                        of
                                                        <?= $total_records ?>
                                                        entries

                                                    </small>
                                                    <nav>
                                                        <ul class="pagination mb-0">

                                                            <!-- Previous Button -->
                                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                                <?php if ($page > 1) { ?>
                                                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                                                        &laquo;
                                                                    </a>
                                                                <?php } else { ?>
                                                                    <span class="page-link">&laquo;</span>
                                                                <?php } ?>
                                                            </li>

                                                            <!-- Page Numbers -->
                                                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                                    <a class="page-link" href="?page=<?= $i ?>">
                                                                        <?= $i ?>
                                                                    </a>
                                                                </li>
                                                            <?php } ?>

                                                            <!-- Next Button -->
                                                            <li
                                                                class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                                <?php if ($page < $total_pages) { ?>
                                                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                                                        &raquo;
                                                                    </a>
                                                                <?php } else { ?>
                                                                    <span class="page-link">&raquo;</span>
                                                                <?php } ?>
                                                            </li>

                                                        </ul>
                                                    </nav>
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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    </script>
</body>

</html>