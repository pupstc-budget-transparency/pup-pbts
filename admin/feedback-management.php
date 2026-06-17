<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];


if (isset($_POST['update_status'])) {

    $feedback_id = (int) $_POST['feedback_id'];
    $status = $_POST['status'];

    $allowed = ['Pending', 'Reviewed', 'Resolved'];

    if (!in_array($status, $allowed)) {
        echo "<script>alert('Invalid status.'); window.history.back();</script>";
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE feedback SET status=? WHERE feedback_id=?"
    );

    mysqli_stmt_bind_param($stmt, "si", $status, $feedback_id);
    mysqli_stmt_execute($stmt);

    // Audit Log
    mysqli_query(
        $conn,
        "INSERT INTO audit_logs (user_id, action, table_name, record_id)
         VALUES ({$_SESSION['user_id']}, 'Updated Feedback Status', 'feedback', $feedback_id)"
    );

    header("Location: feedback-management.php");
    exit();
}


if (isset($_POST['delete_feedback'])) {

    $feedback_id = (int) $_POST['feedback_id'];

    mysqli_query($conn, "DELETE FROM feedback WHERE feedback_id = $feedback_id");

    mysqli_query(
        $conn,
        "INSERT INTO audit_logs (user_id, action, table_name, record_id)
         VALUES ({$_SESSION['user_id']}, 'Deleted Feedback', 'feedback', $feedback_id)"
    );

    header("Location: feedback-management.php");
    exit();
}


$search = '';
$filter_status = '';

if (isset($_GET['search']))
    $search = mysqli_real_escape_string($conn, $_GET['search']);
if (isset($_GET['filter_status']))
    $filter_status = mysqli_real_escape_string($conn, $_GET['filter_status']);

$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($search)
    $where .= " AND (u.fullname LIKE '%$search%' OR f.message LIKE '%$search%')";
if ($filter_status)
    $where .= " AND f.status = '$filter_status'";


$total_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback"))['t'];
$pending_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE status='Pending'"))['t'];
$reviewed_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE status='Reviewed'"))['t'];
$resolved_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE status='Resolved'"))['t'];


$count_sql = "
    SELECT COUNT(*) AS total
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.user_id
    $where
";

$total_records = mysqli_fetch_assoc(mysqli_query($conn, $count_sql))['total'];
$total_pages = max(1, ceil($total_records / $limit));


$sql = "
    SELECT f.*, u.fullname, u.email
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.user_id
    $where
    ORDER BY
        CASE f.status WHEN 'Pending' THEN 1 WHEN 'Reviewed' THEN 2 ELSE 3 END,
        f.feedback_id DESC
    LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Feedback Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
        position: relative;
    }

    .sidebar-btn:hover,
    .sidebar-btn.active {
        background: white;
        color: #7a0000;
        transform: translateX(20px);
        border-radius: 30px 0 0 30px;
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
        overflow-y: hidden;
        overflow-x: hidden;
        background: linear-gradient(180deg, #6b0000, #3d0000);
        scrollbar-width: none;
        -ms-overflow-style: none;
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

    .stats-card .rounded-circle {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .users-table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 14px;
        overflow: hidden;
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
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #ececec;
    }

    .users-table tbody tr:hover {
        background: #f8f9fa;
    }

    .pup-header th {
        background-color: rgb(134, 9, 9) !important;
        color: white !important;
        border-right: 1px solid grey !important;
        border-bottom: 1px solid grey !important;
    }

    .pup-header th:last-child {
        border-right: none !important;
    }

    .users-table tbody td {
        border-right: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .users-table tbody td:last-child {
        border-right: none !important;
    }

    .badge-pending {
        background: #fff3cd;
        color: #856404;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-reviewed {
        background: #cfe2ff;
        color: #084298;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-resolved {
        background: #d1e7dd;
        color: #0a3622;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .action-btn {
        width: 35px;
        height: 35px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
    }

    .msg-preview {
        max-width: 260px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
        overflow: hidden;
    }

    .modal-body {
        background: #f8f9fa;
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


    .msg-bubble {
        background: #fff;
        border-left: 4px solid rgb(134, 9, 9);
        border-radius: 0 12px 12px 0;
        padding: 16px 20px;
        font-size: 15px;
        line-height: 1.7;
        color: #333;
        white-space: pre-wrap;
    }

    .avatar-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8B0000, #c0392b);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 18px;
        flex-shrink: 0;
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

            <!-- SIDEBAR -->
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5" id="mainSidebar">
                    <div class="sidebar-header text-white mb-3">
                        <div class="d-flex align-items-center">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/Polytechnic_University_of_the_Philippines.svg/960px-Polytechnic_University_of_the_Philippines.svg.png"
                                alt="PUP Logo" class="sidebar-logo">
                            <div class="ms-2">
                                <div class="sidebar-title">PUPSTC</div>
                                <div class="sidebar-subtitle">Participatory Budget<br>Transparency System</div>
                            </div>
                        </div>
                        <hr class="sidebar-divider">
                    </div>

                    <?php if ($role == 'super_admin') { ?>
                        <a href="dashboard.php" class="sidebar-btn"><i
                                class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                        <a href="budget-management.php" class="sidebar-btn"><i
                                class="bi bi-wallet2"></i><span>Budget</span></a>
                        <a href="expenditures.php" class="sidebar-btn"><i
                                class="bi bi-wallet2"></i><span>Expenditures</span></a>
                        <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                        <a href="reports.php" class="sidebar-btn"><i
                                class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
                        <a href="feedback-management.php" class="sidebar-btn active"><i
                                class="bi bi-envelope-paper"></i><span>Feedback</span></a>
                        <a href="voting-management.php" class="sidebar-btn"><i
                                class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                        <a href="users.php" class="sidebar-btn"><i
                                class="bi bi-person-lines-fill"></i><span>Users</span></a>
                        <a href="audit-logs.php" class="sidebar-btn"><i class="bi bi-clock-history"></i><span>Audit
                                Logs</span></a>
                        <a href="announcements.php" class="sidebar-btn"><i
                                class="bi bi-megaphone-fill"></i><span>Announcements</span></a>
                    <?php } ?>

                    <?php if ($role == 'student_affairs') { ?>
                        <a href="dashboard.php" class="sidebar-btn">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                        <a href="users.php" class="sidebar-btn"><i class="bi bi-mortarboard"></i><span>Students</span></a>
                        <a href="feedback-management.php" class="sidebar-btn active"><i
                                class="bi bi-envelope-paper"></i><span>Feedback</span></a>
                        <a href="announcements.php" class="sidebar-btn"><i
                                class="bi bi-megaphone-fill"></i><span>Announcements</span></a>
                    <?php } ?>

                    <?php if ($role == 'budget_officer') { ?>
                        <a href="dashboard.php" class="sidebar-btn"><i
                                class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                        <a href="budget-management.php" class="sidebar-btn"><i
                                class="bi bi-wallet2"></i><span>Budget</span></a>
                        <a href="expenditures.php" class="sidebar-btn"><i
                                class="bi bi-wallet2"></i><span>Expenditures</span></a>
                        <a href="reports.php" class="sidebar-btn"><i
                                class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
                    <?php } ?>

                    <?php if ($role == 'project_coordinator') { ?>
                        <a href="dashboard.php" class="sidebar-btn"><i
                                class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                        <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                        <a href="voting-management.php" class="sidebar-btn"><i
                                class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                        <a href="reports.php" class="sidebar-btn"><i
                                class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
                    <?php } ?>

                    <hr class="sidebar-divider">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">Logout</a>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div class="col-12 col-xl-10">
                <div class="row g-0">


                    <div class="col-12 p-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-4 me-3 page-header-row">
                            <div>
                                <h2 class="fw-bold"><?= $_SESSION['fullname']; ?></h2>
                                <h5>Feedback Management</h5>
                            </div>
                            <div class="role-access-card">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-circle role-icon"><i class="bi bi-shield-lock"></i></div>
                                    <div>
                                        <small class="label-text">Current Role</small>
                                        <div class="role-value"><?= strtoupper(str_replace('_', ' ', $role)); ?></div>
                                    </div>
                                </div>
                                <div class="divider"></div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-circle access-icon"><i class="bi bi-shield-check"></i></div>
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
                    </div>

                    <!-- STATS -->
                    <div class="col-6 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-secondary-subtle p-3"><i
                                            class="bi bi-envelope-paper text-secondary fs-4"></i></div>
                                </div>
                                <div>
                                    <small class="text-muted">Total Feedback</small>
                                    <h3 class="mb-0"><?= $total_fb; ?></h3>
                                    <small class="text-muted">All submissions</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-warning-subtle p-3"><i
                                            class="bi bi-hourglass-split text-warning fs-4"></i></div>
                                </div>
                                <div>
                                    <small class="text-muted">Pending</small>
                                    <h3 class="mb-0"><?= $pending_fb; ?></h3>
                                    <small class="text-muted">Needs attention</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-primary-subtle p-3"><i
                                            class="bi bi-eye text-primary fs-4"></i></div>
                                </div>
                                <div>
                                    <small class="text-muted">Reviewed</small>
                                    <h3 class="mb-0"><?= $reviewed_fb; ?></h3>
                                    <small class="text-muted">Under review</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-check-circle text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Resolved</small>
                                    <h3 class="mb-0"><?= $resolved_fb; ?></h3>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- SEARCH / FILTER -->
                <div class="row g-0 px-2 mb-3 me-3">
                    <div class="col-12">
                        <form method="GET" class="d-flex gap-2 flex-wrap justify-content-end">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search student or message..." value="<?= htmlspecialchars($search) ?>"
                                style="width:240px;">
                            <select name="filter_status" class="form-select" style="width:160px;">
                                <option value="">All Status</option>
                                <option value="Pending" <?= ($filter_status == 'Pending') ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="Reviewed" <?= ($filter_status == 'Reviewed') ? 'selected' : '' ?>>Reviewed
                                </option>
                                <option value="Resolved" <?= ($filter_status == 'Resolved') ? 'selected' : '' ?>>Resolved
                                </option>
                            </select>
                            <button type="submit" class="btn text-light" style="background-color:rgb(134,9,9);">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="feedback-management.php" class="btn btn-secondary">Clear</a>
                        </form>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="row g-0">
                    <div class="col-12 p-2 me-3">
                        <div class="card page-card shadow border border-gray">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table users-table align-middle table-striped">
                                        <thead class="pup-header text-center">
                                            <tr>
                                                <th>#</th>
                                                <th>Student</th>
                                                <th>Message</th>
                                                <th>Status</th>
                                                <th>Date Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($query) == 0) { ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-5">
                                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                        No feedback found.
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                                                <tr>
                                                    <td class="text-center" data-label="#"><?= $row['feedback_id']; ?></td>
                                                    <td data-label="Student">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="avatar-circle"
                                                                style="width:36px;height:36px;font-size:14px;">
                                                                <?= strtoupper(substr($row['fullname'] ?? 'U', 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold" style="font-size:14px;">
                                                                    <?= htmlspecialchars($row['fullname'] ?? 'Unknown'); ?>
                                                                </div>
                                                                <small
                                                                    class="text-muted"><?= htmlspecialchars($row['email'] ?? ''); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td data-label="Message">
                                                        <span
                                                            class="msg-preview"><?= htmlspecialchars($row['message']); ?></span>
                                                    </td>
                                                    <td class="text-center" data-label="Status">
                                                        <?php if ($row['status'] == 'Pending') { ?>
                                                            <span class="badge-pending"><i
                                                                    class="bi bi-clock me-1"></i>Pending</span>
                                                        <?php } elseif ($row['status'] == 'Reviewed') { ?>
                                                            <span class="badge-reviewed"><i
                                                                    class="bi bi-eye me-1"></i>Reviewed</span>
                                                        <?php } else { ?>
                                                            <span class="badge-resolved"><i
                                                                    class="bi bi-check-circle me-1"></i>Resolved</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td data-label="Date Submitted"><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                    <td data-label="Actions">
                                                        <div class="d-flex gap-1">
                                                            <button class="btn action-btn btn-info text-white"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#viewModal<?= $row['feedback_id']; ?>"
                                                                title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn action-btn text-dark"
                                                                style="background-color:#FFC72C;" data-bs-toggle="modal"
                                                                data-bs-target="#statusModal<?= $row['feedback_id']; ?>"
                                                                title="Update Status">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>
                                                            <button class="btn action-btn text-white"
                                                                style="background-color:rgb(134,9,9);"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal<?= $row['feedback_id']; ?>"
                                                                title="Delete">
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
                                        $initial = strtoupper(substr($row['fullname'] ?? 'U', 0, 1));
                                    ?>

                                        <!-- VIEW MODAL -->
                                        <div class="modal fade" id="viewModal<?= $row['feedback_id']; ?>" tabindex="-1"
                                            data-bs-backdrop="static">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header text-white"
                                                        style="background-color:rgb(134,9,9);">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-envelope-open me-2"></i>Feedback Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <!-- Student info -->
                                                        <div class="d-flex align-items-center gap-3 mb-4">
                                                            <div class="avatar-circle"><?= $initial; ?></div>
                                                            <div>
                                                                <div class="fw-bold fs-5">
                                                                    <?= htmlspecialchars($row['fullname'] ?? 'Unknown'); ?>
                                                                </div>
                                                                <small
                                                                    class="text-muted"><?= htmlspecialchars($row['email'] ?? ''); ?></small>
                                                            </div>
                                                            <div class="ms-auto">
                                                                <?php if ($row['status'] == 'Pending') { ?>
                                                                    <span class="badge-pending"><i
                                                                            class="bi bi-clock me-1"></i>Pending</span>
                                                                <?php } elseif ($row['status'] == 'Reviewed') { ?>
                                                                    <span class="badge-reviewed"><i
                                                                            class="bi bi-eye me-1"></i>Reviewed</span>
                                                                <?php } else { ?>
                                                                    <span class="badge-resolved"><i
                                                                            class="bi bi-check-circle me-1"></i>Resolved</span>
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                        <div class="msg-bubble"><?= htmlspecialchars($row['message']); ?>
                                                        </div>
                                                        <hr class="mt-4">
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock me-1"></i>
                                                            Submitted on
                                                            <?= date('F d, Y \a\t h:i A', strtotime($row['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light border"
                                                            data-bs-dismiss="modal">
                                                            <i class="bi bi-x-lg"></i> Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- STATUS MODAL -->
                                        <div class="modal fade" id="statusModal<?= $row['feedback_id']; ?>" tabindex="-1"
                                            data-bs-backdrop="static">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header text-white"
                                                        style="background-color:rgb(134,9,9);">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-pencil-square me-2"></i>Update Status
                                                        </h5>
                                                    </div>
                                                    <form method="POST">
                                                        <input type="hidden" name="feedback_id"
                                                            value="<?= $row['feedback_id']; ?>">
                                                        <div class="modal-body p-4">
                                                            <p class="text-muted mb-3">
                                                                Updating status for feedback from
                                                                <strong><?= htmlspecialchars($row['fullname'] ?? 'Unknown'); ?></strong>
                                                            </p>
                                                            <label class="form-label fw-semibold">Select New Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="Pending" <?= ($row['status'] == 'Pending') ? 'selected' : '' ?>>⏳ Pending</option>
                                                                <option value="Reviewed" <?= ($row['status'] == 'Reviewed') ? 'selected' : '' ?>>👁️ Reviewed</option>
                                                                <option value="Resolved" <?= ($row['status'] == 'Resolved') ? 'selected' : '' ?>>✅ Resolved</option>
                                                            </select>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light border"
                                                                data-bs-dismiss="modal">
                                                                <i class="bi bi-x-lg"></i> Cancel
                                                            </button>
                                                            <button type="submit" name="update_status"
                                                                class="btn text-white"
                                                                style="background-color:rgb(134,9,9);">
                                                                <i class="bi bi-check-circle"></i> Save Status
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- DELETE MODAL -->
                                        <div class="modal fade" id="deleteModal<?= $row['feedback_id']; ?>" tabindex="-1"
                                            data-bs-backdrop="static">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Delete Feedback</h5>
                                                        <button type="button" class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center p-4">
                                                        <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                                                        <p class="mt-3">Are you sure you want to permanently delete the
                                                            feedback from
                                                            <strong><?= htmlspecialchars($row['fullname'] ?? 'this student'); ?></strong>?
                                                        </p>
                                                        <small class="text-muted">This action cannot be undone.</small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST">
                                                            <input type="hidden" name="feedback_id"
                                                                value="<?= $row['feedback_id']; ?>">
                                                            <button type="submit" name="delete_feedback"
                                                                class="btn btn-danger">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php } ?>

                                    <div class="d-flex justify-content-between align-items-center mt-4 pagination-wrap">
                                        <small class="text-muted">
                                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?>
                                            of <?= $total_records ?> entries
                                        </small>
                                        <nav>
                                            <ul class="pagination mb-0">
                                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                    <?php if ($page > 1) { ?>
                                                        <a class="page-link"
                                                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>">&laquo;</a>
                                                    <?php } else { ?><span class="page-link">&laquo;</span><?php } ?>
                                                </li>
                                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                        <a class="page-link"
                                                            href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php } ?>
                                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                    <?php if ($page < $total_pages) { ?>
                                                        <a class="page-link"
                                                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_status=<?= urlencode($filter_status) ?>">&raquo;</a>
                                                    <?php } else { ?><span class="page-link">&raquo;</span><?php } ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

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