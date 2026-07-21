<?php
// ============================================================
// EMPLOYEE ATTENDANCE (TIME IN / TIME OUT + HISTORY)
// ============================================================

require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();

$today = date('Y-m-d');

$message = '';
$error = '';

// ============================================================
// AUTO TIMEOUT FOR FORGOTTEN TIME OUT
// ============================================================
$stmt = $pdo->prepare(
    "SELECT id, time_in FROM attendance WHERE employee_id = ? AND date = ? AND time_out IS NULL AND time_in IS NOT NULL AND time_in <= ADDTIME(NOW(), '-10:00:00') LIMIT 1"
);
$stmt->execute([$user['id'], $today]);
$autoTimeout = $stmt->fetch(PDO::FETCH_ASSOC);

if ($autoTimeout) {
    $stmt = $pdo->prepare(
        "UPDATE attendance SET time_out = ADDTIME(time_in, '10:00:00'), hours_worked = 10.00 WHERE id = ?"
    );
    $stmt->execute([$autoTimeout['id']]);
    $message = "You were automatically timed out after 10 hours. 8 hours are regular work and 2 hours are overtime.";
}

// ============================================================
// GET TODAY ATTENDANCE
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ? AND date = ?
");

$stmt->execute([$user['id'], $today]);

$attendance = $stmt->fetch();


// ============================================================
// TIME IN
// ============================================================

if (isset($_POST['time_in'])) {

    if ($attendance) {
        if (!$attendance['time_in']) {
            $stmt = $pdo->prepare(
                "UPDATE attendance SET time_in = NOW(), status = 'present' WHERE id = ?"
            );
            $stmt->execute([$attendance['id']]);
            $message = "Time In successful!";
            header("Refresh:1");
            exit();
        }

        $error = "Already timed in today.";
    } else {

        $nextIdStmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM attendance");
        $nextId = $nextIdStmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO attendance (
                id,
                employee_id,
                date,
                time_in,
                status
            )
            VALUES (?, ?, ?, NOW(), 'present')
        ");

        $stmt->execute([$nextId, $user['id'], $today]);

        $message = "Time In successful!";
        header("Refresh:1");
        exit();
    }
}


// ============================================================
// REFRESH TODAY DATA
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ? AND date = ?
");

$stmt->execute([$user['id'], $today]);

$attendance = $stmt->fetch();


// ============================================================
// TIME OUT
// ============================================================

if (isset($_POST['time_out'])) {

    if (!$attendance) {
        $error = "You need to Time In first.";
    }
    elseif ($attendance['time_out']) {
        $error = "Already timed out.";
    }
    else {

        $stmt = $pdo->prepare("
            UPDATE attendance
            SET
                time_out = NOW(),
                hours_worked = TIMESTAMPDIFF(HOUR, time_in, NOW())
            WHERE id = ?
        ");

        $stmt->execute([$attendance['id']]);

        $message = "Time Out successful!";
        header("Refresh:1");
    }
}


// ============================================================
// GET ATTENDANCE HISTORY (LAST 30 DAYS)
// ============================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ?
    ORDER BY date DESC
    LIMIT 30
");

$stmt->execute([$user['id']]);

$attendance_history = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Payroll System</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <link rel="stylesheet" href="../view/attendanceEmployee.css">
</head>

<body class="dashboard">

<!-- SIDEBAR -->
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
            <a href="../controller/attendanceEmployee.php" class="nav-link active">
                <i>⏰</i> My Attendance
            </a>
        </li>

        <li class="nav-item">
            <a href="leave.php" class="nav-link">
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
        <h1>⏰ My Attendance</h1>
        <div><?= date('l, F d, Y'); ?></div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- TODAY ATTENDANCE -->
    <div class="card attendance-card">

        <div class="card-header">
            <h3 class="card-title">⏰ Today Attendance</h3>
        </div>

        <div class="attendance-info">

            <?php if (!$attendance): ?>

                <p><b>Status:</b> <span class="status-notin">Not Timed In</span></p>

            <?php else: ?>

                <p><b>Time In:</b> <?= date('h:i A', strtotime($attendance['time_in'])) ?></p>

                <?php if ($attendance['time_out']): ?>

                    <?php
                        $hours_worked = (float)$attendance['hours_worked'];
                        $regular_hours = min($hours_worked, 8);
                        $overtime_hours = max(0, $hours_worked - 8);
                    ?>

                    <p><b>Time Out:</b> <?= date('h:i A', strtotime($attendance['time_out'])) ?></p>
                    <p><b>Hours:</b> <?= number_format($hours_worked, 2) ?> hrs</p>
                    <p><b>Regular Hours:</b> <?= number_format($regular_hours, 2) ?> hrs</p>
                    <p><b>Overtime Hours:</b> <?= number_format($overtime_hours, 2) ?> hrs</p>

                <?php else: ?>

                    <p><b>Status:</b> <span class="status-working">Currently Working</span></p>

                <?php endif; ?>

            <?php endif; ?>

        </div>

        <div class="attendance-buttons">

            <!-- TIME IN -->
            <form method="POST">
                <?php if (!$attendance): ?>
                    <button class="btn btn-primary" name="time_in">🟢 Time In</button>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>🟢 Time In</button>
                <?php endif; ?>
            </form>

            <!-- TIME OUT -->
            <form method="POST">
                <?php if ($attendance && !$attendance['time_out']): ?>
                    <button class="btn btn-secondary" name="time_out">🔴 Time Out</button>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>🔴 Time Out</button>
                <?php endif; ?>
            </form>

        </div>

    </div>

    <!-- ATTENDANCE HISTORY -->
    <div class="card attendance-card">

        <div class="card-header">
            <h3 class="card-title">📅 My Attendance History</h3>
        </div>

        <?php if (empty($attendance_history)): ?>

            <p>No attendance records found.</p>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($attendance_history as $row): ?>

                            <tr>

                                <td><?= date('M d, Y', strtotime($row['date'])) ?></td>

                                <td><?= $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-' ?></td>

                                <td><?= $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-' ?></td>

                                <td><?= $row['hours_worked'] ?? 0 ?> hrs</td>

                                <td>
                                    <?php if ($row['time_out']): ?>
                                        <span class="status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="status-progress">Currently Working</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
