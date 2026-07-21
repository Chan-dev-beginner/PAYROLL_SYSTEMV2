<?php
class PHPMailer
{
    public $Host = 'localhost';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 25;
    public $From = '';
    public $FromName = 'Payroll System';
    public $CharSet = 'UTF-8';
    public $isHTML = false;
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $SMTPOptions = [];
    public $ErrorInfo = '';
    public $to = [];
    public $Mailer = 'smtp';

    public function isSMTP() { $this->Mailer = 'smtp'; }
    public function isMail() { $this->Mailer = 'mail'; }
    public function isHTML($isHtml = true) { $this->isHTML = $isHtml; }
    public function addAddress($address, $name = '') { $this->to[] = ['address' => $address, 'name' => $name]; }
    public function send()
    {
        if (empty($this->From)) {
            $this->From = $this->Username ?: 'no-reply@payroll.local';
        }

        $host = $this->Host ?: 'smtp.gmail.com';
        $port = (int)($this->Port ?: 465);
        $secure = strtolower((string)($this->SMTPSecure ?: 'ssl'));
        $remoteHost = ($secure === 'ssl' ? 'ssl://' : '') . $host;

        $context = null;
        if ($secure === 'ssl' || $secure === 'tls') {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);
        }

        $socket = stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $this->ErrorInfo = 'SMTP connection failed: ' . $errstr;
            return false;
        }

        $readResponse = function () use ($socket): string {
            $lines = [];
            while (true) {
                $line = fgets($socket, 515);
                if ($line === false || $line === '') {
                    break;
                }

                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $lines[] = $line;
                if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                    break;
                }
            }

            return implode("\n", $lines);
        };

        $sendCommand = function (string $command) use ($socket, $readResponse): string {
            fputs($socket, $command . "\r\n");
            return $readResponse();
        };

        $greeting = $readResponse();
        if (strpos($greeting, '220') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP server rejected the connection.';
            return false;
        }

        $response = $sendCommand('EHLO localhost');
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP EHLO failed: ' . $response;
            return false;
        }

        if ($secure === 'tls') {
            $response = $sendCommand('STARTTLS');
            if (strpos($response, '220') !== 0) {
                fclose($socket);
                $this->ErrorInfo = 'SMTP STARTTLS failed: ' . $response;
                return false;
            }

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                $this->ErrorInfo = 'Unable to enable TLS.';
                return false;
            }

            $response = $sendCommand('EHLO localhost');
            if (strpos($response, '250') !== 0) {
                fclose($socket);
                $this->ErrorInfo = 'SMTP EHLO after STARTTLS failed: ' . $response;
                return false;
            }
        }

        $response = $sendCommand('AUTH LOGIN');
        if (strpos($response, '334') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP AUTH LOGIN failed: ' . $response;
            return false;
        }

        $response = $sendCommand(base64_encode($this->Username));
        if (strpos($response, '334') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP username rejected: ' . $response;
            return false;
        }

        $response = $sendCommand(base64_encode($this->Password));
        if (strpos($response, '235') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP authentication failed. Check your Gmail App Password.';
            return false;
        }

        $toList = [];
        foreach ($this->to as $recipient) {
            $toList[] = empty($recipient['name']) ? $recipient['address'] : '"' . addslashes($recipient['name']) . '" <' . $recipient['address'] . '>';
        }

        if (empty($toList)) {
            fclose($socket);
            $this->ErrorInfo = 'No recipients were provided.';
            return false;
        }

        $response = $sendCommand('MAIL FROM: <' . $this->From . '>');
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP MAIL FROM failed: ' . $response;
            return false;
        }

        foreach ($toList as $recipient) {
            $recipientEmail = preg_replace('/.*<([^>]+)>.*$/', '$1', $recipient);
            $response = $sendCommand('RCPT TO: <' . $recipientEmail . '>');
            if (strpos($response, '250') !== 0 && strpos($response, '251') !== 0) {
                fclose($socket);
                $this->ErrorInfo = 'SMTP RCPT TO failed: ' . $response;
                return false;
            }
        }

        $response = $sendCommand('DATA');
        if (strpos($response, '354') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP DATA failed: ' . $response;
            return false;
        }

        $headers = [];
        $headers[] = 'From: ' . $this->FromName . ' <' . $this->From . '>';
        $headers[] = 'To: ' . implode(', ', $toList);
        $headers[] = 'Subject: ' . $this->Subject;
        if ($this->isHTML) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        $headers[] = 'Reply-To: ' . $this->From;
        $headers[] = 'MIME-Version: 1.0';

        $message = $this->Body;
        if (!empty($this->AltBody) && !$this->isHTML) {
            $message = $this->AltBody;
        }

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.";
        fputs($socket, $data . "\r\n");
        $response = $readResponse();
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            $this->ErrorInfo = 'SMTP message send failed: ' . $response;
            return false;
        }

        $sendCommand('QUIT');
        fclose($socket);
        return true;
    }
}
