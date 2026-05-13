<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = isset($_POST['question']) ? trim($_POST['question']) : '';

    if (empty($question)) {
        echo json_encode(['success' => false, 'error' => 'Question cannot be empty.']);
        exit;
    }

    if (strlen($question) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Question is too long (max 1000 chars).']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO questions (question_text) VALUES (?)");
        $stmt->execute([$question]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
