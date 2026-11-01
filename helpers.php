<?php
/**
 * helpers.php — fungsi bersama untuk fitur pesanan
 * Dipakai oleh: keranjang.php, riwayat.php, admin.php
 */

// Toko ada di Bali (WITA). Dipakai juga untuk waktu yang disimpan ke database
// supaya jam di halaman pelanggan & admin selalu konsisten.
date_default_timezone_set('Asia/Makassar');

// Nomor WhatsApp admin toko (format internasional tanpa +)
const WA_ADMIN = '6287753917059';

// Urutan status pesanan. "Dibatalkan" di luar alur normal (dipakai admin kalau pesanan batal).
const STATUS_PESANAN = ['Diterima', 'Diproses', 'Selesai', 'Dibatalkan'];
const ALUR_STATUS    = ['Diterima', 'Diproses', 'Selesai'];

// Cookie yang mengenali "perangkat" pemesan, supaya riwayat tetap ada
// walaupun session keranjang sudah habis. Tidak perlu login.
const COOKIE_PEMESAN = 'kf_pemesan';

// Pilihan yang boleh diterima server (sama dengan opsi di form keranjang)
const PILIHAN_PENGAMBILAN = ['Ke Toko', 'Gosend Dari Pembeli'];
const PILIHAN_PEMBAYARAN  = ['QRIS', 'BCA', 'Cash'];

// Palet warna siap pilih di katalog (nama => kode hex). Pembeli juga boleh menulis warna sendiri.
const PALET_BUNGA = [
    'Merah'  => '#c8433f', 'Pink'   => '#f2a7bb', 'Peach'  => '#f7b99b',
    'Kuning' => '#f3d160', 'Putih'  => '#ffffff', 'Ungu'   => '#a68ccb',
    'Biru'   => '#8fb7e3', 'Oranye' => '#ee9448', 'Hijau'  => '#8fb08f',
];
const PALET_WRAP = [
    'Putih'  => '#ffffff', 'Cream'  => '#f1e6d3', 'Pink'   => '#f2a7bb',
    'Merah'  => '#c8433f', 'Biru'   => '#8fb7e3', 'Hijau'  => '#8fb08f',
    'Ungu'   => '#a68ccb', 'Kraft'  => '#b08968', 'Hitam'  => '#2f3136',
];
const MAKS_WARNA_BUNGA = 3; // maksimal warna bunga yang boleh dipilih dari palet

// Pilihan isian modal "Pesan" yang bisa dinyalakan admin per kategori (kunci => tampilan di panel admin)
const OPSI_KUSTOM = [
    'warna_bunga' => ['label' => 'Warna bunga',    'ikon' => 'bi-flower2',        'ket' => 'Pilih warna bunga dari palet / tulis sendiri'],
    'warna_wrap'  => ['label' => 'Warna wrapping', 'ikon' => 'bi-gift',           'ket' => 'Pilih warna kertas pembungkus'],
    'jenis'       => ['label' => 'Jenis ucapan',   'ikon' => 'bi-balloon-heart',  'ket' => 'Pilih acara: ulang tahun, duka cita, grand opening, atau ketik sendiri'],
    'ucapan'      => ['label' => 'Kata-kata ucapan', 'ikon' => 'bi-chat-quote',   'ket' => 'Pembeli menulis ucapan untuk kartu'],
    'foto'        => ['label' => 'Foto referensi', 'ikon' => 'bi-image',        'ket' => 'Pembeli mengunggah gambar contoh rangkaian (karangan bunga custom)'],
    'catatan'     => ['label' => 'Catatan penjual', 'ikon' => 'bi-chat-left-text', 'ket' => 'Request khusus untuk penjual'],
];
// Dipakai kategori lama yang belum pernah diatur admin (sama dengan perilaku sebelumnya)
const OPSI_DEFAULT = ['warna_bunga', 'warna_wrap', 'catatan'];

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rupiah($n) {
    return 'Rp ' . number_format((int)$n, 0, ',', '.');
}

/** Potong teks ke panjang maksimal (aman untuk karakter UTF-8 bila mbstring aktif) */
function potong($teks, $maks) {
    $teks = trim((string)$teks);
    return function_exists('mb_substr') ? mb_substr($teks, 0, $maks, 'UTF-8') : substr($teks, 0, $maks);
}


/** strtolower yang aman untuk UTF-8 bila mbstring aktif */
function huruf_kecil($teks) {
    return function_exists('mb_strtolower') ? mb_strtolower((string)$teks, 'UTF-8') : strtolower((string)$teks);
}

/** Buang karakter kontrol & rapikan spasi, lalu potong. Aman untuk teks bebas dari pembeli. */
function rapikan_teks($teks, $maks) {
    $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$teks);
    $teks = preg_replace('/[ \t]+/u', ' ', $teks);
    return potong($teks, $maks);
}

/**
 * Gabungkan pilihan palet + warna tulis sendiri + kode warna (color picker) jadi satu teks.
 * Contoh hasil: "Pink + Putih + Dusty rose (#c48c94)". Kosong = ikut rekomendasi toko.
 * Pilihan palet divalidasi terhadap $palet, jadi nilai di luar daftar diabaikan.
 */
function gabung_warna($pilihan, array $palet, $teks, $hex, $pakai_hex, $maks_palet = 3) {
    $bagian = [];
    foreach ((array)$pilihan as $nama) {
        if (is_string($nama) && isset($palet[$nama]) && !in_array($nama, $bagian, true)) $bagian[] = $nama;
        if (count($bagian) >= $maks_palet) break;
    }
    $kustom = rapikan_teks($teks, 60);
    $hex    = (string)$hex;
    $kode   = ($pakai_hex && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) ? strtolower($hex) : '';
    if ($kustom !== '' && $kode !== '') $kustom .= ' (' . $kode . ')';
    elseif ($kode !== '')               $kustom = 'Kode ' . $kode;
    if ($kustom !== '') $bagian[] = $kustom;
    return implode(' + ', $bagian);
}

/**
 * Ubah teks tersimpan di kolom kategori.opsi_kustom ("warna_bunga,ucapan") jadi daftar kunci valid.
 * NULL = belum pernah diatur -> pakai OPSI_DEFAULT. Teks kosong = admin sengaja mematikan semuanya.
 */
function opsi_aktif($raw) {
    if ($raw === null) return OPSI_DEFAULT;
    $hasil = [];
    foreach (explode(',', (string)$raw) as $k) {
        $k = trim($k);
        if (isset(OPSI_KUSTOM[$k]) && !in_array($k, $hasil, true)) $hasil[] = $k;
    }
    return $hasil;
}

/** Ambil kiriman checkbox admin -> teks untuk disimpan (hanya kunci yang dikenal) */
function opsi_dari_post($kiriman) {
    $hasil = [];
    foreach ((array)$kiriman as $k) {
        if (is_string($k) && isset(OPSI_KUSTOM[$k]) && !in_array($k, $hasil, true)) $hasil[] = $k;
    }
    // urutkan sesuai urutan di OPSI_KUSTOM
    $hasil = array_values(array_intersect(array_keys(OPSI_KUSTOM), $hasil));
    return implode(',', $hasil);
}

/** Ikon Bootstrap untuk label kustom (Warna bunga, Warna wrapping, Ucapan, Catatan) */
function ikon_kustom($label) {
    foreach (OPSI_KUSTOM as $o) { if ($o['label'] === $label) return $o['ikon']; }
    if ($label === 'Ucapan')  return 'bi-chat-quote';
    if ($label === 'Catatan') return 'bi-chat-left-text';
    return 'bi-gift';
}

const DIR_REFERENSI = 'uploads/referensi/';
// Pilihan jenis ucapan di modal (selain ini, pembeli bisa memilih "Lainnya" dan mengetik sendiri)
const JENIS_UCAPAN = ['Selamat Ulang Tahun', 'Turut Berduka Cita', 'Selamat Grand Opening', 'Selamat Pernikahan', 'Selamat Wisuda', 'Selamat atas Pelantikan'];
const JENIS_LAINNYA = 'Lainnya';

/** Gabungkan pilihan jenis ucapan + teks sendiri jadi satu teks (pilihan di luar daftar diabaikan) */
function gabung_jenis_ucapan($pilih, $teks) {
    $pilih = (string)$pilih;
    if (in_array($pilih, JENIS_UCAPAN, true)) return $pilih;
    if ($pilih === JENIS_LAINNYA) {
        $t = rapikan_teks($teks, 80);
        return $t !== '' ? $t : JENIS_LAINNYA;
    }
    return '';
}

const MAKS_FOTO_BYTE = 2 * 1024 * 1024; // 2 MB (sama dengan batas bawaan PHP)

/**
 * Simpan foto referensi dari pembeli. Mengembalikan [nama_file, pesan_error].
 * Tidak ada file dipilih => ['', '']. Jenis file dicek dari isinya (bukan dari nama), dan nama diganti acak.
 */
function simpan_foto_referensi($f) {
    if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['', ''];
    if ($f['error'] !== UPLOAD_ERR_OK) return ['', 'Foto gagal diunggah (maksimal 2 MB).'];
    if ($f['size'] > MAKS_FOTO_BYTE)    return ['', 'Ukuran foto maksimal 2 MB.'];
    $info = @getimagesize($f['tmp_name']);
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ekstensi[$info[2]])) return ['', 'Format foto harus JPG, PNG, atau WEBP.'];
    if (!is_dir(DIR_REFERENSI)) @mkdir(DIR_REFERENSI, 0755, true);
    $nama = bin2hex(random_bytes(8)) . '.' . $ekstensi[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], DIR_REFERENSI . $nama)) return ['', 'Foto belum bisa disimpan di server.'];
    return [$nama, ''];
}

/** Nama file referensi valid (dan file-nya ada)? Kalau tidak, kembalikan kosong. */
function foto_referensi_valid($nama) {
    $nama = (string)$nama;
    return (preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $nama) && is_file(DIR_REFERENSI . $nama)) ? $nama : '';
}

/** Miniatur foto referensi yang bisa diklik untuk memperbesar */
function foto_html($nama, $ukuran = 64) {
    $nama = foto_referensi_valid($nama);
    if ($nama === '') return '<span class="text-muted">(file tidak ditemukan)</span>';
    $u = e(DIR_REFERENSI . $nama);
    return '<a href="' . $u . '" target="_blank" rel="noopener"><img src="' . $u . '" alt="Foto referensi" style="width:' . (int)$ukuran . 'px;height:' . (int)$ukuran . 'px;object-fit:cover;border-radius:10px;border:1px solid #eadfe1;display:block;margin-top:4px;"></a>';
}

/** Kunci baris keranjang: produk yang sama dengan kustom berbeda jadi baris terpisah */
function kunci_keranjang($id_produk, $warna_bunga, $warna_wrap, $catatan, $ucapan = '', $foto = '', $jenis = '') {
    return (int)$id_produk . '_' . substr(md5(huruf_kecil($warna_bunga . '|' . $warna_wrap . '|' . $catatan . '|' . $ucapan . '|' . $foto . '|' . $jenis)), 0, 8);
}

/** Daftar kustom satu item [label => isi], hanya yang terisi. Dipakai riwayat, admin, dan WhatsApp. */
function kustom_item(array $it) {
    $hasil = [];
    if (!empty($it['warna_bunga']))    $hasil['Warna bunga']    = $it['warna_bunga'];
    if (!empty($it['warna_wrapping'])) $hasil['Warna wrapping'] = $it['warna_wrapping'];
    if (!empty($it['jenis_ucapan']))   $hasil['Jenis ucapan']   = $it['jenis_ucapan'];
    if (!empty($it['ucapan']))         $hasil['Ucapan']         = $it['ucapan'];
    if (!empty($it['foto_referensi'])) $hasil['Foto referensi'] = $it['foto_referensi'];
    if (!empty($it['catatan']))        $hasil['Catatan']        = $it['catatan'];
    return $hasil;
}

/**
 * Tambah kolom kustomisasi ke database lama secara otomatis (sekali jalan, aman diulang).
 * Jadi pemilik toko tidak wajib menjalankan SQL manual. File migrasi_kustom.sql tetap disediakan.
 */
function pastikan_skema($conn) {
    static $sudah = false;
    if ($sudah) return;
    $sudah = true;
    $perlu = [
        'detail_pesanan' => [
            'warna_bunga'    => "varchar(150) DEFAULT NULL",
            'warna_wrapping' => "varchar(150) DEFAULT NULL",
            'catatan'        => "varchar(500) DEFAULT NULL",
            'ucapan'         => "varchar(500) DEFAULT NULL",
            'foto_referensi' => "varchar(150) DEFAULT NULL",
            'jenis_ucapan'   => "varchar(150) DEFAULT NULL",
        ],
        'kategori' => [
            'opsi_kustom'    => "varchar(100) DEFAULT NULL",
        ],
        'pesanan' => [
            'catatan_penjual' => "text DEFAULT NULL",
        ],
    ];
    try {
        foreach ($perlu as $tabel => $kolom_kolom) {
            $r = mysqli_query($conn, "SHOW TABLES LIKE '$tabel'");
            if (!$r || mysqli_num_rows($r) === 0) continue;
            foreach ($kolom_kolom as $kolom => $definisi) {
                $r = mysqli_query($conn, "SHOW COLUMNS FROM `$tabel` LIKE '$kolom'");
                if ($r && mysqli_num_rows($r) === 0) {
                    mysqli_query($conn, "ALTER TABLE `$tabel` ADD COLUMN `$kolom` $definisi");
                }
            }
        }
    } catch (Throwable $ex) {
        error_log('pastikan_skema gagal: ' . $ex->getMessage());
    }
}

/** "2026-10-26 14:05:00" -> "26 Okt 2026, 14:05" */
function tgl_indo($datetime, $dengan_jam = true) {
    static $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($datetime);
    if (!$t) return '-';
    $teks = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $teks . ', ' . date('H:i', $t) : $teks;
}

/**
 * Ambil token pemesan dari cookie. Kalau $buat = true dan belum ada, buat baru.
 * Mengembalikan null kalau belum ada & tidak diminta membuat.
 */
function token_pemesan($buat = false) {
    $token = $_COOKIE[COOKIE_PEMESAN] ?? '';
    if (preg_match('/^[a-f0-9]{32}$/', $token)) {
        return $token;
    }
    if (!$buat) return null;

    $token = bin2hex(random_bytes(16));
    setcookie(COOKIE_PEMESAN, $token, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[COOKIE_PEMESAN] = $token;
    return $token;
}

/** Kode unik yang mudah dibaca, contoh: KF-261026-7QH3M */
function buat_kode_pesanan($conn) {
    $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa O/0/I/1 biar tidak membingungkan
    for ($coba = 0; $coba < 10; $coba++) {
        $acak = '';
        for ($i = 0; $i < 5; $i++) {
            $acak .= $huruf[random_int(0, strlen($huruf) - 1)];
        }
        $kode = 'KF-' . date('ymd') . '-' . $acak;

        $stmt = mysqli_prepare($conn, "SELECT 1 FROM pesanan WHERE kode_pesanan = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $kode);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $ada = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$ada) return $kode;
    }
    throw new RuntimeException('Gagal membuat kode pesanan unik');
}

/** Tampilan tiap status: label, warna badge (Bootstrap), ikon, dan penjelasan untuk pelanggan */
function info_status($status) {
    $daftar = [
        'Diterima'   => ['kelas' => 'warning', 'ikon' => 'bi-inbox-fill',      'teks' => 'Pesananmu sudah kami terima dan menunggu konfirmasi admin.'],
        'Diproses'   => ['kelas' => 'info',    'ikon' => 'bi-flower2',         'teks' => 'Bunga pesananmu sedang dirangkai dengan sepenuh hati.'],
        'Selesai'    => ['kelas' => 'success', 'ikon' => 'bi-bag-check-fill',  'teks' => 'Pesanan selesai dan siap diambil / dikirim.'],
        'Dibatalkan' => ['kelas' => 'danger',  'ikon' => 'bi-x-circle-fill',   'teks' => 'Pesanan ini dibatalkan. Hubungi admin jika ada pertanyaan.'],
    ];
    return $daftar[$status] ?? $daftar['Diterima'];
}

/** Susun teks pesan WhatsApp dari data pesanan yang SUDAH tersimpan di database */
function susun_pesan_wa(array $p, array $items) {
    $baris_pesanan = '';
    foreach ($items as $it) {
        $baris_pesanan .= '- ' . $it['nama_produk'] . ' (Qty: ' . $it['qty'] . ")\n";
        foreach (kustom_item($it) as $label => $isi) {
            if ($label === 'Foto referensi') $isi = 'Ada, dilampirkan di sistem (cek panel admin / Riwayat)';
            $baris_pesanan .= '   ' . $label . ' : ' . preg_replace('/\s*\R\s*/u', ' ', $isi) . "\n";
        }
    }
    $jam = substr($p['jam_ambil'], 0, 5);

    // Pesanan lama (sebelum ada kustom per produk) masih menyimpan tema warna di level pesanan
    $lama = '';
    if (!empty($p['tema_warna']))     $lama .= "Tema Warna Bunga : " . $p['tema_warna'] . "\n";
    if (!empty($p['warna_wrapping'])) $lama .= "Warna Wrapping : " . $p['warna_wrapping'] . "\n";

    $catatan = (isset($p['catatan_penjual']) && $p['catatan_penjual'] !== '') ? $p['catatan_penjual'] : '-';

    return "FORMAT ORDER - Kami Florist\n"
        . "MOHON SEGERA DIISI AGAR DAPAT DIPROSES\n\n"
        . "Kode Pesanan : " . $p['kode_pesanan'] . "\n"
        . "Nama : " . $p['nama_pemesan'] . "\n"
        . "Pesanan : \n" . trim($baris_pesanan) . "\n"
        . "Total : " . rupiah($p['total']) . "\n"
        . $lama
        . "\nCatatan untuk Penjual : \n" . $catatan . "\n\n"
        . "Tanggal Pengambilan : " . date('d/m/Y', strtotime($p['tanggal_ambil'])) . "\n"
        . "Jam Pengambilan : " . $jam . "\n"
        . "(Buka dari jam 09:00 - 19:00)\n"
        . "NB : Pengambilan tidak bisa dimajukan mendadak, jam harus diisikan perkiraan paling awal pesanan di ambil\n\n"
        . "Pengambilan: " . $p['metode_ambil'] . "\n"
        . "NB : Dipilih Salah Satu\n\n"
        . "Pembayaran : " . $p['metode_bayar'] . "\n"
        . "NB : Dipilih Salah Satu\n"
        . "NB : Wajib DP/Full TF untuk total pesanan diatas 100k\n"
        . "NB : custom bouquet wajib DP\n\n"
        . "Free Kartu Ucapan : \n"
        . ($p['ucapan'] !== null && $p['ucapan'] !== '' ? $p['ucapan'] : '(sesuai yang tertulis per produk di atas)') . "\n";
}

function link_wa(array $p, array $items) {
    return 'https://wa.me/' . WA_ADMIN . '?text=' . rawurlencode(susun_pesan_wa($p, $items));
}

/** Ambil semua item untuk sekumpulan id_pesanan -> [id_pesanan => [item, ...]] */
function ambil_detail_pesanan($conn, array $ids) {
    $hasil = [];
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) return $hasil;

    $in  = implode(',', $ids); // sudah dipastikan integer
    $res = mysqli_query($conn, "SELECT * FROM detail_pesanan WHERE id_pesanan IN ($in) ORDER BY id_detail ASC");
    while ($d = mysqli_fetch_assoc($res)) {
        $hasil[$d['id_pesanan']][] = $d;
    }
    return $hasil;
}
