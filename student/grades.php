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
            lastname
        FROM students
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$student =
    $stmt->get_result()->fetch_assoc();


if (!$student) {
    die("ไม่พบข้อมูลนักเรียน");
}


$student_id =
    $student["student_id"];


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
// คำนวณ GPA
// ========================================

$total_credit = 0;
$total_point = 0;

$data = [];

while (
    $row =
    $result->fetch_assoc()
) {

    $data[] = $row;

    $credit =
        floatval($row["credits"]);

    $grade =
        floatval($row["grade"]);

    $total_credit += $credit;

    $total_point +=
        $credit * $grade;
}


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
        ผลการเรียน
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
    <!-- ปุ่มพิมพ์ -->
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