USE `codenexa_db`;
SET NAMES utf8mb4;

-- CODE NEXA SEED STATEMENT
INSERT INTO `courses` (`category_id`,`title`,`slug`,`description`,`language`,`level`,`estimated_minutes`)
SELECT c.id, 'CSS Dasar dan Layout', 'css-dasar',
       'Pelajari styling dari nol: selector, warna, tipografi, box model, Flexbox, Grid, dan desain responsif.',
       'CSS', 'beginner', 210
FROM `categories` c
WHERE c.slug='web-frontend'
  AND NOT EXISTS (SELECT 1 FROM `courses` WHERE slug='css-dasar')
UNION ALL
SELECT c.id, 'Python Dasar dan Proyek Praktik', 'python-dasar',
       'Pelajari Python dari dasar hingga membangun aplikasi: sintaks, tipe data, logika, fungsi, koleksi, file, modul, OOP, pengujian, dan proyek akhir.',
       'Python', 'beginner', 420
FROM `categories` c
WHERE c.slug='dasar-pemrograman'
  AND NOT EXISTS (SELECT 1 FROM `courses` WHERE slug='python-dasar');

-- CODE NEXA SEED STATEMENT
INSERT INTO `courses` (`category_id`,`title`,`slug`,`description`,`language`,`level`,`estimated_minutes`)
SELECT c.id, 'JavaScript Dasar dan Interaktif', 'javascript-dasar',
       'Bangun logika pemrograman, olah data, dan buat halaman web interaktif dengan JavaScript.',
       'JavaScript', 'beginner', 270
FROM `categories` c
WHERE c.slug='web-frontend'
  AND NOT EXISTS (SELECT 1 FROM `courses` WHERE slug='javascript-dasar');

-- CODE NEXA SEED STATEMENT
INSERT INTO `courses` (`category_id`,`title`,`slug`,`description`,`language`,`level`,`estimated_minutes`)
SELECT c.id, 'PHP Native dan MySQL', 'php-native-mysql',
       'Buat aplikasi web dinamis dengan PHP Native, form yang aman, database MySQL, dan CRUD memakai PDO.',
       'PHP', 'beginner', 300
FROM `categories` c
WHERE c.slug='web-backend'
  AND NOT EXISTS (SELECT 1 FROM `courses` WHERE slug='php-native-mysql');

-- CODE NEXA SEED STATEMENT
INSERT INTO `modules` (`course_id`,`title`,`description`,`sort_order`)
SELECT c.id, m.title, m.description, m.sort_order
FROM `courses` c
JOIN (
    SELECT 'css-dasar' AS course_slug, 'Fondasi CSS' AS title, 'Cara memasang CSS, selector, warna, dan tipografi.' AS description, 1 AS sort_order
    UNION ALL SELECT 'css-dasar','Box Model dan Tata Letak','Ukuran elemen, jarak, Flexbox, dan CSS Grid.',2
    UNION ALL SELECT 'css-dasar','Responsif dan Proyek','Media query dan proyek halaman profil responsif.',3
    UNION ALL SELECT 'javascript-dasar','Dasar JavaScript','Variabel, tipe data, operator, dan percabangan.',1
    UNION ALL SELECT 'javascript-dasar','Logika dan Fungsi','Perulangan, array, object, dan fungsi.',2
    UNION ALL SELECT 'javascript-dasar','DOM dan Interaksi','Event browser, validasi form, dan proyek interaktif.',3
    UNION ALL SELECT 'php-native-mysql','Dasar PHP Native','Sintaks, variabel, tipe data, dan alur program.',1
    UNION ALL SELECT 'php-native-mysql','Form dan Pengolahan Data','Request, validasi input, array, dan fungsi.',2
    UNION ALL SELECT 'php-native-mysql','MySQL, PDO, dan CRUD','Koneksi database aman dan operasi data dasar.',3
) AS m ON m.course_slug=c.slug
WHERE NOT EXISTS (
    SELECT 1 FROM `modules` existing
    WHERE existing.course_id=c.id AND existing.title=m.title
);

-- CODE NEXA SEED STATEMENT
INSERT INTO `lessons`
(`module_id`,`title`,`slug`,`content_theory`,`code_example`,`code_language`,`code_explanation`,`exercise`,`project_task`,`duration_minutes`,`xp_reward`,`sort_order`)
SELECT m.id, l.title, l.slug, l.content_theory, l.code_example, l.code_language,
       l.code_explanation, l.exercise, l.project_task, l.duration_minutes, l.xp_reward, l.sort_order
FROM (
    SELECT 'css-dasar' AS course_slug, 'Fondasi CSS' AS module_title,
           'Menghubungkan CSS dan Selector' AS title, 'menghubungkan-css-selector' AS slug,
           'CSS mengatur tampilan elemen HTML. Aturan CSS terdiri dari selector, properti, dan nilai. CSS eksternal paling mudah dirawat karena bisa dipakai banyak halaman. Selector class diawali titik dan dapat dipakai berulang; selector id diawali pagar dan sebaiknya unik.' AS content_theory,
           '/* file style.css */
.judul {
  color: #176b52;
  font-size: 2rem;
}

/* di dalam head HTML:
<link rel="stylesheet" href="style.css">
*/' AS code_example, 'css' AS code_language,
           'Selector memilih elemen yang akan diberi gaya. Properti color mengatur warna teks dan font-size mengatur ukuran tulisan.' AS code_explanation,
           'Buat tiga aturan CSS untuk judul, paragraf, dan tautan. Gunakan class yang namanya menjelaskan fungsi elemen.' AS exercise,
           'Buat halaman profil singkat dan pindahkan seluruh gaya tampilannya ke file CSS eksternal.' AS project_task, 15 AS duration_minutes, 10 AS xp_reward, 1 AS sort_order
    UNION ALL SELECT 'css-dasar','Fondasi CSS','Warna, Tipografi, dan Cascade','warna-tipografi-cascade',
           'Browser menggabungkan aturan CSS berdasarkan cascade: asal aturan, spesifisitas selector, dan urutan deklarasi. Gunakan warna yang kontras, ukuran teks yang nyaman, serta satuan relatif seperti rem untuk tipografi agar halaman lebih mudah diakses.',
           'body {
  font-family: Arial, sans-serif;
  color: #24342c;
  line-height: 1.6;
}

.muted {
  color: #52665a;
  font-size: 0.9rem;
}',
           'css','Aturan pada body diwariskan ke teks di dalamnya. Class muted dapat mengubah warna dan ukuran teks secara khusus.',
           'Pilih palet tiga warna yang kontras dan atur hierarki ukuran untuk judul, isi, dan teks bantu.',
           'Rapikan halaman profil dengan palet warna konsisten dan tipografi yang mudah dibaca.',15,10,2
    UNION ALL SELECT 'css-dasar','Box Model dan Tata Letak','Box Model dan Spacing','box-model-spacing',
           'Setiap elemen berbentuk kotak: content, padding, border, dan margin. Dengan box-sizing border-box, lebar elemen sudah mencakup padding dan border sehingga lebih mudah dihitung. Gunakan margin untuk jarak luar dan padding untuk ruang di dalam elemen.',
           '* {
  box-sizing: border-box;
}

.card {
  width: 100%;
  max-width: 420px;
  padding: 24px;
  border: 1px solid #d7e3da;
  margin: 16px auto;
}',
           'css','max-width membatasi lebar card, padding memberi ruang di dalamnya, dan margin auto memusatkannya secara horizontal.',
           'Gambar diagram kotak content, padding, border, dan margin. Ubah padding dan amati perbedaan jarak.',
           'Buat kartu artikel dengan lebar maksimal, ruang dalam nyaman, dan sudut membulat.',15,10,1
    UNION ALL SELECT 'css-dasar','Box Model dan Tata Letak','Flexbox untuk Komponen','flexbox-komponen',
           'Flexbox mengatur elemen dalam satu dimensi: baris atau kolom. Aktifkan dengan display:flex. Gunakan justify-content untuk distribusi sepanjang sumbu utama dan align-items untuk perataan sumbu silang. Properti gap membuat jarak antarelemen.',
           '.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.menu {
  display: flex;
  gap: 12px;
}',
           'css','Flexbox cocok untuk navigasi, tombol, dan kelompok komponen yang perlu sejajar.',
           'Susun logo dan tiga tautan navigasi dalam satu baris. Pastikan elemen tetap rapi saat ruang menyempit.',
           'Buat navigasi dan baris kartu fitur menggunakan Flexbox.',20,15,2
    UNION ALL SELECT 'css-dasar','Responsif dan Proyek','CSS Grid dan Media Query','grid-media-query',
           'CSS Grid membagi ruang menjadi baris dan kolom, cocok untuk galeri atau dashboard. Media query menerapkan aturan berdasarkan ukuran layar. Mulai dari tampilan satu kolom, lalu tambahkan kolom saat layar lebih lebar.',
           '.cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 700px) {
  .cards {
    grid-template-columns: repeat(2, 1fr);
  }
}',
           'css','repeat(2, 1fr) membuat dua kolom sama lebar. Media query aktif pada layar minimal 700 piksel.',
           'Ubah galeri satu kolom menjadi dua kolom untuk tablet dan tiga kolom untuk desktop.',
           'Bangun halaman portofolio responsif berisi header, profil, tiga proyek, dan footer.',25,20,1
    UNION ALL SELECT 'css-dasar','Responsif dan Proyek','Kerapian dan Aksesibilitas CSS','css-aksesibilitas',
           'Gaya visual harus tetap mudah digunakan. Pertahankan indikator fokus keyboard, kontras warna, ukuran tombol yang cukup, dan jangan menyampaikan status hanya dengan warna. Hindari selector yang terlalu spesifik agar gaya mudah dikembangkan.',
           ':focus-visible {
  outline: 3px solid #176b52;
  outline-offset: 3px;
}

button {
  min-height: 44px;
  padding: 10px 16px;
}',
           'css','focus-visible membantu pengguna keyboard melihat elemen aktif. Target tombol yang cukup besar lebih mudah ditekan.',
           'Navigasikan halaman hanya dengan tombol Tab dan pastikan fokus terlihat pada setiap tautan dan tombol.',
           'Selesaikan portofolio dengan status hover/fokus dan periksa tampilan ponsel serta desktop.',20,15,2
    UNION ALL SELECT 'javascript-dasar','Dasar JavaScript','Variabel, Tipe Data, dan Operator','variabel-tipe-operator',
           'JavaScript menambahkan perilaku pada halaman web. Gunakan const untuk nilai yang tidak akan ditugaskan ulang dan let untuk nilai yang berubah. Tipe data dasar meliputi string, number, boolean, null, dan undefined. Operator digunakan untuk menghitung dan membandingkan nilai.',
           'const nama = "Ayu";
let skor = 0;
skor = skor + 10;
const lulus = skor >= 7;
console.log(nama, skor, lulus);',
           'javascript','const mencegah penugasan ulang variabel, sedangkan let dapat diperbarui. Operator >= menghasilkan nilai boolean.',
           'Buat variabel nama produk, harga, jumlah, lalu hitung total dan apakah total melebihi batas belanja.',
           'Buat kalkulator sederhana di console untuk menghitung total belanja dan diskon.',15,10,1
    UNION ALL SELECT 'javascript-dasar','Dasar JavaScript','Percabangan dan Kondisi','percabangan-kondisi',
           'Percabangan menjalankan kode berbeda berdasarkan kondisi. Gunakan if/else untuk pilihan logika. Bandingkan nilai dengan === agar tipe data ikut diperiksa. Gabungkan kondisi dengan && (dan) atau || (atau).',
           'const nilai = 82;

if (nilai >= 75) {
  console.log("Lulus");
} else {
  console.log("Perlu berlatih lagi");
}',
           'javascript','Blok if berjalan saat kondisi benar. else menangani kondisi yang tidak terpenuhi.',
           'Buat penentu kategori nilai: A untuk 90 ke atas, B untuk 80 ke atas, C untuk 70 ke atas, selain itu perlu latihan.',
           'Tulis program rekomendasi belajar berdasarkan nilai dan kehadiran.',15,10,2
    UNION ALL SELECT 'javascript-dasar','Logika dan Fungsi','Perulangan dan Array','perulangan-array',
           'Array menyimpan kumpulan nilai berurutan. Gunakan for...of untuk membaca setiap nilai. Perulangan mengurangi kode berulang, sedangkan kondisi di dalam loop dapat memilih data yang perlu diproses.',
           'const nilai = [80, 92, 67];
let total = 0;

for (const angka of nilai) {
  total += angka;
}

const rataRata = total / nilai.length;
console.log(rataRata);',
           'javascript','length berisi jumlah data. Operator += menambahkan nilai ke total pada setiap putaran.',
           'Hitung jumlah angka genap dalam array dan temukan nilai terbesar tanpa memakai library.',
           'Buat ringkasan nilai kelas: rata-rata, nilai tertinggi, dan jumlah peserta lulus.',20,15,1
    UNION ALL SELECT 'javascript-dasar','Logika dan Fungsi','Object dan Fungsi','object-fungsi',
           'Object menyimpan data sebagai pasangan key dan value. Fungsi mengemas langkah kerja agar bisa digunakan kembali. Parameter menerima input dan return mengirim hasil kembali ke pemanggil.',
           'function hitungDiskon(harga, persen) {
  return harga * persen / 100;
}

const produk = { nama: "Buku", harga: 50000 };
const diskon = hitungDiskon(produk.harga, 10);
console.log(produk.nama, produk.harga - diskon);',
           'javascript','produk.nama mengakses properti object. Fungsi dapat menerima nilai berbeda dan menghasilkan keluaran dengan return.',
           'Buat object siswa dan fungsi yang menghitung status kelulusan dari nilai siswa tersebut.',
           'Buat daftar produk object dan fungsi untuk menghitung total harga keranjang.',20,15,2
    UNION ALL SELECT 'javascript-dasar','DOM dan Interaksi','DOM dan Event','dom-event',
           'DOM adalah representasi elemen HTML yang dapat dibaca dan diubah JavaScript. Pilih elemen dengan querySelector, ubah teks melalui textContent, dan tanggapi aksi pengguna dengan addEventListener.',
           'const tombol = document.querySelector("#tambah");
const hasil = document.querySelector("#hasil");
let jumlah = 0;

tombol.addEventListener("click", () => {
  jumlah += 1;
  hasil.textContent = jumlah;
});',
           'javascript','Event click terjadi saat tombol ditekan. textContent mengubah teks tanpa menafsirkan HTML.',
           'Tambahkan tombol pengurang dan tombol reset. Pastikan nilai tidak menjadi angka negatif.',
           'Buat penghitung interaktif yang bisa tambah, kurang, dan reset.',20,15,1
    UNION ALL SELECT 'javascript-dasar','DOM dan Interaksi','Validasi Form dan Proyek','validasi-form-js',
           'Validasi di browser memberi umpan balik cepat, tetapi server tetap harus memvalidasi ulang data. Gunakan preventDefault untuk menghentikan pengiriman form sementara, periksa nilai, lalu tampilkan pesan dengan textContent.',
           'const form = document.querySelector("#form-kontak");
const pesan = document.querySelector("#pesan");

form.addEventListener("submit", (event) => {
  event.preventDefault();
  const nama = form.elements.nama.value.trim();

  pesan.textContent = nama
    ? `Terima kasih, ${nama}!`
    : "Nama wajib diisi.";
});',
           'javascript','trim menghapus spasi di awal dan akhir. Template literal menyisipkan nilai ke dalam string.',
           'Validasi nama dan email, lalu tampilkan pesan kesalahan yang jelas pada kolom yang bermasalah.',
           'Buat halaman daftar tugas: tambah tugas, tandai selesai, dan hapus tugas menggunakan DOM.',25,20,2
    UNION ALL SELECT 'php-native-mysql','Dasar PHP Native','Sintaks PHP dan Output HTML','sintaks-php-output',
           'PHP dijalankan di server dan hasilnya dikirim sebagai HTML ke browser. Kode PHP ditulis di antara tag <?php dan ?>. Variabel diawali $, echo mengirim keluaran, dan titik (.) menggabungkan string.',
           '<?php
$nama = "Dina";
$jumlahMateri = 3;
echo "<h1>Halo, " . htmlspecialchars($nama, ENT_QUOTES, "UTF-8") . "</h1>";
echo "<p>Materi: " . $jumlahMateri . "</p>";',
           'php','htmlspecialchars mengubah karakter khusus agar input tidak ditafsirkan sebagai HTML saat ditampilkan.',
           'Buat halaman PHP yang menampilkan nama dan tiga tujuan belajar dengan keluaran HTML yang aman.',
           'Buat halaman perkenalan PHP dan jalankan melalui server lokal, bukan membuka file langsung di browser.',15,10,1
    UNION ALL SELECT 'php-native-mysql','Dasar PHP Native','Kondisi, Perulangan, dan Array','php-kondisi-array',
           'PHP memiliki if/else, foreach, dan array asosiatif. foreach memudahkan membaca setiap item. Selalu periksa tipe data dan berikan nilai default yang masuk akal untuk mencegah peringatan saat data belum tersedia.',
           '<?php
$materi = ["HTML", "CSS", "PHP"];

foreach ($materi as $nomor => $judul) {
    echo "<p>" . ($nomor + 1) . ". "
        . htmlspecialchars($judul, ENT_QUOTES, "UTF-8")
        . "</p>";
}',
           'php','Array berisi daftar materi. foreach mengambil indeks dan nilai pada setiap iterasi.',
           'Simpan daftar nilai dalam array, tampilkan hanya nilai yang lulus, dan hitung jumlahnya.',
           'Buat daftar materi kursus yang dirender dari array asosiatif dengan status selesai.',20,15,2
    UNION ALL SELECT 'php-native-mysql','Form dan Pengolahan Data','Request dan Validasi Form','request-validasi-php',
           'Form HTML biasanya mengirim data dengan GET atau POST. Baca POST untuk data yang mengubah keadaan, validasi setiap nilai di server, dan jangan pernah menganggap validasi browser cukup. Trim spasi dan batasi panjang input.',
           '<?php
$nama = trim((string)($_POST["nama"] ?? ""));
$errors = [];

if ($nama === "" || mb_strlen($nama) > 100) {
    $errors[] = "Nama wajib diisi (maksimal 100 karakter).";
}

if (!$errors) {
    echo "Data siap diproses.";
}',
           'php','Null coalescing ?? memberi nilai default bila input belum dikirim. Casting string dan trim menormalkan input.',
           'Validasi nama, email dengan filter_var, serta pesan yang panjangnya dibatasi.',
           'Buat form pendaftaran peserta dengan validasi server dan pesan sukses/gagal yang aman.',20,15,1
    UNION ALL SELECT 'php-native-mysql','Form dan Pengolahan Data','Fungsi dan Pemisahan File','fungsi-include-php',
           'Fungsi membuat logika bisa digunakan ulang dan lebih mudah diuji. Pisahkan konfigurasi dan fungsi umum dari tampilan. Gunakan require_once untuk memuat file penting tepat satu kali, dan gunakan nama fungsi yang menjelaskan tugasnya.',
           '<?php
function formatNama(string $nama): string
{
    return mb_convert_case(trim($nama), MB_CASE_TITLE, "UTF-8");
}

echo htmlspecialchars(formatNama("budi santoso"), ENT_QUOTES, "UTF-8");',
           'php','Type hint string menjelaskan input fungsi. Fungsi mengembalikan hasil, sedangkan htmlspecialchars mengamankannya saat dirender.',
           'Buat fungsi validasi email dan fungsi untuk mengubah input nama menjadi format yang rapi.',
           'Pisahkan fungsi validasi form ke file helper dan gunakan kembali pada dua halaman.',20,15,2
    UNION ALL SELECT 'php-native-mysql','MySQL, PDO, dan CRUD','Koneksi PDO dan Prepared Statement','pdo-prepared-statement',
           'PDO menyediakan akses database yang konsisten. Gunakan prepared statement untuk memisahkan query dari input pengguna sehingga input tidak dapat mengubah struktur SQL. Simpan kredensial di konfigurasi server, bukan di file publik.',
           '<?php
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$stmt = $pdo->prepare("SELECT id, name FROM students WHERE email = ?");
$stmt->execute([$email]);
$student = $stmt->fetch();',
           'php','Parameter ? dikirim terpisah dari query. Jangan menyusun query dengan menggabungkan input pengguna.',
           'Buat pencarian peserta berdasarkan email memakai prepared statement dan tangani hasil tidak ditemukan.',
           'Hubungkan halaman peserta ke MySQL menggunakan PDO dan konfigurasi koneksi yang tidak dipublikasikan.',25,20,1
    UNION ALL SELECT 'php-native-mysql','MySQL, PDO, dan CRUD','CRUD dan Proyek Akhir','crud-proyek-php',
           'CRUD berarti Create, Read, Update, Delete. Gunakan query terpisah untuk setiap operasi dan validasi ID sebelum mengubah data. Untuk perubahan yang terdiri dari beberapa query, gunakan transaksi agar semua perubahan berhasil atau seluruhnya dibatalkan.',
           '<?php
$stmt = $pdo->prepare("INSERT INTO students (name, email) VALUES (?, ?)");
$stmt->execute([$nama, $email]);

$stmt = $pdo->prepare("SELECT id, name, email FROM students ORDER BY id DESC");
$stmt->execute();
$students = $stmt->fetchAll();',
           'php','Nilai pengguna tetap dikirim lewat parameter. Untuk update dan delete, validasi ID lalu gunakan prepared statement juga.',
           'Implementasikan tambah, daftar, ubah, dan hapus peserta; tampilkan konfirmasi sebelum menghapus.',
           'Bangun aplikasi daftar peserta kursus dengan CRUD, validasi server, prepared statement, dan tampilan kosong yang informatif.',30,25,2
) AS l
JOIN `courses` c ON c.slug=l.course_slug
JOIN `modules` m ON m.course_id=c.id AND m.title=l.module_title
WHERE NOT EXISTS (
    SELECT 1 FROM `lessons` existing
    WHERE existing.module_id=m.id AND existing.slug=l.slug
);
