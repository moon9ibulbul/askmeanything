<?php
$config_file = 'includes/db.php';
if (!file_exists($config_file)) {
    header("Location: install.php");
    exit;
}
require_once $config_file;

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Sorting
$order_by = 'created_at DESC';
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $order_by = 'created_at ASC';
            break;
        case 'most_reacted':
            $order_by = '(love_count + like_count + sad_count + laugh_count) DESC';
            break;
        case 'newest':
        default:
            $order_by = 'created_at DESC';
            break;
    }
}

// Fetch answered questions
$single_q = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND is_answered = 1");
    $stmt->execute([(int)$_GET['id']]);
    $single_q = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($single_q) {
    $questions = [$single_q];
} else {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE is_answered = 1 ORDER BY $order_by LIMIT :start, :limit");
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count total for pagination
$total_stmt = $pdo->query("SELECT COUNT(*) FROM questions WHERE is_answered = 1");
$total_questions = $total_stmt->fetchColumn();
$total_pages = ceil($total_questions / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AstralExpress AMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modern-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-white text-gray-900 font-sans">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight text-gray-800">AstralExpress AMA</h1>
            <button id="menu-toggle" class="md:hidden text-gray-600 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <nav id="menu" class="hidden md:flex space-x-6">
                <a href="index.php" class="text-blue-600 font-medium">Home</a>
            </nav>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-b border-gray-100 px-4 py-2">
            <a href="index.php" class="block py-2 text-blue-600 font-medium">Home</a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Submission Form -->
        <div class="mb-12 bg-white p-6 rounded-2xl modern-shadow border border-gray-100">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">Tanya Apa Saja!</h2>
            <form id="ask-form">
                <textarea id="question_text" name="question" rows="4" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition resize-none" placeholder="Tulis pertanyaan kalian..."></textarea>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full font-medium hover:bg-blue-700 transition transform hover:scale-105">
                        Kirim Pertanyaan
                    </button>
                </div>
            </form>
            <div id="form-message" class="mt-4 hidden text-center font-medium"></div>
        </div>

        <!-- List and Filters -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">
                <?php echo $single_q ? 'Shared Question' : 'Answered Questions'; ?>
            </h2>
            <?php if ($single_q): ?>
                <a href="index.php" class="text-sm text-blue-600 hover:underline">Lihat Semua Pertanyaan</a>
            <?php else: ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Order by:</span>
                    <select id="sort-order" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition" onchange="window.location.href='index.php?sort=' + this.value">
                        <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest</option>
                        <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : ''; ?>>Oldest</option>
                        <option value="most_reacted" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'most_reacted') ? 'selected' : ''; ?>>Most Reacted</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <!-- Question List -->
        <div class="space-y-6">
            <?php if (empty($questions)): ?>
                <div class="text-center py-12 text-gray-500 italic">
                    Belum ada pertanyaan yang dijawab. Cek lagi nanti!
                </div>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <div id="q-card-<?php echo $q['id']; ?>" class="bg-white p-6 rounded-2xl modern-shadow border border-gray-100 group">
                        <div class="mb-4">
                            <span class="text-xs font-bold text-blue-500 uppercase tracking-wider">Question</span>
                            <p class="text-gray-800 text-lg font-medium mt-1"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></p>
                        </div>
                        <div class="mb-6 pl-4 border-l-4 border-gray-200">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Answer</span>
                            <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($q['answer_text'])); ?></p>
                        </div>

                        <!-- Reactions & Share -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-50">
                            <div class="flex items-center gap-4">
                                <button onclick="react(<?php echo $q['id']; ?>, 'love')" class="flex items-center gap-1 text-gray-500 hover:text-red-500 transition group/btn">
                                    <i class="fas fa-heart"></i> <span class="text-sm" id="love-count-<?php echo $q['id']; ?>"><?php echo $q['love_count']; ?></span>
                                </button>
                                <button onclick="react(<?php echo $q['id']; ?>, 'like')" class="flex items-center gap-1 text-gray-500 hover:text-blue-500 transition">
                                    <i class="fas fa-thumbs-up"></i> <span class="text-sm" id="like-count-<?php echo $q['id']; ?>"><?php echo $q['like_count']; ?></span>
                                </button>
                                <button onclick="react(<?php echo $q['id']; ?>, 'sad')" class="flex items-center gap-1 text-gray-500 hover:text-yellow-600 transition">
                                    <i class="fas fa-sad-tear"></i> <span class="text-sm" id="sad-count-<?php echo $q['id']; ?>"><?php echo $q['sad_count']; ?></span>
                                </button>
                                <button onclick="react(<?php echo $q['id']; ?>, 'laugh')" class="flex items-center gap-1 text-gray-500 hover:text-orange-500 transition">
                                    <i class="fas fa-laugh-squint"></i> <span class="text-sm" id="laugh-count-<?php echo $q['id']; ?>"><?php echo $q['laugh_count']; ?></span>
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="copyLink(<?php echo $q['id']; ?>)" class="text-gray-400 hover:text-gray-600 transition text-sm" title="Share via Link">
                                    <i class="fas fa-link"></i>
                                </button>
                                <button onclick="generateImage(<?php echo $q['id']; ?>)" class="text-gray-400 hover:text-gray-600 transition text-sm" title="Generate Image">
                                    <i class="fas fa-image"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo isset($_GET['sort']) ? '&sort='.$_GET['sort'] : ''; ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-lg <?php echo $page === $i ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?> transition">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Hidden element for Image Generation -->
    <div id="image-capture-template" class="fixed -left-[9999px] top-0 p-8 bg-white w-[500px]" style="z-index: -1; pointer-events: none;">
        <div class="border-2 border-gray-100 rounded-3xl p-10 bg-white modern-shadow">
            <div class="mb-8">
                <h1 class="text-sm font-bold text-blue-500 uppercase tracking-[0.2em] mb-4">AstralExpress</h1>
                <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                    <p id="capture-question" class="text-gray-800 text-xl font-bold leading-relaxed"></p>
                </div>
            </div>
            <div class="pl-6 border-l-4 border-gray-100">
                <p id="capture-answer" class="text-gray-600 text-lg leading-relaxed"></p>
            </div>
            <div class="mt-10 pt-6 border-t border-gray-50 flex justify-between items-center text-xs text-gray-400 font-medium">
                <span>Oleh Anonim</span>
                <span id="capture-date"></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle Mobile Menu
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Submit Question
        const askForm = document.getElementById('ask-form');
        const formMessage = document.getElementById('form-message');
        askForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const question = document.getElementById('question_text').value;

            try {
                const response = await fetch('api/submit_question.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `question=${encodeURIComponent(question)}`
                });
                const result = await response.json();

                formMessage.classList.remove('hidden', 'text-red-600', 'text-green-600');
                if (result.success) {
                    formMessage.textContent = 'Question sent successfully!';
                    formMessage.classList.add('text-green-600');
                    askForm.reset();
                } else {
                    formMessage.textContent = result.error || 'Something went wrong.';
                    formMessage.classList.add('text-red-600');
                }

                setTimeout(() => formMessage.classList.add('hidden'), 5000);
            } catch (error) {
                console.error(error);
            }
        });

        // React
        async function react(id, type) {
            try {
                const response = await fetch('api/react.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&type=${type}`
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById(`${type}-count-${id}`).textContent = result.new_count;
                }
            } catch (error) {
                console.error(error);
            }
        }

        // Copy Link
        function copyLink(id) {
            const url = `${window.location.origin}${window.location.pathname}?id=${id}`;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
        }

        // Generate Image
        function generateImage(id) {
            const card = document.getElementById(`q-card-${id}`);
            const question = card.querySelector('p.text-lg').textContent;
            const answer = card.querySelector('.mb-6 p').textContent;

            const template = document.getElementById('image-capture-template');
            document.getElementById('capture-question').textContent = question;
            document.getElementById('capture-answer').textContent = answer;
            document.getElementById('capture-date').textContent = new Date().toLocaleDateString();

            // Briefly show template to capture
            template.style.left = '0';

            html2canvas(template, {
                backgroundColor: '#ffffff',
                scale: 2,
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = `AMA-${id}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                template.style.left = '-9999px'; // Kembalikan jauh ke luar layar
            });
        }
    </script>
</body>
</html>
