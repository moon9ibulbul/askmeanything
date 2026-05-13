<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    $allowed_types = ['love', 'like', 'sad', 'laugh'];

    if ($id <= 0 || !in_array($type, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
        exit;
    }

    $column = $type . '_count';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE questions SET $column = $column + 1 WHERE id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("SELECT $column FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $new_count = $stmt->fetchColumn();

        $pdo->commit();
        echo json_encode(['success' => true, 'new_count' => $new_count]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
