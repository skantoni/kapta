<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_name(SESSION_NAME);
session_start();

if (is_logged_in()) {
    redirect(APP_URL);
}

$error = '';
$step = isset($_GET['role']) ? 2 : 1;
$role = isset($_GET['role']) && in_array($_GET['role'], ['brand', 'creator']) ? $_GET['role'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (!$name || !$email || !$password || !$role) {
        $error = 'Por favor preencha todos os campos obrigatórios.';
    } elseif ($password !== $password_confirm) {
        $error = 'As palavras-passe não coincidem.';
    } elseif (strlen($password) < 6) {
        $error = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Check email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este email já está registado.';
            } else {
                $pdo->beginTransaction();
                
                // Insert User
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $role]);
                $user_id = $pdo->lastInsertId();
                
                // Insert Profile
                if ($role === 'brand') {
                    $company = filter_input(INPUT_POST, 'company_name', FILTER_SANITIZE_STRING);
                    $stmt = $pdo->prepare("INSERT INTO brand_profiles (user_id, company_name) VALUES (?, ?)");
                    $stmt->execute([$user_id, $company ?: $name]);
                } else {
                    $tiktok = filter_input(INPUT_POST, 'tiktok_handle', FILTER_SANITIZE_STRING);
                    $youtube = filter_input(INPUT_POST, 'youtube_channel', FILTER_SANITIZE_STRING);
                    $stmt = $pdo->prepare("INSERT INTO creator_profiles (user_id, tiktok_handle, youtube_channel) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $tiktok, $youtube]);
                }
                
                // Create Wallet
                $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                
                // Auto login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                
                flash('Conta criada com sucesso! Bem-vindo à Kapta.', 'success');
                
                if ($role === 'brand') redirect(APP_URL . '/brand/dashboard.php');
                else redirect(APP_URL . '/creator/dashboard.php');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erro ao criar conta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <canvas id="auth-canvas"></canvas>
    
    <div class="auth-container" style="max-width: <?php echo $step == 1 ? '800px' : '500px'; ?>">
        <a href="../index.php" class="auth-logo gradient-text">Kapta.</a>
        
        <div class="glass-card auth-card">
            <?php if ($error): ?>
                <div class="alert alert-error mb-4">
                    <i class="ph ph-warning-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- STEP 1: CHOOSE ROLE -->
                <div class="text-center mb-8">
                    <h1 class="auth-title">Junte-se à Kapta</h1>
                    <p class="auth-subtitle">Como pretende utilizar a plataforma?</p>
                </div>
                
                <div class="role-selector">
                    <a href="?role=brand" class="role-card">
                        <div class="role-icon">
                            <i class="ph ph-storefront"></i>
                        </div>
                        <h3 class="role-title">Sou uma Marca</h3>
                        <p class="role-desc">Quero criar campanhas e pagar por views reais geradas por creators.</p>
                        <div class="role-btn">Criar Conta Marca <i class="ph ph-arrow-right"></i></div>
                    </a>
                    
                    <a href="?role=creator" class="role-card">
                        <div class="role-icon">
                            <i class="ph ph-video-camera"></i>
                        </div>
                        <h3 class="role-title">Sou um Creator</h3>
                        <p class="role-desc">Quero clipar vídeos, postar nas minhas redes e ganhar Kz por cada view.</p>
                        <div class="role-btn">Criar Conta Creator <i class="ph ph-arrow-right"></i></div>
                    </a>
                </div>
                
                <div class="auth-footer mt-8">
                    Já tem conta? <a href="login.php" class="auth-link">Entre aqui</a>
                </div>
                
            <?php else: ?>
                <!-- STEP 2: REGISTER FORM -->
                <div class="mb-6 flex items-center">
                    <a href="register.php" class="text-secondary hover:text-white transition mr-4">
                        <i class="ph ph-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="auth-title mb-1">
                            <?php echo $role === 'brand' ? 'Conta Marca' : 'Conta Creator'; ?>
                        </h1>
                        <p class="auth-subtitle mb-0">Preencha os seus dados para começar</p>
                    </div>
                </div>
                
                <form method="POST" action="register.php?role=<?php echo $role; ?>" class="auth-form">
                    <input type="hidden" name="role" value="<?php echo $role; ?>">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nome Completo</label>
                        <div class="input-icon">
                            <i class="ph ph-user"></i>
                            <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-icon">
                            <i class="ph ph-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <?php if ($role === 'brand'): ?>
                        <div class="form-group">
                            <label for="company_name" class="form-label">Nome da Empresa</label>
                            <div class="input-icon">
                                <i class="ph ph-buildings"></i>
                                <input type="text" id="company_name" name="company_name" class="form-control" required value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="tiktok_handle" class="form-label">@ TikTok (opcional)</label>
                                <div class="input-icon">
                                    <i class="ph ph-tiktok-logo"></i>
                                    <input type="text" id="tiktok_handle" name="tiktok_handle" class="form-control" placeholder="@username">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="youtube_channel" class="form-label">YouTube (opcional)</label>
                                <div class="input-icon">
                                    <i class="ph ph-youtube-logo"></i>
                                    <input type="text" id="youtube_channel" name="youtube_channel" class="form-control" placeholder="@channel">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="password" class="form-label">Palavra-passe</label>
                            <div class="input-icon">
                                <i class="ph ph-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Confirmar</label>
                            <div class="input-icon">
                                <i class="ph ph-lock-key"></i>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block mt-4">Criar Conta <i class="ph ph-arrow-right"></i></button>
                </form>
                
                <div class="auth-footer">
                    Já tem conta? <a href="login.php" class="auth-link">Entre aqui</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
