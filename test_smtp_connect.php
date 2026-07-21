<?php
$ctx = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);
$socket = stream_socket_client('ssl://smtp.gmail.com:465', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx);
if (!$socket) {
    echo 'ERR: ' . $errno . ' ' . $errstr . PHP_EOL;
    exit(1);
}
echo 'CONNECTED' . PHP_EOL;
echo fgets($socket, 515) . PHP_EOL;
fputs($socket, "EHLO localhost\r\n");
echo fgets($socket, 515) . PHP_EOL;
fclose($socket);
