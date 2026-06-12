<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_name(SESSION_NAME);
session_start();

// If already logged in, redirect
if (is_logged_in()) {
    $user = current_user();
    if ($user['role'] === 'brand') redirect(APP_URL . '/brand/dashboard.php');
    if ($user['role'] === 'creator') redirect(APP_URL . '/creator/dashboard.php');
    if ($user['role'] === 'admin') redirect(APP_URL . '/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (!$email || !$password) {
        $error = 'Por favor preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                flash('Bem-vindo de volta, ' . $user['name'] . '!', 'success');
                
                if ($user['role'] === 'brand') redirect(APP_URL . '/brand/dashboard.php');
                elseif ($user['role'] === 'creator') redirect(APP_URL . '/creator/dashboard.php');
                elseif ($user['role'] === 'admin') redirect(APP_URL . '/admin/dashboard.php');
            } else {
                $error = 'Credenciais inválidas. Tente novamente.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <canvas id="auth-canvas"></canvas>
    
    <div class="auth-container">
        <a href="../index.php" class="auth-logo gradient-text">Kapta.</a>
        
        <div class="glass-card auth-card">
            <h1 class="auth-title">Bem-vindo de volta</h1>
            <p class="auth-subtitle">Aceda à sua conta Kapta</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="ph ph-warning-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-icon">
                        <i class="ph ph-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="flex justify-between">
                        <label for="password" class="form-label">Palavra-passe</label>
                        <a href="#" class="auth-link small">Esqueceu-se?</a>
                    </div>
                    <div class="input-icon">
                        <i class="ph ph-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Manter sessão iniciada</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Entrar <i class="ph ph-arrow-right"></i></button>
            </form>
            
            <div class="auth-footer">
                Não tem conta? <a href="register.php" class="auth-link">Registe-se agora</a>
            </div>
            
            <!-- DEMO CREDENTIALS -->
            <div class="demo-creds mt-6 p-4 rounded bg-black bg-opacity-50 border border-gray-800 text-xs text-gray-400">
                <p class="font-bold mb-2 text-white"><i class="ph ph-info"></i> Contas de Demo:</p>
                <ul class="space-y-1">
                    <li><strong>Marca:</strong> marca@demo.ao / demo123</li>
                    <li><strong>Creator:</strong> creator@demo.ao / demo123</li>
                    <li><strong>Admin:</strong> admin@kapta.ao / admin123</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
