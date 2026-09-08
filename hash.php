<?php

echo "admin123 : ";
echo password_hash("admin123", PASSWORD_DEFAULT);

echo "<br>";

echo "teacher123 : ";
echo password_hash("teacher123", PASSWORD_DEFAULT);

echo "<br>";

echo "student123 : ";
echo password_hash("student123", PASSWORD_DEFAULT);

echo "<br>";

echo "parent123 : ";
echo password_hash("parent123", PASSWORD_DEFAULT);

?>