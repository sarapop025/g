<?php
// config.php

session_start();

// ตั้งค่าฐานข้อมูล
$host = "localhost";
$user = "root";
$pass = "";
$db   = "school_grade";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error);
}

// กำหนดภาษาไทย
$conn->set_charset("utf8mb4");
?>