-- ============================================================
-- CodeNexa - seed.sql  (data contoh; impor SETELAH schema.sql)
-- Tidak ada akun user/admin di sini. Admin dibuat lewat setup aman (Tahap 2).
-- Konten pelajaran ditulis dalam Markdown dan dirender pada Tahap 4.
-- ============================================================
USE `codenexa_db`;
SET NAMES utf8mb4;

INSERT INTO `categories` (`id`,`name`,`slug`,`description`,`icon`) VALUES
(1,'Web Frontend','web-frontend','HTML, CSS, dan JavaScript untuk antarmuka web','layout'),
(2,'Web Backend','web-backend','PHP dan logika server','server'),
(3,'Database','database','SQL, MySQL, dan desain basis data','database'),
(4,'Dasar Pemrograman','dasar-pemrograman','Logika dan konsep dasar pemrograman','brain'),
(5,'Tools & Workflow','tools-workflow','Git, GitHub, dan alur kerja developer','git-branch'),
(6,'Keamanan','keamanan','Keamanan aplikasi web','shield');

INSERT INTO `achievements` (`id`,`code`,`name`,`description`,`icon`,`xp_reward`,`criteria_type`,`criteria_value`) VALUES
(1,'first_lesson','Langkah Pertama','Selesaikan pelajaran pertama Anda','footprints',20,'lessons_completed',1),
(2,'five_lessons','Rajin Belajar','Selesaikan 5 pelajaran','book-open',50,'lessons_completed',5),
(3,'twenty_lessons','Pelajar Tangguh','Selesaikan 20 pelajaran','graduation-cap',150,'lessons_completed',20),
(4,'first_quiz','Penguji Nyali','Lulus kuis pertama','check-circle',30,'quizzes_passed',1),
(5,'perfect_quiz','Nilai Sempurna','Raih skor 100% pada sebuah kuis','star',100,'perfect_quiz',1),
(6,'streak_3','Konsisten 3 Hari','Belajar 3 hari berturut-turut','flame',40,'streak_days',3),
(7,'streak_7','Seminggu Penuh','Belajar 7 hari berturut-turut','zap',100,'streak_days',7),
(8,'first_project','Si Pembuat','Simpan proyek pertama di playground','folder-plus',30,'projects_saved',1),
(9,'course_complete','Lulus Kursus','Selesaikan satu kursus penuh','trophy',200,'courses_completed',1);

INSERT INTO `courses` (`id`,`category_id`,`title`,`slug`,`description`,`thumbnail`,`language`,`level`,`estimated_minutes`) VALUES
(1,1,'HTML Dasar','html-dasar','Pelajari struktur halaman web dari nol: tag, teks, tautan, gambar, daftar, dan tabel.',NULL,'HTML','beginner',120),
(2,4,'Logika Pemrograman','logika-pemrograman','Bangun cara berpikir algoritmik: urutan langkah, percabangan, dan perulangan.',NULL,'Umum','beginner',90),
(3,2,'PHP OOP','php-oop','Pahami pemrograman berorientasi objek di PHP: class, object, enkapsulasi, dan pewarisan.',NULL,'PHP','intermediate',150),
(4,2,'Design Patterns di PHP','design-patterns-php','Terapkan pola desain umum untuk arsitektur aplikasi yang bersih dan mudah dirawat.',NULL,'PHP','advanced',180);

INSERT INTO `modules` (`id`,`course_id`,`title`,`description`,`sort_order`) VALUES
(1,1,'Mengenal HTML','Fondasi HTML dan struktur dokumen',1),
(2,1,'Konten Lanjutan','Tautan, gambar, daftar, dan tabel',2),
(3,2,'Dasar Algoritma','Berpikir seperti programmer',1),
(4,3,'Class dan Object','Konsep inti OOP di PHP',1),
(5,4,'Pola Desain Dasar','Singleton dan Repository',1);

INSERT INTO `lessons` (`id`,`module_id`,`title`,`slug`,`content_theory`,`code_example`,`code_language`,`code_explanation`,`exercise`,`project_task`,`duration_minutes`,`xp_reward`,`sort_order`) VALUES
(1,1,'Apa itu HTML dan Struktur Dasar','apa-itu-html',
'HTML (HyperText Markup Language) adalah bahasa untuk menyusun **struktur** halaman web. HTML bukan bahasa pemrograman, melainkan bahasa markup: ia memberi tahu browser bagian mana yang berupa judul, paragraf, gambar, dan seterusnya.

Setiap dokumen HTML memiliki kerangka: `<!DOCTYPE html>` menyatakan versi HTML5, `<html>` membungkus seluruh isi, `<head>` berisi informasi halaman, dan `<body>` berisi konten yang terlihat.',
'<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Halaman Pertamaku</title>
</head>
<body>
  <h1>Halo, Dunia!</h1>
  <p>Ini halaman HTML pertama saya.</p>
</body>
</html>','html',
'- `<!DOCTYPE html>`: memberi tahu browser bahwa ini dokumen HTML5.
- `lang="id"`: menandai bahasa halaman (berguna untuk mesin pencari dan pembaca layar).
- `<meta charset="UTF-8">`: agar karakter Indonesia tampil benar.
- `<title>`: teks yang tampil di tab browser.
- `<h1>` dan `<p>`: judul utama dan paragraf.',
'Ubah teks di dalam `<h1>` menjadi nama Anda, lalu tambahkan satu paragraf kedua tentang alasan Anda belajar coding.',
'Buat halaman perkenalan diri sederhana berisi judul, dua paragraf, dan judul tab yang sesuai.',10,10,1),
(2,1,'Heading, Paragraf, dan Teks','heading-paragraf-teks',
'HTML menyediakan enam tingkat judul, `<h1>` sampai `<h6>`. Gunakan satu `<h1>` per halaman sebagai judul utama, lalu `<h2>` dan seterusnya untuk sub-bagian. Paragraf ditulis dengan `<p>`.

Untuk menekankan teks gunakan `<strong>` (penting) dan `<em>` (penekanan). Baris baru dibuat dengan `<br>` dan garis pemisah dengan `<hr>`. Pilih tag berdasarkan **makna**, bukan tampilan; tampilan diatur dengan CSS.',
'<h1>Belajar Web</h1>
<h2>Kenapa HTML?</h2>
<p>HTML adalah <strong>fondasi</strong> semua halaman web.</p>
<p>Belajar itu <em>perlu konsisten</em>.</p>
<hr>
<h2>Langkah Berikutnya</h2>
<p>Pelajari CSS.</p>','html',
'- Urutan heading menciptakan kerangka dokumen yang mudah dipahami pembaca dan mesin pencari.
- `<strong>` dan `<em>` memberi makna, bukan sekadar huruf tebal atau miring.
- `<hr>` membuat garis pemisah antarbagian.',
'Tulis artikel pendek dengan satu `<h1>`, dua `<h2>`, dan minimal satu kata yang diberi `<strong>`.',
'Buat halaman "Tentang Saya" dengan struktur heading yang rapi.',10,10,1),
(3,2,'Link dan Gambar','link-dan-gambar',
'Tautan dibuat dengan tag `<a>` dan atribut `href` yang berisi tujuan. Gambar ditampilkan dengan `<img>` yang membutuhkan atribut `src` (lokasi gambar) dan `alt` (teks pengganti untuk aksesibilitas).

Tautan bisa mengarah ke situs lain (URL lengkap), halaman lain di situs yang sama (path relatif), atau bagian di halaman yang sama (anchor, contoh `#kontak`).',
'<a href="https://developer.mozilla.org" target="_blank" rel="noopener">Dokumentasi MDN</a>
<br>
<img src="logo.png" alt="Logo CodeMif" width="120">','html',
'- `href`: alamat tujuan tautan.
- `target="_blank"` membuka tab baru; `rel="noopener"` menjaga keamanan tab baru tersebut.
- `alt` wajib diisi agar gambar dapat dipahami pembaca layar dan tetap bermakna jika gambar gagal dimuat.',
'Buat tiga tautan ke situs favorit Anda dan tambahkan satu gambar dengan teks `alt` yang deskriptif.',
'Buat halaman "Daftar Bookmark" berisi minimal lima tautan.',12,10,1),
(4,2,'List dan Tabel','list-dan-tabel',
'Daftar tidak berurutan memakai `<ul>`, daftar berurutan memakai `<ol>`, dan setiap itemnya memakai `<li>`. Tabel dibangun dari `<table>`, baris `<tr>`, sel header `<th>`, dan sel data `<td>`.

Gunakan tabel hanya untuk data tabular, bukan untuk mengatur tata letak halaman.',
'<ol>
  <li>Belajar HTML</li>
  <li>Belajar CSS</li>
  <li>Belajar JavaScript</li>
</ol>

<table border="1">
  <tr><th>Bahasa</th><th>Fungsi</th></tr>
  <tr><td>HTML</td><td>Struktur</td></tr>
  <tr><td>CSS</td><td>Tampilan</td></tr>
</table>','html',
'- `<ol>` otomatis memberi nomor urut pada setiap `<li>`.
- `<th>` adalah sel judul kolom, `<td>` sel data.
- Atribut `border="1"` hanya untuk latihan; di proyek nyata gunakan CSS.',
'Buat tabel jadwal belajar mingguan dengan kolom Hari dan Topik.',
'Buat halaman roadmap belajar coding memakai satu daftar berurutan dan satu tabel.',15,15,2),
(5,3,'Apa Itu Algoritma','apa-itu-algoritma',
'Algoritma adalah rangkaian langkah yang jelas, berurutan, dan berhingga untuk menyelesaikan suatu masalah. Sebelum menulis kode, pecah masalah menjadi langkah-langkah kecil.

Contoh sehari-hari: membuat mi instan. Didihkan air, masukkan mi, tunggu tiga menit, tambahkan bumbu, sajikan. Komputer membutuhkan instruksi sejelas ini, tanpa langkah yang ambigu.',
'Algoritma: mencari angka terbesar dari tiga angka
1. Baca angka A, B, C
2. Anggap A sebagai terbesar
3. Jika B lebih besar dari terbesar, maka terbesar = B
4. Jika C lebih besar dari terbesar, maka terbesar = C
5. Tampilkan terbesar','text',
'- Langkah 2 menetapkan nilai awal pembanding.
- Langkah 3 dan 4 adalah pembandingan berurutan.
- Hasil akhir selalu benar untuk tiga angka berapa pun.',
'Tulis algoritma (dalam kalimat biasa) untuk menghitung rata-rata tiga nilai ujian.',
'Tulis algoritma untuk menentukan apakah sebuah tahun adalah tahun kabisat.',10,10,1),
(6,3,'Percabangan dan Perulangan','percabangan-perulangan',
'**Percabangan** memilih jalur berdasarkan kondisi (jika... maka... jika tidak...). **Perulangan** mengulang langkah selama kondisi terpenuhi atau untuk sejumlah kali tertentu.

Keduanya adalah dua blok bangunan utama hampir semua program. Contoh di bawah memakai JavaScript agar dapat dicoba langsung di playground.',
'const nilai = 82;

if (nilai >= 75) {
  console.log("Lulus");
} else {
  console.log("Remedial");
}

for (let i = 1; i <= 5; i++) {
  console.log("Pengulangan ke-" + i);
}','javascript',
'- `if / else` memilih salah satu blok berdasarkan kondisi `nilai >= 75`.
- `for` memiliki tiga bagian: inisialisasi (`let i = 1`), kondisi (`i <= 5`), dan perubahan (`i++`).
- Perulangan berhenti saat kondisi bernilai salah.',
'Ubah nilai menjadi 60 dan perhatikan hasilnya. Lalu ubah perulangan agar berjalan sampai 10.',
'Buat program yang mencetak tabel perkalian angka 7 dari 1 sampai 10.',15,15,2),
(7,4,'Class dan Object','class-dan-object',
'Class adalah cetak biru (blueprint), object adalah hasil cetakannya. Class mendefinisikan **properti** (data) dan **method** (perilaku). Object dibuat dengan kata kunci `new`.

Konstruktor `__construct` dijalankan otomatis saat object dibuat, dan `$this` merujuk pada object yang sedang berjalan.',
'<?php
declare(strict_types=1);

class Mahasiswa
{
    public function __construct(
        private string $nama,
        private int $semester
    ) {}

    public function perkenalan(): string
    {
        return "Halo, saya {$this->nama} semester {$this->semester}.";
    }
}

$m = new Mahasiswa("Rina", 3);
echo $m->perkenalan();','php',
'- `private` pada parameter konstruktor membuat properti tersembunyi dari luar class (constructor promotion, PHP 8).
- `perkenalan()` adalah method yang mengakses properti lewat `$this`.
- `new Mahasiswa("Rina", 3)` membuat satu object dengan data tertentu.',
'Tambahkan method `naikSemester()` yang menambah semester sebanyak satu.',
'Buat class `Buku` dengan properti judul dan penulis, serta method untuk menampilkan deskripsinya.',20,20,1),
(8,4,'Enkapsulasi dan Pewarisan','enkapsulasi-pewarisan',
'**Enkapsulasi** menyembunyikan data internal dan hanya membuka akses melalui method yang terkontrol, sehingga data tidak diubah sembarangan. **Pewarisan** memungkinkan class anak memakai dan memperluas perilaku class induk dengan kata kunci `extends`.

Gunakan `private` untuk data internal, `protected` agar dapat diakses class turunan, dan `public` untuk antarmuka yang boleh dipakai siapa saja.',
'<?php
declare(strict_types=1);

class Rekening
{
    protected float $saldo = 0;

    public function setor(float $jumlah): void
    {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException("Jumlah harus positif");
        }
        $this->saldo += $jumlah;
    }

    public function saldo(): float
    {
        return $this->saldo;
    }
}

class RekeningTabungan extends Rekening
{
    public function tambahBunga(float $persen): void
    {
        $this->saldo += $this->saldo * $persen / 100;
    }
}','php',
'- `setor()` memvalidasi input sebelum mengubah saldo; inilah manfaat enkapsulasi.
- `protected` membuat `$saldo` dapat dipakai `RekeningTabungan`, tetapi tidak dari luar.
- `extends` membuat `RekeningTabungan` mewarisi `setor()` dan `saldo()`.',
'Tambahkan method `tarik()` yang menolak penarikan melebihi saldo.',
'Buat class `Hewan` dan dua turunannya dengan method `bersuara()` yang berbeda.',20,20,2),
(9,5,'Singleton dan Repository Pattern','singleton-repository',
'**Singleton** memastikan sebuah class hanya memiliki satu instance, cocok untuk koneksi database. **Repository** memisahkan logika akses data dari logika bisnis: controller memanggil repository, repository yang berbicara dengan database.

Manfaatnya: query terkumpul di satu tempat, mudah diuji, dan perubahan database tidak merembet ke seluruh aplikasi. Waspadai singleton yang berlebihan karena dapat menyulitkan pengujian.',
'<?php
final class CourseRepository
{
    public function __construct(private PDO $pdo) {}

    public function findActiveByLevel(string $level): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, title FROM courses WHERE level = :level AND is_active = 1"
        );
        $stmt->execute([":level" => $level]);
        return $stmt->fetchAll();
    }
}','php',
'- PDO diberikan lewat konstruktor (dependency injection), bukan dibuat di dalam class.
- Prepared statement dengan parameter bernama mencegah SQL Injection.
- Controller cukup memanggil `findActiveByLevel()` tanpa tahu detail SQL.',
'Tambahkan method `findBySlug(string $slug)` pada repository tersebut.',
'Buat `UserRepository` dengan method `findByEmail` dan `create`.',25,30,1);

INSERT INTO `quizzes` (`id`,`course_id`,`lesson_id`,`title`,`description`,`difficulty`,`pass_score`,`xp_reward`) VALUES
(1,1,NULL,'Kuis HTML Dasar','Uji pemahaman struktur dan tag dasar HTML','easy',70,50);

INSERT INTO `questions` (`id`,`quiz_id`,`type`,`question_text`,`code_snippet`,`correct_answer`,`explanation`,`points`,`sort_order`) VALUES
(1,1,'multiple_choice','Tag manakah yang dipakai untuk judul utama halaman?',NULL,NULL,'`<h1>` adalah heading tingkat tertinggi dan sebaiknya dipakai sekali per halaman.',25,1),
(2,1,'true_false','Atribut alt pada tag img berguna untuk aksesibilitas.',NULL,NULL,'Benar. Teks alt dibaca pembaca layar dan tampil saat gambar gagal dimuat.',25,2),
(3,1,'code_completion','Lengkapi atribut agar tautan menuju https://example.com:','<a ____="https://example.com">Contoh</a>','href','Atribut href berisi alamat tujuan tautan.',25,3),
(4,1,'debugging','Kode berikut gagal menampilkan daftar dengan benar. Apa masalahnya?','<ul>
  <p>Satu</p>
  <p>Dua</p>
</ul>',NULL,'Item di dalam ul harus dibungkus li, bukan p.',25,4);

INSERT INTO `question_options` (`question_id`,`option_text`,`is_correct`,`sort_order`) VALUES
(1,'<h1>',1,1),(1,'<title>',0,2),(1,'<head>',0,3),(1,'<header>',0,4),
(2,'Benar',1,1),(2,'Salah',0,2),
(4,'Tag p seharusnya diganti li',1,1),(4,'Tag ul tidak boleh dipakai',0,2),(4,'Perlu atribut href',0,3),(4,'Tidak ada masalah',0,4);

INSERT INTO `announcements` (`title`,`body`,`is_active`,`published_at`) VALUES
('Selamat datang di CodeMif','Mulai perjalanan coding Anda dari kursus HTML Dasar. Selamat belajar!',1,NOW());
