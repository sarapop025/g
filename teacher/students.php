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


// ========================================
// หา teacher_id
// ========================================

$user_id = $_SESSION["user_id"];

$sql = "SELECT teacher_id
        FROM teachers
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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
// SQL
// ========================================

$sql = "SELECT DISTINCT

            students.student_id,
            students.student_code,
            students.firstname,
            students.lastname,

            classrooms.level,
            classrooms.room,
            classrooms.academic_year,
            classrooms.semester

        FROM teaching

        INNER JOIN students
        ON teaching.classroom_id = students.classroom_id

        INNER JOIN classrooms
        ON students.classroom_id = classrooms.classroom_id

        WHERE teaching.teacher_id = ?";


$params = [$teacher_id];
$types = "i";


if ($search != "") {

    $sql .= " AND (
                students.student_code LIKE ?
                OR students.firstname LIKE ?
                OR students.lastname LIKE ?
            )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= "sss";
}


$sql .= " ORDER BY
          classrooms.level,
          classrooms.room,
          students.student_code";


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

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>รายชื่อนักเรียน</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>
        รายชื่อนักเรียน
    </h1>


    <p>

        <a href="dashboard.php">
            ← Dashboard
        </a>

        |

        <a href="scores.php">
            บันทึกคะแนน
        </a>

        |

        <a href="grades.php">
            ผลการเรียน
        </a>

        |

        <a href="../logout.php">
            ออกจากระบบ
        </a>

    </p>


    <hr>


    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหารหัสหรือชื่อนักเรียน"
            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="students.php">
            ล้าง
        </a>

    </form>


    <br>


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

        </tr>


        <?php

        $no = 1;

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

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
                    $row["academic_year"]
                );

                ?>

            </td>

            <td>

                <?php

                echo $row["semester"];

                ?>

            </td>

        </tr>

        <?php

            }

        } else {

        ?>

        <tr>

            <td colspan="6">
                ไม่พบข้อมูลนักเรียน
            </td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>

</html>