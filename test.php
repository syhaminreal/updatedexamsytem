<?php
$conn = mysqli_connect("localhost", "root", "", "updatedexamsystem");

if (!$conn) {
    die("Connection failed");
}

echo "Database connected successfully ✅";
?>