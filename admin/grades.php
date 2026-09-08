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
// ตรวจสอบสิทธิ์ Admin
// ========================================

if ($_SESSION["role"] != "admin") {
    header("Location: ../dashboard.php");
    exit();
}


// ========================================
// ฟังก์ชันคำนวณเกรด
// ========================================

function calculateGrade($score)
{
    if ($score >= 80) {
        return 4.0;
    } elseif ($score >= 75) {
        return 3.5;
    } elseif ($score >= 70) {
        return 3.0;
    } elseif ($score >= 65) {
        return 2.5;
    } elseif ($score >= 60) {
        return 2.0;
    } elseif ($score >= 55) {
        return 1.5;
    } elseif ($score >= 50) {
        return 1.0;
    } else {
        return 0.0;
    }
}


// ========================================
// ฟังก์ชันสถานะ
// ========================================

function getGradeStatus($score)
{
    if ($score < 50) {
        return "ไม่ผ่าน";
    }

    return "ผ่าน";
}


// ========================================
// บันทึกเกรดทั้งหมด
// ========================================

if (isset($_POST["save_grades"])) {

    $conn->begin_transaction();

    try {

        // ดึงคะแนนทั้งหมด
        $sql = "SELECT
                    scores.score_id,
                    scores.student_id,
                    scores.teaching_id,
                    scores.score

                FROM scores";

        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {

            $student_id  = $row["student_id"];
            $teaching_id = $row["teaching_id"];
            $score       = $row["score"];

            // คำนวณเกรด
            $grade = calculateGrade($score);

            // สถานะ
            $status = getGradeStatus($score);


            // ========================================
            // ตรวจสอบว่ามีเกรดอยู่แล้วหรือไม่
            // ========================================

            $check = $conn->prepare(
                "SELECT grade_id
                 FROM grades
                 WHERE student_id = ?
                 AND teaching_id = ?"
            );

            $check->bind_param(
                "ii",
                $student_id,
                $teaching_id
            );

            $check->execute();

            $check_result = $check->get_result();


            if ($check_result->num_rows > 0) {

                // ====================================
                // UPDATE
                // ====================================

                $existing = $check_result->fetch_assoc();

                $grade_id = $existing["grade_id"];

                $sql_update = "UPDATE grades
                               SET score = ?,
                                   grade = ?,
                                   status = ?
                               WHERE grade_id = ?";

                $stmt_update =
                    $conn->prepare($sql_update);

                $stmt_update->bind_param(
                    "ddsi",
                    $score,
                    $grade,
                    $status,
                    $grade_id
                );

                $stmt_update->execute();

            } else {

                // ====================================
                // INSERT
                // ====================================

                $sql_insert = "INSERT INTO grades
                               (
                                   student_id,
                                   teaching_id,
                                   score,
                                   grade,
                                   status
                               )

                               VALUES (?, ?, ?, ?, ?)";

                $stmt_insert =
                    $conn->prepare($sql_insert);

                $stmt_insert->bind_param(
                    "iidds",
                    $student_id,
                    $teaching_id,
                    $score,
                    $grade,
                    $status
                );

                $stmt_insert->execute();
            }
        }

        $conn->commit();

        header(
            "Location: grades.php?success=save"
        );

        exit();

    } catch (Exception $e) {

        $conn->rollback();

        $error = "บันทึกเกรดไม่สำเร็จ: "
               . $e->getMessage();
    }
}


// ========================================
// ค้นหา
// ========================================

$search = "";

if (isset($_GET["search"])) {

    $search = trim($_GET["search"]);
}


// ========================================
// ดึงผลการเรียน
// ========================================

if ($search != "") {

    $sql = "SELECT

                grades.*,

                students.student_code,
                students.firstname AS student_firstname,
                students.lastname AS student_lastname,

                subjects.subject_code,
                subjects.subject_name,
                subjects.credits,

                classrooms.level,
                classrooms.room,

                teachers.firstname AS teacher_firstname,
                teachers.lastname AS teacher_lastname

            FROM grades

            INNER JOIN students
            ON grades.student_id = students.student_id

            INNER JOIN teaching
            ON grades.teaching_id = teaching.teaching_id

            INNER JOIN subjects
            ON teaching.subject_id = subjects.subject_id

            INNER JOIN classrooms
            ON teaching.classroom_id = classrooms.classroom_id

            INNER JOIN teachers
            ON teaching.teacher_id = teachers.teacher_id

            WHERE students.student_code LIKE ?
               OR students.firstname LIKE ?
               OR students.lastname LIKE ?
               OR subjects.subject_code LIKE ?
               OR subjects.subject_name LIKE ?

            ORDER BY
                students.student_code,
                subjects.subject_code";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->bind_param(
        "sssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $grades = $stmt->get_result();

} else {

    $sql = "SELECT

                grades.*,

                students.student_code,
                students.firstname AS student_firstname,
                students.lastname AS student_lastname,

                subjects.subject_code,
                subjects.subject_name,
                subjects.credits,

                classrooms.level,
                classrooms.room,

                teachers.firstname AS teacher_firstname,
                teachers.lastname AS teacher_lastname

            FROM grades

            INNER JOIN students
            ON grades.student_id = students.student_id

            INNER JOIN teaching
            ON grades.teaching_id = teaching.teaching_id

            INNER JOIN subjects
            ON teaching.subject_id = subjects.subject_id

            INNER JOIN classrooms
            ON teaching.classroom_id = classrooms.classroom_id

            INNER JOIN teachers
            ON teaching.teacher_id = teachers.teacher_id

            ORDER BY
                students.student_code,
                subjects.subject_code";

    $grades = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>ผลการเรียน</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>ผลการเรียน / ตัดเกรด</h1>

    <p>
        ผู้ใช้งาน:
        <?php
        echo htmlspecialchars(
            $_SESSION["fullname"]
        );
        ?>
    </p>


    <!-- ================================= -->
    <!-- เมนู -->
    <!-- ================================= -->

    <p>

        <a href="dashboard.php">
            ← กลับ Dashboard
        </a>

        |

        <a href="scores.php">
            จัดการคะแนน
        </a>

        |

        <a href="../logout.php">
            ออกจากระบบ
        </a>

    </p>


    <!-- ================================= -->
    <!-- แจ้งเตือน -->
    <!-- ================================= -->

    <?php if (isset($_GET["success"])) { ?>

        <div class="success">

            <?php

            if (
                $_GET["success"] == "save"
            ) {

                echo "คำนวณและบันทึกเกรดเรียบร้อยแล้ว";
            }

            ?>

        </div>

    <?php } ?>


    <?php if (isset($error)) { ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php } ?>


    <!-- ================================= -->
    <!-- คำอธิบายเกณฑ์ -->
    <!-- ================================= -->

    <h2>เกณฑ์การตัดเกรด</h2>

    <table
        border="1"
        cellpadding="8"
        cellspacing="0">

        <tr>
            <th>คะแนน</th>
            <th>เกรด</th>
        </tr>

        <tr>
            <td>80 - 100</td>
            <td>4</td>
        </tr>

        <tr>
            <td>75 - 79</td>
            <td>3.5</td>
        </tr>

        <tr>
            <td>70 - 74</td>
            <td>3</td>
        </tr>

        <tr>
            <td>65 - 69</td>
            <td>2.5</td>
        </tr>

        <tr>
            <td>60 - 64</td>
            <td>2</td>
        </tr>

        <tr>
            <td>55 - 59</td>
            <td>1.5</td>
        </tr>

        <tr>
            <td>50 - 54</td>
            <td>1</td>
        </tr>

        <tr>
            <td>0 - 49</td>
            <td>0</td>
        </tr>

    </table>


    <br>


    <!-- ================================= -->
    <!-- ปุ่มคำนวณ -->
    <!-- ================================= -->

    <form method="POST">

        <button
            type="submit"
            name="save_grades"

            onclick="return confirm(
                'ต้องการคำนวณและบันทึกเกรดทั้งหมดหรือไม่?'
            );">

            คำนวณและบันทึกเกรดทั้งหมด

        </button>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>ผลการเรียน</h2>

    <form method="GET">

        <input
            type="text"
            name="search"

            placeholder="ค้นหารหัสนักเรียน / ชื่อ / วิชา"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="grades.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางผลการเรียน -->
    <!-- ================================= -->

    <table
        border="1"
        cellpadding="10"
        cellspacing="0"
        width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>รหัสนักเรียน</th>

            <th>นักเรียน</th>

            <th>วิชา</th>

            <th>หน่วยกิต</th>

            <th>ห้อง</th>

            <th>คะแนน</th>

            <th>เกรด</th>

            <th>สถานะ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if (
            $grades &&
            $grades->num_rows > 0
        ) {

            while (
                $row =
                $grades->fetch_assoc()
            ) {

        ?>

            <tr>

                <td>
                    <?php echo $i++; ?>
                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["student_code"]
                    );
                    ?>

                </td>


                <td>

                    <?php

                    echo htmlspecialchars(
                        $row["student_firstname"]
                        . " "
                        . $row["student_lastname"]
                    );

                    ?>

                </td>


                <td>

                    <?php

                    echo htmlspecialchars(
                        $row["subject_code"]
                        . " - "
                        . $row["subject_name"]
                    );

                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["credits"]
                    );
                    ?>

                </td>


                <td>

                    <?php

                    echo htmlspecialchars(
                        $row["level"]
                        . "/"
                        . $row["room"]
                    );

                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["score"]
                    );
                    ?>

                    / 100

                </td>


                <td>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $row["grade"]
                        );
                        ?>

                    </strong>

                </td>


                <td>

                    <?php

                    if (
                        $row["status"]
                        == "ผ่าน"
                    ) {

                        echo "ผ่าน";

                    } else {

                        echo "ไม่ผ่าน";

                    }

                    ?>

                </td>

            </tr>

        <?php

            }

        } else {

        ?>

            <tr>

                <td colspan="9">

                    ยังไม่มีผลการเรียน

                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>