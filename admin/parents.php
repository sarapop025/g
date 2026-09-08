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
// เพิ่มผู้ปกครอง
// ========================================

if (isset($_POST["add_parent"])) {

    $username  = trim($_POST["username"]);
    $password  = $_POST["password"];
    $firstname = trim($_POST["firstname"]);
    $lastname  = trim($_POST["lastname"]);
    $phone     = trim($_POST["phone"]);

    // ตรวจสอบ Username ซ้ำ
    $check = $conn->prepare(
        "SELECT user_id FROM users WHERE username = ?"
    );

    $check->bind_param("s", $username);
    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "Username นี้มีอยู่แล้ว";

    } else {

        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $fullname = $firstname . " " . $lastname;

        $conn->begin_transaction();

        try {

            // ========================================
            // เพิ่มข้อมูล users
            // ========================================

            $sql = "INSERT INTO users
                    (username, password, fullname, role)
                    VALUES (?, ?, ?, 'parent')";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sss",
                $username,
                $password_hash,
                $fullname
            );

            $stmt->execute();

            $user_id = $conn->insert_id;


            // ========================================
            // เพิ่มข้อมูล parents
            // ========================================

            $sql = "INSERT INTO parents
                    (user_id, firstname, lastname, phone)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "isss",
                $user_id,
                $firstname,
                $lastname,
                $phone
            );

            $stmt->execute();

            $conn->commit();

            header("Location: parents.php?success=add");
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $error = "เพิ่มข้อมูลไม่สำเร็จ: "
                   . $e->getMessage();
        }
    }
}


// ========================================
// ลบผู้ปกครอง
// ========================================

if (isset($_GET["delete"])) {

    $parent_id = intval($_GET["delete"]);

    // หา user_id
    $sql = "SELECT user_id
            FROM parents
            WHERE parent_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $parent_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $parent = $result->fetch_assoc();

        $user_id = $parent["user_id"];

        $conn->begin_transaction();

        try {

            // ลบ user
            // parent จะถูกลบตาม CASCADE
            $sql = "DELETE FROM users
                    WHERE user_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "i",
                $user_id
            );

            $stmt->execute();

            $conn->commit();

            header("Location: parents.php?success=delete");
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

$edit_parent = null;

if (isset($_GET["edit"])) {

    $parent_id = intval($_GET["edit"]);

    $sql = "SELECT
                parents.*,
                users.username

            FROM parents

            INNER JOIN users
            ON parents.user_id = users.user_id

            WHERE parents.parent_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $parent_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $edit_parent = $result->fetch_assoc();
    }
}


// ========================================
// แก้ไขผู้ปกครอง
// ========================================

if (isset($_POST["update_parent"])) {

    $parent_id = intval($_POST["parent_id"]);
    $user_id   = intval($_POST["user_id"]);

    $username  = trim($_POST["username"]);
    $firstname = trim($_POST["firstname"]);
    $lastname  = trim($_POST["lastname"]);
    $phone     = trim($_POST["phone"]);

    $fullname = $firstname . " " . $lastname;


    // ตรวจสอบ Username ซ้ำ
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

            // Update users
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


            // Update parents
            $sql = "UPDATE parents
                    SET firstname = ?,
                        lastname = ?,
                        phone = ?
                    WHERE parent_id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssi",
                $firstname,
                $lastname,
                $phone,
                $parent_id
            );

            $stmt->execute();

            $conn->commit();

            header("Location: parents.php?success=update");
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $error = "แก้ไขข้อมูลไม่สำเร็จ";
        }
    }
}


// ========================================
// เชื่อมผู้ปกครองกับนักเรียน
// ========================================

if (isset($_POST["link_student"])) {

    $parent_id  = intval($_POST["parent_id"]);
    $student_id = intval($_POST["student_id"]);

    // ตรวจสอบว่ามีความสัมพันธ์อยู่แล้วหรือไม่
    $check = $conn->prepare(
        "SELECT *
         FROM parent_student
         WHERE parent_id = ?
         AND student_id = ?"
    );

    $check->bind_param(
        "ii",
        $parent_id,
        $student_id
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        $error = "ผู้ปกครองกับนักเรียนคู่นี้เชื่อมกันแล้ว";

    } else {

        $sql = "INSERT INTO parent_student
                (parent_id, student_id)
                VALUES (?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ii",
            $parent_id,
            $student_id
        );

        if ($stmt->execute()) {

            header(
                "Location: parents.php?success=link"
            );

            exit();

        } else {

            $error = "เชื่อมข้อมูลไม่สำเร็จ";
        }
    }
}


// ========================================
// ยกเลิกการเชื่อมผู้ปกครองกับนักเรียน
// ========================================

if (isset($_GET["unlink"])) {

    $parent_id  = intval($_GET["parent_id"]);
    $student_id = intval($_GET["unlink"]);

    $sql = "DELETE FROM parent_student
            WHERE parent_id = ?
            AND student_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $parent_id,
        $student_id
    );

    if ($stmt->execute()) {

        header(
            "Location: parents.php?success=unlink"
        );

        exit();
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
// ดึงข้อมูลผู้ปกครอง
// ========================================

if ($search != "") {

    $sql = "SELECT
                parents.*,
                users.username,
                users.status

            FROM parents

            INNER JOIN users
            ON parents.user_id = users.user_id

            WHERE parents.firstname LIKE ?
               OR parents.lastname LIKE ?
               OR parents.phone LIKE ?
               OR users.username LIKE ?

            ORDER BY parents.parent_id DESC";

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

    $parents = $stmt->get_result();

} else {

    $sql = "SELECT
                parents.*,
                users.username,
                users.status

            FROM parents

            INNER JOIN users
            ON parents.user_id = users.user_id

            ORDER BY parents.parent_id DESC";

    $parents = $conn->query($sql);
}


// ========================================
// ดึงนักเรียน
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

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>จัดการผู้ปกครอง</title>

    <link rel="stylesheet"
          href="../css/style.css">

</head>

<body>

<div class="container">

    <h1>จัดการข้อมูลผู้ปกครอง</h1>

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
                    echo "เพิ่มผู้ปกครองเรียบร้อยแล้ว";
                    break;

                case "update":
                    echo "แก้ไขข้อมูลเรียบร้อยแล้ว";
                    break;

                case "delete":
                    echo "ลบผู้ปกครองเรียบร้อยแล้ว";
                    break;

                case "link":
                    echo "เชื่อมผู้ปกครองกับนักเรียนเรียบร้อยแล้ว";
                    break;

                case "unlink":
                    echo "ยกเลิกการเชื่อมเรียบร้อยแล้ว";
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

        echo $edit_parent
            ? "แก้ไขข้อมูลผู้ปกครอง"
            : "เพิ่มผู้ปกครอง";

        ?>

    </h2>


    <form method="POST">

        <?php if ($edit_parent) { ?>

            <input
                type="hidden"
                name="parent_id"
                value="<?php
                    echo $edit_parent["parent_id"];
                ?>">

            <input
                type="hidden"
                name="user_id"
                value="<?php
                    echo $edit_parent["user_id"];
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

                echo $edit_parent
                    ? htmlspecialchars(
                        $edit_parent["username"]
                    )
                    : "";

                ?>">

        </div>


        <!-- Password -->

        <?php if (!$edit_parent) { ?>

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

                echo $edit_parent
                    ? htmlspecialchars(
                        $edit_parent["firstname"]
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

                echo $edit_parent
                    ? htmlspecialchars(
                        $edit_parent["lastname"]
                    )
                    : "";

                ?>">

        </div>


        <!-- เบอร์โทร -->

        <div>

            <label>
                เบอร์โทรศัพท์
            </label>

            <input
                type="text"
                name="phone"

                value="<?php

                echo $edit_parent
                    ? htmlspecialchars(
                        $edit_parent["phone"]
                    )
                    : "";

                ?>">

        </div>


        <br>


        <?php if ($edit_parent) { ?>

            <button
                type="submit"
                name="update_parent">

                บันทึกการแก้ไข

            </button>

            <a href="parents.php">
                ยกเลิก
            </a>

        <?php } else { ?>

            <button
                type="submit"
                name="add_parent">

                เพิ่มผู้ปกครอง

            </button>

        <?php } ?>

    </form>


    <hr>


    <!-- ================================= -->
    <!-- ค้นหา -->
    <!-- ================================= -->

    <h2>รายชื่อผู้ปกครอง</h2>

    <form method="GET">

        <input
            type="text"
            name="search"
            placeholder="ค้นหาชื่อ / Username / เบอร์โทร"

            value="<?php
                echo htmlspecialchars($search);
            ?>">

        <button type="submit">
            ค้นหา
        </button>

        <a href="parents.php">
            แสดงทั้งหมด
        </a>

    </form>


    <br>


    <!-- ================================= -->
    <!-- ตารางผู้ปกครอง -->
    <!-- ================================= -->

    <table
        border="1"
        cellpadding="10"
        cellspacing="0"
        width="100%">

        <thead>

        <tr>

            <th>ลำดับ</th>

            <th>ชื่อ-นามสกุล</th>

            <th>Username</th>

            <th>เบอร์โทร</th>

            <th>นักเรียน</th>

            <th>จัดการ</th>

        </tr>

        </thead>


        <tbody>

        <?php

        $i = 1;

        if ($parents && $parents->num_rows > 0) {

            while ($row = $parents->fetch_assoc()) {

        ?>

            <tr>

                <td>
                    <?php echo $i++; ?>
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
                    echo htmlspecialchars(
                        $row["phone"]
                    );
                    ?>

                </td>

                <td>

                    <?php

                    // ดึงนักเรียนของผู้ปกครอง
                    $sql_student = "
                        SELECT
                            students.student_id,
                            students.student_code,
                            students.firstname,
                            students.lastname

                        FROM parent_student

                        INNER JOIN students
                        ON parent_student.student_id
                           = students.student_id

                        WHERE parent_student.parent_id = ?
                    ";

                    $stmt_student =
                        $conn->prepare($sql_student);

                    $stmt_student->bind_param(
                        "i",
                        $row["parent_id"]
                    );

                    $stmt_student->execute();

                    $student_result =
                        $stmt_student->get_result();

                    if ($student_result->num_rows > 0) {

                        while (
                            $student =
                            $student_result->fetch_assoc()
                        ) {

                            echo htmlspecialchars(
                                $student["student_code"]
                                . " - "
                                . $student["firstname"]
                                . " "
                                . $student["lastname"]
                            );

                            echo "<br>";

                            echo "<a href='parents.php?"
                                . "parent_id="
                                . $row["parent_id"]
                                . "&unlink="
                                . $student["student_id"]
                                . "'"
                                . " onclick=\"return confirm("
                                . "'ยกเลิกการเชื่อมกับนักเรียนหรือไม่?'"
                                . ");\">"
                                . "ยกเลิก"
                                . "</a>";

                            echo "<br><br>";
                        }

                    } else {

                        echo "ยังไม่มีนักเรียน";
                    }

                    ?>

                </td>

                <td>

                    <a href="parents.php?edit=<?php
                        echo $row["parent_id"];
                    ?>">
                        แก้ไข
                    </a>

                    |

                    <a
                        href="parents.php?delete=<?php
                            echo $row["parent_id"];
                        ?>"

                        onclick="return confirm(
                            'ต้องการลบผู้ปกครองคนนี้หรือไม่?'
                        );">

                        ลบ

                    </a>

                </td>

            </tr>


            <!-- ================================= -->
            <!-- เชื่อมนักเรียน -->
            <!-- ================================= -->

            <tr>

                <td colspan="6">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="parent_id"
                            value="<?php
                                echo $row["parent_id"];
                            ?>">

                        <label>
                            เชื่อมกับนักเรียน:
                        </label>

                        <select
                            name="student_id"
                            required>

                            <option value="">
                                -- เลือกนักเรียน --
                            </option>

                            <?php

                            // ดึงนักเรียนใหม่
                            $student_list = $conn->query(
                                "SELECT
                                    student_id,
                                    student_code,
                                    firstname,
                                    lastname
                                 FROM students
                                 ORDER BY firstname"
                            );

                            while (
                                $student =
                                $student_list->fetch_assoc()
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

                            <?php } ?>

                        </select>

                        <button
                            type="submit"
                            name="link_student">

                            เชื่อมข้อมูล

                        </button>

                    </form>

                </td>

            </tr>

        <?php

            }

        } else {

        ?>

            <tr>

                <td colspan="6">
                    ไม่พบข้อมูลผู้ปกครอง
                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>