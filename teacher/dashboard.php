<?php

require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["role"] != "teacher") {
    header("Location: ../dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Teacher Dashboard</title>

    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

<h1>ระบบตัดเกรดโรงเรียนสาธิต</h1>

<h2>Dashboard ครูประจำชั้น</h2>

<p>
    ครู:
    <?php echo htmlspecialchars($_SESSION["fullname"]); ?>
</p>

<hr>

<ul>

    <li>
        <a href="students.php">
            นักเรียน
        </a>
    </li>

    <li>
        <a href="scores.php">
            คะแนน
        </a>
    </li>

    <li>
        <a href="grades.php">
            ผลการเรียน
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
