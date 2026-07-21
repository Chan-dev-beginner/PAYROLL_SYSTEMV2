<?php
// ============================================================
// DATABASE & SESSION
// ============================================================
require_once('../model/database.php');
require_once('../model/session.php');

requireHR(); // Only HR/Admin

$user = getCurrentUser();

$message = '';
$error = '';

// ============================================================
// FILTER MONTH
// ============================================================
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'all';

// ============================================================
// FETCH LEAVE REQUESTS
// ============================================================
if ($selected_month == 'all') {

    $stmt = $pdo->query("
        SELECT 
            lr.*,
            e.firstname,
            e.lastname,
            lt.leave_name,
            lt.is_paid
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        ORDER BY lr.start_date DESC
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT 
            lr.*,
            e.firstname,
            e.lastname,
            lt.leave_name,
            lt.is_paid
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        WHERE MONTH(lr.start_date) = ?
        ORDER BY lr.start_date DESC
    ");

    $stmt->execute([$selected_month]);
}

$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leaves</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <style>
        /* ============================================ */
        /* ADDITIONAL STYLES FOR LEAVE MANAGEMENT */
        /* ============================================ */
        
        /* Filter section styling */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
        }
        
        .filter-form label {
            font-weight: 600;
            color: #334155;
            margin: 0;
        }
        
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            min-width: 150px;
        }
        
        .filter-form select:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        /* Table container for horizontal scroll */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Leave requests table */
        .leave-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .leave-table th,
        .leave-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .leave-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }
        
        .leave-table tbody tr:hover {
            background-color: #faf9ff;
        }
        
        /* Column widths */
        .leave-table th:nth-child(1),
        .leave-table td:nth-child(1) {
            width: 60px;
            min-width: 60px;
        }
        
        .leave-table th:nth-child(2),
        .leave-table td:nth-child(2) {
            width: 150px;
            min-width: 150px;
        }
        
        .leave-table th:nth-child(3),
        .leave-table td:nth-child(3) {
            width: 130px;
            min-width: 130px;
        }
        
        .leave-table th:nth-child(4),
        .leave-table td:nth-child(4) {
            width: 110px;
            min-width: 110px;
        }
        
        .leave-table th:nth-child(5),
        .leave-table td:nth-child(5) {
            width: 110px;
            min-width: 110px;
        }
        
        .leave-table th:nth-child(6),
        .leave-table td:nth-child(6) {
            width: 90px;
            min-width: 90px;
            text-align: center;
        }
        
        .leave-table th:nth-child(7),
        .leave-table td:nth-child(7) {
            width: 200px;
            min-width: 200px;
        }
        
        .leave-table th:nth-child(8),
        .leave-table td:nth-child(8) {
            width: 100px;
            min-width: 100px;
        }
        
        .leave-table th:nth-child(9),
        .leave-table td:nth-child(9) {
            width: 100px;
            min-width: 100px;
        }
        
        .leave-table th:nth-child(10),
        .leave-table td:nth-child(10) {
            width: 160px;
            min-width: 160px;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .status-pending {
            background-color: #fef08a;
            color: #713f12;
        }
        
        .status-approved {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Paid/Unpaid badges */
        .paid-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .paid-badge.paid {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .paid-badge.unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Action buttons - FIXED HORIZONTAL LAYOUT */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: nowrap;
        }
        
        .btn-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-primary {
            background-color: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4f46e5;
        }
        
        .done-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #e2e8f0;
            color: #475569;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        /* Reason column text wrap */
        .leave-table td:nth-child(7) {
            word-break: break-word;
            white-space: normal;
            max-width: 200px;
        }
        
        /* Total days center alignment */
        .leave-table td:nth-child(6) {
            text-align: center;
            font-weight: 500;
        }
        
        /* No records message */
        .no-records {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .filter-form select {
                width: 100%;
            }
            
            .filter-form button {
                width: 100%;
            }
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
            <a href="payslip.php" class="nav-link">
                <i>💰</i> My Payslip
            </a>
        </li>

        <?php if ($user['is_hr'] || $user['is_admin']): ?>

        <li class="nav-item" style="margin-top:20px; padding:10px 20px; color:rgba(255,255,255,.5); font-size:12px; font-weight:bold;">
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
            <a href="manage_leaves.php" class="nav-link active">
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

        <li class="nav-item" style="margin-top:20px;">
            <a href="logout.php" class="nav-link">
                <i>🚪</i> Logout
            </a>
        </li>

    </ul>

    <div class="user-info">

        <strong>
            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
        </strong>

        <small><?php echo htmlspecialchars($user['email']); ?></small>

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
        <h1>✅ Leave Approval Management</h1>
    </div>

    <!-- FILTER SECTION - IMPROVED LAYOUT -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <label><strong>Filter By Month:</strong></label>

            <select name="month">
                <option value="all" <?= ($selected_month == 'all') ? 'selected' : ''; ?>>
                    Show All Leaves
                </option>

                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= ($selected_month == $i) ? 'selected' : ''; ?>>
                        <?= date('F', mktime(0,0,0,$i,1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <!-- LEAVE REQUESTS TABLE -->
    <div class="card">
        <div class="table-container">

            <?php if (empty($leave_requests)): ?>
                <div class="no-records">
                    No leave requests found.
                </div>
            <?php else: ?>

            <table class="leave-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Days</th>
                        <th>Reason</th>
                        <th>Paid / Unpaid</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($leave_requests as $leave): ?>

                    <tr>
                        <td><?= htmlspecialchars($leave['id']); ?></td>

                        <td>
                            <?= htmlspecialchars($leave['firstname'] . ' ' . $leave['lastname']); ?>
                        </td>

                        <td><?= htmlspecialchars($leave['leave_name']); ?></td>

                        <td><?= date('M d, Y', strtotime($leave['start_date'])); ?></td>
                        <td><?= date('M d, Y', strtotime($leave['end_date'])); ?></td>
                        <td><?= $leave['total_days']; ?></td>

                        <td><?= htmlspecialchars($leave['reason']); ?></td>

                        <!-- PAID / UNPAID BADGES -->
                        <td>
                            <?php if ($leave['is_paid'] == 1): ?>
                                <span class="paid-badge paid">Paid</span>
                            <?php else: ?>
                                <span class="paid-badge unpaid">Unpaid</span>
                            <?php endif; ?>
                        </td>

                        <!-- STATUS BADGES -->
                        <td>
                            <span class="status-badge status-<?= $leave['status']; ?>">
                                <?= ucfirst($leave['status']); ?>
                            </span>
                        </td>

                        <!-- ACTION BUTTONS - FIXED HORIZONTAL LAYOUT -->
                        <td>
                            <?php if($leave['status'] == 'pending'): ?>
                                <div class="action-buttons">
                                    <a href="approve_leave.php?id=<?= $leave['id']; ?>" 
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('Approve leave request for <?= htmlspecialchars($leave['firstname'] . ' ' . $leave['lastname']); ?>?')">
                                        Approve
                                    </a>
                                    <a href="reject_leave.php?id=<?= $leave['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Reject leave request for <?= htmlspecialchars($leave['firstname'] . ' ' . $leave['lastname']); ?>?')">
                                        Reject
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="done-badge">
                                    <?= ucfirst($leave['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

            <?php endif; ?>

        </div>
    </div>

</main>

</body>
</html>
