
<?php

require_once "config.php";


// ========================================
// ถ้า Login อยู่แล้ว
// ========================================

if (isset($_SESSION["user_id"])) {

    header("Location: index.php");
    exit();

}


// ========================================
// ตัวแปร
// ========================================

$error = "";


// ========================================
// เมื่อกด Login
// ========================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";


    // ====================================
    // ตรวจสอบข้อมูล
    // ====================================

    if ($username == "" || $password == "") {

        $error = "กรุณากรอก Username และ Password";

    } else {


        // ==================================
        // ค้นหาผู้ใช้งาน
        // ==================================

        $sql = "SELECT
                    user_id,
                    username,
                    password,
                    fullname,
                    role,
                    status

                FROM users

                WHERE username = ?

                LIMIT 1";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";

        } else {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();


            $result =
                $stmt->get_result();


            // =================================
            // พบ Username
            // =================================

            if ($result->num_rows == 1) {

                $user =
                    $result->fetch_assoc();


                // =============================
                // ตรวจสอบสถานะบัญชี
                // =============================

                if ($user["status"] != 1) {

                    $error =
                        "บัญชีนี้ถูกระงับการใช้งาน";

                }


                // =============================
                // ตรวจสอบ Password
                // =============================

                elseif (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {


                    // =========================
                    // สร้าง Session
                    // =========================

                    session_regenerate_id(true);


                    $_SESSION["user_id"] =
                        $user["user_id"];

                    $_SESSION["username"] =
                        $user["username"];

                    $_SESSION["fullname"] =
                        $user["fullname"];

                    $_SESSION["role"] =
                        $user["role"];


                    // =========================
                    // ไปหน้า index
                    // =========================

                    header(
                        "Location: index.php"
                    );

                    exit();


                } else {

                    $error =
                        "Username หรือ Password ไม่ถูกต้อง";

                }


            } else {

                $error =
                    "Username หรือ Password ไม่ถูกต้อง";

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Login - ระบบจัดการผลการเรียน
    </title>


    <link
        rel="stylesheet"
        href="css/style.css">

</head>


<body>


<div class="login-container">


    <h1>
        ระบบจัดการผลการเรียน
    </h1>


    <h2>
        เข้าสู่ระบบ
    </h2>


    <!-- ================================= -->
    <!-- แสดง Error -->
    <!-- ================================= -->

    <?php if ($error != "") { ?>

        <div class="error">

            <?php

            echo htmlspecialchars(
                $error
            );

            ?>

        </div>

    <?php } ?>


    <!-- ================================= -->
    <!-- Login Form -->
    <!-- ================================= -->

    <form
        method="POST"
        action="">


        <label>
            Username
        </label>


        <input
            type="text"
            name="username"
            placeholder="กรอก Username"
            autocomplete="username"
            required>


        <label>
            Password
        </label>


        <input
            type="password"
            name="password"
            id="password"
            placeholder="กรอก Password"
            autocomplete="current-password"
            required>


        <button
            type="button"
            id="togglePassword">

            แสดงรหัสผ่าน

        </button>


        <br><br>


        <button
            type="submit">

            เข้าสู่ระบบ

        </button>


    </form>


    <hr>


    <p>
        ระบบจัดการผลการเรียน
    </p>


</div>


<!-- ===================================== -->
<!-- JavaScript -->
<!-- ===================================== -->

<script src="js/script.js"></script>


</body>

</html>
