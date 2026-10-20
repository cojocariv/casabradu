<?php

declare(strict_types=1);

function site_mail(string $to, string $subject, string $body, string $replyTo = '', string $replyName = '', string $from = '', array $attachments = []): bool
{
    $to = str_replace(["\r", "\n"], '', $to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $from = str_replace(["\r", "\n"], '', $from);
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = SITE_EMAIL;
    }
    $replyTo = str_replace(["\r", "\n"], '', $replyTo);
    if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $replyTo = SITE_EMAIL;
    }
    $replyName = trim(str_replace(["\r", "\n", '<', '>', '"'], '', $replyName));
    $replyHeader = $replyName !== '' ? $replyName . ' <' . $replyTo . '>' : $replyTo;
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $attachments = site_mail_clean_attachments($attachments);

    if ($attachments !== [] && !smtp_ready()) {
        site_mail_status('Pentru trimiterea PDF, adaugă parola cutiei comenzi@casabradu.md în configurarea site-ului.');

        return false;
    }

    if (smtp_ready()) {
        site_mail_status('');

        return site_mail_auth($to, $subject, $body, $replyHeader, $from, $attachments);
    }

    $encodedSubject = preg_match('/[^\x20-\x7E]/', $subject) === 1
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;
    if (function_exists('mail')) {
        $headers = "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . 'From: Casa Bradu Caffe <' . $from . ">\r\n"
            . 'Reply-To: ' . $replyHeader . "\r\n";
        $previous = @ini_set('sendmail_from', $from);
        $sent = @mail($to, $encodedSubject, $body, $headers, '-f' . $from);
        if (is_string($previous)) {
            @ini_set('sendmail_from', $previous);
        }
        if ($sent) {
            return true;
        }
    }

    return site_mail_smtp($to, $subject, $body, $replyHeader, $from, $attachments);
}

function site_mail_clean_attachments(array $attachments): array
{
    $clean = [];
    foreach ($attachments as $file) {
        if (!is_array($file)) {
            continue;
        }
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($file['name'] ?? 'fisier.pdf')) ?? 'fisier.pdf';
        $type = (string) ($file['type'] ?? 'application/octet-stream');
        $data = (string) ($file['data'] ?? '');
        if ($name === '' || $data === '') {
            continue;
        }
        if (!preg_match('#^[A-Za-z0-9.+/-]+$#', $type)) {
            $type = 'application/octet-stream';
        }
        $clean[] = [
            'name' => substr($name, 0, 120),
            'type' => $type,
            'data' => $data,
        ];
    }

    return $clean;
}

function site_mail_data(string $to, string $subject, string $body, string $replyHeader, string $from, array $attachments = []): string
{
    $encodedSubject = preg_match('/[^\x20-\x7E]/', $subject) === 1
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;
    $headers = 'Date: ' . date('r') . "\r\n"
        . 'From: Casa Bradu Caffe <' . $from . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Reply-To: ' . $replyHeader . "\r\n"
        . 'Subject: ' . $encodedSubject . "\r\n"
        . "MIME-Version: 1.0\r\n";

    if ($attachments === []) {
        return $headers
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "\r\n"
            . str_replace("\n", "\r\n", $body);
    }

    $boundary = '=_CasaBradu_' . bin2hex(random_bytes(12));
    $message = $headers
        . 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n"
        . "\r\n"
        . '--' . $boundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n"
        . "\r\n"
        . str_replace("\n", "\r\n", $body) . "\r\n";

    foreach ($attachments as $file) {
        $message .= '--' . $boundary . "\r\n"
            . 'Content-Type: ' . $file['type'] . '; name="' . $file['name'] . "\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Content-Disposition: attachment; filename="' . $file['name'] . "\"\r\n"
            . "\r\n"
            . chunk_split(base64_encode($file['data'])) . "\r\n";
    }

    return $message . '--' . $boundary . "--\r\n";
}

function site_mail_smtp(string $to, string $subject, string $body, string $replyHeader, string $from, array $attachments = []): bool
{
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen('127.0.0.1', 25, $errno, $errstr, 5);
    if ($socket === false) {
        return false;
    }
    stream_set_timeout($socket, 8);

    $read = static function () use ($socket): string {
        $data = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return $data;
    };
    $command = static function (string $line, array $codes) use ($socket, $read): bool {
        if ($line !== '') {
            fwrite($socket, $line . "\r\n");
        }
        $code = substr($read(), 0, 3);

        return in_array($code, $codes, true);
    };

    $ok = $command('', ['220']);
    $hello = $ok && $command('EHLO ' . SITE_DOMAIN, ['250']);
    if ($ok && !$hello) {
        $hello = $command('HELO ' . SITE_DOMAIN, ['250']);
    }
    $ok = $hello
        && $command('MAIL FROM:<' . $from . '>', ['250'])
        && $command('RCPT TO:<' . $to . '>', ['250', '251'])
        && $command('DATA', ['354']);

    if (!$ok) {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return false;
    }

    $payload = site_mail_data($to, $subject, $body, $replyHeader, $from, $attachments);
    $lines = explode("\r\n", $payload);
    foreach ($lines as $index => $line) {
        if (isset($line[0]) && $line[0] === '.') {
            $lines[$index] = '.' . $line;
        }
    }
    fwrite($socket, implode("\r\n", $lines) . "\r\n.\r\n");
    $accepted = $command('', ['250']);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return $accepted;
}

function smtp_ready(): bool
{
    return defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS')
        && SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== '';
}

function site_mail_auth(string $to, string $subject, string $body, string $replyHeader, string $from, array $attachments = []): bool
{
    $preferred = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
    if ($preferred < 1 || $preferred > 65535) {
        $preferred = 587;
    }
    $ports = [$preferred];
    foreach ([587, 465] as $port) {
        if (!in_array($port, $ports, true)) {
            $ports[] = $port;
        }
    }

    foreach ($ports as $port) {
        $result = site_mail_auth_port($to, $subject, $body, $replyHeader, $from, $port, $attachments);
        if ($result === 'sent') {
            site_mail_status('');

            return true;
        }
        if ($result === 'auth') {
            site_mail_status('Cutia ' . SMTP_USER . ' a refuzat parola.');

            return false;
        }
        if ($result === 'reject') {
            site_mail_status('Mesajul nu a fost acceptat pentru ' . $to . '.');

            return false;
        }
    }

    site_mail_status('Conexiunea securizată cu serverul de mail a eșuat.');

    return false;
}

function site_mail_status(?string $set = null): string
{
    static $status = '';
    if ($set !== null) {
        $status = $set;
    }

    return $status;
}

function site_mail_auth_port(string $to, string $subject, string $body, string $replyHeader, string $from, int $port, array $attachments = []): string
{
    $host = str_replace(["\r", "\n"], '', SMTP_HOST);
    $user = str_replace(["\r", "\n"], '', SMTP_USER);
    $pass = str_replace(["\r", "\n"], '', SMTP_PASS);
    if ($host === '' || !filter_var($user, FILTER_VALIDATE_EMAIL) || $pass === '') {
        return 'auth';
    }
    $sender = strcasecmp($from, $user) === 0 ? $from : $user;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);
    $errno = 0;
    $errstr = '';
    $remote = ($port === 465 ? 'ssl' : 'tcp') . '://' . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context);
    if ($socket === false) {
        return 'connect';
    }
    stream_set_timeout($socket, 12);

    $read = static function () use ($socket): string {
        $data = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return $data;
    };
    $command = static function (string $line) use ($socket, $read): string {
        if ($line !== '') {
            fwrite($socket, $line . "\r\n");
        }

        return substr($read(), 0, 3);
    };

    $close = static function () use ($socket): void {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    };

    $banner = $read();
    $tlsName = $host;
    if (preg_match('/^220[\s-]+([A-Za-z0-9.-]+\.[A-Za-z]{2,})/', $banner, $match) === 1) {
        $tlsName = $match[1];
    }
    if (substr($banner, 0, 3) !== '220' || $command('EHLO ' . SITE_DOMAIN) !== '250') {
        $close();

        return 'connect';
    }
    if ($port !== 465) {
        if ($command('STARTTLS') !== '220') {
            $close();

            return 'connect';
        }
        stream_context_set_option($socket, 'ssl', 'peer_name', $tlsName);
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        if (!@stream_socket_enable_crypto($socket, true, $crypto)) {
            $close();

            return 'connect';
        }
        if ($command('EHLO ' . SITE_DOMAIN) !== '250') {
            $close();

            return 'connect';
        }
    }

    $authed = false;
    if ($command('AUTH LOGIN') === '334') {
        $authed = $command(base64_encode($user)) === '334' && $command(base64_encode($pass)) === '235';
    } else {
        $authed = $command('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass)) === '235';
    }
    if (!$authed) {
        $close();

        return 'auth';
    }

    $accepted = $command('MAIL FROM:<' . $sender . '>') === '250'
        && in_array($command('RCPT TO:<' . $to . '>'), ['250', '251'], true)
        && $command('DATA') === '354';
    if (!$accepted) {
        $close();

        return 'reject';
    }

    $payload = site_mail_data($to, $subject, $body, $replyHeader, $sender, $attachments);
    $lines = explode("\r\n", $payload);
    foreach ($lines as $index => $line) {
        if (isset($line[0]) && $line[0] === '.') {
            $lines[$index] = '.' . $line;
        }
    }
    fwrite($socket, implode("\r\n", $lines) . "\r\n.\r\n");
    $sent = $command('') === '250';
    $close();

    return $sent ? 'sent' : 'auth';
}
