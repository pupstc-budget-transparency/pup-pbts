<?php


require '../includes/auth.php';

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PUPSTC STUDENT SIDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>


<body>


    <div class="container-fluid  text-white shadow-sm sticky-top" style="background-color: rgb(134, 9, 9);">
        <div class="py-3">
            <h5 class="mb-0">
                PUPSTC Participatory Budget Transparency System
            </h5>
        </div>
    </div>

    <div class="d-flex flex-column gap-4 p-3 pt-5 bg-danger"
        style="background: linear-gradient(180deg, #6b0000 0%, #3d0000 100%); width: 180px; min-height: 100vh;">
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Dashboard</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Projects</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Reports</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Expenditures</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Vote</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Feedback</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Notifications</a>
        <a href="#" class="btn rounded-pill text-dark"
            style="background: rgba(255, 255, 255, 0.93); font-size:12px;">Announcements</a>
        <div class="mt-5">
            <a href="#" class="btn rounded-pill w-100 text-white-50"
                style="background: rgba(255,255,255,0.08); font-size:12px; font-size:12px;">Log out</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

</body>

</html>