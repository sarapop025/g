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
// เพิ่มห้องเรียน
// ========================================

if (isset($_POST["add_classroom"])) {

    $level         = trim($_POST["level"]);
    $room          = trim($_POST["room"]);
    $academic_year = trim($_POST["academic_year"]);
    $semester      = intval($_POST["semester"]);

    // ตรวจสอบข้อมูลซ้ำ
    $check = $conn->prepare(
        "SELECT classroom_id
         FROM classrooms
         WHERE level = ?
         AND room = ?
         AND academic_year = ?
         AND semester = ?"
    );

    $check->bind_param(
        "sssi",
        $level,
        $room,
        $academic_year,
        $semester
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "ห้องเรียนนี้มีอยู่แล้ว";

    } else {

        $sql = "INSERT INTO classrooms
                (level, room, academic_year, semester)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssi",
            $level,
            $room,
            $academic_year,
            $semester
        );

        if ($stmt->execute()) {

            header(
                "Location: classrooms.php?success=add"
            );

            exit();

        } else {

            $error = "เพิ่มห้องเรียนไม่สำเร็จ";
        }
    }
}


// ========================================
// ลบห้องเรียน
// ========================================

if (isset($_GET["delete"])) {

    $classroom_id = intval($_GET["delete"]);

    // ตรวจสอบว่ามีนักเรียนอยู่หรือไม่
    $check = $conn->prepare(
        "SELECT student_id
         FROM students
         WHERE classroom_id = ?"
    );

    $check->bind_param(
        "i",
        $classroom_id
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "ไม่สามารถลบห้องเรียนได้ เพราะมีนักเรียนอยู่ในห้อง";

    } else {

        $sql = "DELETE FROM classrooms
                WHERE classroom_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "i",
            $classroom_id
        );

        if ($stmt->execute()) {

            header(
                "Location: classrooms.php?success=delete"
            );

            exit();

        } else {

            $error = "ลบห้องเรียนไม่สำเร็จ";
        }
    }
}


// ========================================
// ดึงข้อมูลห้องเรียนสำหรับแก้ไข
// ========================================

$edit_classroom = null;

if (isset($_GET["edit"])) {

    $classroom_id = intval($_GET["edit"]);

    $sql = "SELECT *
            FROM classrooms
            WHERE classroom_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $classroom_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_classroom = $result->fetch_assoc();
    }
}


// ========================================
// แก้ไขห้องเรียน
// ========================================

if (isset($_POST["update_classroom"])) {

    $classroom_id  = intval($_POST["classroom_id"]);
    $level         = trim($_POST["level"]);
    $room          = trim($_POST["room"]);
    $academic_year = trim($_POST["academic_year"]);
    $semester      = intval($_POST["semester"]);

    // ตรวจสอบข้อมูลซ้ำ
    $check = $conn->prepare(
        "SELECT classroom_id
         FROM classrooms
         WHERE level = ?
         AND room = ?
         AND academic_year = ?
         AND semester = ?
         AND classroom_id != ?"
    );

    $check->bind_param(
        "sssii",
        $level,
        $room,
        $academic_year,
        $semester,
        $classroom_id
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "ข้อมูลห้องเรียนนี้มีอยู่แล้ว";

    } else {

        $sql = "UPDATE classrooms
                SET level = ?,
                    room = ?,
                    academic_year = ?,
                    semester = ?
                WHERE classroom_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $level,
            $room,
            $academic_year,
            $semester,
            $classroom_id
        );

        if ($stmt->execute()) {

            header(
                "Location: classrooms.php?success=update"
            );

            exit();

        } else {

            $error = "แก้ไขห้องเรียนไม่สำเร็จ";
        }
    }
}


// ========================================
// ค้นหาห้องเรียน
// ========================================

$search = "";

if (isset($_GET["search"])) {

    $search = trim($_GET["search"]);
}


// ========================================
// ดึงข้อมูลห้องเรียน
// ========================================

if ($search != "") {

    $sql = "SELECT
                classrooms.*,
                COUNT(students.student_id) AS student_count

            FROM classrooms

            LEFT JOIN students
            ON classrooms.classroom_id
               = students.classroom_id

            WHERE classrooms.level LIKE ?
               OR classrooms.room LIKE ?
               OR classrooms.academic_year LIKE ?

            GROUP BY classrooms.classroom_id

            ORDER BY
                classrooms.academic_year DESC,
                classrooms.level,
                classrooms.room";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $classrooms = $stmt->get_result();

} else {

    $sql = "SELECT
                classrooms.*,
                COUNT(students.student_id) AS student_count

            FROM classrooms

            LEFT JOIN students
            ON classrooms.classroom_id
               = students.classroom_id

            GROUP BY classrooms.classroom_id

            ORDER BY
                classrooms.academic_year DESC,
                classrooms.level,
                classrooms.room";

    $classrooms = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>จัดการห้องเรียน</title>

    <link rel="stylesheet"
          href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการห้องเรียน</h1>

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
                    echo "เพิ่มห้องเรียนเรียบร้อยแล้ว";
                    break;

                case "update":
                    echo "แก้ไขห้องเรียนเรียบร้อยแล้ว";
                    break;

                case "delete":
                    echo "ลบห้องเรียนเรียบร้อยแล้ว";
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

        echo $edit_classroom
            ? "แก้ไขห้องเรียน"
            : "เพิ่มห้องเรียน";

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_classroom) { ?>

            <input
                type="hidden"
                name="classroom_id"
                value="<?php
                    echo $edit_classroom["classroom_id"];
                ?>">

        <?php } ?>


        <!-- ระดับชั้น -->

        <div>

            <label>
                ระดับชั้น
            </label>

            <select
                name="level"
                required>

                <option value="">
                    -- เลือกระดับชั้น --
                </option>

                <?php

                $levels = [
                    "อนุบาล 1",
                    "อนุบาล 2",
                    "อนุบาล 3",
                    "ป.1",
                    "ป.2",
                    "ป.3",
                    "ป.4",
                    "ป.5",
                    "ป.6",
                    "ม.1",
                    "ม.2",
                    "ม.3",
                    "ม.4",
                    "ม.5",
                    "ม.6"
                ];

                foreach ($levels as $item) {

                    $selected = "";

                    if (
                        $edit_classroom &&
                        $edit_classroom["level"] == $item
                    ) {

                        $selected = "selected";
                    }

                ?>

                    <option
                        value="<?php
                            echo htmlspecialchars($item);
                        ?>"
                        <?php echo $selected; ?>>

                        <?php
                        echo htmlspecialchars($item);
                        ?>

                    </option>

                <?php } ?>

            </select>

        </div>


        <!-- ห้อง -->

        <div>

            <label>
                ห้อง
            </label>

            <input
                type="text"
                name="room"
                required
                placeholder="เช่น 1, 2, 3"

                value="<?php

                echo $edit_classroom
                    ? htmlspecialchars(
                        $edit_classroom["room"]
                    )
                    : "";

                ?>">

        </div>


        <!-- ปีการศึกษา -->

        <div>

            <label>
                ปีการศึกษา
            </label>

            <input
                type="text"
                name="academic_year"
                required
                placeholder="เช่น 2569"

                value="<?php

                echo $edit_classroom
                    ? htmlspecialchars(
                        $edit_classroom["academic_year"]
                    )
                    : "2569";

                ?>">

        </div>


        <!-- ภาคเรียน -->

        <div>

            <label>
                ภาคเรียน
            </label>

            <select
                name="semester"
                required>

                <option
                    value="1"
                    <?php

                    if (
                        $edit_classroom &&
                        $edit_classroom["semester"] == 1
                    ) {
                        echo "selected";
                    }

                    ?>>

                    ภาคเรียนที่ 1

                </option>

                <option
                    value="2"
                    <?php

                    if (
                        $edit_classroom &&
                        $edit_classroom["semester"] == 2
                    ) {
                        echo "selected";
                    }

                    ?>>

                    ภาคเรียนที่ 2

                </option>

            </select>

        </div>


        <br>


        <?php if ($edit_classroom) { ?>

            <button
                type="submit"
                name="update_classroom">

                บันทึกการแก้ไข

            </button>

            <a href="classrooms.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_classroom">

                เพิ่มห้องเรียน

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายการห้องเรียน</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหาระดับชั้น / ห้อง / ปีการศึกษา"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="classrooms.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางห้องเรียน -->
    <!-- ================================= -->

    <table
        border="1"
        cellpadding="10"
        cellspacing="0"
        width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>ระดับชั้น</th>

            <th>ห้อง</th>

            <th>ปีการศึกษา</th>

            <th>ภาคเรียน</th>

            <th>จำนวนนักเรียน</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if (
            $classrooms &&
            $classrooms->num_rows > 0
        ) {

            while (
                $row =
                $classrooms->fetch_assoc()
            ) {

        ?>

            <tr>

                <td>
                    <?php echo $i++; ?>
                </td>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $row["level"]
                    );
                    ?>
                </td>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $row["room"]
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

                    echo $row["semester"] == 1
                        ? "ภาคเรียนที่ 1"
                        : "ภาคเรียนที่ 2";

                    ?>

                </td>

                <td>

                    <?php
                    echo $row["student_count"];
                    ?>

                    คน

                </td>

                <td>

                    <a href="classrooms.php?edit=<?php
                        echo $row["classroom_id"];
                    ?>">

                        แก้ไข

                    </a>

                    |

                    <a
                        href="classrooms.php?delete=<?php
                            echo $row["classroom_id"];
                        ?>"

                        onclick="return confirm(
                            'ต้องการลบห้องเรียนนี้หรือไม่?'
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

                <td colspan="7">
                    ไม่พบข้อมูลห้องเรียน
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>
