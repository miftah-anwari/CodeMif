<?php
declare(strict_types=1);

/**
 * Bootstrap: dipanggil di awal setiap halaman publik.
 * Mengatur path, autoloader, .env, timezone, dan penanganan error.
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register(BASE_PATH . '/app');

\App\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) \App\Core\Config::get('app.timezone', 'Asia/Jakarta'));

$debug = (bool) \App\Core\Config::get('app.debug', false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(static function (\Throwable $e) use ($debug): void {
    \App\Core\Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><meta charset="utf-8"><title>Terjadi kesalahan</title>';
    echo '<body style="font-family:sans-serif;background:#0B1020;color:#F8FAFC;padding:40px">';
    echo '<h1>Terjadi kesalahan pada server</h1>';
    echo '<p>Silakan coba lagi beberapa saat lagi.</p>';

    if ($debug) {
        echo '<pre style="background:#121A2B;padding:16px;border-radius:8px;color:#F87171">'
            . htmlspecialchars($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8')
            . '</pre>';
    }

    echo '</body>';
});
