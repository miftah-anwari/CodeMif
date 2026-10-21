<?php
declare(strict_types=1);

use App\Core\Env;

/**
 * Konfigurasi umum aplikasi.
 * Nilai dibaca dari file .env sehingga mudah dibedakan
 * antara development dan production.
 */
return [
    'name'     => Env::get('APP_NAME', 'CodeMif'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => (bool) Env::get('APP_DEBUG', false),
    'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Jakarta'),
];
