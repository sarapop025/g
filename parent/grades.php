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
// ตรวจสอบ Role
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
            lastname
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
// รับ student_id
// ========================================

$student_id = isset($_GET["student_id"])
    ? intval($_GET["student_id"])
    : 0;


// ========================================
// ตรวจสอบว่านักเรียนเป็นบุตรที่ผูกกับบัญชี
// ========================================

if ($student_id > 0) {

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
            AND parent_student.student_id = ?";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $parent_id,
        $student_id
    );

    $stmt->execute();

    $student =
        $stmt->get_result()->fetch_assoc();


    if (!$student) {

        die(
            "ไม่มีสิทธิ์ดูผลการเรียนของนักเรียนคนนี้"
        );

    }

} else {

    // ====================================
    // ถ้าไม่ได้เลือกนักเรียน
    // ให้ดึงคนแรกที่ผูกไว้
    // ====================================

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

            ORDER BY students.student_code

            LIMIT 1";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $parent_id
    );

    $stmt->execute();

    $student =
        $stmt->get_result()->fetch_assoc();


    if (!$student) {

        die(
            "ยังไม่มีนักเรียนที่ผูกกับบัญชีผู้ปกครอง"
        );

    }


    $student_id =
        $student["student_id"];
}


// ========================================
// ดึงรายชื่อนักเรียนทั้งหมด
// ========================================

$sql = "SELECT

            students.student_id,
            students.student_code,
            students.firstname,
            students.lastname

        FROM parent_student

        INNER JOIN students
        ON parent_student.student_id =
           students.student_id

        WHERE parent_student.parent_id = ?

        ORDER BY students.student_code";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $parent_id
);

$stmt->execute();

$children =
    $stmt->get_result();


// ========================================
// ดึงผลการเรียน
// ========================================

$sql = "SELECT

            grades.grade_id,

            grades.score,
            grades.grade,
            grades.status,

            subjects.subject_code,
            subjects.subject_name,
            subjects.credits,

            classrooms.level,
            classrooms.room,
            classrooms.academic_year,
            classrooms.semester

        FROM grades

        INNER JOIN teaching
        ON grades.teaching_id =
           teaching.teaching_id

        INNER JOIN subjects
        ON teaching.subject_id =
           subjects.subject_id

        INNER JOIN classrooms
        ON teaching.classroom_id =
           classrooms.classroom_id

        WHERE grades.student_id = ?

        ORDER BY

            classrooms.academic_year DESC,

            classrooms.semester DESC,

            subjects.subject_code";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $student_id
);

$stmt->execute();

$result =
    $stmt->get_result();


// ========================================
// เก็บข้อมูลและคำนวณ GPA
// ========================================

$data = [];

$total_credit = 0;
$total_point = 0;


while (
    $row =
    $result->fetch_assoc()
) {

    $data[] = $row;


    $credit =
        floatval($row["credits"]);


    $grade =
        floatval($row["grade"]);


    $total_credit +=
        $credit;


    $total_point +=
        ($credit * $grade);
}


if ($total_credit > 0) {

    $gpa =
        $total_point
        / $total_credit;

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
        ผลการเรียนของนักเรียน
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css">

    <style>

        .gpa-box {

            border: 1px solid #ccc;

            padding: 15px;

            margin-top: 20px;

            margin-bottom: 20px;

            font-size: 20px;

        }


        @media print {

            .no-print {
                display: none;
            }

        }

    </style>

</head>

<body>

<div class="container">


    <div class="no-print">

        <h1>
            ผลการเรียน
        </h1>


        <p>

            <a href="dashboard.php">
                ← Dashboard
            </a>

            |

            <a href="../logout.php">
                ออกจากระบบ
            </a>

        </p>

    </div>


    <hr>


    <!-- ================================= -->
    <!-- เลือกนักเรียน -->
    <!-- ================================= -->

    <div class="no-print">

        <h2>
            เลือกนักเรียน
        </h2>


        <form method="GET">

            <select
                name="student_id"
                onchange="this.form.submit()">

                <?php

                while (
                    $child =
                    $children->fetch_assoc()
                ) {

                ?>

                <option

                    value="<?php
                        echo $child["student_id"];
                    ?>"

                    <?php

                    if (
                        $child["student_id"]
                        == $student_id
                    ) {

                        echo "selected";

                    }

                    ?>

                >

                    <?php

                    echo htmlspecialchars(
                        $child["student_code"]
                        . " - "
                        . $child["firstname"]
                        . " "
                        . $child["lastname"]
                    );

                    ?>

                </option>

                <?php } ?>

            </select>

        </form>

    </div>


    <!-- ================================= -->
    <!-- ข้อมูลนักเรียน -->
    <!-- ================================= -->

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


    <p>

        ชั้น:

        <strong>

            <?php

            echo htmlspecialchars(
                ($student["level"] ?? "-")
                . "/"
                . ($student["room"] ?? "-")
            );

            ?>

        </strong>

    </p>


    <p>

        ปีการศึกษา:

        <strong>

            <?php

            echo htmlspecialchars(
                $student["academic_year"]
                ?? "-"
            );

            ?>

        </strong>

        |

        ภาคเรียน:

        <strong>

            <?php

            echo $student["semester"]
                ?? "-";

            ?>

        </strong>

    </p>


    <!-- ================================= -->
    <!-- GPA -->
    <!-- ================================= -->

    <div class="gpa-box">

        GPA:

        <strong>

            <?php

            echo number_format(
                $gpa,
                2
            );

            ?>

        </strong>


        <br>


        หน่วยกิตรวม:

        <strong>

            <?php

            echo number_format(
                $total_credit,
                1
            );

            ?>

        </strong>

    </div>


    <!-- ================================= -->
    <!-- พิมพ์ -->
    <!-- ================================= -->

    <div class="no-print">

        <button
            type="button"
            onclick="window.print();">

            🖨 พิมพ์ผลการเรียน

        </button>

    </div>


    <br>


    <!-- ================================= -->
    <!-- ตารางผลการเรียน -->
    <!-- ================================= -->

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
                ปีการศึกษา
            </th>

            <th>
                ภาคเรียน
            </th>

            <th>
                รหัสวิชา
            </th>

            <th>
                รายวิชา
            </th>

            <th>
                หน่วยกิต
            </th>

            <th>
                คะแนน
            </th>

            <th>
                เกรด
            </th>

            <th>
                สถานะ
            </th>

        </tr>


        <?php

        if (count($data) > 0) {

            $no = 1;

            foreach ($data as $row) {

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
                    $row["academic_year"]
                );

                ?>

            </td>


            <td>

                <?php

                echo $row["semester"];

                ?>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $row["subject_code"]
                );

                ?>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $row["subject_name"]
                );

                ?>

            </td>


            <td>

                <?php

                echo $row["credits"];

                ?>

            </td>


            <td>

                <?php

                echo $row["score"];

                ?>

            </td>


            <td>

                <strong>

                    <?php

                    echo number_format(
                        $row["grade"],
                        1
                    );

                    ?>

                </strong>

            </td>


            <td>

                <?php

                echo htmlspecialchars(
                    $row["status"]
                );

                ?>

            </td>

        </tr>


        <?php

            }

        } else {

        ?>


        <tr>

            <td colspan="9">

                ยังไม่มีข้อมูลผลการเรียน

            </td>

        </tr>


        <?php } ?>

    </table>


    <br>


    <!-- ================================= -->
    <!-- เกณฑ์เกรด -->
    <!-- ================================= -->

    <div class="no-print">

        <h3>
            เกณฑ์การให้เกรด
        </h3>


        <table
            border="1"
            cellpadding="6">

            <tr>

                <th>
                    คะแนน
                </th>

                <th>
                    เกรด
                </th>

            </tr>

            <tr>
                <td>80 - 100</td>
                <td>4.0</td>
            </tr>

            <tr>
                <td>75 - 79</td>
                <td>3.5</td>
            </tr>

            <tr>
                <td>70 - 74</td>
                <td>3.0</td>
            </tr>

            <tr>
                <td>65 - 69</td>
                <td>2.5</td>
            </tr>

            <tr>
                <td>60 - 64</td>
                <td>2.0</td>
            </tr>

            <tr>
                <td>55 - 59</td>
                <td>1.5</td>
            </tr>

            <tr>
                <td>50 - 54</td>
                <td>1.0</td>
            </tr>

            <tr>
                <td>0 - 49</td>
                <td>0.0</td>
            </tr>

        </table>

    </div>


</div>

</body>

</html>