<?php

require '../includes/auth.php';
require '../includes/config.php';

$role = $_SESSION['role'];

$search = '';

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );
}

$limit = 10;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

$offset = ($page - 1) * $limit;

$count_sql = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE
        fullname LIKE '%$search%'
        OR email LIKE '%$search%'
        OR role LIKE '%$search%'
";

$count_query = mysqli_query($conn, $count_sql);

$total_records = mysqli_fetch_assoc($count_query)['total'];

$total_pages = ceil($total_records / $limit);

$sql = "
    SELECT *
    FROM users
    WHERE
        fullname LIKE '%$search%'
        OR email LIKE '%$search%'
        OR role LIKE '%$search%'
    ORDER BY user_id DESC
    LIMIT $offset, $limit
";

$query = mysqli_query($conn, $sql);

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

    /* Hover Effect */
    .sidebar-btn:hover {
        background: white;
        color: #7a0000;
        transform: translateX(20px);
        border-radius: 30px 0 0 30px;
    }

    /* Active Page */
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
        width: 200px;
        height: 100vh;
        background: linear-gradient(180deg, #6b0000, #3d0000);
        overflow: hidden;
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

    .role-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
    }

    .role-admin {
        background: #ede9fe;
        color: #6d28d9;
    }

    .role-student {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .role-affairs {
        background: #fef3c7;
        color: #b45309;
    }

    .role-budget {
        background: #fce7f3;
        color: #be185d;
    }

    .role-project {
        background: #d1fae5;
        color: #047857;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 13px;
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

        box-shadow:
            0 8px 25px rgba(0, 0, 0, .06);

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
        <div class="row g-0">
            <div class="col-12 col-xl-2">
                <div class="sidebar d-flex flex-column gap-3 p-3 pt-5 position-sticky" style=" top: 0; height: 100vh;">
                
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

                        <a href="users.php" class="sidebar-btn active">
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
                    <div class="col-12 col-xl-12 p-2 mt-3 ">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <div>


                                    <h2 class="fw-bold">
                                        <?php echo $_SESSION['fullname']; ?>

                                    </h2>
                                    <h5>Users Management</h5>
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

                    <div class="col-12 col-xl-3 p-2">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">


                                <div class="me-3">
                                    <div class="rounded-circle bg-danger-subtle p-3">
                                        <i class="bi bi-people text-danger fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted">Total Users</small>
                                    <h3 class="mb-0">
                                        <?= mysqli_num_rows($query); ?>
                                    </h3>
                                    <small class="text-muted">
                                        All registered users
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-3 p-2">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-success-subtle p-3">
                                        <i class="bi bi-check-circle text-success fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted">Active Users</small>
                                    <h3 class="mb-0">
                                        <?php
                                        $active = mysqli_query(
                                            $conn,
                                            "SELECT COUNT(*) total
                                                 FROM users
                                                 WHERE status='active'"
                                        );
                                        echo mysqli_fetch_assoc($active)['total'];
                                        ?>
                                    </h3>
                                    <small class="text-muted">
                                        Currently active
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>


                    <div class="col-12 col-xl-3 p-2">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-warning-subtle p-3">
                                        <i class="bi bi-mortarboard text-warning fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted">Students</small>
                                    <h3 class="mb-0">
                                        <?php
                                        $students = mysqli_query(
                                            $conn,
                                            "SELECT COUNT(*) total
                             FROM users
                             WHERE role='student'"
                                        );
                                        echo mysqli_fetch_assoc($students)['total'];
                                        ?>
                                    </h3>
                                    <small class="text-muted">
                                        Student accounts
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-3 p-2">
                        <div class="card stats-card shadow border border-gray">
                            <div class="card-body d-flex align-items-center">

                                <div class="me-3">
                                    <div class="rounded-circle bg-primary-subtle p-3">
                                        <i class="bi bi-shield-check text-primary fs-4"></i>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted">Administrators</small>
                                    <h3 class="mb-0">
                                        <?php
                                        $admins = mysqli_query(
                                            $conn,
                                            "SELECT COUNT(*) total
                                                FROM users
                                                WHERE role='super_admin'"
                                        );
                                        echo mysqli_fetch_assoc($admins)['total'];
                                        ?>
                                    </h3>
                                    <small class="text-muted">
                                        Admin accounts
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row g-0">
                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <div class="d-flex justify-content-start ">
                            <a href="add-user.php" class="btn text-light px-4 py-2 shadow border border-gray"
                                style="background-color: rgb(134,9,9);font-size:16px; font-weight:600; border-radius:10px;">

                                <i class="bi bi-plus-lg"></i>
                                Add User

                            </a>

                        </div>
                    </div>

                    <div class="col-12 col-xl-6 p-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center  ">
                            <div class="text-muted">
                                Showing
                                <?= mysqli_num_rows($query); ?>
                                users
                            </div>
                            <form method="GET" class="d-flex gap-2">

                                <input type="text" name="search" class="form-control" placeholder="Search users..."
                                    value="<?= htmlspecialchars($search) ?>" style="width:250px;">

                                <button type="submit" class="btn text-light" style="background-color: rgb(134,9,9);">

                                    <i class="bi bi-search"></i>
                                    Search

                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    Clear
                                </a>
                            </form>


                        </div>
                    </div>

                    <div class="row g-0">
                        <div class="col-12 col-xl-12 p-2">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="flex-grow-1">
                                    <div class="card page-card shadow border border-gray">

                                        <div class="card-body">
                                            <div class="table-responsive ">
                                                <table
                                                    class="table users-table align-middle  table-bordered table-striped ">

                                                    <thead class="pup-header">
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Full Name</th>
                                                            <th>Email</th>
                                                            <th>Role</th>
                                                            <th>Status</th>
                                                            <th width="150">Action</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>

                                                        <?php mysqli_data_seek($query, 0); ?>

                                                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>

                                                            <tr>
                                                                <td>
                                                                    #<?= $row['user_id']; ?>
                                                                </td>

                                                                <td>
                                                                    <strong><?= $row['fullname']; ?></strong>
                                                                </td>

                                                                <td>
                                                                    <?= $row['email']; ?>
                                                                </td>

                                                                <td>
                                                                    <?php
                                                                    $roleClass = '';

                                                                    switch ($row['role']) {

                                                                        case 'super_admin':
                                                                            $roleClass = 'role-admin';
                                                                            break;

                                                                        case 'student':
                                                                            $roleClass = 'role-student';
                                                                            break;

                                                                        case 'student_affairs':
                                                                            $roleClass = 'role-affairs';
                                                                            break;

                                                                        case 'budget_officer':
                                                                            $roleClass = 'role-budget';
                                                                            break;

                                                                        case 'project_coordinator':
                                                                            $roleClass = 'role-project';
                                                                            break;
                                                                    }
                                                                    ?>

                                                                    <span class="role-badge <?= $roleClass ?>">
                                                                        <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                                                                    </span>
                                                                </td>

                                                                <td>
                                                                    <span class="status-active">
                                                                        ● <?= ucfirst($row['status']) ?>
                                                                    </span>
                                                                </td>

                                                                <td>
                                                                    <a href="edit-user.php?id=<?= $row['user_id']; ?>"
                                                                        class="btn action-btn text-dark"
                                                                        style="background-color: #FFC72C;">
                                                                        <i class="bi bi-pencil"></i>

                                                                    </a>

                                                                    <a href="delete-user.php?id=<?= $row['user_id']; ?>"
                                                                        class="btn action-btn text-light"
                                                                        style="background-color: rgb(134,9,9);">
                                                                        <i class="bi bi-trash"></i>

                                                                    </a>
                                                                </td>
                                                            </tr>

                                                        <?php } ?>

                                                    </tbody>
                                                </table>
                                                <div class="d-flex justify-content-between align-items-center mt-4">

                                                    <small class="text-muted">

                                                        Showing
                                                        <?= $offset + 1 ?>
                                                        to
                                                        <?= min($offset + $limit, $total_records) ?>
                                                        of
                                                        <?= $total_records ?>
                                                        entries

                                                    </small>

                                                    <nav>
                                                        <ul class="pagination mb-0">

                                                            <!-- Previous -->

                                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                                <a class="page-link" href="?page=<?= $page - 1 ?>">

                                                                    &laquo;

                                                                </a>
                                                            </li>

                                                            <!-- Page Numbers -->

                                                            <?php
                                                            for (
                                                                $i = 1;
                                                                $i <= $total_pages;
                                                                $i++
                                                            ) {
                                                                ?>

                                                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                                                                    <a class="page-link" href="?page=<?= $i ?>">

                                                                        <?= $i ?>

                                                                    </a>

                                                                </li>

                                                            <?php } ?>

                                                            <!-- Next -->

                                                            <li
                                                                class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">

                                                                <a class="page-link" href="?page=<?= $page + 1 ?>">

                                                                    &raquo;

                                                                </a>
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
    </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>