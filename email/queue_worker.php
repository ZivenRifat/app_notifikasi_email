<?php
/**
 * =======================================================================
 * FILE: email/queue_worker.php
 * DESCRIPTION: Worker untuk mengirim email tertunda (tanpa database)
 * USAGE: php email/queue_worker.php
 * =======================================================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * =========================
 * BASE PATH
 * =========================
 */
$basePath = dirname(__DIR__);

/**
 * =========================
 * PATH CONFIG
 * =========================
 */
$configPath = $basePath . '/config/config.php';
$queueFile  = $basePath . '/storage/email_queue.json';
$logFile    = $basePath . '/logs/email.log';

/**
 * =========================
 * VALIDASI FILE
 * =========================
 */
if (!file_exists($configPath)) {
    die("[ERROR] Config SMTP tidak ditemukan.\n");
}

if (!file_exists($queueFile)) {
    echo "[INFO] Queue kosong.\n";
    exit;
}

/**
 * =========================
 * LOAD CONFIG SMTP
 * =========================
 */
$config = require $configPath;

if (!isset($config['smtp'])) {
    die("[ERROR] Konfigurasi SMTP tidak valid.\n");
}

$smtp = $config['smtp'];

/**
 * =========================
 * LOAD PHPMailer
 * =========================
 */
require_once $basePath . '/vendor/phpmailer/Exception.php';
require_once $basePath . '/vendor/phpmailer/PHPMailer.php';
require_once $basePath . '/vendor/phpmailer/SMTP.php';

/**
 * =========================
 * LOAD QUEUE
 * =========================
 */
$queueData = json_decode(file_get_contents($queueFile), true);

if (!is_array($queueData) || empty($queueData)) {
    echo "[" . date('Y-m-d H:i:s') . "] Queue kosong.\n";
    exit;
}

$now         = time();
$newQueue    = [];
$sentCounter = 0;

/**
 * =========================
 * PROSES QUEUE
 * =========================
 */
foreach ($queueData as $job) {

    // Validasi struktur job
    if (
        !isset(
            $job['email'],
            $job['subject'],
            $job['message'],
            $job['send_at'],
            $job['status']
        )
    ) {
        continue;
    }

    // Jika belum waktunya atau bukan pending → simpan kembali
    if ($job['status'] !== 'pending' || $job['send_at'] > $now) {
        $newQueue[] = $job;
        continue;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = $smtp['port'];
        $mail->SMTPSecure = $smtp['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        // Email content
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($job['email']);
        $mail->isHTML(true);
        $mail->Subject = $job['subject'];
        $mail->Body    = $job['message'];
        $mail->AltBody = strip_tags($job['message']);

        // Kirim email
        $mail->send();
        $sentCounter++;

        // Logging sukses
        if (is_dir(dirname($logFile))) {
            file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] SENT → {$job['email']}\n",
                FILE_APPEND
            );
        }

        echo "[✓] Email terkirim ke {$job['email']}\n";

        // ⚠️ PENTING:
        // Job TIDAK dimasukkan ke $newQueue → artinya dihapus (selesai)

    } catch (Exception $e) {

        // Jika gagal → tandai failed dan simpan ulang
        $job['status'] = 'failed';
        $job['error']  = $mail->ErrorInfo ?: $e->getMessage();

        $newQueue[] = $job;

        echo "[✗] Gagal kirim ke {$job['email']} | {$job['error']}\n";
    }
}

/**
 * =========================
 * SIMPAN QUEUE TERBARU
 * =========================
 */
file_put_contents(
    $queueFile,
    json_encode($newQueue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

echo "Selesai. Email terkirim: {$sentCounter}\n";
