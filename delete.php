<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Permintaan hapus tidak valid.');
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    setFlash('danger', 'ID biodata tidak valid.');
    header('Location: index.php');
    exit;
}

try {
    $pdo = getPDO();

    $selectStmt = $pdo->prepare('SELECT foto FROM biodata WHERE id = :id');
    $selectStmt->execute([':id' => $id]);
    $row = $selectStmt->fetch();

    if (!$row) {
        setFlash('danger', 'Data biodata tidak ditemukan.');
        header('Location: index.php');
        exit;
    }

    $deleteStmt = $pdo->prepare('DELETE FROM biodata WHERE id = :id');
    $deleteStmt->execute([':id' => $id]);

    deleteUploadedPhoto($row['foto'] ?? null);

    setFlash('success', 'Biodata berhasil dihapus.');
} catch (PDOException $e) {
    setFlash('danger', 'Gagal menghapus data dari database.');
}

header('Location: index.php');
exit;
