<?php

require '../includes/auth.php';
require '../includes/config.php';

$user_id = (int) $_SESSION['user_id'];


$search = '';
$filter_type = '';

if (isset($_GET['search']))
    $search = mysqli_real_escape_string($conn, $_GET['search']);
if (isset($_GET['filter_type']))
    $filter_type = mysqli_real_escape_string($conn, $_GET['filter_type']);

$limit = 6;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = "WHERE (title LIKE '%$search%' OR content LIKE '%$search%')";
if ($filter_type)
    $where .= " AND announcement_type = '$filter_type'";


$total_ann = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM announcements")
)['t'];

$this_month_ann = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) t FROM announcements
         WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
    )
)['t'];

$emergency_ann = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) t FROM announcements WHERE announcement_type='Emergency'")
)['t'];

$count_sql = "SELECT COUNT(*) AS total FROM announcements $where";
$total_records = mysqli_fetch_assoc(mysqli_query($conn, $count_sql))['total'];
$total_pages = max(1, ceil($total_records / $limit));

$sql = "
    SELECT announcements.*, users.fullname AS created_by_name
    FROM announcements
    LEFT JOIN users ON announcements.created_by = users.user_id
    $where
    ORDER BY announcements.created_at DESC
    LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);

$emergency_alert = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT * FROM announcements
         WHERE announcement_type='Emergency'
         ORDER BY created_at DESC LIMIT 1"
    )
);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Announcements</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<style>
    body {
        background: #f5f7fb;
        font-family: 'Segoe UI', sans-serif;
    }

    .top-nav {
        background: rgb(134, 9, 9);
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .25);
    }

    .top-nav .brand {
        color: white;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .top-nav .brand span.short-brand {
        display: none;
    }

    .top-nav .user-pill {
        color: white;
        font-weight: 600;
        white-space: nowrap;
    }

    .sidebar {
        position: sticky;
        top: 52px;
        left: 0;
        width: 200px;
        height: calc(100vh - 52px);
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
        flex-shrink: 0;
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

    .section-header {
        background: linear-gradient(135deg, #8B0000, #c0392b);
        color: white;
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 6px 20px rgba(139, 0, 0, .3);
    }

    .section-header .icon-wrap {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        flex-shrink: 0;
    }

    .section-header h3 {
        font-size: 1.5rem;
    }

    .emergency-banner {
        background: linear-gradient(135deg, #dc2626, #991b1b);
        color: white;
        border-radius: 14px;
        padding: 16px 22px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 16px rgba(220, 38, 38, .35);
        animation: pulseAlert 2.4s infinite;
        flex-wrap: wrap;
    }

    @keyframes pulseAlert {

        0%,
        100% {
            box-shadow: 0 4px 16px rgba(220, 38, 38, .35);
        }

        50% {
            box-shadow: 0 4px 26px rgba(220, 38, 38, .6);
        }
    }

    .emergency-banner i.bell {
        font-size: 26px;
        flex-shrink: 0;
    }

    .emergency-banner .label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .8px;
        opacity: .85;
        font-weight: 700;
    }

    .emergency-banner .title {
        font-weight: 700;
        font-size: 15px;
    }

    .stat-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
        transition: .3s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .filter-bar {
        background: #fff;
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
        margin-bottom: 24px;
    }

    .filter-chip-scroll {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .type-chip {
        border: 1.5px solid #e9ecef;
        border-radius: 50px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        cursor: pointer;
        transition: .2s;
        text-decoration: none;
        display: inline-block;
        white-space: nowrap;
    }

    .type-chip:hover {
        border-color: rgb(134, 9, 9);
        color: rgb(134, 9, 9);
    }

    .type-chip.active {
        background: rgb(134, 9, 9);
        border-color: rgb(134, 9, 9);
        color: white;
    }

    .ann-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 3px 14px rgba(0, 0, 0, .07);
        transition: .25s;
        margin-bottom: 18px;
        overflow: hidden;
        border-left: 5px solid rgb(134, 9, 9);
    }

    .ann-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
    }

    .ann-card.type-emergency {
        border-left-color: #dc2626;
    }

    .ann-card.type-budget {
        border-left-color: #198754;
    }

    .ann-card.type-project {
        border-left-color: #0d6efd;
    }

    .ann-card.type-voting {
        border-left-color: #f0ad4e;
    }

    .ann-card.type-report {
        border-left-color: #0dcaf0;
    }

    .ann-card.type-general {
        border-left-color: #6c757d;
    }

    .ann-card-body {
        padding: 20px 24px;
    }

    .ann-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .type-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .badge-general {
        background: #e9ecef;
        color: #495057;
    }

    .badge-budget {
        background: #d1e7dd;
        color: #0a3622;
    }

    .badge-project {
        background: #cfe2ff;
        color: #084298;
    }

    .badge-voting {
        background: #fff3cd;
        color: #856404;
    }

    .badge-report {
        background: #cff4fc;
        color: #055160;
    }

    .badge-emergency {
        background: #f8d7da;
        color: #842029;
    }

    .ann-title {
        font-size: 17px;
        font-weight: 700;
        color: #222;
        margin-bottom: 8px;
        word-break: break-word;
    }

    .ann-preview {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
    }

    .ann-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #f1f1f1;
        font-size: 12.5px;
        color: #999;
        flex-wrap: wrap;
        gap: 8px;
    }

    .read-more-btn {
        font-size: 13px;
        font-weight: 600;
        color: rgb(134, 9, 9);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        white-space: nowrap;
    }

    .read-more-btn:hover {
        text-decoration: underline;
    }

    .empty-state {
        text-align: center;
        padding: 60px 24px;
        color: #bbb;
    }

    .empty-state i {
        font-size: 56px;
        display: block;
        margin-bottom: 16px;
    }

    .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }

    .modal-body {
        background: #f8f9fa;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
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

    @media (max-width: 575.98px) {
        .top-nav .brand span.full-brand {
            display: none;
        }

        .top-nav .brand span.short-brand {
            display: inline;
        }

        .top-nav {
            padding: 12px 16px;
        }

        .user-pill {
            font-size: 12px;
            padding: 5px 12px;
        }
    }

    @media (max-width: 575.98px) {
        .section-header {
            padding: 20px;
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }

        .section-header h3 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 575.98px) {
        .emergency-banner {
            flex-direction: column;
            text-align: center;
            padding: 16px;
        }
    }

    @media (max-width: 767.98px) {
        .stat-card .card-body {
            padding: 14px;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            font-size: 18px;
        }

        .stat-card .fs-4 {
            font-size: 1.1rem !important;
        }
    }

    @media (max-width: 767.98px) {
        .filter-bar .d-flex.justify-content-between {
            flex-direction: column;
            align-items: stretch !important;
        }

        .filter-bar form {
            width: 100%;
        }

        .filter-bar form input {
            flex: 1;
            width: auto !important;
        }
    }

    @media (max-width: 575.98px) {
        .filter-chip-scroll {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 6px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .filter-chip-scroll::-webkit-scrollbar {
            height: 4px;
        }

        .filter-chip-scroll::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 4px;
        }
    }

    @media (max-width: 575.98px) {
        .ann-card-body {
            padding: 16px 18px;
        }

        .ann-title {
            font-size: 15.5px;
        }

        .ann-footer {
            flex-direction: column;
            align-items: flex-start;
        }

        .ann-footer .read-more-btn {
            align-self: flex-end;
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

    @media (max-width: 575.98px) {
        .modal-body.p-4 {
            padding: 1rem !important;
        }
    }
</style>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>


    <div class="top-nav">
        <div class="brand">
            <button class="btn btn-sm text-white d-xl-none border-0 p-0 me-1" onclick="toggleSidebar()"
                style="font-size:22px; line-height:1;">
                <i class="bi bi-list"></i>
            </button>
            <span class="full-brand">PUPSTC Participatory Budget Transparency System</span>
            <span class="short-brand">PUPSTC</span>
        </div>
        <div class="user-pill">
            <strong><?= htmlspecialchars($_SESSION['fullname']); ?></strong>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-12 col-xl-2" id="sidebarCol">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-4" id="mainSidebar">
                    <div class="text-white mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2e/Polytechnic_University_of_the_Philippines.svg/960px-Polytechnic_University_of_the_Philippines.svg.png"
                                alt="PUP" class="sidebar-logo">
                            <div>
                                <div class="sidebar-title">PUPSTC</div>
                                <div class="sidebar-subtitle">Participatory Budget<br>Transparency System</div>
                            </div>
                        </div>
                        <hr style="border-color:rgba(255,255,255,.2);">
                    </div>

                    <a href="dashboard.php" class="sidebar-btn"><i
                            class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="budget_explore.php" class="sidebar-btn"><i class="bi bi-wallet2"></i><span>Budget
                            Explorer</span></a>
                    <a href="projrcts.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                    <a href="voting.php" class="sidebar-btn"><i
                            class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                    <a href="feedback.php" class="sidebar-btn"><i
                            class="bi bi-envelope-paper"></i><span>Feedback</span></a>
                    <a href="announcement.php" class="sidebar-btn active"><i
                            class="bi bi-megaphone"></i><span>Announcements</span></a>
                    <a href="notifications.php" class="sidebar-btn"><i
                            class="bi bi-bell"></i><span>Notifications</span></a>

                    <hr style="border-color:rgba(255,255,255,.2);">
                    <div class="mt-auto">
                        <a href="../logout.php" class="btn w-100 rounded-pill text-white"
                            style="background:rgba(255,255,255,.15);">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-10 p-2 p-md-3 p-lg-4">
                <div class="section-header">
                    <div class="icon-wrap"><i class="bi bi-megaphone-fill"></i></div>
                    <div>
                        <h3 class="fw-bold mb-1">Announcements</h3>
                        <p class="mb-0" style="opacity:.85;">
                            Stay updated with the latest budget, project, and voting news from the administration.
                        </p>
                    </div>
                </div>


                <?php if ($emergency_alert) { ?>
                    <div class="emergency-banner">
                        <i class="bi bi-exclamation-triangle-fill bell"></i>
                        <div class="flex-grow-1">
                            <div class="label">⚠ Emergency Notice</div>
                            <div class="title"><?= htmlspecialchars($emergency_alert['title']); ?></div>
                        </div>
                        <button class="btn btn-sm btn-light fw-semibold" data-bs-toggle="modal"
                            data-bs-target="#viewModal<?= $emergency_alert['announcement_id']; ?>">
                            View
                        </button>
                    </div>
                <?php } ?>

                <div class="row mb-4">
                    <div class="col-12 col-sm-4 mb-2 mb-sm-0">
                        <div class="card stat-card h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background:#fce7e7;">
                                    <i class="bi bi-megaphone text-danger fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size:12px;">Total Announcements</div>
                                    <div class="fw-bold fs-4"><?= $total_ann; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4 mb-2 mb-sm-0">
                        <div class="card stat-card h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background:#d1e7dd;">
                                    <i class="bi bi-calendar-check text-success fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size:12px;">This Month</div>
                                    <div class="fw-bold fs-4"><?= $this_month_ann; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="card stat-card h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon" style="background:#f8d7da;">
                                    <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size:12px;">Emergency Notices</div>
                                    <div class="fw-bold fs-4"><?= $emergency_ann; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-bar">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div class="filter-chip-scroll">
                            <a href="?filter_type=&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == '' ? 'active' : '' ?>">All</a>
                            <a href="?filter_type=General&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'General' ? 'active' : '' ?>">General</a>
                            <a href="?filter_type=Budget&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'Budget' ? 'active' : '' ?>">Budget</a>
                            <a href="?filter_type=Project&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'Project' ? 'active' : '' ?>">Project</a>
                            <a href="?filter_type=Voting&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'Voting' ? 'active' : '' ?>">Voting</a>
                            <a href="?filter_type=Report&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'Report' ? 'active' : '' ?>">Report</a>
                            <a href="?filter_type=Emergency&search=<?= urlencode($search) ?>"
                                class="type-chip <?= $filter_type == 'Emergency' ? 'active' : '' ?>">Emergency</a>
                        </div>

                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="filter_type" value="<?= htmlspecialchars($filter_type) ?>">
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Search announcements..." value="<?= htmlspecialchars($search) ?>"
                                style="width:220px;">
                            <button type="submit" class="btn btn-sm text-white" style="background-color:rgb(134,9,9);">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (mysqli_num_rows($query) == 0) { ?>
                    <div class="card">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p class="fw-semibold mb-1">No announcements found</p>
                            <small>Try adjusting your filters or check back later.</small>
                        </div>
                    </div>
                <?php } ?>

                <?php while ($row = mysqli_fetch_assoc($query)) {

                    $type = $row['announcement_type'] ?: 'General';
                    $type_class = strtolower($type);
                    $badge_class = 'badge-' . $type_class;

                    $preview = mb_strlen($row['content']) > 180
                        ? mb_substr($row['content'], 0, 180) . '…'
                        : $row['content'];
                ?>

                    <div class="ann-card type-<?= $type_class; ?>">
                        <div class="ann-card-body">
                            <div class="ann-meta">
                                <span class="type-badge <?= $badge_class; ?>"><?= htmlspecialchars($type); ?></span>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('M d, Y \a\t h:i A', strtotime($row['created_at'])); ?>
                                </small>
                            </div>

                            <div class="ann-title"><?= htmlspecialchars($row['title']); ?></div>
                            <div class="ann-preview"><?= htmlspecialchars($preview); ?></div>

                            <div class="ann-footer">
                                <span>
                                    <i class="bi bi-person-circle me-1"></i>
                                    Posted by <?= htmlspecialchars($row['created_by_name'] ?? 'Administrator'); ?>
                                </span>
                                <?php if (mb_strlen($row['content']) > 180) { ?>
                                    <button class="read-more-btn" data-bs-toggle="modal"
                                        data-bs-target="#viewModal<?= $row['announcement_id']; ?>">
                                        Read full announcement <i class="bi bi-arrow-right"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                <?php } ?>

                <?php
                mysqli_data_seek($query, 0);
                while ($row = mysqli_fetch_assoc($query)) {
                    $type = $row['announcement_type'] ?: 'General';
                    $badge_class = 'badge-' . strtolower($type);
                ?>
                    <div class="modal fade" id="viewModal<?= $row['announcement_id']; ?>" tabindex="-1"
                        data-bs-backdrop="static">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header text-white" style="background-color:rgb(134,9,9);">
                                    <h5 class="modal-title fw-bold">
                                        <i class="bi bi-megaphone me-2"></i>Announcement Details
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-3 p-md-4">
                                    <span class="type-badge <?= $badge_class; ?> mb-3 d-inline-block">
                                        <?= htmlspecialchars($type); ?>
                                    </span>
                                    <h4 class="fw-bold mb-3"><?= htmlspecialchars($row['title']); ?></h4>
                                    <p class="text-muted" style="white-space:pre-wrap; line-height:1.8;">
                                        <?= htmlspecialchars($row['content']); ?>
                                    </p>
                                    <hr>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>
                                        Posted by
                                        <strong><?= htmlspecialchars($row['created_by_name'] ?? 'Administrator'); ?></strong>
                                        &nbsp;|&nbsp;
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('F d, Y \a\t h:i A', strtotime($row['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg"></i> Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($total_records > 0) { ?>
                    <div class="d-flex justify-content-between align-items-center mt-4 pagination-wrap">
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of
                            <?= $total_records ?> announcements
                        </small>
                        <nav>
                            <ul class="pagination mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <?php if ($page > 1) { ?>
                                        <a class="page-link"
                                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_type=<?= urlencode($filter_type) ?>">&laquo;</a>
                                    <?php } else { ?><span class="page-link">&laquo;</span><?php } ?>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_type=<?= urlencode($filter_type) ?>"><?= $i ?></a>
                                    </li>
                                <?php } ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <?php if ($page < $total_pages) { ?>
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_type=<?= urlencode($filter_type) ?>">&raquo;</a>
                                    <?php } else { ?><span class="page-link">&raquo;</span><?php } ?>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php } ?>

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