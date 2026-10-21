<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Akses konfigurasi dengan "dot notation", contoh: Config::get('app.name').
 */
final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file  = array_shift($parts);

        if (!isset(self::$cache[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            self::$cache[$file] = is_file($path) ? (array) require $path : [];
        }

        $value = self::$cache[$file];

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
