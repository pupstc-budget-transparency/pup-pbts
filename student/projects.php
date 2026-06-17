<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];


$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

$status_filter = '';
if (isset($_GET['status_filter']) && in_array($_GET['status_filter'], ['Planning', 'Ongoing', 'Completed', 'Cancelled'])) {
    $status_filter = $_GET['status_filter'];
}

$limit = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$status_clause = $status_filter ? "AND projects.status = '$status_filter'" : '';

$count_sql = "
SELECT COUNT(*) AS total
FROM projects
WHERE record_status='active'
$status_clause
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

    (
        SELECT IFNULL(SUM(e.amount), 0)
        FROM expenditures e
        WHERE e.project_id = projects.project_id
        AND e.record_status = 'active'
    ) AS total_spent

FROM projects

LEFT JOIN budgets
    ON projects.budget_id = budgets.budget_id

WHERE
    projects.record_status='active'
    $status_clause

AND
(
    projects.project_title LIKE '%$search%'
    OR projects.project_code LIKE '%$search%'
)

ORDER BY projects.project_id DESC

LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);


$total_projects = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM projects WHERE record_status='active'")
)['total'];

$ongoing_projects = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM projects WHERE record_status='active' AND status='Ongoing'")
)['total'];

$completed_projects = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM projects WHERE record_status='active' AND status='Completed'")
)['total'];

$total_allocated = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(allocated_budget) total FROM projects WHERE record_status='active'")
)['total'];

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Projects</title>
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

    .project-card {
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

    .project-card:hover {
        box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
        transform: translateY(-3px);
    }

    .project-card-header {
        padding: 18px 20px 12px;
        border-bottom: 1px solid #f1f1f1;
    }

    .project-card-body {
        padding: 16px 20px;
        flex-grow: 1;
    }

    .project-card-footer {
        padding: 14px 20px;
        background: #f8f9fa;
        border-top: 1px solid #f1f1f1;
    }

    .project-code-tag {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        letter-spacing: .5px;
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
                    <a href="budget_explore.php" class="sidebar-btn"><i class="bi bi-wallet2"></i><span>Budget
                            Explorer</span></a>
                    <a href="projects.php" class="sidebar-btn active"><i
                            class="bi bi-kanban"></i><span>Projects</span></a>
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

                <!-- Page header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['fullname']); ?></h2>
                        <h5 class="text-muted">Projects</h5>
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

                <!-- Info banner -->
                <div class="info-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>
                        Browse all university projects funded through the participatory budget.
                        Click <strong>View Details</strong> on any project to see its full description,
                        timeline, and budget allocation.
                    </p>
                </div>

                <div class="row g-3 mb-4">

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-danger-subtle p-3">
                                    <i class="bi bi-kanban text-danger fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Total Projects</small>
                                    <h3 class="mb-0"><?= $total_projects; ?></h3>
                                    <small class="text-muted">All records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary-subtle p-3">
                                    <i class="bi bi-arrow-repeat text-primary fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Ongoing</small>
                                    <h3 class="mb-0"><?= $ongoing_projects; ?></h3>
                                    <small class="text-muted">In progress</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success-subtle p-3">
                                    <i class="bi bi-check-circle text-success fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Completed</small>
                                    <h3 class="mb-0"><?= $completed_projects; ?></h3>
                                    <small class="text-muted">Finished</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-3">
                        <div class="card stats-card shadow border border-light">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning-subtle p-3">
                                    <i class="bi bi-cash-stack text-warning fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Total Allocated</small>
                                    <h3 class="mb-0" style="font-size:17px;">₱<?= number_format($total_allocated, 2); ?>
                                    </h3>
                                    <small class="text-muted">Across all projects</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">

                    <div class="d-flex flex-wrap gap-2">
                        <a href="?status_filter="
                            class="filter-pill <?= $status_filter == '' ? 'active' : ''; ?>">All</a>
                        <a href="?status_filter=Planning"
                            class="filter-pill <?= $status_filter == 'Planning' ? 'active' : ''; ?>">Planning</a>
                        <a href="?status_filter=Ongoing"
                            class="filter-pill <?= $status_filter == 'Ongoing' ? 'active' : ''; ?>">Ongoing</a>
                        <a href="?status_filter=Completed"
                            class="filter-pill <?= $status_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
                        <a href="?status_filter=Cancelled"
                            class="filter-pill <?= $status_filter == 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    </div>

                    <form method="GET" class="d-flex gap-2">
                        <?php if ($status_filter) { ?>
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter); ?>">
                        <?php } ?>
                        <input type="text" name="search" class="form-control" placeholder="Search project..."
                            value="<?= htmlspecialchars($search); ?>" style="width:230px; border-radius:10px;">
                        <button type="submit" class="btn text-light"
                            style="background-color:rgb(134,9,9); border-radius:10px;">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="projects.php" class="btn btn-secondary" style="border-radius:10px;">Clear</a>
                    </form>
                </div>

                <div class="text-muted mb-3">
                    Showing <?= mysqli_num_rows($query); ?> of <?= $total_records; ?>
                    project<?= $total_records != 1 ? 's' : ''; ?>
                </div>

                <?php if (mysqli_num_rows($query) == 0) { ?>
                    <div class="card page-card shadow border border-light">
                        <div class="card-body empty-state">
                            <i class="bi bi-kanban"></i>
                            <p class="fw-semibold mb-1">No projects found</p>
                            <small>Try adjusting your search or filter.</small>
                        </div>
                    </div>
                <?php } else { ?>

                    <div class="row g-3 mb-4">
                        <?php while ($row = mysqli_fetch_assoc($query)) {

                            $allocated = (float) $row['allocated_budget'];
                            $spent = (float) $row['total_spent'];
                            $pct = $allocated > 0 ? min(100, round(($spent / $allocated) * 100)) : 0;
                            $bar_color = $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success');

                            $badge = match ($row['status']) {
                                'Planning' => 'bg-secondary',
                                'Ongoing' => 'bg-primary',
                                'Completed' => 'bg-success',
                                default => 'bg-danger',
                            };
                        ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="project-card shadow">

                                    <div class="project-card-header">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="project-code-tag"><?= htmlspecialchars($row['project_code']); ?></span>
                                            <span class="badge <?= $badge; ?>"><?= htmlspecialchars($row['status']); ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($row['project_title']); ?></h6>
                                    </div>

                                    <div class="project-card-body">
                                        <p class="text-muted mb-3" style="font-size:13px;">
                                            <?= htmlspecialchars(mb_strimwidth($row['project_description'], 0, 100, '...')); ?>
                                        </p>

                                        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                                            <span class="text-muted">Budget Utilized</span>
                                            <strong><?= $pct; ?>%</strong>
                                        </div>
                                        <div class="progress-thin mb-3">
                                            <div class="progress-bar <?= $bar_color; ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between" style="font-size:12.5px;">
                                            <span class="text-muted"><i
                                                    class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($row['start_date'])); ?></span>
                                            <span class="text-muted"><?= date('M d, Y', strtotime($row['end_date'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="project-card-footer d-flex justify-content-between align-items-center">
                                        <span class="fw-bold" style="color:rgb(134,9,9); font-size:14px;">
                                            ₱<?= number_format($allocated, 2); ?>
                                        </span>
                                        <button class="btn btn-sm text-white"
                                            style="background-color:rgb(134,9,9); border-radius:8px;" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $row['project_id']; ?>">
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

                        $allocated = (float) $row['allocated_budget'];
                        $spent = (float) $row['total_spent'];
                        $remaining = $allocated - $spent;
                        $pct = $allocated > 0 ? min(100, round(($spent / $allocated) * 100)) : 0;
                        $bar_color = $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success');

                        $badge = match ($row['status']) {
                            'Planning' => 'bg-secondary',
                            'Ongoing' => 'bg-primary',
                            'Completed' => 'bg-success',
                            default => 'bg-danger',
                        };
                    ?>

                        <div class="modal fade" id="viewModal<?= $row['project_id']; ?>" tabindex="-1"
                            data-bs-backdrop="static">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">

                                    <div class="modal-header text-white" style="background-color:rgb(134,9,9);">
                                        <h5 class="modal-title fw-bold">
                                            <i class="bi bi-kanban me-2"></i> Project Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white"
                                            data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body p-4">

                                        <div class="d-flex justify-content-between align-items-start mb-4">
                                            <div>
                                                <span
                                                    class="project-code-tag"><?= htmlspecialchars($row['project_code']); ?></span>
                                                <h4 class="fw-bold mt-1 mb-0"><?= htmlspecialchars($row['project_title']); ?>
                                                </h4>
                                            </div>
                                            <span class="badge <?= $badge; ?>" style="font-size:13px; padding:6px 14px;">
                                                <?= htmlspecialchars($row['status']); ?>
                                            </span>
                                        </div>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-card-text"></i></div>
                                            <h6 class="mb-0 fw-bold">Description</h6>
                                        </div>
                                        <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($row['project_description'])); ?>
                                        </p>

                                        <hr>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-wallet2"></i></div>
                                            <h6 class="mb-0 fw-bold">Budget Allocation</h6>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-6 col-md-3">
                                                <div class="detail-label">Budget Source</div>
                                                <div class="detail-value" style="font-size:13.5px;">
                                                    <?= htmlspecialchars($row['budget_title'] ?? '—'); ?>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="detail-label">Allocated</div>
                                                <div class="detail-value">₱<?= number_format($allocated, 2); ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="detail-label">Spent</div>
                                                <div class="detail-value">₱<?= number_format($spent, 2); ?></div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="detail-label">Remaining</div>
                                                <div class="detail-value" style="color:#198754;">
                                                    ₱<?= number_format($remaining, 2); ?></div>
                                            </div>
                                        </div>

                                        <div class="progress-thin mb-1" style="height:10px;">
                                            <div class="progress-bar <?= $bar_color; ?>" style="width:<?= $pct; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $pct; ?>% of allocated budget utilized</small>

                                        <hr>

                                        <div class="section-title">
                                            <div class="icon"><i class="bi bi-calendar-range"></i></div>
                                            <h6 class="mb-0 fw-bold">Timeline</h6>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="detail-label">Start Date</div>
                                                <div class="detail-value"><?= date('M d, Y', strtotime($row['start_date'])); ?>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="detail-label">End Date</div>
                                                <div class="detail-value"><?= date('M d, Y', strtotime($row['end_date'])); ?>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                            <i class="bi bi-x-lg me-1"></i> Close
                                        </button>
                                        <a href="project-monitoring.php?project_id=<?= $row['project_id']; ?>"
                                            class="btn text-white" style="background-color:rgb(134,9,9);">
                                            <i class="bi bi-bar-chart-line me-1"></i> View Progress
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
                                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">&laquo;</a>
                                    <?php } else { ?>
                                        <span class="page-link">&laquo;</span>
                                    <?php } ?>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                                    </li>
                                <?php } ?>

                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <?php if ($page < $total_pages) { ?>
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>">&raquo;</a>
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