<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Membaca file .env sederhana (KEY=VALUE) tanpa library tambahan.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Hapus komentar di akhir baris untuk nilai tanpa tanda kutip.
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = rtrim(substr($value, 0, $hashPos));
                }
            }

            // Hapus tanda kutip pembungkus.
            if (strlen($value) >= 2 && $value[0] === $value[strlen($value) - 1]
                && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                self::$values[$key] = $value;
            }
        }
    }

    /**
     * Ambil nilai konfigurasi. "true"/"false"/"null" dikonversi otomatis.
     *
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$values[$key] ?? null;

        if ($value === null) {
            $env = getenv($key);
            $value = ($env !== false) ? $env : null;
        }

        if ($value === null) {
            return $default;
        }

        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }
}
