<?php
declare(strict_types=1);

require_once '/var/www/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $toEmail, string $toName, string $code): true|string
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($_ENV['MAIL_FROM'] ?? $_ENV['MAIL_USERNAME'] ?? '', $_ENV['MAIL_FROM_NAME'] ?? 'Panineria');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = '🥖 Il tuo codice di verifica — Panineria';
        $mail->Body    = buildEmailHtml($toName, $code);
        $mail->AltBody = "Ciao $toName,\n\nIl tuo codice: $code\n\nValido 15 minuti.";
        $mail->send();
        return true;
    } catch (Exception) {
        return $mail->ErrorInfo;
    }
}

function buildEmailHtml(string $name, string $code): string
{
    $display = substr($code, 0, 3) . ' ' . substr($code, 3);
    return <<<HTML
    <!DOCTYPE html>
    <html lang="it">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#fffbeb;font-family:Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;padding:40px 0;">
        <tr><td align="center">
          <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
            <tr>
              <td style="background:#b45309;padding:32px;text-align:center;">
                <p style="margin:0;font-size:36px;">🥖</p>
                <h1 style="margin:8px 0 0;color:#fff;font-size:22px;font-weight:700;">Panineria</h1>
              </td>
            </tr>
            <tr>
              <td style="padding:36px 40px;">
                <p style="margin:0 0 8px;font-size:16px;color:#374151;">Ciao <strong>{$name}</strong>,</p>
                <p style="margin:0 0 28px;font-size:15px;color:#6b7280;line-height:1.5;">
                  Usa il codice qui sotto per completare la registrazione.<br>
                  Valido per <strong>15 minuti</strong>.
                </p>
                <div style="background:#fffbeb;border:2px dashed #f59e0b;border-radius:12px;padding:24px;text-align:center;margin-bottom:28px;">
                  <p style="margin:0 0 4px;font-size:12px;color:#92400e;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Il tuo codice</p>
                  <p style="margin:0;font-size:42px;font-weight:800;color:#b45309;letter-spacing:8px;">{$display}</p>
                </div>
                <p style="margin:0;font-size:13px;color:#9ca3af;">Se non hai creato un account su Panineria, ignora questa email.</p>
              </td>
            </tr>
            <tr>
              <td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                <p style="margin:0;font-size:12px;color:#9ca3af;">© Panineria — I panini più buoni della città</p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}
