<?php
// ============================================================
// DASHBOARD - Main Page (UPDATED ROLE STRUCTURE)
// ============================================================

require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();

// ============================================================
// STATS (HR / ADMIN ONLY)
// ============================================================

$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
$total_employees = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
$pending_leaves = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM incentive_types");
$incentive_types_count = $stmt->fetchColumn();

// ============================================================
// MY PAYSLIP (ALL USERS)
// ============================================================

$stmt = $pdo->prepare("
    SELECT * FROM payroll 
    WHERE employee_id = ? 
    ORDER BY month_year DESC 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$my_payslip = $stmt->fetch();

// ============================================================
// MY LEAVE REQUESTS
// ============================================================

$stmt = $pdo->prepare("
    SELECT lr.*, lt.leave_name, lt.is_paid
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.employee_id = ?
    ORDER BY lr.created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$my_leaves = $stmt->fetchAll();

// ============================================================
// RECENT LEAVES (HR / ADMIN)
// ============================================================

$stmt = $pdo->query("
    SELECT lr.*, e.firstname, e.lastname, lt.leave_name, lt.is_paid
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$recent_leaves = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Payroll System</title>

    <link rel="stylesheet" href="../view/dashboard.css">
    
    <style>
        /* Status badge styles */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Color Legend Styles */
        .legend-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .legend-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .legend-items {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 20px;
        }
        
        .legend-color.pending {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
        }
        
        .legend-color.approved {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .legend-color.rejected {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
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
            <a href="dashboard.php" class="nav-link active">
                <i>🏠</i> Dashboard
            </a>
        </li>

        <!-- ALL USERS FEATURES -->
        <li class="nav-item">
            <a href="../controller/attendanceEmployee.php" class="nav-link">
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

        <!-- HR / ADMIN ONLY -->
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
        <h1>🏠 Dashboard</h1>
        <div><?= date('l, F d, Y'); ?></div>
    </div>

    <!-- COLOR LEGEND -->
    <div class="legend-container">
        <div class="legend-title">📊 Status Color Legend</div>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-color pending"></div>
                <span>Pending</span>
            </div>
            <div class="legend-item">
                <div class="legend-color approved"></div>
                <span>Approved</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rejected"></div>
                <span>Rejected</span>
            </div>
        </div>
    </div>

    <!-- STATS (HR ONLY) -->
    <?php if ($user['is_hr'] || $user['is_admin']): ?>
    <div class="stats-grid">

        <div class="stat-card blue">
            <h3>Total Employees</h3>
            <div class="stat-value"><?= $total_employees ?></div>
        </div>

        <div class="stat-card orange">
            <h3>Pending Leaves</h3>
            <div class="stat-value"><?= $pending_leaves ?></div>
        </div>

        <div class="stat-card green">
            <h3>Incentives</h3>
            <div class="stat-value"><?= $incentive_types_count ?></div>
        </div>

    </div>
    <?php endif; ?>

    <!-- MY PAYSLIP -->
    <?php if ($my_payslip): ?>
    <div class="card">

        <h3>💰 My Latest Payslip</h3>

        <p><b>Net Pay:</b> ₱<?= number_format($my_payslip['net_pay'], 2) ?></p>
        <p><b>Days Worked:</b> <?= $my_payslip['days_worked'] ?></p>

    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

        <!-- MY LEAVES -->
        <div class="card">

            <div class="card-header">
                <h3>🏖️ My Leave Requests</h3>
            </div>

            <?php if (empty($my_leaves)): ?>
                <p>No leave requests yet.</p>
            <?php else: ?>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Days</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_leaves as $leave): ?>
                        <tr>
                            <td><?= htmlspecialchars($leave['leave_name']) ?></td>
                            <td><?= $leave['total_days'] ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch($leave['status']) {
                                    case 'pending':
                                        $status_class = 'status-pending';
                                        break;
                                    case 'approved':
                                        $status_class = 'status-approved';
                                        break;
                                    case 'rejected':
                                        $status_class = 'status-rejected';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= ucfirst($leave['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>

        <!-- HR LEAVES -->
        <?php if ($user['is_hr'] || $user['is_admin']): ?>
        <div class="card">

            <div class="card-header">
                <h3>📋 Recent Leave Requests</h3>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_leaves as $leave): ?>
                    <tr>
                        <td><?= htmlspecialchars($leave['firstname'] . ' ' . $leave['lastname']) ?></td>
                        <td><?= htmlspecialchars($leave['leave_name']) ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($leave['status']) {
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'approved':
                                    $status_class = 'status-approved';
                                    break;
                                case 'rejected':
                                    $status_class = 'status-rejected';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <?= ucfirst($leave['status']) ?>
                            </span>
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
