<?php

require_once "config.php";

// ล้างข้อมูล Session
$_SESSION = [];

// ทำลาย Session
session_destroy();

// กลับหน้า Login
header("Location: login.php");
exit();

?>