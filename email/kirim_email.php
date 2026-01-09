<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

/**
 * =========================
 * HELPER
 * =========================
 */
function sendJsonResponse($success, $message)
{
    ob_clean();
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * =========================
 * VALIDASI METHOD
 * =========================
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method tidak diizinkan.');
}

/**
 * =========================
 * VALIDASI INPUT
 * =========================
 */
$email = trim($_POST['email'] ?? '');
$type  = $_POST['type'] ?? 'now';

if ($email === '') {
    sendJsonResponse(false, 'Email tidak boleh kosong.');
}

if (!isValidEmail($email)) {
    sendJsonResponse(false, 'Format email tidak valid.');
}

/**
 * =========================
 * RATE LIMIT
 * =========================
 */
require_once __DIR__ . '/rate_limit.php';

$rateLimit = checkRateLimit($email, 10, 60);

if (!$rateLimit['allowed']) {
    $reset = formatTimeRemaining($rateLimit['reset_time']);
    sendJsonResponse(
        false,
        "Batas pengiriman tercapai. Coba lagi dalam {$reset}."
    );
}

/**
 * =========================
 * KIRIM LANGSUNG (NOW)
 * =========================
 */
if ($type === 'now') {

    $basePath = dirname(__DIR__);
    $config   = require $basePath . '/config/config.php';

    if (!isset($config['smtp'])) {
        sendJsonResponse(false, 'Konfigurasi SMTP tidak ditemukan.');
    }

    $smtp = $config['smtp'];

    require_once $basePath . '/vendor/phpmailer/Exception.php';
    require_once $basePath . '/vendor/phpmailer/PHPMailer.php';
    require_once $basePath . '/vendor/phpmailer/SMTP.php';

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = $smtp['port'];
        $mail->SMTPSecure = $smtp['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Notifikasi Email';
        $mail->Body    = '<h3>Halo!</h3><p>Email ini dikirim langsung.</p>';
        $mail->AltBody = 'Email ini dikirim langsung.';

        $mail->send();

        // CATAT RATE LIMIT (SETELAH BERHASIL)
        recordEmailSent($email);

        sendJsonResponse(true, 'Email berhasil dikirim sekarang.');
    } catch (Exception $e) {
        sendJsonResponse(false, 'Gagal mengirim email.');
    }
}

/**
 * =========================
 * MASUK QUEUE (DELAY)
 * =========================
 */
$now = time();

switch ($type) {
    case '5min':
        $sendAt = $now + 300;
        $label  = '5 menit';
        break;
    case '12hour':
        $sendAt = $now + 43200;
        $label  = '12 jam';
        break;
    default:
        sendJsonResponse(false, 'Tipe pengiriman tidak valid.');
}

$queueFile = dirname(__DIR__) . '/storage/email_queue.json';

if (!is_dir(dirname($queueFile))) {
    mkdir(dirname($queueFile), 0755, true);
}

$queue = file_exists($queueFile)
    ? json_decode(file_get_contents($queueFile), true)
    : [];

$queue[] = [
    'id'      => uniqid(),
    'email'   => $email,
    'subject' => 'Notifikasi Email',
    'message' => '<h3>Halo!</h3><p>Email ini dikirim terjadwal.</p>',
    'send_at' => $sendAt,
    'status'  => 'pending'
];

file_put_contents(
    $queueFile,
    json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

// CATAT RATE LIMIT (SETELAH MASUK QUEUE)
recordEmailSent($email);

sendJsonResponse(true, "Email dijadwalkan dan akan dikirim {$label}.");
