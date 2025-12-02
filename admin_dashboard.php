<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch All Meetings with User Info
$sql = "SELECT bookings.*, users.full_name, users.email 
        FROM bookings 
        JOIN users ON bookings.user_id = users.id 
        ORDER BY bookings.date ASC, bookings.time ASC";
$result = $conn->query($sql);

// Fetch Status Counts for Analysis
$count_sql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
$count_result = $conn->query($count_sql);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($row = $count_result->fetch_assoc()) {
    $counts[$row['status']] = $row['count'];
}

// Fetch All Users for Dropdown
$users_sql = "SELECT id, full_name FROM users WHERE role != 'admin'";
$users_result = $conn->query($users_sql);

// Handle Monthly Activity Analysis
$monthly_data = [];
$selected_user_id = isset($_GET['analysis_user_id']) ? $_GET['analysis_user_id'] : '';

if ($selected_user_id) {
    $analysis_sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, status, COUNT(*) as count 
                     FROM bookings 
                     WHERE user_id = '$selected_user_id' 
                     AND status IN ('approved', 'rejected')
                     GROUP BY month, status
                     ORDER BY month ASC";
    $analysis_result = $conn->query($analysis_sql);

    while ($row = $analysis_result->fetch_assoc()) {
        $month = $row['month'];
        if (!isset($monthly_data[$month])) {
            $monthly_data[$month] = ['approved' => 0, 'rejected' => 0];
        }
        $monthly_data[$month][$row['status']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Meeting Booking System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Meeting Booking Admin</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">Welcome, Admin</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Analysis Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h4>Meeting Analysis</h4>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h4>User Monthly Activity</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="mb-3">
                            <div class="input-group">
                                <select class="form-select" name="analysis_user_id" required>
                                    <option value="" selected disabled>Select User</option>
                                    <?php
                                    if ($users_result->num_rows > 0) {
                                        while ($user = $users_result->fetch_assoc()) {
                                            $selected = ($selected_user_id == $user['id']) ? 'selected' : '';
                                            echo "<option value='" . $user['id'] . "' $selected>" . htmlspecialchars($user['full_name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <button class="btn btn-primary" type="submit">Analyze</button>
                            </div>
                        </form>
                        <?php if ($selected_user_id && !empty($monthly_data)): ?>
                            <div style="height: 240px;">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        <?php elseif ($selected_user_id): ?>
                            <p class="text-center text-muted mt-5">No approved or rejected meetings found for this user.</p>
                        <?php else: ?>
                            <p class="text-center text-muted mt-5">Select a user to view monthly activity.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Bookings Section -->
        <div class="card">
            <div class="card-header">
                <h4>Manage Bookings</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Title</th>
                                <th>Meeting Hall</th>
                                <th class="date-col">Date</th>
                                <th>Time</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($row['full_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['meeting_hall'] ?? 'N/A'); ?></td>
                                        <td><?php echo $row['date']; ?></td>
                                        <td><?php echo date('H:i', strtotime($row['time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo ($row['status'] == 'approved') ? 'bg-success' : (($row['status'] == 'rejected') ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="approve_meeting.php?id=<?php echo $row['id']; ?>&status=approved"
                                                    class="btn btn-success btn-sm rounded-pill action-btn <?php echo ($row['status'] == 'approved') ? 'disabled' : ''; ?>"
                                                    title="Approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                                <a href="approve_meeting.php?id=<?php echo $row['id']; ?>&status=rejected"
                                                    class="btn btn-danger btn-sm rounded-pill action-btn <?php echo ($row['status'] == 'rejected') ? 'disabled' : ''; ?>"
                                                    title="Reject">
                                                    <i class="fas fa-times"></i> Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status Chart
        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    label: 'Number of Meetings',
                    data: [<?php echo $counts['approved']; ?>, <?php echo $counts['pending']; ?>, <?php echo $counts['rejected']; ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)', // Green for Approved
                        'rgba(255, 193, 7, 0.7)', // Yellow for Pending
                        'rgba(220, 53, 69, 0.7)'  // Red for Rejected
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Meeting Status Overview'
                    }
                }
            }
        });

        // Monthly Activity Chart
        <?php if ($selected_user_id && !empty($monthly_data)):
            $months = array_keys($monthly_data);
            $approved_data = [];
            $rejected_data = [];
            foreach ($months as $m) {
                $approved_data[] = $monthly_data[$m]['approved'];
                $rejected_data[] = $monthly_data[$m]['rejected'];
            }
            ?>
            const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
            new Chart(ctxMonthly, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [
                        {
                            label: 'Approved',
                            data: <?php echo json_encode($approved_data); ?>,
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Rejected',
                            data: <?php echo json_encode($rejected_data); ?>,
                            borderColor: 'rgba(220, 53, 69, 1)',
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Approved vs Rejected Meetings'
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>