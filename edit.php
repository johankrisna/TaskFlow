<?php
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($pdo, $user_id);

// Cek apakah ini edit atau tambah baru
$is_edit = isset($_GET['id']);
$task = null;

if ($is_edit) {
    $task_id = $_GET['id'];
    
    // Ambil data tugas
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        header('Location: index.php');
        exit;
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($title) || empty($due_date) || empty($category)) {
        $error = "Judul, tanggal jatuh tempo, dan kategori wajib diisi";
    } else {
        try {
            if ($is_edit) {
                // Update tugas
                $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, category = ?, priority = ?, status = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $description, $due_date, $category, $priority, $status, $task_id, $user_id]);
                
                // Catat riwayat
                $stmt = $pdo->prepare("INSERT INTO task_history (task_id, action, description, performed_by) VALUES (?, 'update', ?, ?)");
                $stmt->execute([$task_id, "Tugas '{$title}' diperbarui", $user_id]);
                
                $success = "Tugas berhasil diperbarui";
            } else {
                // Tambah tugas baru
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, due_date, category, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $description, $due_date, $category, $priority, $status]);
                $task_id = $pdo->lastInsertId();
                
                // Catat riwayat
                $stmt = $pdo->prepare("INSERT INTO task_history (task_id, action, description, performed_by) VALUES (?, 'create', ?, ?)");
                $stmt->execute([$task_id, "Tugas '{$title}' dibuat", $user_id]);
                
                $success = "Tugas berhasil ditambahkan";
            }
            
            // Redirect jika berhasil
            if (isset($success)) {
                header("Location: detail.php?id={$task_id}");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Tugas' : 'Tambah Tugas'; ?> - TaskFlow</title>
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

        <div class="edit-container">
            <div class="edit-header">
                <a href="<?php echo $is_edit ? 'detail.php?id='.$task['id'] : 'index.php'; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <h1><?php echo $is_edit ? 'Edit Tugas' : 'Tambah Tugas Baru'; ?></h1>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>

            <div class="edit-form-container">
                <form method="POST" class="edit-form">
                    <div class="form-group">
                        <label for="title">Judul Tugas *</label>
                        <input type="text" id="title" name="title" value="<?php echo $is_edit ? htmlspecialchars($task['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"><?php echo $is_edit ? htmlspecialchars($task['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="due_date">Tanggal Jatuh Tempo *</label>
                            <input type="date" id="due_date" name="due_date" value="<?php echo $is_edit ? $task['due_date'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Kategori *</label>
                            <select id="category" name="category" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Pekerjaan" <?php echo $is_edit && $task['category'] === 'Pekerjaan' ? 'selected' : ''; ?>>Pekerjaan</option>
                                <option value="Personal" <?php echo $is_edit && $task['category'] === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                                <option value="Belanja" <?php echo $is_edit && $task['category'] === 'Belanja' ? 'selected' : ''; ?>>Belanja</option>
                                <option value="Kesehatan" <?php echo $is_edit && $task['category'] === 'Kesehatan' ? 'selected' : ''; ?>>Kesehatan</option>
                                <option value="Pendidikan" <?php echo $is_edit && $task['category'] === 'Pendidikan' ? 'selected' : ''; ?>>Pendidikan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="priority">Prioritas</label>
                            <select id="priority" name="priority">
                                <option value="low" <?php echo $is_edit && $task['priority'] === 'low' ? 'selected' : ''; ?>>Rendah</option>
                                <option value="medium" <?php echo (!$is_edit) || ($is_edit && $task['priority'] === 'medium') ? 'selected' : ''; ?>>Sedang</option>
                                <option value="high" <?php echo $is_edit && $task['priority'] === 'high' ? 'selected' : ''; ?>>Tinggi</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="pending" <?php echo (!$is_edit) || ($is_edit && $task['status'] === 'pending') ? 'selected' : ''; ?>>Tertunda</option>
                                <option value="in_progress" <?php echo $is_edit && $task['status'] === 'in_progress' ? 'selected' : ''; ?>>Dalam Proses</option>
                                <option value="completed" <?php echo $is_edit && $task['status'] === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo $is_edit ? 'detail.php?id='.$task['id'] : 'index.php'; ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn"><?php echo $is_edit ? 'Perbarui Tugas' : 'Tambah Tugas'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set minimal tanggal ke hari ini
        document.getElementById('due_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>