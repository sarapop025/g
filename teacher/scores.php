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
$stmt->bind_param("i", $user_id);
$stmt->execute();

$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    die("ไม่พบข้อมูลครู");
}

$teacher_id = $teacher["teacher_id"];


// ========================================
// เพิ่มคะแนน
// ========================================

if (isset($_POST["add_score"])) {

    $student_id = intval($_POST["student_id"]);
    $teaching_id = intval($_POST["teaching_id"]);
    $score = floatval($_POST["score"]);


    // ตรวจสอบคะแนน
    if ($score < 0 || $score > 100) {

        die("คะแนนต้องอยู่ระหว่าง 0 - 100");

    }


    // ตรวจสอบว่า teaching เป็นของครูจริง
    $sql = "SELECT teaching_id
            FROM teaching
            WHERE teaching_id = ?
            AND teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $teaching_id,
        $teacher_id
    );

    $stmt->execute();

    $check = $stmt->get_result();


    if ($check->num_rows == 0) {

        die("คุณไม่มีสิทธิ์บันทึกคะแนนวิชานี้");

    }


    // ตรวจสอบว่านักเรียนอยู่ในห้องเดียวกับวิชา
    $sql = "SELECT students.student_id
            FROM students

            INNER JOIN teaching
            ON students.classroom_id =
               teaching.classroom_id

            WHERE students.student_id = ?
            AND teaching.teaching_id = ?
            AND teaching.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iii",
        $student_id,
        $teaching_id,
        $teacher_id
    );

    $stmt->execute();

    $check = $stmt->get_result();


    if ($check->num_rows == 0) {

        die("นักเรียนคนนี้ไม่ได้อยู่ในห้องของวิชานี้");

    }


    // ตรวจสอบคะแนนซ้ำ
    $sql = "SELECT score_id
            FROM scores
            WHERE student_id = ?
            AND teaching_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $student_id,
        $teaching_id
    );

    $stmt->execute();

    $check = $stmt->get_result();


    if ($check->num_rows > 0) {

        die("นักเรียนคนนี้มีคะแนนแล้ว");

    }


    // บันทึกคะแนน
    $sql = "INSERT INTO scores
            (student_id, teaching_id, score)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iid",
        $student_id,
        $teaching_id,
        $score
    );

    $stmt->execute();


    header("Location: scores.php");
    exit();
}


// ========================================
// แก้ไขคะแนน
// ========================================

if (isset($_POST["update_score"])) {

    $score_id = intval($_POST["score_id"]);
    $score = floatval($_POST["score"]);


    if ($score < 0 || $score > 100) {
        die("คะแนนต้องอยู่ระหว่าง 0 - 100");
    }


    // ตรวจสอบว่า score เป็นของครู
    $sql = "SELECT scores.score_id

            FROM scores

            INNER JOIN teaching
            ON scores.teaching_id =
               teaching.teaching_id

            WHERE scores.score_id = ?
            AND teaching.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $score_id,
        $teacher_id
    );

    $stmt->execute();

    $check = $stmt->get_result();


    if ($check->num_rows == 0) {
        die("ไม่มีสิทธิ์แก้ไขคะแนนนี้");
    }


    // Update
    $sql = "UPDATE scores
            SET score = ?
            WHERE score_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "di",
        $score,
        $score_id
    );

    $stmt->execute();


    header("Location: scores.php");
    exit();
}


// ========================================
// ลบคะแนน
// ========================================

if (isset($_GET["delete"])) {

    $score_id = intval($_GET["delete"]);


    // ตรวจสอบสิทธิ์ก่อนลบ
    $sql = "SELECT scores.score_id

            FROM scores

            INNER JOIN teaching
            ON scores.teaching_id =
               teaching.teaching_id

            WHERE scores.score_id = ?
            AND teaching.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $score_id,
        $teacher_id
    );

    $stmt->execute();

    $check = $stmt->get_result();


    if ($check->num_rows == 0) {
        die("ไม่มีสิทธิ์ลบคะแนนนี้");
    }


    $sql = "DELETE FROM scores
            WHERE score_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $score_id
    );

    $stmt->execute();


    header("Location: scores.php");
    exit();
}


// ========================================
// ดึงวิชาที่ครูสอน
// ========================================

$sql = "SELECT

            teaching.teaching_id,

            subjects.subject_code,
            subjects.subject_name,

            classrooms.level,
            classrooms.room

        FROM teaching

        INNER JOIN subjects
        ON teaching.subject_id =
           subjects.subject_id

        INNER JOIN classrooms
        ON teaching.classroom_id =
           classrooms.classroom_id

        WHERE teaching.teacher_id = ?

        ORDER BY
            classrooms.level,
            classrooms.room,
            subjects.subject_code";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $teacher_id
);

$stmt->execute();

$teachings = $stmt->get_result();


// ========================================
// ดึงนักเรียน
// ========================================

$sql = "SELECT

            students.student_id,
            students.student_code,
            students.firstname,
            students.lastname,

            classrooms.level,
            classrooms.room

        FROM students

        INNER JOIN classrooms
        ON students.classroom_id =
           classrooms.classroom_id

        ORDER BY
            classrooms.level,
            classrooms.room,
            students.student_code";


$students = $conn->query($sql);


// ========================================
// ดึงคะแนนของครู
// ========================================

$sql = "SELECT

            scores.score_id,

            students.student_code,
            students.firstname,
            students.lastname,

            subjects.subject_code,
            subjects.subject_name,

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

        WHERE teaching.teacher_id = ?

        ORDER BY
            classrooms.level,
            classrooms.room,
            students.student_code,
            subjects.subject_code";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $teacher_id
);

$stmt->execute();

$scores = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>บันทึกคะแนน</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>
        บันทึกคะแนน
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

        <a href="grades.php">
            ผลการเรียน
        </a>

        |

        <a href="../logout.php">
            ออกจากระบบ
        </a>

    </p>


    <hr>


    <!-- ================================= -->
    <!-- เพิ่มคะแนน -->
    <!-- ================================= -->

    <h2>
        เพิ่มคะแนน
    </h2>

    <form method="POST">


        <label>
            วิชา
        </label>

        <select
            name="teaching_id"
            required>

            <option value="">
                -- เลือกวิชา --
            </option>

            <?php

            while (
                $row =
                $teachings->fetch_assoc()
            ) {

            ?>

            <option
                value="<?php
                    echo $row["teaching_id"];
                ?>">

                <?php

                echo htmlspecialchars(
                    $row["subject_code"]
                    . " - "
                    . $row["subject_name"]
                    . " ("
                    . $row["level"]
                    . "/"
                    . $row["room"]
                    . ")"
                );

                ?>

            </option>

            <?php } ?>

        </select>


        <br><br>


        <label>
            นักเรียน
        </label>

        <select
            name="student_id"
            required>

            <option value="">
                -- เลือกนักเรียน --
            </option>

            <?php

            while (
                $row =
                $students->fetch_assoc()
            ) {

            ?>

            <option
                value="<?php
                    echo $row["student_id"];
                ?>">

                <?php

                echo htmlspecialchars(
                    $row["student_code"]
                    . " - "
                    . $row["firstname"]
                    . " "
                    . $row["lastname"]
                    . " ("
                    . $row["level"]
                    . "/"
                    . $row["room"]
                    . ")"
                );

                ?>

            </option>

            <?php } ?>

        </select>


        <br><br>


        <label>
            คะแนน
        </label>

        <input
            type="number"
            name="score"
            min="0"
            max="100"
            step="0.01"
            required>


        <br><br>


        <button
            type="submit"
            name="add_score">

            บันทึกคะแนน

        </button>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- รายการคะแนน -->
    <!-- ================================= -->

    <h2>
        รายการคะแนน
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
                นักเรียน
            </th>

            <th>
                ชั้น
            </th>

            <th>
                วิชา
            </th>

            <th>
                คะแนน
            </th>

            <th>
                จัดการ
            </th>

        </tr>


        <?php

        $no = 1;

        while (
            $row =
            $scores->fetch_assoc()
        ) {

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

                echo $row["score"];

                ?>

            </td>

            <td>

                <!-- แก้ไข -->

                <form
                    method="POST"
                    style="display:inline;">

                    <input
                        type="hidden"
                        name="score_id"
                        value="<?php
                            echo $row["score_id"];
                        ?>">

                    <input
                        type="number"
                        name="score"
                        min="0"
                        max="100"
                        step="0.01"
                        value="<?php
                            echo $row["score"];
                        ?>"
                        required>

                    <button
                        type="submit"
                        name="update_score">

                        แก้ไข

                    </button>

                </form>


                <!-- ลบ -->

                <a
                    href="scores.php?delete=<?php
                        echo $row["score_id"];
                    ?>"
                    onclick="return confirm(
                        'ต้องการลบคะแนนนี้หรือไม่?'
                    );">

                    ลบ

                </a>

            </td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>

</html>