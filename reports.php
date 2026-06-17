<?php

require '../includes/config.php';
require '../includes/auth.php';

$role = $_SESSION['role'];

$total_reports = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM reports")
)['t'];

$reports_this_month = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) t FROM reports
         WHERE MONTH(created_at)=MONTH(CURDATE())
         AND YEAR(created_at)=YEAR(CURDATE())"
    )
)['t'];

$last_report = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT r.*, u.fullname FROM reports r
         LEFT JOIN users u ON r.generated_by = u.user_id
         ORDER BY r.created_at DESC LIMIT 1"
    )
);


$student_visible_types = ['Budget Summary', 'Expenditure Summary', 'Project Status', 'Voting Results'];

$filter_type = isset($_GET['filter_type']) ? mysqli_real_escape_string($conn, $_GET['filter_type']) : '';
$filter_from = isset($_GET['filter_from']) ? $_GET['filter_from'] : '';
$filter_to = isset($_GET['filter_to']) ? $_GET['filter_to'] : '';

$visible_list_sql = "'" . implode("','", $student_visible_types) . "'";

$where_clauses = ["r.report_type IN ($visible_list_sql)"];

if ($filter_type && in_array($filter_type, $student_visible_types)) {
    $where_clauses[] = "r.report_type = '$filter_type'";
}
if ($filter_from) {
    $where_clauses[] = "DATE(r.created_at) >= '$filter_from'";
}
if ($filter_to) {
    $where_clauses[] = "DATE(r.created_at) <= '$filter_to'";
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

$reports_query = mysqli_query(
    $conn,
    "SELECT r.*, u.fullname AS generated_by_name
     FROM reports r
     LEFT JOIN users u ON r.generated_by = u.user_id
     $where_sql
     ORDER BY r.created_at DESC"
);


$preview_data = [];
$preview_report = null;

if (isset($_GET['preview'])) {

    $preview_id = (int) $_GET['preview'];
    $p_from = isset($_GET['from']) ? mysqli_real_escape_string($conn, $_GET['from']) : date('Y-01-01');
    $p_to = isset($_GET['to']) ? mysqli_real_escape_string($conn, $_GET['to']) : date('Y-m-d');

    $preview_report = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT r.*, u.fullname AS generated_by_name
             FROM reports r
             LEFT JOIN users u ON r.generated_by = u.user_id
             WHERE r.report_id = $preview_id"
        )
    );


    if ($preview_report && !in_array($preview_report['report_type'], $student_visible_types)) {
        $preview_report = null;
    }

    if ($preview_report) {

        $rtype = $preview_report['report_type'];

        if ($rtype == 'Budget Summary') {
            $preview_data = mysqli_query(
                $conn,
                "SELECT budget_title, fiscal_year, total_amount, record_status, created_at
                 FROM budgets
                 WHERE DATE(created_at) BETWEEN '$p_from' AND '$p_to'
                 ORDER BY created_at DESC"
            );
        } elseif ($rtype == 'Expenditure Summary') {
            $preview_data = mysqli_query(
                $conn,
                "SELECT e.reference_no, e.category, e.description, e.amount,
                        e.expenditure_date, e.status, p.project_title
                 FROM expenditures e
                 LEFT JOIN projects p ON e.project_id = p.project_id
                 WHERE DATE(e.expenditure_date) BETWEEN '$p_from' AND '$p_to'
                 ORDER BY e.expenditure_date DESC"
            );
        } elseif ($rtype == 'Project Status') {
            $preview_data = mysqli_query(
                $conn,
                "SELECT project_code, project_title, status, allocated_budget,
                        start_date, end_date, record_status
                 FROM projects
                 WHERE DATE(created_at) BETWEEN '$p_from' AND '$p_to'
                 ORDER BY created_at DESC"
            );
        } elseif ($rtype == 'Voting Results') {
            $preview_data = mysqli_query(
                $conn,
                "SELECT vs.title AS session_title, vs.status,
                        p.project_code, p.project_title,
                        COUNT(v.vote_id) AS vote_count
                 FROM voting_sessions vs
                 LEFT JOIN voting_session_projects vsp ON vs.session_id = vsp.session_id
                 LEFT JOIN projects p ON vsp.project_id = p.project_id
                 LEFT JOIN votes v ON v.session_id = vs.session_id AND v.project_id = p.project_id
                 WHERE DATE(vs.start_date) BETWEEN '$p_from' AND '$p_to'
                 GROUP BY vs.session_id, p.project_id
                 ORDER BY vs.session_id DESC, vote_count DESC"
            );
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- SheetJS for Excel/CSV export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- jsPDF + AutoTable for PDF export -->
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
</head>

<style>
    body {
        background: #f5f7fb;
    }

    .sidebar {
        position: sticky;
        top: 56px;
        left: 0;
        width: 200px;
        height: calc(100vh - 56px);
        background: linear-gradient(180deg, #6b0000, #3d0000);
        overflow-y: hidden;
        overflow-x: hidden;
        scrollbar-width: none;
    }

    .sidebar:hover {
        overflow-y: auto;
    }

    .sidebar::-webkit-scrollbar {
        display: none;
    }

    .sidebar-logo {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .sidebar-title {
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }

    .sidebar-subtitle {
        font-size: 10px;
        color: rgba(255, 255, 255, .8);
    }

    .sidebar-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
        border-radius: 30px;
        text-decoration: none;
        color: white;
        background: transparent;
        transition: all .3s ease;
        position: relative;
        font-size: 14px;
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

    .info-banner {
        background: #e7f3ff;
        border: 1px solid #b6dcff;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 24px;
    }

    .info-banner i {
        font-size: 22px;
        color: #0d6efd;
        margin-top: 2px;
    }

    .info-banner p {
        margin: 0;
        font-size: 13.5px;
        color: #374151;
    }

    .users-table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .pup-header th {
        background-color: rgb(134, 9, 9) !important;
        color: white !important;
        border-right: 1px solid grey !important;
        border-bottom: 1px solid grey !important;
        padding: 14px 16px;
        font-weight: 600;
    }

    .pup-header th:last-child {
        border-right: none !important;
    }

    .users-table tbody td {
        padding: 13px 16px;
        vertical-align: middle;
        border-right: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .users-table tbody td:last-child {
        border-right: none !important;
    }

    .users-table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge-budget {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-expenditure {
        background: #fce7f3;
        color: #be185d;
    }

    .badge-project {
        background: #d1fae5;
        color: #047857;
    }

    .badge-voting {
        background: #ede9fe;
        color: #6d28d9;
    }

    .rtype-badge {
        font-size: 12px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 50px;
        display: inline-block;
    }

    .action-btn {
        width: 35px;
        height: 35px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
    }

    .preview-section {
        border: none;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        overflow: hidden;
        background: #fff;
    }

    .preview-header {
        background: linear-gradient(135deg, #1f2937, #111827);
        color: white;
        padding: 18px 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #8B0000;
        box-shadow: 0 0 0 .15rem rgba(139, 0, 0, .15);
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

    @media print {
        .no-print {
            display: none !important;
        }

        .preview-section {
            box-shadow: none;
            border-radius: 0;
        }
    }
</style>

<body>


    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color:rgb(134,9,9);">
        <div class="container-xl py-3 d-flex justify-content-between">
            <h6 class="mb-0">PUPSTC Participatory Budget Transparency System</h6>
            <span><strong><?= $_SESSION['fullname']; ?></strong></span>
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
                                <div class="sidebar-subtitle">Participatory Budget<br>Transparency System</div>
                            </div>
                        </div>
                        <hr class="sidebar-divider">
                    </div>

                    <a href="dashboard.php" class="sidebar-btn"><i
                            class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="budget_explore.php" class="sidebar-btn"><i class="bi bi-wallet2"></i><span>Budget
                            Explorer</span></a>
                    <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                    <a href="voting.php" class="sidebar-btn"><i
                            class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                    <a href="reports.php" class="sidebar-btn active"><i
                            class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
                    <a href="feedback.php" class="sidebar-btn"><i
                            class="bi bi-envelope-paper"></i><span>Feedback</span></a>
                    <a href="announcement.php" class="sidebar-btn"><i
                            class="bi bi-megaphone-fill"></i><span>Announcements</span></a>
                    <a href="notifications.php" class="sidebar-btn"><i
                            class="bi bi-bell-fill"></i><span>Notifications</span></a>

                    <hr class="sidebar-divider">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">Logout</a>
                    </div>
                </div>
            </div>


            <div class="col-12 col-xl-10 p-3 p-xl-4">

                <!-- Page header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['fullname']); ?></h2>
                        <h5 class="text-muted">Reports</h5>
                    </div>
                    <div class="role-access-card">
                        <div class="role-box">
                            <div class="icon-circle role-icon"><i class="bi bi-shield-lock"></i></div>
                            <div>
                                <small class="label-text">Current Role</small>
                                <div class="role-value">STUDENT</div>
                            </div>
                        </div>
                        <div class="divider"></div>
                        <div class="role-box">
                            <div class="icon-circle access-icon"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <small class="label-text">Access Level</small>
                                <div class="access-value" style="font-size:18px;">View &amp; Download Only</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info banner -->
                <div class="info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>
                        These reports are generated by the administration for transparency purposes.
                        You can view, filter, and download them, but new reports can only be
                        generated by authorized budget officers and administrators.
                    </p>
                </div>

                <div class="row g-3 mb-4">

                    <div class="col-12 col-md-4">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-danger-subtle p-3">
                                    <i class="bi bi-file-earmark-bar-graph text-danger fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Available Reports</small>
                                    <h3 class="mb-0">
                                        <?= mysqli_num_rows($reports_query) > 0 ? $total_reports : $total_reports; ?>
                                    </h3>
                                    <small class="text-muted">Published for viewing</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success-subtle p-3">
                                    <i class="bi bi-calendar-check text-success fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Published This Month</small>
                                    <h3 class="mb-0"><?= $reports_this_month; ?></h3>
                                    <small class="text-muted">New this month</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning-subtle p-3">
                                    <i class="bi bi-clock-history text-warning fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Most Recently Published</small>
                                    <h3 class="mb-0" style="font-size:15px; line-height:1.3;">
                                        <?= $last_report ? htmlspecialchars($last_report['report_title']) : '—'; ?>
                                    </h3>
                                    <small class="text-muted">
                                        <?= $last_report ? date('M d, Y', strtotime($last_report['created_at'])) : ''; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                <?php if ($preview_report && $preview_data) { ?>
                    <div class="preview-section mb-4" id="previewSection">
                        <div class="preview-header no-print">
                            <div>
                                <h5 class="mb-1 fw-bold">
                                    <i class="bi bi-eye me-2"></i>
                                    <?= htmlspecialchars($preview_report['report_title']); ?>
                                </h5>
                                <small style="opacity:.75;">
                                    Generated by
                                    <strong><?= htmlspecialchars($preview_report['generated_by_name'] ?? 'Admin'); ?></strong>
                                    on <?= date('F d, Y h:i A', strtotime($preview_report['created_at'])); ?>
                                </small>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-sm btn-light fw-semibold" onclick="exportPDF()">
                                    <i class="bi bi-file-pdf me-1 text-danger"></i> Export PDF
                                </button>
                                <button class="btn btn-sm btn-light fw-semibold" onclick="exportExcel()">
                                    <i class="bi bi-file-earmark-excel me-1 text-success"></i> Export Excel
                                </button>
                                <button class="btn btn-sm btn-light fw-semibold" onclick="exportCSV()">
                                    <i class="bi bi-filetype-csv me-1 text-primary"></i> Export CSV
                                </button>
                                <button class="btn btn-sm btn-light fw-semibold" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                        </div>

                        <div class="p-4 d-none d-print-block text-center border-bottom mb-3">
                            <h4 class="fw-bold">PUPSTC Participatory Budget Transparency System</h4>
                            <h5><?= htmlspecialchars($preview_report['report_title']); ?></h5>
                            <small>Generated by <?= htmlspecialchars($preview_report['generated_by_name'] ?? 'Admin'); ?>
                                on <?= date('F d, Y h:i A', strtotime($preview_report['created_at'])); ?></small>
                        </div>

                        <div class="p-4 table-responsive" id="previewTableWrap">
                            <table class="table users-table align-middle table-striped" id="previewTable">
                                <?php
                                $rtype = $preview_report['report_type'];

                                if ($rtype == 'Budget Summary') { ?>
                                    <thead class="pup-header text-center">
                                        <tr>
                                            <th>Budget Title</th>
                                            <th>Fiscal Year</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($r = mysqli_fetch_assoc($preview_data)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['budget_title']); ?></td>
                                                <td class="text-center"><?= $r['fiscal_year']; ?></td>
                                                <td class="text-end">₱<?= number_format($r['total_amount'], 2); ?></td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge <?= $r['record_status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?= ucfirst($r['record_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($r['created_at'])); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>

                                <?php } elseif ($rtype == 'Expenditure Summary') { ?>
                                    <thead class="pup-header text-center">
                                        <tr>
                                            <th>Ref No</th>
                                            <th>Category</th>
                                            <th>Project</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($r = mysqli_fetch_assoc($preview_data)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['reference_no']); ?></td>
                                                <td><?= htmlspecialchars($r['category']); ?></td>
                                                <td><?= htmlspecialchars($r['project_title'] ?? '—'); ?></td>
                                                <td><?= htmlspecialchars($r['description']); ?></td>
                                                <td class="text-end">₱<?= number_format($r['amount'], 2); ?></td>
                                                <td><?= date('M d, Y', strtotime($r['expenditure_date'])); ?></td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge <?= $r['status'] == 'Approved' ? 'bg-success' : ($r['status'] == 'Pending' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                                        <?= htmlspecialchars($r['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>

                                <?php } elseif ($rtype == 'Project Status') { ?>
                                    <thead class="pup-header text-center">
                                        <tr>
                                            <th>Code</th>
                                            <th>Project Title</th>
                                            <th>Allocated Budget</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($r = mysqli_fetch_assoc($preview_data)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['project_code']); ?></td>
                                                <td><?= htmlspecialchars($r['project_title']); ?></td>
                                                <td class="text-end">₱<?= number_format($r['allocated_budget'], 2); ?></td>
                                                <td><?= date('M d, Y', strtotime($r['start_date'])); ?></td>
                                                <td><?= date('M d, Y', strtotime($r['end_date'])); ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $sc = ['Planning' => 'bg-secondary', 'Ongoing' => 'bg-primary', 'Completed' => 'bg-success', 'Cancelled' => 'bg-danger'];
                                                    ?>
                                                    <span class="badge <?= $sc[$r['status']] ?? 'bg-secondary'; ?>">
                                                        <?= htmlspecialchars($r['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>

                                <?php } elseif ($rtype == 'Voting Results') { ?>
                                    <thead class="pup-header text-center">
                                        <tr>
                                            <th>Session</th>
                                            <th>Session Status</th>
                                            <th>Project Code</th>
                                            <th>Project Title</th>
                                            <th>Votes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($r = mysqli_fetch_assoc($preview_data)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['session_title']); ?></td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge <?= $r['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?= htmlspecialchars($r['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($r['project_code'] ?? '—'); ?></td>
                                                <td><?= htmlspecialchars($r['project_title'] ?? '—'); ?></td>
                                                <td class="text-center fw-bold"><?= $r['vote_count']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                <?php } elseif (isset($_GET['preview'])) { ?>
                    <div class="alert alert-warning rounded-3 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        That report is not available for viewing.
                    </div>
                <?php } ?>


                <div class="card page-card shadow border border-light mb-4 no-print">
                    <div class="card-body">

                        <!-- Filter bar -->
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-list-ul me-1"></i> Published Reports
                            </h6>
                            <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
                                <div>
                                    <label class="form-label mb-1" style="font-size:12px;">Type</label>
                                    <select name="filter_type" class="form-select form-select-sm" style="width:170px;">
                                        <option value="">All Types</option>
                                        <?php
                                        foreach ($student_visible_types as $t) {
                                            $sel = ($filter_type == $t) ? 'selected' : '';
                                            echo "<option value=\"$t\" $sel>$t</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label mb-1" style="font-size:12px;">From</label>
                                    <input type="date" name="filter_from" class="form-control form-control-sm"
                                        value="<?= htmlspecialchars($filter_from); ?>">
                                </div>
                                <div>
                                    <label class="form-label mb-1" style="font-size:12px;">To</label>
                                    <input type="date" name="filter_to" class="form-control form-control-sm"
                                        value="<?= htmlspecialchars($filter_to); ?>">
                                </div>
                                <button type="submit" class="btn btn-sm text-white"
                                    style="background-color:rgb(134,9,9); border-radius:8px;">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="reports.php" class="btn btn-sm btn-secondary" style="border-radius:8px;">
                                    Clear
                                </a>
                            </form>
                        </div>

                        <!-- Report table -->
                        <div class="table-responsive">
                            <table class="table users-table align-middle table-striped">
                                <thead class="pup-header text-center">
                                    <tr>
                                        <th>#</th>
                                        <th>Report Title</th>
                                        <th>Type</th>
                                        <th>Published By</th>
                                        <th>Date &amp; Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = 1;
                                    while ($rep = mysqli_fetch_assoc($reports_query)):
                                        $type_badge = match ($rep['report_type']) {
                                            'Budget Summary' => 'badge-budget',
                                            'Expenditure Summary' => 'badge-expenditure',
                                            'Project Status' => 'badge-project',
                                            'Voting Results' => 'badge-voting',
                                            default => 'bg-secondary text-white',
                                        };
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $counter++; ?></td>
                                            <td><strong><?= htmlspecialchars($rep['report_title']); ?></strong></td>
                                            <td>
                                                <span class="rtype-badge <?= $type_badge; ?>">
                                                    <?= htmlspecialchars($rep['report_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="bi bi-person-circle me-1 text-muted"></i>
                                                <?= htmlspecialchars($rep['generated_by_name'] ?? 'Admin'); ?>
                                            </td>
                                            <td>
                                                <span><?= date('M d, Y', strtotime($rep['created_at'])); ?></span><br>
                                                <small
                                                    class="text-muted"><?= date('h:i A', strtotime($rep['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">

                                                    <!-- View -->
                                                    <a href="reports.php?preview=<?= $rep['report_id']; ?>&from=<?= date('Y-01-01'); ?>&to=<?= date('Y-m-d'); ?>"
                                                        class="btn action-btn btn-info text-white" title="View Report">
                                                        <i class="bi bi-eye"></i>
                                                    </a>

                                                    <!-- Download PDF -->
                                                    <a href="reports.php?preview=<?= $rep['report_id']; ?>&from=<?= date('Y-01-01'); ?>&to=<?= date('Y-m-d'); ?>&dl=pdf"
                                                        class="btn action-btn btn-danger text-white" title="Download PDF">
                                                        <i class="bi bi-file-pdf"></i>
                                                    </a>

                                                    <!-- Download Excel -->
                                                    <a href="reports.php?preview=<?= $rep['report_id']; ?>&from=<?= date('Y-01-01'); ?>&to=<?= date('Y-m-d'); ?>&dl=excel"
                                                        class="btn action-btn text-white" style="background-color:#1d6f42;"
                                                        title="Download Excel">
                                                        <i class="bi bi-file-earmark-excel"></i>
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                    <?php if ($counter == 1) { ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class="bi bi-file-earmark-x"></i>
                                                    <p class="fw-semibold mb-1">No reports available</p>
                                                    <small>Check back later or adjust your filters.</small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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
        document.addEventListener('DOMContentLoaded', function() {
            const urlP = new URLSearchParams(window.location.search);
            if (urlP.get('dl') === 'pdf') exportPDF();
            if (urlP.get('dl') === 'excel') exportExcel();

            const preview = document.getElementById('previewSection');
            if (preview) {
                setTimeout(() => preview.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                }), 200);
            }
        });


        function exportPDF() {
            const table = document.getElementById('previewTable');
            if (!table) {
                alert('No report preview is currently loaded.');
                return;
            }

            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'pt',
                format: 'a4'
            });

            doc.setFontSize(13);
            doc.text('PUPSTC Participatory Budget Transparency System', 40, 35);
            doc.setFontSize(10);

            const headerEl = document.querySelector('.preview-header h5');
            const title = headerEl ? headerEl.textContent.replace(/^\s*\S+\s*/, '').trim() : 'Report';
            doc.text(title, 40, 52);
            doc.text('Downloaded: ' + new Date().toLocaleString(), 40, 66);

            doc.autoTable({
                html: '#previewTable',
                startY: 80,
                styles: {
                    fontSize: 8,
                    cellPadding: 4
                },
                headStyles: {
                    fillColor: [134, 9, 9],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [248, 249, 250]
                },
                margin: {
                    left: 40,
                    right: 40
                },
            });

            doc.save(title.replace(/[^a-zA-Z0-9]/g, '_') + '.pdf');
        }


        function exportExcel() {
            const table = document.getElementById('previewTable');
            if (!table) {
                alert('No report preview is currently loaded.');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);

            const headerEl = document.querySelector('.preview-header h5');
            const title = headerEl ? headerEl.textContent.replace(/^\s*\S+\s*/, '').trim() : 'Report';

            XLSX.utils.book_append_sheet(wb, ws, 'Report');
            XLSX.writeFile(wb, title.replace(/[^a-zA-Z0-9]/g, '_') + '.xlsx');
        }


        function exportCSV() {
            const table = document.getElementById('previewTable');
            if (!table) {
                alert('No report preview is currently loaded.');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);

            const headerEl = document.querySelector('.preview-header h5');
            const title = headerEl ? headerEl.textContent.replace(/^\s*\S+\s*/, '').trim() : 'Report';

            XLSX.utils.book_append_sheet(wb, ws, 'Report');
            XLSX.writeFile(wb, title.replace(/[^a-zA-Z0-9]/g, '_') + '.csv', {
                bookType: 'csv'
            });
        }
    </script>

</body>

</html>