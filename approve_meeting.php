<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Validate status
    if ($status == 'approved' || $status == 'rejected') {
        $sql = "UPDATE bookings SET status = '$status' WHERE id = '$id'";
        if ($conn->query($sql) === TRUE) {
            header("Location: admin_dashboard.php?msg=Status updated successfully");
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
} else {
    header("Location: admin_dashboard.php");
}
?>