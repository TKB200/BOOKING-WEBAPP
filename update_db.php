<?php
include 'db_connect.php';

// Check if column exists
$check_col = "SHOW COLUMNS FROM bookings LIKE 'meeting_hall'";
$result = $conn->query($check_col);

if ($result->num_rows == 0) {
    // Add column if it doesn't exist
    $sql = "ALTER TABLE bookings ADD COLUMN meeting_hall VARCHAR(50) AFTER description";
    if ($conn->query($sql) === TRUE) {
        echo "Table updated successfully: Added meeting_hall column.";
    } else {
        echo "Error updating table: " . $conn->error;
    }
} else {
    echo "Column meeting_hall already exists.";
}

$conn->close();
?>