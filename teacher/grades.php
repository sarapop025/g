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

if ($_SESSION["role"] != "teacher") {
    header("Location: ../dashboard.php");
    exit();
}


// ========================================
// หา teacher_id
// ========================================

$user_id = $_SESSION["user_id"];

$sql = "SELECT teacher_id
        FROM teachers
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$teacher = $stmt->get_result()->fetch_assoc();


if (!$teacher) {
    die("ไม่พบข้อมูลครู");
}


$teacher_id = $teacher["teacher_id"];


// ========================================
// ค้นหา
// ========================================

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


// ========================================
// ดึงคะแนนและคำนวณเกรด
// ========================================

$sql = "SELECT

            scores.score_id,

            students.student_code,
            students.firstname,
            students.lastname,

            subjects.subject_code,
            subjects.subject_name,
            subjects.credits,

            classrooms.level,
            classrooms.room,

            scores.score

        FROM scores

        INNER JOIN students
        ON scores.student_id =
           students.student_id

        INNER JOIN teaching
        ON scores.teaching_id =
           teaching.teaching_id

        INNER JOIN subjects
        ON teaching.subject_id =
           subjects.subject_id

        INNER JOIN classrooms
        ON teaching.classroom_id =
           classrooms.classroom_id

        WHERE teaching.teacher_id = ?";


$params = [$teacher_id];
$types = "i";


if ($search != "") {

    $sql .= " AND (
                students.student_code LIKE ?
                OR students.firstname LIKE ?
                OR students.lastname LIKE ?
                OR subjects.subject_code LIKE ?
                OR subjects.subject_name LIKE ?
            )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= "sssss";
}


$sql .= " ORDER BY
          classrooms.level,
          classrooms.room,
          students.student_code,
          subjects.subject_code";


$stmt = $conn->prepare($sql);


$bind = [];
$bind[] = $types;


for ($i = 0; $i < count($params); $i++) {

    $bind[] = &$params[$i];

}


call_user_func_array(
    [$stmt, "bind_param"],
    $bind
);


$stmt->execute();

$result = $stmt->get_result();


// ========================================
// ฟังก์ชันคำนวณเกรด
// ========================================

function calculateGrade($score)
{
    if ($score >= 80) {
        return 4.0;
    }

    if ($score >= 75) {
        return 3.5;
    }

    if ($score >= 70) {
        return 3.0;
    }

    if ($score >= 65) {
        return 2.5;
    }

    if ($score >= 60) {
        return 2.0;
    }

    if ($score >= 55) {
        return 1.5;
    }

    if ($score >= 50) {
        return 1.0;
    }

    return 0.0;
}


// ========================================
// สถานะ
// ========================================

function getStatus($score)
{
    if ($score >= 50) {
        return "ผ่าน";
    }

    return "ไม่ผ่าน";
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


    <h1>
        ผลการเรียน
    </h1>


    <p>

        <a href="dashboard.php">
            ← Dashboard
        </a>

        |

        <a href="students.php">
            นักเรียน
        </a>

        |

        <a href="scores.php">
            บันทึกคะแนน
        </a>

        |

        <a href="../logout.php">
            ออกจากระบบ
        </a>

    </p>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="รหัสนักเรียน / ชื่อ / วิชา"
            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="grades.php">
            ล้าง
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตาราง -->
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
                รหัสนักเรียน
            </th>

            <th>
                นักเรียน
            </th>

            <th>
                ชั้น
            </th>

            <th>
                วิชา
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

        $no = 1;

        if ($result->num_rows > 0) {

            while (
                $row =
                $result->fetch_assoc()
            ) {

                $grade =
                    calculateGrade(
                        floatval($row["score"])
                    );

                $status =
                    getStatus(
                        floatval($row["score"])
                    );

        ?>

        <tr>

            <td>
                <?php echo $no++; ?>
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
                    $row["firstname"]
                    . " "
                    . $row["lastname"]
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
                    $row["subject_code"]
                    . " - "
                    . $row["subject_name"]
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
                        $grade,
                        1
                    );

                    ?>

                </strong>

            </td>

            <td>

                <?php

                echo $status;

                ?>

            </td>

        </tr>

        <?php

            }

        } else {

        ?>

        <tr>

            <td colspan="9">

                ไม่พบข้อมูลผลการเรียน

            </td>

        </tr>

        <?php } ?>

    </table>


    <br>


    <h3>
        เกณฑ์การให้เกรด
    </h3>

    <table
        border="1"
        cellpadding="6">

        <tr>
            <th>คะแนน</th>
            <th>เกรด</th>
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

</body>

</html>