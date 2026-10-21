<?php
declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

$password = getenv('CODEMIF_SUPER_ADMIN_PASSWORD');
if (!is_string($password) || $password === '') {
    fwrite(STDERR, "Atur CODEMIF_SUPER_ADMIN_PASSWORD sementara sebelum menjalankan skrip ini.\n");
    exit(1);
}

putenv('CODEMIF_SUPER_ADMIN_PASSWORD');
unset($_ENV['CODEMIF_SUPER_ADMIN_PASSWORD'], $_SERVER['CODEMIF_SUPER_ADMIN_PASSWORD']);

if (strlen($password) < 12) {
    fwrite(STDERR, "Password awal minimal 12 karakter.\n");
    exit(1);
}

try {
    $pdo = Database::connection();
    $columns = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'role'")->fetch();
    if (!$columns || !str_contains((string)$columns['Type'], "'super_admin'")) {
        throw new RuntimeException('Jalankan database/migrate_super_admin.sql terlebih dahulu.');
    }

    $passwordColumn = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'must_change_password'")->fetch();
    if (!$passwordColumn) {
        throw new RuntimeException('Kolom penggantian password belum tersedia. Jalankan database/migrate_super_admin.sql.');
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->execute(['admin', 'admin@codemif.invalid']);
    if ($check->fetch()) {
        throw new RuntimeException('Username admin atau email bootstrap sudah terpakai. Skrip tidak mengubah akun yang ada.');
    }

    $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, must_change_password, status) VALUES (?, ?, ?, ?, 'super_admin', 1, 'active')");
    $insert->execute([
        'Miftah',
        'admin',
        'admin@codemif.invalid',
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    fwrite(STDOUT, "Akun Super Admin berhasil dibuat. Username: admin. Ganti password setelah login pertama.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
} finally {
    $password = '';
}
