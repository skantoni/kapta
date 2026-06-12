<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('admin');
$user = current_user();
$page_title = 'Painel Admin';

// Platform stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM campaigns");
$total_campaigns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(views) as total FROM campaign_submissions WHERE status IN ('approved', 'active')");
$total_views = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE type = 'platform_fee' AND amount < 0");
$platform_revenue = abs($stmt->fetchColumn() ?: 0);

// Recent users
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// System check
$system_warnings = [];
if (empty(YOUTUBE_API_KEY)) {
    $system_warnings[] = 'A chave YOUTUBE_API_KEY não está configurada em config.php. As views reais não serão sincronizadas.';
}

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
            <span class="ml-2 text-xs text-red-500 border border-red-500 rounded px-1">ADMIN</span>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="#" class="nav-item text-secondary cursor-not-allowed" title="Em desenvolvimento"><i class="ph ph-users"></i> Utilizadores</a>
            <a href="#" class="nav-item text-secondary cursor-not-allowed" title="Em desenvolvimento"><i class="ph ph-megaphone"></i> Campanhas</a>
        </div>
        <div class="sidebar-footer">
            <div class="user-profile mb-4">
                <div class="user-avatar bg-red-600"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Administrador</div>
                </div>
            </div>
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Administração do Sistema</h1>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php foreach ($system_warnings as $warning): ?>
                <div class="alert alert-warning mb-6">
                    <i class="ph ph-warning-circle"></i>
                    <?php echo $warning; ?>
                </div>
            <?php endforeach; ?>

            <div class="stats-grid">
                <div class="glass-card stat-card">
                    <i class="ph ph-users stat-icon"></i>
                    <div class="stat-title">Total Utilizadores</div>
                    <div class="stat-value animate-number" data-value="<?php echo $total_users; ?>">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-megaphone stat-icon"></i>
                    <div class="stat-title">Campanhas Criadas</div>
                    <div class="stat-value animate-number" data-value="<?php echo $total_campaigns; ?>">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-eye stat-icon"></i>
                    <div class="stat-title">Views Plataforma</div>
                    <div class="stat-value animate-number" data-value="<?php echo $total_views; ?>" data-format="views">0</div>
                </div>
                
                <div class="glass-card stat-card border border-gold">
                    <i class="ph ph-money stat-icon"></i>
                    <div class="stat-title">Receita (Taxas 10%)</div>
                    <div class="stat-value animate-number text-gold" data-value="<?php echo $platform_revenue; ?>" data-format="kz">Kz 0</div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <div class="glass-card p-6">
                    <h2 class="text-xl font-bold font-outfit mb-6">Últimos Registos</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td class="font-medium text-white"><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $u['role'] == 'brand' ? 'active' : ($u['role'] == 'creator' ? 'approved' : 'paused'); ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/dashboard.js"></script>
<?php include '../includes/footer.php'; ?>
