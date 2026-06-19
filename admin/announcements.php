<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];



if (isset($_POST['delete_announcement'])) {

    $announcement_id = (int) $_POST['announcement_id'];

    mysqli_query(
        $conn,
        "DELETE FROM announcements
         WHERE announcement_id = $announcement_id"
    );

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
             'Deleted Announcement',
             'announcements',
             $announcement_id
         )"
    );

    header("Location: announcements.php");
    exit();
}


if (isset($_POST['update_announcement'])) {

    $announcement_id = (int) $_POST['announcement_id'];
    $title = trim($_POST['title']);
    $announcement_type = trim($_POST['announcement_type']);
    $content = trim($_POST['content']);

    // Validation
    if (empty($title)) {
        echo "<script>alert('Title cannot be empty.'); window.history.back();</script>";
        exit();
    }

    if (empty($content)) {
        echo "<script>alert('Content cannot be empty.'); window.history.back();</script>";
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE announcements
         SET title=?, announcement_type=?, content=?
         WHERE announcement_id=?"
    );

    mysqli_stmt_bind_param($stmt, "sssi", $title, $announcement_type, $content, $announcement_id);
    mysqli_stmt_execute($stmt);

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
             'Updated Announcement',
             'announcements',
             $announcement_id
         )"
    );

    header("Location: announcements.php");
    exit();
}


if (isset($_POST['add_announcement'])) {

    $title = trim($_POST['title']);
    $announcement_type = trim($_POST['announcement_type']);
    $content = trim($_POST['content']);
    $user_id = (int) $_SESSION['user_id'];

    // Validation
    if (empty($title)) {
        echo "<script>alert('Title cannot be empty.'); window.history.back();</script>";
        exit();
    }

    if (empty($content)) {
        echo "<script>alert('Content cannot be empty.'); window.history.back();</script>";
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO announcements
         (title, announcement_type, content,created_by)
         VALUES
         (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "sssi", $title, $announcement_type, $content, $user_id);
    mysqli_stmt_execute($stmt);

    $new_id = mysqli_insert_id($conn);

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
             $user_id,
             'Added Announcement',
             'announcements',
             $new_id
         )"
    );

    header("Location: announcements.php");
    exit();
}



$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;


$count_sql = "
SELECT COUNT(*) AS total
FROM announcements
WHERE title LIKE '%$search%'
OR content LIKE '%$search%'
OR announcement_type LIKE '%$search%'
";

$count_query = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_query)['total'];
$total_pages = max(1, ceil($total_records / $limit));

$sql = "
SELECT
    announcements.*,
    users.fullname AS created_by_name
FROM announcements

LEFT JOIN users
    ON announcements.created_by = users.user_id

WHERE
    announcements.title LIKE '%$search%'
    OR announcements.content LIKE '%$search%'
    OR announcements.announcement_type LIKE '%$search%'
    

ORDER BY announcements.announcement_id DESC

LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);


$total_ann = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM announcements")
)['total'];

$this_month_ann = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total FROM announcements
      WHERE MONTH(created_at)=MONTH(NOW())
      AND YEAR(created_at)=YEAR(NOW())"
    )
)['total'];

$latest_ann = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT title FROM announcements ORDER BY created_at DESC LIMIT 1")
);

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

        .users-table td[data-label="Action"] {
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .users-table td[data-label="Action"]::before {
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
    }

    .modal-body {
        background: #f8f9fa;
    }

    .content-preview {
        max-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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

                        <a href="users.php" class="sidebar-btn">
                            <i class="bi bi-person-lines-fill"></i>
                            <span> Users</span>
                        </a>

                        <a href="audit-logs.php" class="sidebar-btn">
                            <i class="bi bi-clock-history"></i>
                            <span>Audit Logs</span>
                        </a>
                        <a href="archive.php" class="sidebar-btn">
                            <i class="bi bi-archive-fill"></i>
                            <span>Archive</span>
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
                    <hr class="sidebar-divider">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">

                            Logout

                        </a>
                    </div>
                </div>
            </div>


            <!-- MAIN CONTENT -->
            <div class="col-12 col-xl-10 p-3 p-xl-4">
                <div class="row g-0">

                    <!-- PAGE HEADER -->
                    <div class="col-12 col-xl-12 p-2 mt-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-4 me-3">
                                <div>
                                    <h2 class="fw-bold"><?php echo $_SESSION['fullname']; ?></h2>
                                    <h5>Announcement Management</h5>
                                </div>

                                <div class="role-access-card">
                                    <div class="role-box">
                                        <div class="icon-circle role-icon">
                                            <i class="bi bi-shield-lock"></i>
                                        </div>
                                        <div>
                                            <small class="label-text">Current Role</small>
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
                                            <small class="label-text">Access Level</small>
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

                    <!-- STATS CARDS -->
                    <div class="col-12 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-danger-subtle p-3">
                                        <i class="bi bi-megaphone text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Total Announcements</small>
                                    <h4 class="mb-0 fw-bold"><?= $total_ann; ?></h4>
                                    <small class="text-muted">All records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-calendar-check text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>

                                    <small class="text-muted">This Month</small>
                                    <h4 class="mb-0 fw-bold"><?= $this_month_ann; ?></h4>
                                    <small class="text-muted"><?= date('F Y'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-warning-subtle p-3">
                                        <i class="bi bi-bell text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Latest Announcement</small>
                                    <h4 class="mb-0 fw-bold"
                                        style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= $latest_ann ? htmlspecialchars($latest_ann['title']) : 'None yet'; ?>
                                    </h4>
                                    <small class="text-muted">Most recent</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- end stats row -->

                <div class="row g-0">

                    <!-- ADD BUTTON -->
                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <button class="btn text-light px-4 py-2 shadow border border-gray"
                            style="background-color: rgb(134,9,9);font-size:16px; font-weight:600; border-radius:10px;"
                            data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg"></i>
                            Add Announcement
                        </button>
                    </div>

                    <!-- SEARCH -->
                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center me-3">
                            <div class="text-muted">
                                Showing <?= mysqli_num_rows($query); ?> entries
                            </div>
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Search announcement..." value="<?= htmlspecialchars($search) ?>"
                                    style="width:250px;">
                                <button type="submit" class="btn text-light" style="background-color: rgb(134,9,9);">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="announcements.php" class="btn btn-secondary">Clear</a>
                            </form>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="col-12 col-xl-12 p-2">
                        <div class="d-flex justify-content-between mb-3 me-3">
                            <div class="flex-grow-1">
                                <div class="card page-card shadow border border-gray">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table users-table align-middle table-striped">

                                                <thead class="pup-header text-center">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Title</th>
                                                        <th>Type</th>
                                                        <th>Content</th>
                                                        <th>Created By</th>
                                                        <th>Date Posted</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                                                        <tr>
                                                            <td><?= $row['announcement_id']; ?></td>
                                                            <td><?= htmlspecialchars($row['title']); ?></td>
                                                            <td>

                                                                <?php
                                                                $type = $row['announcement_type'];

                                                                if ($type == 'Budget') {
                                                                    echo '<span class="badge bg-success">Budget</span>';
                                                                } elseif ($type == 'Project') {
                                                                    echo '<span class="badge bg-primary">Project</span>';
                                                                } elseif ($type == 'Voting') {
                                                                    echo '<span class="badge bg-warning text-dark">Voting</span>';
                                                                } elseif ($type == 'Report') {
                                                                    echo '<span class="badge bg-info">Report</span>';
                                                                } elseif ($type == 'Emergency') {
                                                                    echo '<span class="badge bg-danger">Emergency</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-secondary">General</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <span class="content-preview">
                                                                    <?= htmlspecialchars($row['content']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['created_by_name'] ?? 'N/A'); ?>
                                                            </td>
                                                            <td>
                                                                <?= date('M d, Y', strtotime($row['created_at'])); ?>
                                                            </td>

                                                            <td>
                                                                <div class="btn-group">

                                                                    <!-- VIEW BUTTON -->
                                                                    <button class="btn action-btn btn-info text-white me-2"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#viewModal<?= $row['announcement_id']; ?>">
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>

                                                                    <!-- EDIT BUTTON -->
                                                                    <button class="btn action-btn text-dark me-2"
                                                                        style="background-color:#FFC72C;"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editModal<?= $row['announcement_id']; ?>">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>

                                                                    <!-- DELETE BUTTON -->
                                                                    <button class="btn action-btn text-light"
                                                                        style="background-color:rgb(134,9,9);"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteModal<?= $row['announcement_id']; ?>">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>

                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>

                                            </table>

                                            <?php
                                            // RESET pointer for modals
                                            mysqli_data_seek($query, 0);
                                            while ($row = mysqli_fetch_assoc($query)) {
                                            ?>

                                                <!-- VIEW MODAL -->
                                                <div class="modal fade" id="viewModal<?= $row['announcement_id']; ?>"
                                                    tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow"
                                                            style="border-radius:16px; overflow:hidden;">
                                                            <div class="modal-header text-white"
                                                                style="background-color:rgb(134,9,9);">
                                                                <h5 class="modal-title fw-bold">
                                                                    <i class="bi bi-megaphone me-2"></i>
                                                                    Announcement Details
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <h5 class="fw-bold mb-3">
                                                                    <?= htmlspecialchars($row['title']); ?>
                                                                </h5>
                                                                <p class="text-muted" style="white-space: pre-wrap;">
                                                                    <?= htmlspecialchars($row['content']); ?>
                                                                </p>
                                                                <hr>
                                                                <small class="text-muted">
                                                                    <i class="bi bi-person me-1"></i>
                                                                    Posted by:
                                                                    <strong><?= htmlspecialchars($row['created_by_name'] ?? 'N/A'); ?></strong>
                                                                    &nbsp;|&nbsp;
                                                                    <i class="bi bi-clock me-1"></i>
                                                                    <?= date('F d, Y h:i A', strtotime($row['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                            <div class="mb-3">

                                                                <?php
                                                                $type = $row['announcement_type'];

                                                                if ($type == 'Budget') {
                                                                    echo '<span class="badge bg-success">Budget</span>';
                                                                } elseif ($type == 'Project') {
                                                                    echo '<span class="badge bg-primary">Project</span>';
                                                                } elseif ($type == 'Voting') {
                                                                    echo '<span class="badge bg-warning text-dark">Voting</span>';
                                                                } elseif ($type == 'Report') {
                                                                    echo '<span class="badge bg-info">Report</span>';
                                                                } elseif ($type == 'Emergency') {
                                                                    echo '<span class="badge bg-danger">Emergency</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-secondary">General</span>';
                                                                }
                                                                ?>

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

                                                <!-- EDIT MODAL -->
                                                <div class="modal fade" id="editModal<?= $row['announcement_id']; ?>"
                                                    tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow"
                                                            style="border-radius:16px; overflow:hidden;">
                                                            <div class="modal-header text-white"
                                                                style="background-color:rgb(134,9,9);">
                                                                <h5 class="modal-title fw-bold">
                                                                    <i class="bi bi-pencil-square me-2"></i>
                                                                    Edit Announcement
                                                                </h5>
                                                            </div>
                                                            <form method="POST">
                                                                <input type="hidden" name="announcement_id"
                                                                    value="<?= $row['announcement_id']; ?>">
                                                                <div class="modal-body p-4">
                                                                    <div class="card border-0">
                                                                        <div class="card-body">

                                                                            <div class="section-title">
                                                                                <div class="icon">
                                                                                    <i class="bi bi-megaphone"></i>
                                                                                </div>
                                                                                <h5>Announcement Information</h5>
                                                                            </div>

                                                                            <div class="mb-4">
                                                                                <label class="form-label fw-semibold">
                                                                                    Title
                                                                                </label>
                                                                                <input type="text" name="title"
                                                                                    class="form-control"
                                                                                    value="<?= htmlspecialchars($row['title']); ?>"
                                                                                    required>
                                                                            </div>
                                                                            <div class="mb-4">

                                                                                <label class="form-label fw-semibold">
                                                                                    Announcement Type
                                                                                </label>

                                                                                <select name="announcement_type"
                                                                                    class="form-select" required>

                                                                                    <option value="General"
                                                                                        <?= ($row['announcement_type'] == 'General') ? 'selected' : ''; ?>>
                                                                                        General
                                                                                    </option>

                                                                                    <option value="Budget"
                                                                                        <?= ($row['announcement_type'] == 'Budget') ? 'selected' : ''; ?>>
                                                                                        Budget
                                                                                    </option>

                                                                                    <option value="Project"
                                                                                        <?= ($row['announcement_type'] == 'Project') ? 'selected' : ''; ?>>
                                                                                        Project
                                                                                    </option>

                                                                                    <option value="Voting"
                                                                                        <?= ($row['announcement_type'] == 'Voting') ? 'selected' : ''; ?>>
                                                                                        Voting
                                                                                    </option>

                                                                                    <option value="Report"
                                                                                        <?= ($row['announcement_type'] == 'Report') ? 'selected' : ''; ?>>
                                                                                        Report
                                                                                    </option>

                                                                                    <option value="Emergency"
                                                                                        <?= ($row['announcement_type'] == 'Emergency') ? 'selected' : ''; ?>>
                                                                                        Emergency
                                                                                    </option>

                                                                                </select>

                                                                            </div>


                                                                            <div class="mb-4">
                                                                                <label class="form-label fw-semibold">
                                                                                    Content
                                                                                </label>
                                                                                <textarea name="content"
                                                                                    class="form-control" rows="6"
                                                                                    required><?= htmlspecialchars($row['content']); ?></textarea>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light border"
                                                                        data-bs-dismiss="modal">
                                                                        <i class="bi bi-x-lg"></i> Cancel
                                                                    </button>
                                                                    <button type="submit" name="update_announcement"
                                                                        class="btn text-white"
                                                                        style="background-color:rgb(134,9,9);">
                                                                        <i class="bi bi-check-circle"></i> Save Changes
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- DELETE MODAL -->
                                                <div class="modal fade" id="deleteModal<?= $row['announcement_id']; ?>"
                                                    tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Delete Announcement</h5>
                                                                <button type="button" class="btn-close btn-close-white"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <i
                                                                    class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                                                                <p class="mt-3">
                                                                    Are you sure you want to delete
                                                                    <strong><?= htmlspecialchars($row['title']); ?></strong>?
                                                                </p>
                                                                <small class="text-muted">
                                                                    This action cannot be undone.
                                                                </small>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <form method="POST">
                                                                    <input type="hidden" name="announcement_id"
                                                                        value="<?= $row['announcement_id']; ?>">
                                                                    <button type="submit" name="delete_announcement"
                                                                        class="btn btn-danger">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php } ?>

                                            <!-- PAGINATION -->
                                            <div class="d-flex justify-content-between align-items-center mt-5">
                                                <small class="text-muted">
                                                    Showing <?= $offset + 1 ?> to
                                                    <?= min($offset + $limit, $total_records) ?>
                                                    of <?= $total_records ?> entries
                                                </small>
                                                <nav>
                                                    <ul class="pagination mb-0">

                                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                            <?php if ($page > 1) { ?>
                                                                <a class="page-link"
                                                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                                                    &laquo;
                                                                </a>
                                                            <?php } else { ?>
                                                                <span class="page-link">&laquo;</span>
                                                            <?php } ?>
                                                        </li>

                                                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                                <a class="page-link"
                                                                    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                                                    <?= $i ?>
                                                                </a>
                                                            </li>
                                                        <?php } ?>

                                                        <li
                                                            class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                            <?php if ($page < $total_pages) { ?>
                                                                <a class="page-link"
                                                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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


    <div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header text-white" style="background-color:rgb(134,9,9);">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add New Announcement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="card border-0">
                            <div class="card-body">

                                <div class="section-title">
                                    <div class="icon">
                                        <i class="bi bi-megaphone"></i>
                                    </div>
                                    <h5>Announcement Information</h5>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Title</label>
                                    <input type="text" name="title" class="form-control"
                                        placeholder="Enter announcement title" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        Announcement Type
                                    </label>

                                    <select name="announcement_type" class="form-select" required>

                                        <option value="">Select Type</option>

                                        <option value="General">General</option>
                                        <option value="Budget">Budget</option>
                                        <option value="Project">Project</option>
                                        <option value="Voting">Voting</option>
                                        <option value="Report">Report</option>
                                        <option value="Emergency">Emergency</option>

                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Content</label>

                                    <textarea name="content" class="form-control" rows="6"
                                        placeholder="Enter announcement content..." required></textarea>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                        <button type="submit" name="add_announcement" class="btn text-white"
                            style="background-color:rgb(134,9,9);">
                            <i class="bi bi-check-circle"></i> Post Announcement
                        </button>
                    </div>
                </form>
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