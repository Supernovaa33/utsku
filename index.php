<?php
require_once __DIR__ . '/config.php';

$pageTitle = APP_BRAND . ' - Biodata Tim';
$activePage = 'home';

$flash = getFlash();
$biodataList = [];
$dbError = null;

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, nama, nim, deskripsi, foto, created_at FROM biodata ORDER BY id ASC');
    $biodataList = $stmt->fetchAll();
} catch (PDOException $e) {
    $dbError = 'Koneksi database SQLite gagal: ' . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show flash-alert mb-3" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($dbError !== null): ?>
    <div class="alert alert-danger alert-dismissible fade show flash-alert mb-3" role="alert">
        <?= e($dbError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div id="daftar-biodata" class="row row-cols-1 row-cols-md-3 g-3">
    <!-- Card pertama: tombol tambah biodata -->
    <div class="col">
        <a href="create.php" class="card h-100 biodata-card add-card d-flex align-items-center justify-content-center" title="Tambah Biodata Baru" aria-label="Tambah Biodata Baru">
            <div class="add-circle">
                <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </div>
        </a>
    </div>

    <!-- Card biodata dari database -->
    <?php foreach ($biodataList as $row): ?>
        <div class="col">
            <div class="card h-100 biodata-card">
                <div class="biodata-img-wrap">
                    <img
                        src="<?= e(getPhotoUrl($row['foto'])) ?>"
                        class="card-img-top biodata-img"
                        alt="<?= e($row['nama']) ?>"
                    >
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title biodata-name"><?= e($row['nama']) ?></h5>
                    <?php
                    $deskripsiText = (string) $row['deskripsi'];
                    $nimText = trim((string) $row['nim']);
                    ?>
                    <p class="card-text biodata-desc mb-0">
                        <?php if ($nimText !== '' && stripos($deskripsiText, $nimText) === false): ?>
                            NIM <?= e($nimText) ?> &mdash; <?= nl2br(e($deskripsiText)) ?>
                        <?php else: ?>
                            <?= nl2br(e($deskripsiText)) ?>
                        <?php endif; ?>
                    </p>
                    <div class="mt-auto">
                        <div class="biodata-divider"></div>
                        <div class="d-flex gap-2">
                            <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm btn-delete-trigger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama']) ?>"
                            >Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus biodata <strong id="deleteTargetName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form action="delete.php" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" id="deleteTargetId" value="">
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteModal');
    if (!deleteModal) return;

    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const id = button.getAttribute('data-id') || '';
        const nama = button.getAttribute('data-nama') || '';

        document.getElementById('deleteTargetId').value = id;
        document.getElementById('deleteTargetName').textContent = nama;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
