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
// รับค่าค้นหา
// ========================================

$academic_year = isset($_GET["academic_year"])
    ? trim($_GET["academic_year"])
    : "";

$semester = isset($_GET["semester"])
    ? intval($_GET["semester"])
    : 0;

$classroom_id = isset($_GET["classroom_id"])
    ? intval($_GET["classroom_id"])
    : 0;

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


// ========================================
// ดึงปีการศึกษา
// ========================================

$years = $conn->query(
    "SELECT DISTINCT academic_year
     FROM classrooms
     ORDER BY academic_year DESC"
);


// ========================================
// ดึงห้องเรียน
// ========================================

$classrooms = $conn->query(
    "SELECT *
     FROM classrooms
     ORDER BY academic_year DESC, level, room"
);


// ========================================
// สร้าง SQL รายงาน
// ========================================

$sql = "SELECT

            students.student_id,
            students.student_code,
            students.firstname AS student_firstname,
            students.lastname AS student_lastname,

            classrooms.classroom_id,
            classrooms.level,
            classrooms.room,
            classrooms.academic_year,
            classrooms.semester,

            subjects.subject_code,
            subjects.subject_name,
            subjects.credits,

            grades.score,
            grades.grade,
            grades.status

        FROM grades

        INNER JOIN students
        ON grades.student_id = students.student_id

        INNER JOIN teaching
        ON grades.teaching_id = teaching.teaching_id

        INNER JOIN subjects
        ON teaching.subject_id = subjects.subject_id

        INNER JOIN classrooms
        ON teaching.classroom_id = classrooms.classroom_id

        WHERE 1=1";


// ========================================
// เงื่อนไขปีการศึกษา
// ========================================

$params = [];
$types = "";

if ($academic_year != "") {

    $sql .= " AND classrooms.academic_year = ?";

    $params[] = $academic_year;
    $types .= "s";
}


// ========================================
// เงื่อนไขภาคเรียน
// ========================================

if ($semester > 0) {

    $sql .= " AND classrooms.semester = ?";

    $params[] = $semester;
    $types .= "i";
}


// ========================================
// เงื่อนไขห้องเรียน
// ========================================

if ($classroom_id > 0) {

    $sql .= " AND classrooms.classroom_id = ?";

    $params[] = $classroom_id;
    $types .= "i";
}


// ========================================
// ค้นหานักเรียน
// ========================================

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


// ========================================
// เรียงข้อมูล
// ========================================

$sql .= " ORDER BY
            classrooms.level,
            classrooms.room,
            students.student_code,
            subjects.subject_code";


// ========================================
// Execute Query
// ========================================

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}


// ========================================
// Bind Parameter แบบ Dynamic
// ========================================

if (count($params) > 0) {

    $bind_names = [];

    $bind_names[] = $types;

    for ($i = 0; $i < count($params); $i++) {

        $bind_names[] = &$params[$i];
    }

    call_user_func_array(
        [$stmt, "bind_param"],
        $bind_names
    );
}


$stmt->execute();

$result = $stmt->get_result();


// ========================================
// จัดกลุ่มข้อมูลตามนักเรียน
// ========================================

$students_report = [];

while ($row = $result->fetch_assoc()) {

    $student_id = $row["student_id"];

    if (!isset($students_report[$student_id])) {

        $students_report[$student_id] = [

            "student_code"
                => $row["student_code"],

            "firstname"
                => $row["student_firstname"],

            "lastname"
                => $row["student_lastname"],

            "level"
                => $row["level"],

            "room"
                => $row["room"],

            "academic_year"
                => $row["academic_year"],

            "semester"
                => $row["semester"],

            "subjects"
                => [],

            "total_credit"
                => 0,

            "total_point"
                => 0
        ];
    }


    // ====================================
    // เพิ่มข้อมูลวิชา
    // ====================================

    $students_report[$student_id]["subjects"][] = [

        "subject_code"
            => $row["subject_code"],

        "subject_name"
            => $row["subject_name"],

        "credits"
            => floatval($row["credits"]),

        "score"
            => floatval($row["score"]),

        "grade"
            => floatval($row["grade"]),

        "status"
            => $row["status"]
    ];


    // ====================================
    // คำนวณ GPA
    // ====================================

    $credit = floatval($row["credits"]);

    $grade = floatval($row["grade"]);

    $students_report[$student_id]["total_credit"]
        += $credit;

    $students_report[$student_id]["total_point"]
        += ($credit * $grade);
}


// ========================================
// คำนวณ GPA ของแต่ละคน
// ========================================

foreach ($students_report as $student_id => &$student) {

    if ($student["total_credit"] > 0) {

        $student["gpa"] =
            $student["total_point"]
            / $student["total_credit"];

    } else {

        $student["gpa"] = 0;
    }
}

unset($student);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>รายงานผลการเรียน</title>

    <link
        rel="stylesheet"
        href="../css/style.css">

    <style>

        .report-card {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 30px;
        }

        .report-header {
            margin-bottom: 15px;
        }

        .gpa {
            font-size: 20px;
            font-weight: bold;
        }

        .print-button {
            margin-bottom: 20px;
        }

        @media print {

            .no-print {
                display: none;
            }

            body {
                background: white;
            }

            .report-card {
                page-break-inside: avoid;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- ================================= -->
    <!-- หัวข้อ -->
    <!-- ================================= -->

    <div class="no-print">

        <h1>
            รายงานผลการเรียน
        </h1>

        <p>
            ผู้ใช้งาน:
            <?php
            echo htmlspecialchars(
                $_SESSION["fullname"]
            );
            ?>
        </p>

    </div>


    <!-- ================================= -->
    <!-- เมนู -->
    <!-- ================================= -->

    <div class="no-print">

        <p>

            <a href="dashboard.php">
                ← กลับ Dashboard
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

    </div>


    <!-- ================================= -->
    <!-- ตัวกรอง -->
    <!-- ================================= -->

    <div class="no-print">

        <h2>
            ค้นหารายงาน
        </h2>

        <form method="GET">

            <!-- ปีการศึกษา -->

            <div>

                <label>
                    ปีการศึกษา
                </label>

                <select
                    name="academic_year">

                    <option value="">
                        -- ทุกปีการศึกษา --
                    </option>

                    <?php

                    if ($years) {

                        while (
                            $year =
                            $years->fetch_assoc()
                        ) {

                    ?>

                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $year["academic_year"]
                                );
                            ?>"

                            <?php

                            if (
                                $academic_year
                                == $year["academic_year"]
                            ) {

                                echo "selected";
                            }

                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $year["academic_year"]
                            );
                            ?>

                        </option>

                    <?php

                        }

                    }

                    ?>

                </select>

            </div>


            <!-- ภาคเรียน -->

            <div>

                <label>
                    ภาคเรียน
                </label>

                <select name="semester">

                    <option value="0">
                        -- ทุกภาคเรียน --
                    </option>

                    <option
                        value="1"
                        <?php
                        echo $semester == 1
                            ? "selected"
                            : "";
                        ?>>

                        ภาคเรียนที่ 1

                    </option>

                    <option
                        value="2"
                        <?php
                        echo $semester == 2
                            ? "selected"
                            : "";
                        ?>>

                        ภาคเรียนที่ 2

                    </option>

                </select>

            </div>


            <!-- ห้องเรียน -->

            <div>

                <label>
                    ห้องเรียน
                </label>

                <select name="classroom_id">

                    <option value="0">
                        -- ทุกห้องเรียน --
                    </option>

                    <?php

                    if ($classrooms) {

                        while (
                            $class =
                            $classrooms->fetch_assoc()
                        ) {

                    ?>

                        <option
                            value="<?php
                                echo $class["classroom_id"];
                            ?>"

                            <?php

                            if (
                                $classroom_id
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
                                . " | ปี "
                                . $class["academic_year"]
                                . " | เทอม "
                                . $class["semester"]
                            );

                            ?>

                        </option>

                    <?php

                        }

                    }

                    ?>

                </select>

            </div>


            <!-- นักเรียน -->

            <div>

                <label>
                    นักเรียน
                </label>

                <input
                    type="text"
                    name="search"

                    placeholder="รหัส / ชื่อ / นามสกุล"

                    value="<?php
                        echo htmlspecialchars($search);
                    ?>">

            </div>


            <br>


            <button type="submit">
                ค้นหารายงาน
            </button>

            <a href="reports.php">
                ล้างการค้นหา
            </a>

        </form>

    </div>


    <!-- ================================= -->
    <!-- ปุ่มพิมพ์ -->
    <!-- ================================= -->

    <div class="no-print print-button">

        <button
            type="button"
            onclick="window.print();">

            🖨 พิมพ์รายงาน

        </button>

    </div>


    <!-- ================================= -->
    <!-- สรุปจำนวน -->
    <!-- ================================= -->

    <h2>
        รายงานผลการเรียน
    </h2>

    <p>

        จำนวนนักเรียน:
        <strong>
            <?php
            echo count($students_report);
            ?>
        </strong>
        คน

    </p>


    <!-- ================================= -->
    <!-- รายงานนักเรียน -->
    <!-- ================================= -->

    <?php

    if (count($students_report) > 0) {

        foreach (
            $students_report
            as $student
        ) {

    ?>

        <div class="report-card">


            <!-- ================================= -->
            <!-- ข้อมูลนักเรียน -->
            <!-- ================================= -->

            <div class="report-header">

                <h3>

                    <?php

                    echo htmlspecialchars(
                        $student["student_code"]
                        . " - "
                        . $student["firstname"]
                        . " "
                        . $student["lastname"]
                    );

                    ?>

                </h3>

                <p>

                    ชั้น:

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $student["level"]
                            . "/"
                            . $student["room"]
                        );

                        ?>

                    </strong>

                    |

                    ปีการศึกษา:

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $student["academic_year"]
                        );
                        ?>

                    </strong>

                    |

                    ภาคเรียน:

                    <strong>

                        <?php
                        echo $student["semester"];
                        ?>

                    </strong>

                </p>

            </div>


            <!-- ================================= -->
            <!-- ตารางผลการเรียน -->
            <!-- ================================= -->

            <table
                border="1"
                cellpadding="8"
                cellspacing="0"
                width="100%">

                <thead>

                <tr>

                    <th>ลำดับ</th>

                    <th>รหัสวิชา</th>

                    <th>รายวิชา</th>

                    <th>หน่วยกิต</th>

                    <th>คะแนน</th>

                    <th>เกรด</th>

                    <th>สถานะ</th>

                </tr>

                </thead>


                <tbody>

                <?php

                $no = 1;

                foreach (
                    $student["subjects"]
                    as $subject
                ) {

                ?>

                    <tr>

                        <td>
                            <?php echo $no++; ?>
                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $subject["subject_code"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $subject["subject_name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $subject["credits"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $subject["score"];

                            ?>

                        </td>

                        <td>

                            <strong>

                                <?php

                                echo $subject["grade"];

                                ?>

                            </strong>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $subject["status"]
                            );

                            ?>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>


            <!-- ================================= -->
            <!-- GPA -->
            <!-- ================================= -->

            <p class="gpa">

                GPA:

                <?php

                echo number_format(
                    $student["gpa"],
                    2
                );

                ?>

            </p>


            <p>

                หน่วยกิตรวม:

                <?php

                echo number_format(
                    $student["total_credit"],
                    1
                );

                ?>

            </p>


        </div>

    <?php

        }

    } else {

    ?>

        <div class="error">

            ไม่พบข้อมูลผลการเรียน

        </div>

    <?php } ?>

</div>

</body>

</html>