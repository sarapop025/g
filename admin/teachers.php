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
// เพิ่มครู
// ========================================

if (isset($_POST["add_teacher"])) {

    $username     = trim($_POST["username"]);
    $password     = $_POST["password"];
    $firstname    = trim($_POST["firstname"]);
    $lastname     = trim($_POST["lastname"]);
    $teacher_code = trim($_POST["teacher_code"]);

    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare(
        "SELECT user_id FROM users WHERE username = ?"
    );

    $check->bind_param("s", $username);
    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "Username นี้มีอยู่แล้ว";

    } else {

        // เข้ารหัส Password
        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // เริ่ม Transaction
        $conn->begin_transaction();

        try {

            // -------------------------------
            // เพิ่ม users
            // -------------------------------

            $sql = "INSERT INTO users
                    (username, password, fullname, role)
                    VALUES (?, ?, ?, 'teacher')";

            $stmt = $conn->prepare($sql);

            $fullname = $firstname . " " . $lastname;

            $stmt->bind_param(
                "sss",
                $username,
                $password_hash,
                $fullname
            );

            $stmt->execute();

            $user_id = $conn->insert_id;


            // -------------------------------
            // เพิ่ม teachers
            // -------------------------------

            $sql = "INSERT INTO teachers
                    (user_id, teacher_code, firstname, lastname)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "isss",
                $user_id,
                $teacher_code,
                $firstname,
                $lastname
            );

            $stmt->execute();

            // สำเร็จ
            $conn->commit();

            header("Location: teachers.php?success=add");
            exit();

        } catch (Exception $e) {

            // ถ้าเกิด Error ให้ย้อนกลับ
            $conn->rollback();

            $error = "เพิ่มข้อมูลไม่สำเร็จ: " . $e->getMessage();
        }
    }
}


// ========================================
// ลบครู
// ========================================

if (isset($_GET["delete"])) {

    $teacher_id = intval($_GET["delete"]);

    // หา user_id ก่อน
    $sql = "SELECT user_id
            FROM teachers
            WHERE teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $teacher_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $teacher = $result->fetch_assoc();

        $user_id = $teacher["user_id"];

        $conn->begin_transaction();

        try {

            // ลบ users
            // teachers จะถูกลบตาม ON DELETE CASCADE
            $sql = "DELETE FROM users
                    WHERE user_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param("i", $user_id);

            $stmt->execute();

            $conn->commit();

            header("Location: teachers.php?success=delete");
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $error = "ลบข้อมูลไม่สำเร็จ";
        }
    }
}


// ========================================
// ดึงข้อมูลสำหรับแก้ไข
// ========================================

$edit_teacher = null;

if (isset($_GET["edit"])) {

    $teacher_id = intval($_GET["edit"]);

    $sql = "SELECT
                teachers.*,
                users.username

            FROM teachers

            INNER JOIN users
            ON teachers.user_id = users.user_id

            WHERE teachers.teacher_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $teacher_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_teacher = $result->fetch_assoc();
    }
}


// ========================================
// แก้ไขครู
// ========================================

if (isset($_POST["update_teacher"])) {

    $teacher_id   = intval($_POST["teacher_id"]);
    $user_id      = intval($_POST["user_id"]);

    $username     = trim($_POST["username"]);
    $teacher_code = trim($_POST["teacher_code"]);
    $firstname    = trim($_POST["firstname"]);
    $lastname     = trim($_POST["lastname"]);

    $fullname = $firstname . " " . $lastname;


    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare(
        "SELECT user_id
         FROM users
         WHERE username = ?
         AND user_id != ?"
    );

    $check->bind_param(
        "si",
        $username,
        $user_id
    );

    $check->execute();

    $check_result = $check->get_result();


    if ($check_result->num_rows > 0) {

        $error = "Username นี้มีอยู่แล้ว";

    } else {

        $conn->begin_transaction();

        try {

            // -------------------------------
            // Update users
            // -------------------------------

            $sql = "UPDATE users
                    SET username = ?,
                        fullname = ?
                    WHERE user_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssi",
                $username,
                $fullname,
                $user_id
            );

            $stmt->execute();


            // -------------------------------
            // Update teachers
            // -------------------------------

            $sql = "UPDATE teachers
                    SET teacher_code = ?,
                        firstname = ?,
                        lastname = ?
                    WHERE teacher_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssi",
                $teacher_code,
                $firstname,
                $lastname,
                $teacher_id
            );

            $stmt->execute();

            $conn->commit();

            header("Location: teachers.php?success=update");
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $error = "แก้ไขข้อมูลไม่สำเร็จ";
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
// ดึงข้อมูลครู
// ========================================

if ($search != "") {

    $sql = "SELECT
                teachers.*,
                users.username,
                users.status

            FROM teachers

            INNER JOIN users
            ON teachers.user_id = users.user_id

            WHERE teachers.teacher_code LIKE ?
               OR teachers.firstname LIKE ?
               OR teachers.lastname LIKE ?
               OR users.username LIKE ?

            ORDER BY teachers.teacher_id DESC";

    $stmt = $conn->prepare($sql);

    $keyword = "%" . $search . "%";

    $stmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $teachers = $stmt->get_result();

} else {

    $sql = "SELECT
                teachers.*,
                users.username,
                users.status

            FROM teachers

            INNER JOIN users
            ON teachers.user_id = users.user_id

            ORDER BY teachers.teacher_id DESC";

    $teachers = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>จัดการข้อมูลครู</title>

    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการข้อมูลครู</h1>

    <p>
        ผู้ใช้งาน:
        <?php
        echo htmlspecialchars($_SESSION["fullname"]);
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

            if ($_GET["success"] == "add") {
                echo "เพิ่มข้อมูลครูเรียบร้อยแล้ว";
            }

            elseif ($_GET["success"] == "update") {
                echo "แก้ไขข้อมูลครูเรียบร้อยแล้ว";
            }

            elseif ($_GET["success"] == "delete") {
                echo "ลบข้อมูลครูเรียบร้อยแล้ว";
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

        echo $edit_teacher
            ? "แก้ไขข้อมูลครู"
            : "เพิ่มครู";

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_teacher) { ?>

            <input
                type="hidden"
                name="teacher_id"
                value="<?php
                    echo $edit_teacher["teacher_id"];
                ?>">

            <input
                type="hidden"
                name="user_id"
                value="<?php
                    echo $edit_teacher["user_id"];
                ?>">

        <?php } ?>


        <!-- Username -->

        <div>

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                required

                value="<?php

                echo $edit_teacher
                    ? htmlspecialchars(
                        $edit_teacher["username"]
                    )
                    : "";

                ?>">

        </div>


        <!-- Password เฉพาะตอนเพิ่ม -->

        <?php if (!$edit_teacher) { ?>

            <div>

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    required
                    minlength="4">

            </div>

        <?php } ?>


        <!-- รหัสครู -->

        <div>

            <label>
                รหัสครู
            </label>

            <input
                type="text"
                name="teacher_code"
                required

                value="<?php

                echo $edit_teacher
                    ? htmlspecialchars(
                        $edit_teacher["teacher_code"]
                    )
                    : "";

                ?>">

        </div>


        <!-- ชื่อ -->

        <div>

            <label>
                ชื่อ
            </label>

            <input
                type="text"
                name="firstname"
                required

                value="<?php

                echo $edit_teacher
                    ? htmlspecialchars(
                        $edit_teacher["firstname"]
                    )
                    : "";

                ?>">

        </div>


        <!-- นามสกุล -->

        <div>

            <label>
                นามสกุล
            </label>

            <input
                type="text"
                name="lastname"
                required

                value="<?php

                echo $edit_teacher
                    ? htmlspecialchars(
                        $edit_teacher["lastname"]
                    )
                    : "";

                ?>">

        </div>


        <br>


        <?php if ($edit_teacher) { ?>

            <button
                type="submit"
                name="update_teacher">

                บันทึกการแก้ไข

            </button>

            <a href="teachers.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_teacher">

                เพิ่มครู

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายชื่อครู</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหารหัสครู / ชื่อ / Username"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="teachers.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางครู -->
    <!-- ================================= -->

    <table
        border="1"
        cellpadding="10"
        cellspacing="0"
        width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>รหัสครู</th>

            <th>ชื่อ-นามสกุล</th>

            <th>Username</th>

            <th>สถานะ</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if ($teachers && $teachers->num_rows > 0) {

            while ($row = $teachers->fetch_assoc()) {

        ?>

            <tr>

                <td>
                    <?php echo $i++; ?>
                </td>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $row["teacher_code"]
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
                        $row["username"]
                    );
                    ?>
                </td>

                <td>

                    <?php

                    if ($row["status"] == 1) {
                        echo "ใช้งาน";
                    } else {
                        echo "ปิดใช้งาน";
                    }

                    ?>

                </td>

                <td>

                    <a href="teachers.php?edit=<?php
                        echo $row["teacher_id"];
                    ?>">
                        แก้ไข
                    </a>

                    |

                    <a
                        href="teachers.php?delete=<?php
                            echo $row["teacher_id"];
                        ?>"

                        onclick="return confirm(
                            'ต้องการลบข้อมูลครูคนนี้หรือไม่?'
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
                    ไม่พบข้อมูลครู
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>