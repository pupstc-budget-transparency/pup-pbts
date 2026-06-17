<?php

require '../includes/auth.php';
require '../includes/config.php';


$role = $_SESSION['role'];

$message = "";

if (isset($_POST['add_expenditure'])) {

    $budget_id = $_POST['budget_id'];
    $category = trim($_POST['category']);
    $project_id = trim($_POST['project_id']);
    $description = trim($_POST['description']);
    $amount = $_POST['amount'];
    $payment_method = trim($_POST['payment_method']);
    $expenditure_date = $_POST['expenditure_date'];
    $status = trim($_POST['status']);
    $reference_no = 'EXP-' . date('YmdHis');

    if (empty($budget_id)) {

        $message = "Please select a budget allocation.";
    } elseif (empty($category)) {

        $message = "Category is required.";
    } elseif (empty($project_id)) {

        $message = "Please select a project.";
    } elseif (empty($description)) {

        $message = "Description is required.";
    } elseif (empty($amount)) {

        $message = "Amount is required.";
    } elseif ($amount <= 0) {

        $message = "Amount must be greater than zero.";
    } elseif (empty($payment_method)) {

        $message = "Please select a payment method.";
    } elseif (empty($expenditure_date)) {

        $message = "Please select an expenditure date.";
    } elseif (empty($status)) {

        $message = "Please select a status.";
    } else {

        $budget_query = mysqli_query(
            $conn,
            "SELECT total_amount
             FROM budgets
             WHERE budget_id = $budget_id"
        );

        $budget = mysqli_fetch_assoc($budget_query);

        $spent_query = mysqli_query(
            $conn,
            "SELECT IFNULL(SUM(amount),0) AS total_spent
             FROM expenditures
             WHERE budget_id = $budget_id
             AND record_status='active'"
        );

        $spent = mysqli_fetch_assoc($spent_query);

        $remaining = $budget['total_amount'] - $spent['total_spent'];

        if ($amount > $remaining) {

            $message = "Insufficient budget balance. Remaining: ₱" .
                number_format($remaining, 2);
        }
    }



    if (empty($message)) {

        $project_check = mysqli_query(
            $conn,
            "SELECT *
             FROM projects
             WHERE project_id = $project_id
             AND budget_id = $budget_id"
        );

        if (mysqli_num_rows($project_check) == 0) {

            $message = "Selected project does not belong to the selected budget.";
        }

        if (empty($message)) {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO expenditures
                (
                    budget_id,
                    reference_no,
                    category,
                    project_id,
                    description,
                    amount,
                    payment_method,
                    expenditure_date,
                    status,
                    created_by,
                    record_status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active'
                )"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "issisdsssi",
                $budget_id,
                $reference_no,
                $category,
                $project_id,
                $description,
                $amount,
                $payment_method,
                $expenditure_date,
                $status,
                $_SESSION['user_id']
            );

            if (mysqli_stmt_execute($stmt)) {

                $new_id = mysqli_insert_id($conn);

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
                        'Added Expenditure',
                        'expenditures',
                        $new_id
                    )"
                );

                header("Location: expenditures.php");
                exit();
            } else {

                $message = "Failed to add expenditure.";
            }
        }
    }
}

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

    .form-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .section-title .icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #fff1f1;
        color: #800000;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-title h5 {
        margin: 0;
        color: #800000;
        font-weight: 600;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        min-height: 52px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #800000;
        box-shadow: 0 0 0 .15rem rgba(128, 0, 0, .15);
    }

    .create-btn {
        background: #800000;
        border: none;
        border-radius: 10px;
        padding: 10px 25px;
    }

    .create-btn:hover {
        background: #650000;
    }

    .cancel-btn {
        border-radius: 10px;
        padding: 10px 25px;
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
</style>

<body>

    <div class="container-fluid text-white shadow-sm sticky-top" style="background-color: rgb(134,9,9);">
        <div class="container-xl py-3 d-flex justify-content-between">
            <h6 class="mb-0">
                PUPSTC Participatory Budget Transparency System
            </h6>
            <span>
                <strong><?php echo $_SESSION['fullname']; ?></strong>
            </span>
        </div>
    </div>
    <div class="container-fluid px-0">
        <div class="row  g-0">
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5">

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

                    <a href="expenditures.php" class="sidebar-btn active">
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

                    <div class="col-12 col-xl-11 p-2 mt-3 ">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <div>
                                    <h2 class="fw-bold">
                                        Add New Budget Allocation
                                    </h2>
                                    <h5>budget Management</h5>
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
                    <div class="col-12 col-xl-11 p-2">
                        <div class="flex-grow-1">
                            <div class="card shadow border border-gray" style="border-radius: 16px;">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="card form-card">
                                            <div class="card-body p-4">
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-wallet2"></i>
                                                    </div>
                                                    <h5>1. Budget Information</h5>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12 mb-4">
                                                        <label class="form-label">
                                                            Budget Allocation
                                                        </label>

                                                        <select name="budget_id" class="form-select" required>

                                                            <option value="">
                                                                -- Select Budget --
                                                            </option>

                                                            <?php
                                                            $budgets = mysqli_query(
                                                                $conn,
                                                                "SELECT budget_id,budget_title
                                                                 FROM budgets
                                                                 WHERE record_status='active'
                                                                 ORDER BY budget_title"
                                                            );

                                                            while ($budget = mysqli_fetch_assoc($budgets)) {
                                                            ?>

                                                                <option value="<?= $budget['budget_id']; ?>">
                                                                    <?= htmlspecialchars($budget['budget_title']); ?>
                                                                </option>

                                                            <?php } ?>

                                                        </select>
                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-receipt"></i>
                                                    </div>
                                                    <h5>2. Expenditure Information</h5>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Category
                                                        </label>

                                                        <input type="text" name="category" class="form-control"
                                                            required>

                                                    </div>

                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Project
                                                        </label>

                                                        <select name="project_id" class="form-select" required>

                                                            <option value="">
                                                                -- Select Project --
                                                            </option>

                                                            <?php

                                                            $projects = mysqli_query(
                                                                $conn,
                                                                "SELECT project_id, project_title
                                                                 FROM projects
                                                                 WHERE record_status='active'
                                                                 ORDER BY project_title"
                                                            );

                                                            while ($project = mysqli_fetch_assoc($projects)) {

                                                            ?>

                                                                <option value="<?= $project['project_id']; ?>">
                                                                    <?= htmlspecialchars($project['project_title']); ?>
                                                                </option>

                                                            <?php } ?>

                                                        </select>
                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-cash-stack"></i>
                                                    </div>
                                                    <h5>3. Financial Details</h5>
                                                </div>

                                                <div class="row">

                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Amount
                                                        </label>

                                                        <input type="number" step="0.01" name="amount"
                                                            class="form-control" required>

                                                    </div>

                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Payment Method
                                                        </label>

                                                        <select name="payment_method" class="form-select" required>

                                                            <option value="Cash">Cash</option>
                                                            <option value="Check">Check</option>
                                                            <option value="Bank Transfer">Bank Transfer</option>

                                                        </select>

                                                    </div>
                                                </div>

                                                <hr>
                                                <div class="section-title">
                                                    <div class="icon">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </div>
                                                    <h5>4. Transaction Details</h5>
                                                </div>

                                                <div class="row">

                                                    <div class="col-md-12 mb-4">

                                                        <label class="form-label">
                                                            Description
                                                        </label>

                                                        <textarea name="description" class="form-control" rows="3"
                                                            required></textarea>

                                                    </div>

                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Expenditure Date
                                                        </label>

                                                        <input type="date" name="expenditure_date" class="form-control"
                                                            required>

                                                    </div>

                                                    <div class="col-md-6 mb-4">

                                                        <label class="form-label">
                                                            Status
                                                        </label>

                                                        <select name="status" class="form-select" required>

                                                            <option value="Pending">Pending</option>
                                                            <option value="Paid">Paid</option>

                                                        </select>

                                                    </div>
                                                </div>


                                                <div class="d-flex justify-content-end gap-3 mt-4">

                                                    <a href="add-expenditure.php"
                                                        class="btn btn-light border cancel-btn">

                                                        <i class="bi bi-x-lg"></i>
                                                        Cancel

                                                    </a>

                                                    <button type="submit" name="add_expenditure"
                                                        class="btn text-white create-btn">

                                                        <i class="bi bi-person-plus"></i>
                                                        Add Expenditure

                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="modal fade" id="errorModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        Validation Error
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white"
                                                        data-bs-dismiss="modal">
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    <?php echo $message; ?>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Close
                                                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($message)) { ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {

                var myModal = new bootstrap.Modal(
                    document.getElementById("errorModal")
                );

                myModal.show();

            });
        </script>

    <?php } ?>
</body>

</html>