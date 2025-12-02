<?php
include 'db_connect.php';

// Add end_time column if it doesn't exist
$check_col = "SHOW COLUMNS FROM bookings LIKE 'end_time'";
$result = $conn->query($check_col);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE bookings ADD COLUMN end_time TIME NOT NULL AFTER time";
    if ($conn->query($sql) === TRUE) {
        echo "Table 'bookings' updated successfully: 'end_time' column added.<br>";
    } else {
        echo "Error updating table: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'end_time' already exists.<br>";
}

// Update existing records to have an end_time (e.g., start time + 1 hour) if end_time is 00:00:00
$update_sql = "UPDATE bookings SET end_time = ADDTIME(time, '01:00:00') WHERE end_time = '00:00:00'";
if ($conn->query($update_sql) === TRUE) {
    echo "Existing records updated with default end time (1 hour duration).";
} else {
    echo "Error updating existing records: " . $conn->error;
}

$conn->close();
?>