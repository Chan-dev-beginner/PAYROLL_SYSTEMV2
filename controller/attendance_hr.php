<?php
// ============================================================
// ATTENDANCE PAGE (HR CAN SEE ALL + EMPLOYEE OWN VIEW)
// ============================================================

require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();

// ============================================================
// FILTER
// ============================================================

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$all_months = isset($_GET['all_months']);

// ============================================================
// BUILD QUERY (IMPORTANT FIX HERE)
// ============================================================

$params = [];

$sql = "
    SELECT attendance.*, employees.firstname, employees.lastname
    FROM attendance
    JOIN employees ON attendance.employee_id = employees.id
    WHERE YEAR(attendance.date) = ?
";

$params[] = $year;

// if NOT HR/Admin → only show own records
if (!$user['is_hr'] && !$user['is_admin']) {
    $sql .= " AND attendance.employee_id = ? ";
    $params[] = $user['id'];
}

// month filter (only if NOT all months)
if (!$all_months) {
    $sql .= " AND MONTH(attendance.date) = ? ";
    $params[] = $month;
}

$sql .= " ORDER BY attendance.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$attendance_records = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <link rel="stylesheet" href="../view/attendance_hr.css">
    <style>
        /* ============================================ */
        /* FIXED FILTER SECTION PADDING & STATUS BADGES */
        /* ============================================ */
        
        /* Filter card styles */
        .filter-card {
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
            gap: 20px;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-checkbox label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }
        
        .filter-checkbox input {
            width: auto;
            margin: 0;
            cursor: pointer;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            margin: 0;
            font-weight: 500;
            color: #334155;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .btn-filter {
            background-color: #10b981;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-filter:hover {
            background-color: #059669;
        }
        
        /* Table styles */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .attendance-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #475569;
        }
        
        .attendance-table tbody tr:hover {
            background-color: #faf9ff;
        }
        
        /* Status badges - FIXED */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        
        .status-present {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-late {
            background-color: #fed7aa;
            color: #9a3412;
        }
        
        .status-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-halfday {
            background-color: #fef08a;
            color: #713f12;
        }
        
        /* For any other status */
        .status-default {
            background-color: #e2e8f0;
            color: #475569;
        }
        
        /* Hours column */
        .hours-cell {
            font-weight: 500;
            font-family: monospace;
        }
        
        /* No records message */
        .no-records {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .filter-group {
                flex-wrap: wrap;
            }
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
                    <i>👥</i> Employees
                </a>
            </li>
            <li class="nav-item">
                <a href="attendance_hr.php" class="nav-link active">
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

<!-- ============================================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================================ -->
<main class="main-content">

    <div class="topbar">
        <h1>📋 Attendance Records</h1>
        <div><?= date('l, F d, Y'); ?></div>
    </div>

    <!-- FILTER SECTION - IMPROVED PADDING AND LAYOUT -->
    <div class="filter-card">
        <form method="GET" class="filter-form">

            <?php if ($user['is_hr'] || $user['is_admin']): ?>
                <div class="filter-checkbox">
                    <input type="checkbox" name="all_months" id="all_months" <?= $all_months ? 'checked' : '' ?>>
                    <label for="all_months">Show All Months (This Year)</label>
                </div>
            <?php endif; ?>

            <div class="filter-group">
                <label>Month:</label>
                <select name="month">
                    <?php for ($m=1; $m<=12; $m++): ?>
                        <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>"
                            <?= $month == str_pad($m,2,'0',STR_PAD_LEFT) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Year:</label>
                <select name="year">
                    <option value="2024" <?= $year==2024?'selected':'' ?>>2024</option>
                    <option value="2025" <?= $year==2025?'selected':'' ?>>2025</option>
                    <option value="2026" <?= $year==2026?'selected':'' ?>>2026</option>
                </select>
            </div>

            <button type="submit" class="btn-filter">Filter</button>

        </form>
    </div>

    <!-- TABLE SECTION -->
    <div class="card">

        <?php if (empty($attendance_records)): ?>
            <div class="no-records">
                No attendance records found.
            </div>
        <?php else: ?>

        <table class="attendance-table">

            <thead>
                <tr>
                    <th>DATE</th>
                    <?php if ($user['is_hr'] || $user['is_admin']): ?>
                        <th>EMPLOYEE</th>
                    <?php endif; ?>
                    <th>TIME IN</th>
                    <th>TIME OUT</th>
                    <th>HOURS</th>
                    <th>STATUS</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($attendance_records as $row): ?>

                <tr>
                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>

                    <?php if ($user['is_hr'] || $user['is_admin']): ?>
                        <td><?= htmlspecialchars($row['firstname'].' '.$row['lastname']) ?></td>
                    <?php endif; ?>

                    <td><?= $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-' ?></td>
                    <td><?= $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-' ?></td>
                    <td class="hours-cell"><?= number_format($row['hours_worked'] ?? 0, 2) ?> hrs</td>

                    <td>
                        <?php 
                        $status = strtolower($row['status'] ?? '');
                        switch($status):
                            case 'present': ?>
                                <span class="status-badge status-present">Present</span>
                                <?php break;
                            case 'late': ?>
                                <span class="status-badge status-late">Late</span>
                                <?php break;
                            case 'absent': ?>
                                <span class="status-badge status-absent">Absent</span>
                                <?php break;
                            case 'halfday': ?>
                                <span class="status-badge status-halfday">Half Day</span>
                                <?php break;
                            default: ?>
                                <span class="status-badge status-default"><?= ucfirst($status ?: 'Unknown') ?></span>
                        <?php endswitch; ?>
                    </td>
                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
