<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('brand');
$user = current_user();
$page_title = 'Criar Campanha';

// Get current wallet balance
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$wallet = $stmt->fetch();
$balance = $wallet['balance'] ?? 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? htmlspecialchars(strip_tags($_POST['title'])) : '';
    $description = isset($_POST['description']) ? htmlspecialchars(strip_tags($_POST['description'])) : '';
    $category = isset($_POST['category']) ? htmlspecialchars(strip_tags($_POST['category'])) : '';
    $cpm_rate = filter_input(INPUT_POST, 'cpm_rate', FILTER_VALIDATE_FLOAT);
    $budget = filter_input(INPUT_POST, 'budget', FILTER_VALIDATE_FLOAT);
    $min_followers = filter_input(INPUT_POST, 'min_followers', FILTER_VALIDATE_INT) ?: 0;
    $deadline = $_POST['deadline'] ?? '';
    
    $platforms = $_POST['platforms'] ?? [];
    
    if (!$title || !$description || !$cpm_rate || !$budget || empty($platforms) || !$deadline) {
        $error = 'Por favor, preencha todos os campos obrigatórios e selecione pelo menos uma plataforma.';
    } elseif ($budget > $balance) {
        $error = 'Saldo insuficiente. Tem ' . format_kz($balance) . ' disponíveis. Por favor carregue a carteira.';
    } elseif ($budget < 1000) {
        $error = 'O orçamento mínimo é de Kz 1.000,00.';
    } elseif ($cpm_rate <= 0) {
        $error = 'A taxa CPM deve ser superior a zero.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Deduct budget from wallet (provisioning)
            $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND balance >= ?");
            $stmt->execute([$budget, $user['id'], $budget]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Falha ao cativar saldo. Verifique o seu saldo disponível.");
            }
            
            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, user_id, type, amount, description, status) VALUES ((SELECT id FROM wallets WHERE user_id = ?), ?, 'campaign_fund', ?, ?, 'completed')");
            $stmt->execute([$user['id'], $user['id'], -$budget, "Provisão para campanha: " . $title]);
            
            // Create campaign
            $platforms_json = json_encode($platforms);
            $stmt = $pdo->prepare("
                INSERT INTO campaigns 
                (brand_id, title, description, category, platforms, cpm_rate, budget, min_followers, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([
                $user['id'], $title, $description, $category, $platforms_json, 
                $cpm_rate, $budget, $min_followers, $deadline
            ]);
            
            $campaign_id = $pdo->lastInsertId();
            
            $pdo->commit();
            
            flash('Campanha criada com sucesso! O orçamento foi cativado da sua carteira.', 'success');
            redirect("campaign-detail.php?id=" . $campaign_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Erro: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="campaigns.php" class="nav-item"><i class="ph ph-megaphone"></i> Campanhas</a>
            <a href="campaign-create.php" class="nav-item active"><i class="ph ph-plus-circle"></i> Criar Campanha</a>
            <a href="wallet.php" class="nav-item"><i class="ph ph-wallet"></i> Carteira</a>
        </div>
        <div class="sidebar-footer">
            <div class="user-profile mb-4">
                <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Marca</div>
                </div>
            </div>
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Criar Campanha</h1>
            </div>
            <div class="topbar-actions">
                <div class="wallet-badge">
                    <i class="ph ph-coins"></i> Saldo: <?php echo format_kz($balance); ?>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper max-w-4xl mx-auto">
            <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <i class="ph ph-warning-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($balance <= 0): ?>
                <div class="glass-card p-6 border-l-4 border-gold bg-gold bg-opacity-10 mb-8">
                    <div class="flex items-start">
                        <i class="ph ph-warning text-gold text-2xl mr-4 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">A sua carteira está vazia</h3>
                            <p class="text-secondary mb-4">É necessário ter saldo na carteira para criar uma campanha. O orçamento da campanha será cativado do seu saldo.</p>
                            <a href="wallet.php" class="btn btn-primary"><i class="ph ph-plus"></i> Adicionar Fundos</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="glass-card p-8">
                <div class="mb-8 border-b border-dark-border pb-6">
                    <h2 class="text-xl font-bold font-outfit mb-2">Detalhes Básicos</h2>
                    <p class="text-secondary text-sm">Descreva o que os creators devem fazer na sua campanha.</p>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Título da Campanha *</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Ex: Divulgação do novo Produto X" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="form-label">Categoria *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="Tecnologia">Tecnologia & Gadgets</option>
                            <option value="Moda">Moda & Beleza</option>
                            <option value="Gaming">Gaming & eSports</option>
                            <option value="Lifestyle">Lifestyle & Vlogs</option>
                            <option value="Comida">Comida & Bebida</option>
                            <option value="Negócios">Negócios & Finanças</option>
                            <option value="Educação">Educação</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="deadline" class="form-label">Data Limite de Entregas *</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" min="<?php echo date('Y-m-d'); ?>" required value="<?php echo isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Briefing para Creators *</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Instruções claras sobre o que o vídeo deve conter, o que não pode ser dito, links a usar, etc." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-8 mt-10 border-b border-dark-border pb-6">
                    <h2 class="text-xl font-bold font-outfit mb-2">Plataformas Aceites</h2>
                    <p class="text-secondary text-sm">Selecione onde os creators podem publicar o conteúdo.</p>
                </div>

                <div class="form-group">
                    <div class="checkbox-card-grid">
                        <label class="checkbox-card">
                            <input type="checkbox" name="platforms[]" value="youtube" checked>
                            <div class="checkbox-card-content">
                                <i class="ph ph-youtube-logo text-red-500"></i>
                                <span>YouTube Shorts</span>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="platforms[]" value="tiktok" checked>
                            <div class="checkbox-card-content">
                                <i class="ph ph-tiktok-logo text-white"></i>
                                <span>TikTok</span>
                            </div>
                        </label>
                        
                        <label class="checkbox-card">
                            <input type="checkbox" name="platforms[]" value="instagram" checked>
                            <div class="checkbox-card-content">
                                <i class="ph ph-instagram-logo text-purple-500"></i>
                                <span>Instagram Reels</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-8 mt-10 border-b border-dark-border pb-6">
                    <h2 class="text-xl font-bold font-outfit mb-2">Orçamento & Performance</h2>
                    <p class="text-secondary text-sm">Defina quanto paga por resultados.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpm_rate" class="form-label flex justify-between">
                            <span>Taxa CPM *</span>
                            <span class="text-xs text-gold">Valor a pagar por cada 1.000 views</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-text">Kz</div>
                            <input type="number" step="0.01" min="1" id="cpm_rate" name="cpm_rate" class="form-control" placeholder="Ex: 1000" required value="<?php echo isset($_POST['cpm_rate']) ? htmlspecialchars($_POST['cpm_rate']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget" class="form-label flex justify-between">
                            <span>Orçamento Total *</span>
                            <span class="text-xs text-secondary">Será cativado do seu saldo</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-text">Kz</div>
                            <input type="number" step="0.01" min="1000" max="<?php echo $balance; ?>" id="budget" name="budget" class="form-control" placeholder="Ex: 50000" required value="<?php echo isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : ''; ?>">
                        </div>
                        <div id="views_estimate" class="text-sm mt-2 text-secondary"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="min_followers" class="form-label">Mínimo de Seguidores (Opcional)</label>
                    <input type="number" min="0" id="min_followers" name="min_followers" class="form-control" placeholder="Deixe em 0 para não exigir um mínimo" value="<?php echo isset($_POST['min_followers']) ? htmlspecialchars($_POST['min_followers']) : '0'; ?>">
                </div>

                <div class="mt-10 flex justify-end">
                    <a href="campaigns.php" class="btn btn-secondary mr-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary" <?php echo $balance <= 0 ? 'disabled' : ''; ?>>
                        <i class="ph ph-check-circle"></i> Criar Campanha
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>


<?php include '../includes/footer.php'; ?>
