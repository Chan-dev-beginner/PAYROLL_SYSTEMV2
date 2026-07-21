<?php
// ============================================================
// SESSION HELPER - Login/Logout Functions
// ============================================================

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// CHECK IF USER IS LOGGED IN
// ============================================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ============================================================
// REQUIRE LOGIN - Redirect to login if not logged in
// ============================================================
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// ============================================================
// REQUIRE HR ACCESS
// ============================================================
function requireHR() {
    requireLogin();
    if ($_SESSION['is_hr'] != 1 && $_SESSION['is_admin'] != 1) {
        header("Location: dashboard.php?error=Access denied");
        exit();
    }
}

// ============================================================
// GET CURRENT USER INFO
// ============================================================
function getCurrentUser() {

    global $pdo;

    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("
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
        WHERE e.id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user_id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================================
// LOGIN FUNCTION
// ============================================================
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_hr'] = $user['is_hr'];
    $_SESSION['is_admin'] = $user['is_admin'];
}

// ============================================================
// LOGOUT FUNCTION
// ============================================================
function logoutUser() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
