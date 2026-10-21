<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jelajahi Kursus - CodeMif</title>
</head>
<body style="background:#0B1020; color:#F8FAFC; font-family:Inter, sans-serif; padding:24px; margin:0;">
    <div style="max-width:900px; margin:0 auto;">
        <h1>Jelajahi Kursus CodeMif</h1>
        <p style="color:#94A3B8; margin-bottom:24px;">Pilih kursus di bawah ini untuk mulai belajar coding dari nol.</p>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <div style="background:#121A2B; border:1px solid #263248; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                        <span style="display:inline-block; padding:4px 8px; background:rgba(34,211,238,0.1); color:#22D3EE; font-size:0.75rem; border-radius:6px; margin-bottom:8px;">
                            <?= htmlspecialchars(strtoupper($course['language'])) ?> &bull; <?= htmlspecialchars(ucfirst($course['level'])) ?>
                        </span>
                        <h3 style="margin:0 0 8px; font-size:1.2rem;"><?= htmlspecialchars($course['title']) ?></h3>
                        <p style="color:#94A3B8; font-size:0.9rem; margin:0 0 16px;"><?= htmlspecialchars($course['description'] ?? 'Tidak ada deskripsi.') ?></p>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:#94A3B8;">
                            <span>⏱️ <?= (int)$course['estimated_minutes'] ?> menit</span>
                            <span style="color:#6366F1; font-weight:600;">Mulai &rarr;</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#F87171;">Belum ada kursus yang tersedia di database.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top:32px;">
            <a href="http://localhost/codenexa/public/" style="color:#22D3EE; text-decoration:none;">&larr; Kembali ke Beranda / Dashboard</a>
        </div>
    </div>
</body>
</html>