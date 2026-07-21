<?php

require_once('../model/database.php');
require_once('../model/session.php');

requireHR();

if(isset($_GET['id'])) {

    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("
        UPDATE leave_requests
        SET status = 'approved'
        WHERE id = ?
    ");

    $stmt->execute([$id]);
}

header("Location: manage_leaves.php");
exit;
