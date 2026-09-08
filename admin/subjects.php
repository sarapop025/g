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
// เพิ่มรายวิชา
// ========================================

if (isset($_POST["add_subject"])) {

    $subject_code = trim($_POST["subject_code"]);
    $subject_name = trim($_POST["subject_name"]);
    $credits      = floatval($_POST["credits"]);

    // ตรวจสอบรหัสวิชาซ้ำ
    $check = $conn->prepare(
        "SELECT subject_id
         FROM subjects
         WHERE subject_code = ?"
    );

    $check->bind_param(
        "s",
        $subject_code
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "รหัสวิชานี้มีอยู่แล้ว";

    } else {

        $sql = "INSERT INTO subjects
                (subject_code, subject_name, credits)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssd",
            $subject_code,
            $subject_name,
            $credits
        );

        if ($stmt->execute()) {

            header(
                "Location: subjects.php?success=add"
            );

            exit();

        } else {

            $error = "เพิ่มรายวิชาไม่สำเร็จ";
        }
    }
}


// ========================================
// ลบรายวิชา
// ========================================

if (isset($_GET["delete"])) {

    $subject_id = intval($_GET["delete"]);

    // ตรวจสอบว่ารายวิชาถูกใช้ใน teaching หรือไม่
    $check = $conn->prepare(
        "SELECT teaching_id
         FROM teaching
         WHERE subject_id = ?"
    );

    $check->bind_param(
        "i",
        $subject_id
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "ไม่สามารถลบรายวิชาได้ เพราะรายวิชานี้ถูกใช้งานแล้ว";

    } else {

        $sql = "DELETE FROM subjects
                WHERE subject_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "i",
            $subject_id
        );

        if ($stmt->execute()) {

            header(
                "Location: subjects.php?success=delete"
            );

            exit();

        } else {

            $error = "ลบรายวิชาไม่สำเร็จ";
        }
    }
}


// ========================================
// ดึงข้อมูลสำหรับแก้ไข
// ========================================

$edit_subject = null;

if (isset($_GET["edit"])) {

    $subject_id = intval($_GET["edit"]);

    $sql = "SELECT *
            FROM subjects
            WHERE subject_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $subject_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_subject = $result->fetch_assoc();
    }
}


// ========================================
// แก้ไขรายวิชา
// ========================================

if (isset($_POST["update_subject"])) {

    $subject_id   = intval($_POST["subject_id"]);
    $subject_code = trim($_POST["subject_code"]);
    $subject_name = trim($_POST["subject_name"]);
    $credits      = floatval($_POST["credits"]);

    // ตรวจสอบรหัสวิชาซ้ำ
    $check = $conn->prepare(
        "SELECT subject_id
         FROM subjects
         WHERE subject_code = ?
         AND subject_id != ?"
    );

    $check->bind_param(
        "si",
        $subject_code,
        $subject_id
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "รหัสวิชานี้มีอยู่แล้ว";

    } else {

        $sql = "UPDATE subjects
                SET subject_code = ?,
                    subject_name = ?,
                    credits = ?
                WHERE subject_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssdi",
            $subject_code,
            $subject_name,
            $credits,
            $subject_id
        );

        if ($stmt->execute()) {

            header(
                "Location: subjects.php?success=update"
            );

            exit();

        } else {

            $error = "แก้ไขรายวิชาไม่สำเร็จ";
        }
    }
}


// ========================================
// ค้นหารายวิชา
// ========================================

$search = "";

if (isset($_GET["search"])) {

    $search = trim($_GET["search"]);
}


// ========================================
// ดึงข้อมูลรายวิชา
// ========================================

if ($search != "") {

    $sql = "SELECT
                subjects.*,
                COUNT(teaching.teaching_id)
                AS teaching_count

            FROM subjects

            LEFT JOIN teaching
            ON subjects.subject_id
               = teaching.subject_id

            WHERE subjects.subject_code LIKE ?
               OR subjects.subject_name LIKE ?

            GROUP BY subjects.subject_id

            ORDER BY subjects.subject_code";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->bind_param(
        "ss",
        $keyword,
        $keyword
    );

    $stmt->execute();

    $subjects = $stmt->get_result();

} else {

    $sql = "SELECT
                subjects.*,
                COUNT(teaching.teaching_id)
                AS teaching_count

            FROM subjects

            LEFT JOIN teaching
            ON subjects.subject_id
               = teaching.subject_id

            GROUP BY subjects.subject_id

            ORDER BY subjects.subject_code";

    $subjects = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>จัดการรายวิชา</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการรายวิชา</h1>

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
                    echo "เพิ่มรายวิชาเรียบร้อยแล้ว";
                    break;

                case "update":
                    echo "แก้ไขรายวิชาเรียบร้อยแล้ว";
                    break;

                case "delete":
                    echo "ลบรายวิชาเรียบร้อยแล้ว";
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

        echo $edit_subject
            ? "แก้ไขรายวิชา"
            : "เพิ่มรายวิชา";

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_subject) { ?>

            <input
                type="hidden"
                name="subject_id"
                value="<?php
                    echo $edit_subject["subject_id"];
                ?>">

        <?php } ?>


        <!-- รหัสวิชา -->

        <div>

            <label>
                รหัสวิชา
            </label>

            <input
                type="text"
                name="subject_code"
                required
                placeholder="เช่น MAT101"

                value="<?php

                echo $edit_subject
                    ? htmlspecialchars(
                        $edit_subject["subject_code"]
                    )
                    : "";

                ?>">

        </div>


        <!-- ชื่อวิชา -->

        <div>

            <label>
                ชื่อรายวิชา
            </label>

            <input
                type="text"
                name="subject_name"
                required
                placeholder="เช่น คณิตศาสตร์"

                value="<?php

                echo $edit_subject
                    ? htmlspecialchars(
                        $edit_subject["subject_name"]
                    )
                    : "";

                ?>">

        </div>


        <!-- หน่วยกิต -->

        <div>

            <label>
                หน่วยกิต
            </label>

            <input
                type="number"
                name="credits"
                required
                min="0.5"
                max="10"
                step="0.5"

                value="<?php

                echo $edit_subject
                    ? htmlspecialchars(
                        $edit_subject["credits"]
                    )
                    : "1.0";

                ?>">

        </div>


        <br>


        <?php if ($edit_subject) { ?>

            <button
                type="submit"
                name="update_subject">

                บันทึกการแก้ไข

            </button>

            <a href="subjects.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_subject">

                เพิ่มรายวิชา

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายการรายวิชา</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหารหัสวิชา / ชื่อวิชา"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="subjects.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางรายวิชา -->
    <!-- ================================= -->

    <table
        border="1"
        cellpadding="10"
        cellspacing="0"
        width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>รหัสวิชา</th>

            <th>ชื่อวิชา</th>

            <th>หน่วยกิต</th>

            <th>จำนวนการสอน</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if (
            $subjects &&
            $subjects->num_rows > 0
        ) {

            while (
                $row =
                $subjects->fetch_assoc()
            ) {

        ?>

            <tr>

                <td>
                    <?php echo $i++; ?>
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
                    echo htmlspecialchars(
                        $row["credits"]
                    );
                    ?>

                </td>

                <td>

                    <?php
                    echo $row["teaching_count"];
                    ?>

                    รายการ

                </td>

                <td>

                    <a href="subjects.php?edit=<?php
                        echo $row["subject_id"];
                    ?>">

                        แก้ไข

                    </a>

                    |

                    <a
                        href="subjects.php?delete=<?php
                            echo $row["subject_id"];
                        ?>"

                        onclick="return confirm(
                            'ต้องการลบรายวิชานี้หรือไม่?'
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

                <td colspan="6">
                    ไม่พบข้อมูลรายวิชา
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>