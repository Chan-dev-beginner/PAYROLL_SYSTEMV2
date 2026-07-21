<?php
require 'PHPMailer/PHPMailer.php';

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 465;
$mail->SMTPAuth = true;
$mail->Username = 'cbejerano14@gmail.com';
$mail->Password = 'nneivkjbqbghhoku';
$mail->SMTPSecure = 'ssl';
$mail->From = 'cbejerano14@gmail.com';
$mail->FromName = 'Payroll System';
$mail->isHTML(true);
$mail->Subject = 'SMTP Test';
$mail->Body = '<p>This is a test email from the payroll app.</p>';
$mail->AltBody = 'This is a test email from the payroll app.';
$mail->addAddress('cbejerano14@gmail.com');

$result = $mail->send();
if ($result) {
    echo 'SENT';
} else {
    echo $mail->ErrorInfo;
}
