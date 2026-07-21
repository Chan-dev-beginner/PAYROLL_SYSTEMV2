<?php
require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

function getNextId(PDO $pdo, string $table)
{
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM {$table}");
    return (int) $stmt->fetchColumn();
}

if (isset($_GET['success']) && $_GET['success'] === 'assigned') {
    $message = 'Incentive assigned successfully!';
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

if (!$user['is_hr'] && !$user['is_admin']) {
    header("Location: dashboard.php");
    exit();
}

/* DELETE */
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM incentive_types WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_incentives.php");
    exit();
}

/* EDIT FETCH */
$editData = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM incentive_types WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ADD */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_incentive'])) {

    $stmt = $pdo->prepare("
        INSERT INTO incentive_types (incentive_name, description, amount)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $_POST['incentive_name'],
        $_POST['description'],
        $_POST['amount']
    ]);

    header("Location: manage_incentives.php");
    exit();
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_incentive'])) {

    $stmt = $pdo->prepare("
        UPDATE incentive_types
        SET incentive_name = ?, description = ?, amount = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['incentive_name'],
        $_POST['description'],
        $_POST['amount'],
        $_POST['id']
    ]);

    header("Location: manage_incentives.php");
    exit();
}

/* ASSIGN INCENTIVE TO EMPLOYEE */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_incentive'])) {

    $newId = getNextId($pdo, 'employee_incentives');

    $stmt = $pdo->prepare("
        INSERT INTO employee_incentives (id, employee_id, incentive_type_id, amount, remarks)
        VALUES (?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([
            $newId,
            $_POST['employee_id'],
            $_POST['incentive_type_id'],
            $_POST['amount'],
            $_POST['remarks']
        ]);

        header("Location: manage_incentives.php?success=assigned");
        exit();
    } catch (PDOException $e) {
        header("Location: manage_incentives.php?error=" . urlencode('Failed to assign incentive: ' . $e->getMessage()));
        exit();
    }
}

/* DATA */
$incentives = $pdo->query("
    SELECT id, incentive_name, description, amount, created_at
    FROM incentive_types
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$employees = $pdo->query("
    SELECT id, firstname, lastname
    FROM employees
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Incentives</title>
    <link rel="stylesheet" href="../view/dashboard.css">
    <style>
       
    /* ==========================================
   MAIN CONTENT
========================================== */
.main-content{
    margin-left:260px;
    padding:30px;
    min-height:100vh;
}

/* ==========================================
   TOPBAR
========================================== */
.topbar{
    background:#fff;
    padding:20px 30px;
    margin-bottom:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
}

.topbar h1{
    margin:0;
    font-size:28px;
    color:#333;
}

/* ==========================================
   ACTION BUTTONS
========================================== */
.action-header{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.btn-add,
.btn-assign{
    border:none;
    border-radius:8px;
    padding:10px 18px;
    color:#fff;
    cursor:pointer;
    font-size:14px;
    font-weight:600;
    transition:.3s;
}

.btn-add{
    background:#3498db;
}

.btn-add:hover{
    background:#2980b9;
}

.btn-assign{
    background:#28a745;
}

.btn-assign:hover{
    background:#218838;
}

/* ==========================================
   CARD
========================================== */
.card{
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.card-header{
    padding:20px;
    background:#f8f9fa;
    border-bottom:1px solid #e9ecef;
}

.card-title{
    margin:0;
    font-size:20px;
}

/* ==========================================
   TABLE
========================================== */
.table-container{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#2c3e50;
    color:white;
}

th,
td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid #eee;
}

tbody tr:hover{
    background:#f8f9fa;
}

/* ==========================================
   ACTION BUTTONS INSIDE TABLE
========================================== */
.action-buttons{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.btn-sm{
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:13px;
    text-decoration:none;
}

.btn-warning{
    background:#ffc107;
    color:#212529;
}

.btn-warning:hover{
    background:#e0a800;
}

.btn-danger{
    background:#dc3545;
    color:white;
}

.btn-danger:hover{
    background:#c82333;
}

/* ==========================================
   MODAL
========================================== */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    backdrop-filter:blur(4px);
    z-index:9999;
}

.modal-content{
    width:500px;
    max-width:95%;
    margin:60px auto;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    animation:fadeIn .25s ease;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(-20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.modal-header{
    background:#2c3e50;
    color:white;
    padding:20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.modal-body{
    padding:20px;
}

.modal-footer{
    padding:20px;
    background:#f8f9fa;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

.close-modal{
    font-size:28px;
    cursor:pointer;
}

/* ==========================================
   FORM
========================================== */
.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:6px;
    font-size:14px;
}

.form-group textarea{
    min-height:90px;
    resize:vertical;
}

.btn-primary{
    background:#3498db;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
}

.btn-primary:hover{
    background:#2980b9;
}

.btn-secondary{
    background:#95a5a6;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
}

.btn-secondary:hover{
    background:#7f8c8d;
}

/* ==========================================
   MOBILE
========================================== */
@media(max-width:768px){

    .main-content{
        margin-left:0;
        padding:15px;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .action-header{
        justify-content:stretch;
    }

    .btn-add,
    .btn-assign{
        width:100%;
    }

    .action-buttons{
        flex-direction:column;
    }

    .action-buttons .btn{
        width:100%;
        text-align:center;
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
            <a href="manage_incentives.php" class="nav-link active">
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

<!-- MAIN -->
<main class="main-content">

    <div class="topbar">
        <h1>🎁 Incentives Management</h1>
        <div><?= date('l, F d, Y') ?></div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:20px; padding:15px; border-radius:8px; background:#d4edda; color:#155724; border:1px solid #c3e6cb;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:20px; padding:15px; border-radius:8px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-header">
        <button class="btn-add" id="openAddModalBtn">
            ➕ Add New Incentive
        </button>
        <button class="btn-assign" id="openAssignModalBtn">
            🎯 Assign Incentive to Employee
        </button>
    </div>

    <!-- DOMINANT INCENTIVE RECORDS TABLE -->
    <div class="card dominant-table">
        <div class="card-header">
            <h3 class="card-title">📋 Incentive Records</h3>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Incentive</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incentives as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['incentive_name']) ?></td>
                            <td>₱<?= number_format($i['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($i['description']) ?></td>
                            <td><?= $i['created_at'] ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-warning btn-sm edit-incentive-btn" 
                                        data-id="<?= $i['id'] ?>"
                                        data-name="<?= htmlspecialchars($i['incentive_name']) ?>"
                                        data-amount="<?= $i['amount'] ?>"
                                        data-description="<?= htmlspecialchars($i['description']) ?>">
                                    Edit
                                </button>
                                <a href="?delete=<?= $i['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this incentive? This action cannot be undone.')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($incentives)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No incentive records found. Click "Add New Incentive" to create one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- MODAL: Add/Edit Incentive -->
<div id="incentiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Incentive</h3>
            <span class="close-modal" id="closeModalBtn">&times;</span>
        </div>
        <form method="POST" id="incentiveForm">
            <input type="hidden" name="id" id="incentive_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Incentive Name *</label>
                    <input type="text" name="incentive_name" id="incentive_name" required>
                </div>
                <div class="form-group">
                    <label>Amount (₱) *</label>
                    <input type="number" step="0.01" name="amount" id="incentive_amount" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="incentive_description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelModalBtn">Cancel</button>
                <button type="submit" name="add_incentive" id="submitBtn" class="btn-primary">Add Incentive</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Assign Incentive to Employee -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Incentive to Employee</h3>
            <span class="close-modal" id="closeAssignModalBtn">&times;</span>
        </div>
        <form method="POST" id="assignForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= htmlspecialchars($e['firstname'] . ' ' . $e['lastname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Incentive Type *</label>
                    <select name="incentive_type_id" id="assign_incentive_select" required onchange="fillAssignAmount(this)">
                        <option value="">Select Incentive</option>
                        <?php foreach ($incentives as $i): ?>
                            <option value="<?= $i['id'] ?>" data-amount="<?= $i['amount'] ?>">
                                <?= htmlspecialchars($i['incentive_name']) ?> — ₱<?= number_format($i['amount'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="amount" id="assign_amount_field">
                <div class="form-group">
                    <label>Amount (Auto-filled)</label>
                    <input type="text" id="assign_amount_display" readonly
                           style="background: #f0f0f0; cursor: not-allowed;"
                           placeholder="Auto-filled from incentive type">
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" placeholder="Optional remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelAssignBtn">Cancel</button>
                <button type="submit" name="assign_incentive" class="btn-primary">Assign Incentive</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal elements
const incentiveModal = document.getElementById('incentiveModal');
const assignModal = document.getElementById('assignModal');
const openAddModalBtn = document.getElementById('openAddModalBtn');
const openAssignModalBtn = document.getElementById('openAssignModalBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const closeAssignModalBtn = document.getElementById('closeAssignModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');
const cancelAssignBtn = document.getElementById('cancelAssignBtn');
const incentiveForm = document.getElementById('incentiveForm');
const modalTitle = document.getElementById('modalTitle');
const submitBtn = document.getElementById('submitBtn');

// Open Add Modal
openAddModalBtn.onclick = function() {
    modalTitle.textContent = 'Add New Incentive';
    submitBtn.name = 'add_incentive';
    submitBtn.textContent = 'Add Incentive';
    document.getElementById('incentive_id').value = '';
    document.getElementById('incentive_name').value = '';
    document.getElementById('incentive_amount').value = '';
    document.getElementById('incentive_description').value = '';
    incentiveModal.style.display = 'block';
}

// Open Assign Modal
openAssignModalBtn.onclick = function() {
    assignModal.style.display = 'block';
    // Reset assign form
    document.getElementById('assignForm').reset();
    document.getElementById('assign_amount_display').value = '';
    document.getElementById('assign_amount_field').value = '';
}

// Close modals
function closeModals() {
    incentiveModal.style.display = 'none';
    assignModal.style.display = 'none';
}

closeModalBtn.onclick = closeModals;
closeAssignModalBtn.onclick = closeModals;
cancelModalBtn.onclick = closeModals;
cancelAssignBtn.onclick = closeModals;

// Click outside to close
window.onclick = function(event) {
    if (event.target == incentiveModal) {
        incentiveModal.style.display = 'none';
    }
    if (event.target == assignModal) {
        assignModal.style.display = 'none';
    }
}

// Edit incentive functionality
document.querySelectorAll('.edit-incentive-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const amount = this.dataset.amount;
        const description = this.dataset.description;
        
        modalTitle.textContent = 'Edit Incentive';
        submitBtn.name = 'update_incentive';
        submitBtn.textContent = 'Update Incentive';
        document.getElementById('incentive_id').value = id;
        document.getElementById('incentive_name').value = name;
        document.getElementById('incentive_amount').value = amount;
        document.getElementById('incentive_description').value = description;
        
        incentiveModal.style.display = 'block';
    });
});

// Fill amount for assign modal
function fillAssignAmount(select) {
    const amount = select.options[select.selectedIndex].dataset.amount || '';
    document.getElementById('assign_amount_field').value = amount;
    document.getElementById('assign_amount_display').value = amount ? '₱' + parseFloat(amount).toFixed(2) : '';
}

// If there's an edit parameter in URL (for backward compatibility), auto-open edit modal
<?php if ($editData): ?>
document.addEventListener('DOMContentLoaded', function() {
    modalTitle.textContent = 'Edit Incentive';
    submitBtn.name = 'update_incentive';
    submitBtn.textContent = 'Update Incentive';
    document.getElementById('incentive_id').value = '<?= $editData['id'] ?>';
    document.getElementById('incentive_name').value = '<?= htmlspecialchars($editData['incentive_name']) ?>';
    document.getElementById('incentive_amount').value = '<?= $editData['amount'] ?>';
    document.getElementById('incentive_description').value = '<?= htmlspecialchars($editData['description']) ?>';
    incentiveModal.style.display = 'block';
});
<?php endif; ?>
</script>

</body>
</html>
