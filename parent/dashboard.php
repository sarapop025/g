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
// ตรวจสอบสิทธิ์ Parent
// ========================================

if ($_SESSION["role"] != "parent") {
    header("Location: ../dashboard.php");
    exit();
}


// ========================================
// หา parent_id
// ========================================

$user_id = $_SESSION["user_id"];

$sql = "SELECT
            parent_id,
            firstname,
            lastname,
            phone
        FROM parents
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$parent =
    $stmt->get_result()->fetch_assoc();


if (!$parent) {
    die("ไม่พบข้อมูลผู้ปกครอง");
}


$parent_id =
    $parent["parent_id"];


// ========================================
// ดึงรายชื่อนักเรียนที่ผูกกับผู้ปกครอง
// ========================================

$sql = "SELECT

            students.student_id,
            students.student_code,
            students.firstname,
            students.lastname,

            classrooms.level,
            classrooms.room,
            classrooms.academic_year,
            classrooms.semester

        FROM parent_student

        INNER JOIN students
        ON parent_student.student_id =
           students.student_id

        LEFT JOIN classrooms
        ON students.classroom_id =
           classrooms.classroom_id

        WHERE parent_student.parent_id = ?

        ORDER BY students.student_code";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $parent_id
);

$stmt->execute();

$students =
    $stmt->get_result();


// ========================================
// นับจำนวนบุตร/นักเรียน
// ========================================

$total_students =
    $students->num_rows;

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard ผู้ปกครอง
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>
        Dashboard ผู้ปกครอง
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


    <!-- ================================= -->
    <!-- ข้อมูลผู้ปกครอง -->
    <!-- ================================= -->

    <h2>
        ข้อมูลผู้ปกครอง
    </h2>


    <p>

        ชื่อ-นามสกุล:

        <strong>

            <?php

            echo htmlspecialchars(
                $parent["firstname"]
                . " "
                . $parent["lastname"]
            );

            ?>

        </strong>

    </p>


    <p>

        เบอร์โทรศัพท์:

        <strong>

            <?php

            echo htmlspecialchars(
                $parent["phone"]
            );

            ?>

        </strong>

    </p>


    <p>

        จำนวนบุตร/นักเรียนที่ดูแล:

        <strong>

            <?php

            echo $total_students;

            ?>

        </strong>

        คน

    </p>


    <hr>


    <!-- ================================= -->
    <!-- รายชื่อนักเรียน -->
    <!-- ================================= -->

    <h2>
        นักเรียนที่ดูแล
    </h2>


    <table
        border="1"
        cellpadding="8"
        cellspacing="0"
        width="100%">

        <tr>

            <th>
                ลำดับ
            </th>

            <th>
                รหัสนักเรียน
            </th>

            <th>
                ชื่อ-นามสกุล
            </th>

            <th>
                ชั้น
            </th>

            <th>
                ปีการศึกษา
            </th>

            <th>
                ภาคเรียน
            </th>

            <th>
                ผลการเรียน
            </th>

        </tr>


        <?php

        $no = 1;

        if ($students->num_rows > 0) {

            while (
                $student =
                $students->fetch_assoc()
            ) {

        ?>

        <tr>

            <td>

                <?php

                echo $no++;

                ?>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $student["student_code"]
                );

                ?>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $student["firstname"]
                    . " "
                    . $student["lastname"]
                );

                ?>

            </td>


            <td>

                <?php

                if (
                    $student["level"] != null
                ) {

                    echo htmlspecialchars(
                        $student["level"]
                        . "/"
                        . $student["room"]
                    );

                } else {

                    echo "-";

                }

                ?>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $student["academic_year"]
                    ?? "-"
                );

                ?>

            </td>


            <td>

                <?php

                echo $student["semester"]
                    ?? "-";

                ?>

            </td>


            <td>

                <a href="grades.php?student_id=<?php
                    echo $student["student_id"];
                ?>">

                    ดูผลการเรียน

                </a>

            </td>

        </tr>


        <?php

            }

        } else {

        ?>


        <tr>

            <td colspan="7">

                ยังไม่มีข้อมูลนักเรียนที่ผูกกับบัญชีนี้

            </td>

        </tr>


        <?php } ?>

    </table>


    <hr>


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
