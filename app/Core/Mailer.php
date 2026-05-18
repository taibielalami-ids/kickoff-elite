<?php

class Mailer
{
    public static function sendText(string $to, string $subject, string $message): array
    {
        $host = trim((string) config('mail.smtp.host', ''));
        $port = (int) config('mail.smtp.port', 587);
        $username = trim((string) config('mail.smtp.username', ''));
        $password = trim((string) config('mail.smtp.password', ''));
        $encryption = trim((string) config('mail.smtp.encryption', 'tls'));
        $auth = (bool) config('mail.smtp.auth', true);

        if ($host === '' || $username === '' || $password === '') {
            return [
                'ok' => false,
                'error' => 'SMTP credentials are missing in config/config.php',
            ];
        }

        require_once BASE_PATH . '/app/ThirdParty/PHPMailer/src/Exception.php';
        require_once BASE_PATH . '/app/ThirdParty/PHPMailer/src/SMTP.php';
        require_once BASE_PATH . '/app/ThirdParty/PHPMailer/src/PHPMailer.php';

        $fromAddress = trim((string) config('mail.from_address', $username));
        if ($fromAddress === '') {
            $fromAddress = $username;
        }
        $fromName = (string) config('mail.from_name', 'KickOff Elite');

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = $auth;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = ($encryption === 'ssl')
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout = 20;

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML(false);

            $mail->send();

            return ['ok' => true, 'error' => ''];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function looksConfigured(): bool
    {
        $host = trim((string) config('mail.smtp.host', ''));
        $username = trim((string) config('mail.smtp.username', ''));
        $password = trim((string) config('mail.smtp.password', ''));
        return $host !== '' && $username !== '' && $password !== '';
    }
}
