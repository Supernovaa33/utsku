<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Konfigurasi Identitas Mahasiswa / Tim (Ubah di sini)
// ============================================================
define('APP_BRAND', 'UTS Pem Web');
define('NAV_NAMA', '(Masukkan Nama)');
define('NAV_NIM', '(Masukkan NIM)');
define('TEAM_KETUA', '(Masukkan Nama ketua)');
define('TEAM_ANGGOTA', '(Masukkan Nama Anggota)');

// ============================================================
// Konfigurasi Database SQLite (Tanpa XAMPP / Laragon)
// ============================================================
define('DB_DIR', __DIR__ . '/data');
define('DB_FILE', DB_DIR . '/biodata.sqlite');

// ============================================================
// Konfigurasi Upload Foto
// ============================================================
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

/**
 * Mengembalikan koneksi PDO SQLite dan otomatis membuat tabel + data awal
 * saat aplikasi dijalankan pertama kali.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(DB_DIR)) {
        mkdir(DB_DIR, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    initDatabase($pdo);

    return $pdo;
}

/**
 * Buat tabel biodata jika belum ada, dan isi 2 data awal hanya saat tabel baru dibuat.
 */
function initDatabase(PDO $pdo): void
{
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'biodata'");
    $tableExists = $tableCheck !== false && $tableCheck->fetchColumn() !== false;

    if ($tableExists) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE biodata (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            nim TEXT NOT NULL,
            deskripsi TEXT NOT NULL,
            foto TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $seedStmt = $pdo->prepare(
        'INSERT INTO biodata (nama, nim, deskripsi, foto) VALUES (:nama, :nim, :deskripsi, :foto)'
    );

    $seeds = [
        [
            ':nama'      => 'rizki eka prasodjo',
            ':nim'       => '12.11.5866',
            ':deskripsi' => '12.11.5866 merupakan master dari uts ini dengan tugas membuat front-end dan mengirimkannya ke grup',
            ':foto'      => 'seed-rizki.png',
        ],
        [
            ':nama'      => 'andi agus salim',
            ':nim'       => '12.11.6018',
            ':deskripsi' => 'dengan NIM 12.11.6018 merupakan pegawai dan mempunyai tugas mengerjakan back-end pada UTS ini',
            ':foto'      => 'seed-andi.png',
        ],
    ];

    foreach ($seeds as $seed) {
        $seedStmt->execute($seed);
    }
}

/**
 * Escape string HTML untuk mencegah XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Simpan flash message ke session.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Ambil dan hapus flash message dari session.
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Generate atau ambil CSRF token untuk keamanan form.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token dari request POST.
 */
function verifyCsrf(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validasi input teks form biodata (nama, nim, deskripsi).
 *
 * @return array{data: array<string, string>, errors: array<string, string>}
 */
function validateBiodataInput(array $input): array
{
    $nama = trim((string) ($input['nama'] ?? ''));
    $nim = trim((string) ($input['nim'] ?? ''));
    $deskripsi = trim((string) ($input['deskripsi'] ?? ''));

    $errors = [];

    if ($nama === '') {
        $errors['nama'] = 'Nama wajib diisi.';
    } elseif (mb_strlen($nama) > 150) {
        $errors['nama'] = 'Nama maksimal 150 karakter.';
    }

    if ($nim === '') {
        $errors['nim'] = 'NIM wajib diisi.';
    } elseif (mb_strlen($nim) > 50) {
        $errors['nim'] = 'NIM maksimal 50 karakter.';
    }

    if ($deskripsi === '') {
        $errors['deskripsi'] = 'Deskripsi wajib diisi.';
    } elseif (mb_strlen($deskripsi) > 1000) {
        $errors['deskripsi'] = 'Deskripsi maksimal 1000 karakter.';
    }

    return [
        'data' => [
            'nama'      => $nama,
            'nim'       => $nim,
            'deskripsi' => $deskripsi,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validasi dan simpan file foto yang diupload ke folder uploads/.
 *
 * @return array{filename: ?string, error: ?string}
 */
function handlePhotoUpload(array $file): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas maksimal 2 MB.',
            default => 'Terjadi kesalahan saat mengupload file.',
        };
        return ['filename' => null, 'error' => $message];
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        return ['filename' => null, 'error' => 'Ukuran foto maksimal 2 MB.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['filename' => null, 'error' => 'File upload tidak valid.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    if (!is_string($mimeType) || !array_key_exists($mimeType, ALLOWED_MIME_TYPES)) {
        return ['filename' => null, 'error' => 'Tipe file harus JPG, PNG, atau WEBP.'];
    }

    $extension = ALLOWED_MIME_TYPES[$mimeType];

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $extension;
    $destination = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['filename' => null, 'error' => 'Gagal menyimpan file foto ke server.'];
    }

    return ['filename' => $filename, 'error' => null];
}

/**
 * Hapus file foto lama dari folder uploads/ (kecuali file seed bawaan).
 */
function deleteUploadedPhoto(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }

    $safeName = basename($filename);
    if (in_array($safeName, ['seed-rizki.png', 'seed-andi.png', 'default-avatar.png'], true)) {
        return;
    }

    $path = UPLOAD_DIR . '/' . $safeName;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Kembalikan path relatif URL foto untuk ditampilkan di tag <img>.
 */
function getPhotoUrl(?string $filename): string
{
    if ($filename !== null && $filename !== '') {
        $safeName = basename($filename);
        if (is_file(UPLOAD_DIR . '/' . $safeName)) {
            return UPLOAD_URL . '/' . rawurlencode($safeName);
        }
    }

    return UPLOAD_URL . '/default-avatar.png';
}
