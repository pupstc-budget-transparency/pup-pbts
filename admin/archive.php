<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];

$allowed_tables = [
    'budgets' => ['pk' => 'budget_id', 'label' => 'Budget', 'log_table' => 'budgets'],
    'projects' => ['pk' => 'project_id', 'label' => 'Project', 'log_table' => 'projects'],
    'expenditures' => ['pk' => 'expenditure_id', 'label' => 'Expenditure', 'log_table' => 'expenditures'],
];

if (isset($_POST['restore_record'])) {

    $table = $_POST['table'] ?? '';
    $record_id = (int) ($_POST['record_id'] ?? 0);

    if (isset($allowed_tables[$table]) && $record_id > 0) {

        $pk = $allowed_tables[$table]['pk'];

        mysqli_query(
            $conn,
            "UPDATE $table
             SET record_status='active'
             WHERE $pk = $record_id"
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
                'Restored {$allowed_tables[$table]['label']}',
                '$table',
                $record_id
            )"
        );

        // Optional: log into archive_logs if that table exists in your schema
        mysqli_query(
            $conn,
            "INSERT INTO archive_logs
            (
                user_id,
                table_name,
                record_id,
                action
            )
            VALUES
            (
                {$_SESSION['user_id']},
                '$table',
                $record_id,
                'Restored'
            )"
        );
    }

    header("Location: archive.php?type=" . urlencode($_GET['type'] ?? ''));
    exit();
}


$type = $_GET['type'] ?? '';   // '', 'budgets', 'projects', 'expenditures'
$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;


$archived = [];

// 1. BUDGETS
if ($type === '' || $type === 'budgets') {
    $q = mysqli_query(
        $conn,
        "SELECT budget_id, budget_title, fiscal_year, total_amount, updated_at, created_at
         FROM budgets
         WHERE record_status='archived'
         AND (budget_title LIKE '%$search%' OR fiscal_year LIKE '%$search%')
         ORDER BY budget_id DESC"
    );
    while ($r = mysqli_fetch_assoc($q)) {
        $archived[] = [
            'table' => 'budgets',
            'pk_value' => $r['budget_id'],
            'icon' => 'bi-wallet2',
            'type_label' => 'Budget',
            'badge_class' => 'bg-danger',
            'title' => $r['budget_title'],
            'subtitle' => 'FY ' . $r['fiscal_year'],
            'amount' => $r['total_amount'],
            'archived_date' => $r['updated_at'] ?? $r['created_at'],
        ];
    }
}

// 2. PROJECTS
if ($type === '' || $type === 'projects') {
    $q = mysqli_query(
        $conn,
        "SELECT project_id, project_code, project_title, allocated_budget, status, updated_at, created_at
         FROM projects
         WHERE record_status='archived'
         AND (project_title LIKE '%$search%' OR project_code LIKE '%$search%')
         ORDER BY project_id DESC"
    );
    while ($r = mysqli_fetch_assoc($q)) {
        $archived[] = [
            'table' => 'projects',
            'pk_value' => $r['project_id'],
            'icon' => 'bi-kanban',
            'type_label' => 'Project',
            'badge_class' => 'bg-primary',
            'title' => $r['project_title'],
            'subtitle' => $r['project_code'] . ' • ' . $r['status'],
            'amount' => $r['allocated_budget'],
            'archived_date' => $r['updated_at'] ?? $r['created_at'],
        ];
    }
}

// 3. EXPENDITURES
if ($type === '' || $type === 'expenditures') {
    $q = mysqli_query(
        $conn,
        "SELECT e.expenditure_id, e.reference_no, e.category, e.amount, e.status,
                e.updated_at, e.created_at, p.project_title
         FROM expenditures e
         LEFT JOIN projects p ON e.project_id = p.project_id
         WHERE e.record_status='archived'
         AND (e.reference_no LIKE '%$search%' OR e.category LIKE '%$search%')
         ORDER BY e.expenditure_id DESC"
    );
    while ($r = mysqli_fetch_assoc($q)) {
        $archived[] = [
            'table' => 'expenditures',
            'pk_value' => $r['expenditure_id'],
            'icon' => 'bi-receipt',
            'type_label' => 'Expenditure',
            'badge_class' => 'bg-success',
            'title' => $r['reference_no'] . ' — ' . $r['category'],
            'subtitle' => $r['project_title'] ?? '—',
            'amount' => $r['amount'],
            'archived_date' => $r['updated_at'] ?? $r['created_at'],
        ];
    }
}



usort($archived, function ($a, $b) {
    return strtotime($b['archived_date']) - strtotime($a['archived_date']);
});

$total_records = count($archived);
$total_pages = max(1, ceil($total_records / $limit));
$page_items = array_slice($archived, $offset, $limit);



$count_budgets = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM budgets WHERE record_status='archived'")
)['t'];

$count_projects = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM projects WHERE record_status='archived'")
)['t'];

$count_expenditures = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM expenditures WHERE record_status='archived'")
)['t'];

$total_archived = $count_budgets + $count_projects + $count_expenditures;

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC Admin Dashboard – Archive</title>
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
        position: relative;
    }

    .sidebar-btn span {
        opacity: 1;
        transform: translateX(0);
        transition: .3s;
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

    .page-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
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
        flex-shrink: 0;
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
        white-space: nowrap;
    }

    .users-table td {
        padding: 16px;
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

    .users-table tbody td {
        border-right: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .users-table tbody td:last-child,
    .pup-header th:last-child {
        border-right: none !important;
    }

    .action-btn {
        width: 35px;
        height: 35px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
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
        flex-shrink: 0;
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

    .filter-pill {
        padding: 7px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        border: 1.5px solid #e9ecef;
        background: #fff;
        color: #6b7280;
        text-decoration: none;
        display: inline-block;
        transition: .2s;
        white-space: nowrap;
    }

    .filter-pill:hover {
        border-color: rgb(134, 9, 9);
        color: rgb(134, 9, 9);
    }

    .filter-pill.active {
        background: rgb(134, 9, 9);
        border-color: rgb(134, 9, 9);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #bbb;
    }

    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #ddd;
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

        .stats-card .rounded-circle {
            width: 48px;
            height: 48px;
        }

        .stats-card h3 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 767.98px) {
        .archive-search-form {
            flex-direction: column !important;
            width: 100%;
        }

        .archive-search-form input,
        .archive-search-form button,
        .archive-search-form a {
            width: 100%;
        }

        .filter-pill-scroll {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 6px;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (max-width: 575.98px) {
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

        .users-table td[data-label="Action"] {
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .users-table td[data-label="Action"]::before {
            display: none;
        }

        .users-table .btn-group {
            width: 100%;
            justify-content: center;
        }
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
            <div class="col-12 col-xl-2" id="sidebarCol">
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

                    <a href="dashboard.php" class="sidebar-btn">
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

                    <a href="archive.php" class="sidebar-btn active">
                        <i class="bi bi-archive-fill"></i>
                        <span>Archive</span>
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
            <!-- END SIDEBAR -->

            <!-- MAIN CONTENT -->
            <div class="col-12 col-xl-10">
                <div class="row g-0">
                    <div class="col-12 p-2 mt-3">
                        <div class="flex-grow-1">
                            <div
                                class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-3">

                                <div>
                                    <h2 class="fw-bold"><?php echo $_SESSION['fullname']; ?></h2>
                                    <h5>Archive Management</h5>
                                </div>

                                <div class="role-access-card w-100 w-lg-auto">
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

                    <!-- STAT CARDS -->
                    <div class="col-6 col-md-6 col-lg-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-secondary-subtle p-3">
                                        <i class="bi bi-archive text-secondary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Total Archived</small>
                                    <h3 class="mb-0"><?= $total_archived; ?></h3>
                                    <small class="text-muted">All record types</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-6 col-lg-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-danger-subtle p-3">
                                        <i class="bi bi-wallet2 text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Archived Budgets</small>
                                    <h3 class="mb-0"><?= $count_budgets; ?></h3>
                                    <small class="text-muted">Budget records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-6 col-lg-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-primary-subtle p-3">
                                        <i class="bi bi-kanban text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Archived Projects</small>
                                    <h3 class="mb-0"><?= $count_projects; ?></h3>
                                    <small class="text-muted">Project records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-6 col-lg-3 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-receipt text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Archived Expenditures</small>
                                    <h3 class="mb-0"><?= $count_expenditures; ?></h3>
                                    <small class="text-muted">Expenditure records</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END STAT CARDS -->
                </div>

                <div class="row g-0">

                    <!-- FILTER PILLS + SEARCH -->
                    <div class="col-12 p-2 mt-3">
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">

                            <div class="filter-pill-scroll d-flex gap-2 flex-wrap">
                                <a href="?type=" class="filter-pill <?= $type === '' ? 'active' : '' ?>">All</a>
                                <a href="?type=budgets" class="filter-pill <?= $type === 'budgets' ? 'active' : '' ?>">
                                    <i class="bi bi-wallet2 me-1"></i>Budgets
                                </a>
                                <a href="?type=projects"
                                    class="filter-pill <?= $type === 'projects' ? 'active' : '' ?>">
                                    <i class="bi bi-kanban me-1"></i>Projects
                                </a>
                                <a href="?type=expenditures"
                                    class="filter-pill <?= $type === 'expenditures' ? 'active' : '' ?>">
                                    <i class="bi bi-receipt me-1"></i>Expenditures
                                </a>
                            </div>

                            <form method="GET" class="d-flex gap-2 archive-search-form">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                <input type="text" name="search" class="form-control" placeholder="Search archived..."
                                    value="<?= htmlspecialchars($search) ?>" style="width:250px;">
                                <button type="submit" class="btn text-light" style="background-color: rgb(134,9,9);">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="archive.php" class="btn btn-secondary">Clear</a>
                            </form>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="col-12 p-2">
                        <div class="card page-card shadow border border-gray">
                            <div class="card-body p-2 p-md-3 p-lg-4">

                                <?php if (empty($page_items)) { ?>
                                    <div class="empty-state">
                                        <i class="bi bi-archive"></i>
                                        <p class="fw-semibold mb-1">No archived records found</p>
                                        <small>Try a different filter or search term.</small>
                                    </div>
                                <?php } else { ?>

                                    <div class="table-responsive">
                                        <table class="table users-table align-middle table-striped">
                                            <thead class="pup-header text-center">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Title</th>
                                                    <th>Details</th>
                                                    <th>Amount</th>
                                                    <th>Archived Date</th>
                                                    <th width="120">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($page_items as $item) { ?>
                                                    <tr>
                                                        <td data-label="Type">
                                                            <span class="badge <?= $item['badge_class']; ?>">
                                                                <i class="bi <?= $item['icon']; ?> me-1"></i>
                                                                <?= $item['type_label']; ?>
                                                            </span>
                                                        </td>
                                                        <td data-label="Title"><?= htmlspecialchars($item['title']); ?></td>
                                                        <td data-label="Details"><?= htmlspecialchars($item['subtitle']); ?>
                                                        </td>
                                                        <td data-label="Amount">
                                                            ₱<?= number_format($item['amount'], 2); ?>
                                                        </td>
                                                        <td data-label="Archived Date">
                                                            <?= date('M d, Y', strtotime($item['archived_date'])); ?>
                                                        </td>
                                                        <td data-label="Action">
                                                            <button class="btn action-btn text-white"
                                                                style="background-color:#198754;" data-bs-toggle="modal"
                                                                data-bs-target="#restoreModal-<?= $item['table']; ?>-<?= $item['pk_value']; ?>"
                                                                title="Restore">
                                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- RESTORE CONFIRMATION MODALS -->
                                    <?php foreach ($page_items as $item) { ?>
                                        <div class="modal fade"
                                            id="restoreModal-<?= $item['table']; ?>-<?= $item['pk_value']; ?>" tabindex="-1"
                                            data-bs-backdrop="static" data-bs-keyboard="false">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header text-white" style="background-color:#198754;">
                                                        <h5 class="modal-title">Restore <?= $item['type_label']; ?></h5>
                                                        <button type="button" class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <i class="bi bi-arrow-counterclockwise text-success fs-1"></i>
                                                        <p class="mt-3">
                                                            Restore <strong><?= htmlspecialchars($item['title']); ?></strong>
                                                            back to active records?
                                                        </p>
                                                        <small class="text-muted">
                                                            It will reappear in the
                                                            <?= strtolower($item['type_label']); ?> management list.
                                                        </small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST">
                                                            <input type="hidden" name="table" value="<?= $item['table']; ?>">
                                                            <input type="hidden" name="record_id"
                                                                value="<?= $item['pk_value']; ?>">
                                                            <button type="submit" name="restore_record" class="btn text-white"
                                                                style="background-color:#198754;">
                                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <!-- PAGINATION -->
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
                                                            href="?page=<?= $page - 1 ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>">&laquo;</a>
                                                    <?php } else { ?>
                                                        <span class="page-link">&laquo;</span>
                                                    <?php } ?>
                                                </li>
                                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                        <a class="page-link"
                                                            href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php } ?>
                                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                    <?php if ($page < $total_pages) { ?>
                                                        <a class="page-link"
                                                            href="?page=<?= $page + 1 ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>">&raquo;</a>
                                                    <?php } else { ?>
                                                        <span class="page-link">&raquo;</span>
                                                    <?php } ?>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>

                                <?php } ?>

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