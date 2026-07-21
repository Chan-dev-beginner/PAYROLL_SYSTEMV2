<?php
require_once('../model/database.php');
require_once('../model/session.php');

requireLogin();

$user = getCurrentUser();

if (!$user['is_hr'] && !$user['is_admin']) {
    header("Location: dashboard.php");
    exit();
}

/* ============================================================
   ADD INCENTIVE TYPE
============================================================ */
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

    $success = "Incentive type added successfully!";
}

/* ============================================================
   UPDATE INCENTIVE TYPE
============================================================ */
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

    $success = "Incentive type updated successfully!";
}

/* ============================================================
   DELETE INCENTIVE TYPE
============================================================ */
if (isset($_GET['delete'])) {

    $stmt = $pdo->prepare("DELETE FROM incentive_types WHERE id = ?");
    $stmt->execute([$_GET['delete']]);

    header("Location: manage_incentives.php");
    exit();
}

/* ============================================================
   EDIT MODE FETCH
============================================================ */
$editData = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("SELECT * FROM incentive_types WHERE id = ?");
    $stmt->execute([$_GET['edit']]);

    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ============================================================
   FETCH ALL INCENTIVES
============================================================ */
$incentives = $pdo->query("
    SELECT id, incentive_name, description, amount, created_at
    FROM incentive_types
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Incentives</title>

    <link rel="stylesheet" href="../view/dashboard.css">
</head>

<body class="dashboard">

<main class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
        <h1>🎁 Incentives Management</h1>
                        <div><?= date('l, F d, Y') ?></div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="form-row">

        <!-- FORM -->
        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    <?= $editData ? "Edit Incentive Type" : "Add Incentive Type" ?>
                </h3>
            </div>

            <form method="POST">

                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Incentive Name</label>
                    <input type="text" name="incentive_name"
                           value="<?= $editData['incentive_name'] ?? '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount"
                           value="<?= $editData['amount'] ?? '' ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?= $editData['description'] ?? '' ?></textarea>
                </div>

                <button type="submit"
                        name="<?= $editData ? 'update_incentive' : 'add_incentive' ?>"
                        class="btn btn-primary btn-block">

                    <?= $editData ? "✏️ Update Incentive" : "➕ Add Incentive Type" ?>
                </button>

                <?php if ($editData): ?>
                    <a href="manage_incentives.php" class="btn btn-secondary btn-block">
                        Cancel Edit
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <!-- TABLE -->
        <div class="card">

            <div class="card-header">
                <h3 class="card-title">Incentive Records</h3>
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

                    <?php if (empty($incentives)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No incentive records found
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($incentives as $i): ?>
                        <tr>

                            <td><?= htmlspecialchars($i['incentive_name']) ?></td>

                            <td>
                                <strong>₱<?= number_format($i['amount'], 2) ?></strong>
                            </td>

                            <td><?= htmlspecialchars($i['description']) ?></td>

                            <td><?= $i['created_at'] ?></td>

                            <td class="action-buttons">

                                <a href="?edit=<?= $i['id'] ?>"
                                   class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <a href="?delete=<?= $i['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this incentive type?')">
                                    Delete
                                </a>

                            </td>

                        </tr>
                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

</body>
</html>
