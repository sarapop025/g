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
// เพิ่มนักเรียน
// ========================================

if (isset($_POST["add_student"])) {

    $student_code = trim($_POST["student_code"]);
    $firstname    = trim($_POST["firstname"]);
    $lastname     = trim($_POST["lastname"]);
    $classroom_id = $_POST["classroom_id"];
    $birthdate    = $_POST["birthdate"];

    $sql = "INSERT INTO students
            (student_code, firstname, lastname, classroom_id, birthdate)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssis",
        $student_code,
        $firstname,
        $lastname,
        $classroom_id,
        $birthdate
    );

    if ($stmt->execute()) {

        header("Location: students.php?success=add");
        exit();

    } else {

        $error = "เพิ่มข้อมูลไม่สำเร็จ: " . $conn->error;

    }
}


// ========================================
// ลบนักเรียน
// ========================================

if (isset($_GET["delete"])) {

    $student_id = intval($_GET["delete"]);

    $sql = "DELETE FROM students WHERE student_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {

        header("Location: students.php?success=delete");
        exit();

    } else {

        $error = "ลบข้อมูลไม่สำเร็จ";

    }
}


// ========================================
// ดึงข้อมูลสำหรับแก้ไข
// ========================================

$edit_student = null;

if (isset($_GET["edit"])) {

    $student_id = intval($_GET["edit"]);

    $sql = "SELECT *
            FROM students
            WHERE student_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_student = $result->fetch_assoc();

    }
}


// ========================================
// บันทึกการแก้ไข
// ========================================

if (isset($_POST["update_student"])) {

    $student_id   = intval($_POST["student_id"]);
    $student_code = trim($_POST["student_code"]);
    $firstname     = trim($_POST["firstname"]);
    $lastname      = trim($_POST["lastname"]);
    $classroom_id = $_POST["classroom_id"];
    $birthdate     = $_POST["birthdate"];

    $sql = "UPDATE students SET
                student_code = ?,
                firstname = ?,
                lastname = ?,
                classroom_id = ?,
                birthdate = ?
            WHERE student_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssisi",
        $student_code,
        $firstname,
        $lastname,
        $classroom_id,
        $birthdate,
        $student_id
    );

    if ($stmt->execute()) {

        header("Location: students.php?success=update");
        exit();

    } else {

        $error = "แก้ไขข้อมูลไม่สำเร็จ";

    }
}


// ========================================
// ค้นหานักเรียน
// ========================================

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


// ========================================
// ดึงข้อมูลนักเรียน
// ========================================

if ($search != "") {

    $sql = "SELECT
                students.*,
                classrooms.level,
                classrooms.room,
                classrooms.academic_year,
                classrooms.semester

            FROM students

            LEFT JOIN classrooms
            ON students.classroom_id = classrooms.classroom_id

            WHERE students.student_code LIKE ?
               OR students.firstname LIKE ?
               OR students.lastname LIKE ?

            ORDER BY students.student_id DESC";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $students = $stmt->get_result();

} else {

    $sql = "SELECT
                students.*,
                classrooms.level,
                classrooms.room,
                classrooms.academic_year,
                classrooms.semester

            FROM students

            LEFT JOIN classrooms
            ON students.classroom_id = classrooms.classroom_id

            ORDER BY students.student_id DESC";

    $students = $conn->query($sql);
}


// ========================================
// ดึงข้อมูลห้องเรียน
// ========================================

$classrooms = $conn->query(
    "SELECT *
     FROM classrooms
     ORDER BY level, room"
);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>จัดการนักเรียน</title>

    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการข้อมูลนักเรียน</h1>

    <p>
        ผู้ใช้งาน:
        <?php echo htmlspecialchars($_SESSION["fullname"]); ?>
    </p>


    <!-- ================================= -->
    <!-- ปุ่มกลับ -->
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
    <!-- ข้อความแจ้งเตือน -->
    <!-- ================================= -->

    <?php if (isset($_GET["success"])) { ?>

        <div class="success">

            <?php

            if ($_GET["success"] == "add") {
                echo "เพิ่มนักเรียนเรียบร้อยแล้ว";
            }

            if ($_GET["success"] == "update") {
                echo "แก้ไขข้อมูลเรียบร้อยแล้ว";
            }

            if ($_GET["success"] == "delete") {
                echo "ลบนักเรียนเรียบร้อยแล้ว";
            }

            ?>

        </div>

    <?php } ?>


    <?php if (isset($error)) { ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>


    <!-- ================================= -->
    <!-- ฟอร์มเพิ่ม / แก้ไข -->
    <!-- ================================= -->

    <h2>

        <?php

        if ($edit_student) {
            echo "แก้ไขข้อมูลนักเรียน";
        } else {
            echo "เพิ่มนักเรียน";
        }

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_student) { ?>

            <input
                type="hidden"
                name="student_id"
                value="<?php echo $edit_student["student_id"]; ?>">

        <?php } ?>


        <div>

            <label>
                รหัสนักเรียน
            </label>

            <input
                type="text"
                name="student_code"
                required
                value="<?php
                    echo $edit_student
                        ? htmlspecialchars($edit_student["student_code"])
                        : "";
                ?>">

        </div>


        <div>

            <label>
                ชื่อ
            </label>

            <input
                type="text"
                name="firstname"
                required
                value="<?php
                    echo $edit_student
                        ? htmlspecialchars($edit_student["firstname"])
                        : "";
                ?>">

        </div>


        <div>

            <label>
                นามสกุล
            </label>

            <input
                type="text"
                name="lastname"
                required
                value="<?php
                    echo $edit_student
                        ? htmlspecialchars($edit_student["lastname"])
                        : "";
                ?>">

        </div>


        <div>

            <label>
                ห้องเรียน
            </label>

            <select name="classroom_id" required>

                <option value="">
                    -- เลือกห้องเรียน --
                </option>

                <?php while ($class = $classrooms->fetch_assoc()) { ?>

                    <option
                        value="<?php echo $class["classroom_id"]; ?>"

                        <?php

                        if (
                            $edit_student &&
                            $edit_student["classroom_id"]
                            == $class["classroom_id"]
                        ) {
                            echo "selected";
                        }

                        ?>
                    >

                        <?php
                        echo htmlspecialchars(
                            $class["level"]
                            . "/"
                            . $class["room"]
                            . " ปี "
                            . $class["academic_year"]
                            . " เทอม "
                            . $class["semester"]
                        );
                        ?>

                    </option>

                <?php } ?>

            </select>

        </div>


        <div>

            <label>
                วันเกิด
            </label>

            <input
                type="date"
                name="birthdate"

                value="<?php
                    echo $edit_student
                        ? htmlspecialchars($edit_student["birthdate"])
                        : "";
                ?>">

        </div>


        <br>


        <?php if ($edit_student) { ?>

            <button
                type="submit"
                name="update_student">

                บันทึกการแก้ไข

            </button>

            <a href="students.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_student">

                เพิ่มนักเรียน

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายชื่อนักเรียน</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหารหัส / ชื่อ / นามสกุล"
            value="<?php echo htmlspecialchars($search); ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="students.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางนักเรียน -->
    <!-- ================================= -->

    <table border="1"
           cellpadding="10"
           cellspacing="0"
           width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>รหัสนักเรียน</th>

            <th>ชื่อ-นามสกุล</th>

            <th>ห้อง</th>

            <th>ปีการศึกษา</th>

            <th>ภาคเรียน</th>

            <th>วันเกิด</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if ($students && $students->num_rows > 0) {

            while ($row = $students->fetch_assoc()) {

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
                    echo htmlspecialchars(
                        $row["semester"]
                    );
                    ?>
                </td>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $row["birthdate"]
                    );
                    ?>
                </td>

                <td>

                    <a href="students.php?edit=<?php
                        echo $row["student_id"];
                    ?>">
                        แก้ไข
                    </a>

                    |

                    <a
                        href="students.php?delete=<?php
                            echo $row["student_id"];
                        ?>"
                        onclick="return confirm(
                            'ต้องการลบนักเรียนคนนี้หรือไม่?'
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
                    ไม่พบข้อมูลนักเรียน
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>