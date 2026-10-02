<?php

declare(strict_types=1);

$server = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
if ($server === false) {
    throw new RuntimeException($message);
}
echo stream_socket_get_name($server, false)."\n";
flush();
$client = stream_socket_accept($server, 15);
if ($client === false) {
    throw new RuntimeException('SMTP de prueba sin conexión.');
}
stream_set_timeout($client, 10);
fwrite($client, "220 localhost SMTP test\r\n");
$body = '';
while (($line = fgets($client)) !== false) {
    if (str_starts_with($line, 'EHLO') || str_starts_with($line, 'HELO')) {
        fwrite($client, "250 localhost\r\n");
    } elseif (str_starts_with($line, 'MAIL FROM:') || str_starts_with($line, 'RCPT TO:')) {
        fwrite($client, "250 OK\r\n");
    } elseif (trim($line) === 'DATA') {
        fwrite($client, "354 Send message\r\n");
        while (($data = fgets($client)) !== false && $data !== ".\r\n") {
            $body .= $data;
        }
        fwrite($client, "250 Accepted\r\n");
        echo str_contains(quoted_printable_decode($body), 'http://localhost/verificar/test-token') ? "LINK_RECEIVED\n" : "LINK_MISSING\n";
        flush();
    } elseif (trim($line) === 'QUIT') {
        fwrite($client, "221 Bye\r\n");
        break;
    } else {
        fwrite($client, "250 OK\r\n");
    }
}
fclose($client);
fclose($server);
