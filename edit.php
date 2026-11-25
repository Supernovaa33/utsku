<?php
require_once __DIR__ . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    setFlash('danger', 'ID biodata tidak valid.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, nama, nim, deskripsi, foto FROM biodata WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $biodata = $stmt->fetch();
} catch (PDOException $e) {
    setFlash('danger', 'Gagal mengambil data dari database.');
    header('Location: index.php');
    exit;
}

if (!$biodata) {
    setFlash('danger', 'Data biodata tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit Biodata - ' . APP_BRAND;
$activePage = 'home';

$errors = [];
$old = [
    'nama'      => $biodata['nama'],
    'nim'       => $biodata['nim'],
    'deskripsi' => $biodata['deskripsi'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $validation = validateBiodataInput($_POST);
        $old = $validation['data'];
        $errors = $validation['errors'];

        $newFoto = null;
        if (empty($errors)) {
            $upload = handlePhotoUpload($_FILES['foto'] ?? []);
            if ($upload['error'] !== null) {
                $errors['foto'] = $upload['error'];
            } else {
                $newFoto = $upload['filename'];
            }
        }

        if (empty($errors)) {
            $finalFoto = $newFoto ?? $biodata['foto'];

            try {
                $updateStmt = $pdo->prepare(
                    'UPDATE biodata SET nama = :nama, nim = :nim, deskripsi = :deskripsi, foto = :foto WHERE id = :id'
                );
                $updateStmt->execute([
                    ':nama'      => $old['nama'],
                    ':nim'       => $old['nim'],
                    ':deskripsi' => $old['deskripsi'],
                    ':foto'      => $finalFoto,
                    ':id'        => $id,
                ]);

                if ($newFoto !== null && $biodata['foto'] !== $newFoto) {
                    deleteUploadedPhoto($biodata['foto']);
                }

                setFlash('success', 'Biodata berhasil diperbarui.');
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                deleteUploadedPhoto($newFoto);
                $errors['form'] = 'Gagal memperbarui data di database.';
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
                    <h4 class="mb-0 fw-bold">Edit Biodata</h4>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-danger py-2"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form action="edit.php?id=<?= (int) $id ?>" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $id ?>">

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                            id="nama"
                            name="nama"
                            value="<?= e($old['nama']) ?>"
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
                            maxlength="1000"
                            required
                        ><?= e($old['deskripsi']) ?></textarea>
                        <?php if (isset($errors['deskripsi'])): ?>
                            <div class="invalid-feedback"><?= e($errors['deskripsi']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="foto" class="form-label">Ganti Foto (Opsional)</label>
                        <div class="mb-2">
                            <img
                                src="<?= e(getPhotoUrl($biodata['foto'])) ?>"
                                alt="Foto saat ini"
                                id="fotoPreview"
                                class="preview-thumb"
                            >
                        </div>
                        <input
                            type="file"
                            class="form-control <?= isset($errors['foto']) ? 'is-invalid' : '' ?>"
                            id="foto"
                            name="foto"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <div class="form-text">Biarkan kosong jika tidak ingin mengganti foto. Format: JPG, PNG, WEBP (maks. 2 MB).</div>
                        <?php if (isset($errors['foto'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['foto']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Perbarui Biodata</button>
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
    const preview = document.getElementById('fotoPreview');
    if (!file || !preview) return;

    const reader = new FileReader();
    reader.onload = function (ev) {
        if (ev.target?.result) {
            preview.src = ev.target.result;
        }
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
