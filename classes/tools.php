<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Tools
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }


    // -------------- SANITIZE INPUT ----------------//
    public function sanitizeInput($input)
    {
        $input = trim((string) $input);
        $input = stripslashes($input);

        return $input;
    }

    public function escapeForHtml($input)
    {
        return htmlspecialchars($this->sanitizeInput($input), ENT_QUOTES, 'UTF-8');
    }

    public function formatRichText($input): string
    {
        $sanitized = $this->sanitizeInput($input);
        if ($sanitized === '') {
            return '';
        }

        $escaped = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $linked = preg_replace_callback(
            '/((?:https?:\/\/|www\.)[^\s<]+)/i',
            static function (array $matches): string {
                $label = rtrim($matches[1], ".,;:!?)]}");
                $trailing = substr($matches[1], strlen($label));
                $href = preg_match('/^https?:\/\//i', $label) === 1 ? $label : 'https://' . $label;

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="font-bold underline break-all">'
                    . $label
                    . '</a>'
                    . $trailing;
            },
            $escaped
        );

        return nl2br($linked ?? $escaped, false);
    }

    public function formatExcerpt($input, int $maxLength = 140): string
    {
        $sanitized = preg_replace('/\s+/u', ' ', $this->sanitizeInput($input)) ?? '';
        $sanitized = trim($sanitized);

        if ($sanitized === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($sanitized, 'UTF-8') > $maxLength) {
                $sanitized = rtrim(mb_substr($sanitized, 0, max(1, $maxLength - 1), 'UTF-8')) . '…';
            }
        } elseif (strlen($sanitized) > $maxLength) {
            $sanitized = rtrim(substr($sanitized, 0, max(1, $maxLength - 1))) . '...';
        }

        return htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
    }

    public function escapeForSql($input)
    {
        if (!$this->connection instanceof mysqli) {
            throw new RuntimeException('Database connection is not available.');
        }

        return $this->connection->real_escape_string($this->sanitizeInput($input));
    }

    // --------------  GENERATE RANDOM USER ID ----------------//
    public function generateUserId()
    {
        return str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    }


    // --------------  GENERATE REFERRAL CODE ----------------//
    public function generateReferralCode($length = 6)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }


    // --------------  ALERT ----------------//
    public function alert()
    {
        if (isset($_SESSION["alert"])) {
            include __DIR__ . '/../components/alert-modal.php';

            unset($_SESSION["alert"]);
        }
    }

    // --------------  SEND EMAIL ----------------//
    public function sendEmail($content, $to, $subject)
    {
        if (!class_exists(PHPMailer::class)) {
            return [
                'success' => false,
                'err' => new RuntimeException('PHPMailer is not installed. Run composer install to enable email sending.')
            ];
        }

        $mail = new PHPMailer(true);
        $mailHost = Env::get('MAIL_HOST');
        $mailPort = Env::getInt('MAIL_PORT', 587);
        $mailEncryption = strtolower(Env::get('MAIL_ENCRYPTION', 'tls'));
        $mailUsername = Env::get('MAIL_USERNAME');
        $mailPassword = Env::get('MAIL_PASSWORD');
        $mailFromAddress = Env::get('MAIL_FROM_ADDRESS', $mailUsername);
        $mailFromName = Env::get('MAIL_FROM_NAME', Env::get('APP_NAME', 'Pixelwar'));

        if ($mailHost === null || $mailUsername === null || $mailPassword === null || $mailFromAddress === null) {
            return [
                'success' => false,
                'err' => new RuntimeException('Mail configuration is incomplete.')
            ];
        }

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = $mailUsername;
            $mail->Password = $mailPassword;
            $mail->SMTPSecure = $mailEncryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $mailPort;

            // Recipients
            $mail->setFrom($mailFromAddress, $mailFromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;

            $mail->send();
            return [
                'success' => true,
                'err' => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'err' => $e
            ];
        }
    }


    // --------------  MASK EMAIL ----------------//
    public function maskEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false; // invalid email
        }

        list($username, $domain) = explode('@', $email);

        // Always show the first character of the username
        $visibleChar = substr($username, 0, 1);
        $maskedPart = str_repeat('*', max(strlen($username) - 1, 0));

        return $visibleChar . $maskedPart . '@' . $domain;
    }




    // --------------  FORMAT TIMESTAMP  ----------------//
    public function formatTimestamp($timestamp, $format = "F j, Y h:i a")
    {
        if (!is_numeric($timestamp) || strlen($timestamp) != 10) {
            return false; // invalid input
        }

        return date($format, $timestamp);
    }

    // --------------  MASK A USERNAME  ----------------//
    public function maskUsername($username)
    {
        $length = strlen($username);

        if ($length <= 1) {
            return str_repeat('*', $length);
        }

        if ($length == 2) {
            return $username[0] . '*';
        }

        if ($length <= 4) {
            return $username[0] . str_repeat('*', $length - 2) . $username[$length - 1];
        }

        // For usernames longer than 4 characters
        return $username[0] . '*' . '...' . '*' . $username[$length - 1];
    }

}
