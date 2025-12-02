<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Booking Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $date = $_POST['date'];
    $time = $_POST['time']; // Start Time
    $end_time = $_POST['end_time']; // End Time
    $description = $_POST['description'];
    $meeting_hall = $_POST['meeting_hall'];

    // Basic validation
    if (empty($title) || empty($date) || empty($time) || empty($end_time) || empty($meeting_hall)) {
        $error = "Please fill in all required fields.";
    } elseif ($end_time <= $time) {
        $error = "End time must be after the start time.";
    } else {
        // Check for conflicts
        // Overlap logic: (StartA < EndB) AND (EndA > StartB)
        $check_sql = "SELECT b.time, b.end_time, u.full_name 
                      FROM bookings b 
                      JOIN users u ON b.user_id = u.id 
                      WHERE b.meeting_hall = '$meeting_hall' 
                      AND b.date = '$date' 
                      AND b.status != 'rejected'
                      AND ( (b.time < '$end_time') AND (b.end_time > '$time') )";

        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $existing_booking = $check_result->fetch_assoc();
            $booked_start = date('H:i', strtotime($existing_booking['time']));
            $booked_end = date('H:i', strtotime($existing_booking['end_time']));
            $booked_by = $existing_booking['full_name'];
            $error = "This Board Room is already booked from $booked_start to $booked_end by $booked_by.";
        } else {
            $sql = "INSERT INTO bookings (user_id, title, date, time, end_time, description, meeting_hall) VALUES ('$user_id', '$title', '$date', '$time', '$end_time', '$description', '$meeting_hall')";
            if ($conn->query($sql) === TRUE) {
                $message = "Meeting booked successfully! Waiting for admin approval.";

                // Send Email to User
                $user_sql = "SELECT email FROM users WHERE id = '$user_id'";
                $user_result = $conn->query($user_sql);
                if ($user_result->num_rows > 0) {
                    $user_row = $user_result->fetch_assoc();
                    $to = $user_row['email'];
                    $subject = "Meeting Booking Confirmation";
                    $email_message = "Dear User,\n\nYour meeting request has been received.\n\nDetails:\nTitle: $title\nHall: $meeting_hall\nDate: $date\nTime: $time - $end_time\nDescription: $description\n\nStatus: Pending Approval\n\nThank you.";
                    $headers = "From: no-reply@meetingbooking.com";

                    // Use @ to suppress warnings if mail server is not configured
                    @mail($to, $subject, $email_message, $headers);
                }
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Fetch User's Meetings
$sql = "SELECT * FROM bookings WHERE user_id = '$user_id' ORDER BY date ASC, time ASC";
$result = $conn->query($sql);

// Fetch All Booked Dates for Calendar
$calendar_sql = "SELECT b.date, b.time, b.end_time, b.status, b.meeting_hall, u.full_name 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.status != 'rejected'";
$calendar_result = $conn->query($calendar_sql);
$booked_dates = [];
while ($row = $calendar_result->fetch_assoc()) {
    $booked_dates[] = [
        'date' => $row['date'],
        'time' => $row['time'],
        'end_time' => $row['end_time'],
        'status' => $row['status'],
        'hall' => $row['meeting_hall'],
        'user' => $row['full_name']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Meeting Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Adjust Calendar Size */
        #calendar {
            max-width: 900px;
            margin: 0 auto;
            height: 500px;
            /* Reduced height */
        }

        .fc-toolbar-title {
            font-size: 1.2rem !important;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Meeting Booking</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Calendar Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Meeting Calendar</h4>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Book a Meeting</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">Meeting Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="time" name="time" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="meeting_hall" class="form-label">Meeting Hall</label>
                                <select class="form-select" id="meeting_hall" name="meeting_hall" required>
                                    <option value="" selected disabled>Select a Hall</option>
                                    <option value="FCL MiniBoard Room">FCL MiniBoard Room</option>
                                    <option value="FCL Main Board Room">FCL Main Board Room</option>
                                    <option value="FCML Board Room">FCML Board Room</option>
                                    <option value="FDL Board Room">FDL Board Room</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Book Meeting</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>My Meetings</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Hall</th>
                                    <th class="date-col">Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo htmlspecialchars($row['meeting_hall'] ?? 'N/A'); ?></td>
                                            <td><?php echo $row['date']; ?></td>
                                            <td>
                                                <?php
                                                echo date('H:i', strtotime($row['time'])) . ' - ' . date('H:i', strtotime($row['end_time']));
                                                ?>
                                            </td>
                                            <td>
                                                <span class="<?php echo 'status-' . $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No meetings booked yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Meeting Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Booked By:</strong> <span id="modalUser"></span></p>
                    <p><strong>Hall:</strong> <span id="modalHall"></span></p>
                    <p><strong>Time:</strong> <span id="modalTime"></span></p>
                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
         document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 500, // Fixed height for smaller calendar
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php
                    foreach ($booked_dates as $booking) {
                        $color = ($booking['status'] == 'approved') ? '#28a745' : '#ffc107';
                        // Combine date and time for FullCalendar
                        $start = $booking['date'] . 'T' . $booking['time'];
                        $end = $booking['date'] . 'T' . $booking['end_time'];
                        $display_time = date('H:i', strtotime($booking['time'])) . '-' . date('H:i', strtotime($booking['end_time']));
                        $display_title = $display_time . ' ' . $booking['hall'];

                        echo "{ 
                            title: '" . addslashes($display_title) . "', 
                            start: '" . $start . "', 
                            end: '" . $end . "',
                            color: '$color',
                            extendedProps: {
                                user: '" . addslashes($booking['user']) . "',
                                hall: '" . addslashes($booking['hall']) . "',
                                time: '" . $display_time . "',
                                status: '" . ucfirst($booking['status']) . "'
                            }
                        },";
                    }
                    ?>
                ],
                eventClick: function(info) {
                    // Populate modal with event details
                    document.getElementById('modalUser').textContent = info.event.extendedProps.user;
                    document.getElementById('modalHall').textContent = info.event.extendedProps.hall;
                    document.getElementById('modalTime').textContent = info.event.extendedProps.time;
                    document.getElementById('modalStatus').textContent = info.event.extendedProps.status;
                    
                    // Show the modal
                    bookingModal.show();
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>