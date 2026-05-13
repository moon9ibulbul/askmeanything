<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'CSRF token mismatch.']);
        exit;
    }

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }

    if ($action === 'answer') {
        $answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';
        if (empty($answer)) {
            echo json_encode(['success' => false, 'error' => 'Answer cannot be empty.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE questions SET answer_text = ?, is_answered = 1 WHERE id = ?");
            $stmt->execute([$answer, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
