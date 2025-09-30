<?php
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_data($pdo, $user_id);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        $error = "Nama lengkap dan email wajib diisi";
    } else {
        try {
            // Update data pengguna
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            
            $success = "Profil berhasil diperbarui";
            $user = get_user_data($pdo, $user_id); // Refresh data user
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email sudah digunakan oleh akun lain";
            } else {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - TaskFlow</title>
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
                    <li><a href="profile.php" class="active">Profil</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <div class="profile-container">
            <div class="profile-header">
                <h1><i class="fas fa-user"></i> Profil Pengguna</h1>
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

            <div class="profile-content">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <div class="avatar-large">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
                                $stmt->execute([$user_id]);
                                $total_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                echo $total_tasks;
                                ?>
                            </div>
                            <div class="stat-label">Total Tugas</div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-value">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status = 'completed'");
                                $stmt->execute([$user_id]);
                                $completed_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                echo $completed_tasks;
                                ?>
                            </div>
                            <div class="stat-label">Tugas Selesai</div>
                        </div>

                        <div class="stat-item">
                            <div class="stat-value">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status = 'in_progress'");
                                $stmt->execute([$user_id]);
                                $in_progress_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                echo $in_progress_tasks;
                                ?>
                            </div>
                            <div class="stat-label">Dalam Proses</div>
                        </div>
                    </div>
                </div>

                <div class="profile-form-container">
                    <h2><i class="fas fa-edit"></i> Edit Profil</h2>
                    
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small>Username tidak dapat diubah</small>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn">Perbarui Profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>