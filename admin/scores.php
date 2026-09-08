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
// เพิ่มคะแนน
// ========================================

if (isset($_POST["add_score"])) {

    $student_id = intval($_POST["student_id"]);
    $teaching_id = intval($_POST["teaching_id"]);
    $score = floatval($_POST["score"]);

    // ตรวจสอบคะแนน
    if ($score < 0 || $score > 100) {

        $error = "คะแนนต้องอยู่ระหว่าง 0 - 100";

    } else {

        // ตรวจสอบว่ามีคะแนนแล้วหรือไม่
        $check = $conn->prepare(
            "SELECT score_id
             FROM scores
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

            $error = "นักเรียนคนนี้มีคะแนนวิชานี้แล้ว";

        } else {

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

            if ($stmt->execute()) {

                header(
                    "Location: scores.php?success=add"
                );

                exit();

            } else {

                $error = "เพิ่มคะแนนไม่สำเร็จ";
            }
        }
    }
}


// ========================================
// ลบคะแนน
// ========================================

if (isset($_GET["delete"])) {

    $score_id = intval($_GET["delete"]);

    $sql = "DELETE FROM scores
            WHERE score_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $score_id
    );

    if ($stmt->execute()) {

        header(
            "Location: scores.php?success=delete"
        );

        exit();

    } else {

        $error = "ลบคะแนนไม่สำเร็จ";
    }
}


// ========================================
// ดึงคะแนนสำหรับแก้ไข
// ========================================

$edit_score = null;

if (isset($_GET["edit"])) {

    $score_id = intval($_GET["edit"]);

    $sql = "SELECT *
            FROM scores
            WHERE score_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $score_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_score = $result->fetch_assoc();
    }
}


// ========================================
// แก้ไขคะแนน
// ========================================

if (isset($_POST["update_score"])) {

    $score_id = intval($_POST["score_id"]);
    $score = floatval($_POST["score"]);

    if ($score < 0 || $score > 100) {

        $error = "คะแนนต้องอยู่ระหว่าง 0 - 100";

    } else {

        $sql = "UPDATE scores
                SET score = ?
                WHERE score_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "di",
            $score,
            $score_id
        );

        if ($stmt->execute()) {

            header(
                "Location: scores.php?success=update"
            );

            exit();

        } else {

            $error = "แก้ไขคะแนนไม่สำเร็จ";
        }
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
// ดึงข้อมูลนักเรียน
// ========================================

$students = $conn->query(
    "SELECT
        student_id,
        student_code,
        firstname,
        lastname
     FROM students
     ORDER BY firstname, lastname"
);


// ========================================
// ดึงรายการการสอน
// ========================================

$teachings = $conn->query(
    "SELECT
        teaching.teaching_id,

        subjects.subject_code,
        subjects.subject_name,

        teachers.firstname AS teacher_firstname,
        teachers.lastname AS teacher_lastname,

        classrooms.level,
        classrooms.room,
        classrooms.academic_year,
        classrooms.semester

     FROM teaching

     INNER JOIN subjects
     ON teaching.subject_id = subjects.subject_id

     INNER JOIN teachers
     ON teaching.teacher_id = teachers.teacher_id

     INNER JOIN classrooms
     ON teaching.classroom_id = classrooms.classroom_id

     ORDER BY
        classrooms.level,
        classrooms.room,
        subjects.subject_code"
);


// ========================================
// ดึงคะแนน
// ========================================

if ($search != "") {

    $sql = "SELECT

                scores.*,

                students.student_code,
                students.firstname AS student_firstname,
                students.lastname AS student_lastname,

                subjects.subject_code,
                subjects.subject_name,

                teachers.firstname AS teacher_firstname,
                teachers.lastname AS teacher_lastname,

                classrooms.level,
                classrooms.room

            FROM scores

            INNER JOIN students
            ON scores.student_id = students.student_id

            INNER JOIN teaching
            ON scores.teaching_id = teaching.teaching_id

            INNER JOIN subjects
            ON teaching.subject_id = subjects.subject_id

            INNER JOIN teachers
            ON teaching.teacher_id = teachers.teacher_id

            INNER JOIN classrooms
            ON teaching.classroom_id = classrooms.classroom_id

            WHERE students.student_code LIKE ?
               OR students.firstname LIKE ?
               OR students.lastname LIKE ?
               OR subjects.subject_code LIKE ?
               OR subjects.subject_name LIKE ?

            ORDER BY scores.score_id DESC";

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

    $scores = $stmt->get_result();

} else {

    $sql = "SELECT

                scores.*,

                students.student_code,
                students.firstname AS student_firstname,
                students.lastname AS student_lastname,

                subjects.subject_code,
                subjects.subject_name,

                teachers.firstname AS teacher_firstname,
                teachers.lastname AS teacher_lastname,

                classrooms.level,
                classrooms.room

            FROM scores

            INNER JOIN students
            ON scores.student_id = students.student_id

            INNER JOIN teaching
            ON scores.teaching_id = teaching.teaching_id

            INNER JOIN subjects
            ON teaching.subject_id = subjects.subject_id

            INNER JOIN teachers
            ON teaching.teacher_id = teachers.teacher_id

            INNER JOIN classrooms
            ON teaching.classroom_id = classrooms.classroom_id

            ORDER BY scores.score_id DESC";

    $scores = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>จัดการคะแนน</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการคะแนนนักเรียน</h1>

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

            switch ($_GET["success"]) {

                case "add":
                    echo "เพิ่มคะแนนเรียบร้อยแล้ว";
                    break;

                case "update":
                    echo "แก้ไขคะแนนเรียบร้อยแล้ว";
                    break;

                case "delete":
                    echo "ลบคะแนนเรียบร้อยแล้ว";
                    break;
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
    <!-- ฟอร์มเพิ่ม / แก้ไข -->
    <!-- ================================= -->

    <h2>

        <?php

        echo $edit_score
            ? "แก้ไขคะแนน"
            : "เพิ่มคะแนน";

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_score) { ?>

            <input
                type="hidden"
                name="score_id"
                value="<?php
                    echo $edit_score["score_id"];
                ?>">

        <?php } ?>


        <?php if (!$edit_score) { ?>

            <!-- นักเรียน -->

            <div>

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

                    if ($students) {

                        while (
                            $student =
                            $students->fetch_assoc()
                        ) {

                    ?>

                        <option
                            value="<?php
                                echo $student["student_id"];
                            ?>">

                            <?php

                            echo htmlspecialchars(
                                $student["student_code"]
                                . " - "
                                . $student["firstname"]
                                . " "
                                . $student["lastname"]
                            );

                            ?>

                        </option>

                    <?php

                        }

                    }

                    ?>

                </select>

            </div>


            <!-- วิชาที่เปิดสอน -->

            <div>

                <label>
                    รายวิชา / ห้อง / ครู
                </label>

                <select
                    name="teaching_id"
                    required>

                    <option value="">
                        -- เลือกรายวิชา --
                    </option>

                    <?php

                    if ($teachings) {

                        while (
                            $teaching =
                            $teachings->fetch_assoc()
                        ) {

                    ?>

                        <option
                            value="<?php
                                echo $teaching["teaching_id"];
                            ?>">

                            <?php

                            echo htmlspecialchars(
                                $teaching["subject_code"]
                                . " - "
                                . $teaching["subject_name"]
                                . " | "
                                . $teaching["level"]
                                . "/"
                                . $teaching["room"]
                                . " | ครู "
                                . $teaching["teacher_firstname"]
                                . " "
                                . $teaching["teacher_lastname"]
                            );

                            ?>

                        </option>

                    <?php

                        }

                    }

                    ?>

                </select>

            </div>

        <?php } ?>


        <?php if ($edit_score) { ?>

            <p>
                <strong>คะแนนปัจจุบัน:</strong>
                <?php
                echo $edit_score["score"];
                ?>
            </p>

        <?php } ?>


        <!-- คะแนน -->

        <div>

            <label>
                คะแนน
            </label>

            <input
                type="number"
                name="score"
                required
                min="0"
                max="100"
                step="0.01"

                value="<?php

                echo $edit_score
                    ? htmlspecialchars(
                        $edit_score["score"]
                    )
                    : "";

                ?>">

        </div>


        <br>


        <?php if ($edit_score) { ?>

            <button
                type="submit"
                name="update_score">

                บันทึกการแก้ไข

            </button>

            <a href="scores.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_score">

                บันทึกคะแนน

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายการคะแนน</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหานักเรียน / รหัสวิชา / วิชา"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="scores.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางคะแนน -->
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

            <th>ห้อง</th>

            <th>ครูผู้สอน</th>

            <th>คะแนน</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if (
            $scores &&
            $scores->num_rows > 0
        ) {

            while (
                $row =
                $scores->fetch_assoc()
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
                        $row["level"]
                        . "/"
                        . $row["room"]
                    );

                    ?>

                </td>

                <td>

                    <?php

                    echo htmlspecialchars(
                        $row["teacher_firstname"]
                        . " "
                        . $row["teacher_lastname"]
                    );

                    ?>

                </td>

                <td>

                    <strong>
                        <?php
                        echo $row["score"];
                        ?>
                    </strong>

                    / 100

                </td>

                <td>

                    <a href="scores.php?edit=<?php
                        echo $row["score_id"];
                    ?>">

                        แก้ไข

                    </a>

                    |

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

        <?php

            }

        } else {

        ?>

            <tr>

                <td colspan="8">
                    ไม่พบข้อมูลคะแนน
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>