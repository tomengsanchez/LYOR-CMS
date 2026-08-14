<?php
namespace Core;

use App\Models\AppSettings;

class Mailer
{
    /**
     * @param string $to
     * @param string $subject
     * @param string $body Plain text or HTML depending on $isHtml
     * @param bool $isHtml If true, send as text/html for rich formatting (e.g. highlighted changes).
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = false): array
    {
        $config = AppSettings::getEmailConfig();
        $provider = strtolower(trim((string) ($config->email_provider ?? 'smtp')));

        // 'log' provider: write to email log and report success without contacting a remote MTA.
        // Used for local dev and E2E so flows that depend on email (2FA, notifications) do not
        // fail when SMTP is unconfigured. Set email_provider = 'log' in app_settings to enable.
        if ($provider === 'log') {
            Logger::email('Sent (log provider)', [
                'provider' => 'log',
                'to' => $to,
                'subject' => $subject,
                'preview' => substr($body, 0, 256),
            ]);
            return ['success' => true];
        }

        if ($provider === 'mailersend') {
            return self::sendViaMailerSend($config, $to, $subject, $body, $isHtml);
        }

        return self::sendViaSmtp($config, $to, $subject, $body, $isHtml);
    }

    private static function sendViaSmtp(object $config, string $to, string $subject, string $body, bool $isHtml): array
    {
        if (empty($config->smtp_host)) {
            Logger::email('Send failed', ['provider' => 'smtp', 'to' => $to, 'subject' => $subject, 'error' => 'SMTP host is not configured.']);
            return ['success' => false, 'error' => 'SMTP host is not configured.'];
        }
        $encryption = strtolower($config->smtp_encryption ?? '');
        $host = $config->smtp_host;
        $port = (int) $config->smtp_port ?: 587;
        $secure = ($encryption === 'ssl');
        if ($encryption === 'ssl' && $port === 587) {
            $port = 465;
        }
        $prefix = $secure ? 'ssl://' : '';
        $fp = @stream_socket_client(
            $prefix . $host . ':' . $port,
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => false]])
        );
        if (!$fp) {
            Logger::email('Send failed', ['provider' => 'smtp', 'to' => $to, 'subject' => $subject, 'error' => "Connection failed: $errstr ($errno)"]);
            return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
        }

        stream_set_timeout($fp, 10);
        $read = function () use ($fp) {
            $r = '';
            while ($line = fgets($fp, 515)) {
                $r .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $r;
        };
        $write = function ($cmd) use ($fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $read();
        $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $read();
        if ($encryption === 'tls' && !$secure) {
            $write('STARTTLS');
            $read();
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $read();
        }
        if (!empty($config->smtp_username)) {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode($config->smtp_username));
            $read();
            $write(base64_encode($config->smtp_password ?? ''));
            $resp = $read();
            if (strpos($resp, '235') === false && strpos($resp, 'Authentication successful') === false) {
                fclose($fp);
                Logger::email('Send failed', ['provider' => 'smtp', 'to' => $to, 'subject' => $subject, 'error' => 'SMTP authentication failed.', 'response' => trim($resp)]);
                return ['success' => false, 'error' => 'SMTP authentication failed.'];
            }
        }
        $from = $config->from_email ?: $config->smtp_username ?: 'noreply@localhost';
        $fromName = $config->from_name ?: 'PAPeR';
        $write('MAIL FROM:<' . $from . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();
        $contentType = $isHtml ? 'text/html' : 'text/plain';
        $headers = "From: " . ($fromName ? "\"$fromName\" <$from>" : $from) . "\r\n";
        $headers .= "To: $to\r\nSubject: $subject\r\nMIME-Version: 1.0\r\nContent-Type: {$contentType}; charset=UTF-8\r\n\r\n";
        $write($headers . $body . "\r\n.");
        $resp = $read();
        $write('QUIT');
        fclose($fp);
        if (strpos($resp, '250') !== false) {
            Logger::email('Sent', ['provider' => 'smtp', 'to' => $to, 'subject' => $subject, 'response' => trim($resp)]);
            return ['success' => true];
        }
        Logger::email('Send failed', ['provider' => 'smtp', 'to' => $to, 'subject' => $subject, 'error' => trim($resp), 'response' => trim($resp)]);
        return ['success' => false, 'error' => trim($resp)];
    }

    private static function sendViaMailerSend(object $config, string $to, string $subject, string $body, bool $isHtml): array
    {
        $token = trim((string) ($config->mailersend_api_token ?? ''));
        if ($token === '') {
            Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => 'MailerSend API token is not configured.']);
            return ['success' => false, 'error' => 'MailerSend API token is not configured.'];
        }

        $fromEmail = trim((string) ($config->mailersend_from_email ?? ''));
        if ($fromEmail === '') {
            $fromEmail = trim((string) ($config->from_email ?? ''));
        }
        if ($fromEmail === '') {
            Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => 'MailerSend sender email is not configured.']);
            return ['success' => false, 'error' => 'MailerSend sender email is not configured.'];
        }

        $fromName = trim((string) ($config->mailersend_from_name ?? ''));
        if ($fromName === '') {
            $fromName = trim((string) ($config->from_name ?? ''));
        }
        if ($fromName === '') {
            $fromName = 'PAPeR';
        }

        $payload = [
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'to' => [['email' => $to]],
            'subject' => $subject,
        ];
        if ($isHtml) {
            $payload['html'] = $body;
        } else {
            $payload['text'] = $body;
        }
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => 'Failed to encode MailerSend payload.']);
            return ['success' => false, 'error' => 'Failed to encode MailerSend payload.'];
        }

        $ch = curl_init('https://api.mailersend.com/v1/email');
        if ($ch === false) {
            Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => 'Unable to initialize HTTP client.']);
            return ['success' => false, 'error' => 'Unable to initialize HTTP client.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => 'MailerSend request failed: ' . $curlErr]);
            return ['success' => false, 'error' => 'MailerSend request failed: ' . $curlErr];
        }
        if ($status >= 200 && $status < 300) {
            Logger::email('Sent', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'status' => $status]);
            return ['success' => true];
        }

        $err = 'MailerSend API error (HTTP ' . $status . ')';
        if (is_string($responseBody) && trim($responseBody) !== '') {
            $err .= ': ' . substr(trim($responseBody), 0, 400);
        }
        Logger::email('Send failed', ['provider' => 'mailersend', 'to' => $to, 'subject' => $subject, 'error' => $err, 'status' => $status]);
        return ['success' => false, 'error' => $err];
    }
}
