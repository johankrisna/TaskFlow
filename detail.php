<?php
require_once 'config.php';
require_login();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data tugas
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: index.php');
    exit;
}

// Ambil riwayat tugas
$stmt = $pdo->prepare("SELECT th.*, u.full_name 
                       FROM task_history th 
                       JOIN users u ON th.performed_by = u.id 
                       WHERE th.task_id = ? 
                       ORDER BY th.performed_at DESC");
$stmt->execute([$task_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = get_user_data($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tugas - TaskFlow</title>
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
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <div class="detail-container">
            <div class="detail-header">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <div class="detail-actions">
                    <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn">
                        <i class="fas fa-edit"></i> Edit Tugas
                    </a>
                </div>
            </div>

            <div class="detail-content">
                <div class="task-detail-card">
                    <div class="task-header">
                        <h1><?php echo htmlspecialchars($task['title']); ?></h1>
                        <div class="task-meta-badges">
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
                                        case 'in_progress': echo 'Dalam Proses'; break;
                                        case 'pending': echo 'Tertunda'; break;
                                    }
                                ?>
                            </span>
                            <span class="task-category">
                                <i class="fas fa-<?php 
                                    switch($task['category']) {
                                        case 'Pekerjaan': echo 'briefcase'; break;
                                        case 'Personal': echo 'home'; break;
                                        case 'Belanja': echo 'shopping-cart'; break;
                                        case 'Kesehatan': echo 'heart'; break;
                                        case 'Pendidikan': echo 'book'; break;
                                        default: echo 'circle';
                                    }
                                ?>"></i> 
                                <?php echo $task['category']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="task-detail-body">
                        <div class="detail-section">
                            <h3><i class="fas fa-align-left"></i> Deskripsi</h3>
                            <p><?php echo nl2br(htmlspecialchars($task['description'] ?: 'Tidak ada deskripsi')); ?></p>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Tanggal Jatuh Tempo</label>
                                    <p><?php echo date('d F Y', strtotime($task['due_date'])); ?></p>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Kategori</label>
                                    <p><?php echo $task['category']; ?></p>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-flag"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Prioritas</label>
                                    <p class="priority-<?php echo $task['priority']; ?>">
                                        <?php 
                                            switch($task['priority']) {
                                                case 'high': echo 'Tinggi'; break;
                                                case 'medium': echo 'Sedang'; break;
                                                case 'low': echo 'Rendah'; break;
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Status</label>
                                    <p class="status-<?php echo $task['status']; ?>">
                                        <?php 
                                            switch($task['status']) {
                                                case 'completed': echo 'Selesai'; break;
                                                case 'in_progress': echo 'Dalam Proses'; break;
                                                case 'pending': echo 'Tertunda'; break;
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Dibuat Pada</label>
                                    <p><?php echo date('d F Y H:i', strtotime($task['created_at'])); ?></p>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Diperbarui Pada</label>
                                    <p><?php echo date('d F Y H:i', strtotime($task['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="task-history">
                    <h2><i class="fas fa-history"></i> Riwayat Tugas</h2>
                    
                    <?php if (count($history) > 0): ?>
                        <div class="history-list">
                            <?php foreach ($history as $item): ?>
                            <div class="history-item">
                                <div class="history-icon">
                                    <i class="fas fa-<?php 
                                        switch($item['action']) {
                                            case 'create': echo 'plus'; break;
                                            case 'update': echo 'edit'; break;
                                            case 'delete': echo 'trash'; break;
                                            case 'status_change': echo 'exchange-alt'; break;
                                            default: echo 'circle';
                                        }
                                    ?>"></i>
                                </div>
                                <div class="history-content">
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="history-meta">
                                        <span>Oleh: <?php echo htmlspecialchars($item['full_name']); ?></span>
                                        <span>•</span>
                                        <span><?php echo date('d F Y H:i', strtotime($item['performed_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-history">
                            <i class="fas fa-history"></i>
                            <p>Belum ada riwayat untuk tugas ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>