<?php
include 'db_connect.php';

$email = "Admin@booking";
$password = "Admin@123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$full_name = "System Admin";
$role = "admin";

// Check if admin exists
$check_sql = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$hashed_password', '$role')";
    if ($conn->query($sql) === TRUE) {
        echo "Admin user created successfully.";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    echo "Admin user already exists.";
}

$conn->close();
?>