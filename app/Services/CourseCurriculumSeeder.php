<?php
declare(strict_types=1);

namespace App\Services;

final class CourseCurriculumSeeder
{
    private const PYTHON_CURRICULUM = [
        [
            'Menjalankan Python dan print',
            'Python adalah bahasa pemrograman yang sintaksnya mudah dibaca. Simpan kode dalam file .py, jalankan dengan Python 3, dan gunakan print untuk menampilkan hasil.',
            'print("Halo, Python!")\nprint("Saya mulai belajar pemrograman.")',
            'Setiap pemanggilan print menampilkan nilai pada baris baru. Teks ditulis di antara tanda kutip.',
            'Ubah pesan dan tampilkan nama serta tujuan belajar pada dua baris.',
            'Buat program sambutan singkat yang menampilkan nama, kursus, dan target belajar.',
        ],
        [
            'Variabel dan aturan penamaan',
            'Variabel memberi nama pada nilai agar dapat digunakan kembali. Nama variabel Python peka huruf besar-kecil, tidak boleh diawali angka, dan sebaiknya menjelaskan isi nilainya.',
            'nama = "Ayu"\numur = 18\nprint(nama, umur)',
            'Tanda sama dengan memberikan nilai ke variabel. Python menentukan jenis nilai dari data yang diberikan.',
            'Buat variabel untuk nama, kota, dan hobi, lalu tampilkan kalimat perkenalan.',
            'Simpan informasi profil sederhana dalam variabel dan cetak sebagai ringkasan.',
        ],
        [
            'Tipe data dan konversi',
            'Tipe data dasar meliputi str untuk teks, int untuk bilangan bulat, float untuk desimal, dan bool untuk benar atau salah. Gunakan int(), float(), atau str() untuk konversi yang sesuai.',
            'jumlah = int("3")\nharga = float("12500.5")\naktif = True\nprint(type(jumlah), harga, aktif)',
            'Fungsi type membantu mengenali tipe data. Konversi hanya berhasil jika isi nilai dapat diubah ke tipe tujuan.',
            'Konversikan teks angka menjadi bilangan, lalu hitung total dua nilai.',
            'Buat ringkasan belanja dengan harga, jumlah barang, dan total bertipe numerik.',
        ],
        [
            'Input dan output pengguna',
            'input() membaca masukan pengguna sebagai teks. Konversikan hasilnya sebelum dipakai dalam perhitungan dan berikan prompt yang menjelaskan data yang diminta.',
            'nama = input("Nama kamu: ")\numur = int(input("Umur kamu: "))\nprint(f"Halo {nama}, tahun depan umurmu {umur + 1}.")',
            'f-string menyisipkan ekspresi di dalam kurung kurawal. input selalu menghasilkan str, sehingga nilai angka perlu dikonversi.',
            'Minta dua angka dari pengguna dan tampilkan hasil penjumlahannya.',
            'Buat kalkulator sederhana yang menerima dua angka dan menampilkan hasil hitung.',
        ],
        [
            'Operator aritmetika dan prioritas',
            'Python mendukung +, -, *, /, //, %, dan **. Tanda kurung memperjelas urutan operasi. Pembagian biasa menghasilkan float, sedangkan // membulatkan ke bawah.',
            'harga = 25000\njumlah = 3\ntotal = harga * jumlah\ndiskon = total * 0.1\nprint(total - diskon)',
            'Perkalian dikerjakan sebelum pengurangan. Kurung dapat digunakan agar rumus lebih mudah dibaca.',
            'Hitung luas persegi panjang dan sisa pembagian sejumlah benda ke beberapa orang.',
            'Buat kalkulator diskon yang menampilkan harga awal, diskon, dan harga akhir.',
        ],
        [
            'Perbandingan dan nilai boolean',
            'Operator ==, !=, <, <=, >, dan >= membandingkan nilai dan menghasilkan True atau False. Gunakan == untuk membandingkan, bukan = yang digunakan untuk memberi nilai.',
            'nilai = 82\nlulus = nilai >= 75\nprint(lulus)\nprint(nilai == 100)',
            'Hasil ekspresi perbandingan dapat disimpan pada variabel boolean atau langsung digunakan pada kondisi.',
            'Buat pemeriksaan apakah umur memenuhi batas tertentu dan apakah nilai lulus.',
            'Buat pemeriksa kelulusan dengan beberapa kondisi nilai dan kehadiran.',
        ],
        [
            'Percabangan if, elif, dan else',
            'Percabangan menjalankan blok berbeda sesuai kondisi. Python menggunakan indentasi untuk menandai isi blok. Gunakan elif untuk kondisi lanjutan dan else sebagai pilihan terakhir.',
            'nilai = 82\nif nilai >= 90:\n    predikat = "A"\nelif nilai >= 75:\n    predikat = "B"\nelse:\n    predikat = "Perlu latihan"\nprint(predikat)',
            'Kondisi diperiksa dari atas ke bawah. Blok pertama yang kondisinya benar akan dijalankan.',
            'Buat kategori nilai A, B, C, dan perlu perbaikan, lalu uji nilai pada batas kategori.',
            'Buat program rekomendasi belajar berdasarkan nilai dan jumlah kehadiran.',
        ],
        [
            'Logika gabungan dan validasi',
            'Gunakan and jika semua kondisi harus benar, or jika salah satu cukup, dan not untuk membalik kondisi. Gabungkan operator dengan tanda kurung agar maksudnya jelas.',
            'umur = 20\npunya_izin = True\nboleh_daftar = umur >= 18 and punya_izin\nprint(boleh_daftar)',
            'Ekspresi and bernilai benar hanya jika kedua syarat benar. Validasi input sebelum memprosesnya.',
            'Periksa apakah angka berada dalam rentang 1 sampai 100 dan tidak sama dengan nilai terlarang.',
            'Buat validasi formulir pendaftaran dengan pemeriksaan umur, email terisi, dan persetujuan.',
        ],
        [
            'Perulangan for dan range',
            'for mengulang langkah untuk setiap item. range(start, stop) menghasilkan bilangan dari start sampai sebelum stop. Tentukan batas dengan cermat agar jumlah pengulangan sesuai.',
            'for nomor in range(1, 6):\n    print(f"Latihan ke-{nomor}")',
            'range(1, 6) menghasilkan 1 hingga 5. Variabel nomor berubah pada setiap iterasi.',
            'Cetak tabel perkalian angka pilihan dari 1 sampai 10.',
            'Buat program jadwal latihan yang menampilkan nomor dan target setiap hari.',
        ],
        [
            'Perulangan while dan kontrol loop',
            'while mengulang selama kondisinya benar. Pastikan ada perubahan yang membuat kondisi berhenti. break menghentikan loop dan continue melanjutkan ke iterasi berikutnya.',
            'nomor = 1\nwhile nomor <= 3:\n    print(nomor)\n    nomor += 1',
            'Jika nomor tidak bertambah, kondisi selalu benar dan loop tidak akan berhenti.',
            'Buat menu yang terus tampil sampai pengguna memilih keluar.',
            'Buat program tebak angka dengan jumlah percobaan terbatas.',
        ],
        [
            'String dan pemformatan teks',
            'String adalah teks yang dapat digabung, diambil sebagian, dan diformat. String bersifat immutable; method seperti lower dan strip menghasilkan nilai baru.',
            'nama = "  Budi Santoso  "\nrapi = nama.strip().title()\nprint(f"Halo, {rapi}")',
            'strip menghapus spasi di ujung, title mengubah awal kata menjadi huruf kapital, dan f-string menyusun pesan.',
            'Bersihkan input nama dari spasi dan tampilkan dalam huruf kecil serta format judul.',
            'Buat formatter nama dan ringkasan biodata yang rapi.',
        ],
        [
            'List, tuple, set, dan operasi data',
            'List menyimpan data berurutan dan dapat diubah. Tuple berurutan tetapi tetap, sedangkan set menyimpan nilai unik. Gunakan append untuk menambah list, indeks untuk mengambil item, dan len untuk menghitung isinya.',
            'materi = ["variabel", "kondisi"]\nmateri.append("perulangan")\ntetap = ("dasar", "lanjutan")\nunik = {"python", "dasar", "python"}\nprint(materi[0], len(materi), tetap, unik)',
            'Indeks list dimulai dari 0. Tuple cocok untuk data tetap dan set menghilangkan duplikasi.',
            'Buat daftar tugas, tambahkan dan ubah item, lalu hilangkan nama duplikat dari daftar peserta menggunakan set.',
            'Bangun daftar belanja dan kelompokkan kategori unik produk.',
        ],
        [
            'Dictionary dan data terstruktur',
            'Dictionary menyimpan pasangan key-value. Gunakan key bermakna untuk mengakses data dan get() jika key mungkin belum tersedia.',
            'siswa = {"nama": "Dina", "nilai": 88}\nsiswa["lulus"] = siswa["nilai"] >= 75\nprint(siswa.get("nama"), siswa["lulus"])',
            'Dictionary dapat diperbarui dengan key baru. get menghindari KeyError saat key tidak ditemukan.',
            'Buat dictionary produk berisi nama, harga, dan stok, lalu ubah stok setelah pembelian.',
            'Buat ringkasan nilai beberapa siswa memakai list berisi dictionary.',
        ],
        [
            'Fungsi, parameter, dan return',
            'Fungsi membungkus tugas yang dapat dipakai ulang. Parameter menerima masukan dan return mengirim hasil. Pisahkan perhitungan dari input dan output agar lebih mudah diuji.',
            'def hitung_diskon(harga, persen):\n    return harga * persen / 100\n\npotongan = hitung_diskon(50000, 10)\nprint(potongan)',
            'Pemanggilan fungsi menghasilkan nilai dari return. Tanpa return, fungsi mengembalikan None.',
            'Buat fungsi untuk menghitung rata-rata dan status kelulusan dari daftar nilai.',
            'Pecah kalkulator belanja menjadi fungsi hitung subtotal, diskon, dan total akhir.',
        ],
        [
            'Modul, import, dan pustaka standar',
            'Modul memisahkan kode ke file yang dapat diimpor. Pustaka standar menyediakan fungsi umum tanpa instalasi tambahan. Hindari menamai file sama dengan modul standar.',
            'import math\n\nradius = 4\nluas = math.pi * radius ** 2\nprint(round(luas, 2))',
            'import math memberi akses ke pi dan fungsi dari modul math. round membatasi angka desimal untuk tampilan.',
            'Gunakan modul random untuk memilih satu item dari daftar latihan.',
            'Buat program kuis kecil dengan fungsi terpisah dalam modul.',
        ],
        [
            'Membaca dan menulis file',
            'Gunakan with open agar file ditutup otomatis. Tentukan encoding UTF-8 dan mode yang sesuai: r untuk membaca, w untuk menulis ulang, a untuk menambah.',
            'with open("catatan.txt", "w", encoding="utf-8") as file:\n    file.write("Belajar Python" + chr(10))\n\nwith open("catatan.txt", encoding="utf-8") as file:\n    print(file.read())',
            'Blok with mengelola file dengan aman. Mode w mengganti isi file lama, sementara a menambahkan isi.',
            'Simpan tiga tugas ke file lalu baca kembali dan tampilkan per baris.',
            'Buat jurnal belajar sederhana yang menambah catatan ke file teks.',
        ],
        [
            'Exception dan penanganan kesalahan',
            'Exception muncul saat operasi gagal. Tangani hanya error yang memang dapat dipulihkan dengan try/except. Berikan pesan yang membantu dan jangan menutupi kesalahan tak terduga.',
            'teks = "42"\ntry:\n    angka = int(teks)\nexcept ValueError:\n    print("Masukkan bilangan bulat yang valid.")\nelse:\n    print(angka)',
            'except ValueError menangani teks yang tidak bisa dikonversi ke integer. else berjalan hanya jika tidak ada exception.',
            'Lindungi kalkulator dari input angka tidak valid dan pembagian dengan nol.',
            'Tambahkan validasi serta pesan kesalahan yang jelas pada program pencatat data.',
        ],
        [
            'Class dan object dasar',
            'Class adalah rancangan, sedangkan object adalah data yang dibuat dari rancangan tersebut. __init__ mengisi keadaan awal dan self merujuk pada object aktif.',
            'class Buku:\n    def __init__(self, judul):\n        self.judul = judul\n\n    def deskripsi(self):\n        return f"Buku: {self.judul}"\n\nbuku = Buku("Belajar Python")\nprint(buku.deskripsi())',
            'Buku(...) membuat object. Method deskripsi membaca properti dari object melalui self.',
            'Tambahkan properti penulis dan method ringkasan pada class Buku.',
            'Buat class Produk dengan harga, stok, dan method untuk menghitung nilai persediaan.',
        ],
        [
            'Comprehension dan pengolahan data',
            'List comprehension membuat list baru dari iterable dengan ekspresi yang ringkas. Gunakan hanya jika alurnya tetap mudah dibaca.',
            'angka = [1, 2, 3, 4, 5, 6]\nganjil_kuadrat = [n ** 2 for n in angka if n % 2 == 1]\nprint(ganjil_kuadrat)',
            'Ekspresi menghasilkan kuadrat setiap angka, sedangkan kondisi menyaring angka ganjil.',
            'Buat list nilai lulus dari daftar nilai dan hitung nilai rata-ratanya.',
            'Olah data transaksi untuk menghasilkan daftar transaksi valid dan total pendapatan.',
        ],
        [
            'JSON, pengujian, dan proyek akhir',
            'JSON menyimpan data terstruktur untuk pertukaran atau penyimpanan. Uji fungsi dengan kasus normal dan batas. Proyek akhir menggabungkan input, fungsi, koleksi data, file, dan penanganan kesalahan.',
            'import json\n\ndata = {"nama": "Ayu", "materi_selesai": 3}\nteks_json = json.dumps(data, ensure_ascii=False)\nprint(teks_json)',
            'json.dumps mengubah dictionary menjadi teks JSON. Untuk membaca JSON gunakan json.loads dan tangani JSONDecodeError jika datanya tidak valid.',
            'Tulis tes sederhana untuk fungsi konversi nilai dan pastikan data JSON dapat dibaca kembali.',
            'Bangun aplikasi pencatat tugas belajar: tambah, tampilkan, tandai selesai, simpan ke JSON, dan validasi input.',
        ],
    ];

    private const OUTLINES = [
        'html-dasar' => [
            ['Apa itu HTML dan Struktur Dasar', 'Pahami fungsi HTML serta hubungan doctype, html, head, title, dan body.'],
            ['Heading, Paragraf, dan Teks', 'Susun heading secara hierarkis dan gunakan elemen teks sesuai maknanya.'],
            ['Link dan Gambar', 'Buat tautan dengan href dan gambar dengan src serta alt yang deskriptif.'],
            ['List dan Tabel', 'Susun daftar dengan ul/ol dan gunakan tabel hanya untuk data tabular.'],
            ['Tabel Data yang Aksesibel', 'Kelompokkan data dengan caption, thead, tbody, dan header kolom yang jelas.'],
            ['Struktur Semantik HTML', 'Gunakan header, nav, main, section, article, dan footer sesuai fungsi kontennya.'],
            ['Atribut Global dan Metadata', 'Atur bahasa dokumen, judul halaman, metadata, viewport, id, class, dan title.'],
            ['Form dan Label Aksesibel', 'Hubungkan label dengan input dan pilih tipe field sesuai data.'],
            ['Input, Select, dan Textarea', 'Pilih kontrol formulir yang tepat dan beri name agar nilainya ikut dikirim.'],
            ['Validasi Bawaan Browser', 'Gunakan required, minlength, maxlength, min, max, dan type sebagai pemeriksaan awal.'],
            ['Navigasi Halaman', 'Buat tautan internal dan anchor agar pengunjung mudah berpindah bagian.'],
            ['Entitas dan Karakter Khusus', 'Tampilkan karakter khusus HTML menggunakan entitas yang benar.'],
            ['Validasi Dokumen HTML', 'Periksa struktur elemen, atribut, dan nesting untuk menemukan kesalahan markup.'],
            ['SEO Dasar pada HTML', 'Tulis title, description, heading, dan struktur halaman yang menjelaskan isi dengan baik.'],
            ['Aksesibilitas Keyboard', 'Pastikan tautan dan form dapat digunakan dengan keyboard serta urutan fokus masuk akal.'],
            ['Atribut Data dan Custom Data', 'Gunakan data-* untuk menyimpan informasi tambahan yang relevan pada elemen.'],
            ['Komponen Halaman Artikel', 'Susun halaman artikel menggunakan heading, waktu, penulis, gambar, dan bagian konten.'],
            ['Komponen Halaman Kontak', 'Buat halaman kontak dengan informasi yang terstruktur dan form yang berlabel.'],
            ['Komponen Halaman Portofolio', 'Gabungkan daftar proyek, deskripsi, tautan, dan informasi pemilik secara semantik.'],
            ['Proyek Awal: Kerangka Portofolio', 'Susun kerangka portofolio dengan navigasi, profil, proyek, kontak, dan footer semantik.'],
        ],
        'logika-pemrograman' => [
            ['Apa itu algoritma', 'Tentukan masukan, proses, keluaran, dan urutan langkah untuk menyelesaikan masalah.'],
            ['Percabangan dan perulangan', 'Pilih jalur dengan kondisi dan ulangi langkah dengan aturan berhenti yang jelas.'],
            ['Memecah masalah menjadi bagian kecil', 'Tentukan masukan, proses, keluaran, dan batasan sebelum menulis solusi.'],
            ['Variabel dan tipe data', 'Pilih nama variabel bermakna dan bedakan bilangan, teks, serta nilai benar-salah.'],
            ['Operator aritmetika', 'Gunakan penjumlahan, pengurangan, perkalian, pembagian, dan sisa bagi.'],
            ['Perbandingan dan boolean', 'Ubah perbandingan menjadi kondisi benar atau salah yang dapat diuji.'],
            ['Urutan dan pseudocode', 'Tuliskan langkah algoritma dalam urutan yang lengkap dan tidak ambigu.'],
            ['Percabangan sederhana', 'Gunakan kondisi untuk memilih tindakan sesuai keadaan input.'],
            ['Percabangan bertingkat', 'Susun beberapa kondisi tanpa membuat jalur keputusan saling tumpang tindih.'],
            ['Logika AND, OR, NOT', 'Gabungkan kondisi dan periksa hasil logika dengan tabel kasus sederhana.'],
            ['Perulangan dengan penghitung', 'Tentukan nilai awal, batas, dan perubahan penghitung agar loop berhenti.'],
            ['Perulangan berdasarkan kondisi', 'Gunakan kondisi berhenti yang jelas agar perulangan tidak berjalan selamanya.'],
            ['Akumulasi dan pencacahan', 'Hitung total atau banyaknya data yang memenuhi kondisi tertentu.'],
            ['Array dan urutan data', 'Simpan sekumpulan nilai dan akses setiap elemen dengan indeks.'],
            ['Pencarian nilai', 'Telusuri data secara berurutan dan tentukan respons saat nilai ditemukan atau tidak.'],
            ['Nilai minimum dan maksimum', 'Bandingkan setiap data dengan kandidat hasil sementara.'],
            ['Fungsi dan parameter', 'Pisahkan tugas menjadi fungsi kecil dengan masukan yang jelas.'],
            ['Nilai kembali dari fungsi', 'Kembalikan hasil perhitungan agar dapat digunakan langkah berikutnya.'],
            ['Menguji, menelusuri, dan memperbaiki solusi', 'Gunakan tabel jejak dan uji input kosong, nilai ekstrem, data salah, serta data normal untuk menemukan dan memperbaiki kesalahan.'],
            ['Proyek akhir algoritma', 'Rancang solusi lengkap dengan pseudocode, percabangan, perulangan, fungsi, dan pengujian.'],
        ],
        'php-oop' => [
            ['Class dan object', 'Bedakan class sebagai rancangan dengan object sebagai instance yang memiliki keadaan.'],
            ['Enkapsulasi dan pewarisan', 'Lindungi data internal dan perluas perilaku class melalui hubungan turunan yang masuk akal.'],
            ['Properti dan method', 'Kelompokkan data dan perilaku yang saling terkait dalam satu class.'],
            ['Constructor dan this', 'Inisialisasi object melalui constructor dan akses keadaan object dengan this.'],
            ['Getter, setter, dan invariant', 'Validasi perubahan nilai agar object selalu berada dalam keadaan yang sah.'],
            ['Override method', 'Perluas perilaku induk dengan method yang tetap mengikuti kontraknya.'],
            ['Abstract class', 'Definisikan perilaku bersama dan kewajiban method bagi class turunannya.'],
            ['Interface dan kontrak', 'Buat kontrak perilaku yang dapat dipenuhi beberapa class berbeda.'],
            ['Polimorfisme', 'Gunakan object melalui kontrak yang sama tanpa bergantung pada class konkretnya.'],
            ['Composition dibanding inheritance', 'Gabungkan object kecil untuk membangun perilaku tanpa hierarki pewarisan berlebihan.'],
            ['Static dan konstanta class', 'Gunakan anggota static atau konstanta hanya untuk data yang benar-benar milik class.'],
            ['Namespace dan autoload', 'Pisahkan nama class dan muat file class secara teratur pada proyek PHP.'],
            ['Type declaration dan return type', 'Nyatakan tipe parameter dan hasil untuk memperjelas kontrak fungsi.'],
            ['Exception dan penanganan kesalahan', 'Lempar exception untuk kondisi gagal dan tangani pada batas yang tepat.'],
            ['Dependency injection', 'Kirim ketergantungan dari luar agar class mudah diuji dan diganti implementasinya.'],
            ['Readonly dan immutability', 'Lindungi nilai yang tidak seharusnya berubah setelah object dibuat.'],
            ['Trait dan penggunaan yang tepat', 'Gunakan trait secara terbatas untuk berbagi perilaku tanpa membuat kontrak palsu.'],
            ['Unit test untuk object', 'Uji perilaku class menggunakan input berbeda, termasuk kondisi batas dan kesalahan.'],
            ['Refactoring dan prinsip SOLID', 'Pisahkan tanggung jawab dan kurangi ketergantungan langsung antarclass.'],
            ['Proyek akhir OOP PHP', 'Bangun aplikasi kecil berbasis object dengan kontrak, validasi, exception, dan pengujian.'],
        ],
        'design-patterns-php' => [
            ['Singleton dan Repository Pattern', 'Pahami batas Singleton serta gunakan Repository untuk memisahkan akses data dari aturan aplikasi.'],
            ['Masalah yang diselesaikan pattern', 'Kenali masalah desain terlebih dahulu dan hindari memakai pattern hanya karena populer.'],
            ['Prinsip SOLID sebagai fondasi', 'Gunakan tanggung jawab tunggal dan abstraksi untuk menyiapkan desain yang mudah dirawat.'],
            ['Dependency injection', 'Pisahkan pembuatan dependency dari penggunaan agar komponen dapat diuji.'],
            ['Factory Method', 'Pusatkan pembuatan object ketika jenis implementasi ditentukan saat program berjalan.'],
            ['Abstract Factory', 'Buat keluarga object yang saling cocok melalui satu kontrak pembuatan.'],
            ['Builder', 'Bangun object kompleks langkah demi langkah dengan konfigurasi yang terbaca.'],
            ['Adapter', 'Sesuaikan antarmuka komponen lama dengan kontrak yang dibutuhkan tanpa mengubahnya.'],
            ['Decorator', 'Tambahkan tanggung jawab secara bertumpuk tanpa membuat banyak subclass.'],
            ['Facade', 'Sediakan antarmuka sederhana di depan beberapa layanan yang kompleks.'],
            ['Strategy', 'Ganti algoritma dengan implementasi yang memenuhi kontrak yang sama.'],
            ['Observer dan event', 'Sebarkan perubahan keadaan kepada pelanggan tanpa mengikat pengirim ke penerima konkret.'],
            ['Repository', 'Pisahkan aturan akses dan persistensi data dari logika aplikasi.'],
            ['Service layer', 'Tempatkan alur kasus penggunaan di service agar controller tetap tipis.'],
            ['Command', 'Representasikan tindakan sebagai object untuk antrean, log, atau pembatalan operasi.'],
            ['Template Method', 'Tetapkan alur umum dan biarkan langkah tertentu diimplementasikan oleh turunan.'],
            ['Chain of Responsibility', 'Teruskan permintaan melalui handler yang bertanggung jawab pada bagian berbeda.'],
            ['Memilih pattern dan trade-off', 'Bandingkan keterbacaan, jumlah abstraksi, fleksibilitas, dan biaya pemeliharaan.'],
            ['Pengujian arsitektur', 'Uji perilaku pola melalui kontrak dan mock dependency yang relevan.'],
            ['Proyek akhir design patterns', 'Rancang aplikasi PHP kecil dengan repository, service, dependency injection, dan satu pattern perilaku.'],
        ],
        'css-dasar' => [
            ['Menghubungkan CSS dan selector', 'Pilih elemen dengan selector dan pisahkan aturan tampilan dari struktur HTML.'],
            ['Warna, tipografi, dan cascade', 'Pahami pewarisan, spesifisitas, urutan aturan, warna, dan keterbacaan teks.'],
            ['Box model dan spacing', 'Atur content, padding, border, margin, dan box-sizing untuk ukuran yang terprediksi.'],
            ['Flexbox untuk komponen', 'Sejajarkan komponen satu dimensi menggunakan sumbu utama, silang, dan gap.'],
            ['CSS Grid dan media query', 'Bangun kisi dua dimensi dan ubah jumlah kolom sesuai lebar layar.'],
            ['Kerapian dan aksesibilitas CSS', 'Pertahankan fokus terlihat, kontras memadai, dan selector mudah dirawat.'],
            ['Selector kombinator dan atribut', 'Targetkan hubungan elemen dan atribut tanpa menambah class yang tidak diperlukan.'],
            ['Spesifisitas dan cascade lanjutan', 'Hitung prioritas aturan dan atasi konflik tanpa mengandalkan !important.'],
            ['Satuan CSS dan ukuran responsif', 'Gunakan rem, %, vw, dan fungsi min/max/clamp sesuai konteks.'],
            ['Custom properties', 'Simpan warna dan ukuran berulang dalam variabel CSS yang konsisten.'],
            ['Position dan stacking context', 'Atur posisi elemen serta pahami z-index dan konteks tumpukannya.'],
            ['Flexbox untuk layout halaman', 'Bangun header, navigasi, dan susunan kartu yang beradaptasi terhadap ruang.'],
            ['Grid untuk dashboard', 'Gunakan grid-template, fr, minmax, dan gap untuk kisi yang fleksibel.'],
            ['Breakpoint mobile-first', 'Mulai dari layar kecil lalu tambahkan aturan untuk layar yang lebih lebar.'],
            ['Gambar dan media responsif', 'Pastikan gambar mempertahankan rasio dan memenuhi ruang tanpa meluber.'],
            ['Pseudo-class dan pseudo-element', 'Buat state hover, focus, dan dekorasi tanpa markup tambahan yang tidak perlu.'],
            ['Transisi dan animasi yang nyaman', 'Gunakan animasi singkat serta hormati preferensi reduced motion.'],
            ['Komponen form yang konsisten', 'Atur label, input, pesan validasi, dan state fokus agar mudah digunakan.'],
            ['Debugging layout dengan DevTools', 'Periksa box model, aturan aktif, ukuran viewport, dan overflow.'],
            ['Proyek akhir portofolio responsif', 'Bangun halaman portofolio lengkap dan uji pada mobile, tablet, serta desktop.'],
        ],
        'javascript-dasar' => [
            ['Variabel, tipe data, dan operator', 'Gunakan const dan let serta pahami nilai string, number, boolean, null, dan undefined.'],
            ['Percabangan dan kondisi', 'Gunakan if/else dan perbandingan ketat untuk menentukan alur program.'],
            ['Perulangan dan array', 'Telusuri kumpulan data dan olah nilai dengan perulangan yang tepat.'],
            ['Object dan fungsi', 'Kelompokkan data object dan pisahkan proses ke fungsi dengan parameter serta return.'],
            ['DOM dan event', 'Pilih elemen, ubah textContent, dan tanggapi aksi pengguna melalui event listener.'],
            ['Validasi form dan proyek interaktif', 'Validasi input di browser dan tampilkan umpan balik yang dapat dipahami.'],
            ['Scope dan hoisting', 'Pahami ruang lingkup block, function, dan alasan memilih let atau const.'],
            ['String dan template literal', 'Format teks dan sisipkan nilai dengan template literal secara jelas.'],
            ['Method array map dan filter', 'Transformasikan atau saring data tanpa mengubah array sumber secara tidak sengaja.'],
            ['Method array reduce dan find', 'Ringkas data menjadi satu hasil dan cari elemen sesuai kondisi.'],
            ['Destructuring dan spread', 'Ambil bagian object/array serta salin atau gabungkan data dengan sintaks modern.'],
            ['Callback dan fungsi tingkat tinggi', 'Kirim fungsi sebagai argumen untuk mengatur perilaku proses lain.'],
            ['Promise dan alur asynchronous', 'Pahami state promise dan tangani hasil berhasil maupun gagal.'],
            ['Fetch API dan JSON', 'Ambil data HTTP, periksa respons, lalu ubah JSON menjadi data JavaScript.'],
            ['Penanganan error', 'Gunakan try/catch dan tampilkan pesan gagal tanpa menghentikan seluruh antarmuka.'],
            ['Event delegation', 'Tangani aksi banyak elemen melalui satu listener pada induk yang stabil.'],
            ['Local storage', 'Simpan preferensi kecil di browser dan validasi data saat membacanya kembali.'],
            ['Modul JavaScript', 'Pisahkan kode dengan export/import agar fungsi mudah digunakan kembali.'],
            ['Debugging dan pengujian manual', 'Gunakan console dan DevTools untuk melacak nilai serta menguji skenario batas.'],
            ['Proyek akhir aplikasi tugas', 'Bangun aplikasi tugas dengan tambah, tandai, hapus, filter, dan penyimpanan lokal.'],
        ],
        'php-native-mysql' => [
            ['Sintaks PHP dan output HTML', 'Pahami tag PHP, variabel, echo, dan cara menghasilkan HTML secara aman.'],
            ['Kondisi, perulangan, dan array', 'Gunakan alur kontrol dan array untuk mengolah kumpulan data.'],
            ['Request dan validasi form', 'Baca GET/POST di server, normalisasi input, dan validasi data pengguna.'],
            ['Fungsi dan pemisahan file', 'Buat fungsi yang dapat digunakan kembali dan pisahkan konfigurasi dari tampilan.'],
            ['Koneksi PDO dan prepared statement', 'Hubungkan PHP ke database dan pisahkan parameter dari query SQL.'],
            ['CRUD dan proyek akhir', 'Buat operasi create, read, update, delete dengan validasi dan penanganan hasil.'],
            ['Tipe data dan type declaration', 'Gunakan type hint, return type, dan strict_types agar kontrak fungsi jelas.'],
            ['Session dan autentikasi dasar', 'Kelola session dengan aman dan pahami alur masuk serta keluar.'],
            ['Password hashing', 'Simpan password dengan password_hash dan verifikasi memakai password_verify.'],
            ['Validasi dan escaping output', 'Validasi nilai sesuai tujuan dan escape teks saat dirender ke HTML.'],
            ['Relasi tabel dan foreign key', 'Hubungkan data dengan primary key, foreign key, dan aturan integritas.'],
            ['SELECT, filter, dan pagination', 'Ambil data dengan kondisi, urutan, dan batas halaman yang aman.'],
            ['INSERT dan validasi duplikasi', 'Simpan data baru dengan prepared statement dan tangani constraint unik.'],
            ['UPDATE dan DELETE aman', 'Validasi identifier dan konfirmasi perubahan data sebelum eksekusi.'],
            ['Transaksi database', 'Kelompokkan operasi terkait agar berhasil seluruhnya atau dibatalkan seluruhnya.'],
            ['Upload file dengan validasi', 'Periksa error, ukuran, MIME, nama acak, dan lokasi penyimpanan file.'],
            ['CSRF dan keamanan form', 'Gunakan token CSRF untuk melindungi aksi yang mengubah data.'],
            ['Pola Post/Redirect/Get', 'Redirect setelah submit agar refresh tidak mengulang aksi formulir.'],
            ['Logging dan penanganan exception', 'Catat detail kesalahan di log dan tampilkan pesan aman untuk pengguna.'],
            ['Proyek akhir aplikasi CRUD', 'Bangun aplikasi data lengkap dengan PDO, autentikasi, validasi, CSRF, dan tampilan responsif.'],
        ],
    ];

    private const EXTRA_TOPICS = [
        'html-dasar' => [
            ['Gambar responsif dan figure', 'Buat gambar yang memiliki teks alternatif, keterangan, dan ukuran yang tidak merusak tata letak.'],
            ['Audio dan video', 'Gunakan elemen media bawaan browser dengan kontrol dan teks alternatif yang sesuai.'],
            ['Details dan summary', 'Buat bagian informasi yang dapat dibuka-tutup tanpa JavaScript.'],
            ['Dialog dan dialog form', 'Kenali pola dialog native dan label yang menjelaskan tujuan dialog.'],
            ['Form multi-langkah', 'Kelompokkan data formulir dengan fieldset, legend, dan urutan input yang jelas.'],
            ['Input tanggal dan angka', 'Pilih tipe input yang memberi keyboard dan validasi bawaan sesuai data.'],
            ['Autocomplete dan petunjuk input', 'Bantu pengguna mengisi formulir dengan autocomplete dan placeholder yang tepat.'],
            ['Aksesibilitas gambar dan ikon', 'Pastikan gambar informatif memiliki alt dan ikon dekoratif tidak mengganggu pembaca layar.'],
            ['Landmark dan urutan heading', 'Susun landmark dan heading agar halaman mudah dijelajahi dengan teknologi bantu.'],
            ['Tautan unduh dan kontak', 'Buat tautan unduh, email, dan telepon dengan tujuan yang mudah dikenali.'],
            ['Metadata berbagi halaman', 'Tambahkan judul, deskripsi, dan metadata dasar agar halaman mudah dikenali saat dibagikan.'],
            ['Favicon dan identitas halaman', 'Hubungkan ikon situs dan pastikan identitas dokumen konsisten.'],
            ['Data tabel yang kompleks', 'Gunakan caption dan scope pada tabel agar hubungan header dan isi tidak membingungkan.'],
            ['Komponen kartu semantik', 'Susun kartu artikel dan proyek dengan elemen yang mencerminkan makna kontennya.'],
            ['Pola navigasi responsif', 'Buat navigasi yang tetap dapat dipahami tanpa bergantung pada ikon saja.'],
            ['Struktur folder website', 'Pisahkan halaman, aset gambar, stylesheet, dan skrip dalam folder yang mudah ditemukan.'],
            ['Halaman beranda mini', 'Mulai situs mini dengan header, navigasi, hero, dan ajakan bertindak.'],
            ['Bagian tentang dan layanan', 'Tambahkan bagian profil dan layanan menggunakan section dengan heading yang sesuai.'],
            ['Daftar proyek portofolio', 'Tampilkan beberapa proyek dengan judul, ringkasan, gambar, dan tautan.'],
            ['Formulir kontak mini', 'Buat form kontak lengkap dengan label, tipe input, required, dan pesan bantuan.'],
            ['Footer dan navigasi akhir', 'Lengkapi footer dengan informasi pemilik dan tautan yang berguna.'],
            ['Halaman detail proyek', 'Buat struktur detail yang menjelaskan masalah, proses, dan hasil sebuah proyek.'],
            ['Halaman profil responsif', 'Gabungkan biodata, foto, keterampilan, dan tautan dalam hierarki yang mudah dibaca.'],
            ['Halaman daftar artikel', 'Bangun daftar artikel semantik dengan ringkasan dan tanggal publikasi.'],
            ['Validasi HTML proyek', 'Periksa nesting, atribut, heading, dan struktur dokumen pada situs mini.'],
            ['Uji keyboard dan pembaca layar', 'Telusuri halaman dengan keyboard dan pastikan label serta landmark bermakna.'],
            ['Audit konten dan tautan', 'Pastikan teks tautan jelas, gambar memiliki alt, dan tidak ada tautan kosong.'],
            ['Rakit situs mini: kerangka', 'Satukan beranda, tentang, proyek, kontak, dan footer dengan anchor navigasi.'],
            ['Rakit situs mini: halaman proyek', 'Lengkapi kartu proyek dan detailnya dengan markup semantik dan tautan yang berfungsi.'],
            ['Proyek akhir: website portofolio HTML', 'Selesaikan website mini multi-bagian yang hanya memakai HTML semantik dan dapat dinavigasi.'],
        ],
        'css-dasar' => [
            ['Reset dan box sizing', 'Gunakan aturan dasar agar ukuran elemen konsisten dan mudah diprediksi.'],
            ['Warna dengan custom properties', 'Buat palet warna sebagai token supaya tema situs mudah diubah.'],
            ['Tipografi skala responsif', 'Terapkan skala ukuran teks yang nyaman pada layar kecil maupun lebar.'],
            ['Background dan gradient', 'Gunakan warna latar, gambar, dan gradient tanpa mengurangi keterbacaan.'],
            ['Border, radius, dan bayangan', 'Bentuk komponen dengan garis, sudut, dan bayangan yang konsisten.'],
            ['Pola tombol dan state interaksi', 'Rancang tombol dengan hover, active, focus-visible, dan disabled yang jelas.'],
            ['Kartu konten yang konsisten', 'Buat kartu dengan spacing, batas, dan tata letak yang dapat digunakan ulang.'],
            ['Header dan navigasi sticky', 'Tata header dan navigasi sambil mempertimbangkan ruang konten serta fokus keyboard.'],
            ['Hero dan call to action', 'Susun area pembuka dengan judul, teks, tombol, dan visual yang seimbang.'],
            ['Grid portofolio', 'Atur kartu proyek dalam grid yang menyesuaikan jumlah kolom secara otomatis.'],
            ['Formulir yang mudah dibaca', 'Buat label, field, pesan kesalahan, dan fokus dengan jarak yang konsisten.'],
            ['Layout dua kolom yang adaptif', 'Gabungkan kolom konten dan sidebar tanpa menyebabkan overflow di mobile.'],
            ['Container dan lebar konten', 'Batasi lebar baris teks agar nyaman dibaca di layar besar.'],
            ['Aspect ratio dan object fit', 'Jaga proporsi gambar kartu tanpa merusak tampilan saat crop.'],
            ['Transisi komponen', 'Tambahkan transisi singkat yang membantu pengguna memahami perubahan state.'],
            ['Reduced motion dan kontras', 'Hormati preferensi gerakan dan pastikan teks memiliki kontras yang cukup.'],
            ['Token tema website', 'Satukan warna, radius, jarak, dan bayangan ke dalam variabel desain.'],
            ['Komponen hero website', 'Bangun hero portofolio responsif dengan judul, tombol, dan ilustrasi sederhana.'],
            ['Komponen kartu proyek', 'Samakan ukuran gambar, judul, deskripsi, dan tautan pada setiap kartu proyek.'],
            ['Navigasi mobile', 'Ubah susunan navigasi pada layar sempit tanpa menyembunyikan tujuan penting.'],
            ['Bagian tentang dan skill', 'Atur profil dan daftar keterampilan dengan hierarki visual yang jelas.'],
            ['Form kontak dan fokus', 'Tata form kontak dan tampilkan fokus keyboard dengan jelas.'],
            ['Footer multi-kolom', 'Buat footer yang berubah menjadi satu kolom pada layar kecil.'],
            ['Mode warna alternatif', 'Gunakan custom properties untuk tema terang dan gelap yang tetap terbaca.'],
            ['Kerapian stylesheet', 'Kelompokkan aturan per komponen dan hapus deklarasi yang tidak lagi digunakan.'],
            ['Debugging dengan DevTools', 'Temukan overflow, konflik cascade, dan ukuran elemen melalui panel layout browser.'],
            ['Uji responsif lintas ukuran', 'Uji layout pada lebar ponsel, tablet, dan desktop serta perbaiki titik patah.'],
            ['Rakit website mini: sistem desain', 'Tentukan token warna, tipografi, lebar konten, dan spacing untuk satu website mini.'],
            ['Rakit website mini: seluruh halaman', 'Gabungkan header, hero, tentang, proyek, kontak, dan footer dengan gaya konsisten.'],
            ['Proyek akhir: website portofolio CSS', 'Selesaikan gaya website portofolio responsif dan pastikan hasilnya nyaman dibaca.'],
        ],
        'javascript-dasar' => [
            ['Nilai truthy dan falsy', 'Pahami nilai yang dianggap benar atau salah dan hindari kondisi yang ambigu.'],
            ['Operator nullish dan optional chaining', 'Akses data opsional dengan aman dan gunakan fallback hanya saat nilai benar-benar kosong.'],
            ['Validasi angka dan NaN', 'Periksa hasil konversi angka sebelum menghitung atau menampilkan data.'],
            ['Sorting dan pengurutan data', 'Urutkan daftar tanpa mengubah data awal secara tidak sengaja.'],
            ['Immutable update object', 'Perbarui object dan array dengan salinan agar perubahan mudah ditelusuri.'],
            ['Membuat komponen DOM', 'Bangun elemen memakai createElement dan textContent untuk menampilkan teks secara aman.'],
            ['Form submit dan pesan status', 'Tangani submit form, cegah reload yang tidak diperlukan, dan beri umpan balik.'],
            ['Render daftar dari data', 'Ubah array object menjadi daftar elemen yang konsisten di halaman.'],
            ['Tambah, ubah, dan hapus data', 'Hubungkan tombol dengan aksi CRUD sederhana pada kumpulan data di browser.'],
            ['Filter dan pencarian langsung', 'Saring daftar berdasarkan teks input tanpa kehilangan data sumber.'],
            ['Checkbox dan status selesai', 'Ubah status item dan perbarui tampilan sesuai keadaan terbaru.'],
            ['Local storage dengan JSON', 'Simpan dan baca data kecil dengan penanganan JSON yang mungkin rusak.'],
            ['Debounce pencarian', 'Kurangi pekerjaan berulang saat pengguna mengetik dengan jeda yang terukur.'],
            ['Fetch data dan loading state', 'Tampilkan kondisi memuat, berhasil, dan gagal saat mengambil data.'],
            ['Promise.all dan beberapa sumber', 'Jalankan permintaan independen bersama dan tangani kegagalan secara jelas.'],
            ['AbortController dan pembatalan', 'Batalkan permintaan lama agar hasil pencarian tidak saling menimpa.'],
            ['URLSearchParams dan filter URL', 'Simpan pilihan filter di URL agar tampilan bisa dibagikan dan dimuat ulang.'],
            ['Keyboard dan aksesibilitas event', 'Sediakan aksi yang bekerja dengan keyboard dan status yang dapat diumumkan.'],
            ['Pemisahan fungsi aplikasi', 'Pisahkan pengambilan data, pembaruan tampilan, dan aksi pengguna ke fungsi terarah.'],
            ['Pengujian fungsi dan kasus batas', 'Uji fungsi pada input normal, kosong, salah, dan batas nilai.'],
            ['Rancang data website mini', 'Tentukan bentuk data proyek, menu, dan kontak sebelum membangun antarmuka.'],
            ['Bangun DOM website mini', 'Render header, hero, navigasi, kartu proyek, dan footer dari JavaScript.'],
            ['Navigasi dan scroll ke bagian', 'Hubungkan menu dengan bagian halaman dan tandai pilihan aktif.'],
            ['Tambah proyek dari form', 'Ambil nilai form, validasi, lalu tambahkan kartu proyek baru ke tampilan.'],
            ['Pencarian dan filter proyek', 'Saring kartu proyek berdasarkan kata kunci atau kategori.'],
            ['Simpan proyek di browser', 'Persistenkan proyek dan preferensi tampilan menggunakan localStorage.'],
            ['Tema dan preferensi pengguna', 'Buat toggle tema yang tersimpan dan tetap berfungsi dengan keyboard.'],
            ['Rapikan aplikasi dan error state', 'Tampilkan pesan kosong/error dan pisahkan fungsi agar mudah dirawat.'],
            ['Rakit website mini: uji interaksi', 'Uji menu, form, pencarian, tema, dan data tersimpan dengan beberapa skenario.'],
            ['Proyek akhir: website portofolio JavaScript', 'Selesaikan website mini interaktif dengan proyek dinamis, filter, form, dan penyimpanan lokal.'],
        ],
        'php-native-mysql' => [
            ['Konfigurasi proyek dan environment', 'Pisahkan konfigurasi lingkungan dari source code dan jangan tampilkan rahasia ke halaman.'],
            ['Routing halaman sederhana', 'Tentukan halaman berdasarkan route yang diizinkan dan berikan respons 404 yang sesuai.'],
            ['Template PHP dan layout bersama', 'Pisahkan header, navigasi, konten, dan footer agar layout konsisten.'],
            ['Flash message dan redirect', 'Tampilkan pesan satu kali setelah aksi dan gunakan redirect setelah request POST.'],
            ['Cookie dan preferensi pengguna', 'Pahami masa berlaku cookie serta validasi nilai sebelum digunakan.'],
            ['Autentikasi dan regenerasi session', 'Regenerasi ID session saat login dan lindungi halaman yang membutuhkan akun.'],
            ['Otorisasi berdasarkan peran', 'Periksa izin di server sebelum menampilkan atau memproses aksi administratif.'],
            ['Paginasi data aman', 'Batasi ukuran halaman dan validasi nomor halaman sebelum query.'],
            ['Pencarian dengan prepared statement', 'Gunakan parameter PDO dan wildcard yang dibentuk dengan benar untuk pencarian.'],
            ['Relasi one-to-many', 'Rancang hubungan kategori dan proyek dengan foreign key yang konsisten.'],
            ['Skema tabel proyek', 'Buat tabel proyek dengan primary key, judul, deskripsi, URL, dan waktu dibuat.'],
            ['Membaca data dan escaping output', 'Tampilkan data database dengan htmlspecialchars untuk mencegah markup tak tepercaya.'],
            ['Menambah data dan validasi server', 'Validasi field wajib dan panjang teks sebelum INSERT.'],
            ['Mengedit data dengan ownership', 'Pastikan data yang diubah memang milik pengguna yang sedang masuk.'],
            ['Menghapus data dengan konfirmasi', 'Gunakan aksi POST dan pemeriksaan izin untuk penghapusan.'],
            ['Transaksi dan integritas data', 'Gunakan transaksi ketika beberapa perubahan data harus berhasil bersama.'],
            ['CSRF untuk semua aksi perubahan', 'Validasi token pada aksi tambah, ubah, dan hapus.'],
            ['Upload gambar dengan aman', 'Validasi MIME dan ukuran file serta gunakan nama acak di penyimpanan.'],
            ['Penanganan error dan logging', 'Catat exception di server dan berikan pesan umum tanpa membocorkan detail internal.'],
            ['Pengujian alur CRUD', 'Uji input valid, input tidak lengkap, akses tanpa izin, dan hasil kosong.'],
            ['Rancang mini website PHP', 'Rencanakan halaman beranda, daftar proyek, detail, kontak, dan penyimpanan data.'],
            ['Buat layout dan routing mini', 'Satukan header, navigasi, footer, dan route yang diizinkan untuk website mini.'],
            ['Simpan dan tampilkan proyek', 'Buat form proyek, simpan menggunakan PDO, lalu tampilkan daftar hasilnya.'],
            ['Halaman detail dan pencarian', 'Tampilkan detail proyek dan cari berdasarkan kata kunci dengan query aman.'],
            ['Form kontak dan validasi server', 'Validasi nama, email, dan pesan pada server lalu berikan flash message.'],
            ['Login admin mini', 'Batasi pengelolaan proyek untuk akun yang terautentikasi dan memiliki peran sesuai.'],
            ['Dashboard kelola proyek', 'Bangun halaman admin untuk menambah, mengubah, dan menghapus proyek.'],
            ['Perlindungan dan audit keamanan', 'Periksa CSRF, escaping, prepared statement, session, dan izin pada setiap endpoint.'],
            ['Uji dan rapikan website mini', 'Uji semua alur dengan database lokal, data kosong, dan input yang tidak valid.'],
            ['Proyek akhir: website portofolio PHP', 'Selesaikan website mini berbasis PHP dan MySQL dengan halaman publik serta CRUD admin yang aman.'],
        ],
        'python-dasar' => [
            ['Fungsi kecil dan docstring', 'Buat fungsi satu tujuan dengan nama jelas, nilai kembali, dan dokumentasi singkat.'],
            ['Parameter default dan keyword', 'Gunakan parameter default dan pemanggilan bernama untuk membuat fungsi mudah digunakan.'],
            ['Argumen variabel dan unpacking', 'Pahami *args, **kwargs, dan unpacking secara hati-hati untuk fungsi fleksibel.'],
            ['Lambda dan sorting key', 'Gunakan fungsi kecil untuk mengurutkan data berdasarkan field tertentu.'],
            ['Iterator dan generator', 'Hasilkan data bertahap tanpa membuat seluruh kumpulan data sekaligus.'],
            ['Modul dan import', 'Pisahkan fungsi ke modul dan impor hanya bagian yang diperlukan.'],
            ['Pathlib dan berkas', 'Bangun path lintas sistem operasi dan baca atau tulis file secara terkontrol.'],
            ['CSV untuk data tabel', 'Baca dan tulis data tabular dengan modul csv serta header yang jelas.'],
            ['JSON dan validasi data', 'Simpan data terstruktur dan validasi bentuk data setelah membacanya.'],
            ['Tanggal dan waktu', 'Parse dan format tanggal dengan datetime serta tangani format yang tidak valid.'],
            ['Enum dan konstanta', 'Gunakan konstanta dan Enum untuk pilihan nilai yang sudah ditentukan.'],
            ['Dataclass untuk model data', 'Representasikan record sederhana dengan dataclass dan tipe field yang jelas.'],
            ['Pewarisan dan komposisi', 'Pilih komposisi atau pewarisan sesuai hubungan perilaku pada model.'],
            ['Protocol dan type hint', 'Tambahkan type hint agar kontrak fungsi dan object lebih mudah dipahami.'],
            ['Unit test dengan unittest', 'Tulis tes fungsi untuk kasus normal, batas, dan masukan yang tidak valid.'],
            ['Mock dan isolasi pengujian', 'Pisahkan pengujian dari file atau sumber eksternal dengan dependency yang dapat diganti.'],
            ['Logging dan konfigurasi', 'Gunakan logging untuk mencatat kejadian tanpa mencampur log dengan output pengguna.'],
            ['Argparse untuk perintah', 'Buat command line sederhana dengan argumen yang tervalidasi.'],
            ['Refactoring dan struktur package', 'Pisahkan model, fungsi, dan entry point agar aplikasi kecil mudah dirawat.'],
            ['Pengujian proyek lengkap', 'Uji alur aplikasi dari input, validasi, perubahan data, hingga penyimpanan.'],
            ['Rancang konten website mini', 'Susun data profil, navigasi, proyek, dan kontak sebagai model Python.'],
            ['Buat data dan fungsi halaman', 'Bangun fungsi yang mengubah data profil dan proyek menjadi struktur HTML.'],
            ['Render HTML dari Python', 'Hasilkan kerangka website mini sebagai string HTML yang dapat dibaca dan diuji.'],
            ['Template dan escaping nilai', 'Pisahkan konten dari struktur dan escape nilai dinamis sebelum menjadi HTML.'],
            ['Simpan proyek ke JSON', 'Tambahkan fungsi baca/tulis data portofolio dengan penanganan file yang aman.'],
            ['Validasi dan pencarian proyek', 'Validasi data proyek serta sediakan pencarian berdasarkan judul atau kategori.'],
            ['Pisahkan modul website mini', 'Pisahkan model, penyimpanan, pembentuk halaman, dan program utama ke modul.'],
            ['Uji halaman dan data', 'Uji hasil HTML, data kosong, teks khusus, dan kondisi file yang tidak tersedia.'],
            ['Rakit website mini Python', 'Gabungkan profil, proyek, navigasi, kontak, dan data JSON menjadi satu generator website.'],
            ['Proyek akhir: website portofolio Python', 'Selesaikan generator website mini yang menghasilkan HTML portofolio dari data tervalidasi dan dapat diuji.'],
        ],
    ];

    public static function seed(\PDO $pdo): void
    {
        $courses = $pdo->query("SELECT id, slug, title, language FROM courses WHERE is_active=1")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($courses as $course) {
            self::seedCourse($pdo, $course);
        }
    }

    private static function seedCourse(\PDO $pdo, array $course): void
    {
        $targetSlugs = ['html-dasar', 'css-dasar', 'javascript-dasar', 'php-native-mysql', 'python-dasar'];
        $targetCount = in_array($course['slug'], $targetSlugs, true) ? 50 : 20;
        $curriculum = self::curriculumFor($course);
        if (count($curriculum) !== $targetCount) {
            throw new \RuntimeException("Kurikulum {$course['title']} harus memiliki tepat {$targetCount} tahap.");
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id FROM courses WHERE id=? AND is_active=1 FOR UPDATE');
            $stmt->execute([$course['id']]);
            if (!$stmt->fetchColumn()) {
                $pdo->commit();
                return;
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id=l.module_id WHERE m.course_id=? AND m.is_active=1 AND l.is_active=1');
            $stmt->execute([$course['id']]);
            $existingCount = (int)$stmt->fetchColumn();
            if ($existingCount >= $targetCount) {
                $pdo->commit();
                return;
            }

            if ($targetCount === 50) {
                $stmt = $pdo->prepare('SELECT l.id FROM lessons l JOIN modules m ON m.id=l.module_id WHERE m.course_id=? AND m.is_active=1 AND l.is_active=1 ORDER BY m.sort_order, m.id, l.sort_order, l.id');
                $stmt->execute([$course['id']]);
                $existingLessonIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $updateLesson = $pdo->prepare('UPDATE lessons SET title=?, content_theory=?, code_example=?, code_language=?, code_explanation=?, exercise=?, project_task=? WHERE id=?');
                for ($stageIndex = 0; $stageIndex < $existingCount; $stageIndex++) {
                    $lesson = $curriculum[$stageIndex];
                    $updateLesson->execute([
                        $lesson[0],
                        $lesson[1],
                        $lesson[2],
                        $course['language'] ?: 'text',
                        $lesson[3],
                        $lesson[4],
                        $lesson[5],
                        $existingLessonIds[$stageIndex],
                    ]);
                }
            }

            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM modules WHERE course_id=?');
            $stmt->execute([$course['id']]);
            $moduleOrder = (int)$stmt->fetchColumn();
            $moduleId = null;
            $addedInModule = 5;
            $insertModule = $pdo->prepare('INSERT INTO modules (course_id, title, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
            $insertLesson = $pdo->prepare('INSERT INTO lessons (module_id, title, slug, content_theory, code_example, code_language, code_explanation, exercise, project_task, duration_minutes, xp_reward, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 15, 10, ?, 1)');

            for ($stageIndex = $existingCount; $stageIndex < $targetCount; $stageIndex++) {
                $stage = $stageIndex + 1;
                if ($moduleId === null || $addedInModule >= 5) {
                    $moduleOrder++;
                    $rangeStart = $stage;
                    $rangeEnd = min($targetCount, $stage + 4);
                    $moduleTitle = $targetCount === 50
                        ? "Tahap {$rangeStart}–{$rangeEnd}"
                        : "Pendalaman tahap {$rangeStart}–{$rangeEnd}";
                    $insertModule->execute([
                        $course['id'],
                        $moduleTitle,
                        "Rangkaian latihan {$course['title']} tahap {$rangeStart} sampai {$rangeEnd}; setiap tahap diketik, dijalankan, dan dikembangkan menjadi proyek akhir.",
                        $moduleOrder,
                    ]);
                    $moduleId = (int)$pdo->lastInsertId();
                    $addedInModule = 0;
                }

                [$lessonTitle, $theory, $codeExample, $codeExplanation, $exercise, $project] = $curriculum[$stageIndex];
                $slug = sprintf('tahap-%02d-%s', $stage, self::slugify($lessonTitle));
                $insertLesson->execute([
                    $moduleId,
                    $lessonTitle,
                    $slug,
                    $theory,
                    $codeExample,
                    $course['language'] ?: 'text',
                    $codeExplanation,
                    $exercise,
                    $project,
                    $addedInModule + 1,
                ]);
                $addedInModule++;
            }
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id=l.module_id WHERE m.course_id=? AND m.is_active=1 AND l.is_active=1');
            $stmt->execute([$course['id']]);
            if ((int)$stmt->fetchColumn() !== $targetCount) {
                throw new \RuntimeException("Kurikulum {$course['title']} tidak berhasil dilengkapi menjadi {$targetCount} tahap.");
            }
            $stmt = $pdo->prepare("UPDATE enrollments SET status='active', completed_at=NULL WHERE course_id=? AND status='completed'");
            $stmt->execute([$course['id']]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function curriculumFor(array $course): array
    {
        $slug = (string)$course['slug'];
        $language = strtolower((string)($course['language'] ?? ''));
        $targetSlugs = ['html-dasar', 'css-dasar', 'javascript-dasar', 'php-native-mysql', 'python-dasar'];
        if (!in_array($slug, $targetSlugs, true)) {
            $outline = self::OUTLINES[$slug] ?? self::genericOutline($course);
            return array_map(
                static function (array $lesson): array {
                    [$title, $objective] = $lesson;
                    $theory = "{$objective}\n\nPelajari konsep ini dengan menghubungkannya pada materi sebelumnya. Catat istilah penting, kerjakan latihan secara bertahap, lalu amati hasilnya. Jika hasil tidak sesuai, telusuri kembali langkah dan alasan di balik setiap keputusan.";
                    $exercise = "Buat latihan kecil tentang {$title}. Tulis langkah pengerjaan, coba setidaknya satu kasus normal dan satu kasus batas, lalu jelaskan hasilnya dengan kata-katamu sendiri.";
                    $project = "Tambahkan {$title} ke proyek akhir. Pastikan hasilnya berfungsi, mudah dipahami, dan tidak merusak bagian yang sudah dibuat.";
                    return [$title, $theory, null, null, $exercise, $project];
                },
                $outline
            );
        }
        $curriculum = [];
        if ($slug === 'python-dasar') {
            foreach (self::PYTHON_CURRICULUM as $lesson) {
                $curriculum[] = [
                    $lesson[0],
                    $lesson[1],
                    str_replace('\n', "\n", $lesson[2]),
                    $lesson[3],
                    $lesson[4],
                    $lesson[5],
                ];
            }
        } else {
            $outline = self::OUTLINES[$slug] ?? self::genericOutline($course);
            foreach ($outline as $stageIndex => [$title, $objective]) {
                $curriculum[] = self::buildLesson($language, $stageIndex + 1, $title, $objective);
            }
        }

        foreach (self::EXTRA_TOPICS[$slug] ?? [] as [$title, $objective]) {
            $curriculum[] = self::buildLesson($language, count($curriculum) + 1, $title, $objective);
        }
        if (count($curriculum) !== 50) {
            throw new \RuntimeException("Kurikulum {$course['title']} belum memiliki 50 materi.");
        }
        return $curriculum;
    }

    private static function buildLesson(string $language, int $stage, string $title, string $objective): array
    {
        $lesson = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $codeExample = match (true) {
            $language === 'html' => "<section class=\"tahap-{$stage}\">\n  <h2>{$lesson}</h2>\n  <p>Bagian ini menjelaskan {$lesson} pada website mini.</p>\n  <a href=\"#latihan\">Lihat bagian latihan</a>\n</section>",
            $language === 'css' => ".portfolio-card {\n  padding: 1.25rem;\n  border: 1px solid #d8e3dc;\n  border-radius: 1rem;\n  background: #ffffff;\n  color: #183b2c;\n}\n\n.portfolio-card h2 {\n  margin-top: 0;\n  font-size: clamp(1.25rem, 3vw, 2rem);\n}",
            $language === 'javascript' => "const tahap = {$stage};\nconst judul = '{$lesson}';\nconst tujuan = '{$objective}';\n\nconsole.log(`Tahap \${tahap}: \${judul}`);\nconsole.log(tujuan);",
            $language === 'php' => "<?php\n\$tahap = {$stage};\n\$judul = '{$lesson}';\n\$tujuan = '{$objective}';\n\necho '<article class=\"project-card\">';\necho '<h2>' . htmlspecialchars(\$judul, ENT_QUOTES, 'UTF-8') . '</h2>';\necho '<p>' . htmlspecialchars(\$tujuan, ENT_QUOTES, 'UTF-8') . '</p>';\necho '</article>';",
            default => "judul = \"{$lesson}\"\ntujuan = \"{$objective}\"\nprint(f\"Tahap {$stage}: {judul}\")\nprint(tujuan)",
        };
        if ($stage === 50) {
            $codeExample = match ($language) {
                'html' => '<header><nav><a href="#profil">Profil</a> <a href="#proyek">Proyek</a> <a href="#kontak">Kontak</a></nav></header>' . "\n"
                    . '<main><section class="hero"><h1>Halo, saya Rani</h1><p>Belajar membuat website dari nol.</p><a href="#proyek">Lihat proyek</a></section>' . "\n"
                    . '<section id="profil"><h2>Tentang saya</h2><p>Saya sedang belajar web development.</p></section>' . "\n"
                    . '<section id="proyek"><h2>Proyek pilihan</h2><article><h3>Website portofolio</h3><p>Halaman mini yang dibuat bertahap.</p></article></section>' . "\n"
                    . '<section id="kontak"><h2>Kontak</h2><a href="mailto:rani@example.com">Kirim email</a></section></main>' . "\n"
                    . '<footer><p>&copy; 2026 Rani</p></footer>',
                'css' => ':root { --hijau: #176b52; --jarak: 1.25rem; }' . "\n"
                    . '* { box-sizing: border-box; }' . "\n"
                    . 'body { margin: 0; font: 1rem/1.6 system-ui, sans-serif; color: #183b2c; }' . "\n"
                    . 'header, footer { padding: var(--jarak); background: #eaf4ed; }' . "\n"
                    . 'nav, .hero { display: flex; gap: var(--jarak); justify-content: space-between; }' . "\n"
                    . 'main { width: min(100% - 2rem, 70rem); margin: auto; }' . "\n"
                    . 'section { padding-block: 2rem; }' . "\n"
                    . '.project-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: var(--jarak); }' . "\n"
                    . '@media (max-width: 600px) { nav, .hero { flex-direction: column; } }',
                'javascript' => "const proyek = [\n  { judul: 'Portofolio', kategori: 'Website' },\n  { judul: 'Daftar Belajar', kategori: 'Aplikasi' },\n];\n"
                    . "document.body.innerHTML = `\n  <main>\n    <header><h1>Portofolio Rani</h1><button id=\"tema\">Ganti tema</button></header>\n    <label>Cari proyek <input id=\"cari\" type=\"search\"></label>\n    <section id=\"daftar\"></section>\n    <form id=\"kontak\"><label>Pesan <input name=\"pesan\" required></label><button>Kirim</button></form>\n  </main>`;\n"
                    . "const daftar = document.querySelector('#daftar');\n"
                    . "function tampilkan(items) {\n  daftar.innerHTML = items.map((item) => `<article><h2>\${item.judul}</h2><p>\${item.kategori}</p></article>`).join('');\n}\n"
                    . "tampilkan(proyek);\n"
                    . "document.querySelector('#cari').addEventListener('input', (event) => {\n  const kata = event.target.value.toLowerCase();\n  tampilkan(proyek.filter((item) => item.judul.toLowerCase().includes(kata)));\n});",
                'php' => "<?php\n\$proyek = [\n    ['judul' => 'Portofolio', 'kategori' => 'Website'],\n    ['judul' => 'Daftar Belajar', 'kategori' => 'Aplikasi'],\n];\n?>\n<header><h1>Portofolio Rani</h1><nav><a href=\"#proyek\">Proyek</a> <a href=\"#kontak\">Kontak</a></nav></header>\n<main><section id=\"proyek\"><h2>Proyek pilihan</h2>\n<?php foreach (\$proyek as \$item): ?>\n  <article><h3><?= htmlspecialchars(\$item['judul'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars(\$item['kategori'], ENT_QUOTES, 'UTF-8') ?></p></article>\n<?php endforeach; ?>\n</section><section id=\"kontak\"><h2>Kontak</h2><a href=\"mailto:rani@example.com\">Kirim email</a></section></main>\n<footer><p>&copy; 2026 Rani</p></footer>",
                default => "import json\nfrom html import escape\n\nportofolio = {\n    \"nama\": \"Rani\",\n    \"proyek\": [\n        {\"judul\": \"Portofolio\", \"kategori\": \"Website\"},\n        {\"judul\": \"Daftar Belajar\", \"kategori\": \"Aplikasi\"},\n    ],\n}\n\nkartu = \"\".join(\n    f\"<article><h2>{escape(item['judul'])}</h2><p>{escape(item['kategori'])}</p></article>\"\n    for item in portofolio[\"proyek\"]\n)\nhalaman = f\"<main><h1>Portofolio {escape(portofolio['nama'])}</h1>{kartu}</main>\"\nprint(halaman)",
            };
        }
        $codeExplanation = match ($language) {
            'html' => 'Elemen section menjadi satu bagian halaman, heading memberi nama bagian, dan tautan mengarah ke latihan. Jalankan kode untuk melihat markup berubah menjadi bagian website.',
            'css' => 'Selector memilih kartu proyek, lalu deklarasi mengatur jarak, garis, warna, dan tipografi. Jalankan kode untuk melihat perubahan pada pratinjau komponen.',
            'javascript' => 'Variabel menyimpan informasi tahap dan console.log menampilkan hasilnya. Ketik ulang lalu ubah nilai agar melihat perubahan output.',
            'php' => 'PHP membentuk satu kartu proyek. htmlspecialchars mengamankan teks sebelum ditampilkan ke HTML; runner browser tidak mengeksekusi PHP, jadi kode diketik dan dikirim untuk ditinjau.',
            default => 'Variabel menyimpan isi tahap dan print menampilkan hasilnya di panel output Python. Ubah judul atau tujuan lalu jalankan kembali.',
        };
        $theory = "{$objective}\n\nKetik ulang contoh kode pada editor, jalankan, lalu amati bagian hasilnya. Ubah satu nilai atau elemen dan jalankan kembali untuk membandingkan perubahan. Materi ini menjadi satu bagian dari proyek website mini yang disusun bertahap.";
        $exercise = "Ketik ulang contoh {$title} tanpa menyalin-tempel. Ubah isinya sesuai idemu, jalankan, lalu periksa apakah hasilnya sesuai tujuan. Setelah itu, tambahkan satu variasi dan jelaskan perubahan yang terlihat.";
        $project = "Simpan hasil tahap {$stage} ({$title}) sebagai bagian proyek akhir. Pastikan hasil tahap sebelumnya tetap berfungsi; di tahap akhir, gabungkan bagian-bagian ini menjadi website mini portofolio.";
        if ($stage >= 41) {
            $project = "Proyek website mini tahap {$stage}: {$objective} Gabungkan dengan bagian yang sudah dibuat, ketik sendiri perubahan kodenya, lalu uji halaman lengkap sebelum melanjutkan.";
        }
        if ($stage === 50) {
            $project = match ($language) {
                'html' => 'Proyek akhir HTML: selesaikan website portofolio mini berisi navigasi, hero, profil, daftar proyek, kontak, dan footer. Pastikan heading semantik, tautan berfungsi, dan seluruh halaman dapat dijelajahi dengan keyboard.',
                'css' => 'Proyek akhir CSS: selesaikan tampilan website portofolio mini yang responsif, konsisten, mudah dibaca, memiliki state fokus yang terlihat, serta berisi header, hero, proyek, kontak, dan footer.',
                'javascript' => 'Proyek akhir JavaScript: selesaikan website portofolio mini interaktif dengan render kartu proyek, pencarian/filter, form validasi, tema tersimpan, dan navigasi yang diuji.',
                'php' => 'Proyek akhir PHP: selesaikan website portofolio mini dengan halaman publik, daftar/detail proyek, form kontak, serta dashboard CRUD PHP-MySQL yang memakai PDO, validasi, escaping, CSRF, session, dan pemeriksaan izin.',
                default => 'Proyek akhir Python: selesaikan generator website portofolio mini dari data tervalidasi yang tersimpan di JSON; hasilkan HTML berisi navigasi, profil, proyek, kontak, dan footer, lalu uji data kosong serta teks khusus.',
            };
        }
        return [$title, $theory, $codeExample, $codeExplanation, $exercise, $project];
    }

    private static function genericOutline(array $course): array
    {
        $topics = [
            'Pengenalan materi', 'Istilah dan konsep dasar', 'Struktur dan sintaks', 'Nilai dan tipe data',
            'Ekspresi dan operasi', 'Alur kerja dasar', 'Kondisi dan keputusan', 'Pengulangan dan proses',
            'Mengelola kumpulan data', 'Fungsi dan komponen', 'Input dan output', 'Validasi dan aturan',
            'Menghubungkan bagian sistem', 'Penanganan kesalahan', 'Keamanan dan praktik baik',
            'Menguji hasil', 'Debugging langkah demi langkah', 'Menyusun solusi lengkap',
            'Refactoring dan dokumentasi', 'Proyek akhir',
        ];
        $objective = "Pada tahap ini, pelajari {$course['title']} dengan fokus pada topik yang disebutkan. Pahami tujuan penggunaannya, kapan konsep ini tepat dipakai, dan bagaimana menghubungkannya dengan materi kursus.";
        return array_map(
            static fn(string $topic): array => [$topic, $objective],
            $topics
        );
    }

    private static function slugify(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
