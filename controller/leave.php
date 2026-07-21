<?php
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/session.php';

requireLogin();

$user = getCurrentUser();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id   = $user['id'];
    $leave_type_id = intval($_POST['leave_type_id'] ?? 0);
    $start_date    = $_POST['start_date'] ?? '';
    $end_date      = $_POST['end_date'] ?? '';
    $reason        = trim($_POST['reason'] ?? '');

    // CALCULATE TOTAL DAYS
    $start = new DateTime($start_date);
    $end   = new DateTime($end_date);

    $interval = $start->diff($end);
    $total_days = $interval->days + 1;

    // VALIDATION
    if (
        empty($leave_type_id) ||
        empty($start_date) ||
        empty($end_date) ||
        empty($reason)
    ) {

        $error = "Please fill all required fields.";

    } elseif ($end_date < $start_date) {

        $error = "End date cannot be earlier than start date.";

    } else {

        // INSERT LEAVE REQUEST USING PDO
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests
            (
                employee_id,
                leave_type_id,
                start_date,
                end_date,
                total_days,
                reason,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, 'pending')
        ");

        $result = $stmt->execute([
            $employee_id,
            $leave_type_id,
            $start_date,
            $end_date,
            $total_days,
            $reason
        ]);

        if ($result) {

            $success = "Leave request submitted successfully!";

        } else {

            $error = "Database error.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Request Leave</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <link rel="stylesheet" href="../view/leave.css">
    <style>
        /* Enhanced submit button styles */
        .btn-submit {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #45a049 0%, #3d8b40 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-submit:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.3);
        }
    </style>
</head>

<body class="dashboard">

<!-- ============================================================ -->
<!-- SIDEBAR (UNCHANGED - FULL DASHBOARD FEATURES) -->
<!-- ============================================================ -->
<aside class="sidebar">

    <div class="sidebar-header">
        <h2>💼 Payroll System</h2>
        <p>HR Management</p>
    </div>

    <ul class="nav-menu">

        <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
                <i>🏠</i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a href="../controller/attendanceEmployee.php" class="nav-link">
                <i>⏰</i> My Attendance
            </a>
        </li>

        <li class="nav-item">
            <a href="leave.php" class="nav-link active">
                <i>🏖️</i> My Request Leave
            </a>
        </li>

        <li class="nav-item">
            <a href="payslip.php" class="nav-link">
                <i>💰</i> My Payslip
            </a>
        </li>

        <?php if ($user['is_hr'] || $user['is_admin']): ?>

            <li class="nav-item" style="margin-top: 20px; padding: 10px 20px; color: rgba(255,255,255,0.5); font-size: 12px; font-weight: bold;">
                HR MANAGEMENT
            </li>

            <li class="nav-item">
                <a href="employee.php" class="nav-link">
                    <i>👥</i> Employees</a>
            </li>
            <li class="nav-item">
                <a href="attendance_hr.php" class="nav-link">
                    <i>📋</i> Attendance</a>
            </li>
            <li class="nav-item">
                <a href="manage_leaves.php" class="nav-link">
                    <i>✅</i> Leave Approval</a>
            </li>
            <li class="nav-item">
                <a href="manage_incentives.php" class="nav-link">
                    <i>🎁</i> Incentives</a>
            </li>
            <li class="nav-item">
                <a href="payroll.php" class="nav-link">
                    <i>📊</i> Payroll</a>
            </li>

        <?php endif; ?>

        <li class="nav-item" style="margin-top: 20px;">
            <a href="logout.php" class="nav-link">
                <i>🚪</i> Logout</a>
        </li>

    </ul>

    <div class="user-info">
        <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong>
        <small><?= htmlspecialchars($user['email']); ?></small>

        <?php if ($user['is_admin']): ?>
            <span class="badge badge-admin">ADMIN</span>
        <?php elseif ($user['is_hr']): ?>
            <span class="badge badge-hr">HR</span>
        <?php endif; ?>
    </div>

</aside>




<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="topbar">
        <h2>🏖️ Leave Request Form</h2>
        <div><?= date('l, F d, Y'); ?></div>
    </div>

    <div class="card attendance-card">
        
        <?php if($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST">

        <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong> <hr>

        <label>Leave Type</label>
        <select name="leave_type_id" required>
            <option value="">Select Leave Type</option>
            <option value="1">Vacation Leave</option>
            <option value="2">Sick Leave</option>
            <option value="3">Emergency Leave</option>
            <option value="4">Unpaid Leave</option>
            <option value="5">Maternity Leave</option>
        </select>

        <label>Start Date</label>
        <input type="date" name="start_date" required>

        <label>End Date</label>
        <input type="date" name="end_date" required>

        <label>Reason</label>
        <textarea name="reason" required></textarea>

        <button type="submit" class="btn-submit">📨 Submit Leave Request</button>

        </form>

    </div>
</main>


</body>
</html>
