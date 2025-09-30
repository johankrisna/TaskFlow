<?php
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($pdo, $user_id);

// Ambil filter dari URL
$filter = $_GET['filter'] ?? 'all';
$category = $_GET['category'] ?? '';

// Query tasks berdasarkan filter
$query = "SELECT * FROM tasks WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($filter === 'completed') {
    $query .= " AND status = 'completed'";
} elseif ($filter === 'in_progress') {
    $query .= " AND status = 'in_progress'";
} elseif ($filter === 'pending') {
    $query .= " AND status = 'pending'";
} elseif ($filter === 'high') {
    $query .= " AND priority = 'high'";
}

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high
    FROM tasks WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung per kategori
$stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY category");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Sistem Manajemen Tugas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <a href="index.php" class="logo">
                <i class="fas fa-tasks"></i>
                Task<span>Flow</span>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Beranda</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <div class="app-container">
            <aside class="sidebar">
                <div class="user-info">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <h2><i class="fas fa-filter"></i> Filter Tugas</h2>
                <div class="filters">
                    <a href="index.php" class="filter-btn <?php echo $filter === 'all' && empty($category) ? 'active' : ''; ?>">
                        <i class="fas fa-inbox"></i> Semua Tugas
                        <span class="count"><?php echo $stats['total']; ?></span>
                    </a>
                    <a href="index.php?filter=high" class="filter-btn <?php echo $filter === 'high' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Prioritas Tinggi
                        <span class="count"><?php echo $stats['high']; ?></span>
                    </a>
                    <a href="index.php?filter=completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Selesai
                        <span class="count"><?php echo $stats['completed']; ?></span>
                    </a>
                    <a href="index.php?filter=in_progress" class="filter-btn <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">
                        <i class="fas fa-spinner"></i> Dalam Proses
                        <span class="count"><?php echo $stats['in_progress']; ?></span>
                    </a>
                    <a href="index.php?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Tertunda
                        <span class="count"><?php echo $stats['pending']; ?></span>
                    </a>
                </div>

                <h2 style="margin-top: 30px;"><i class="fas fa-layer-group"></i> Kategori</h2>
                <div class="filters">
                    <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?php echo urlencode($cat['category']); ?>" class="filter-btn <?php echo $category === $cat['category'] ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            switch($cat['category']) {
                                case 'Pekerjaan': echo 'briefcase'; break;
                                case 'Personal': echo 'home'; break;
                                case 'Belanja': echo 'shopping-cart'; break;
                                case 'Kesehatan': echo 'heart'; break;
                                case 'Pendidikan': echo 'book'; break;
                                default: echo 'circle';
                            }
                        ?>"></i> 
                        <?php echo htmlspecialchars($cat['category']); ?>
                        <span class="count"><?php echo $cat['count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <main class="main-content">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="value"><?php echo $stats['total']; ?></div>
                        <div class="label">Total Tugas</div>
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $stats['completed']; ?></div>
                        <div class="label">Tugas Selesai</div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $stats['in_progress']; ?></div>
                        <div class="label">Dalam Proses</div>
                        <div class="icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $stats['high']; ?></div>
                        <div class="label">Prioritas Tinggi</div>
                        <div class="icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>

                <section class="tasks-section">
                    <div class="section-header">
                        <h2><i class="fas fa-list-check"></i> Daftar Tugas</h2>
                        <a href="edit.php?action=add" class="btn">
                            <i class="fas fa-plus"></i> Tambah Tugas
                        </a>
                    </div>

                    <div class="tasks-list" id="tasksList">
                        <?php if (count($tasks) > 0): ?>
                            <?php foreach ($tasks as $task): ?>
                            <div class="task-item <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>" data-id="<?php echo $task['id']; ?>">
                                <div class="task-checkbox <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="task-content">
                                    <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                    <p class="task-desc"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <div class="task-meta">
                                        <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($task['due_date'])); ?></span>
                                        <span><i class="fas fa-<?php 
                                            switch($task['category']) {
                                                case 'Pekerjaan': echo 'briefcase'; break;
                                                case 'Personal': echo 'home'; break;
                                                case 'Belanja': echo 'shopping-cart'; break;
                                                case 'Kesehatan': echo 'heart'; break;
                                                case 'Pendidikan': echo 'book'; break;
                                                default: echo 'circle';
                                            }
                                        ?>"></i> <?php echo $task['category']; ?></span>
                                    </div>
                                </div>
                                <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                    <?php 
                                        switch($task['priority']) {
                                            case 'high': echo 'Tinggi'; break;
                                            case 'medium': echo 'Sedang'; break;
                                            case 'low': echo 'Rendah'; break;
                                        }
                                    ?>
                                </span>
                                <span class="task-status status-<?php echo $task['status']; ?>">
                                    <?php 
                                        switch($task['status']) {
                                            case 'completed': echo 'Selesai'; break;
                                            case 'in_progress': echo 'Proses'; break;
                                            case 'pending': echo 'Tertunda'; break;
                                        }
                                    ?>
                                </span>
                                <div class="task-actions">
                                    <a href="detail.php?id=<?php echo $task['id']; ?>" class="action-btn view-btn" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $task['id']; ?>" class="action-btn edit-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete-btn" data-id="<?php echo $task['id']; ?>" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-tasks">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>Tidak ada tugas</h3>
                                <p>Belum ada tugas yang ditambahkan. Klik tombol "Tambah Tugas" untuk membuat tugas baru.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>

        <footer>
            <p>&copy; 2023 TaskFlow - Sistem Manajemen Tugas. Dibuat oleh Johannes Krisnawan Saputro</p>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>