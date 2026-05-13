<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = 'includes/db.php';
if (!file_exists($config_file)) {
    die("Error: Config file not found. Please run install.php first.");
}

require_once $config_file;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        visitor_id VARCHAR(255) NOT NULL,
        reaction_type ENUM('love', 'like', 'sad', 'laugh') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_reaction (visitor_id, question_id),
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");

    echo "Update successful! The 'reactions' table has been created or already exists.<br>";
    echo "<a href='index.php'>Go to Home</a>";
} catch (PDOException $e) {
    die("Update failed: " . $e->getMessage());
}
