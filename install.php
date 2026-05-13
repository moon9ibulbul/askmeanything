<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $admin_user = $_POST['admin_user'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_text TEXT NOT NULL,
            answer_text TEXT DEFAULT NULL,
            love_count INT DEFAULT 0,
            like_count INT DEFAULT 0,
            sad_count INT DEFAULT 0,
            laugh_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_answered BOOLEAN DEFAULT FALSE
        )");

        $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$admin_user, $admin_pass]);

        if (!is_dir('includes')) {
            mkdir('includes', 0755, true);
        }

        $config_content = "<?php\n";
        $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
        $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
        $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
        $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n";
        $config_content .= "\n";
        $config_content .= "try {\n";
        $config_content .= "    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", DB_USER, DB_PASS);\n";
        $config_content .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
        $config_content .= "} catch (PDOException \$e) {\n";
        $config_content .= "    die(\"Connection failed: \" . \$e->getMessage());\n";
        $config_content .= "}\n";

        file_put_contents('includes/db.php', $config_content);

        $success = "Installation successful! You can now <a href='index.php' class='font-bold text-blue-600 underline'>go to Home</a> or <a href='admin/login.php' class='font-bold text-blue-600 underline'>Login to Admin Dashboard</a>. Please delete install.php for security.";
    } catch (PDOException $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - AstralExpress AMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">AstralExpress AMA Installation</h1>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Database Host</label>
                <input type="text" name="db_host" value="localhost" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Database Name</label>
                <input type="text" name="db_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Database User</label>
                <input type="text" name="db_user" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Database Password</label>
                <input type="password" name="db_pass" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <hr class="my-6">
            <h2 class="text-lg font-semibold mb-2">Admin Account</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700">Admin Username</label>
                <input type="text" name="admin_user" value="admin" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Admin Password</label>
                <input type="password" name="admin_pass" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-md hover:bg-blue-700 transition">Install</button>
        </form>
    </div>
</body>
</html>
