<?php

require_once "../config.php";

// ========================================
// ตรวจสอบ Login
// ========================================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// ========================================
// ตรวจสอบสิทธิ์ Student
// ========================================

if ($_SESSION["role"] != "student") {
    header("Location: ../dashboard.php");
    exit();
}


// ========================================
// หา student_id
// ========================================

$user_id = $_SESSION["user_id"];

$sql = "SELECT
            student_id,
            student_code,
            firstname,
            lastname,
            classroom_id
        FROM students
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$student = $result->fetch_assoc();


// ========================================
// ตรวจสอบข้อมูลนักเรียน
// ========================================

if (!$student) {
    die("ไม่พบข้อมูลนักเรียน");
}


$student_id = $student["student_id"];


// ========================================
// ดึงข้อมูลห้องเรียน
// ========================================

$sql = "SELECT *
        FROM classrooms
        WHERE classroom_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $student["classroom_id"]
);

$stmt->execute();

$classroom = $stmt->get_result()->fetch_assoc();


// ========================================
// จำนวนรายวิชา
// ========================================

$sql = "SELECT COUNT(*) AS total
        FROM grades
        WHERE student_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$total_subjects =
    $stmt->get_result()->fetch_assoc()["total"];


// ========================================
// GPA
// ========================================

$sql = "SELECT

            SUM(
                subjects.credits * grades.grade
            ) AS total_point,

            SUM(
                subjects.credits
            ) AS total_credit

        FROM grades

        INNER JOIN teaching
        ON grades.teaching_id =
           teaching.teaching_id

        INNER JOIN subjects
        ON teaching.subject_id =
           subjects.subject_id

        WHERE grades.student_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$gpa_data =
    $stmt->get_result()->fetch_assoc();


$total_credit =
    floatval($gpa_data["total_credit"]);

$total_point =
    floatval($gpa_data["total_point"]);


if ($total_credit > 0) {

    $gpa =
        $total_point / $total_credit;

} else {

    $gpa = 0;

}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard นักเรียน
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>
        Dashboard นักเรียน
    </h1>


    <p>

        ยินดีต้อนรับ

        <strong>

            <?php

            echo htmlspecialchars(
                $_SESSION["fullname"]
            );

            ?>

        </strong>

    </p>


    <hr>


    <h2>
        ข้อมูลนักเรียน
    </h2>


    <p>

        รหัสนักเรียน:

        <strong>

            <?php

            echo htmlspecialchars(
                $student["student_code"]
            );

            ?>

        </strong>

    </p>


    <p>

        ชื่อ-นามสกุล:

        <strong>

            <?php

            echo htmlspecialchars(
                $student["firstname"]
                . " "
                . $student["lastname"]
            );

            ?>

        </strong>

    </p>


    <?php if ($classroom) { ?>

    <p>

        ชั้น:

        <strong>

            <?php

            echo htmlspecialchars(
                $classroom["level"]
                . "/"
                . $classroom["room"]
            );

            ?>

        </strong>

    </p>


    <p>

        ปีการศึกษา:

        <strong>

            <?php

            echo htmlspecialchars(
                $classroom["academic_year"]
            );

            ?>

        </strong>

    </p>


    <p>

        ภาคเรียน:

        <strong>

            <?php

            echo $classroom["semester"];

            ?>

        </strong>

    </p>

    <?php } ?>


    <hr>


    <h2>
        สรุปผลการเรียน
    </h2>


    <p>

        จำนวนรายวิชา:

        <strong>

            <?php

            echo $total_subjects;

            ?>

        </strong>

        วิชา

    </p>


    <p>

        หน่วยกิตรวม:

        <strong>

            <?php

            echo number_format(
                $total_credit,
                1
            );

            ?>

        </strong>

    </p>


    <p>

        GPA:

        <strong>

            <?php

            echo number_format(
                $gpa,
                2
            );

            ?>

        </strong>

    </p>


    <hr>


    <h2>
        เมนู
    </h2>


    <p>

        <a href="grades.php">
            📊 ดูผลการเรียน
        </a>

    </p>


    <p>

        <a href="../logout.php">
            🚪 ออกจากระบบ
        </a>

    </p>

</div>

</body>

</html>
