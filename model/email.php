<?php
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';

function sendSmtpMail(string $toEmail, string $subject, string $message, array $options = []): array
{
    $host = $options['host'] ?? getenv('SMTP_HOST') ?: ($_SERVER['SMTP_HOST'] ?? $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
    $port = (int)($options['port'] ?? getenv('SMTP_PORT') ?: ($_SERVER['SMTP_PORT'] ?? $_ENV['SMTP_PORT'] ?? 465));
    $encryption = $options['encryption'] ?? getenv('SMTP_ENCRYPTION') ?: ($_SERVER['SMTP_ENCRYPTION'] ?? $_ENV['SMTP_ENCRYPTION'] ?? 'ssl');
    $username = $options['username'] ?? getenv('SMTP_USERNAME') ?: ($_SERVER['SMTP_USERNAME'] ?? $_ENV['SMTP_USERNAME'] ?? 'cbejerano14@gmail.com');
    $password = $options['password'] ?? getenv('SMTP_PASSWORD') ?: ($_SERVER['SMTP_PASSWORD'] ?? $_ENV['SMTP_PASSWORD'] ?? 'nneivkjbqbghhoku');
    $fromEmail = $options['from_email'] ?? getenv('SMTP_FROM_EMAIL') ?: ($_SERVER['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_FROM_EMAIL'] ?? 'cbejerano14@gmail.com');
    $fromName = $options['from_name'] ?? getenv('SMTP_FROM_NAME') ?: ($_SERVER['SMTP_FROM_NAME'] ?? $_ENV['SMTP_FROM_NAME'] ?? 'Payroll System');

    if (empty($host) || empty($fromEmail) || empty($username) || empty($password)) {
        return ['success' => false, 'error' => 'SMTP settings are not configured.'];
    }

    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $encryption;
        $mail->From = $fromEmail;
        $mail->FromName = $fromName;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        $mail->addAddress($toEmail);

        $sent = $mail->send();
        if (!$sent) {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
