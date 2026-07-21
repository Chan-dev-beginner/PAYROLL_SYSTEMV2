<?php
// ============================================================
// LOGIN PAGE (PLAIN TEXT PASSWORD VERSION)
// ============================================================
require_once('../model/database.php');
require_once '../model/session.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$inactive = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Get user by email only (no status filter)
    $stmt = $pdo->prepare("
        SELECT * 
        FROM employees 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {

        // ✅ Correct credentials — now check status
        if ($user['status'] === 'inactive') {
            $inactive = true;
        } else {
            loginUser($user);
            header("Location: dashboard.php");
            exit();
        }

    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Payroll System</title>
    <link rel="stylesheet" href="../view/dashboard.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-box">
        <div class="logo">
            <h1>💼 Payroll System</h1>
            <p>Company ni Chan</p>
        </div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to your account</p>

        <?php if ($inactive): ?>
            <div class="alert alert-danger" style="text-align:left; line-height:1.6;">
                🔒 <strong>Account Inactive</strong><br>
                Your account has been deactivated and you are unable to log in.<br>
                Please contact HR for more information.
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required
                       placeholder="your.email@company.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required
                       placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                Sign In →
            </button>
        </form>

        <div class="demo-accounts">
            <p><strong>Demo Accounts:</strong></p>
            <p>Admin: <code>admin@company.com</code> / <code>admin123</code></p>
            <p>Employee: <code>juan@company.com</code> / <code>pass123</code></p>
        </div>
    </div>
</div>
</body>
</html>
