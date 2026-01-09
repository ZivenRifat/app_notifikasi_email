<?php
/**
 * =======================================================================
 * FILE: email/rate_limit.php
 * DESCRIPTION: Rate limiting email (10 email / 1 menit per email address)
 * =======================================================================
 */

$basePath = dirname(__DIR__);
$logFile  = $basePath . '/logs/email_rate_limit.json';

/**
 * =====================================================
 * CEK RATE LIMIT
 * =====================================================
 */
function checkRateLimit(string $email, int $maxAttempts = 10, int $timeWindow = 60): array
{
    global $logFile;

    $emailKey    = strtolower(trim($email));
    $currentTime = time();

    if (!file_exists($logFile)) {
        return [
            'allowed'       => true,
            'remaining'     => $maxAttempts,
            'reset_time'    => $currentTime + $timeWindow,
            'attempt_count' => 0
        ];
    }

    $fp = fopen($logFile, 'c+');
    flock($fp, LOCK_SH);

    $logs = json_decode(stream_get_contents($fp), true) ?: [];

    flock($fp, LOCK_UN);
    fclose($fp);

    if (!isset($logs[$emailKey])) {
        return [
            'allowed'       => true,
            'remaining'     => $maxAttempts,
            'reset_time'    => $currentTime + $timeWindow,
            'attempt_count' => 0
        ];
    }

    // Ambil hanya attempt dalam 1 menit terakhir
    $attempts = array_filter(
        $logs[$emailKey],
        fn ($ts) => ($currentTime - $ts) < $timeWindow
    );

    $count     = count($attempts);
    $allowed   = $count < $maxAttempts;
    $remaining = max(0, $maxAttempts - $count);
    $resetTime = $count > 0
        ? min($attempts) + $timeWindow
        : $currentTime + $timeWindow;

    return [
        'allowed'       => $allowed,
        'remaining'     => $remaining,
        'reset_time'    => $resetTime,
        'attempt_count' => $count
    ];
}

/**
 * =====================================================
 * CATAT EMAIL TERKIRIM
 * =====================================================
 */
function recordEmailSent(string $email): void
{
    global $logFile;

    $emailKey    = strtolower(trim($email));
    $currentTime = time();

    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $fp = fopen($logFile, 'c+');
    flock($fp, LOCK_EX);

    $logs = json_decode(stream_get_contents($fp), true) ?: [];

    // Inisialisasi jika belum ada
    if (!isset($logs[$emailKey])) {
        $logs[$emailKey] = [];
    }

    // Simpan timestamp baru
    $logs[$emailKey][] = $currentTime;

    // Bersihkan data lama (>1 menit) supaya file tidak membengkak
    $logs[$emailKey] = array_values(
        array_filter(
            $logs[$emailKey],
            fn ($ts) => ($currentTime - $ts) < 60
        )
    );

    ftruncate($fp, 0);
    rewind($fp);
    fwrite(
        $fp,
        json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * =====================================================
 * FORMAT SISA WAKTU BLOKIR
 * =====================================================
 */
function formatTimeRemaining(int $timestamp): string
{
    $remaining = $timestamp - time();

    if ($remaining <= 0) {
        return 'sekarang';
    }

    return $remaining . ' detik';
}
