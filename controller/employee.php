<?php
// ============================================================
// DATABASE & SESSION
// ============================================================
require_once('../model/database.php');
require_once('../model/session.php');

requireHR(); // Only HR/Admin

// CURRENT USER
$user = getCurrentUser();

$message = '';
$error = '';

// ============================================================
// DELETE EMPLOYEE
// ============================================================
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Employee deleted successfully!";
    } catch(PDOException $e) {
        $error = "Cannot delete employee: " . $e->getMessage();
    }
}

// ============================================================
// DELETE SMTP ACCOUNT
// ============================================================
if (isset($_GET['delete_smtp_account'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employee_smtp_accounts WHERE id = ?");
        $stmt->execute([$_GET['delete_smtp_account']]);
        $message = "SMTP sender account deleted successfully!";
    } catch(PDOException $e) {
        $error = "Cannot delete SMTP sender account: " . $e->getMessage();
    }
}

// ============================================================
// ADD / UPDATE SMTP ACCOUNT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ADD
    if (isset($_POST['action']) && $_POST['action'] == 'add') {

        $employee_id = 'EMP' . str_pad(rand(100,999), 3, '0', STR_PAD_LEFT);

        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);
        $role_id = $_POST['role_id'];
        $department_id = $_POST['department_id'];
        $shift_id = $_POST['shift_id'];
        $hire_date = $_POST['hire_date'];

        try {

            $stmt = $pdo->prepare("
                INSERT INTO employees
                (
                    employee_id,
                    firstname,
                    lastname,
                    email,
                    password,
                    phone,
                    role_id,
                    department_id,
                    shift_id,
                    hire_date
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $employee_id,
                $firstname,
                $lastname,
                $email,
                $password,
                $phone,
                $role_id,
                $department_id,
                $shift_id,
                $hire_date
            ]);

            $message = "Employee added successfully!";

        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }

    // SMTP ACCOUNT ADD
    if (isset($_POST['action']) && $_POST['action'] == 'add_smtp_account') {
        try {
            $employeeId = (int)$_POST['employee_id'];
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            if ($isDefault) {
                $resetStmt = $pdo->prepare("UPDATE employee_smtp_accounts SET is_default = 0 WHERE employee_id = ?");
                $resetStmt->execute([$employeeId]);
            }

            $sql = <<<SQL
INSERT INTO employee_smtp_accounts
(
    employee_id,
    label,
    smtp_host,
    smtp_port,
    smtp_encryption,
    smtp_username,
    smtp_password,
    from_email,
    from_name,
    is_default
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL;
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $employeeId,
                trim($_POST['label']),
                trim($_POST['smtp_host']),
                (int)$_POST['smtp_port'],
                trim($_POST['smtp_encryption']),
                trim($_POST['smtp_username']),
                trim($_POST['smtp_password']),
                trim($_POST['from_email']),
                trim($_POST['from_name']),
                $isDefault
            ]);

            $message = "SMTP sender account added successfully!";
        } catch(PDOException $e) {
            $error = "Cannot save SMTP sender account: " . $e->getMessage();
        }
    }

    // SMTP ACCOUNT UPDATE
    if (isset($_POST['action']) && $_POST['action'] == 'update_smtp_account') {
        try {
            $accountId = (int)$_POST['smtp_account_id'];
            $employeeId = (int)$_POST['employee_id'];
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            if ($isDefault) {
                $resetStmt = $pdo->prepare("UPDATE employee_smtp_accounts SET is_default = 0 WHERE employee_id = ?");
                $resetStmt->execute([$employeeId]);
            }

            $sql = <<<SQL
UPDATE employee_smtp_accounts
SET
    employee_id = ?,
    label = ?,
    smtp_host = ?,
    smtp_port = ?,
    smtp_encryption = ?,
    smtp_username = ?,
    smtp_password = ?,
    from_email = ?,
    from_name = ?,
    is_default = ?
WHERE id = ?
SQL;
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $employeeId,
                trim($_POST['label']),
                trim($_POST['smtp_host']),
                (int)$_POST['smtp_port'],
                trim($_POST['smtp_encryption']),
                trim($_POST['smtp_username']),
                trim($_POST['smtp_password']),
                trim($_POST['from_email']),
                trim($_POST['from_name']),
                $isDefault,
                $accountId
            ]);

            $message = "SMTP sender account updated successfully!";
        } catch(PDOException $e) {
            $error = "Cannot update SMTP sender account: " . $e->getMessage();
        }
    }

    // UPDATE
    if (isset($_POST['action']) && $_POST['action'] == 'update') {

        $id = $_POST['employee_db_id'];

        try {

            $stmt = $pdo->prepare("
                UPDATE employees
                SET
                    firstname = ?,
                    lastname = ?,
                    email = ?,
                    password = ?,
                    phone = ?,
                    role_id = ?,
                    department_id = ?,
                    shift_id = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['firstname'],
                $_POST['lastname'],
                $_POST['email'],
                $_POST['pass'],
                $_POST['phone'],
                $_POST['role_id'],
                $_POST['department_id'],
                $_POST['shift_id'],
                $_POST['status'],
                $id
            ]);

            $message = "Employee updated successfully!";

        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
}

// ============================================================
// FETCH EMPLOYEES
// ============================================================
$stmt = $pdo->query("
    SELECT
        e.*,
        r.role_name,
        r.monthly_salary,
        d.department_name,
        s.shift_name
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN shifts s ON e.shift_id = s.id
    ORDER BY e.created_at DESC
");

$employees = $stmt->fetchAll();

// DROPDOWNS
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$shifts = $pdo->query("SELECT * FROM shifts")->fetchAll();

$smtpAccounts = $pdo->query("SELECT a.*, e.firstname, e.lastname, e.employee_id AS emp_code FROM employee_smtp_accounts a JOIN employees e ON a.employee_id = e.id ORDER BY a.created_at DESC")->fetchAll();

// ============================================================
// DASHBOARD STATS
// ============================================================

// TOTAL EMPLOYEES
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM employees
    WHERE status = 'active'
");
$total_employees = $stmt->fetchColumn();

// TOTAL HR
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    WHERE r.role_name LIKE '%HR%'
");
$total_hr = $stmt->fetchColumn();

// TOTAL DEPARTMENTS
$stmt = $pdo->query("SELECT COUNT(*) FROM departments");
$total_departments = $stmt->fetchColumn();

// TOTAL SHIFTS
$stmt = $pdo->query("SELECT COUNT(*) FROM shifts");
$total_shifts = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>

    <link rel="stylesheet" href="../view/dashboard.css">
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
            <a href="employee.php" class="nav-link active">
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

        <li class="nav-item" style="margin-top:20px;">
            <a href="logout.php" class="nav-link">
                <i>🚪</i> Logout
            </a>
        </li>

    </ul>

    <div class="user-info">

        <strong> <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?> </strong>
        <small> <?php echo htmlspecialchars($user['email']); ?> </small>

        <?php if ($user['is_admin']): ?>
            <span class="badge badge-admin">ADMIN</span>
        <?php elseif ($user['is_hr']): ?>
            <span class="badge badge-hr">HR</span>
        <?php endif; ?>

    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <h1>👥 Employee Management</h1>
    </div>

    <!-- ALERTS -->
    <?php if($message): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- EMPLOYEE TABLE -->
    <div class="card">

        <div class="card-header">

            <h3 class="card-title">All Employees</h3>

            <div class="button-group">
                <button class="btn btn-primary"
                        onclick="openModal('addModal')">
                    + Add Employee
                </button>
                <a href="export_employees_xml.php" class="btn btn-secondary">
                    📄 Export XML
                </a>
            </div>

        </div>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Shift</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach($employees as $emp): ?>

                <tr>
                    <td><?php echo $emp['employee_id']; ?></td>
                    <td><?php echo $emp['firstname'] . ' ' . $emp['lastname']; ?></td>
                    <td><?php echo $emp['email']; ?></td>
                    <td><?php echo $emp['password']; ?></td>
                    <td><?php echo $emp['role_name']; ?></td>
                    <td><?php echo $emp['department_name']; ?></td>
                    <td><?php echo $emp['shift_name']; ?></td>
                    <td>₱<?php echo number_format($emp['monthly_salary'], 2); ?></td>
                    <td>
                        <span class="status status-<?php echo $emp['status']; ?>">
                            <?php echo ucfirst($emp['status']); ?>
                        </span>
                    </td>

                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary btn-sm"
                                    onclick='editEmployee(<?php echo json_encode($emp); ?>)'>
                                Edit
                            </button>
                            <a href="?delete=<?php echo $emp['id']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete <?php echo htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']); ?>? This cannot be undone.')">
                                Delete
                            </a>
                        </div>
                    </td>
                </tr>

                <?php endforeach; ?>

            </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <div class="card-header">
            <h3 class="card-title">SMTP Sender Accounts</h3>
            <div class="button-group">
                <button class="btn btn-primary" onclick="openModal('addSmtpModal')">
                    + Add SMTP Account
                </button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Label</th>
                        <th>SMTP Host</th>
                        <th>Username</th>
                        <th>From Email</th>
                        <th>Default</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smtpAccounts as $account): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($account['firstname'] . ' ' . $account['lastname']); ?> (<?php echo htmlspecialchars($account['emp_code']); ?>)</td>
                        <td><?php echo htmlspecialchars($account['label']); ?></td>
                        <td><?php echo htmlspecialchars($account['smtp_host']); ?>:<?php echo htmlspecialchars($account['smtp_port']); ?></td>
                        <td><?php echo htmlspecialchars($account['smtp_username']); ?></td>
                        <td><?php echo htmlspecialchars($account['from_email']); ?></td>
                        <td><?php echo $account['is_default'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-primary btn-sm" onclick='editSmtpAccount(<?php echo json_encode($account); ?>)'>Edit</button>
                                <a href="?delete_smtp_account=<?php echo $account['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this SMTP sender account?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ADD SMTP ACCOUNT MODAL -->
<div id="addSmtpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add SMTP Sender Account</h3>
            <button class="close-btn" onclick="closeModal('addSmtpModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_smtp_account">
            <div class="form-group">
                <label>Employee *</label>
                <select name="employee_id" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" placeholder="E.g. Gmail Payroll Sender">
            </div>
            <div class="form-group">
                <label>SMTP Host *</label>
                <input type="text" name="smtp_host" value="smtp.gmail.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Port *</label>
                    <input type="number" name="smtp_port" value="465" required>
                </div>
                <div class="form-group">
                    <label>Encryption *</label>
                    <select name="smtp_encryption" required>
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>SMTP Username *</label>
                <input type="email" name="smtp_username" required>
            </div>
            <div class="form-group">
                <label>SMTP Password *</label>
                <input type="password" name="smtp_password" required>
            </div>
            <div class="form-group">
                <label>From Email *</label>
                <input type="email" name="from_email" required>
            </div>
            <div class="form-group">
                <label>From Name *</label>
                <input type="text" name="from_name" value="Payroll System" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_default" value="1"> Use as default sender for this employee
                </label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save SMTP Account</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSmtpModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SMTP ACCOUNT MODAL -->
<div id="editSmtpModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit SMTP Sender Account</h3>
            <button class="close-btn" onclick="closeModal('editSmtpModal')">&times;</button>
        </div>
        <form method="POST" id="editSmtpForm">
            <input type="hidden" name="action" value="update_smtp_account">
            <input type="hidden" name="smtp_account_id" id="edit_smtp_account_id">
            <div class="form-group">
                <label>Employee *</label>
                <select name="employee_id" id="edit_smtp_employee_id" required>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" id="edit_smtp_label" placeholder="E.g. Gmail Payroll Sender">
            </div>
            <div class="form-group">
                <label>SMTP Host *</label>
                <input type="text" name="smtp_host" id="edit_smtp_host" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Port *</label>
                    <input type="number" name="smtp_port" id="edit_smtp_port" required>
                </div>
                <div class="form-group">
                    <label>Encryption *</label>
                    <select name="smtp_encryption" id="edit_smtp_encryption" required>
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>SMTP Username *</label>
                <input type="email" name="smtp_username" id="edit_smtp_username" required>
            </div>
            <div class="form-group">
                <label>SMTP Password *</label>
                <input type="password" name="smtp_password" id="edit_smtp_password" required>
            </div>
            <div class="form-group">
                <label>From Email *</label>
                <input type="email" name="from_email" id="edit_from_email" required>
            </div>
            <div class="form-group">
                <label>From Name *</label>
                <input type="text" name="from_name" id="edit_from_name" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_default" id="edit_is_default" value="1"> Use as default sender for this employee
                </label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update SMTP Account</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSmtpModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD EMPLOYEE MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">

        <div class="modal-header">
            <h3 class="modal-title">Add New Employee</h3>

            <button class="close-btn"
                    onclick="closeModal('addModal')">
                &times;
            </button>
        </div>

        <form method="POST">

            <input type="hidden" name="action" value="add">

            <div class="form-row">

                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="firstname" required>
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="lastname" required>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>

            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Role *</label>

                    <select name="role_id" required>

                        <option value="">
                            Select Role
                        </option>

                        <?php foreach ($roles as $role): ?>

                        <option value="<?php echo $role['id']; ?>">

                            <?php echo htmlspecialchars($role['role_name']); ?>

                            - ₱<?php echo number_format($role['monthly_salary'], 0); ?>/month

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Department *</label>

                    <select name="department_id" required>

                        <option value=""> Select Department </option>

                        <?php foreach ($departments as $dept): ?>

                        <option value="<?php echo $dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>

                        <?php endforeach; ?>

                    </select>
                </div>
            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Shift *</label>

                    <select name="shift_id" required>

                        <option value=""> Select Shift </option>

                        <?php foreach ($shifts as $shift): ?>

                        <option value="<?php echo $shift['id']; ?>">

                            <?php echo htmlspecialchars($shift['shift_name']); ?>

                            (<?php echo $shift['time_in'] . ' - ' . $shift['time_out']; ?>)

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">
                    <label>Hire Date *</label>
                    <input type="date" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"> Add Employee </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')"> Cancel </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT EMPLOYEE MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">

            <h3 class="modal-title"> Edit Employee </h3>

            <button class="close-btn" onclick="closeModal('editModal')"> &times; </button>

        </div>

        <form method="POST" id="editForm">

            <input type="hidden" name="action" value="update">
            <input type="hidden" name="employee_db_id" id="edit_id">

            <div class="form-row">

                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="firstname" id="edit_firstname" required>
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="lastname" id="edit_lastname" required>
                </div>

            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="edit_email" required>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="pass" id="pass" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone">
            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Role *</label>

                    <select name="role_id" id="edit_role_id" required>

                        <?php foreach ($roles as $role): ?>

                        <option value="<?php echo $role['id']; ?>">

                            <?php echo htmlspecialchars($role['role_name']); ?>

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Department *</label>

                    <select name="department_id" id="edit_department_id" required>

                        <?php foreach ($departments as $dept): ?>

                        <option value="<?php echo $dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>Shift *</label>

                    <select name="shift_id" id="edit_shift_id" required>

                        <?php foreach ($shifts as $shift): ?>

                        <option value="<?php echo $shift['id']; ?>">
                            <?php echo htmlspecialchars($shift['shift_name']); ?>
                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Status *</label>

                    <select name="status" id="edit_status" required>
                        <option value="active"> Active </option>
                        <option value="inactive"> Inactive </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"> Update Employee </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')"> Cancel </button>
            </div>
        </form>
    </div>
</div>

<script>

// OPEN MODAL
function openModal(id){
    document.getElementById(id).classList.add('show');
}

// CLOSE MODAL
function closeModal(id){
    document.getElementById(id).classList.remove('show');
}

// EDIT EMPLOYEE
function editEmployee(emp){

    document.getElementById('edit_id').value = emp.id;
    document.getElementById('edit_firstname').value = emp.firstname;
    document.getElementById('edit_lastname').value = emp.lastname;
    document.getElementById('edit_email').value = emp.email;
    document.getElementById('edit_phone').value = emp.phone;
    document.getElementById('edit_role_id').value = emp.role_id;
    document.getElementById('edit_department_id').value = emp.department_id;
    document.getElementById('edit_shift_id').value = emp.shift_id;
    document.getElementById('edit_status').value = emp.status;

    openModal('editModal');
}

function editSmtpAccount(account) {
    document.getElementById('edit_smtp_account_id').value = account.id;
    document.getElementById('edit_smtp_employee_id').value = account.employee_id;
    document.getElementById('edit_smtp_label').value = account.label;
    document.getElementById('edit_smtp_host').value = account.smtp_host;
    document.getElementById('edit_smtp_port').value = account.smtp_port;
    document.getElementById('edit_smtp_encryption').value = account.smtp_encryption;
    document.getElementById('edit_smtp_username').value = account.smtp_username;
    document.getElementById('edit_smtp_password').value = account.smtp_password;
    document.getElementById('edit_from_email').value = account.from_email;
    document.getElementById('edit_from_name').value = account.from_name;
    document.getElementById('edit_is_default').checked = account.is_default == 1;

    openModal('editSmtpModal');
}

// CLOSE WHEN CLICK OUTSIDE
window.onclick = function(event){

    if(event.target.classList.contains('modal')){
        event.target.classList.remove('show');
    }

}

</script>
</body>
</html>
