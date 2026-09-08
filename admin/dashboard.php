<?php

require_once "../config.php";

// ตรวจสอบ Login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบสิทธิ์
if ($_SESSION["role"] != "admin") {
    header("Location: ../dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<h1>ระบบตัดเกรดโรงเรียนสาธิต</h1>

<p>
    ยินดีต้อนรับ
    <?php echo htmlspecialchars($_SESSION["fullname"]); ?>
</p>

<p>
    สิทธิ์:
    <?php echo $_SESSION["role"]; ?>
</p>

<hr>

<h2>งานวิชาการ / Admin</h2>

<ul>

    <li>
        <a href="students.php">
            จัดการนักเรียน
        </a>
    </li>

    <li>
        <a href="teachers.php">
            จัดการครู
        </a>
    </li>

    <li>
        <a href="parents.php">
            จัดการผู้ปกครอง
        </a>
    </li>

    <li>
        <a href="classrooms.php">
            จัดการห้องเรียน
        </a>
    </li>

    <li>
        <a href="subjects.php">
            จัดการรายวิชา
        </a>
    </li>

    <li>
        <a href="scores.php">
            จัดการคะแนน
        </a>
    </li>

    <li>
        <a href="grades.php">
            จัดการเกรด
        </a>
    </li>

    <li>
        <a href="reports.php">
            รายงานผลการเรียน
        </a>
    </li>

    <li>
        <a href="../logout.php">
            ออกจากระบบ
        </a>
    </li>

</ul>

</body>
</html>