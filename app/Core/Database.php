<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Koneksi database terpusat (singleton).
 * Semua kelas lain cukup memanggil Database::connection().
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );

        try {
            $pdo = new PDO($dsn, (string) $cfg['username'], (string) $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);

            $tz = preg_match('/^[+-]\d{2}:\d{2}$/', (string) $cfg['timezone']) ? $cfg['timezone'] : '+00:00';
            $pdo->exec("SET time_zone = '" . $tz . "'");
        } catch (PDOException $e) {
            // Detail teknis hanya masuk log, tidak ke browser.
            Logger::error('Koneksi database gagal', [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Tidak dapat terhubung ke database.', 500, $e);
        }

        return self::$pdo = $pdo;
    }

    /** Cek cepat apakah koneksi berhasil. */
    public static function ping(): bool
    {
        try {
            return (int) self::connection()->query('SELECT 1')->fetchColumn() === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Jalankan fungsi di dalam transaksi; rollback otomatis jika terjadi error. */
    public static function transaction(callable $callback)
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
