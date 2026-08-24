<?php
// SPST BPS Kota Tegal - Layanan Pengiriman Email Resmi (SMTP & PHPMailer)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config.php';

// Muat autoloader Composer jika tersedia
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Mengirim email menggunakan PHPMailer dengan konfigurasi SMTP
 *
 * @param string $toEmail Alamat email tujuan
 * @param string $toName Nama penerima
 * @param string $subject Judul email
 * @param string $htmlBody Konten email format HTML
 * @param string $altBody Konten email alternatif teks biasa
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): array
{
    // Validasi email penerima
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Alamat email penerima tidak valid.'
        ];
    }

    if (!class_exists(PHPMailer::class)) {
        return [
            'success' => false,
            'message' => 'Library PHPMailer belum terpasang. Jalankan composer install.'
        ];
    }

    $mailDriver = defined('MAIL_DRIVER') ? MAIL_DRIVER : 'smtp';
    $mailHost = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
    $mailPort = defined('MAIL_PORT') ? MAIL_PORT : 587;
    $mailUser = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
    $mailPass = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
    $mailEnc = defined('MAIL_ENCRYPTION') ? strtolower(MAIL_ENCRYPTION) : 'tls';
    $fromAddress = defined('MAIL_FROM_ADDRESS') && !empty(MAIL_FROM_ADDRESS) ? MAIL_FROM_ADDRESS : ($mailUser ?: 'noreply@bps.go.id');
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'SPST BPS Kota Tegal';

    $mail = new PHPMailer(true);

    try {
        // Konfigurasi Server
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        if ($mailDriver === 'smtp') {
            $mail->isSMTP();
            $mail->Host = $mailHost;
            $mail->Port = $mailPort;
            $mail->Timeout = 15; // 15 detik timeout

            // Jika username diisi, aktifkan autentikasi SMTP
            if (!empty($mailUser)) {
                $mail->SMTPAuth = true;
                $mail->Username = $mailUser;
                $mail->Password = $mailPass;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($mailEnc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($mailEnc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // Opsi SSL untuk lingkungan lokal / self-signed certificate
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        } else {
            $mail->isMail();
        }

        // Pengirim & Penerima
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($fromAddress, $fromName);

        // Konten Pesan
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = !empty($altBody) ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n\n"], $htmlBody));

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email berhasil dikirim.'
        ];
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("[MAIL ERROR] Gagal mengirim email ke {$toEmail}: {$errorMsg}");
        
        return [
            'success' => false,
            'message' => "Gagal mengirim email: {$errorMsg}"
        ];
    }
}

/**
 * Mengirim email instruksi reset password dengan template HTML resmi BPS
 *
 * @param string $toEmail
 * @param string $recipientName
 * @param string $resetUrl
 * @return array
 */
function sendResetPasswordEmail(string $toEmail, string $recipientName, string $resetUrl): array
{
    $appName = 'SPST BPS Kota Tegal';
    $safeName = htmlspecialchars($recipientName ?: 'Pengunjung PST', ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $subject = "Instruksi Reset Password Akun - {$appName}";
    $year = date('Y');

    // Desain Template Email HTML Modern & Responsif BPS Kota Tegal
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - SPST BPS Kota Tegal</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b; line-height: 1.6;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 10px;">
    <tr>
      <td align="center">
        <!-- Main Container -->
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.07); border: 1px solid #e2e8f0;">
          
          <!-- Header Banner -->
          <tr>
            <td style="background: linear-gradient(135deg, #002B5B 0%, #004080 100%); padding: 30px 35px; text-align: center;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="center">
                    <div style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; margin-bottom: 4px;">
                      SPST BPS KOTA TEGAL
                    </div>
                    <div style="font-size: 12px; color: #7dd3fc; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;">
                      Sistem Pelayanan Statistik Terpadu
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Body Content -->
          <tr>
            <td style="padding: 35px 35px 25px 35px;">
              <h2 style="color: #0f172a; font-size: 20px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
                Permintaan Atur Ulang Kata Sandi
              </h2>
              
              <p style="font-size: 14px; color: #334155; margin-bottom: 14px;">
                Halo <strong>{$safeName}</strong>,
              </p>
              
              <p style="font-size: 14px; color: #475569; margin-bottom: 24px;">
                Kami menerima permintaan untuk mereset kata sandi (password) akun Anda di portal <strong>SPST BPS Kota Tegal</strong>. Silakan klik tombol di bawah ini untuk membuat kata sandi baru:
              </p>

              <!-- CTA Button -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                <tr>
                  <td align="center">
                    <a href="{$safeUrl}" target="_blank" style="display: inline-block; background-color: #0284c7; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35); text-align: center;">
                      Atur Ulang Kata Sandi Saya
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Notice Box -->
              <div style="background-color: #f8fafc; border-left: 4px solid #0284c7; padding: 14px 16px; border-radius: 6px; margin-bottom: 24px; font-size: 13px; color: #475569;">
                <strong style="color: #0f172a;">Perhatian:</strong>
                <ul style="margin: 6px 0 0 0; padding-left: 18px;">
                  <li>Tautan ini hanya berlaku selama <strong>1 jam</strong>.</li>
                  <li>Jika Anda tidak merasa meminta reset kata sandi, abaikan email ini. Akun Anda tetap aman dan tidak ada perubahan yang dilakukan.</li>
                </ul>
              </div>

              <!-- Fallback Link -->
              <p style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                Jika tombol di atas tidak dapat diklik, salin tautan berikut dan buka di peramban (browser) Anda:
              </p>
              <div style="background-color: #f1f5f9; padding: 10px 14px; border-radius: 8px; font-size: 11px; word-break: break-all; color: #0284c7; border: 1px dashed #cbd5e1; font-family: monospace;">
                <a href="{$safeUrl}" target="_blank" style="color: #0284c7; text-decoration: underline;">{$safeUrl}</a>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 22px 35px; border-top: 1px solid #e2e8f0; text-align: center;">
              <p style="font-size: 12px; font-weight: 700; color: #002B5B; margin: 0 0 6px 0;">
                Badan Pusat Statistik Kota Tegal
              </p>
              <p style="font-size: 11px; color: #475569; margin: 0 0 4px 0; line-height: 1.5;">
                Jl. Nakula No. 36A, Kejambon, Kec. Tegal Timur, Kota Tegal, Jawa Tengah 52124<br>
                Telp: (0283) 351593 | Email: <a href="mailto:bps3376@bps.go.id" style="color: #0284c7; text-decoration: none;">bps3376@bps.go.id</a> | Web: <a href="https://tegalkota.bps.go.id" target="_blank" style="color: #0284c7; text-decoration: none;">tegalkota.bps.go.id</a>
              </p>
              <p style="font-size: 10px; color: #94a3b8; margin: 8px 0 0 0;">
                &copy; {$year} SPST BPS Kota Tegal. Seluruh Hak Cipta Dilindungi.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    // Plain text fallback
    $altBody = "Halo {$recipientName},\n\n"
        . "Kami menerima permintaan untuk mereset kata sandi akun Anda di SPST BPS Kota Tegal.\n\n"
        . "Silakan buka tautan berikut untuk membuat kata sandi baru (berlaku 1 jam):\n"
        . "{$resetUrl}\n\n"
        . "Jika Anda tidak merasa meminta reset kata sandi, abaikan pesan ini.\n\n"
        . "---\n"
        . "Badan Pusat Statistik Kota Tegal\n"
        . "Jl. Nakula No. 36A, Kota Tegal, Jawa Tengah 52124\n"
        . "Telp: (0283) 351593 | Email: bps3376@bps.go.id\n"
        . "Website: https://tegalkota.bps.go.id";

    return sendMail($toEmail, $recipientName, $subject, $htmlBody, $altBody);
}
