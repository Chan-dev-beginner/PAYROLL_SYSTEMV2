<?php
// ============================================================
// PAYROLL MANAGEMENT - Calculate Employee Salaries
// ============================================================

require_once('../model/database.php');
require_once('../model/session.php');
require_once('../model/email.php');

requireLogin();

$user = getCurrentUser();

if (!$user['is_hr'] && !$user['is_admin']) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$error = '';

// ============================================================
// GENERATE PAYROLL FOR A MONTH
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_payroll'])) {
    
    $month_year = $_POST['month_year'] . '-01'; // Format: YYYY-MM-01
    $employee_id = (int)$_POST['employee_id'];
    
    try {
        // Get employee details with role salary
        $stmt = $pdo->prepare("
            SELECT e.*, r.monthly_salary, r.hourly_rate, r.role_name
            FROM employees e
            JOIN roles r ON e.role_id = r.id
            WHERE e.id = ? AND e.status = 'active'
        ");
        $stmt->execute([$employee_id]);
        $employee = $stmt->fetch();
        
        if (!$employee) {
            throw new Exception("Employee not found or inactive");
        }
        
        // Get month start and end dates
        $start_date = date('Y-m-01', strtotime($month_year));
        $end_date = date('Y-m-t', strtotime($month_year));
        
        // 1. COUNT ATTENDANCE DAYS
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as days_present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as days_absent,
                COALESCE(SUM(hours_worked), 0) as total_hours
            FROM attendance 
            WHERE employee_id = ? 
            AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $attendance = $stmt->fetch();
        
        // 2. COUNT APPROVED LEAVES
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN lt.is_paid = 1 THEN lr.total_days ELSE 0 END), 0) as paid_leaves,
                COALESCE(SUM(CASE WHEN lt.is_paid = 0 THEN lr.total_days ELSE 0 END), 0) as unpaid_leaves
            FROM leave_requests lr
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.employee_id = ?
            AND lr.status = 'approved'
            AND lr.start_date >= ? AND lr.end_date <= ?
        ");
        $stmt->execute([$employee_id, $start_date, $end_date]);
        $leaves = $stmt->fetch();
        
        // 3. GET INCENTIVES FOR THIS EMPLOYEE
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_incentives 
            FROM employee_incentives
            WHERE employee_id = ?
            AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')
        ");
        $stmt->execute([$employee_id, $month_year]);
        $incentives = $stmt->fetch();
        
        // 4. CALCULATE PAYROLL
        $days_worked = (int)$attendance['days_present'];
        $days_absent = (int)$attendance['days_absent'];
        $paid_leaves = (int)$leaves['paid_leaves'];
        $unpaid_leaves = (int)$leaves['unpaid_leaves'];
        $total_hours = (float)$attendance['total_hours'];
        
        // Working days in month (22 working days standard)
        $working_days = 22;
        $daily_rate = $employee['monthly_salary'] / $working_days;
        
        // Basic salary = (days worked + paid leaves) × daily rate
        $basic_salary = ($days_worked + $paid_leaves) * $daily_rate;
        
        // Calculate overtime (if hours exceed 8 * days_worked)
        $standard_hours = $days_worked * 8;
        $overtime_hours = max(0, $total_hours - $standard_hours);
        $overtime_pay = $overtime_hours * ($employee['hourly_rate'] * 1.25); // 25% overtime premium
        
        // Total incentives
        $total_incentives = (float)$incentives['total_incentives'];
        
        // Gross pay
        $gross_pay = $basic_salary + $overtime_pay + $total_incentives;
        
        // Deductions (Simplified: 15% total - SSS, PhilHealth, Pag-IBIG, Tax)
        $total_deductions = $gross_pay * 0.15;
        
        // Net pay
        $net_pay = $gross_pay - $total_deductions;
        
        // 5. SAVE OR UPDATE PAYROLL RECORD
        $stmt = $pdo->prepare("
            INSERT INTO payroll 
            (employee_id, month_year, basic_salary, total_incentives, total_deductions, 
             days_worked, days_absent, paid_leaves, unpaid_leaves, total_hours, 
             overtime_hours, gross_pay, net_pay, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ON DUPLICATE KEY UPDATE
                basic_salary = VALUES(basic_salary),
                total_incentives = VALUES(total_incentives),
                total_deductions = VALUES(total_deductions),
                days_worked = VALUES(days_worked),
                days_absent = VALUES(days_absent),
                paid_leaves = VALUES(paid_leaves),
                unpaid_leaves = VALUES(unpaid_leaves),
                total_hours = VALUES(total_hours),
                overtime_hours = VALUES(overtime_hours),
                gross_pay = VALUES(gross_pay),
                net_pay = VALUES(net_pay),
                status = 'draft'
        ");
        
        $stmt->execute([
            $employee_id,
            $month_year,
            $basic_salary,
            $total_incentives,
            $total_deductions,
            $days_worked,
            $days_absent,
            $paid_leaves,
            $unpaid_leaves,
            $total_hours,
            $overtime_hours,
            $gross_pay,
            $net_pay
        ]);
        
        $message = "Payroll generated successfully for " . $employee['firstname'] . " " . $employee['lastname'];
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ============================================================
// FINALIZE PAYROLL (Mark as paid)
// ============================================================
if (isset($_GET['finalize'])) {
    try {
        $stmt = $pdo->prepare("UPDATE payroll SET status = 'finalized' WHERE id = ?");
        $stmt->execute([$_GET['finalize']]);

        $payrollId = (int)$_GET['finalize'];
        $stmt = $pdo->prepare("SELECT p.*, e.email, CONCAT(e.firstname, ' ', e.lastname) as employee_name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ?");
        $stmt->execute([$payrollId]);
        $payroll = $stmt->fetch();

        if ($payroll && !empty($payroll['email'])) {
            $payload = [
                'to_email' => $payroll['email'],
                'subject' => 'PAYCHECK',
                'message' => '<p>Hi ' . htmlspecialchars($payroll['employee_name']) . ',</p><p>Your payroll for <strong>' . htmlspecialchars($payroll['month_year']) . '</strong> has been finalized.</p><p>Net pay: ' . number_format((float)$payroll['net_pay'], 2) . '</p>'
            ];

            $senderAccount = null;
            $stmt2 = $pdo->prepare("SELECT * FROM employee_smtp_accounts WHERE employee_id = ? AND is_default = 1 LIMIT 1");
            $stmt2->execute([$payroll['employee_id']]);
            $senderAccount = $stmt2->fetch();

            if (!$senderAccount) {
                $stmt2 = $pdo->prepare("SELECT * FROM employee_smtp_accounts WHERE employee_id = ? ORDER BY id LIMIT 1");
                $stmt2->execute([$payroll['employee_id']]);
                $senderAccount = $stmt2->fetch();
            }

            if ($senderAccount) {
                $sendOptions = [
                    'host' => $senderAccount['smtp_host'],
                    'port' => (int)$senderAccount['smtp_port'],
                    'encryption' => $senderAccount['smtp_encryption'],
                    'username' => $senderAccount['smtp_username'],
                    'password' => $senderAccount['smtp_password'],
                    'from_email' => $senderAccount['from_email'],
                    'from_name' => $senderAccount['from_name']
                ];
            } else {
                $sendOptions = [
                    'host' => 'smtp.gmail.com',
                    'port' => 465,
                    'encryption' => 'ssl',
                    'username' => 'cbejerano14@gmail.com',
                    'password' => 'nneivkjbqbghhoku',
                    'from_email' => 'cbejerano14@gmail.com',
                    'from_name' => 'Payroll System'
                ];
            }

            $emailResult = sendSmtpMail(
                $payload['to_email'],
                $payload['subject'],
                $payload['message'],
                $sendOptions
            );

            if (!$emailResult['success']) {
                $message = 'Payroll finalized successfully, but email notification could not be sent: ' . ($emailResult['error'] ?? 'Unknown error');
            } else {
                $message = 'Payroll finalized successfully and email notification sent!';
            }
        } else {
            $message = 'Payroll finalized successfully!';
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ============================================================
// DELETE PAYROLL
// ============================================================
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Payroll record deleted!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// GET ALL EMPLOYEES
$employees = $pdo->query("
    SELECT id, employee_id, CONCAT(firstname, ' ', lastname) as name 
    FROM employees 
    WHERE status = 'active' 
    ORDER BY firstname
")->fetchAll();

// GET ALL PAYROLL RECORDS
$payrolls = $pdo->query("
    SELECT p.*, 
           CONCAT(e.firstname, ' ', e.lastname) as employee_name,
           e.employee_id as emp_code,
           r.role_name
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN roles r ON e.role_id = r.id
    ORDER BY p.month_year DESC, e.firstname
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Management</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <style>
        /* Action buttons container - horizontal alignment */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .action-buttons .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }
        
        .action-buttons .btn:active {
            transform: translateY(1px);
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-sm {
            font-size: 12px;
            padding: 5px 10px;
        }
        
        /* Table container responsive */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f9f9f9;
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
            <a href="payroll.php" class="nav-link active">
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

<!-- MAIN CONTENT -->
<main class="main-content">
    
    <div class="topbar">
        <h1>📊 Payroll Management</h1>
        <div><?php echo date('l, F d, Y'); ?></div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- GENERATE PAYROLL FORM -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Generate Payroll</h3>
        </div>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Select Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Choose Employee...</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Month/Year *</label>
                    <input type="month" name="month_year" value="<?php echo date('Y-m'); ?>" required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" name="generate_payroll" class="btn btn-primary">
                        🧮 Calculate Payroll
                    </button>
                </div>
            </div>
        </form>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
            <strong>ℹ️ Calculation Method:</strong>
            <ul style="margin: 10px 0 0 20px; font-size: 14px;">
                <li><strong>Basic Salary:</strong> (Days Worked + Paid Leaves) × Daily Rate</li>
                <li><strong>Overtime:</strong> Extra hours × (Hourly Rate × 1.25)</li>
                <li><strong>Incentives:</strong> Total incentives given for the month</li>
                <li><strong>Deductions:</strong> 15% of gross pay (SSS, PhilHealth, Pag-IBIG, Tax)</li>
                <li><strong>Net Pay:</strong> Gross Pay - Deductions</li>
            </ul>
        </div>
    </div>

    <!-- PAYROLL RECORDS -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payroll Records</h3>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month/Year</th>
                        <th>Days Worked</th>
                        <th>Basic Salary</th>
                        <th>Incentives</th>
                        <th>Deductions</th>
                        <th>Gross Pay</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: #999;">
                            No payroll records yet. Generate payroll above.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['employee_name']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars($p['emp_code']); ?> - <?php echo htmlspecialchars($p['role_name']); ?></small>
                            </td>
                            <td><?php echo date('F Y', strtotime($p['month_year'])); ?></td>
                            <td>
                                <?php echo $p['days_worked']; ?> days
                                <?php if ($p['paid_leaves'] > 0): ?>
                                    <br><small style="color: green;">+ <?php echo $p['paid_leaves']; ?> paid leaves</small>
                                <?php endif; ?>
                                <?php if ($p['days_absent'] > 0): ?>
                                    <br><small style="color: red;"><?php echo $p['days_absent']; ?> absent</small>
                                <?php endif; ?>
                            </td>
                            <td>₱<?php echo number_format($p['basic_salary'], 2); ?></td>
                            <td>
                                <?php if ($p['total_incentives'] > 0): ?>
                                    <span style="color: green;">₱<?php echo number_format($p['total_incentives'], 2); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: red;">₱<?php echo number_format($p['total_deductions'], 2); ?></span>
                            </td>
                            <td><strong>₱<?php echo number_format($p['gross_pay'], 2); ?></strong></td>
                            <td><strong style="color: green;">₱<?php echo number_format($p['net_pay'], 2); ?></strong></td>
                            <td>
                                <span class="status status-<?php echo $p['status']; ?>">
                                    <?php echo ucfirst($p['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button 
                                    type="button"
                                    class="btn btn-info btn-sm"
                                    onclick='showDetails(<?= json_encode($p) ?>)'>
                                    📄 Details
                                </button>
                                <?php if ($p['status'] == 'draft'): ?>
                                <a href="?finalize=<?php echo $p['id']; ?>" 
                                   class="btn btn-success btn-sm"
                                   onclick="return confirm('Finalize this payroll?')">
                                    ✅ Finalize
                                </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $p['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this payroll record?')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- PAYROLL DETAILS MODAL -->
<div id="payrollModal" style="
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    justify-content:center;
    align-items:center;
    z-index:9999;
">

    <div style="
        background:#fff;
        width:500px;
        padding:20px;
        border-radius:10px;
        max-height:80vh;
        overflow:auto;
    ">

        <h3>📄 Payroll Breakdown</h3>

        <div id="modalContent"></div>

        <br>

        <button onclick="closeDetails()" class="btn btn-danger btn-sm">
            Close
        </button>

    </div>
</div>

<script>
function showDetails(p) {

    document.getElementById("modalContent").innerHTML = `
        <p><b>Employee:</b> ${p.employee_name}</p>
        <p><b>Role:</b> ${p.role_name}</p>
        <p><b>Month:</b> ${p.month_year}</p>

        <hr>

        <h4>📊 Attendance</h4>
        <p>Days Worked: ${p.days_worked}</p>
        <p>Days Absent: ${p.days_absent}</p>
        <p>Paid Leaves: ${p.paid_leaves}</p>
        <p>Unpaid Leaves: ${p.unpaid_leaves}</p>

        <hr>

        <h4>💰 Computation</h4>
        <p>Basic Salary: ₱${parseFloat(p.basic_salary).toFixed(2)}</p>
        <p>Incentives: ₱${parseFloat(p.total_incentives).toFixed(2)}</p>
        <p>Gross Pay: ₱${parseFloat(p.gross_pay).toFixed(2)}</p>
        <p style="color:red;">Deductions: ₱${parseFloat(p.total_deductions).toFixed(2)}</p>
        <p style="color:green;"><b>Net Pay: ₱${parseFloat(p.net_pay).toFixed(2)}</b></p>
    `;

    document.getElementById("payrollModal").style.display = "flex";
}

function closeDetails() {
    document.getElementById("payrollModal").style.display = "none";
}
</script>
</body>
</html>
