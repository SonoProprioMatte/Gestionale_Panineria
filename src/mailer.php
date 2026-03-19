<?php
declare(strict_types=1);

require_once '/var/www/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =============================================
// HELPER — crea e configura istanza PHPMailer
// =============================================
function createMailer(string $toEmail, string $toName): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST']     ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
    $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($_ENV['MAIL_FROM'] ?? $_ENV['MAIL_USERNAME'] ?? '', $_ENV['MAIL_FROM_NAME'] ?? 'Panineria');
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    return $mail;
}

function emailHeader(): string {
    return '
    <tr>
      <td style="background:#b45309;padding:32px;text-align:center;">
        <p style="margin:0;font-size:36px;">🥖</p>
        <h1 style="margin:8px 0 0;color:#fff;font-size:22px;font-weight:700;">Panineria</h1>
      </td>
    </tr>';
}

function emailFooter(): string {
    return '
    <tr>
      <td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0;font-size:12px;color:#9ca3af;">© Panineria — I panini più buoni della città</p>
      </td>
    </tr>';
}

function emailWrapper(string $content): string {
    return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#fffbeb;font-family:Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;padding:40px 0;">
        <tr><td align="center">
          <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
            ' . emailHeader() . '
            <tr><td style="padding:36px 40px;">' . $content . '</td></tr>
            ' . emailFooter() . '
          </table>
        </td></tr>
      </table>
    </body></html>';
}

// =============================================
// 1. EMAIL VERIFICA REGISTRAZIONE
// =============================================
function sendVerificationEmail(string $toEmail, string $toName, string $code): true|string {
    try {
        $mail = createMailer($toEmail, $toName);
        $mail->Subject = '🥖 Il tuo codice di verifica — Panineria';
        $display = substr($code, 0, 3) . ' ' . substr($code, 3);
        $mail->Body = emailWrapper("
            <p style='margin:0 0 8px;font-size:16px;color:#374151;'>Ciao <strong>{$toName}</strong>,</p>
            <p style='margin:0 0 28px;font-size:15px;color:#6b7280;line-height:1.5;'>
                Usa il codice qui sotto per completare la registrazione.<br>
                Valido per <strong>15 minuti</strong>.
            </p>
            <div style='background:#fffbeb;border:2px dashed #f59e0b;border-radius:12px;padding:24px;text-align:center;margin-bottom:28px;'>
                <p style='margin:0 0 4px;font-size:12px;color:#92400e;text-transform:uppercase;letter-spacing:1px;font-weight:600;'>Il tuo codice</p>
                <p style='margin:0;font-size:42px;font-weight:800;color:#b45309;letter-spacing:8px;'>{$display}</p>
            </div>
            <p style='margin:0;font-size:13px;color:#9ca3af;'>Se non hai creato un account su Panineria, ignora questa email.</p>
        ");
        $mail->AltBody = "Ciao {$toName},\n\nIl tuo codice: {$code}\n\nValido per 15 minuti.";
        $mail->send();
        return true;
    } catch (Exception) {
        return $mail->ErrorInfo;
    }
}

// =============================================
// 2. EMAIL ORDINE PRONTO (con scontrino)
// =============================================
function sendOrderReadyEmail(string $toEmail, string $toName, int $orderId, array $items, float $total): true|string {
    try {
        $mail = createMailer($toEmail, $toName);
        $mail->Subject = "✅ Il tuo ordine #{$orderId} è pronto! — Panineria";

        // Costruisci righe scontrino
        $rows = '';
        foreach ($items as $item) {
            $rows .= "
                <tr>
                    <td style='padding:8px 0;border-bottom:1px solid #f3f4f6;'>
                        <p style='margin:0;font-size:14px;color:#374151;font-weight:600;'>{$item['qty']}x {$item['name']}</p>";

            if (!empty($item['customizations'])) {
                foreach ($item['customizations'] as $c) {
                    if ($c['type'] === 'remove') {
                        $rows .= "<p style='margin:2px 0 0;font-size:12px;color:#ef4444;'>− {$c['label']}</p>";
                    } elseif ($c['type'] === 'extra') {
                        $price = $c['price'] > 0 ? " (+€" . number_format((float)$c['price'], 2) . ")" : '';
                        $rows .= "<p style='margin:2px 0 0;font-size:12px;color:#16a34a;'>+ {$c['label']}{$price}</p>";
                    } elseif ($c['type'] === 'variant') {
                        $rows .= "<p style='margin:2px 0 0;font-size:12px;color:#3b82f6;'>{$c['label']}</p>";
                    } elseif ($c['type'] === 'note') {
                        $rows .= "<p style='margin:2px 0 0;font-size:12px;color:#9ca3af;font-style:italic;'>\"{$c['label']}\"</p>";
                    }
                }
            }

            $itemTotal = number_format((float)$item['unit_price'] * $item['qty'], 2);
            $rows .= "
                    </td>
                    <td style='padding:8px 0;border-bottom:1px solid #f3f4f6;text-align:right;vertical-align:top;'>
                        <p style='margin:0;font-size:14px;color:#b45309;font-weight:600;'>€{$itemTotal}</p>
                    </td>
                </tr>";
        }

        $totalFormatted = number_format($total, 2);

        $mail->Body = emailWrapper("
            <p style='margin:0 0 4px;font-size:16px;color:#374151;'>Ciao <strong>{$toName}</strong>,</p>
            <p style='margin:0 0 24px;font-size:15px;color:#6b7280;'>Il tuo ordine è <strong style='color:#16a34a;'>pronto</strong>! Puoi venire a ritirarlo. 🎉</p>

            <div style='background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;'>
                <p style='margin:0 0 12px;font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-weight:600;'>Ordine #{$orderId}</p>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    {$rows}
                    <tr>
                        <td style='padding-top:12px;'>
                            <p style='margin:0;font-size:16px;font-weight:700;color:#374151;'>Totale</p>
                        </td>
                        <td style='padding-top:12px;text-align:right;'>
                            <p style='margin:0;font-size:18px;font-weight:800;color:#b45309;'>€{$totalFormatted}</p>
                        </td>
                    </tr>
                </table>
            </div>

            <p style='margin:0;font-size:13px;color:#9ca3af;'>Grazie per aver scelto Panineria!</p>
        ");
        $mail->AltBody = "Ciao {$toName},\n\nIl tuo ordine #{$orderId} è pronto! Totale: €{$totalFormatted}\n\nGrazie per aver scelto Panineria!";
        $mail->send();
        return true;
    } catch (Exception) {
        return $mail->ErrorInfo;
    }
}

// =============================================
// 3. EMAIL NUOVO ACCESSO
// =============================================
function sendNewLoginEmail(string $toEmail, string $toName): true|string {
    try {
        $mail = createMailer($toEmail, $toName);
        $mail->Subject = '🔔 Nuovo accesso al tuo account — Panineria';

        $date = date('d/m/Y H:i');

        $mail->Body = emailWrapper("
            <p style='margin:0 0 8px;font-size:16px;color:#374151;'>Ciao <strong>{$toName}</strong>,</p>
            <p style='margin:0 0 24px;font-size:15px;color:#6b7280;line-height:1.5;'>
                È stato rilevato un nuovo accesso al tuo account Panineria.
            </p>
            <div style='background:#f9fafb;border-radius:12px;padding:20px;margin-bottom:24px;'>
                <p style='margin:0 0 8px;font-size:13px;color:#6b7280;'>
                    <strong style='color:#374151;'>Data e ora:</strong> {$date}
                </p>
                <p style='margin:0;font-size:13px;color:#6b7280;'>
                    <strong style='color:#374151;'>Account:</strong> {$toEmail}
                </p>
            </div>
            <p style='margin:0;font-size:13px;color:#9ca3af;'>
                Se non sei stato tu, contatta immediatamente l'amministratore.
            </p>
        ");
        $mail->AltBody = "Ciao {$toName},\n\nNuovo accesso al tuo account Panineria il {$date}.\n\nSe non sei stato tu, contatta l'amministratore.";
        $mail->send();
        return true;
    } catch (Exception) {
        return $mail->ErrorInfo;
    }
}
