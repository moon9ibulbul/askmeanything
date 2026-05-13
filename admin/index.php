<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
    'answered' => $pdo->query("SELECT COUNT(*) FROM questions WHERE is_answered = 1")->fetchColumn(),
    'reactions' => $pdo->query("SELECT SUM(love_count + like_count + sad_count + laugh_count) FROM questions")->fetchColumn() ?: 0
];

// Pagination & Search
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query_str = "SELECT * FROM questions";
$params = [];
if ($search) {
    $query_str .= " WHERE question_text LIKE ? OR answer_text LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$query_str .= " ORDER BY created_at DESC LIMIT $start, $limit";

$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$questions = $stmt->fetchAll();

// Total for pagination
$total_query = "SELECT COUNT(*) FROM questions";
if ($search) {
    $total_query .= " WHERE question_text LIKE ? OR answer_text LIKE ?";
}
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$total_questions = $total_stmt->fetchColumn();
$total_pages = ceil($total_questions / $limit);

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AstralExpress AMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Nav -->
    <nav class="bg-white border-b border-gray-100 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
            <a href="../index.php" target="_blank" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200 transition">View Site <i class="fas fa-external-link-alt ml-1"></i></a>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-sm text-gray-500 hidden md:inline">Logged in as <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
            <a href="?logout=1" class="text-sm text-red-500 hover:text-red-700 font-medium">Logout</a>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium mb-1">Total Questions</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium mb-1">Answered</p>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['answered']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium mb-1">Total Reactions</p>
                <p class="text-3xl font-bold text-red-500"><?php echo $stats['reactions']; ?></p>
            </div>
        </div>

        <!-- Question Management -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-gray-800">Manage Questions</h2>
                <form action="" method="GET" class="w-full md:w-auto">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search questions..." class="pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition w-full md:w-64 text-sm">
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Question</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($questions as $q): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-800 font-medium line-clamp-2"><?php echo htmlspecialchars($q['question_text']); ?></p>
                                    <?php if ($q['answer_text']): ?>
                                        <p class="text-xs text-gray-400 mt-1 line-clamp-1 italic">A: <?php echo htmlspecialchars($q['answer_text']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($q['is_answered']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Answered</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <?php echo date('d M Y, H:i', strtotime($q['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($q)); ?>)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Answer/Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteQuestion(<?php echo $q['id']; ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($questions)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No questions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="p-6 border-t border-gray-50 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-sm <?php echo $page === $i ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?> transition">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal -->
    <div id="answer-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800" id="modal-title">Answer Question</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <form id="answer-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="q-id">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Question</label>
                    <p id="modal-q-text" class="text-gray-800 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Your Answer</label>
                    <textarea name="answer" id="q-answer" rows="5" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">Save Answer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('answer-modal');
        const answerForm = document.getElementById('answer-form');

        function openModal(question) {
            document.getElementById('q-id').value = question.id;
            document.getElementById('modal-q-text').textContent = question.question_text;
            document.getElementById('q-answer').value = question.answer_text || '';
            document.getElementById('modal-title').textContent = question.is_answered ? 'Edit Answer' : 'Answer Question';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        answerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(answerForm);
            formData.append('action', 'answer');

            try {
                const response = await fetch('../api/admin_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                console.error(error);
            }
        });

        async function deleteQuestion(id) {
            if (!confirm('Are you sure you want to delete this question?')) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            try {
                const response = await fetch('../api/admin_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                console.error(error);
            }
        }
    </script>
</body>
</html>
