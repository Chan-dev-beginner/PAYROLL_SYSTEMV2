<?php
require_once __DIR__ . '/../model/email.php';

function sendPayrollFinalizedEmail(array $payload): array
{
    $toEmail = $payload['to_email'] ?? $payload['to'] ?? '';
    $subject = $payload['subject'] ?? 'Payroll Finalized';
    $message = $payload['message'] ?? '';

    if (empty($toEmail)) {
        return ['success' => false, 'error' => 'Recipient email is required.'];
    }

    if (empty($message)) {
        return ['success' => false, 'error' => 'Email message is required.'];
    }

    return sendSmtpMail($toEmail, $subject, $message);
}

if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $result = sendPayrollFinalizedEmail($data);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
