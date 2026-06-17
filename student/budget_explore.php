<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];

$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

$year_filter = '';
if (isset($_GET['year_filter']) && ctype_digit($_GET['year_filter'])) {
    $year_filter = $_GET['year_filter'];
}

$limit = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;


$years_query = mysqli_query(
    $conn,
    "SELECT DISTINCT fiscal_year FROM budgets
     WHERE record_status='active'
     ORDER BY fiscal_year DESC"
);
$available_years = [];
while ($y = mysqli_fetch_assoc($years_query)) {
    $available_years[] = $y['fiscal_year'];
}

$year_clause = $year_filter ? "AND fiscal_year = '$year_filter'" : '';

$count_sql = "
SELECT COUNT(*) AS total
FROM budgets
WHERE record_status='active'
$year_clause
AND
(
    budget_title LIKE '%$search%'
    OR purpose LIKE '%$search%'
)
";

$count_query = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_query)['total'];
$total_pages = max(1, ceil($total_records / $limit));

$sql = "
SELECT
    budgets.*,

    (
        SELECT IFNULL(SUM(p.allocated_budget), 0)
        FROM projects p
        WHERE p.budget_id = budgets.budget_id
        AND p.record_status = 'active'
    ) AS total_allocated_to_projects,

    (
        SELECT COUNT(*)
        FROM projects p
        WHERE p.budget_id = budgets.budget_id
        AND p.record_status = 'active'
    ) AS linked_project_count

FROM budgets

WHERE
    budgets.record_status='active'
    $year_clause

AND
(
    budgets.budget_title LIKE '%$search%'
    OR budgets.purpose LIKE '%$search%'
)

ORDER BY budgets.fiscal_year DESC, budgets.budget_id DESC

LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);


$total_budgets = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM budgets WHERE record_status='active'")
)['total'];

$total_amount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(total_amount) total FROM budgets WHERE record_status='active'")
)['total'];

$current_year = date('Y');
$current_year_budgets = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM budgets WHERE record_status='active' AND fiscal_year='$current_year'")
)['total'];

$largest_budget = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT MAX(total_amount) total FROM budgets WHERE record_status='active'")
)['total'];

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Budget Explorer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<style>
    body {
        background: #f5f7fb;
        font-family: 'Segoe UI', sans-serif;
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

    .budget-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .07);
        border: none;
        transition: .25s;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .budget-card:hover {
        box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
        transform: translateY(-3px);
    }

    .budget-card-header {
        padding: 18px 20px 12px;
        border-bottom: 1px solid #f1f1f1;
    }

    .budget-card-body {
        padding: 16px 20px;
        flex-grow: 1;
    }

    .budget-card-footer {
        padding: 14px 20px;
        background: #f8f9fa;
        border-top: 1px solid #f1f1f1;
    }

    .fiscal-year-tag {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .progress-thin {
        height: 8px;
        border-radius: 6px;
        background: #e9ecef;
    }

    .progress-thin .progress-bar {
        border-radius: 6px;
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

    .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }

    .modal-body {
        background: #f8f9fa;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .section-title .icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: rgba(134, 9, 9, .1);
        color: rgb(134, 9, 9);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .detail-label {
        font-size: 12px;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 3px;
    }

    .detail-value {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 14px;
    }

    .transparency-box {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 12px;
        border-left: 4px solid rgb(134, 9, 9);
    }

    .transparency-box .t-label {
        font-size: 12px;
        font-weight: 700;
        color: rgb(134, 9, 9);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 6px;
    }

    .transparency-box .t-value {
        font-size: 14px;
        color: #374151;
        line-height: 1.6;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #bbb;
    }

    .empty-state i {
        font-size: 56px;
        display: block;
        margin-bottom: 16px;
        color: #ddd;
    }

    .linked-projects-pill {
        background: #ede9fe;
        color: #6d28d9;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 50px;
    }
</style>

<body>

    <!-- NAVBAR -->
    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color:rgb(134,9,9);">
        <div class="container-xl p-3 d-flex justify-content-between">
            <h6 class="mb-0">PUPSTC Participatory Budget Transparency System</h6>
            <span><strong><?= htmlspecialchars($_SESSION['fullname']); ?></strong></span>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="row g-0">

            <!-- SIDEBAR -->
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
                    <a href="budget_explore.php" class="sidebar-btn active"><i class="bi bi-wallet2"></i><span>Budget
                            Explorer</span></a>
                    <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                    <a href="voting.php" class="sidebar-btn"><i
                            class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                    <a href="reports.php" class="sidebar-btn"><i
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

            <!-- MAIN -->
            <div class="col-12 col-xl-10 p-3 p-xl-4">


                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['fullname']); ?></h2>
                        <h5 class="text-muted">Budget Explorer</h5>
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
                                <div class="access-value" style="font-size:18px;">View Only</div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>
                        Explore how the university allocates its budget. Click <strong>View Details</strong>
                        on any budget to see its full purpose, intended beneficiaries, expected outcomes,
                        and which projects it funds.
                    </p>
                </div>

                <!-- STATS -->
                <div class="row g-3 mb-4">

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-danger-subtle p-3">
                                    <i class="bi bi-wallet2 text-danger fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Total Budgets</small>
                                    <h3 class="mb-0"><?= $total_budgets; ?></h3>
                                    <small class="text-muted">Budget records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success-subtle p-3">
                                    <i class="bi bi-cash-stack text-success fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Total Allocated</small>
                                    <h3 class="mb-0" style="font-size:16px;">₱<?= number_format($total_amount, 2); ?>
                                    </h3>
                                    <small class="text-muted">All fiscal years</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning-subtle p-3">
                                    <i class="bi bi-calendar-check text-warning fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Current Year</small>
                                    <h3 class="mb-0"><?= $current_year_budgets; ?></h3>
                                    <small class="text-muted">FY <?= $current_year; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary-subtle p-3">
                                    <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Largest Budget</small>
                                    <h3 class="mb-0" style="font-size:16px;">₱<?= number_format($largest_budget, 2); ?>
                                    </h3>
                                    <small class="text-muted">Highest allocation</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">

                    <div class="d-flex flex-wrap gap-2">
                        <a href="?year_filter=" class="filter-pill <?= $year_filter == '' ? 'active' : ''; ?>">All
                            Years</a>
                        <?php foreach ($available_years as $yr) { ?>
                            <a href="?year_filter=<?= $yr; ?>"
                                class="filter-pill <?= $year_filter == $yr ? 'active' : ''; ?>">FY <?= $yr; ?></a>
                        <?php } ?>
                    </div>

                    <form method="GET" class="d-flex gap-2">
                        <?php if ($year_filter) { ?>
                            <input type="hidden" name="year_filter" value="<?= htmlspecialchars($year_filter); ?>">
                        <?php } ?>
                        <input type="text" name="search" class="form-control" placeholder="Search budget..."
                            value="<?= htmlspecialchars($search); ?>" style="width:230px; border-radius:10px;">
                        <button type="submit" class="btn text-light"
                            style="background-color:rgb(134,9,9); border-radius:10px;">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="budget_explore.php" class="btn btn-secondary" style="border-radius:10px;">Clear</a>
                    </form>
                </div>

                <div class="text-muted mb-3">
                    Showing <?= mysqli_num_rows($query); ?> of <?= $total_records; ?>
                    budget<?= $total_records != 1 ? 's' : ''; ?>
                </div>

                <!-- BUDGET GRID -->
                <?php if (mysqli_num_rows($query) == 0) { ?>
                    <div class="card page-card shadow border border-light">
                        <div class="card-body empty-state">
                            <i class="bi bi-wallet2"></i>
                            <p class="fw-semibold mb-1">No budgets found</p>
                            <small>Try adjusting your search or filter.</small>
                        </div>
                    </div>
                <?php } else { ?>

                    <div class="row g-3 mb-4">
                        <?php while ($row = mysqli_fetch_assoc($query)) {

                            $total = (float) $row['total_amount'];
                            $allocated = (float) $row['total_allocated_to_projects'];
                            $remaining = $total - $allocated;
                            $pct = $total > 0 ? min(100, round(($allocated / $total) * 100)) : 0;
                            $bar_color = $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success');
                        ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="budget-card shadow">

                                    <div class="budget-card-header">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fiscal-year-tag">FY
                                                <?= htmlspecialchars($row['fiscal_year']); ?></span>
                                            <span class="linked-projects-pill">
                                                <?= (int) $row['linked_project_count']; ?>
                                                project<?= $row['linked_project_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </div>
                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($row['budget_title']); ?></h6>
                                    </div>

                                    <div class="budget-card-body">
                                        <p class="text-muted mb-3" style="font-size:13px;">
                                            <?= htmlspecialchars(mb_strimwidth($row['purpose'], 0, 100, '...')); ?>
                                        </p>

                                        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                                            <span class="text-muted">Allocated to Projects</span>
                                            <strong><?= $pct; ?>%</strong>
                                        </div>
                                        <div class="progress-thin mb-3">
                                            <div class="progress-bar <?= $bar_color; ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>

                                        <div style="font-size:12.5px;" class="text-muted">
                                            <i
                                                class="bi bi-people me-1"></i><?= htmlspecialchars(mb_strimwidth($row['beneficiaries'], 0, 40, '...')); ?>
                                        </div>
                                    </div>

                                    <div class="budget-card-footer d-flex justify-content-between align-items-center">
                                        <span class="fw-bold" style="color:rgb(134,9,9); font-size:14px;">
                                            ₱<?= number_format($total, 2); ?>
                                        </span>
                                        <button class="btn btn-sm text-white"
                                            style="background-color:rgb(134,9,9); border-radius:8px;" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $row['budget_id']; ?>">
                                            <i class="bi bi-eye me-1"></i> View Details
                                        </button>
                                    </div>

                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <?php
                    mysqli_data_seek($query, 0);
                    while ($row = mysqli_fetch_assoc($query)) {

                        $total = (float) $row['total_amount'];
                        $allocated = (float) $row['total_allocated_to_projects'];
                        $remaining = $total - $allocated;
                        $pct = $total > 0 ? min(100, round(($allocated / $total) * 100)) : 0;
                        $bar_color = $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success');

                        // Fetch projects funded by this budget (read-only list inside the modal)
                        $linked_projects = mysqli_query(
                            $conn,
                            "SELECT project_id, project_code, project_title, status, allocated_budget
                         FROM projects
                         WHERE budget_id = {$row['budget_id']}
                         AND record_status = 'active'
                         ORDER BY project_id DESC"
                        );
                    ?>

                        <div class="modal fade" id="viewModal<?= $row['budget_id']; ?>" tabindex="-1" data-bs-backdrop="static">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content border-0 shadow">

                                    <div class="modal-header text-white" style="background-color:rgb(134,9,9);">
                                        <h5 class="modal-title fw-bold">
                                            <i class="bi bi-wallet2 me-2"></i> Budget Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body p-4">

                                        <div class="d-flex justify-content-between align-items-start mb-4">
                                            <div>
                                                <span class="fiscal-year-tag">FY
                                                    <?= htmlspecialchars($row['fiscal_year']); ?></span>
                                                <h4 class="fw-bold mt-1 mb-0"><?= htmlspecialchars($row['budget_title']); ?>
                                                </h4>
                                            </div>
                                            <span class="fw-bold" style="color:rgb(134,9,9); font-size:18px;">
                                                ₱<?= number_format($total, 2); ?>
                                            </span>
                                        </div>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-cash-stack"></i></div>
                                            <h6 class="mb-0 fw-bold">Fund Utilization</h6>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-4">
                                                <div class="detail-label">Total Amount</div>
                                                <div class="detail-value">₱<?= number_format($total, 2); ?></div>
                                            </div>
                                            <div class="col-4">
                                                <div class="detail-label">Allocated</div>
                                                <div class="detail-value">₱<?= number_format($allocated, 2); ?></div>
                                            </div>
                                            <div class="col-4">
                                                <div class="detail-label">Remaining</div>
                                                <div class="detail-value" style="color:#198754;">
                                                    ₱<?= number_format($remaining, 2); ?></div>
                                            </div>
                                        </div>

                                        <div class="progress-thin mb-1" style="height:10px;">
                                            <div class="progress-bar <?= $bar_color; ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $pct; ?>% allocated to active projects</small>

                                        <hr>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-shield-check"></i></div>
                                            <h6 class="mb-0 fw-bold">Transparency Details</h6>
                                        </div>

                                        <div class="transparency-box">
                                            <div class="t-label"><i class="bi bi-bullseye me-1"></i> Purpose</div>
                                            <div class="t-value"><?= nl2br(htmlspecialchars($row['purpose'])); ?></div>
                                        </div>

                                        <div class="transparency-box">
                                            <div class="t-label"><i class="bi bi-people-fill me-1"></i> Beneficiaries</div>
                                            <div class="t-value"><?= nl2br(htmlspecialchars($row['beneficiaries'])); ?></div>
                                        </div>

                                        <div class="transparency-box">
                                            <div class="t-label"><i class="bi bi-flag-fill me-1"></i> Expected Outcome</div>
                                            <div class="t-value"><?= nl2br(htmlspecialchars($row['expected_outcome'])); ?></div>
                                        </div>

                                        <hr>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-kanban"></i></div>
                                            <h6 class="mb-0 fw-bold">Funded Projects
                                                (<?= (int) $row['linked_project_count']; ?>)</h6>
                                        </div>

                                        <?php if (mysqli_num_rows($linked_projects) == 0) { ?>
                                            <p class="text-muted text-center" style="font-size:13px;">
                                                No projects are currently linked to this budget.
                                            </p>
                                        <?php } else { ?>
                                            <?php while ($proj = mysqli_fetch_assoc($linked_projects)) {
                                                $pbadge = match ($proj['status']) {
                                                    'Planning' => 'bg-secondary',
                                                    'Ongoing' => 'bg-primary',
                                                    'Completed' => 'bg-success',
                                                    default => 'bg-danger',
                                                };
                                            ?>
                                                <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-3"
                                                    style="background:#f8f9fa;">
                                                    <div>
                                                        <small
                                                            class="text-muted"><?= htmlspecialchars($proj['project_code']); ?></small><br>
                                                        <span
                                                            style="font-size:13.5px; font-weight:600;"><?= htmlspecialchars($proj['project_title']); ?></span>
                                                    </div>
                                                    <div class="text-end">
                                                        <span
                                                            class="badge <?= $pbadge; ?> mb-1"><?= htmlspecialchars($proj['status']); ?></span><br>
                                                        <small
                                                            class="text-muted">₱<?= number_format($proj['allocated_budget'], 2); ?></small>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg me-1"></i> Close
                                        </button>
                                        <a href="projects.php?search=<?= urlencode($row['budget_title']); ?>"
                                            class="btn text-white" style="background-color:rgb(134,9,9);">
                                            <i class="bi bi-kanban me-1"></i> View Related Projects
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>

                    <?php } ?>

                    <!-- PAGINATION -->
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-5">
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of
                            <?= $total_records ?> entries
                        </small>
                        <nav>
                            <ul class="pagination mb-0">

                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <?php if ($page > 1) { ?>
                                        <a class="page-link"
                                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&year_filter=<?= urlencode($year_filter) ?>">&laquo;</a>
                                    <?php } else { ?>
                                        <span class="page-link">&laquo;</span>
                                    <?php } ?>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&year_filter=<?= urlencode($year_filter) ?>"><?= $i ?></a>
                                    </li>
                                <?php } ?>

                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <?php if ($page < $total_pages) { ?>
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&year_filter=<?= urlencode($year_filter) ?>">&raquo;</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>