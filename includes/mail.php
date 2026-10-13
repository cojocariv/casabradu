<?php

declare(strict_types=1);

function site_mail(string $to, string $subject, string $body, string $replyTo = '', string $replyName = ''): bool
{
    $to = str_replace(["\r", "\n"], '', $to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $replyTo = str_replace(["\r", "\n"], '', $replyTo);
    if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $replyTo = SITE_EMAIL;
    }
    $replyName = trim(str_replace(["\r", "\n", '<', '>', '"'], '', $replyName));
    $replyHeader = $replyName !== '' ? $replyName . ' <' . $replyTo . '>' : $replyTo;
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $encodedSubject = preg_match('/[^\x20-\x7E]/', $subject) === 1
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;

    if (function_exists('mail')) {
        $headers = "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . 'From: Casa Bradu Caffe <' . SITE_EMAIL . ">\r\n"
            . 'Reply-To: ' . $replyHeader . "\r\n";
        $previous = @ini_set('sendmail_from', SITE_EMAIL);
        $sent = @mail($to, $encodedSubject, $body, $headers, '-f' . SITE_EMAIL);
        if (is_string($previous)) {
            @ini_set('sendmail_from', $previous);
        }
        if ($sent) {
            return true;
        }
    }

    return site_mail_smtp($to, $subject, $body, $replyHeader);
}

function site_mail_smtp(string $to, string $subject, string $body, string $replyHeader): bool
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
        && $command('MAIL FROM:<' . SITE_EMAIL . '>', ['250'])
        && $command('RCPT TO:<' . $to . '>', ['250', '251'])
        && $command('DATA', ['354']);

    if (!$ok) {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return false;
    }

    $lines = explode("\n", $body);
    foreach ($lines as $index => $line) {
        if (isset($line[0]) && $line[0] === '.') {
            $lines[$index] = '.' . $line;
        }
    }
    $encodedSubject = preg_match('/[^\x20-\x7E]/', $subject) === 1
        ? '=?UTF-8?B?' . base64_encode($subject) . '?='
        : $subject;
    fwrite(
        $socket,
        'Date: ' . date('r') . "\r\n"
        . 'From: Casa Bradu Caffe <' . SITE_EMAIL . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Reply-To: ' . $replyHeader . "\r\n"
        . 'Subject: ' . $encodedSubject . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n"
        . "\r\n"
        . implode("\r\n", $lines) . "\r\n.\r\n"
    );
    $accepted = $command('', ['250']);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return $accepted;
}
