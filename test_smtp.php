<?php
$host = 'smtp.gmail.com';
$port = 587;
$encryption = 'tls';
$username = 'cbejerano14@gmail.com';
$password = 'nneivkjbqbghhoku';
$from = 'cbejerano14@gmail.com';
$to = 'cbejerano14@gmail.com';

$socket = fsockopen(($encryption === 'ssl' ? 'ssl://' : '') . $host, $port, $errno, $errstr, 15);
if (!$socket) {
    echo 'CONNECTION_FAILED: ' . $errstr . PHP_EOL;
    exit(1);
}

echo '220_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "EHLO localhost\r\n");
echo 'EHLO_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "STARTTLS\r\n");
echo 'STARTTLS_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
fputs($socket, "EHLO localhost\r\n");
echo 'EHLO2_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "AUTH LOGIN\r\n");
echo 'AUTH_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, base64_encode($username) . "\r\n");
echo 'USER_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, base64_encode($password) . "\r\n");
echo 'PASS_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "MAIL FROM: <{$from}>\r\n");
echo 'MAIL_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "RCPT TO: <{$to}>\r\n");
echo 'RCPT_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "DATA\r\n");
echo 'DATA_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "From: Payroll System <{$from}>\r\nTo: {$to}\r\nSubject: SMTP Test\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nHello from local PHP.\r\n.\r\n");
echo 'SEND_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fputs($socket, "QUIT\r\n");
echo 'QUIT_RESPONSE: ' . trim(fgets($socket, 515)) . PHP_EOL;
fclose($socket);
