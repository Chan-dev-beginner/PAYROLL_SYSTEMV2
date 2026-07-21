<?php
require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();
$employee_id = $user['id'];

/* ============================================================
   GET LATEST PAYROLL RECORD
============================================================ */
$stmt = $pdo->prepare("
    SELECT p.*, 
           CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
           r.role_name,
           e.department_id
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN roles r ON e.role_id = r.id
    WHERE p.employee_id = ?
    ORDER BY p.month_year DESC
    LIMIT 1
");

$stmt->execute([$employee_id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

/* ============================================================
   ATTENDANCE SUMMARY
============================================================ */
$stmt = $pdo->prepare("
    SELECT
        COUNT(CASE WHEN status = 'present' THEN 1 END) AS days_worked,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) AS days_absent
    FROM attendance
    WHERE employee_id = ?
");

$stmt->execute([$employee_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payslip</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <link rel="stylesheet" href="../view/payslip.css">
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
                <a href="attendanceEmployee.php" class="nav-link">
                    <i>⏰</i> My Attendance
                </a>
        </li>

        <li class="nav-item">
            <a href="leave.php" class="nav-link">
                <i>🏖️</i> My Request Leave
            </a>
        </li>
        <li class="nav-item">
            <a href="payslip.php" class="nav-link active">
                <i>💰</i> My Payslip
            </a>
        </li>
        
        <?php if ($user['is_hr'] || $user['is_admin']): ?>
        <li class="nav-item" style="margin-top: 20px; padding: 10px 20px; color: rgba(255,255,255,0.5); font-size: 12px; font-weight: bold;">
            HR MANAGEMENT
        </li>
        <li class="nav-item">
            <a href="employee.php" class="nav-link">
                <i>👥</i> Employees
            </a>
        </li>
        <li class="nav-item">
            <a href="attendance_hr.php" class="nav-link">
                <i>📋</i> Attendance
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_leaves.php" class="nav-link">
                <i>✅</i> Leave Approval
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_incentives.php" class="nav-link">
                <i>🎁</i> Incentives
            </a>
        </li>
        <li class="nav-item">
            <a href="payroll.php" class="nav-link">
                <i>📊</i> Payroll
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item" style="margin-top: 20px;">
            <a href="logout.php" class="nav-link">
                <i>🚪</i> Logout
            </a>
        </li>
    </ul>
    
    <div class="user-info">
        <strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong>
        <small><?php echo htmlspecialchars($user['email']); ?></small>
        <?php if ($user['is_admin']): ?>
            <span class="badge badge-admin">ADMIN</span>
        <?php elseif ($user['is_hr']): ?>
            <span class="badge badge-hr">HR</span>
        <?php endif; ?>
    </div>
</aside>

<main class="main-content">

<div class="topbar">
    <h2>My Payslip</h2>
    <div><?= date('F d, Y'); ?></div>
</div>

<div class="card">

<?php if (!$payroll): ?>
    <p style="color:red;">No payroll record found for this month.</p>
<?php else: ?>


    <h2 style="text-align:center;">PAYSLIP</h2>
    <p style="text-align:center;">Company ni Chan</p>

    <hr>

    <!-- EMPLOYEE INFO -->
    <p><b>Employee Name:</b> <?= htmlspecialchars($payroll['employee_name']); ?></p>
    <p><b>Role:</b> <?= htmlspecialchars($payroll['role_name']); ?></p>
    <p><b>Month:</b> <?= date('F Y', strtotime($payroll['month_year'])); ?></p>

    <hr>

    <!-- ATTENDANCE -->
    <h3>Attendance</h3>
    <p><b>Days Worked:</b> <?= $attendance['days_worked'] ?? 0; ?></p>
    <p><b>Days Absent:</b> <?= $attendance['days_absent'] ?? 0; ?></p>
    <p><b>Paid Leaves:</b> <?= $payroll['paid_leaves'] ?? 0; ?></p>
    <p><b>Unpaid Leaves:</b> <?= $payroll['unpaid_leaves'] ?? 0; ?></p>

    <hr>

    <!-- COMPUTATION -->
    <h3>Computation</h3>

    <p><b>Basic Salary:</b> ₱<?= number_format($payroll['basic_salary'] ?? 0, 2); ?></p>
    <p><b>Incentives:</b> ₱<?= number_format($payroll['total_incentives'] ?? 0, 2); ?></p>
    <p><b>Gross Pay:</b> ₱<?= number_format($payroll['gross_pay'] ?? 0, 2); ?></p>
    <p><b>Deductions:</b> ₱<?= number_format($payroll['total_deductions'] ?? 0, 2); ?></p>
    <p><b style="color:green;">Net Pay:</b> ₱<?= number_format($payroll['net_pay'] ?? 0, 2); ?></p>


    <br><br>
<?php endif; ?>

<div class="button-row">

    <?php if ($payroll): ?>
        <a href="export_payslip_xml.php" class="btn btn-primary">
            📄 Export XML
        </a>
    <?php endif; ?>

</div>

</div>

</main>
</body>
</html>
