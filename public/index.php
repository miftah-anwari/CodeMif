<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\CourseTutor;
use App\Services\CourseTutorException;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('codenexa_session');
    session_start();
}

$pdo = Database::connection();
$baseUrl = rtrim((string) \App\Core\Config::get('app.url', '/codenexa/public'), '/');
if ($baseUrl === '') $baseUrl = '/codenexa/public';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function url(string $page = 'dashboard', array $params = []): string {
    global $baseUrl;
    return $baseUrl . '/?page=' . urlencode($page) . ($params ? '&' . http_build_query($params) : '');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}
function current_user(): ?array {
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}
function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: ' . url('login'));
        exit;
    }
    return $user;
}
function require_admin(): array {
    $user = require_login();
    if (!in_array(($user['role'] ?? ''), ['admin', 'super_admin'], true)) {
        flash('error', 'Halaman ini hanya dapat diakses oleh administrator.');
        redirect('dashboard');
    }
    return $user;
}
function require_active_school_admin(\PDO $pdo, ?array $user): ?array {
    if (!$user) return null;
    $stmt = $pdo->prepare('SELECT status, must_change_password FROM users WHERE id=?');
    $stmt->execute([$user['id']]);
    $account = $stmt->fetch();
    if (!$account || $account['status'] !== 'active') {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        flash('error', ($user['role'] ?? '') === 'school_admin'
            ? 'Akun admin sekolah sedang dinonaktifkan. Hubungi Super Admin.'
            : 'Akun Anda sudah dinonaktifkan atau dihapus. Hubungi administrator jika ini tidak sesuai.');
        redirect('login');
    }
    if (($user['role'] ?? '') === 'school_admin') {
        $user['must_change_password'] = (int)$account['must_change_password'];
        $_SESSION['user']['must_change_password'] = $user['must_change_password'];
    }
    return $user;
}
function approval_schema_ready(\PDO $pdo): bool {
    $columns = $pdo->query('SHOW COLUMNS FROM lesson_progress')->fetchAll();
    $types = [];
    foreach ($columns as $column) {
        $types[$column['Field']] = $column['Type'];
    }
    return isset($types['submitted_at'], $types['reviewed_by'], $types['reviewed_at'], $types['review_note'], $types['evidence_image'], $types['status'])
        && str_contains($types['status'], "'pending'")
        && str_contains($types['status'], "'rejected'");
}
function practice_schema_ready(\PDO $pdo): bool {
    foreach ($pdo->query('SHOW COLUMNS FROM lesson_progress')->fetchAll() as $column) {
        if ($column['Field'] === 'submitted_code') return true;
    }
    return false;
}
function learning_activity_schema_ready(\PDO $pdo): bool {
    foreach ($pdo->query('SHOW COLUMNS FROM enrollments')->fetchAll() as $column) {
        if ($column['Field'] === 'last_activity_at') return true;
    }
    return false;
}
function school_admin_schema_ready(\PDO $pdo): bool {
    $tables = $pdo->query("SHOW TABLES LIKE 'schools'")->fetchColumn();
    if (!$tables) return false;
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $column) {
        $columns[$column['Field']] = $column['Type'];
    }
    return isset($columns['school_id'], $columns['role'])
        && str_contains($columns['role'], "'school_admin'");
}
function school_class_schema_ready(\PDO $pdo): bool {
    if (!$pdo->query("SHOW TABLES LIKE 'school_classes'")->fetchColumn()) return false;
    foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $column) {
        if ($column['Field'] === 'class_id') return true;
    }
    return false;
}
function student_grade_schema_ready(\PDO $pdo): bool {
    foreach ($pdo->query('SHOW COLUMNS FROM users')->fetchAll() as $column) {
        if ($column['Field'] === 'grade_level') return true;
    }
    return false;
}
function school_course_visibility_schema_ready(\PDO $pdo): bool {
    return (bool)$pdo->query("SHOW TABLES LIKE 'school_course_visibility'")->fetchColumn();
}
function school_course_visibility_clause(bool $schemaReady, string $courseAlias = 'c'): string {
    if (!$schemaReady) return '';
    return " AND NOT EXISTS (SELECT 1 FROM school_course_visibility scv WHERE scv.course_id={$courseAlias}.id AND scv.school_id=(SELECT school_id FROM users WHERE id=?) AND scv.is_visible=0)";
}
function course_art_key(array $course): string {
    $slug = strtolower((string)($course['slug'] ?? ''));
    $language = strtolower((string)($course['language'] ?? ''));
    if (str_contains($slug, 'html') || $language === 'html') return 'html';
    if (str_contains($slug, 'css') || $language === 'css') return 'css';
    if (str_contains($slug, 'javascript') || $language === 'javascript') return 'javascript';
    if (str_contains($slug, 'python') || $language === 'python') return 'python';
    if (str_contains($slug, 'php') || $language === 'php') return 'php';
    if (str_contains($slug, 'logika') || str_contains($slug, 'algoritma')) return 'logic';
    return 'code';
}
function store_lesson_evidence(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran foto maksimal 5 MB.',
            UPLOAD_ERR_NO_FILE => 'Pilih foto hasil tugas sebelum mengirim.',
            default => 'Foto gagal diunggah. Silakan pilih ulang.',
        };
        throw new \RuntimeException($message);
    }
    if (!isset($file['tmp_name'], $file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
        throw new \RuntimeException('Foto tidak valid atau ukurannya melebihi 5 MB.');
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new \RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    }

    $relativePath = 'uploads/submissions/' . bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    $directory = BASE_PATH . '/storage/uploads/submissions';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new \RuntimeException('Folder penyimpanan foto tidak dapat dibuat.');
    }
    if (!move_uploaded_file($file['tmp_name'], BASE_PATH . '/storage/' . $relativePath)) {
        throw new \RuntimeException('Foto gagal disimpan. Periksa izin folder storage.');
    }
    return $relativePath;
}
function store_profile_avatar(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran foto profil maksimal 5 MB.',
            UPLOAD_ERR_NO_FILE => 'Pilih foto profil terlebih dahulu.',
            default => 'Foto profil gagal diunggah. Silakan pilih ulang.',
        };
        throw new \RuntimeException($message);
    }
    if (!isset($file['tmp_name'], $file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
        throw new \RuntimeException('Foto profil tidak valid atau ukurannya melebihi 5 MB.');
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) {
        throw new \RuntimeException('Format foto profil harus JPG, PNG, atau WebP yang valid.');
    }

    $relativePath = 'uploads/profiles/' . bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    $directory = BASE_PATH . '/storage/uploads/profiles';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new \RuntimeException('Folder penyimpanan foto profil tidak dapat dibuat.');
    }
    if (!move_uploaded_file($file['tmp_name'], BASE_PATH . '/storage/' . $relativePath)) {
        throw new \RuntimeException('Foto profil gagal disimpan. Periksa izin folder storage.');
    }
    return $relativePath;
}
function delete_profile_avatar(?string $relativePath): void {
    if (!$relativePath || !preg_match('~^uploads/profiles/[a-f0-9]{40}\.(jpg|png|webp)$~', $relativePath)) return;
    $path = BASE_PATH . '/storage/' . $relativePath;
    if (is_file($path) && !unlink($path)) {
        \App\Core\Logger::error('Foto profil lama gagal dihapus', ['path' => $relativePath]);
    }
}
function delete_lesson_evidence(?string $relativePath): void {
    if (!$relativePath || !preg_match('~^uploads/submissions/[a-f0-9]{40}\.(jpg|png|webp)$~', $relativePath)) return;
    $path = BASE_PATH . '/storage/' . $relativePath;
    if (is_file($path) && !unlink($path)) {
        \App\Core\Logger::error('Foto hasil tugas lama gagal dihapus', ['path' => $relativePath]);
    }
}
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function redirect(string $page, array $params = []): never {
    header('Location: ' . url($page, $params));
    exit;
}
function redirect_tutor_chat(int $courseId): never {
    header('Location: ' . url('ai-tutor', ['course_id' => $courseId]) . '#tutor-chat');
    exit;
}
function send_tutor_stream_event(array $event): void {
    echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    flush();
}
function send_tutor_stream_error(int $status, string $message): never {
    http_response_code($status);
    header('Content-Type: application/x-ndjson; charset=utf-8');
    send_tutor_stream_event(['type' => 'error', 'message' => $message]);
    exit;
}
function lesson_text(string $text): string {
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    return nl2br($text);
}
function ensure_learning_paths(\PDO $pdo): void {
    $slugs = ['css-dasar', 'javascript-dasar', 'php-native-mysql', 'python-dasar'];
    $existingLearningSlugs = array_slice($slugs, 0, 3);
    $placeholders = implode(',', array_fill(0, count($slugs), '?'));
    $learningPlaceholders = implode(',', array_fill(0, count($existingLearningSlugs), '?'));
    $checkPaths = static function () use ($pdo, $placeholders, $slugs, $learningPlaceholders, $existingLearningSlugs): array {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.id), COUNT(DISTINCT CASE WHEN c.slug IN ({$learningPlaceholders}) THEN m.id END), COUNT(DISTINCT CASE WHEN c.slug IN ({$learningPlaceholders}) THEN l.id END) FROM courses c LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id WHERE c.slug IN ({$placeholders})");
        $stmt->execute(array_merge($existingLearningSlugs, $existingLearningSlugs, $slugs));
        return $stmt->fetch(\PDO::FETCH_NUM);
    };
    $totals = $checkPaths();
    if ((int)$totals[0] >= 4 && (int)$totals[1] >= 9 && (int)$totals[2] >= 18) return;

    $seedPath = BASE_PATH . '/database/seed_learning_paths.sql';
    $seed = file_get_contents($seedPath);
    if ($seed === false) {
        throw new \RuntimeException('File data kursus baru tidak dapat dibaca.');
    }
    $seed = preg_replace('/\AUSE\s+`codenexa_db`;\s*SET\s+NAMES\s+utf8mb4;\s*/i', '', $seed, 1);
    $statements = preg_split('/^-- CODE NEXA SEED STATEMENT\s*$/m', $seed);
    if ($statements === false || count($statements) !== 6) {
        throw new \RuntimeException('Format file data kursus baru tidak valid.');
    }

    $pdo->beginTransaction();
    try {
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $pdo->exec($statement);
        }
        $totals = $checkPaths();
        if ((int)$totals[0] < 4 || (int)$totals[1] < 9 || (int)$totals[2] < 18) {
            throw new \RuntimeException('Kategori Web Frontend atau Web Backend tidak ditemukan. Impor seed.sql terlebih dahulu.');
        }
        $pdo->commit();
    } catch (\Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

$page = (string)($_GET['page'] ?? (current_user() ? 'dashboard' : 'login'));
$allowedPages = ['login','register','logout','dashboard','courses','profile','learn','admin','ai-tutor','certificate','change-password','school-admins','school-admin','school-class','school-student','submission-image','profile-image','registration-options'];
if (!in_array($page, $allowedPages, true)) $page = 'dashboard';
$errors = [];
$schoolClassSchemaReady = school_class_schema_ready($pdo);
$studentGradeSchemaReady = student_grade_schema_ready($pdo);
$schoolCourseVisibilitySchemaReady = school_course_visibility_schema_ready($pdo);
$registrationSchools = [];
$registrationClasses = [];
if ($page === 'register' && $schoolClassSchemaReady) {
    $registrationSchools = $pdo->query("SELECT s.id, s.name, (SELECT u.full_name FROM users u WHERE u.school_id=s.id AND u.role='school_admin' AND u.status='active' ORDER BY u.updated_at DESC, u.id DESC LIMIT 1) AS admin_name FROM schools s WHERE s.status='active' AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=s.id AND active_admin.role='school_admin' AND active_admin.status='active') ORDER BY s.name")->fetchAll();
    $registrationClasses = $pdo->query("SELECT c.id, c.name, c.school_id FROM school_classes c JOIN schools s ON s.id=c.school_id WHERE c.status='active' AND s.status='active' AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=s.id AND active_admin.role='school_admin' AND active_admin.status='active') ORDER BY s.name, c.name")->fetchAll();
}
if ($page === 'registration-options') {
    if (!$schoolClassSchemaReady) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Pilihan sekolah dan kelas belum tersedia. Jalankan database/migrate_school_classes.sql.']);
        exit;
    }
    $schools = $pdo->query("SELECT s.id, s.name, (SELECT u.full_name FROM users u WHERE u.school_id=s.id AND u.role='school_admin' AND u.status='active' ORDER BY u.updated_at DESC, u.id DESC LIMIT 1) AS admin_name FROM schools s WHERE s.status='active' AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=s.id AND active_admin.role='school_admin' AND active_admin.status='active') ORDER BY s.name")->fetchAll();
    $classes = $pdo->query("SELECT c.id, c.name, c.school_id FROM school_classes c JOIN schools s ON s.id=c.school_id WHERE c.status='active' AND s.status='active' AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=s.id AND active_admin.role='school_admin' AND active_admin.status='active') ORDER BY s.name, c.name")->fetchAll();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(['schools' => $schools, 'classes' => $classes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($page === 'submission-image') {
    require_admin();
    if (!approval_schema_ready($pdo)) {
        http_response_code(503);
        exit('Fitur foto hasil tugas belum aktif.');
    }
    $progressId = filter_input(INPUT_GET, 'progress_id', FILTER_VALIDATE_INT) ?: 0;
    $stmt = $pdo->prepare("SELECT evidence_image FROM lesson_progress WHERE id=? AND evidence_image IS NOT NULL");
    $stmt->execute([$progressId]);
    $relativePath = $stmt->fetchColumn();
    if (!$relativePath || !preg_match('~^uploads/submissions/[a-f0-9]{40}\.(jpg|png|webp)$~', $relativePath)) {
        http_response_code(404);
        exit('Foto hasil tugas tidak ditemukan.');
    }
    $imagePath = BASE_PATH . '/storage/' . $relativePath;
    if (!is_file($imagePath)) {
        http_response_code(404);
        exit('Foto hasil tugas tidak ditemukan.');
    }
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($imagePath);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(415);
        exit('Format foto tidak didukung.');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($imagePath));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($imagePath);
    exit;
}
if ($page === 'profile-image') {
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        exit('Silakan masuk untuk melihat foto profil.');
    }
    $relativePath = $user['avatar'] ?? '';
    if (!is_string($relativePath) || !preg_match('~^uploads/profiles/[a-f0-9]{40}\.(jpg|png|webp)$~', $relativePath)) {
        http_response_code(404);
        exit('Foto profil tidak ditemukan.');
    }
    $imagePath = BASE_PATH . '/storage/' . $relativePath;
    if (!is_file($imagePath)) {
        http_response_code(404);
        exit('Foto profil tidak ditemukan.');
    }
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($imagePath);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(415);
        exit('Format foto tidak didukung.');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($imagePath));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($imagePath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $sessionUser = current_user();
    $sessionUser = require_active_school_admin($pdo, $sessionUser);
    if ($sessionUser && (int)($sessionUser['must_change_password'] ?? 0) === 1 && $action !== 'change_initial_password') {
        redirect('change-password');
    }

    if ($action === 'admin_toggle_user_status') {
        $admin = require_login();
        $isGlobalAdmin = in_array(($admin['role'] ?? ''), ['admin', 'super_admin'], true);
        $isSchoolAdmin = ($admin['role'] ?? '') === 'school_admin';
        if (!$isGlobalAdmin && !$isSchoolAdmin) {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh administrator.');
        }
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
        $returnPage = $isSchoolAdmin ? 'school-class' : 'admin';
        $returnParams = [];
        if ($isSchoolAdmin) {
            $classId = filter_var($_POST['class_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$classId || $classId < 1) {
                flash('error', 'Kelas yang dipilih tidak valid.');
                redirect('school-admin');
            }
            $returnParams = ['class_id' => $classId];
        }
        if (!$targetId || $targetId === (int)$admin['id']) {
            flash('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        } elseif ($isSchoolAdmin) {
            $stmt = $pdo->prepare("UPDATE users u JOIN school_classes c ON c.id=u.class_id AND c.school_id=u.school_id JOIN users a ON a.school_id=u.school_id AND a.id=? AND a.role='school_admin' AND a.status='active' SET u.status=IF(u.status='active','suspended','active') WHERE u.id=? AND u.role='user' AND c.id=?");
            $stmt->execute([$admin['id'], $targetId, $classId]);
            flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount()
                ? 'Status akun siswa berhasil diperbarui.'
                : 'Akun siswa tidak ditemukan pada kelas/sekolah Anda.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ? AND role = 'user'");
            $stmt->execute([$targetId]);
            flash('success', $stmt->rowCount() ? 'Status pengguna berhasil diperbarui.' : 'Pengguna tidak ditemukan atau tidak dapat diubah.');
        }
        redirect($returnPage, $returnParams);
    } elseif ($action === 'admin_delete_user') {
        $admin = require_login();
        $isGlobalAdmin = in_array(($admin['role'] ?? ''), ['admin', 'super_admin'], true);
        $isSchoolAdmin = ($admin['role'] ?? '') === 'school_admin';
        if (!$isGlobalAdmin && !$isSchoolAdmin) {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh administrator.');
        }
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
        $returnPage = $isSchoolAdmin ? 'school-class' : 'admin';
        $returnParams = [];
        if ($isSchoolAdmin) {
            $classId = filter_var($_POST['class_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$classId || $classId < 1) {
                flash('error', 'Kelas yang dipilih tidak valid.');
                redirect('school-admin');
            }
            $returnParams = ['class_id' => $classId];
        }
        if (!$targetId || $targetId === (int)$admin['id']) {
            flash('error', 'Akun yang dipilih tidak valid untuk dihapus.');
            redirect($returnPage, $returnParams);
        }
        try {
            $pdo->beginTransaction();
            if ($isSchoolAdmin) {
                $stmt = $pdo->prepare("SELECT u.id, u.avatar FROM users u JOIN school_classes c ON c.id=u.class_id AND c.school_id=u.school_id JOIN users a ON a.school_id=u.school_id AND a.id=? AND a.role='school_admin' AND a.status='active' WHERE u.id=? AND u.role='user' AND c.id=? FOR UPDATE");
                $stmt->execute([$admin['id'], $targetId, $classId]);
            } else {
                $stmt = $pdo->prepare("SELECT id, avatar FROM users WHERE id=? AND role='user' FOR UPDATE");
                $stmt->execute([$targetId]);
            }
            $targetUser = $stmt->fetch();
            if (!$targetUser) {
                $pdo->rollBack();
                flash('error', 'Akun tidak ditemukan atau berada di luar kewenangan Anda.');
                redirect($returnPage, $returnParams);
            }
            $stmt = $pdo->prepare('SELECT evidence_image FROM lesson_progress WHERE user_id=? AND evidence_image IS NOT NULL');
            $stmt->execute([$targetId]);
            $evidencePaths = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='user'");
            $stmt->execute([$targetId]);
            if ($stmt->rowCount() !== 1) {
                throw new \RuntimeException('Akun tidak terhapus atau sudah berubah.');
            }
            $pdo->commit();
            delete_profile_avatar($targetUser['avatar'] ?? null);
            foreach ($evidencePaths as $evidencePath) {
                delete_lesson_evidence(is_string($evidencePath) ? $evidencePath : null);
            }
            flash('success', 'Akun siswa beserta riwayat belajarnya berhasil dihapus permanen.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            \App\Core\Logger::error('Penghapusan akun siswa gagal', [
                'user_id' => $targetId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', 'Akun belum berhasil dihapus. Periksa log aplikasi lalu coba lagi.');
        }
        redirect($returnPage, $returnParams);
    } elseif ($action === 'admin_set_course_visibility') {
        require_admin();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $isActive = filter_var($_POST['is_active'] ?? null, FILTER_VALIDATE_INT);
        if (!$courseId || !in_array($isActive, [0, 1], true)) {
            flash('error', 'Kursus atau pengaturan visibilitas tidak valid.');
        } else {
            $stmt = $pdo->prepare('UPDATE courses SET is_active=? WHERE id=?');
            $stmt->execute([$isActive, $courseId]);
            $exists = $pdo->prepare('SELECT id FROM courses WHERE id=?');
            $exists->execute([$courseId]);
            $courseExists = (bool)$exists->fetchColumn();
            flash($courseExists ? 'success' : 'error', $courseExists
                ? ($isActive ? 'Kursus berhasil ditampilkan kepada pengguna.' : 'Kursus disembunyikan dari pengguna.')
                : 'Kursus tidak ditemukan.');
        }
        redirect('admin');
    } elseif ($action === 'school_set_course_visibility') {
        $schoolAdmin = require_login();
        if (($schoolAdmin['role'] ?? '') !== 'school_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Admin Sekolah.');
        }
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $isVisible = filter_var($_POST['is_visible'] ?? null, FILTER_VALIDATE_INT);
        if (!$schoolCourseVisibilitySchemaReady) {
            flash('error', 'Pengaturan kursus sekolah belum aktif. Jalankan database/migrate_school_course_visibility.sql terlebih dahulu.');
        } elseif (!$courseId || !in_array($isVisible, [0, 1], true)) {
            flash('error', 'Kursus atau pengaturan tampilan tidak valid.');
        } else {
            $schoolStmt = $pdo->prepare("SELECT school_id FROM users WHERE id=? AND role='school_admin' AND status='active'");
            $schoolStmt->execute([$schoolAdmin['id']]);
            $schoolId = $schoolStmt->fetchColumn();
            $courseStmt = $pdo->prepare('SELECT id, is_active FROM courses WHERE id=?');
            $courseStmt->execute([$courseId]);
            $course = $courseStmt->fetch();
            if (!$schoolId || !$course) {
                flash('error', 'Sekolah atau kursus tidak ditemukan.');
            } elseif (!(bool)$course['is_active'] && $isVisible === 1) {
                flash('error', 'Kursus ini dinonaktifkan oleh Super Admin dan belum dapat ditampilkan.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO school_course_visibility (school_id, course_id, is_visible) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_visible=VALUES(is_visible)');
                $stmt->execute([(int)$schoolId, $courseId, $isVisible]);
                flash('success', $isVisible
                    ? 'Kursus ditampilkan kepada siswa di sekolah Anda.'
                    : 'Kursus disembunyikan dari siswa di sekolah Anda.');
            }
        }
        redirect('school-admin');
    } elseif ($action === 'create_school_class') {
        $schoolAdmin = require_login();
        if (($schoolAdmin['role'] ?? '') !== 'school_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Admin Sekolah.');
        }
        if (!$schoolClassSchemaReady) {
            flash('error', 'Database kelas belum siap. Jalankan database/migrate_school_classes.sql terlebih dahulu.');
            redirect('school-admin');
        }
        $className = trim((string)($_POST['class_name'] ?? ''));
        if (mb_strlen($className) < 2 || mb_strlen($className) > 80) {
            flash('error', 'Nama kelas harus terdiri dari 2–80 karakter.');
            redirect('school-admin');
        }
        $schoolStmt = $pdo->prepare("SELECT s.id FROM schools s JOIN users u ON u.school_id=s.id WHERE u.id=? AND u.role='school_admin' AND u.status='active' AND s.status='active' LIMIT 1");
        $schoolStmt->execute([$schoolAdmin['id']]);
        $schoolId = $schoolStmt->fetchColumn();
        if (!$schoolId) {
            flash('error', 'Sekolah tidak ditemukan atau sedang dinonaktifkan.');
            redirect('school-admin');
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO school_classes (school_id, name, status) VALUES (?, ?, 'active')");
            $stmt->execute([(int)$schoolId, $className]);
            flash('success', 'Kelas berhasil ditambahkan.');
        } catch (Throwable $exception) {
            \App\Core\Logger::error('Pembuatan kelas sekolah gagal', [
                'school_id' => (int)$schoolId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', $exception instanceof \PDOException && $exception->getCode() === '23000'
                ? 'Nama kelas tersebut sudah digunakan di sekolah ini.'
                : 'Kelas belum berhasil ditambahkan. Periksa log aplikasi lalu coba lagi.');
        }
        redirect('school-admin');
    } elseif ($action === 'toggle_school_class_status') {
        $schoolAdmin = require_login();
        if (($schoolAdmin['role'] ?? '') !== 'school_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Admin Sekolah.');
        }
        $classId = filter_var($_POST['class_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$schoolClassSchemaReady || !$classId || $classId < 1) {
            flash('error', !$schoolClassSchemaReady
                ? 'Database kelas belum siap. Jalankan database/migrate_school_classes.sql terlebih dahulu.'
                : 'Kelas yang dipilih tidak valid.');
            redirect('school-admin');
        }
        $stmt = $pdo->prepare("UPDATE school_classes c JOIN users u ON u.school_id=c.school_id SET c.status=IF(c.status='active','suspended','active') WHERE c.id=? AND u.id=? AND u.role='school_admin' AND u.status='active'");
        $stmt->execute([$classId, $schoolAdmin['id']]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount()
            ? 'Status kelas berhasil diperbarui.'
            : 'Kelas tidak ditemukan atau statusnya tidak berubah.');
        redirect('school-admin');
    } elseif ($action === 'edit_school_name') {
        $superAdmin = require_login();
        if (($superAdmin['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Super Admin.');
        }
        if (!school_admin_schema_ready($pdo)) {
            flash('error', 'Database sekolah belum siap. Jalankan database/migrate_school_admins.sql terlebih dahulu.');
            redirect('school-admins');
        }
        $schoolId = filter_var($_POST['school_id'] ?? '', FILTER_VALIDATE_INT);
        $schoolName = trim((string)($_POST['school_name'] ?? ''));
        if (!$schoolId || $schoolId < 1 || mb_strlen($schoolName) < 2 || mb_strlen($schoolName) > 160) {
            flash('error', 'Nama atau data sekolah tidak valid. Nama sekolah harus terdiri dari 2–160 karakter.');
            redirect('school-admins');
        }
        $duplicateCheck = $pdo->prepare('SELECT id FROM schools WHERE name=? AND id<>? LIMIT 1');
        $duplicateCheck->execute([$schoolName, $schoolId]);
        if ($duplicateCheck->fetchColumn()) {
            flash('error', 'Nama sekolah tersebut sudah digunakan. Gunakan nama yang berbeda agar siswa tidak salah memilih.');
            redirect('school-admins');
        }
        $schoolCheck = $pdo->prepare('SELECT name FROM schools WHERE id=?');
        $schoolCheck->execute([$schoolId]);
        $oldSchoolName = $schoolCheck->fetchColumn();
        if ($oldSchoolName === false) {
            flash('error', 'Sekolah tidak ditemukan.');
            redirect('school-admins');
        }
        if ($oldSchoolName === $schoolName) {
            flash('success', 'Nama sekolah tidak berubah.');
            redirect('school-admins');
        }
        $stmt = $pdo->prepare('UPDATE schools SET name=? WHERE id=?');
        $stmt->execute([$schoolName, $schoolId]);
        flash('success', 'Nama sekolah berhasil diperbarui. Daftar pendaftaran akan mengikuti nama terbaru secara otomatis.');
        redirect('school-admins');
    } elseif ($action === 'create_school_admin') {
        $superAdmin = require_login();
        if (($superAdmin['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Super Admin.');
        }
        $schoolName = trim((string)($_POST['school_name'] ?? ''));
        $schoolIdInput = trim((string)($_POST['school_id'] ?? ''));
        $schoolId = $schoolIdInput === '' ? null : filter_var($schoolIdInput, FILTER_VALIDATE_INT);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $initialPassword = (string)($_POST['initial_password'] ?? '');
        if ($schoolIdInput !== '' && ($schoolId === false || $schoolId < 1)) $errors[] = 'Sekolah yang dipilih tidak valid.';
        if ($schoolId !== false && $schoolId !== null && $schoolName !== '') $errors[] = 'Pilih sekolah yang sudah ada atau isi nama sekolah baru, jangan keduanya.';
        if ($schoolId === false || $schoolId === null) {
            if (mb_strlen($schoolName) < 2 || mb_strlen($schoolName) > 160) $errors[] = 'Nama sekolah baru harus terdiri dari 2–160 karakter.';
        }
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) $errors[] = 'Nama admin harus terdiri dari 2–120 karakter.';
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username)) $errors[] = 'Username harus 3–40 karakter, hanya huruf, angka, dan garis bawah.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email admin tidak valid.';
        if (strlen($initialPassword) < 12) $errors[] = 'Password awal minimal 12 karakter.';
        if (!school_admin_schema_ready($pdo)) $errors[] = 'Database belum siap. Jalankan database/migrate_school_admins.sql terlebih dahulu.';

        if (!$errors) {
            if ($schoolId !== false && $schoolId !== null) {
                $schoolCheck = $pdo->prepare('SELECT id FROM schools WHERE id=?');
                $schoolCheck->execute([$schoolId]);
                if (!$schoolCheck->fetchColumn()) $errors[] = 'Sekolah yang dipilih tidak ditemukan.';
            }
            $check = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
            if (!$errors) $check->execute([$username, $email]);
            if (!$errors && $check->fetch()) {
                $errors[] = 'Username atau email sudah digunakan akun lain.';
            } elseif (!$errors) {
                try {
                    $pdo->beginTransaction();
                    if ($schoolId === false || $schoolId === null) {
                        $stmt = $pdo->prepare("INSERT INTO schools (name, status) VALUES (?, 'active')");
                        $stmt->execute([$schoolName]);
                        $schoolId = (int)$pdo->lastInsertId();
                    }
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, must_change_password, school_id, status) VALUES (?, ?, ?, ?, 'school_admin', 1, ?, 'active')");
                    $stmt->execute([$fullName, $username, $email, password_hash($initialPassword, PASSWORD_DEFAULT), $schoolId]);
                    $pdo->commit();
                    flash('success', 'Akun admin sekolah berhasil dibuat. Sampaikan password awal secara pribadi; admin wajib menggantinya saat login pertama.');
                    redirect('school-admins');
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    \App\Core\Logger::error('Pembuatan sekolah/admin sekolah gagal', [
                        'username' => $username,
                        'message' => $exception->getMessage(),
                    ]);
                    $errors[] = $exception instanceof \PDOException && $exception->getCode() === '23000'
                        ? 'Username atau email baru saja dipakai. Muat ulang dan gunakan identitas lain.'
                        : 'Sekolah dan akun admin belum berhasil dibuat. Periksa log aplikasi lalu coba lagi.';
                }
            }
        }
        $page = 'school-admins';
    } elseif ($action === 'edit_school_admin') {
        $superAdmin = require_login();
        if (($superAdmin['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Super Admin.');
        }

        if (!school_admin_schema_ready($pdo)) $errors[] = 'Database belum siap. Jalankan database/migrate_school_admins.sql terlebih dahulu.';
        $accountId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
        if (!$accountId || $accountId < 1) $errors[] = 'Akun admin yang dipilih tidak valid.';
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) $errors[] = 'Nama admin harus terdiri dari 2–120 karakter.';
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username)) $errors[] = 'Username harus 3–40 karakter, hanya huruf, angka, dan garis bawah.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email admin tidak valid.';
        if ($newPassword !== '' && strlen($newPassword) < 12) $errors[] = 'Password baru minimal 12 karakter.';
        if ($newPassword !== $passwordConfirm) $errors[] = 'Konfirmasi password baru tidak sama.';

        if (!$errors) {
            $accountCheck = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='school_admin'");
            $accountCheck->execute([$accountId]);
            if (!$accountCheck->fetchColumn()) {
                $errors[] = 'Akun admin sekolah tidak ditemukan.';
            }
        }
        if (!$errors) {
            $identityCheck = $pdo->prepare('SELECT id FROM users WHERE (username=? OR email=?) AND id<>? LIMIT 1');
            $identityCheck->execute([$username, $email, $accountId]);
            if ($identityCheck->fetch()) $errors[] = 'Username atau email sudah digunakan akun lain.';
        }
        if (!$errors) {
            try {
                if ($newPassword !== '') {
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, password_hash=?, must_change_password=1 WHERE id=? AND role='school_admin'");
                    $stmt->execute([$fullName, $username, $email, password_hash($newPassword, PASSWORD_DEFAULT), $accountId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=? WHERE id=? AND role='school_admin'");
                    $stmt->execute([$fullName, $username, $email, $accountId]);
                }
                flash('success', 'Data akun admin sekolah berhasil diperbarui.' . ($newPassword !== '' ? ' Sampaikan password sementara secara pribadi; admin wajib menggantinya saat login berikutnya.' : ''));
                redirect('school-admins');
            } catch (Throwable $exception) {
                \App\Core\Logger::error('Perubahan akun admin sekolah gagal', [
                    'user_id' => $accountId,
                    'message' => $exception->getMessage(),
                ]);
                $errors[] = $exception instanceof \PDOException && $exception->getCode() === '23000'
                    ? 'Username atau email baru saja dipakai. Gunakan identitas lain.'
                    : 'Data akun admin belum berhasil diperbarui. Periksa log aplikasi lalu coba lagi.';
            }
        }
        $page = 'school-admins';
    } elseif ($action === 'set_school_admin_status') {
        $superAdmin = require_login();
        if (($superAdmin['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Super Admin.');
        }

        $accountId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
        $status = (string)($_POST['status'] ?? '');
        if (!school_admin_schema_ready($pdo)) {
            flash('error', 'Database belum siap. Jalankan database/migrate_school_admins.sql terlebih dahulu.');
        } elseif (!$accountId || $accountId < 1 || !in_array($status, ['active', 'suspended'], true)) {
            flash('error', 'Akun atau status yang dipilih tidak valid.');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='school_admin'");
            $stmt->execute([$status, $accountId]);
            $updated = $stmt->rowCount() === 1;
            flash($updated ? 'success' : 'error', $updated
                ? 'Status akun admin sekolah berhasil diperbarui.'
                : 'Akun admin sekolah tidak ditemukan atau statusnya tidak berubah.');
        }
        redirect('school-admins');
    } elseif ($action === 'delete_school_account') {
        $superAdmin = require_login();
        if (($superAdmin['role'] ?? '') !== 'super_admin') {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh Super Admin.');
        }
        $accountId = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
        if (!school_admin_schema_ready($pdo)) {
            flash('error', 'Database belum siap. Jalankan database/migrate_school_admins.sql terlebih dahulu.');
            redirect('school-admins');
        }
        if (!$accountId || $accountId < 1) {
            flash('error', 'Akun Admin Sekolah yang dipilih tidak valid.');
            redirect('school-admins');
        }
        try {
            $pdo->beginTransaction();
            $schoolStmt = $pdo->prepare("SELECT school_id FROM users WHERE id=? AND role='school_admin' AND school_id IS NOT NULL FOR UPDATE");
            $schoolStmt->execute([$accountId]);
            $schoolId = $schoolStmt->fetchColumn();
            if (!$schoolId) {
                throw new \RuntimeException('Akun Admin Sekolah tidak ditemukan atau tidak terhubung dengan sekolah.');
            }
            $accountStmt = $pdo->prepare("SELECT id, avatar FROM users WHERE school_id=? AND role IN ('school_admin','user') FOR UPDATE");
            $accountStmt->execute([(int)$schoolId]);
            $schoolAccounts = $accountStmt->fetchAll();
            if (!$schoolAccounts) {
                throw new \RuntimeException('Tidak ada akun yang dapat dihapus untuk sekolah ini.');
            }
            $accountIds = array_map(
                static fn(array $account): int => (int)$account['id'],
                $schoolAccounts
            );
            $evidencePaths = [];
            if ($accountIds) {
                $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
                $evidenceStmt = $pdo->prepare("SELECT evidence_image FROM lesson_progress WHERE user_id IN ({$placeholders}) AND evidence_image IS NOT NULL");
                $evidenceStmt->execute($accountIds);
                $evidencePaths = $evidenceStmt->fetchAll(\PDO::FETCH_COLUMN);
            }
            $avatars = array_column($schoolAccounts, 'avatar');
            $deleteAccounts = $pdo->prepare("DELETE FROM users WHERE school_id=? AND role IN ('school_admin','user')");
            $deleteAccounts->execute([(int)$schoolId]);
            if ($deleteAccounts->rowCount() !== count($schoolAccounts)) {
                throw new \RuntimeException('Tidak semua akun sekolah berhasil dihapus.');
            }
            $deleteSchool = $pdo->prepare('DELETE FROM schools WHERE id=?');
            $deleteSchool->execute([(int)$schoolId]);
            if ($deleteSchool->rowCount() !== 1) {
                throw new \RuntimeException('Data sekolah tidak berhasil dihapus.');
            }
            $pdo->commit();
            foreach ($avatars as $avatar) {
                delete_profile_avatar(is_string($avatar) ? $avatar : null);
            }
            foreach ($evidencePaths as $evidencePath) {
                delete_lesson_evidence(is_string($evidencePath) ? $evidencePath : null);
            }
            flash('success', 'Sekolah berhasil dihapus beserta akun Admin Sekolah, akun siswa, kelas, dan riwayat belajar terkait.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            \App\Core\Logger::error('Penghapusan sekolah dan akun terkait gagal', [
                'user_id' => (int)$accountId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', 'Sekolah belum berhasil dihapus. Periksa log aplikasi lalu coba lagi.');
        }
        redirect('school-admins');
    } elseif ($action === 'read_notifications') {
        $user = require_login();
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$user['id']]);
        $returnPage = (string)($_POST['return_page'] ?? 'dashboard');
        if (!in_array($returnPage, ['dashboard', 'courses', 'profile', 'learn', 'ai-tutor'], true)) $returnPage = 'dashboard';
        $params = [];
        if (in_array($returnPage, ['learn', 'ai-tutor'], true)) {
            $courseId = filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT);
            if ($courseId) $params['course_id'] = $courseId;
        }
        redirect($returnPage, $params);
    } elseif ($action === 'learning_heartbeat') {
        $user = require_login();
        if (!learning_activity_schema_ready($pdo)) {
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Fitur pemantauan aktivitas belum aktif. Jalankan database/migrate_learning_activity.sql.']);
            exit;
        }
        $courseId = filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $lessonId = filter_var($_POST['lesson_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        if (!$courseId || !$lessonId) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Kursus atau materi tidak valid.']);
            exit;
        }
        $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
        $accessCheck = $pdo->prepare("SELECT c.id FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE c.id=? AND c.is_active=1 AND e.user_id=? AND e.status='active'{$visibilityClause}");
        $accessCheck->execute($schoolCourseVisibilitySchemaReady ? [$courseId, $user['id'], $user['id']] : [$courseId, $user['id']]);
        if (!$accessCheck->fetch()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Kursus tidak tersedia untuk akun Anda.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE enrollments e JOIN lessons l ON l.id=? JOIN modules m ON m.id=l.module_id SET e.last_lesson_id=l.id, e.last_activity_at=NOW() WHERE e.user_id=? AND e.course_id=? AND e.course_id=m.course_id AND e.status='active' AND l.is_active=1 AND m.is_active=1");
        $stmt->execute([$lessonId, $user['id'], $courseId]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['tracked' => $stmt->rowCount() > 0]);
        exit;
    } elseif ($action === 'stream_course_ai') {
        $user = require_login();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $question = trim((string)($_POST['question'] ?? ''));
        if ($courseId < 1 || $question === '' || mb_strlen($question) > 1200) {
            send_tutor_stream_error(422, 'Pilih kursus dan tulis pertanyaan maksimal 1.200 karakter.');
        }

        $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
        $stmt = $pdo->prepare("SELECT c.id FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE c.id=? AND c.is_active=1 AND e.user_id=?{$visibilityClause}");
        $stmt->execute($schoolCourseVisibilitySchemaReady ? [$courseId, $user['id'], $user['id']] : [$courseId, $user['id']]);
        if (!$stmt->fetch()) {
            send_tutor_stream_error(403, 'Asisten hanya tersedia untuk kursus yang sudah kamu ikuti.');
        }

        $historyKey = $user['id'] . ':' . $courseId;
        $history = $_SESSION['course_tutor'][$historyKey] ?? [];
        $rateKey = (string)$user['id'];
        $recentRequests = array_values(array_filter(
            $_SESSION['course_tutor_rate'][$rateKey] ?? [],
            static fn($timestamp): bool => is_int($timestamp) && $timestamp > time() - 60
        ));
        if (count($recentRequests) >= 8) {
            send_tutor_stream_error(429, 'Batas pertanyaan sementara tercapai. Coba lagi dalam satu menit.');
        }
        $recentRequests[] = time();
        $_SESSION['course_tutor_rate'][$rateKey] = $recentRequests;

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');
        ini_set('zlib.output_compression', '0');
        send_tutor_stream_event(['type' => 'start']);

        try {
            $receivedChunks = false;
            $answer = (new CourseTutor())->answer(
                $pdo,
                $courseId,
                $question,
                $history,
                static function (string $chunk) use (&$receivedChunks): void {
                    $receivedChunks = true;
                    send_tutor_stream_event(['type' => 'chunk', 'content' => $chunk]);
                }
            );
            if (!$receivedChunks) {
                send_tutor_stream_event(['type' => 'chunk', 'content' => $answer]);
            }

            $history[] = ['role' => 'user', 'content' => $question];
            $history[] = ['role' => 'assistant', 'content' => $answer];
            $_SESSION['course_tutor'][$historyKey] = array_slice($history, -10);
            session_write_close();
            send_tutor_stream_event(['type' => 'done']);
        } catch (CourseTutorException $exception) {
            if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
            send_tutor_stream_event(['type' => 'error', 'message' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            \App\Core\Logger::error('Tutor kursus gagal menjawab secara streaming', [
                'user_id' => $user['id'],
                'course_id' => $courseId,
                'message' => $exception->getMessage(),
            ]);
            if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
            send_tutor_stream_event(['type' => 'error', 'message' => 'Pertanyaan belum dapat dijawab. Silakan coba lagi.']);
        }
        exit;
    } elseif ($action === 'ask_course_ai') {
        $user = require_login();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $question = trim((string)($_POST['question'] ?? ''));
        if ($courseId < 1 || $question === '' || mb_strlen($question) > 1200) {
            flash('error', 'Pilih kursus dan tulis pertanyaan maksimal 1.200 karakter.');
            redirect('ai-tutor', $courseId > 0 ? ['course_id' => $courseId] : []);
        }

        $stmt = $pdo->prepare("SELECT c.id FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE c.id=? AND c.is_active=1 AND e.user_id=?");
        $stmt->execute([$courseId, $user['id']]);
        if (!$stmt->fetch()) {
            flash('error', 'Asisten hanya tersedia untuk kursus yang sudah kamu ikuti.');
            redirect('ai-tutor');
        }

        $historyKey = $user['id'] . ':' . $courseId;
        $history = $_SESSION['course_tutor'][$historyKey] ?? [];
        try {
            $rateKey = (string)$user['id'];
            $recentRequests = array_values(array_filter(
                $_SESSION['course_tutor_rate'][$rateKey] ?? [],
                static fn($timestamp): bool => is_int($timestamp) && $timestamp > time() - 60
            ));
            if (count($recentRequests) >= 8) {
                flash('error', 'Batas pertanyaan sementara tercapai. Coba lagi dalam satu menit.');
                redirect_tutor_chat($courseId);
            }

            $answer = (new CourseTutor())->answer($pdo, $courseId, $question, $history);
            $recentRequests[] = time();
            $_SESSION['course_tutor_rate'][$rateKey] = $recentRequests;
            $history[] = ['role' => 'user', 'content' => $question];
            $history[] = ['role' => 'assistant', 'content' => $answer];
            $_SESSION['course_tutor'][$historyKey] = array_slice($history, -10);
        } catch (CourseTutorException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            \App\Core\Logger::error('Tutor kursus gagal menjawab', [
                'user_id' => $user['id'],
                'course_id' => $courseId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', 'Pertanyaan belum dapat dijawab. Silakan coba lagi.');
        }
        redirect_tutor_chat($courseId);
    } elseif ($action === 'clear_course_ai') {
        $user = require_login();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        if ($courseId > 0) unset($_SESSION['course_tutor'][$user['id'] . ':' . $courseId]);
        redirect('ai-tutor', $courseId > 0 ? ['course_id' => $courseId] : []);
    } elseif ($action === 'register') {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        $schoolIdInput = trim((string)($_POST['school_id'] ?? ''));
        $schoolId = $schoolIdInput === '' ? null : filter_var($schoolIdInput, FILTER_VALIDATE_INT);
        $classIdInput = trim((string)($_POST['class_id'] ?? ''));
        $classId = $classIdInput === '' ? null : filter_var($classIdInput, FILTER_VALIDATE_INT);
        $gradeLevelInput = trim((string)($_POST['grade_level'] ?? ''));
        $gradeLevel = $gradeLevelInput === '' ? null : $gradeLevelInput;

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Nama harus terdiri dari 2–120 karakter.';
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username)) $errors[] = 'Username harus 3–40 karakter, hanya huruf, angka, dan garis bawah.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
        if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
        if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak sama.';
        if ($gradeLevel !== null && (!$studentGradeSchemaReady || !in_array($gradeLevel, ['X', 'XI', 'XII'], true))) {
            $errors[] = 'Tingkat kelas tidak valid. Pilih X, XI, atau XII.';
        }
        if ($schoolIdInput !== '' && (!$schoolClassSchemaReady || $schoolId === false || $schoolId < 1)) {
            $errors[] = 'Sekolah yang dipilih tidak valid. Muat ulang halaman lalu pilih sekolah yang tersedia.';
        }
        if ($classIdInput !== '' && (!$schoolClassSchemaReady || $classId === false || $classId < 1)) {
            $errors[] = 'Kelas yang dipilih tidak valid. Muat ulang halaman lalu pilih kelas yang tersedia.';
        }
        if ($schoolClassSchemaReady && $schoolId !== null && !$errors) {
            $schoolStmt = $pdo->prepare("SELECT id FROM schools WHERE id=? AND status='active' AND EXISTS (SELECT 1 FROM school_classes WHERE school_id=schools.id AND status='active') AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=schools.id AND active_admin.role='school_admin' AND active_admin.status='active')");
            $schoolStmt->execute([$schoolId]);
            if (!$schoolStmt->fetchColumn()) $errors[] = 'Sekolah yang dipilih tidak memiliki kelas aktif atau sudah tidak tersedia.';
        }
        if ($classId !== null && !$errors) {
            $classStmt = $pdo->prepare("SELECT c.school_id FROM school_classes c JOIN schools s ON s.id=c.school_id WHERE c.id=? AND c.status='active' AND s.status='active' AND EXISTS (SELECT 1 FROM users active_admin WHERE active_admin.school_id=s.id AND active_admin.role='school_admin' AND active_admin.status='active')");
            $classStmt->execute([$classId]);
            $classSchoolId = $classStmt->fetchColumn();
            if (!$classSchoolId || $schoolId === null || (int)$classSchoolId !== (int)$schoolId) {
                $errors[] = 'Kelas tidak sesuai dengan sekolah yang dipilih. Pilih ulang sekolah dan kelas.';
            }
        } else {
            $classSchoolId = $schoolId;
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $check->execute([$email, $username]);
            if ($check->fetch()) {
                $errors[] = 'Email atau username sudah digunakan.';
            } else {
                if ($schoolClassSchemaReady && $studentGradeSchemaReady) {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, school_id, class_id, grade_level, status) VALUES (?, ?, ?, ?, 'user', ?, ?, ?, 'active')");
                    $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $classSchoolId, $classId, $gradeLevel]);
                } elseif ($schoolClassSchemaReady) {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, school_id, class_id, status) VALUES (?, ?, ?, ?, 'user', ?, ?, 'active')");
                    $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $classSchoolId, $classId]);
                } elseif ($studentGradeSchemaReady) {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, grade_level, status) VALUES (?, ?, ?, ?, 'user', ?, 'active')");
                    $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $gradeLevel]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
                    $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                }
                flash('success', 'Akun berhasil dibuat. Silakan login.');
                redirect('login');
            }
        }
        $page = 'register';
    } elseif ($action === 'login') {
        $identity = trim((string)($_POST['identity'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $stmt = $pdo->prepare("SELECT id, full_name, username, email, password_hash, role, status, avatar, bio, xp, level, must_change_password FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([strtolower($identity), $identity]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email/username atau password tidak sesuai.';
            $page = 'login';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Akun sedang dinonaktifkan. Hubungi administrator.';
            $page = 'login';
        } else {
            session_regenerate_id(true);
            unset($user['password_hash']);
            $levelStmt = $pdo->prepare('UPDATE users SET level=FLOOR(xp / 100) + 1 WHERE id=?');
            $levelStmt->execute([$user['id']]);
            $user['level'] = intdiv((int)$user['xp'], 100) + 1;
            $_SESSION['user'] = $user;
            $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $upd->execute([$user['id']]);
            if ((int)$user['must_change_password'] === 1) {
                redirect('change-password');
            }
            redirect(match ($user['role']) {
                'super_admin', 'admin' => 'admin',
                'school_admin' => 'school-admin',
                default => 'dashboard',
            });
        }
    } elseif ($action === 'change_initial_password') {
        $user = require_login();
        if ((int)($user['must_change_password'] ?? 0) !== 1) {
            redirect('dashboard');
        }
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['password_confirm'] ?? '');
        if (strlen($newPassword) < 12) {
            $errors[] = 'Password baru minimal 12 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Konfirmasi password baru tidak sama.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=? AND must_change_password=1');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            if ($stmt->rowCount() !== 1) {
                flash('error', 'Password belum berhasil diperbarui. Silakan coba lagi.');
                redirect('change-password');
            }
            session_regenerate_id(true);
            $_SESSION['user']['must_change_password'] = 0;
            flash('success', 'Password berhasil diganti.');
            redirect(match ($user['role']) {
                'super_admin', 'admin' => 'admin',
                'school_admin' => 'school-admin',
                default => 'dashboard',
            });
        }
        $page = 'change-password';
    } elseif ($action === 'profile_avatar') {
        $user = require_login();
        $newAvatar = null;
        try {
            $newAvatar = store_profile_avatar($_FILES['avatar'] ?? []);
            $stmt = $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
            $stmt->execute([$newAvatar, $user['id']]);
        } catch (\RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect('profile');
        } catch (Throwable $exception) {
            delete_profile_avatar($newAvatar);
            throw $exception;
        }
        delete_profile_avatar($user['avatar'] ?? null);
        $_SESSION['user']['avatar'] = $newAvatar;
        flash('success', 'Foto profil berhasil diperbarui.');
        redirect('profile');
    } elseif ($action === 'profile') {
        $user = require_login();
        $name = trim((string)($_POST['full_name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['password_confirm'] ?? '');
        $hasClassSelection = array_key_exists('class_id', $_POST);
        $classIdInput = $hasClassSelection ? trim((string)$_POST['class_id']) : '';
        $classId = $classIdInput === '' ? null : filter_var($classIdInput, FILTER_VALIDATE_INT);
        $classSchoolId = null;
        $newAvatar = null;
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Nama harus terdiri dari 2–120 karakter.';
        if (mb_strlen($bio) > 500) $errors[] = 'Bio maksimal 500 karakter.';
        if ($newPassword !== '' && strlen($newPassword) < 8) $errors[] = 'Password baru minimal 8 karakter.';
        if ($newPassword !== $confirmPassword) $errors[] = 'Konfirmasi password baru tidak sama.';
        if (($user['role'] ?? '') === 'user' && $schoolClassSchemaReady) {
            if (!$hasClassSelection) {
                $currentClassStmt = $pdo->prepare('SELECT school_id, class_id FROM users WHERE id=? AND role=\'user\'');
                $currentClassStmt->execute([$user['id']]);
                $currentClass = $currentClassStmt->fetch();
                if ($currentClass) {
                    $classId = $currentClass['class_id'] !== null ? (int)$currentClass['class_id'] : null;
                    $classSchoolId = $currentClass['school_id'] !== null ? (int)$currentClass['school_id'] : null;
                }
            } elseif ($classIdInput !== '' && ($classId === false || $classId < 1)) {
                $errors[] = 'Kelas yang dipilih tidak valid.';
            } elseif ($classId !== null) {
                $classStmt = $pdo->prepare('SELECT c.school_id, c.status, s.status AS school_status, u.class_id AS current_class_id FROM school_classes c JOIN schools s ON s.id=c.school_id LEFT JOIN users u ON u.id=? WHERE c.id=?');
                $classStmt->execute([$user['id'], $classId]);
                $class = $classStmt->fetch();
                if (!$class || (($class['status'] !== 'active' || $class['school_status'] !== 'active') && (int)$class['current_class_id'] !== $classId)) {
                    $errors[] = 'Kelas yang dipilih sudah tidak tersedia.';
                } else {
                    $classSchoolId = (int)$class['school_id'];
                }
            }
        }

        if (!$errors) {
            $avatarFile = $_FILES['avatar'] ?? null;
            if (is_array($avatarFile) && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $newAvatar = store_profile_avatar($avatarFile);
                } catch (\RuntimeException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }
        if (!$errors) {
            try {
                if (($user['role'] ?? '') === 'user' && $schoolClassSchemaReady && $newPassword !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ?, avatar = COALESCE(?, avatar), school_id = ?, class_id = ?, password_hash = ? WHERE id = ?');
                    $stmt->execute([$name, $bio ?: null, $newAvatar, $classSchoolId, $classId, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
                } elseif (($user['role'] ?? '') === 'user' && $schoolClassSchemaReady) {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ?, avatar = COALESCE(?, avatar), school_id = ?, class_id = ? WHERE id = ?');
                    $stmt->execute([$name, $bio ?: null, $newAvatar, $classSchoolId, $classId, $user['id']]);
                } elseif ($newPassword !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ?, avatar = COALESCE(?, avatar), password_hash = ? WHERE id = ?');
                    $stmt->execute([$name, $bio ?: null, $newAvatar, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ?, avatar = COALESCE(?, avatar) WHERE id = ?');
                    $stmt->execute([$name, $bio ?: null, $newAvatar, $user['id']]);
                }
            } catch (Throwable $exception) {
                delete_profile_avatar($newAvatar);
                throw $exception;
            }
            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['bio'] = $bio;
            if ($newAvatar !== null) {
                delete_profile_avatar($user['avatar'] ?? null);
                $_SESSION['user']['avatar'] = $newAvatar;
            }
            flash('success', 'Profil berhasil diperbarui.');
            redirect('profile');
        }
        $page = 'profile';
    } elseif ($action === 'enroll') {
        $user = require_login();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        if (!$courseId) {
            flash('error', 'Kursus tidak valid.');
        } else {
            $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
            $check = $pdo->prepare("SELECT c.id FROM courses c WHERE c.id=? AND c.is_active=1{$visibilityClause}");
            $check->execute($schoolCourseVisibilitySchemaReady ? [$courseId, $user['id']] : [$courseId]);
            if (!$check->fetch()) {
                flash('error', 'Kursus tidak ditemukan atau tidak aktif.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE status = IF(status = 'completed', 'completed', 'active')");
                $stmt->execute([$user['id'], $courseId]);
                flash('success', 'Kursus ditambahkan ke pembelajaran Anda.');
            }
        }
        redirect('learn', ['course_id' => $courseId]);
    } elseif ($action === 'admin_review_lesson') {
        $reviewer = require_login();
        $reviewerRole = (string)($reviewer['role'] ?? '');
        if (in_array($reviewerRole, ['admin', 'super_admin'], true)) {
            $admin = $reviewer;
            $reviewPage = 'admin';
            $reviewParams = [];
            $reviewSchoolId = null;
        } elseif ($reviewerRole === 'school_admin') {
            $admin = $reviewer;
            $reviewPage = 'school-student';
            $studentId = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT);
            $reviewParams = $studentId && $studentId > 0 ? ['user_id' => $studentId] : [];
            $schoolStmt = $pdo->prepare("SELECT school_id FROM users WHERE id=? AND role='school_admin' AND status='active'");
            $schoolStmt->execute([$reviewer['id']]);
            $reviewSchoolId = $schoolStmt->fetchColumn();
            if (!$reviewSchoolId || !$reviewParams) {
                flash('error', 'Akun sekolah atau pengguna yang dipilih tidak valid.');
                redirect('school-admin');
            }
        } else {
            http_response_code(403);
            exit('Aksi ini hanya dapat dilakukan oleh administrator.');
        }
        if (!approval_schema_ready($pdo) || !practice_schema_ready($pdo)) {
            flash('error', 'Fitur peninjauan kode belum aktif. Jalankan migrasi persetujuan progres dan database/migrate_practice_code.sql pada database codenexa_db.');
            redirect($reviewPage, $reviewParams);
        }
        $progressId = filter_input(INPUT_POST, 'progress_id', FILTER_VALIDATE_INT) ?: 0;
        $decision = (string)($_POST['decision'] ?? '');
        $reviewNote = trim((string)($_POST['review_note'] ?? ''));
        if (!$progressId || !in_array($decision, ['approve', 'reject'], true) || mb_strlen($reviewNote) > 500) {
            $validationMessage = !$progressId
                ? 'ID pengajuan tidak terkirim. Muat ulang halaman lalu coba lagi.'
                : (!in_array($decision, ['approve', 'reject'], true)
                    ? 'Pilihan Setujui/Tolak tidak terkirim. Muat ulang halaman lalu klik tombol keputusan.'
                    : 'Catatan review melebihi batas 500 karakter.');
            flash('error', $validationMessage);
            redirect($reviewPage, $reviewParams);
        }

        try {
            $pdo->beginTransaction();
            $reviewScope = $reviewSchoolId !== null ? ' AND u.school_id=? AND lp.user_id=?' : '';
            $stmt = $pdo->prepare("SELECT lp.id, lp.user_id, lp.lesson_id, lp.xp_awarded, l.title AS lesson_title, l.xp_reward, m.course_id, c.title AS course_title FROM lesson_progress lp JOIN users u ON u.id=lp.user_id JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE lp.id=? AND lp.status='pending'{$reviewScope} FOR UPDATE");
            $stmt->execute($reviewSchoolId !== null
                ? [$progressId, $reviewSchoolId, $reviewParams['user_id']]
                : [$progressId]);
            $review = $stmt->fetch();
            if (!$review) {
                $pdo->rollBack();
                flash('error', 'Pengajuan sudah ditinjau atau tidak ditemukan.');
                redirect($reviewPage, $reviewParams);
            }

            $approved = $decision === 'approve';
            $newStatus = $approved ? 'completed' : 'rejected';
            $stmt = $pdo->prepare('UPDATE lesson_progress SET status=?, reviewed_by=?, reviewed_at=NOW(), review_note=?, completed_at=? , xp_awarded=? WHERE id=? AND status=\'pending\'');
            $stmt->execute([$newStatus, $admin['id'], $reviewNote !== '' ? $reviewNote : null, $approved ? date('Y-m-d H:i:s') : null, $approved ? 1 : $review['xp_awarded'], $progressId]);
            if ($approved && !(bool)$review['xp_awarded']) {
                $xpReward = (int)$review['xp_reward'];
                $stmt = $pdo->prepare('UPDATE users SET level=FLOOR((xp + ?) / 100) + 1, xp=xp + ? WHERE id=?');
                $stmt->execute([$xpReward, $xpReward, $review['user_id']]);
            }

            $courseCompleted = false;
            if ($approved) {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN lp.status='completed' THEN 1 ELSE 0 END) AS completed FROM lessons l JOIN modules m ON m.id=l.module_id LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.user_id=? WHERE m.course_id=? AND m.is_active=1 AND l.is_active=1");
                $stmt->execute([$review['user_id'], $review['course_id']]);
                $totals = $stmt->fetch();
                $courseCompleted = (int)$totals['total'] > 0 && (int)$totals['completed'] === (int)$totals['total'];
                $activityTimestampUpdate = learning_activity_schema_ready($pdo) ? ', last_activity_at=NOW()' : '';
                $stmt = $pdo->prepare("UPDATE enrollments SET last_lesson_id=?{$activityTimestampUpdate}, status=?, completed_at=IF(?,COALESCE(completed_at,NOW()),NULL) WHERE user_id=? AND course_id=?");
                $stmt->execute([$review['lesson_id'], $courseCompleted ? 'completed' : 'active', $courseCompleted ? 1 : 0, $review['user_id'], $review['course_id']]);
            }

            $noticeTitle = !$approved
                ? 'Pengajuan progres perlu diperbaiki'
                : ($courseCompleted ? 'Kursus selesai — sertifikat tersedia' : 'Progres pembelajaran disetujui');
            $noticeMessage = $approved
                ? 'Materi "' . $review['lesson_title'] . '" disetujui. +' . (int)$review['xp_reward'] . ' XP.'
                : 'Pengajuan materi "' . $review['lesson_title'] . '" ditolak.';
            if ($approved && $courseCompleted) {
                $noticeMessage = 'Selamat! Kursus "' . $review['course_title'] . '" telah selesai. Sertifikat CodeMif Anda sudah tersedia di dashboard.';
            }
            if ($reviewNote !== '') {
                $commentPrefix = ' Komentar admin: ';
                $availableCommentLength = max(0, 500 - mb_strlen($noticeMessage) - mb_strlen($commentPrefix));
                $noticeMessage .= $commentPrefix . mb_substr($reviewNote, 0, $availableCommentLength);
            }
            $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
            $stmt->execute([$review['user_id'], $noticeTitle, $noticeMessage, $approved ? 'success' : 'info']);
            $pdo->commit();
            flash('success', $approved ? 'Progres disetujui dan XP diberikan.' : 'Pengajuan ditolak dan catatan dikirim ke pengguna.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            \App\Core\Logger::error('Persetujuan progres gagal disimpan', [
                'progress_id' => $progressId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', 'Peninjauan gagal disimpan. Silakan coba kembali.');
        }
        redirect($reviewPage, $reviewParams);
    } elseif ($action === 'submit_lesson') {
        $user = require_login();
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $lessonId = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT) ?: 0;
        $submittedCode = $_POST['submitted_code'] ?? '';
        if (!is_string($submittedCode)) {
            flash('error', 'Kode praktik tidak valid. Silakan ketik ulang kode lalu coba lagi.');
            redirect('learn', ['course_id' => $courseId, 'lesson_id' => $lessonId]);
        }
        $submittedCode = trim($submittedCode);
        if (!approval_schema_ready($pdo) || !practice_schema_ready($pdo)) {
            flash('error', 'Fitur pengajuan praktik belum aktif. Admin perlu menjalankan migrasi persetujuan progres dan database/migrate_practice_code.sql terlebih dahulu.');
            redirect('learn', ['course_id' => $courseId, 'lesson_id' => $lessonId]);
        }
        if ($submittedCode === '' || mb_strlen($submittedCode) > 60000) {
            flash('error', 'Ketik kode praktik terlebih dahulu (maksimal 60.000 karakter).');
            redirect('learn', ['course_id' => $courseId, 'lesson_id' => $lessonId]);
        }
        $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady, 'c');
        $stmt = $pdo->prepare("SELECT l.id FROM lessons l JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id JOIN enrollments e ON e.course_id=m.course_id WHERE l.id=? AND m.course_id=? AND c.is_active=1 AND l.is_active=1 AND m.is_active=1 AND e.user_id=?{$visibilityClause}");
        $stmt->execute($schoolCourseVisibilitySchemaReady ? [$lessonId, $courseId, $user['id'], $user['id']] : [$lessonId, $courseId, $user['id']]);
        $lesson = $stmt->fetch();
        if (!$lesson) {
            flash('error', 'Materi tidak tersedia atau kursus belum diikuti.');
            redirect('courses');
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, status, evidence_image, submitted_code FROM lesson_progress WHERE user_id=? AND lesson_id=? FOR UPDATE');
            $stmt->execute([$user['id'], $lessonId]);
            $progress = $stmt->fetch();
            $legacyCompletion = $progress
                && $progress['status'] === 'completed'
                && trim((string)$progress['submitted_code']) === '';
            if ($progress && $progress['status'] === 'completed' && trim((string)$progress['submitted_code']) !== '') {
                $pdo->commit();
                flash('success', 'Materi ini sudah disetujui sebelumnya.');
            } elseif ($progress && $progress['status'] === 'pending') {
                $pdo->commit();
                flash('success', 'Pengajuan materi ini sedang menunggu persetujuan admin.');
            } else {
                $oldEvidencePath = $progress['evidence_image'] ?? null;
                if ($progress) {
                    $stmt = $pdo->prepare("UPDATE lesson_progress SET status='pending', submitted_at=NOW(), reviewed_by=NULL, reviewed_at=NULL, review_note=NULL, completed_at=NULL, evidence_image=NULL, submitted_code=? WHERE id=?");
                    $stmt->execute([$submittedCode, $progress['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, submitted_at, submitted_code) VALUES (?, ?, 'pending', NOW(), ?)");
                    $stmt->execute([$user['id'], $lessonId, $submittedCode]);
                }
                if ($legacyCompletion) {
                    $stmt = $pdo->prepare("UPDATE lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id SET lp.status='in_progress', lp.completed_at=NULL WHERE lp.user_id=? AND m.course_id=? AND lp.status='completed' AND (lp.submitted_code IS NULL OR lp.submitted_code='') AND lp.lesson_id<>?");
                    $stmt->execute([$user['id'], $courseId, $lessonId]);
                }
                $stmt = $pdo->prepare("UPDATE enrollments SET status='active', completed_at=NULL WHERE user_id=? AND course_id=? AND status='completed'");
                $stmt->execute([$user['id'], $courseId]);
                $pdo->commit();
                flash('success', 'Pengajuan penyelesaian materi berhasil dikirim untuk ditinjau.');
                delete_lesson_evidence($oldEvidencePath);
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            \App\Core\Logger::error('Pengajuan progres gagal disimpan', [
                'user_id' => $user['id'],
                'lesson_id' => $lessonId,
                'message' => $exception->getMessage(),
            ]);
            flash('error', $exception instanceof \RuntimeException ? $exception->getMessage() : 'Pengajuan belum tersimpan. Silakan coba lagi.');
        }
        redirect('learn', ['course_id' => $courseId, 'lesson_id' => $lessonId]);
    }
}

if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$user = current_user();
$user = require_active_school_admin($pdo, $user);
if ($page === 'certificate') {
    $user = require_login();
    $certificateCourseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
    $stmt = $pdo->prepare("SELECT e.id AS enrollment_id, e.completed_at, u.full_name, c.title AS course_title FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.course_id=? AND e.status='completed' AND e.completed_at IS NOT NULL");
    $stmt->execute([$user['id'], $certificateCourseId]);
    $certificate = $stmt->fetch();
    if (!$certificate) {
        flash('error', 'Sertifikat hanya tersedia untuk kursus yang sudah selesai.');
        redirect('dashboard');
    }
}
if (in_array($page, ['dashboard','courses','profile','learn','admin','ai-tutor','change-password','school-admins','school-admin','school-class','school-student'], true) && !$user) redirect('login');
if ($user && (int)($user['must_change_password'] ?? 0) === 1 && $page !== 'change-password') redirect('change-password');
if ($page === 'change-password' && $user && (int)($user['must_change_password'] ?? 0) !== 1) redirect('dashboard');
if (in_array($page, ['login','register'], true) && $user) {
    redirect((int)($user['must_change_password'] ?? 0) === 1 ? 'change-password' : 'dashboard');
}
if ($page === 'admin' && $user && !in_array($user['role'], ['admin', 'super_admin'], true)) {
    flash('error', 'Halaman ini hanya dapat diakses oleh administrator.');
    redirect('dashboard');
}
if ($page === 'school-admins' && $user && ($user['role'] ?? '') !== 'super_admin') {
    flash('error', 'Kelola Admin Sekolah hanya dapat diakses oleh Super Admin.');
    redirect('dashboard');
}
if (in_array($page, ['school-admin', 'school-class', 'school-student'], true) && $user && ($user['role'] ?? '') !== 'school_admin') {
    flash('error', 'Halaman ini hanya dapat diakses oleh admin sekolah.');
    redirect('dashboard');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$courses = [];
$learningCourse = null;
$learningModules = [];
$learningLessons = [];
$selectedLesson = null;
$selectedLessonIndex = 0;
$learningCompletedCount = 0;
$learningPendingCount = 0;
$stats = ['enrolled' => 0, 'completed' => 0, 'lessons' => 0];
$notifications = [];
$unreadNotifications = 0;
$approvalSchemaReady = false;
$practiceSchemaReady = false;
$learningActivitySchemaReady = false;
$schoolAdminSchemaReady = school_admin_schema_ready($pdo);
$schoolAdminAccounts = [];
$schoolAdminSchools = [];
$schoolAdminSchool = null;
$schoolAdminClasses = [];
$schoolAdminClassStudents = [];
$schoolAdminClassGradeCounts = [];
$schoolAdminClassAccounts = [];
$schoolAdminGradeFilter = (string)($_GET['grade'] ?? '');
$schoolAdminGradeCounts = ['X' => 0, 'XI' => 0, 'XII' => 0, 'unassigned' => 0];
$schoolAdminCourses = [];
$schoolCourseVisibilityReady = false;
$schoolAdminSelectedClass = null;
$schoolAdminSelectedStudent = null;
$schoolAdminStudentSubmissions = [];
$schoolReviewSchemaReady = false;
$adminStats = [];
$adminUsers = [];
$adminCourses = [];
$adminLearningActivity = [];
$adminPendingReviews = [];
$tutorCourses = [];
$tutorCourse = null;
$tutorMessages = [];
$dashboardCertificates = [];
$certificate = $certificate ?? null;
if ($user) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    $unreadNotifications = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 6');
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();
}
if ($user && in_array($page, ['courses', 'ai-tutor', 'learn'], true)) {
    try {
        ensure_learning_paths($pdo);
        \App\Services\CourseCurriculumSeeder::seed($pdo);
    } catch (Throwable $exception) {
        \App\Core\Logger::error('Kurikulum kursus gagal disiapkan', [
            'message' => $exception->getMessage(),
        ]);
        flash('error', $exception instanceof \PDOException
            ? 'Materi kursus belum dapat disiapkan. Periksa koneksi database dan log aplikasi.'
            : $exception->getMessage());
    }
}
if ($user && $page === 'ai-tutor') {
    $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
    $stmt = $pdo->prepare("SELECT c.id, c.title, e.status AS enrollment_status FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=? WHERE c.is_active=1{$visibilityClause} ORDER BY c.title");
    $stmt->execute($schoolCourseVisibilitySchemaReady ? [$user['id'], $user['id']] : [$user['id']]);
    $tutorCourses = $stmt->fetchAll();
    $requestedCourseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
    foreach ($tutorCourses as $course) {
        if ((int)$course['id'] === $requestedCourseId) {
            $tutorCourse = $course;
            break;
        }
    }
    if (!$tutorCourse && $tutorCourses) $tutorCourse = $tutorCourses[0];
    if ($tutorCourse && $tutorCourse['enrollment_status'] !== null) {
        $historyKey = $user['id'] . ':' . $tutorCourse['id'];
        $tutorMessages = $_SESSION['course_tutor'][$historyKey] ?? [];
    }
}
if ($user && $page === 'admin') {
    $approvalSchemaReady = approval_schema_ready($pdo);
    $practiceSchemaReady = practice_schema_ready($pdo);
    $learningActivitySchemaReady = learning_activity_schema_ready($pdo);
    $adminStats['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = \'user\'')->fetchColumn();
    $adminStats['active_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'")->fetchColumn();
    $adminStats['courses'] = (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    $adminStats['active_courses'] = (int)$pdo->query('SELECT COUNT(*) FROM courses WHERE is_active = 1')->fetchColumn();
    $adminStats['enrollments'] = (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
    $adminUsers = $pdo->query("SELECT id, full_name, username, email, status, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 12")->fetchAll();
    $adminCourses = $pdo->query('SELECT c.id, c.title, c.level, c.is_active, cat.name AS category_name, COUNT(e.id) AS enrollment_count FROM courses c JOIN categories cat ON cat.id = c.category_id LEFT JOIN enrollments e ON e.course_id = c.id GROUP BY c.id ORDER BY c.created_at DESC')->fetchAll();
    if ($approvalSchemaReady && $practiceSchemaReady) {
        $adminPendingReviews = $pdo->query("SELECT lp.id, lp.user_id, lp.lesson_id, lp.submitted_at, lp.evidence_image, lp.submitted_code, u.full_name, u.username, c.title AS course_title, m.title AS module_title, l.title AS lesson_title, l.xp_reward, (SELECT COUNT(*) FROM lessons stage_l JOIN modules stage_m ON stage_m.id=stage_l.module_id WHERE stage_m.course_id=c.id AND stage_m.is_active=1 AND stage_l.is_active=1 AND (stage_m.sort_order<m.sort_order OR (stage_m.sort_order=m.sort_order AND (stage_l.sort_order<l.sort_order OR (stage_l.sort_order=l.sort_order AND stage_l.id<=l.id))))) AS lesson_number FROM lesson_progress lp JOIN users u ON u.id=lp.user_id JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE lp.status='pending' ORDER BY lp.submitted_at ASC, lp.id ASC LIMIT 30")->fetchAll();
    }
    if ($learningActivitySchemaReady) {
        $adminLearningActivity = $pdo->query("SELECT u.full_name, u.username, c.id AS course_id, c.title AS course_title, e.status AS enrollment_status, e.last_activity_at, l.title AS lesson_title, m.title AS module_title, (SELECT COUNT(*) FROM lessons total_l JOIN modules total_m ON total_m.id=total_l.module_id WHERE total_m.course_id=c.id AND total_m.is_active=1 AND total_l.is_active=1) AS total_lessons, (SELECT COUNT(*) FROM lesson_progress done_lp JOIN lessons done_l ON done_l.id=done_lp.lesson_id JOIN modules done_m ON done_m.id=done_l.module_id WHERE done_lp.user_id=u.id AND done_m.course_id=c.id AND done_m.is_active=1 AND done_l.is_active=1 AND done_lp.status='completed') AS completed_lessons, (SELECT COUNT(*) FROM lesson_progress pending_lp JOIN lessons pending_l ON pending_l.id=pending_lp.lesson_id JOIN modules pending_m ON pending_m.id=pending_l.module_id WHERE pending_lp.user_id=u.id AND pending_m.course_id=c.id AND pending_m.is_active=1 AND pending_l.is_active=1 AND pending_lp.status='pending') AS pending_lessons, (SELECT COUNT(*) FROM lessons stage_l JOIN modules stage_m ON stage_m.id=stage_l.module_id WHERE stage_m.course_id=c.id AND stage_m.is_active=1 AND stage_l.is_active=1 AND (stage_m.sort_order<m.sort_order OR (stage_m.sort_order=m.sort_order AND (stage_l.sort_order<l.sort_order OR (stage_l.sort_order=l.sort_order AND stage_l.id<=l.id))))) AS current_stage FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id LEFT JOIN lessons l ON l.id=e.last_lesson_id LEFT JOIN modules m ON m.id=l.module_id WHERE e.status='active' ORDER BY e.last_activity_at DESC, e.enrolled_at DESC LIMIT 50")->fetchAll();
    }
}
if ($user && $schoolAdminSchemaReady && $page === 'school-admins') {
    $schoolAdminAccounts = $pdo->query("SELECT u.id AS user_id, s.id AS school_id, s.name AS school_name, s.status AS school_status, u.full_name, u.username, u.email, u.status AS account_status, u.created_at FROM users u JOIN schools s ON s.id=u.school_id WHERE u.role='school_admin' ORDER BY s.created_at DESC, u.created_at DESC, u.id DESC LIMIT 100")->fetchAll();
    $schoolAdminSchools = $pdo->query("SELECT id, name, status FROM schools ORDER BY name")->fetchAll();
}
if ($user && $schoolAdminSchemaReady && in_array($page, ['school-admin', 'school-class', 'school-student'], true)) {
    $stmt = $pdo->prepare("SELECT s.id, s.name, s.status FROM schools s JOIN users u ON u.school_id=s.id WHERE u.id=? AND u.role='school_admin' LIMIT 1");
    $stmt->execute([$user['id']]);
    $schoolAdminSchool = $stmt->fetch();
    if ($schoolAdminSchool && $schoolClassSchemaReady) {
        if (in_array($page, ['school-class', 'school-student'], true)) {
            if ($page === 'school-student') {
                $schoolReviewSchemaReady = approval_schema_ready($pdo) && practice_schema_ready($pdo);
                $studentId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: 0;
                $gradeLevelSql = $studentGradeSchemaReady ? 'u.grade_level' : 'NULL AS grade_level';
                $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.email, u.status, {$gradeLevelSql}, c.id AS class_id, c.name AS class_name FROM users u JOIN school_classes c ON c.id=u.class_id AND c.school_id=u.school_id WHERE u.id=? AND u.school_id=? AND u.role='user' LIMIT 1");
                $stmt->execute([$studentId, $schoolAdminSchool['id']]);
                $schoolAdminSelectedStudent = $stmt->fetch();
                if (!$schoolAdminSelectedStudent) {
                    flash('error', 'Akun siswa tidak ditemukan di sekolah Anda.');
                    redirect('school-admin');
                }
                if ($schoolReviewSchemaReady) {
                    $stmt = $pdo->prepare("SELECT lp.id AS progress_id, lp.status, lp.submitted_at, lp.submitted_code, lp.review_note, lp.reviewed_at, l.title AS lesson_title, l.xp_reward, m.title AS module_title, c.title AS course_title, (SELECT COUNT(*) FROM lessons stage_l JOIN modules stage_m ON stage_m.id=stage_l.module_id WHERE stage_m.course_id=c.id AND stage_m.is_active=1 AND stage_l.is_active=1 AND (stage_m.sort_order<m.sort_order OR (stage_m.sort_order=m.sort_order AND (stage_l.sort_order<l.sort_order OR (stage_l.sort_order=l.sort_order AND stage_l.id<=l.id))))) AS lesson_number FROM lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE lp.user_id=? AND lp.submitted_at IS NOT NULL AND lp.submitted_code IS NOT NULL AND lp.submitted_code<>'' ORDER BY lp.submitted_at DESC, lp.id DESC");
                    $stmt->execute([$schoolAdminSelectedStudent['id']]);
                    $schoolAdminStudentSubmissions = $stmt->fetchAll();
                }
            } else {
                $classId = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT) ?: 0;
                $stmt = $pdo->prepare('SELECT id, name, status FROM school_classes WHERE id=? AND school_id=? LIMIT 1');
                $stmt->execute([$classId, $schoolAdminSchool['id']]);
                $schoolAdminSelectedClass = $stmt->fetch();
                if (!$schoolAdminSelectedClass) {
                    flash('error', 'Kelas tidak ditemukan di sekolah Anda.');
                    redirect('school-admin');
                }
                $schoolReviewSchemaReady = approval_schema_ready($pdo) && practice_schema_ready($pdo);
                $submissionCountSql = $schoolReviewSchemaReady
                    ? ", (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.user_id=u.id AND lp.submitted_at IS NOT NULL AND lp.submitted_code IS NOT NULL AND lp.submitted_code<>'') AS submission_count, (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.user_id=u.id AND lp.status='pending' AND lp.submitted_code IS NOT NULL AND lp.submitted_code<>'') AS pending_review_count"
                    : ', 0 AS submission_count, 0 AS pending_review_count';
                $gradeLevelSql = $studentGradeSchemaReady ? 'u.grade_level' : 'NULL AS grade_level';
                $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.email, u.status, u.created_at, {$gradeLevelSql}{$submissionCountSql} FROM users u WHERE u.school_id=? AND u.class_id=? AND u.role='user' ORDER BY u.full_name, u.id");
                $stmt->execute([$schoolAdminSchool['id'], $schoolAdminSelectedClass['id']]);
                $schoolAdminClassAccounts = $stmt->fetchAll();
                foreach ($schoolAdminClassAccounts as $student) {
                    $studentGrade = $student['grade_level'] ?? '';
                    $gradeKey = in_array($studentGrade, ['X', 'XI', 'XII'], true) ? $studentGrade : 'unassigned';
                    $schoolAdminGradeCounts[$gradeKey]++;
                }
                if (!in_array($schoolAdminGradeFilter, ['X', 'XI', 'XII', 'unassigned'], true)) {
                    $schoolAdminGradeFilter = '';
                }
                if ($schoolAdminGradeFilter !== '') {
                    $schoolAdminClassAccounts = array_values(array_filter(
                        $schoolAdminClassAccounts,
                        static fn(array $student): bool => $schoolAdminGradeFilter === 'unassigned'
                            ? empty($student['grade_level'])
                            : ($student['grade_level'] ?? '') === $schoolAdminGradeFilter
                    ));
                }
            }
        } else {
            $stmt = $pdo->prepare("SELECT id, name, status FROM school_classes WHERE school_id=? ORDER BY name");
            $stmt->execute([$schoolAdminSchool['id']]);
            $schoolAdminClasses = $stmt->fetchAll();
            $classGradeSelect = $studentGradeSchemaReady ? ', grade_level' : ', NULL AS grade_level';
            $stmt = $pdo->prepare("SELECT id, full_name, username, email, status, class_id{$classGradeSelect} FROM users WHERE school_id=? AND role='user' AND class_id IS NOT NULL ORDER BY full_name, id");
            $stmt->execute([$schoolAdminSchool['id']]);
            foreach ($stmt->fetchAll() as $student) {
                $schoolAdminClassStudents[(int)$student['class_id']][] = $student;
                $classId = (int)$student['class_id'];
                $schoolAdminClassGradeCounts[$classId] ??= ['X' => 0, 'XI' => 0, 'XII' => 0, 'unassigned' => 0];
                $studentGrade = $student['grade_level'] ?? '';
                $gradeKey = in_array($studentGrade, ['X', 'XI', 'XII'], true) ? $studentGrade : 'unassigned';
                $schoolAdminClassGradeCounts[$classId][$gradeKey]++;
            }
        }
    }
}
if ($user && $schoolAdminSchemaReady && $page === 'school-admin' && $schoolAdminSchool) {
    $schoolCourseVisibilityReady = $schoolCourseVisibilitySchemaReady;
    if ($schoolCourseVisibilityReady) {
        $stmt = $pdo->prepare('SELECT c.id, c.title, c.level, c.is_active, cat.name AS category_name, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS enrollment_count, COALESCE(scv.is_visible, 1) AS school_visible FROM courses c JOIN categories cat ON cat.id=c.category_id LEFT JOIN school_course_visibility scv ON scv.course_id=c.id AND scv.school_id=? ORDER BY c.title');
        $stmt->execute([$schoolAdminSchool['id']]);
        $schoolAdminCourses = $stmt->fetchAll();
    }
}
if ($user && $page === 'dashboard') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = ?');
    $stmt->execute([$user['id']]); $stats['enrolled'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user['id']]); $stats['completed'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user['id']]); $stats['lessons'] = (int)$stmt->fetchColumn();
    $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
    $stmt = $pdo->prepare("SELECT c.*, cat.name AS category_name, e.status AS enrollment_status FROM enrollments e JOIN courses c ON c.id=e.course_id JOIN categories cat ON cat.id=c.category_id WHERE e.user_id=? AND c.is_active=1{$visibilityClause} ORDER BY e.enrolled_at DESC LIMIT 3");
    $stmt->execute($schoolCourseVisibilitySchemaReady ? [$user['id'], $user['id']] : [$user['id']]); $courses = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT c.id, c.title, e.completed_at FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.status='completed' AND e.completed_at IS NOT NULL ORDER BY e.completed_at DESC");
    $stmt->execute([$user['id']]); $dashboardCertificates = $stmt->fetchAll();
}
if ($user && $page === 'courses') {
    $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
    $stmt = $pdo->prepare("SELECT c.*, cat.name AS category_name, e.status AS enrollment_status FROM courses c JOIN categories cat ON cat.id=c.category_id LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=? WHERE c.is_active=1{$visibilityClause} ORDER BY c.created_at DESC");
    $stmt->execute($schoolCourseVisibilitySchemaReady ? [$user['id'], $user['id']] : [$user['id']]); $courses = $stmt->fetchAll();
}
if ($user && $page === 'profile') {
    $stmt = $pdo->prepare('UPDATE users SET level=FLOOR(xp / 100) + 1 WHERE id=?');
    $stmt->execute([$user['id']]);
    $stmt = $pdo->prepare('SELECT full_name, username, email, bio, avatar, xp, level, created_at FROM users WHERE id=?');
    $stmt->execute([$user['id']]); $profile = $stmt->fetch();
}
if ($user && $page === 'learn') {
    $courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
    $visibilityClause = school_course_visibility_clause($schoolCourseVisibilitySchemaReady);
    $stmt = $pdo->prepare("SELECT c.*, cat.name AS category_name, e.status AS enrollment_status, e.last_lesson_id FROM courses c JOIN categories cat ON cat.id=c.category_id JOIN enrollments e ON e.course_id=c.id AND e.user_id=? WHERE c.id=? AND c.is_active=1{$visibilityClause}");
    $stmt->execute($schoolCourseVisibilitySchemaReady ? [$user['id'], $courseId, $user['id']] : [$user['id'], $courseId]);
    $learningCourse = $stmt->fetch();
    if (!$learningCourse) {
        flash('error', 'Ikuti kursus terlebih dahulu untuk membuka materinya.');
        redirect('courses');
    }
    $reviewNoteColumn = approval_schema_ready($pdo) ? 'lp.review_note' : 'NULL AS review_note';
    $submittedCodeColumn = practice_schema_ready($pdo) ? 'lp.submitted_code' : 'NULL AS submitted_code';
    $stmt = $pdo->prepare("SELECT m.id AS module_id, m.title AS module_title, l.id AS lesson_id, l.title AS lesson_title, l.content_theory, l.code_example, l.code_language, l.code_explanation, l.exercise, l.project_task, l.duration_minutes, l.xp_reward, lp.status AS progress_status, {$reviewNoteColumn}, {$submittedCodeColumn} FROM modules m JOIN lessons l ON l.module_id=m.id LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.user_id=? WHERE m.course_id=? AND m.is_active=1 AND l.is_active=1 ORDER BY m.sort_order, l.sort_order, l.id");
    $stmt->execute([$user['id'], $courseId]);
    foreach ($stmt->fetchAll() as $lessonRow) {
        $moduleId = (int)$lessonRow['module_id'];
        if (!isset($learningModules[$moduleId])) {
            $learningModules[$moduleId] = ['title' => $lessonRow['module_title'], 'lessons' => []];
        }
        $learningModules[$moduleId]['lessons'][] = $lessonRow;
        $learningLessons[] = $lessonRow;
        if ($lessonRow['progress_status'] === 'completed') $learningCompletedCount++;
        if ($lessonRow['progress_status'] === 'pending') $learningPendingCount++;
    }
    $requestedLessonId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT) ?: (int)$learningCourse['last_lesson_id'];
    foreach ($learningLessons as $index => $lessonRow) {
        if ((int)$lessonRow['lesson_id'] === $requestedLessonId) {
            $selectedLesson = $lessonRow;
            $selectedLessonIndex = $index;
            break;
        }
    }
    if (!$selectedLesson && $learningLessons) $selectedLesson = $learningLessons[0];
    if ($selectedLesson && $learningCourse['enrollment_status'] === 'active' && learning_activity_schema_ready($pdo)) {
        $stmt = $pdo->prepare("UPDATE enrollments SET last_lesson_id=?, last_activity_at=NOW() WHERE user_id=? AND course_id=? AND status='active'");
        $stmt->execute([$selectedLesson['lesson_id'], $user['id'], $courseId]);
    }
}
$levelLabels = ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir'];
$pageTitles = [
    'dashboard' => 'Dashboard',
    'courses' => 'Jelajahi Kursus',
    'profile' => 'Profil Saya',
    'learn' => 'Ruang Belajar',
    'certificate' => 'Sertifikat Kelulusan',
    'admin' => 'Panel Admin',
    'change-password' => 'Ganti Password',
    'school-admins' => 'Kelola Admin Sekolah',
    'school-admin' => 'Panel Admin Sekolah',
    'school-class' => 'Akun Kelas',
    'school-student' => 'Hasil Praktik Siswa',
    'ai-tutor' => 'Tutor AI Kursus',
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(ucfirst($page)) ?> — CodeMif</title>
<link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/css/app.css?v=20261021-37">
<link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/css/password-visibility.css?v=20261021-1">
<?php if (in_array($page, ['school-admins', 'school-admin', 'school-class', 'school-student'], true)): ?><link rel="stylesheet" href="<?= e($baseUrl) ?>/assets/css/school-admins.css?v=20261021-9"><?php endif; ?>
<script defer src="<?= e($baseUrl) ?>/assets/js/app.js?v=20261021-7"></script>
</head>
<body class="<?= $page === 'certificate' ? 'certificate-page' : '' ?>">
<?php if ($user): ?>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= e(url('dashboard')) ?>"><span class="brand-mark">&lt;/&gt;</span><span>Code<span>Mif</span><small>LEARN · BUILD · GROW</small></span></a>
  <div class="nav-label">MENU UTAMA</div>
  <nav class="nav">
    <a class="<?= $page==='dashboard'?'active':'' ?>" href="<?= e(url('dashboard')) ?>"><span>▦</span> Dashboard</a>
    <a class="<?= $page==='courses'?'active':'' ?>" href="<?= e(url('courses')) ?>"><span>▤</span> Jelajahi Kursus</a>
    <a class="<?= $page==='ai-tutor'?'active':'' ?>" href="<?= e(url('ai-tutor')) ?>"><span>✦</span> Tutor AI Kursus</a>
    <a class="<?= $page==='profile'?'active':'' ?>" href="<?= e(url('profile')) ?>"><span>◎</span> Profil Saya</a>
        <?php if (in_array($user['role'], ['admin', 'super_admin'], true)): ?><a class="<?= $page==='admin'?'active':'' ?>" href="<?= e(url('admin')) ?>"><span>⚙</span> <?= $user['role'] === 'super_admin' ? 'Panel Super Admin' : 'Panel Admin' ?></a><?php endif; ?>
        <?php if ($user['role'] === 'super_admin'): ?><a class="<?= $page==='school-admins'?'active':'' ?>" href="<?= e(url('school-admins')) ?>"><span>♙</span> Kelola Admin Sekolah</a><?php elseif ($user['role'] === 'school_admin'): ?><a class="<?= in_array($page, ['school-admin', 'school-class', 'school-student'], true)?'active':'' ?>" href="<?= e(url('school-admin')) ?>"><span>⚙</span> Panel Admin Sekolah</a><?php endif; ?>
  </nav>
  <div class="sidebar-bottom"><div class="mini-user"><div class="avatar"><?php if (!empty($user['avatar'])): ?><img src="<?= e(url('profile-image')) ?>" alt="Foto profil <?= e($user['full_name']) ?>"><?php else: ?><?= e(mb_strtoupper(mb_substr($user['full_name'],0,1))) ?><?php endif; ?></div><div><b><?= e($user['full_name']) ?></b><small><?= e($user['role']) ?></small></div></div><a class="logout-link" href="<?= e(url('logout')) ?>" data-confirm="Yakin ingin keluar?">↪ Keluar</a></div>
</aside>
<main class="main-area">
<header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Buka menu">☰</button><div class="topbar-title"><div class="eyebrow">RUANG BELAJAR ANDA</div><h1><?= e($pageTitles[$page] ?? 'CodeMif') ?></h1></div><div class="topbar-actions"><div class="notification-wrap" id="notificationWrap"><button class="notification-button" id="notificationToggle" type="button" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" aria-controls="notificationPanel"><span aria-hidden="true">&#128276;</span><?php if ($unreadNotifications): ?><span class="notification-count"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span><?php endif; ?></button><section class="notification-panel" id="notificationPanel" aria-label="Notifikasi" hidden><div class="notification-heading"><strong>Notifikasi</strong><span><?= $unreadNotifications ?> belum dibaca</span></div><div class="notification-list"><?php if (!$notifications): ?><p class="notification-empty">Belum ada notifikasi.</p><?php else: foreach ($notifications as $notification): ?><article class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>"><span class="notification-dot" aria-hidden="true"></span><div><h2><?= e($notification['title']) ?></h2><p><?= e($notification['message']) ?></p><?php if (str_contains($notification['title'], 'sertifikat tersedia')): ?><a class="notification-certificate-link" href="<?= e(url('dashboard')) ?>#certificates">Klaim sertifikat →</a><?php endif; ?><time><?= e(date('d M Y, H:i', strtotime($notification['created_at']))) ?></time></div></article><?php endforeach; endif; ?></div><form method="post" class="notification-footer"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="read_notifications"><input type="hidden" name="return_page" value="<?= e($page) ?>"><?php if (in_array($page, ['learn', 'ai-tutor'], true) && isset($_GET['course_id'])): ?><input type="hidden" name="course_id" value="<?= (int)$_GET['course_id'] ?>"><?php endif; ?><button type="submit" <?= $unreadNotifications ? '' : 'disabled' ?>>Tandai semua dibaca</button></form></section></div><a class="top-user top-user-avatar" href="<?= e(url('profile')) ?>" aria-label="Buka profil <?= e($user['full_name']) ?>" title="Profil saya"><span class="top-user-avatar-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['full_name'],0,1))) ?></span><?php if (!empty($user['avatar'])): ?><img src="<?= e(url('profile-image')) ?>" alt="" onerror="this.remove()"><?php endif; ?><span class="online-dot" aria-hidden="true"></span></a></div></header>
<div class="content">
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>

<?php if ($page === 'admin'): ?>
<?php if (!$approvalSchemaReady || !$practiceSchemaReady): ?><div class="admin-migration-notice"><strong>Peninjauan praktik kode belum aktif</strong><p>Di phpMyAdmin, pilih database <code>codenexa_db</code>, lalu jalankan migrasi persetujuan progres dan impor <code>database/migrate_practice_code.sql</code>.</p></div><?php endif; ?>
<section class="admin-intro"><div><div class="eyebrow">PUSAT KONTROL</div><h2>Ringkasan CodeMif</h2><p>Kelola akun pengguna, kursus, dan hasil praktik kode.</p></div><span class="admin-role">Administrator</span></section>
<div class="admin-stats"><article><span>Total pengguna</span><strong><?= $adminStats['users'] ?></strong><small><?= $adminStats['active_users'] ?> akun aktif</small></article><article><span>Total kursus</span><strong><?= $adminStats['courses'] ?></strong><small><?= $adminStats['active_courses'] ?> kursus aktif</small></article><article><span>Pendaftaran</span><strong><?= $adminStats['enrollments'] ?></strong><small>Di seluruh kursus</small></article></div>
<?php if (!$learningActivitySchemaReady): ?><div class="admin-migration-notice"><strong>Pemantauan aktivitas belajar belum aktif</strong><p>Jalankan <code>database/migrate_learning_activity.sql</code> di phpMyAdmin agar materi terakhir dan waktu aktivitas tampil.</p></div><?php else: ?>
<section class="admin-section"><div class="section-heading"><div><h2>Pemantauan belajar <span class="review-count"><?= count($adminLearningActivity) ?></span></h2><p>Kursus aktif, soal terakhir yang dikerjakan, progres persetujuan, dan waktu aktivitas terakhir.</p></div></div><?php if (!$adminLearningActivity): ?><div class="admin-empty-panel">Belum ada pengguna yang sedang mengikuti kursus aktif.</div><?php else: ?><div class="admin-table-wrap"><table class="admin-table activity-table"><thead><tr><th>Pengguna</th><th>Kursus dan modul</th><th>Soal terakhir dikerjakan</th><th>Progres</th><th>Aktivitas terakhir</th></tr></thead><tbody><?php foreach ($adminLearningActivity as $activity): ?><?php $activityTotal = (int)$activity['total_lessons']; $activityCompleted = (int)$activity['completed_lessons']; $activityPercent = $activityTotal ? (int)round($activityCompleted / $activityTotal * 100) : 0; ?><tr><td><strong><?= e($activity['full_name']) ?></strong><small>@<?= e($activity['username']) ?></small></td><td><strong><?= e($activity['course_title']) ?></strong><small><?= e($activity['module_title'] ?: 'Belum membuka materi') ?></small></td><td><?= $activity['lesson_title'] ? 'Soal ke-' . (int)$activity['current_stage'] . ' · ' . e($activity['lesson_title']) : 'Belum membuka materi' ?><?php if ((int)$activity['pending_lessons'] > 0): ?><small><?= (int)$activity['pending_lessons'] ?> pengajuan menunggu review</small><?php endif; ?></td><td><div class="activity-progress"><span><strong><?= $activityCompleted ?>/<?= $activityTotal ?></strong> materi disetujui · <?= $activityPercent ?>%</span><span class="activity-progress-track"><i style="width:<?= $activityPercent ?>%"></i></span></div></td><td><?= $activity['last_activity_at'] ? e(date('d M Y, H:i', strtotime($activity['last_activity_at']))) : 'Belum ada aktivitas' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<section class="admin-section"><div class="section-heading"><div><h2>Peninjauan praktik <span class="review-count"><?= count($adminPendingReviews) ?></span></h2><p>Periksa kode yang diketik peserta sebelum menyetujui progres.</p></div></div><?php if (!$adminPendingReviews): ?><div class="admin-empty-panel">Belum ada praktik kode yang menunggu peninjauan.</div><?php else: ?><div class="review-list"><?php foreach ($adminPendingReviews as $reviewItem): ?><article class="review-card"><div class="review-details"><div><strong>Soal ke-<?= (int)$reviewItem['lesson_number'] ?> · <?= e($reviewItem['lesson_title']) ?></strong><small><?= e($reviewItem['course_title']) ?> · <?= e($reviewItem['module_title']) ?></small></div><span class="review-student"><?= e($reviewItem['full_name']) ?> <small>@<?= e($reviewItem['username']) ?></small></span><span class="review-date"><?= e(date('d M Y, H:i', strtotime($reviewItem['submitted_at']))) ?></span><span class="review-xp">+<?= (int)$reviewItem['xp_reward'] ?> XP</span></div><?php if (!empty($reviewItem['submitted_code'])): ?><details class="review-code"><summary>Lihat kode praktik</summary><pre><code><?= e($reviewItem['submitted_code']) ?></code></pre></details><?php endif; ?><?php if ($reviewItem['evidence_image']): ?><a class="review-evidence" href="<?= e(url('submission-image', ['progress_id' => (int)$reviewItem['id']])) ?>" target="_blank" rel="noopener"><img src="<?= e(url('submission-image', ['progress_id' => (int)$reviewItem['id']])) ?>" alt="Foto tugas lama dari <?= e($reviewItem['full_name']) ?>" loading="lazy"><span>Lihat foto tugas lama ↗</span></a><?php endif; ?><form method="post" class="review-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_review_lesson"><input type="hidden" name="progress_id" value="<?= (int)$reviewItem['id'] ?>"><textarea name="review_note" maxlength="500" rows="2" aria-label="Catatan untuk <?= e($reviewItem['full_name']) ?>" placeholder="Catatan untuk peserta (opsional, maksimal 500 karakter)"></textarea><div><button class="admin-action approve" type="submit" name="decision" value="approve">Setujui</button><button class="admin-action danger" type="submit" name="decision" value="reject">Tolak</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="admin-section"><div class="section-heading"><div><h2>Pengguna terbaru</h2><p>Maksimal 12 akun terakhir dibuat. Akun bermasalah dapat dinonaktifkan atau dihapus.</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Pengguna</th><th>Email</th><th>Bergabung</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if (!$adminUsers): ?><tr><td colspan="5" class="admin-empty">Belum ada akun pengguna.</td></tr><?php else: foreach ($adminUsers as $adminUser): ?><tr><td><strong><?= e($adminUser['full_name']) ?></strong><small>@<?= e($adminUser['username']) ?></small></td><td><?= e($adminUser['email']) ?></td><td><?= e(date('d M Y', strtotime($adminUser['created_at']))) ?></td><td><span class="admin-status <?= e($adminUser['status']) ?>"><?= $adminUser['status'] === 'active' ? 'Aktif' : 'Ditangguhkan' ?></span></td><td><div class="school-account-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_toggle_user_status"><input type="hidden" name="user_id" value="<?= (int)$adminUser['id'] ?>"><button class="admin-action <?= $adminUser['status'] === 'active' ? 'danger' : '' ?>" type="submit"><?= $adminUser['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete_user"><input type="hidden" name="user_id" value="<?= (int)$adminUser['id'] ?>"><button class="admin-action danger" type="submit" data-confirm="Hapus akun <?= e($adminUser['full_name']) ?> secara permanen? Semua progres dan riwayat belajar akun ini juga akan dihapus.">Hapus akun</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
<section class="admin-section"><div class="section-heading"><div><h2>Pengelolaan kursus <span class="review-count"><?= count($adminCourses) ?></span></h2><p>Pilih kursus yang tampil di katalog pengguna. Kursus tersembunyi tidak dapat diikuti atau dibuka dari tautan langsung.</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Kursus</th><th>Kategori</th><th>Level</th><th>Peserta</th><th>Visibilitas untuk pengguna</th><th>Aksi</th></tr></thead><tbody><?php if (!$adminCourses): ?><tr><td colspan="6" class="admin-empty">Belum ada kursus.</td></tr><?php else: foreach ($adminCourses as $adminCourse): ?><tr><td><strong><?= e($adminCourse['title']) ?></strong></td><td><?= e($adminCourse['category_name']) ?></td><td><?= e($levelLabels[$adminCourse['level']] ?? $adminCourse['level']) ?></td><td><?= (int)$adminCourse['enrollment_count'] ?></td><td><span class="admin-status <?= $adminCourse['is_active'] ? 'active' : 'suspended' ?>"><?= $adminCourse['is_active'] ? 'Ditampilkan' : 'Disembunyikan' ?></span></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_set_course_visibility"><input type="hidden" name="course_id" value="<?= (int)$adminCourse['id'] ?>"><input type="hidden" name="is_active" value="<?= $adminCourse['is_active'] ? '0' : '1' ?>"><button class="admin-action <?= $adminCourse['is_active'] ? 'danger' : '' ?>" type="submit"><?= $adminCourse['is_active'] ? 'Sembunyikan dari user' : 'Tampilkan ke user' ?></button></form></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
<?php elseif ($page === 'school-admins'): ?>
<?php if ($schoolAdminSchemaReady): ?><div class="admin-migration-notice school-admin-password-guidance"><strong>Password yang lupa dapat direset, bukan dilihat</strong><p>Password akun tersimpan dalam bentuk hash sehingga tidak dapat ditampilkan, termasuk oleh Super Admin. Untuk membantu admin yang lupa, buka <strong>Atur ulang password</strong>, tetapkan password sementara baru, lalu sampaikan secara pribadi. Admin wajib menggantinya saat login berikutnya.</p></div><?php endif; ?>
<?php if (!$schoolAdminSchemaReady): ?><div class="admin-migration-notice"><strong>Fitur admin sekolah belum aktif</strong><p>Impor <code>database/migrate_school_admins.sql</code> pada database <code>codenexa_db</code>, lalu muat ulang halaman ini.</p></div><?php else: ?>
<section class="admin-intro"><div><div class="eyebrow">KHUSUS SUPER ADMIN</div><h2>Kelola Admin Sekolah</h2><p>Tambah admin ke sekolah yang sudah ada, atau daftarkan sekolah dan akun admin baru.</p></div><span class="admin-role">Super Admin</span></section>
<section class="admin-section"><div class="section-heading"><div><h2>Tambah akun admin sekolah</h2><p>Password awal minimal 12 karakter dan wajib diganti saat admin login pertama.</p></div></div><form method="post" class="form-card school-admin-create-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_school_admin"><label>Sekolah terdaftar<select name="school_id"><option value="">Buat sekolah baru</option><?php foreach ($schoolAdminSchools as $school): ?><option value="<?= (int)$school['id'] ?>" <?= (string)($_POST['school_id'] ?? '') === (string)$school['id'] ? 'selected' : '' ?>><?= e($school['name']) ?></option><?php endforeach; ?></select><small>Pilih sekolah yang sudah ada, atau isi nama sekolah baru di bawah.</small></label><label>Nama sekolah baru<input name="school_name" minlength="2" maxlength="160" value="<?= e($_POST['school_name'] ?? '') ?>" placeholder="Contoh: SMP Negeri 1 Bandung"></label><label>Nama lengkap admin sekolah<input name="full_name" required minlength="2" maxlength="120" value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="Contoh: Admin SMP Negeri 1 Bandung"></label><label>Username untuk login<input name="username" required minlength="3" maxlength="40" pattern="[A-Za-z0-9_]+" value="<?= e($_POST['username'] ?? '') ?>" placeholder="Contoh: admin_smpn1bdg"></label><label>Email admin<input type="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@sekolah.sch.id"></label><label>Password awal<input type="password" name="initial_password" required minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter"></label><button class="btn btn-primary" type="submit">Tambah akun admin</button></form></section>
<section class="admin-section"><div class="section-heading"><div><h2>Daftar sekolah <span class="review-count"><?= count($schoolAdminSchools) ?></span></h2><p>Ubah nama sekolah di sini. Perubahan dan sekolah baru akan muncul otomatis di formulir pendaftaran.</p></div></div><?php if (!$schoolAdminSchools): ?><div class="admin-empty-panel">Belum ada sekolah terdaftar.</div><?php else: ?><div class="school-directory"><?php foreach ($schoolAdminSchools as $school): ?><form method="post" class="school-directory-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="edit_school_name"><input type="hidden" name="school_id" value="<?= (int)$school['id'] ?>"><div class="school-directory-icon" aria-hidden="true">⌂</div><div class="school-directory-info"><label for="schoolName<?= (int)$school['id'] ?>">Nama sekolah</label><input id="schoolName<?= (int)$school['id'] ?>" name="school_name" required minlength="2" maxlength="160" value="<?= e($school['name']) ?>"><small class="admin-status <?= e($school['status']) ?>"><?= $school['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></small></div><button class="admin-action" type="submit">Simpan nama</button></form><?php endforeach; ?></div><?php endif; ?></section>
<section class="admin-section"><div class="section-heading"><div><h2>Akun admin sekolah <span class="review-count"><?= count($schoolAdminAccounts) ?></span></h2><p>Maksimal 100 akun terbaru. Perbarui informasi login, reset password, ubah status, atau hapus sekolah beserta semua akun siswanya.</p></div></div><?php if (!$schoolAdminAccounts): ?><div class="admin-empty-panel">Belum ada akun admin sekolah.</div><?php else: ?><div class="admin-table-wrap"><table class="admin-table school-admin-table"><thead><tr><th>Sekolah</th><th>Data akun admin</th><th>Status akun</th><th>Akses</th></tr></thead><tbody><?php foreach ($schoolAdminAccounts as $schoolAdminAccount): ?><tr><td><strong><?= e($schoolAdminAccount['school_name']) ?></strong><small>Status sekolah: <?= $schoolAdminAccount['school_status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></small></td><td><form method="post" class="school-admin-edit-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="edit_school_admin"><input type="hidden" name="user_id" value="<?= (int)$schoolAdminAccount['user_id'] ?>"><label>Nama<input name="full_name" required minlength="2" maxlength="120" value="<?= e($schoolAdminAccount['full_name']) ?>"></label><label>Username<input name="username" required minlength="3" maxlength="40" pattern="[A-Za-z0-9_]+" value="<?= e($schoolAdminAccount['username']) ?>"></label><label>Email<input type="email" name="email" required maxlength="190" value="<?= e($schoolAdminAccount['email']) ?>"></label><details><summary>Atur ulang password (opsional)</summary><div class="school-admin-password-fields"><label>Password baru<input type="password" name="new_password" minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter"></label><label>Konfirmasi password<input type="password" name="password_confirm" minlength="12" autocomplete="new-password"></label></div></details><button class="admin-action" type="submit">Simpan perubahan</button></form></td><td><span class="admin-status <?= e($schoolAdminAccount['account_status']) ?>"><?= $schoolAdminAccount['account_status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td><td><div class="school-account-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_school_admin_status"><input type="hidden" name="user_id" value="<?= (int)$schoolAdminAccount['user_id'] ?>"><input type="hidden" name="status" value="<?= $schoolAdminAccount['account_status'] === 'active' ? 'suspended' : 'active' ?>"><button class="admin-action <?= $schoolAdminAccount['account_status'] === 'active' ? 'danger' : '' ?>" type="submit"><?= $schoolAdminAccount['account_status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_school_account"><input type="hidden" name="user_id" value="<?= (int)$schoolAdminAccount['user_id'] ?>"><button class="admin-action danger" type="submit" data-confirm="Hapus permanen sekolah <?= e($schoolAdminAccount['school_name']) ?>? Semua akun Admin Sekolah dan siswa, kelas, progres, serta riwayat belajar sekolah ini ikut terhapus. Tindakan ini tidak dapat dibatalkan.">Hapus sekolah</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php elseif ($page === 'school-admin'): ?>
<section class="admin-intro"><div><div class="eyebrow">ADMIN SEKOLAH</div><h2><?= e($schoolAdminSchool['name'] ?? 'Sekolah tidak terhubung') ?></h2><p>Kelola kelas, akun siswa, dan kursus yang ditampilkan untuk siswa sekolah Anda.</p></div><span class="admin-role">Admin Sekolah</span></section>
<?php if (!$schoolAdminSchool): ?><div class="admin-migration-notice"><strong>Sekolah belum terhubung</strong><p>Hubungi Super Admin untuk memastikan akun Anda terhubung dengan sekolah yang benar.</p></div>
<?php elseif (!$schoolClassSchemaReady): ?><div class="admin-migration-notice"><strong>Pengelolaan kelas belum aktif</strong><p>Jalankan <code>database/migrate_school_classes.sql</code> pada database <code>codenexa_db</code>, lalu muat ulang halaman ini.</p></div>
<?php else: ?>
<section class="admin-section"><div class="section-heading"><div><h2>Daftar kelas <span class="review-count"><?= count($schoolAdminClasses) ?></span></h2><p>Buka daftar akun dari kelas masing-masing. Siswa yang memilih sekolah dan kelas saat mendaftar otomatis tercatat di sini.</p></div></div><form method="post" class="form-card school-class-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_school_class"><label for="className">Nama kelas</label><input id="className" name="class_name" required minlength="2" maxlength="80" placeholder="Contoh: PPLG1"><button class="btn btn-primary" type="submit">Tambah kelas</button></form><?php if (!$schoolAdminClasses): ?><div class="admin-empty-panel">Belum ada kelas. Tambahkan kelas pertama, misalnya PPLG1.</div><?php else: ?><div class="admin-table-wrap"><table class="admin-table school-class-table"><thead><tr><th>Kelas</th><th>Akun terdaftar</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach ($schoolAdminClasses as $class): ?><?php $classIdForCounts = (int)$class['id']; $classStudents = $schoolAdminClassStudents[$classIdForCounts] ?? []; $gradeCounts = $schoolAdminClassGradeCounts[$classIdForCounts] ?? ['X' => 0, 'XI' => 0, 'XII' => 0, 'unassigned' => 0]; ?><tr><td><strong><?= e($class['name']) ?></strong></td><td><a class="school-class-account-link" href="<?= e(url('school-class', ['class_id' => $classIdForCounts])) ?>">Lihat akun <span><?= count($classStudents) ?></span></a><?php if ($studentGradeSchemaReady): ?><div class="school-grade-counts"><?php foreach (['X', 'XI', 'XII'] as $gradeLabel): ?><span><?= e($gradeLabel) ?>: <?= (int)$gradeCounts[$gradeLabel] ?></span><?php endforeach; ?></div><?php endif; ?></td><td><span class="admin-status <?= $class['status'] === 'active' ? 'active' : 'suspended' ?>"><?= $class['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_school_class_status"><input type="hidden" name="class_id" value="<?= $classIdForCounts ?>"><button class="admin-action <?= $class['status'] === 'active' ? 'danger' : '' ?>" type="submit"><?= $class['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php if ($schoolAdminSchool && !$schoolCourseVisibilityReady): ?><div class="admin-migration-notice"><strong>Pengaturan tampilan kursus belum aktif</strong><p>Jalankan <code>database/migrate_school_course_visibility.sql</code> di database <code>codenexa_db</code>, lalu muat ulang Panel Admin Sekolah.</p></div>
<?php elseif ($schoolAdminSchool): ?>
<section class="admin-section"><div class="section-heading"><div><h2>Pengaturan kursus untuk siswa <span class="review-count"><?= count($schoolAdminCourses) ?></span></h2><p>Pilih kursus yang muncul untuk siswa di sekolah Anda. Pengaturan ini tidak mengubah tampilan kursus di sekolah lain.</p></div></div><?php if (!$schoolAdminCourses): ?><div class="admin-empty-panel">Belum ada kursus yang tersedia untuk diatur.</div><?php else: ?><div class="admin-table-wrap"><table class="admin-table school-course-table"><thead><tr><th>Kursus</th><th>Kategori</th><th>Level</th><th>Visibilitas</th><th>Aksi</th></tr></thead><tbody><?php foreach ($schoolAdminCourses as $schoolCourse): ?><tr><td><strong><?= e($schoolCourse['title']) ?></strong><small><?= (int)$schoolCourse['enrollment_count'] ?> siswa terdaftar</small></td><td><?= e($schoolCourse['category_name']) ?></td><td><?= e($levelLabels[$schoolCourse['level']] ?? $schoolCourse['level']) ?></td><td><?php if (!(bool)$schoolCourse['is_active']): ?><span class="admin-status suspended">Dinonaktifkan Super Admin</span><?php else: ?><span class="admin-status <?= (bool)$schoolCourse['school_visible'] ? 'active' : 'suspended' ?>"><?= (bool)$schoolCourse['school_visible'] ? 'Ditampilkan' : 'Disembunyikan' ?></span><?php endif; ?></td><td><?php if (!(bool)$schoolCourse['is_active']): ?><span class="school-review-unavailable">Hubungi Super Admin untuk mengaktifkan</span><?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="school_set_course_visibility"><input type="hidden" name="course_id" value="<?= (int)$schoolCourse['id'] ?>"><input type="hidden" name="is_visible" value="<?= (bool)$schoolCourse['school_visible'] ? '0' : '1' ?>"><button class="admin-action <?= (bool)$schoolCourse['school_visible'] ? 'danger' : '' ?>" type="submit"><?= (bool)$schoolCourse['school_visible'] ? 'Sembunyikan dari siswa' : 'Tampilkan ke siswa' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php elseif ($page === 'school-class'): ?>
<?php if (!$schoolAdminSchool): ?><div class="admin-migration-notice"><strong>Sekolah belum terhubung</strong><p>Hubungi Super Admin untuk memastikan akun Anda terhubung dengan sekolah yang benar.</p></div>
<?php elseif (!$schoolClassSchemaReady): ?><div class="admin-migration-notice"><strong>Pengelolaan kelas belum aktif</strong><p>Jalankan <code>database/migrate_school_classes.sql</code> pada database <code>codenexa_db</code>, lalu muat ulang halaman ini.</p></div>
<?php else: ?>
<section class="admin-intro"><div><div class="eyebrow">DAFTAR AKUN KELAS</div><h2><?= e($schoolAdminSelectedClass['name']) ?></h2><p><?= e($schoolAdminSchool['name']) ?> · Akun siswa yang terdaftar di kelas ini.</p></div><span class="admin-role"><?= count($schoolAdminClassAccounts) ?> akun</span></section>
<div class="school-class-page-actions"><a class="btn btn-secondary" href="<?= e(url('school-admin')) ?>">← Kembali ke daftar kelas</a></div>
<section class="admin-section">
<div class="section-heading"><div><h2>Akun terdaftar <span class="review-count"><?= count($schoolAdminClassAccounts) ?></span></h2><p>Kelola akun siswa kelas <?= e($schoolAdminSelectedClass['name']) ?>, lihat hasil praktik, atau tangani akun bermasalah.</p></div></div>
<?php if ($studentGradeSchemaReady): ?>
<nav class="school-grade-filters" aria-label="Filter tingkat kelas">
<a class="<?= $schoolAdminGradeFilter === '' ? 'selected' : '' ?>" href="<?= e(url('school-class', ['class_id' => (int)$schoolAdminSelectedClass['id']])) ?>">Semua <span><?= array_sum($schoolAdminGradeCounts) ?></span></a>
<?php foreach (['X', 'XI', 'XII'] as $gradeLabel): ?><a class="<?= $schoolAdminGradeFilter === $gradeLabel ? 'selected' : '' ?>" href="<?= e(url('school-class', ['class_id' => (int)$schoolAdminSelectedClass['id'], 'grade' => $gradeLabel])) ?>"><?= e($gradeLabel) ?> <span><?= (int)$schoolAdminGradeCounts[$gradeLabel] ?></span></a><?php endforeach; ?>
<a class="<?= $schoolAdminGradeFilter === 'unassigned' ? 'selected' : '' ?>" href="<?= e(url('school-class', ['class_id' => (int)$schoolAdminSelectedClass['id'], 'grade' => 'unassigned'])) ?>">Belum dipilih <span><?= (int)$schoolAdminGradeCounts['unassigned'] ?></span></a>
</nav>
<?php endif; ?>
<?php if (!$schoolAdminClassAccounts): ?><div class="admin-empty-panel"><?= $schoolAdminGradeFilter !== '' ? 'Tidak ada akun siswa pada tingkat yang dipilih.' : 'Belum ada akun yang terdaftar di kelas ini.' ?></div><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table school-class-account-table"><thead><tr><th>Nama pengguna</th><th>Tingkat</th><th>Username</th><th>Email</th><th>Hasil praktik</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($schoolAdminClassAccounts as $student): ?><tr><td><strong><?= e($student['full_name']) ?></strong></td><td><?= e($student['grade_level'] ?: 'Belum dipilih') ?></td><td>@<?= e($student['username']) ?></td><td><?= e($student['email']) ?></td><td><?php if ($schoolReviewSchemaReady): ?><a class="school-student-results-link" href="<?= e(url('school-student', ['user_id' => (int)$student['id']])) ?>">Lihat hasil <span><?= (int)$student['submission_count'] ?></span><?php if ((int)$student['pending_review_count'] > 0): ?><b><?= (int)$student['pending_review_count'] ?> menunggu</b><?php endif; ?></a><?php else: ?><span class="school-review-unavailable">Belum aktif</span><?php endif; ?></td><td><span class="admin-status <?= e($student['status']) ?>"><?= $student['status'] === 'active' ? 'Aktif' : 'Ditangguhkan' ?></span></td><td><div class="school-account-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_toggle_user_status"><input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>"><input type="hidden" name="class_id" value="<?= (int)$schoolAdminSelectedClass['id'] ?>"><button class="admin-action <?= $student['status'] === 'active' ? 'danger' : '' ?>" type="submit"><?= $student['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_delete_user"><input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>"><input type="hidden" name="class_id" value="<?= (int)$schoolAdminSelectedClass['id'] ?>"><button class="admin-action danger" type="submit" data-confirm="Hapus akun <?= e($student['full_name']) ?> secara permanen? Semua progres dan riwayat belajar akun ini juga akan dihapus.">Hapus akun</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php elseif ($page === 'school-student'): ?>
<?php if (!$schoolAdminSchool || !$schoolAdminSelectedStudent): ?><div class="admin-migration-notice"><strong>Akun siswa tidak ditemukan</strong><p>Akun tersebut tidak terhubung dengan sekolah Anda.</p></div>
<?php elseif (!$schoolReviewSchemaReady): ?><div class="admin-migration-notice"><strong>Peninjauan hasil belum aktif</strong><p>Jalankan <code>database/migrate_lesson_approval.sql</code> dan <code>database/migrate_practice_code.sql</code> pada database <code>codenexa_db</code>.</p></div>
<?php else: ?>
<section class="admin-intro"><div><div class="eyebrow">HASIL PRAKTIK SISWA</div><h2><?= e($schoolAdminSelectedStudent['full_name']) ?></h2><p>@<?= e($schoolAdminSelectedStudent['username']) ?> · <?= e($schoolAdminSelectedStudent['class_name']) ?> · Tingkat <?= e($schoolAdminSelectedStudent['grade_level'] ?: 'belum dipilih') ?></p></div><span class="admin-role"><?= count($schoolAdminStudentSubmissions) ?> hasil</span></section>
<div class="school-class-page-actions"><a class="btn btn-secondary" href="<?= e(url('school-class', ['class_id' => (int)$schoolAdminSelectedStudent['class_id']])) ?>">← Kembali ke akun kelas</a></div>
<section class="admin-section"><div class="section-heading"><div><h2>Riwayat hasil praktik <span class="review-count"><?= count($schoolAdminStudentSubmissions) ?></span></h2><p>Hanya kiriman dari akun siswa ini yang ditampilkan. Tinjau setiap kiriman secara terpisah.</p></div></div><?php if (!$schoolAdminStudentSubmissions): ?><div class="admin-empty-panel">Siswa ini belum mengirim hasil praktik.</div><?php else: ?><div class="review-list"><?php foreach ($schoolAdminStudentSubmissions as $submission): ?><article class="review-card school-student-submission"><div class="review-details"><div><strong>Soal ke-<?= (int)$submission['lesson_number'] ?> · <?= e($submission['lesson_title']) ?></strong><small><?= e($submission['course_title']) ?> · <?= e($submission['module_title']) ?></small></div><span class="admin-status <?= e($submission['status'] === 'completed' ? 'active' : ($submission['status'] === 'pending' ? 'pending' : 'suspended')) ?>"><?= $submission['status'] === 'completed' ? 'Disetujui' : ($submission['status'] === 'pending' ? 'Menunggu review' : 'Perlu diperbaiki') ?></span><time class="review-date"><?= e(date('d M Y, H:i', strtotime($submission['submitted_at']))) ?></time></div><details class="review-code"><summary>Lihat kode praktik</summary><pre><code><?= e($submission['submitted_code']) ?></code></pre></details><?php if ($submission['status'] === 'rejected' && $submission['review_note']): ?><p class="school-student-review-note"><strong>Catatan sebelumnya:</strong> <?= e($submission['review_note']) ?></p><?php endif; ?><?php if ($submission['status'] === 'pending'): ?><form method="post" class="review-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="admin_review_lesson"><input type="hidden" name="progress_id" value="<?= (int)$submission['progress_id'] ?>"><input type="hidden" name="student_id" value="<?= (int)$schoolAdminSelectedStudent['id'] ?>"><textarea name="review_note" maxlength="500" rows="2" aria-label="Catatan untuk <?= e($schoolAdminSelectedStudent['full_name']) ?>" placeholder="Catatan untuk siswa (opsional, maksimal 500 karakter)"></textarea><div><button class="admin-action approve" type="submit" name="decision" value="approve">Setujui</button><button class="admin-action danger" type="submit" name="decision" value="reject">Minta perbaikan</button></div></form><?php elseif ($submission['status'] === 'completed' && $submission['reviewed_at']): ?><p class="school-student-review-note">Disetujui pada <?= e(date('d M Y, H:i', strtotime($submission['reviewed_at']))) ?>.</p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?></section>
<?php endif; ?>
<?php elseif ($page === 'change-password'): ?>
<section class="form-card"><div class="section-heading"><div><h2>Ganti password awal</h2><p>Buat password baru sebelum melanjutkan ke CodeMif.</p></div></div><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_initial_password"><label>Password baru<input type="password" name="new_password" required minlength="12" autocomplete="new-password"></label><label>Konfirmasi password baru<input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label><button class="btn btn-primary" type="submit">Simpan password baru</button></form></section>
<?php elseif ($page === 'certificate'): ?>
<?php $certificateMonths = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; $certificateDate = strtotime($certificate['completed_at']); ?>
<div class="certificate-toolbar"><a class="btn btn-secondary" href="<?= e(url('dashboard')) ?>">← Kembali ke dashboard</a><button class="btn btn-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
<article class="certificate-sheet" aria-label="Sertifikat penyelesaian kursus">
  <div class="certificate-frame">
    <span class="certificate-corner top-left" aria-hidden="true"></span><span class="certificate-corner top-right" aria-hidden="true"></span><span class="certificate-corner bottom-left" aria-hidden="true"></span><span class="certificate-corner bottom-right" aria-hidden="true"></span>
    <div class="certificate-brand"><span class="certificate-logo-mark" aria-hidden="true">&lt;/&gt;</span><span>Code<strong>Mif</strong><small>LEARN · BUILD · GROW</small></span></div>
    <div class="certificate-content">
      <div class="certificate-kicker">PENGAKUAN PENCAPAIAN</div>
      <h2>SERTIFIKAT</h2>
      <div class="certificate-subtitle">PENYELESAIAN KURSUS</div>
      <p class="certificate-intro">Dengan bangga diberikan kepada</p>
      <h3 class="certificate-recipient"><?= e($certificate['full_name']) ?></h3>
      <div class="certificate-rule"></div>
      <p class="certificate-description">Telah menyelesaikan seluruh materi pembelajaran pada kursus</p>
      <h4 class="certificate-course"><?= e($certificate['course_title']) ?></h4>
      <p class="certificate-date">Diselesaikan pada <?= e(date('j', $certificateDate) . ' ' . $certificateMonths[(int)date('n', $certificateDate)] . ' ' . date('Y', $certificateDate)) ?></p>
      <div class="certificate-signature"><span class="signature-script">CodeMif</span><span class="signature-line"></span><strong>CodeMif</strong><small>Perusahaan Penyelenggara</small></div>
    </div>
    <div class="certificate-id">SERTIFIKAT CM-<?= e(str_pad((string)$certificate['enrollment_id'], 6, '0', STR_PAD_LEFT)) ?></div>
  </div>
</article>
<?php elseif ($page === 'dashboard'): ?>
<section class="welcome"><div><div class="eyebrow light">SELAMAT DATANG KEMBALI 👋</div><h2>Terus berkembang, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h2><p>Belajar sedikit setiap hari adalah langkah besar menuju masa depanmu.</p><a class="btn btn-light" href="<?= e(url('courses')) ?>">Temukan kursus <span>→</span></a></div><div class="welcome-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="code-symbol">{ }</div><div class="spark">✦</div></div></section>
<div class="section-heading"><div><h2>Ringkasan belajar</h2><p>Perjalanan coding kamu sejauh ini.</p></div></div>
<div class="stats-grid"><article class="stat-card"><div class="stat-icon purple">▤</div><span>Kursus diikuti</span><strong><?= $stats['enrolled'] ?></strong><small>Total kursus yang kamu mulai</small></article><article class="stat-card"><div class="stat-icon green">✓</div><span>Kursus selesai</span><strong><?= $stats['completed'] ?></strong><small>Terus tingkatkan progresmu</small></article><article class="stat-card"><div class="stat-icon orange">⌘</div><span>Materi selesai</span><strong><?= $stats['lessons'] ?></strong><small>Pelajaran yang dituntaskan</small></article></div>
<section class="certificate-list-section" id="certificates"><div class="section-heading"><div><h2>Sertifikat saya</h2><p>Klaim dan cetak sertifikat dari kursus yang sudah kamu selesaikan.</p></div></div><?php if (!$dashboardCertificates): ?><div class="certificate-empty">Selesaikan semua materi kursus untuk mendapatkan sertifikat pertamamu.</div><?php else: ?><div class="certificate-list"><?php foreach ($dashboardCertificates as $completedCourse): ?><article class="certificate-list-card"><span class="certificate-list-icon" aria-hidden="true">✓</span><div><strong><?= e($completedCourse['title']) ?></strong><small>Selesai <?= e(date('d M Y', strtotime($completedCourse['completed_at']))) ?></small></div><a class="btn btn-certificate" href="<?= e(url('certificate', ['course_id' => (int)$completedCourse['id']])) ?>">Klaim sertifikat</a></article><?php endforeach; ?></div><?php endif; ?></section>
<div class="section-heading"><div><h2>Lanjutkan belajar</h2><p>Kursus yang sudah kamu ikuti.</p></div><a class="text-link" href="<?= e(url('courses')) ?>">Lihat semua →</a></div>
<?php if (!$courses): ?><div class="empty-state"><div class="empty-icon">✦</div><h3>Mulai perjalanan codingmu</h3><p>Kamu belum mengikuti kursus. Pilih kursus pertama dan mulai belajar hari ini.</p><a class="btn btn-primary" href="<?= e(url('courses')) ?>">Jelajahi kursus</a></div>
<?php else: ?><div class="course-grid"><?php foreach ($courses as $course): ?><?php $artKey = course_art_key($course); ?><article class="course-card<?= $course['enrollment_status'] === 'completed' ? ' is-completed' : '' ?>"><div class="course-visual course-art-<?= e($artKey) ?>"><img class="course-art" src="<?= e($baseUrl) ?>/assets/images/courses/<?= e($artKey) ?>.svg" alt="Ilustrasi <?= e($course['title']) ?>" loading="lazy" decoding="async"><span class="course-tag"><?= e($course['category_name']) ?></span><?php if ($course['enrollment_status'] === 'completed'): ?><span class="course-completed-badge" aria-label="Kursus selesai"><span aria-hidden="true">✓</span> Selesai</span><?php endif; ?></div><div class="course-body"><div class="course-meta"><span><?= e($levelLabels[$course['level']] ?? $course['level']) ?></span><span>◷ <?= (int)$course['estimated_minutes'] ?> menit</span></div><h3><?= e($course['title']) ?></h3><p><?= e(mb_strimwidth($course['description'],0,120,'…')) ?></p><span class="pill"><?= $course['enrollment_status']==='completed'?'✓ Selesai':'Sedang dipelajari' ?></span><?php if ($course['enrollment_status'] === 'completed'): ?><a class="btn btn-certificate" href="<?= e(url('certificate', ['course_id' => (int)$course['id']])) ?>">Lihat sertifikat</a><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?>

<?php elseif ($page === 'courses'): ?>
<div class="page-intro"><div><h2>Temukan kursus favoritmu</h2><p>Bangun kemampuan baru, satu materi dalam satu waktu.</p></div><span class="result-count"><?= count($courses) ?> kursus tersedia</span></div>
<?php if (!$courses): ?><div class="empty-state"><div class="empty-icon">⌘</div><h3>Belum ada kursus aktif</h3><p>Pastikan data kursus sudah diimpor ke database.</p></div><?php else: ?><div class="course-grid"><?php foreach ($courses as $course): ?><?php $artKey = course_art_key($course); ?><article class="course-card<?= $course['enrollment_status'] === 'completed' ? ' is-completed' : '' ?>"><div class="course-visual course-art-<?= e($artKey) ?>"><img class="course-art" src="<?= e($baseUrl) ?>/assets/images/courses/<?= e($artKey) ?>.svg" alt="Ilustrasi <?= e($course['title']) ?>" loading="lazy" decoding="async"><span class="course-tag"><?= e($course['category_name']) ?></span><?php if ($course['enrollment_status'] === 'completed'): ?><span class="course-completed-badge" aria-label="Kursus selesai"><span aria-hidden="true">✓</span> Selesai</span><?php endif; ?></div><div class="course-body"><div class="course-meta"><span><?= e($levelLabels[$course['level']] ?? $course['level']) ?></span><span>◷ <?= (int)$course['estimated_minutes'] ?> menit</span></div><h3><?= e($course['title']) ?></h3><p><?= e(mb_strimwidth($course['description'],0,140,'…')) ?></p><?php if ($course['enrollment_status']): ?><div class="course-actions"><a class="btn btn-secondary" href="<?= e(url('learn', ['course_id' => (int)$course['id']])) ?>"><?= $course['enrollment_status']==='completed'?'Lihat materi':'Lanjutkan belajar' ?> <span>→</span></a><?php if ($course['enrollment_status'] === 'completed'): ?><a class="btn btn-certificate" href="<?= e(url('certificate', ['course_id' => (int)$course['id']])) ?>">Sertifikat</a><?php endif; ?></div><?php else: ?><form method="post" class="enroll-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="enroll"><input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"><button class="btn btn-primary" type="submit">Mulai belajar <span>→</span></button></form><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?>

<?php elseif ($page === 'ai-tutor'): ?>
<section class="tutor-intro"><div class="tutor-intro-copy"><span class="tutor-kicker"><span class="tutor-kicker-dot"></span> TEMAN BELAJAR KODINGMU</span><h2>Belajar makin paham,<br><span>bareng Tutor AI.</span></h2><p>Bingung dengan materi? Tanyakan saja. Tutor akan menjelaskan dengan sederhana berdasarkan kursus yang kamu ikuti.</p><div class="tutor-benefits"><span><b>✓</b> Fokus pada materi kursus</span><span><b>✓</b> Penjelasan langkah demi langkah</span></div></div><div class="tutor-hero-art" aria-hidden="true"><div class="tutor-orbit tutor-orbit-one"></div><div class="tutor-orbit tutor-orbit-two"></div><div class="tutor-hero-code">&lt;/&gt;</div><span class="tutor-float tutor-float-one">CSS</span><span class="tutor-float tutor-float-two">{ JS }</span><span class="tutor-float tutor-float-three">PHP</span><span class="tutor-float tutor-float-four">HTML</span><span class="tutor-spark">✦</span></div></section>
<?php if (!$tutorCourses): ?><div class="empty-state"><div class="empty-icon">✦</div><h3>Ikuti kursus untuk mulai bertanya</h3><p>Tutor AI menggunakan materi dari kursus yang sudah kamu ikuti agar jawabannya tetap sesuai pelajaran.</p><a class="btn btn-primary" href="<?= e(url('courses')) ?>">Jelajahi kursus</a></div>
<?php else: ?>
<div class="tutor-course-picker"><div class="tutor-picker-label"><span class="tutor-picker-icon">⌘</span><div><strong>Ruang belajarmu</strong><small>Pilih kursus yang ingin kamu pelajari</small></div></div><form method="get" action="<?= e($baseUrl) ?>/"><input type="hidden" name="page" value="ai-tutor"><select id="tutorCourse" name="course_id" aria-label="Pilih kursus"><?php foreach ($tutorCourses as $course): ?><option value="<?= (int)$course['id'] ?>" <?= (int)$tutorCourse['id'] === (int)$course['id'] ? 'selected' : '' ?>><?= e($course['title']) ?><?= $course['enrollment_status'] === null ? ' · Ikuti kursus dulu' : '' ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Pilih kursus <span>→</span></button></form></div>
<?php if ($tutorCourse['enrollment_status'] === null): ?>
<section class="tutor-enroll-prompt"><span class="tutor-enroll-icon">⌘</span><div><h3>Mulai belajar <?= e($tutorCourse['title']) ?></h3><p>Ikuti kursus ini terlebih dahulu untuk membuka seluruh materi dan bertanya kepada Tutor AI.</p></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="enroll"><input type="hidden" name="course_id" value="<?= (int)$tutorCourse['id'] ?>"><button class="btn btn-primary" type="submit">Ikuti kursus <span>→</span></button></form></section>
<?php else: ?>
<section class="tutor-chat" id="tutor-chat"><div class="tutor-chat-heading"><div class="tutor-chat-identity"><span class="tutor-avatar">✦</span><span class="tutor-status-dot"></span><span class="tutor-chat-title"><strong><?= e($tutorCourse['title']) ?></strong><small><span></span> Tutor siap membantu · Jawaban dari materi kursus</small></span></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="clear_course_ai"><input type="hidden" name="course_id" value="<?= (int)$tutorCourse['id'] ?>"><button class="tutor-clear" type="submit"><span>↺</span> Percakapan baru</button></form></div>
<div class="tutor-messages<?= !$tutorMessages ? ' is-empty' : '' ?>" aria-live="polite"><?php if (!$tutorMessages): ?><div class="tutor-welcome-message"><span class="tutor-message-avatar">✦</span><article class="tutor-message assistant"><strong>Tutor AI <span>· Baru saja</span></strong><p>Hai! 👋 Senang bisa menemanimu belajar. Ada materi yang terasa sulit? Ceritakan bagian yang membingungkan, kita pelajari bersama.</p></article></div><div class="tutor-suggestions"><span class="tutor-suggestion-label">MULAI DENGAN PERTANYAAN</span><div><button type="button" data-tutor-suggestion="Apa konsep dasar yang perlu saya pahami di kursus ini?"><span>✦</span> Jelaskan konsep dasarnya <b>↗</b></button><button type="button" data-tutor-suggestion="Bisa jelaskan contoh kode di materi ini langkah demi langkah?"><span>&lt;/&gt;</span> Bedah contoh kode <b>↗</b></button><button type="button" data-tutor-suggestion="Apa kesalahan umum yang perlu saya hindari pada materi ini?"><span>◎</span> Kesalahan yang umum <b>↗</b></button></div></div><?php else: foreach ($tutorMessages as $message): ?><article class="tutor-message <?= $message['role'] === 'user' ? 'user' : 'assistant' ?>"><strong><?= $message['role'] === 'user' ? 'Kamu' : 'Tutor AI' ?></strong><p><?= nl2br(e($message['content'])) ?></p></article><?php endforeach; endif; ?></div>
<form method="post" action="<?= e(url('ai-tutor', ['course_id' => (int)$tutorCourse['id']])) ?>" class="tutor-prompt"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="stream_course_ai"><input type="hidden" name="course_id" value="<?= (int)$tutorCourse['id'] ?>"><label class="visually-hidden" for="tutorQuestion">Pertanyaan tentang materi kursus</label><div class="tutor-input-wrap"><span aria-hidden="true">✎</span><textarea id="tutorQuestion" name="question" required maxlength="1200" rows="2" placeholder="Tulis pertanyaanmu di sini..."></textarea></div><div><small><span>✦</span> Tutor hanya menjawab seputar materi kursus · Maksimal 1.200 karakter</small><button class="btn btn-primary" type="submit"><span>Kirim pertanyaan</span> <b>↑</b></button></div></form></section>
<?php endif; ?>
<?php endif; ?>

<?php elseif ($page === 'learn'): ?>
<section class="learning-intro"><a class="learning-back" href="<?= e(url('courses')) ?>">← Jelajahi kursus</a><span class="eyebrow"><?= e($learningCourse['category_name']) ?> · <?= e($levelLabels[$learningCourse['level']] ?? $learningCourse['level']) ?></span><h2><?= e($learningCourse['title']) ?></h2><p><?= e($learningCourse['description']) ?></p><div class="learning-progress"><div><strong><?= $learningCompletedCount ?> dari <?= count($learningLessons) ?> materi disetujui</strong><span><?= $learningPendingCount ?> menunggu review</span></div><div class="learning-progress-track"><span style="width:<?= count($learningLessons) ? (int)round($learningCompletedCount / count($learningLessons) * 100) : 0 ?>%"></span></div></div><?php if ($learningCourse['enrollment_status'] === 'completed'): ?><a class="btn btn-certificate learning-certificate-link" href="<?= e(url('certificate', ['course_id' => (int)$learningCourse['id']])) ?>">🎓 Ambil sertifikat kursus</a><?php endif; ?></section>
<?php $learningStep = 0; ?><div class="learning-layout"><aside class="lesson-outline"><h2>Daftar materi · <?= count($learningLessons) ?> tahap</h2><?php foreach ($learningModules as $module): ?><section class="outline-module"><h3><?= e($module['title']) ?></h3><?php foreach ($module['lessons'] as $outlineLesson): ?><?php $learningStep++; $outlineStatus = $outlineLesson['progress_status'] ?? 'in_progress'; ?><a class="outline-lesson <?= (int)$selectedLesson['lesson_id'] === (int)$outlineLesson['lesson_id'] ? 'current' : '' ?>" href="<?= e(url('learn', ['course_id' => (int)$learningCourse['id'], 'lesson_id' => (int)$outlineLesson['lesson_id']])) ?>"><span class="outline-state <?= e($outlineStatus) ?>"><?= $outlineStatus === 'completed' ? '✓' : ($outlineStatus === 'pending' ? '…' : ($outlineStatus === 'rejected' ? '!' : '○')) ?></span><span>Tahap <?= $learningStep ?> · <?= e($outlineLesson['lesson_title']) ?></span></a><?php endforeach; ?></section><?php endforeach; ?></aside>
<article class="lesson-panel"><?php if (!$selectedLesson): ?><div class="empty-state"><h3>Materi belum tersedia</h3><p>Kursus ini belum memiliki materi aktif.</p></div><?php else: ?>
<?php $lessonStatus = $selectedLesson['progress_status'] ?? 'in_progress'; if ($lessonStatus === 'completed' && trim((string)($selectedLesson['submitted_code'] ?? '')) === '') $lessonStatus = 'in_progress'; $practiceLanguage = strtolower(trim((string)($selectedLesson['code_language'] ?: $learningCourse['language']))); $practiceCanRun = in_array($practiceLanguage, ['python', 'py', 'html', 'css', 'javascript', 'js'], true); ?>
<div class="lesson-meta"><span>Tahap <?= $selectedLessonIndex + 1 ?> dari <?= count($learningLessons) ?></span><span><?= (int)$selectedLesson['duration_minutes'] ?> menit · +<?= (int)$selectedLesson['xp_reward'] ?> XP</span></div><h2><?= e($selectedLesson['lesson_title']) ?></h2><div class="lesson-content"><?= lesson_text($selectedLesson['content_theory']) ?></div>
<?php if ($selectedLesson['code_example']): ?><section class="lesson-code" data-no-copy><div><span><?= e($selectedLesson['code_language'] ?: 'Contoh kode') ?> · ketik ulang di editor</span></div><pre><code><?= e($selectedLesson['code_example']) ?></code></pre><?php if ($selectedLesson['code_explanation']): ?><p><?= lesson_text($selectedLesson['code_explanation']) ?></p><?php endif; ?></section><?php endif; ?>
<?php if ($selectedLesson['exercise']): ?><section class="lesson-task"><h3>Latihan</h3><p><?= lesson_text($selectedLesson['exercise']) ?></p></section><?php endif; ?><?php if ($selectedLesson['project_task']): ?><section class="lesson-task project"><h3>Tugas praktik</h3><p><?= lesson_text($selectedLesson['project_task']) ?></p></section><?php endif; ?>
<div class="lesson-review-state <?= e($lessonStatus) ?>"><?php if ($lessonStatus === 'completed'): ?><strong>Progres disetujui</strong><span>XP sudah ditambahkan ke akunmu.</span><?php elseif ($lessonStatus === 'pending'): ?><strong>Menunggu persetujuan admin</strong><span>Admin sedang meninjau kode praktikmu.</span><?php elseif ($lessonStatus === 'rejected'): ?><strong>Pengajuan perlu diperbaiki</strong><span><?= e($selectedLesson['review_note'] ?: 'Pelajari kembali materi lalu ajukan ulang.') ?></span><?php endif; ?></div>
<form method="post" class="lesson-submit"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="submit_lesson"><input type="hidden" name="course_id" value="<?= (int)$learningCourse['id'] ?>"><input type="hidden" name="lesson_id" value="<?= (int)$selectedLesson['lesson_id'] ?>">
<section class="practice-workspace" data-practice-editor data-language="<?= e($practiceLanguage) ?>"><div class="practice-heading"><div><h3>Ruang praktik kode</h3><p>Ketik kode sendiri, jalankan, lalu kirimkan kepada admin untuk ditinjau.</p></div><span><?= e(strtoupper($practiceLanguage)) ?></span></div><label class="visually-hidden" for="practiceCode">Kode praktik <?= e($practiceLanguage) ?></label><textarea id="practiceCode" name="submitted_code" maxlength="60000" required spellcheck="false" autocapitalize="off" autocomplete="off" placeholder="Ketik kode latihanmu di sini..." <?= $lessonStatus === 'pending' || $lessonStatus === 'completed' ? 'disabled' : '' ?>></textarea><?php if ($practiceLanguage === 'python' || $practiceLanguage === 'py'): ?><label class="practice-input-label" for="practiceInput">Nilai untuk input() <small>Opsional · satu nilai per baris</small></label><textarea id="practiceInput" data-practice-input rows="2" spellcheck="false" placeholder="Contoh: Dina&#10;18" <?= $lessonStatus === 'pending' || $lessonStatus === 'completed' ? 'disabled' : '' ?>></textarea><?php endif; ?><div class="practice-controls"><button type="button" class="btn btn-primary" data-run-practice <?= !$practiceCanRun || $lessonStatus === 'pending' || $lessonStatus === 'completed' ? 'disabled' : '' ?>>Jalankan kode</button><span data-practice-status aria-live="polite"><?= $practiceCanRun ? 'Kode berjalan aman di browser.' : 'Runner langsung belum tersedia untuk bahasa ini; kode tetap bisa dikirim untuk ditinjau.' ?></span></div><pre class="practice-output" data-practice-output aria-live="polite">Hasil kode akan tampil di sini.</pre></section>
<button class="btn <?= $lessonStatus === 'pending' || $lessonStatus === 'completed' ? 'btn-secondary' : 'btn-primary' ?>" type="submit" <?= $lessonStatus === 'pending' || $lessonStatus === 'completed' ? 'disabled' : '' ?>><?= $lessonStatus === 'completed' ? 'Sudah disetujui' : ($lessonStatus === 'pending' ? 'Menunggu persetujuan admin' : ($lessonStatus === 'rejected' ? 'Kirim ulang kode praktik' : 'Kirim kode praktik untuk ditinjau')) ?></button></form><?php endif; ?></article></div>

<?php elseif ($page === 'profile'): ?>
<div class="profile-layout"><section class="profile-card"><div class="profile-banner"><div class="profile-avatar"><?php if (!empty($profile['avatar'])): ?><img src="<?= e(url('profile-image')) ?>" alt="Foto profil <?= e($profile['full_name']) ?>"><?php else: ?><?= e(mb_strtoupper(mb_substr($profile['full_name'],0,1))) ?><?php endif; ?><form method="post" enctype="multipart/form-data" class="profile-avatar-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="profile_avatar"><input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden><button class="profile-avatar-change" type="button" aria-label="Ubah foto profil" title="Ubah foto profil">+</button></form></div></div><h2><?= e($profile['full_name']) ?></h2><p class="muted">@<?= e($profile['username']) ?></p><div class="profile-stats"><div><strong><?= (int)$profile['xp'] ?></strong><small>XP</small></div><div><strong><?= (int)$profile['level'] ?></strong><small>Level</small></div></div><p class="bio"><?= e($profile['bio'] ?: 'Belum ada bio. Ceritakan sedikit tentang dirimu!') ?></p><small class="muted">Bergabung <?= e(date('d M Y', strtotime($profile['created_at']))) ?></small></section>
<section class="form-card"><div class="section-heading"><div><h2>Pengaturan profil</h2><p>Perbarui informasi akun kamu.</p></div></div><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="profile"><label>Nama lengkap<input name="full_name" required maxlength="120" value="<?= e($profile['full_name']) ?>"></label><label>Username<input value="<?= e($profile['username']) ?>" disabled><small>Username tidak dapat diubah dari halaman ini.</small></label><label>Email<input value="<?= e($profile['email']) ?>" disabled></label><label>Bio<textarea name="bio" maxlength="500" rows="3" placeholder="Ceritakan tentang dirimu..."><?= e($profile['bio']) ?></textarea></label><div class="form-divider"></div><h3>Ganti password <span class="muted">(opsional)</span></h3><label>Password baru<input type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"></label><label>Konfirmasi password baru<input type="password" name="password_confirm" minlength="8" autocomplete="new-password" placeholder="Ulangi password baru"></label><button class="btn btn-primary" type="submit">Simpan perubahan</button></form></section></div>
<?php endif; ?>
<footer class="footer">© <?= date('Y') ?> CodeMif <span>Belajar hari ini, bangun masa depan.</span></footer>
</div></main></div>
<?php else: ?>
<div class="auth-layout"><div class="auth-decoration"><a class="brand brand-large" href="<?= e(url('login')) ?>"><span class="brand-mark">&lt;/&gt;</span><span>Code<span>Mif</span><small>LEARN · BUILD · GROW</small></span></a><div class="auth-message"><span class="eyebrow light">YOUR NEXT CHAPTER STARTS HERE</span><h1>Ubah rasa ingin tahu menjadi <span>kemampuan nyata.</span></h1><p>Belajar coding dengan terarah, pantau progresmu, dan bangun masa depan digitalmu.</p><div class="floating-code">&lt;learn /&gt;<br><span>build();</span><br>grow();</div></div><div class="auth-foot">Belajar hari ini, bangun masa depan.</div></div><main class="auth-main"><div class="auth-form-wrap"><div class="mobile-brand"><a class="brand" href="<?= e(url('login')) ?>"><span class="brand-mark">&lt;/&gt;</span><span>Code<span>Mif</span></span></a></div>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if ($page === 'register'): ?>
<div class="form-eyebrow">MULAI PERJALANANMU</div><h2>Buat akun baru</h2><p class="auth-subtitle">Daftar gratis dan mulai belajar coding.</p>
<?php if (!$studentGradeSchemaReady): ?><div class="admin-migration-notice"><strong>Pilihan tingkat kelas belum aktif</strong><p>Jalankan <code>database/migrate_student_grade_level.sql</code> pada database <code>codenexa_db</code>, lalu muat ulang halaman pendaftaran.</p></div><?php endif; ?>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="register"><label>Nama lengkap<input name="full_name" required minlength="2" maxlength="120" autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="Nama kamu"></label><label>Username<input name="username" required minlength="3" maxlength="40" pattern="[A-Za-z0-9_]+" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="contoh: miftah_dev"></label><label>Email<input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="nama@email.com"></label>
<?php if ($studentGradeSchemaReady): ?><fieldset class="grade-level-choice"><legend>Tingkat kelas</legend><div class="grade-level-options"><?php foreach (['X', 'XI', 'XII'] as $gradeLevelOption): ?><label><input type="radio" name="grade_level" value="<?= e($gradeLevelOption) ?>" <?= (string)($_POST['grade_level'] ?? '') === $gradeLevelOption ? 'checked' : '' ?>><span><?= e($gradeLevelOption) ?></span></label><?php endforeach; ?></div><small>Pilih tingkat kelasmu (opsional).</small></fieldset><?php endif; ?>
<?php if ($schoolClassSchemaReady): ?><div class="class-choice-field" data-school-class-picker data-options-url="<?= e(url('registration-options')) ?>"><span class="class-choice-heading"><span>Sekolah & kelas</span><span class="class-choice-optional">Opsional</span></span><label class="class-choice-label">Sekolah<span class="class-select-wrap"><select name="school_id" data-school-select><option value="">Pilih sekolah</option><?php foreach ($registrationSchools as $school): ?><option value="<?= (int)$school['id'] ?>" data-admin-name="<?= e($school['admin_name']) ?>" <?= (string)($_POST['school_id'] ?? '') === (string)$school['id'] ? 'selected' : '' ?>><?= e($school['admin_name'] ?: $school['name']) ?></option><?php endforeach; ?></select></span></label><label class="class-choice-label">Rombongan kelas<span class="class-select-wrap"><select name="class_id" data-class-select <?= empty($_POST['school_id']) ? 'disabled' : '' ?>><option value="">Pilih rombongan kelas (opsional)</option><?php foreach ($registrationClasses as $class): ?><option value="<?= (int)$class['id'] ?>" data-school-id="<?= (int)$class['school_id'] ?>" <?= (string)($_POST['class_id'] ?? '') === (string)$class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option><?php endforeach; ?></select></span></label><small>Pilih sekolah, lalu rombongan kelasnya (misalnya PPLG1). Pilihan sekolah mengikuti nama yang diatur Super Admin.</small></div><?php endif; ?>
<label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"></label><label>Konfirmasi password<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="Ulangi password"></label><button class="btn btn-primary btn-full" type="submit">Buat akun <span>→</span></button></form><p class="auth-switch">Sudah punya akun? <a href="<?= e(url('login')) ?>">Masuk di sini</a></p>
<?php else: ?>
<div class="form-eyebrow">SELAMAT DATANG KEMBALI</div><h2>Masuk ke CodeMif</h2><p class="auth-subtitle">Lanjutkan perjalanan belajarmu.</p>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login"><label>Email atau username<input name="identity" required autocomplete="username" value="<?= e($_POST['identity'] ?? '') ?>" placeholder="Masukkan email atau username"></label><label>Password<input type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password"></label><button class="btn btn-primary btn-full" type="submit">Masuk <span>→</span></button></form><p class="auth-switch">Belum punya akun? <a href="<?= e(url('register')) ?>">Daftar gratis</a></p>
<?php endif; ?><p class="auth-legal">Dengan melanjutkan, kamu setuju menggunakan CodeMif untuk kegiatan belajar yang positif.</p></div></main></div>
<?php endif; ?>
</body></html>
