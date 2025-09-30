<?php
require_once 'config.php';

// Redirect jika sudah login
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi";
    } else {
        // Cek user di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Username atau password salah";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TaskFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(99, 102, 241, 0.1);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header .logo {
            font-size: 2.5rem;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            color: var(--light);
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--gray);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="#" class="logo">
                    <i class="fas fa-tasks"></i>
                    Task<span>Flow</span>
                </a>
                <h1>Selamat Datang Kembali</h1>
                <p>Silakan masuk ke akun Anda</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn" style="width: 100%">Masuk</button>
                </div>
            </form>

<div class="login-footer" style="text-align: center; margin-top: 20px;">
    <p style="color: var(--gray);">Demo Login: username: <strong>johannes</strong>, password: <strong>password</strong></p>
</div>
        </div>
    </div>
</body>
</html>