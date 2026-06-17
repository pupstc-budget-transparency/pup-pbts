<?php

require '../includes/auth.php';
require '../includes/config.php';

$user_id = (int) $_SESSION['user_id'];


$success_msg = '';
$error_msg = '';

if (isset($_POST['submit_feedback'])) {

    $message = trim($_POST['message']);

    if (empty($message)) {
        $error_msg = "Please write your feedback before submitting.";
    } elseif (strlen($message) < 10) {
        $error_msg = "Feedback must be at least 10 characters long.";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO feedback (user_id, message) VALUES (?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
        mysqli_stmt_execute($stmt);

        $success_msg = "Your feedback has been submitted successfully! Thank you.";
    }
}


$history = mysqli_query(
    $conn,
    "SELECT * FROM feedback
     WHERE user_id = $user_id
     ORDER BY feedback_id DESC"
);

$my_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE user_id=$user_id"))['t'];
$my_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE user_id=$user_id AND status='Pending'"))['t'];
$my_resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM feedback WHERE user_id=$user_id AND status='Resolved'"))['t'];

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC – My Feedback</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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

    .section-header {
        background: linear-gradient(135deg, #8B0000, #c0392b);
        color: white;
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 28px;
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

    .compose-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
    }

    .compose-card .card-header {
        background: linear-gradient(135deg, #8B0000, #c0392b);
        color: white;
        border-radius: 18px 18px 0 0 !important;
        padding: 18px 24px;
        border: none;
    }

    .compose-textarea {
        border-radius: 12px !important;
        border: 2px solid #e9ecef !important;
        font-size: 15px;
        transition: border-color .2s;
        resize: none;
    }

    .compose-textarea:focus {
        border-color: rgb(134, 9, 9) !important;
        box-shadow: 0 0 0 3px rgba(139, 0, 0, .1) !important;
    }

    .char-counter {
        font-size: 12px;
        color: #aaa;
    }

    .char-counter.warn {
        color: #e67e22;
    }

    .char-counter.danger {
        color: #e74c3c;
    }

    .history-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .07);
        transition: .3s;
        margin-bottom: 16px;
    }

    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
    }

    .history-card .card-body {
        padding: 20px 24px;
    }

    .msg-bubble-student {
        background: #f8f9fa;
        border-left: 4px solid rgb(134, 9, 9);
        border-radius: 0 10px 10px 0;
        padding: 14px 18px;
        font-size: 14px;
        line-height: 1.7;
        color: #444;
        white-space: pre-wrap;
        margin-top: 12px;
    }

    /* status badges */
    .badge-pending {
        background: #fff3cd;
        color: #856404;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge-reviewed {
        background: #cfe2ff;
        color: #084298;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    .badge-resolved {
        background: #d1e7dd;
        color: #0a3622;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
    }

    /* timeline dot */
    .timeline-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 6px;
    }

    .dot-pending {
        background: #ffc107;
    }

    .dot-reviewed {
        background: #0d6efd;
    }

    .dot-resolved {
        background: #198754;
    }

    /* empty state */
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #bbb;
    }

    .empty-state i {
        font-size: 56px;
        display: block;
        margin-bottom: 16px;
    }

    .alert-custom-success {
        background: #d1e7dd;
        border: none;
        border-radius: 12px;
        color: #0a3622;
        padding: 16px 20px;
        font-weight: 500;
    }

    .alert-custom-error {
        background: #f8d7da;
        border: none;
        border-radius: 12px;
        color: #842029;
        padding: 16px 20px;
        font-weight: 500;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
    }
</style>

<body>

    <!-- TOP NAV -->
    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color:rgb(134,9,9);">
        <div class="container-xl p-3 d-flex justify-content-between">
            <h6 class="mb-0">PUPSTC Participatory Budget Transparency System</h6>
            <span><strong><?= htmlspecialchars($_SESSION['fullname']); ?></strong></span>
        </div>
    </div>

    <div class="container-fluid px-0">
        <div class="row g-0">

            <!-- SIDEBAR -->
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
                            <a href="budget_explore.php" class="sidebar-btn"><i
                                    class="bi bi-wallet2"></i><span>Budget</span></a>
                            <a href="projects.php" class="sidebar-btn"><i class="bi bi-kanban"></i><span>Projects</span></a>
                            <a href="voting.php" class="sidebar-btn"><i
                                    class="bi bi-hand-index-thumb"></i><span>Voting</span></a>
                            <a href="reports.php" class="sidebar-btn"><i
                                    class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
                            <a href="feedback.php" class="sidebar-btn active"><i
                                    class="bi bi-envelope-paper"></i><span>Feedback</span></a>
                            <a href="announcement.php" class="sidebar-btn"><i
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

                    <!-- MAIN CONTENT -->
                    <div class="col-12 col-xl-10 p-4">

                        <div class="section-header">
                            <div class="icon-wrap"><i class="bi bi-envelope-paper-fill"></i></div>
                            <div>
                                <h3 class="fw-bold mb-1">Send Feedback</h3>
                                <p class="mb-0" style="opacity:.85;">
                                    Share your thoughts, suggestions, or concerns about budget transparency and projects.
                                    Your voice matters to us!
                                </p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-4">
                                <div class="card stat-card">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <div class="stat-icon" style="background:#fff3cd;">
                                            <i class="bi bi-envelope-paper text-warning fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted" style="font-size:12px;">My Submissions</div>
                                            <div class="fw-bold fs-4"><?= $my_total; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card stat-card">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <div class="stat-icon" style="background:#fff3cd;">
                                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted" style="font-size:12px;">Pending</div>
                                            <div class="fw-bold fs-4"><?= $my_pending; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card stat-card">
                                    <div class="card-body d-flex align-items-center gap-3">
                                        <div class="stat-icon" style="background:#d1e7dd;">
                                            <i class="bi bi-check-circle text-success fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted" style="font-size:12px;">Resolved</div>
                                            <div class="fw-bold fs-4"><?= $my_resolved; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-12 col-xl-5 mb-4">
                                <div class="card compose-card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0 fw-bold">
                                            <i class="bi bi-pencil-square me-2"></i>Write Your Feedback
                                        </h5>
                                        <small style="opacity:.8;">All feedback is confidential and reviewed by our team</small>
                                    </div>
                                    <div class="card-body p-4">

                                        <?php if ($success_msg) { ?>
                                            <div class="alert-custom-success mb-4">
                                                <i class="bi bi-check-circle-fill me-2"></i><?= $success_msg; ?>
                                            </div>
                                        <?php } ?>
                                        <?php if ($error_msg) { ?>
                                            <div class="alert-custom-error mb-4">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_msg; ?>
                                            </div>
                                        <?php } ?>

                                        <form method="POST" id="feedbackForm">

                                            <!-- Category hint chips -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-muted" style="font-size:13px;">
                                                    FEEDBACK TOPIC (optional hint)
                                                </label>
                                                <div class="d-flex flex-wrap gap-2" id="chips">
                                                    <?php
                                                    $chips = [
                                                        'Budget Transparency',
                                                        'Project Updates',
                                                        'Voting Process',
                                                        'Announcements',
                                                        'Website Issues',
                                                        'General Suggestion'
                                                    ];
                                                    foreach ($chips as $chip) { ?>
                                                        <span class="chip badge rounded-pill border"
                                                            style="cursor:pointer; padding:7px 14px; font-size:12px; font-weight:500; color:#555; background:#f8f9fa; transition:.2s;"
                                                            onclick="insertChip('<?= $chip ?>')">
                                                            <?= $chip ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Your Message <span
                                                        class="text-danger">*</span></label>
                                                <textarea name="message" id="msgArea" class="form-control compose-textarea"
                                                    rows="8"
                                                    placeholder="Describe your feedback here... Be as detailed as possible so we can address your concern effectively."
                                                    maxlength="2000" oninput="updateCounter(this)"
                                                    required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">Minimum 10 characters</small>
                                                    <small class="char-counter" id="charCount">0 / 2000</small>
                                                </div>
                                            </div>

                                            <div class="mb-4 p-3 rounded-3"
                                                style="background:#fff8f8; border:1px solid #f5c6cb;">
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle text-danger me-1"></i>
                                                    Your feedback will be reviewed by the Student Affairs office.
                                                    Please be respectful and constructive in your message.
                                                </small>
                                            </div>

                                            <button type="submit" name="submit_feedback"
                                                class="btn w-100 text-white fw-bold py-3"
                                                style="background:linear-gradient(135deg,#8B0000,#c0392b); border-radius:12px; font-size:15px;">
                                                <i class="bi bi-send-fill me-2"></i>Submit Feedback
                                            </button>

                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-7 mb-4">
                                <h5 class="fw-bold mb-3">
                                    <i class="bi bi-clock-history me-2" style="color:rgb(134,9,9);"></i>
                                    My Feedback History
                                </h5>

                                <?php if (mysqli_num_rows($history) == 0) { ?>
                                    <div class="card compose-card">
                                        <div class="card-body empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p class="fw-semibold mb-1">No feedback submitted yet</p>
                                            <small>Use the form on the left to share your thoughts!</small>
                                        </div>
                                    </div>
                                <?php } ?>

                                <?php while ($fb = mysqli_fetch_assoc($history)) { ?>
                                    <div class="card history-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="timeline-dot
                                    <?= $fb['status'] == 'Pending' ? 'dot-pending' : ($fb['status'] == 'Reviewed' ? 'dot-reviewed' : 'dot-resolved') ?>">
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar3 me-1"></i>
                                                            <?= date('F d, Y \a\t h:i A', strtotime($fb['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div>
                                                    <?php if ($fb['status'] == 'Pending') { ?>
                                                        <span class="badge-pending"><i class="bi bi-clock me-1"></i>Pending</span>
                                                    <?php } elseif ($fb['status'] == 'Reviewed') { ?>
                                                        <span class="badge-reviewed"><i class="bi bi-eye me-1"></i>Reviewed</span>
                                                    <?php } else { ?>
                                                        <span class="badge-resolved"><i
                                                                class="bi bi-check-circle me-1"></i>Resolved</span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="msg-bubble-student">
                                                <?= htmlspecialchars($fb['message']); ?>
                                            </div>

                                            <?php if ($fb['status'] == 'Resolved') { ?>
                                                <div class="mt-2 p-2 rounded-3 d-flex align-items-center gap-2"
                                                    style="background:#d1e7dd; font-size:13px; color:#0a3622;">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    Your feedback has been resolved. Thank you for reaching out!
                                                </div>
                                            <?php } elseif ($fb['status'] == 'Reviewed') { ?>
                                                <div class="mt-2 p-2 rounded-3 d-flex align-items-center gap-2"
                                                    style="background:#cfe2ff; font-size:13px; color:#084298;">
                                                    <i class="bi bi-eye-fill"></i>
                                                    Your feedback is currently being reviewed by our team.
                                                </div>
                                            <?php } else { ?>
                                                <div class="mt-2 p-2 rounded-3 d-flex align-items-center gap-2"
                                                    style="background:#fff3cd; font-size:13px; color:#856404;">
                                                    <i class="bi bi-hourglass-split"></i>
                                                    Your feedback is in the queue and will be addressed soon.
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                // Character counter
                function updateCounter(el) {
                    const count = el.value.length;
                    const max = el.maxLength;
                    const el2 = document.getElementById('charCount');
                    el2.textContent = count + ' / ' + max;
                    el2.className = 'char-counter' + (count > 1800 ? ' danger' : count > 1500 ? ' warn' : '');
                }

                // Init counter on load
                const area = document.getElementById('msgArea');
                if (area && area.value.length) updateCounter(area);

                // Topic chips
                function insertChip(text) {
                    const area = document.getElementById('msgArea');
                    const prefix = '[' + text + '] ';
                    if (!area.value.startsWith('[')) {
                        area.value = prefix + area.value;
                    } else {
                        // replace existing chip
                        area.value = area.value.replace(/^\[.*?\]\s*/, prefix);
                    }
                    updateCounter(area);
                    area.focus();

                    // highlight active chip
                    document.querySelectorAll('.chip').forEach(c => {
                        c.style.background = c.textContent.trim() === text ? 'rgb(134,9,9)' : '#f8f9fa';
                        c.style.color = c.textContent.trim() === text ? 'white' : '#555';
                        c.style.borderColor = c.textContent.trim() === text ? 'rgb(134,9,9)' : '#dee2e6';
                    });
                }
            </script>
</body>

</html>