<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Tambah Biodata - ' . APP_BRAND;
$activePage = 'home';

$errors = [];
$old = [
    'nama'      => '',
    'nim'       => '',
    'deskripsi' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $validation = validateBiodataInput($_POST);
        $old = $validation['data'];
        $errors = $validation['errors'];

        $uploadedFoto = null;
        if (empty($errors)) {
            $upload = handlePhotoUpload($_FILES['foto'] ?? []);
            if ($upload['error'] !== null) {
                $errors['foto'] = $upload['error'];
            } else {
                $uploadedFoto = $upload['filename'];
            }
        }

        if (empty($errors)) {
            try {
                $pdo = getPDO();
                $stmt = $pdo->prepare(
                    'INSERT INTO biodata (nama, nim, deskripsi, foto) VALUES (:nama, :nim, :deskripsi, :foto)'
                );
                $stmt->execute([
                    ':nama'      => $old['nama'],
                    ':nim'       => $old['nim'],
                    ':deskripsi' => $old['deskripsi'],
                    ':foto'      => $uploadedFoto,
                ]);

                setFlash('success', 'Biodata berhasil ditambahkan.');
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                deleteUploadedPhoto($uploadedFoto);
                $errors['form'] = 'Gagal menyimpan data ke database.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-2">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card form-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0 fw-bold">Tambah Biodata</h4>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger py-2"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form action="create.php" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                            id="nama"
                            name="nama"
                            value="<?= e($old['nama']) ?>"
                            placeholder="contoh: rizki eka prasodjo"
                            maxlength="150"
                            required
                        >
                        <?php if (isset($errors['nama'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nama']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="nim" class="form-label">NIM</label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['nim']) ? 'is-invalid' : '' ?>"
                            id="nim"
                            name="nim"
                            value="<?= e($old['nim']) ?>"
                            placeholder="contoh: 12.11.5866"
                            maxlength="50"
                            required
                        >
                        <?php if (isset($errors['nim'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nim']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi / Peran</label>
                        <textarea
                            class="form-control <?= isset($errors['deskripsi']) ? 'is-invalid' : '' ?>"
                            id="deskripsi"
                            name="deskripsi"
                            rows="3"
                            placeholder="Tuliskan NIM dan peran anggota pada proyek ini..."
                            maxlength="1000"
                            required
                        ><?= e($old['deskripsi']) ?></textarea>
                        <?php if (isset($errors['deskripsi'])): ?>
                            <div class="invalid-feedback"><?= e($errors['deskripsi']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="foto" class="form-label">Foto (Opsional)</label>
                        <input
                            type="file"
                            class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                            id="foto"
                            name="foto"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <div class="form-text">Format: JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                        <?php if (isset($errors['foto'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['foto']) ?></div>
                        <?php endif; ?>
                        <div class="mt-2 d-none" id="previewContainer">
                            <img src="#" alt="Preview Foto" id="fotoPreview" class="preview-thumb">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Simpan Biodata</button>
                        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('foto')?.addEventListener('change', function (e) {
    const file = e.target.files?.[0];
    const container = document.getElementById('previewContainer');
    const preview = document.getElementById('fotoPreview');
    if (!file || !container || !preview) return;

    const reader = new FileReader();
    reader.onload = function (ev) {
        preview.src = ev.target?.result || '#';
        container.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
