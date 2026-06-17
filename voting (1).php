<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];
$student_id = $_SESSION['user_id'];


if (isset($_POST['cast_vote'])) {

    $session_id = (int) $_POST['session_id'];
    $project_id = (int) $_POST['project_id'];

    // 1. Validate the session exists and is Active
    $sess_check = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM voting_sessions
             WHERE session_id = $session_id"
        )
    );

    if (!$sess_check) {
        echo "<script>alert('Voting session not found.'); window.location='voting.php';</script>";
        exit();
    }

    if ($sess_check['status'] !== 'Active') {
        echo "<script>alert('This voting session is closed and no longer accepting votes.'); window.location='voting.php';</script>";
        exit();
    }

    $today = date('Y-m-d');
    if ($today < $sess_check['start_date'] || $today > $sess_check['end_date']) {
        echo "<script>alert('This voting session is not currently open for voting.'); window.location='voting.php';</script>";
        exit();
    }

    $proj_check = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT vsp.*, p.project_title
             FROM voting_session_projects vsp
             JOIN projects p ON vsp.project_id = p.project_id
             WHERE vsp.session_id = $session_id
             AND vsp.project_id = $project_id"
        )
    );

    if (!$proj_check) {
        echo "<script>alert('Invalid project selection for this session.'); window.location='voting.php';</script>";
        exit();
    }

    $existing_vote = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT vote_id FROM votes
             WHERE session_id = $session_id
             AND student_id = $student_id"
        )
    );

    if ($existing_vote) {
        echo "<script>alert('You have already voted in this session. Each student may only vote once per session.'); window.location='voting.php';</script>";
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO votes (session_id, project_id, student_id, voted_at)
         VALUES (?, ?, ?, NOW())"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $session_id,
        $project_id,
        $student_id
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_query(
            $conn,
            "INSERT INTO audit_logs (user_id, action, table_name, record_id)
             VALUES ($student_id, 'Cast Vote', 'votes', $session_id)"
        );

        header("Location: voting.php?voted=1&session=$session_id");
        exit();
    } else {

        echo "<script>alert('Failed to cast your vote. Please try again.'); window.location='voting.php';</script>";
        exit();
    }
}


$active_sessions_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) t FROM voting_sessions
         WHERE status='Active'
         AND CURDATE() BETWEEN start_date AND end_date"
    )
)['t'];

$my_votes_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) t FROM votes WHERE student_id = $student_id"
    )
)['t'];

$closed_sessions_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) t FROM voting_sessions WHERE status='Closed'"
    )
)['t'];

$sessions_query = mysqli_query(
    $conn,
    "SELECT vs.*,
            COUNT(DISTINCT v.vote_id) AS total_votes_cast,
            COUNT(DISTINCT vsp.project_id) AS project_count
     FROM voting_sessions vs
     LEFT JOIN votes v ON vs.session_id = v.session_id
     LEFT JOIN voting_session_projects vsp ON vs.session_id = vsp.session_id
     GROUP BY vs.session_id
     ORDER BY
        CASE WHEN vs.status = 'Active' THEN 0 ELSE 1 END,
        vs.session_id DESC"
);

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – Voting</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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

    .success-banner {
        background: #d1e7dd;
        border: 1px solid #a3cfbb;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 24px;
    }

    .success-banner i {
        font-size: 22px;
        color: #0a3622;
        margin-top: 2px;
    }

    .success-banner p {
        margin: 0;
        font-size: 13.5px;
        color: #0a3622;
    }

    .session-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .07);
        border: none;
        transition: .25s;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .session-card:hover {
        box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
        transform: translateY(-2px);
    }

    .session-card.voted-card {
        border-left: 5px solid #198754;
    }

    .session-card.closed-card {
        border-left: 5px solid #6c757d;
        opacity: .92;
    }

    .session-header {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .session-body {
        padding: 18px 22px;
    }

    .session-footer {
        padding: 14px 22px;
        background: #f8f9fa;
        border-top: 1px solid #f1f1f1;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .status-badge-active {
        background: #d1e7dd;
        color: #0a3622;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-badge-closed {
        background: #e2e3e5;
        color: #41464b;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-badge-voted {
        background: #cfe2ff;
        color: #084298;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .project-choice {
        border: 2px solid #e9ecef;
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: .2s;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .project-choice:hover {
        border-color: rgb(134, 9, 9);
        background: #fff8f8;
    }

    .project-choice.selected {
        border-color: rgb(134, 9, 9);
        background: #fff2f2;
        box-shadow: 0 0 0 2px rgba(134, 9, 9, .12);
    }

    .project-choice input[type="radio"] {
        width: 20px;
        height: 20px;
        accent-color: rgb(134, 9, 9);
        flex-shrink: 0;
        cursor: pointer;
    }

    .project-choice .proj-title {
        font-weight: 600;
        font-size: 14.5px;
        color: #1f2937;
    }

    .project-choice .proj-code {
        font-size: 12px;
        color: #6b7280;
    }

    .vote-bar-wrap {
        margin-bottom: 12px;
    }

    .vote-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #555;
        margin-bottom: 4px;
    }

    .vote-bar-track {
        height: 10px;
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }

    .vote-bar-fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, #8B0000, #c0392b);
        transition: width .6s ease;
    }

    .locked-results {
        text-align: center;
        padding: 30px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        color: #6b7280;
    }

    .locked-results i {
        font-size: 32px;
        display: block;
        margin-bottom: 10px;
        color: #cbd5e1;
    }

    .voted-confirmation {
        background: #d1e7dd;
        border: 1px solid #a3cfbb;
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .voted-confirmation i {
        font-size: 26px;
        color: #198754;
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

    .vote-submit-btn {
        background: rgb(134, 9, 9);
        border: none;
        color: white;
        border-radius: 10px;
        padding: 10px 28px;
        font-weight: 600;
        transition: .2s;
    }

    .vote-submit-btn:disabled {
        background: #c7b9b9;
        cursor: not-allowed;
    }

    .vote-submit-btn:hover:not(:disabled) {
        background: #650000;
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
                    <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                    <a href="voting.php" class="sidebar-btn active"><i
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

            <div class="col-12 col-xl-10">
                <div class="row g-0">


                    <div class="col-12 p-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-4 me-3">
                            <div>
                                <h2 class="fw-bold"><?= htmlspecialchars($_SESSION['fullname']); ?></h2>
                                <h5 class="text-muted">Voting</h5>
                            </div>
                            <div class="role-access-card">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-circle role-icon"><i class="bi bi-shield-lock"></i></div>
                                    <div>
                                        <small class="label-text">Current Role</small>
                                        <div class="role-value">STUDENT</div>
                                    </div>
                                </div>
                                <div class="divider"></div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-circle access-icon"><i class="bi bi-shield-check"></i></div>
                                    <div>
                                        <small class="label-text">Access Level</small>
                                        <div class="access-value" style="font-size:18px;">Vote &amp; View Only</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info banner -->
                    <div class="col-12 p-2">
                        <div class="info-banner">
                            <i class="bi bi-shield-lock-fill"></i>
                            <p>
                                Your vote is <strong>anonymous</strong> — your identity is never shown alongside
                                results. You may vote <strong>once per session</strong>, and full results are
                                revealed only after a session closes to keep voting fair and unbiased.
                            </p>
                        </div>
                    </div>


                    <?php if (isset($_GET['voted']) && $_GET['voted'] == 1) { ?>
                        <div class="col-12 p-2">
                            <div class="success-banner">
                                <i class="bi bi-check-circle-fill"></i>
                                <p>
                                    <strong>Your vote has been recorded!</strong>
                                    Thank you for participating. Results for this session will be visible once it closes.
                                </p>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- STATS -->
                    <div class="col-6 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-lightning-charge text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Open for Voting</small>
                                    <h3 class="mb-0"><?= $active_sessions_count; ?></h3>
                                    <small class="text-muted">Active sessions</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-primary-subtle p-3">
                                        <i class="bi bi-hand-index-thumb text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Your Total Votes</small>
                                    <h3 class="mb-0"><?= $my_votes_count; ?></h3>
                                    <small class="text-muted">Sessions you joined</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4 p-2 mb-3">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-warning-subtle p-3">
                                        <i class="bi bi-archive text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Closed Sessions</small>
                                    <h3 class="mb-0"><?= $closed_sessions_count; ?></h3>
                                    <small class="text-muted">Results available</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- SESSION CARDS -->
                <div class="row g-0">
                    <div class="col-12 p-2 me-3">

                        <?php if (mysqli_num_rows($sessions_query) == 0) { ?>
                            <div class="card page-card shadow border border-gray">
                                <div class="card-body empty-state">
                                    <i class="bi bi-hand-index-thumb"></i>
                                    <p class="fw-semibold mb-1">No voting sessions available</p>
                                    <small>Check back later for new participatory budget votes.</small>
                                </div>
                            </div>
                        <?php } ?>

                        <?php while ($sess = mysqli_fetch_assoc($sessions_query)) {


                            $proj_res = mysqli_query(
                                $conn,
                                "SELECT p.project_id, p.project_code, p.project_title, p.project_description,
                                        COUNT(v.vote_id) AS vote_count
                                 FROM voting_session_projects vsp
                                 JOIN projects p ON vsp.project_id = p.project_id
                                 LEFT JOIN votes v
                                     ON v.project_id = p.project_id
                                     AND v.session_id = {$sess['session_id']}
                                 WHERE vsp.session_id = {$sess['session_id']}
                                 GROUP BY p.project_id
                                 ORDER BY vote_count DESC"
                            );

                            $projects_in_sess = [];
                            while ($pr = mysqli_fetch_assoc($proj_res))
                                $projects_in_sess[] = $pr;


                            $my_vote = mysqli_fetch_assoc(
                                mysqli_query(
                                    $conn,
                                    "SELECT v.*, p.project_title FROM votes v
                                     JOIN projects p ON v.project_id = p.project_id
                                     WHERE v.session_id = {$sess['session_id']}
                                     AND v.student_id = $student_id"
                                )
                            );

                            $is_active = $sess['status'] == 'Active';
                            $today = date('Y-m-d');
                            $in_window = ($today >= $sess['start_date'] && $today <= $sess['end_date']);
                            $can_vote = $is_active && $in_window && !$my_vote;

                            $card_class = $my_vote ? 'voted-card' : (!$is_active ? 'closed-card' : '');
                        ?>

                            <div class="session-card <?= $card_class; ?>">

                                <!-- Header -->
                                <div class="session-header">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($sess['title']); ?></h5>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('M d, Y', strtotime($sess['start_date'])); ?> →
                                            <?= date('M d, Y', strtotime($sess['end_date'])); ?>
                                        </small>
                                        <?php if ($sess['description']) { ?>
                                            <p class="text-muted mt-1 mb-0" style="font-size:13px;">
                                                <?= htmlspecialchars($sess['description']); ?>
                                            </p>
                                        <?php } ?>
                                    </div>
                                    <div class="d-flex flex-column align-items-end gap-2">
                                        <?php if ($my_vote) { ?>
                                            <span class="status-badge-voted"><i class="bi bi-check-circle-fill me-1"></i>You
                                                Voted</span>
                                        <?php } elseif ($is_active && $in_window) { ?>
                                            <span class="status-badge-active"><i
                                                    class="bi bi-lightning-charge me-1"></i>Open</span>
                                        <?php } else { ?>
                                            <span class="status-badge-closed"><i class="bi bi-lock me-1"></i>Closed</span>
                                        <?php } ?>
                                        <small class="text-muted"><?= $sess['project_count']; ?>
                                            project<?= $sess['project_count'] != 1 ? 's' : ''; ?></small>
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="session-body">

                                    <?php if ($my_vote) { ?>

                                        <!-- Already voted -->
                                        <div class="voted-confirmation mb-3">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <div>
                                                <strong>You voted for:
                                                    <?= htmlspecialchars($my_vote['project_title']); ?></strong><br>
                                                <small class="text-muted">
                                                    Cast on <?= date('M d, Y h:i A', strtotime($my_vote['voted_at'])); ?>
                                                </small>
                                            </div>
                                        </div>

                                    <?php } ?>

                                    <?php if ($is_active) { ?>


                                        <div class="locked-results">
                                            <i class="bi bi-eye-slash"></i>
                                            <strong>Results are hidden while voting is open</strong>
                                            <p class="mb-0 mt-1" style="font-size:13px;">
                                                Vote counts will be revealed once this session closes,
                                                to keep the process fair for everyone.
                                            </p>
                                        </div>

                                    <?php } else { ?>


                                        <?php if (empty($projects_in_sess)) { ?>
                                            <p class="text-muted text-center">No projects were linked to this session.</p>
                                        <?php } else { ?>
                                            <?php foreach ($projects_in_sess as $idx => $pr) {
                                                $pct = round(($pr['vote_count'] / max($sess['total_votes_cast'], 1)) * 100);
                                                $is_leader = $idx === 0 && $pr['vote_count'] > 0;
                                                $is_my_pick = $my_vote && $my_vote['project_id'] == $pr['project_id'];
                                            ?>
                                                <div class="vote-bar-wrap">
                                                    <div class="vote-bar-label">
                                                        <span>
                                                            <?php if ($is_leader) { ?><i
                                                                    class="bi bi-trophy-fill text-warning me-1"></i><?php } ?>
                                                            <strong><?= htmlspecialchars($pr['project_code']); ?></strong>
                                                            — <?= htmlspecialchars($pr['project_title']); ?>
                                                            <?php if ($is_my_pick) { ?>
                                                                <span class="badge bg-primary ms-1" style="font-size:10px;">Your Vote</span>
                                                            <?php } ?>
                                                        </span>
                                                        <span class="fw-bold" style="color:rgb(134,9,9);">
                                                            <?= $pr['vote_count']; ?> vote<?= $pr['vote_count'] != 1 ? 's' : ''; ?>
                                                            (<?= $pct; ?>%)
                                                        </span>
                                                    </div>
                                                    <div class="vote-bar-track">
                                                        <div class="vote-bar-fill"
                                                            style="width:<?= $pct; ?>%;
                                                    <?= $is_leader ? 'background:linear-gradient(90deg,#8B0000,#e74c3c);' : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

                                            <?php if (!empty($projects_in_sess) && $projects_in_sess[0]['vote_count'] > 0) { ?>
                                                <div class="mt-3 p-3 rounded-3" style="background:#d1e7dd;border:1px solid #a3cfbb;">
                                                    <i class="bi bi-trophy-fill text-warning me-2"></i>
                                                    <strong>Winning Project:</strong>
                                                    <?= htmlspecialchars($projects_in_sess[0]['project_title']); ?>
                                                    with <?= $projects_in_sess[0]['vote_count']; ?> votes
                                                </div>
                                            <?php } ?>
                                        <?php } ?>

                                    <?php } ?>
                                </div>

                                <!-- Footer -->
                                <div class="session-footer">
                                    <?php if ($can_vote) { ?>
                                        <button class="btn vote-submit-btn" data-bs-toggle="modal"
                                            data-bs-target="#voteModal<?= $sess['session_id']; ?>">
                                            <i class="bi bi-hand-index-thumb me-1"></i> Cast Your Vote
                                        </button>
                                    <?php } elseif (!$is_active) { ?>
                                        <button class="btn btn-sm btn-light border" disabled>
                                            <i class="bi bi-lock me-1"></i> Voting Closed
                                        </button>
                                    <?php } elseif (!$in_window) { ?>
                                        <button class="btn btn-sm btn-light border" disabled>
                                            <i class="bi bi-clock-history me-1"></i> Not Yet Open
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>



                            <?php if ($can_vote) { ?>
                                <div class="modal fade" id="voteModal<?= $sess['session_id']; ?>" tabindex="-1"
                                    data-bs-backdrop="static" data-bs-keyboard="false">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">

                                            <div class="modal-header text-white" style="background-color:rgb(134,9,9);">
                                                <h5 class="modal-title fw-bold">
                                                    <i class="bi bi-hand-index-thumb me-2"></i>
                                                    Vote: <?= htmlspecialchars($sess['title']); ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white"
                                                    data-bs-dismiss="modal"></button>
                                            </div>

                                            <form method="POST" id="voteForm<?= $sess['session_id']; ?>">
                                                <input type="hidden" name="session_id" value="<?= $sess['session_id']; ?>">

                                                <div class="modal-body p-4">

                                                    <div class="alert alert-warning border-0 rounded-3 mb-3"
                                                        style="font-size:13px;">
                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                        You can only vote <strong>once</strong> in this session.
                                                        Please choose carefully — this cannot be changed after submitting.
                                                    </div>

                                                    <?php if (empty($projects_in_sess)) { ?>
                                                        <p class="text-muted text-center">No projects available to vote on.</p>
                                                    <?php } else { ?>
                                                        <?php foreach ($projects_in_sess as $pr) { ?>
                                                            <label class="project-choice"
                                                                onclick="selectProject(<?= $sess['session_id']; ?>, this)">
                                                                <input type="radio" name="project_id" value="<?= $pr['project_id']; ?>"
                                                                    required>
                                                                <div>
                                                                    <div class="proj-title">
                                                                        <?= htmlspecialchars($pr['project_title']); ?></div>
                                                                    <div class="proj-code"><?= htmlspecialchars($pr['project_code']); ?>
                                                                    </div>
                                                                    <?php if (!empty($pr['project_description'])) { ?>
                                                                        <div class="text-muted mt-1" style="font-size:12.5px;">
                                                                            <?= htmlspecialchars(mb_strimwidth($pr['project_description'], 0, 120, '...')); ?>
                                                                        </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </label>
                                                        <?php } ?>
                                                    <?php } ?>

                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                                        <i class="bi bi-x-lg"></i> Cancel
                                                    </button>
                                                    <button type="submit" name="cast_vote" class="btn vote-submit-btn"
                                                        id="submitBtn<?= $sess['session_id']; ?>" disabled>
                                                        <i class="bi bi-check-circle me-1"></i> Confirm My Vote
                                                    </button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                        <?php }  ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function selectProject(sessionId, el) {
            // Visually mark selection within this modal only
            const modal = document.getElementById('voteModal' + sessionId);
            modal.querySelectorAll('.project-choice').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');

            // Enable confirm button
            document.getElementById('submitBtn' + sessionId).disabled = false;
        }


        document.querySelectorAll('form[id^="voteForm"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const checked = form.querySelector('input[name="project_id"]:checked');
                if (!checked) {
                    e.preventDefault();
                    alert('Please select a project before confirming your vote.');
                    return;
                }
                const projTitle = checked.closest('.project-choice').querySelector('.proj-title').textContent.trim();
                const confirmed = confirm('Confirm your vote for "' + projTitle + '"?\n\nThis action cannot be undone.');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>

</html>