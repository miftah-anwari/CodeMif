<?php
declare(strict_types=1);

use App\Core\Env;

/**
 * Konfigurasi database. Semua nilai berasal dari file .env.
 * Tidak ada kredensial yang ditulis langsung di dalam kode.
 */
return [
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) Env::get('DB_PORT', 3306),
    'database' => Env::get('DB_NAME', 'codenexa_db'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
    'timezone' => Env::get('DB_TIMEZONE', '+07:00'),
];
