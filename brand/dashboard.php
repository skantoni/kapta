<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('brand');

$user = current_user();
$page_title = 'Dashboard Marca';

// Fetch stats
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$wallet = $stmt->fetch();
$balance = $wallet['balance'] ?? 0;

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_campaigns,
        SUM(budget) as total_budget,
        SUM(spent) as total_spent
    FROM campaigns 
    WHERE brand_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT creator_id) as total_creators, IFNULL(SUM(views), 0) as total_views 
    FROM campaign_submissions cs
    JOIN campaigns c ON cs.campaign_id = c.id
    WHERE c.brand_id = ? AND cs.status = 'approved'
");
$stmt->execute([$user['id']]);
$perf_stats = $stmt->fetch();

// Recent campaigns
$stmt = $pdo->prepare("
    SELECT id, title, platforms, cpm_rate, budget, spent, status, created_at 
    FROM campaigns 
    WHERE brand_id = ? 
    ORDER BY created_at DESC 
    LIMIT 4
");
$stmt->execute([$user['id']]);
$recent_campaigns = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="campaigns.php" class="nav-item"><i class="ph ph-megaphone"></i> Campanhas</a>
            <a href="campaign-create.php" class="nav-item"><i class="ph ph-plus-circle"></i> Criar Campanha</a>
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

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Dashboard</h1>
            </div>
            <div class="topbar-actions">
                <div class="wallet-badge">
                    <i class="ph ph-coins"></i> <?php echo format_kz($balance); ?>
                </div>
                <a href="campaign-create.php" class="btn btn-primary text-sm py-2"><i class="ph ph-plus"></i> Nova</a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> mb-6">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="glass-card stat-card">
                    <i class="ph ph-chart-line-up stat-icon"></i>
                    <div class="stat-title">Total Investido</div>
                    <div class="stat-value animate-number" data-value="<?php echo $stats['total_spent'] ?? 0; ?>" data-format="kz">Kz 0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-eye stat-icon"></i>
                    <div class="stat-title">Total de Views</div>
                    <div class="stat-value animate-number gold" data-value="<?php echo $perf_stats['total_views'] ?? 0; ?>" data-format="views">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-users stat-icon"></i>
                    <div class="stat-title">Creators Ativos</div>
                    <div class="stat-value animate-number" data-value="<?php echo $perf_stats['total_creators'] ?? 0; ?>">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-megaphone stat-icon"></i>
                    <div class="stat-title">Campanhas</div>
                    <div class="stat-value animate-number" data-value="<?php echo $stats['total_campaigns'] ?? 0; ?>">0</div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold font-outfit">Campanhas Recentes</h2>
                        <a href="campaigns.php" class="text-gold text-sm hover:underline">Ver todas</a>
                    </div>
                    
                    <?php if (empty($recent_campaigns)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="ph ph-megaphone"></i></div>
                            <h3 class="empty-state-title">Sem Campanhas</h3>
                            <p class="empty-state-desc">Ainda não criou nenhuma campanha. Comece agora a conectar-se com creators.</p>
                            <a href="campaign-create.php" class="btn btn-primary">Criar Primeira Campanha</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Campanha</th>
                                        <th>CPM</th>
                                        <th>Gasto / Orçamento</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_campaigns as $camp): ?>
                                    <tr>
                                        <td class="font-medium"><?php echo htmlspecialchars($camp['title']); ?></td>
                                        <td class="text-gold"><?php echo format_kz($camp['cpm_rate']); ?></td>
                                        <td>
                                            <div class="text-sm"><?php echo format_kz($camp['spent']); ?> / <?php echo format_kz($camp['budget']); ?></div>
                                            <div class="progress-container h-1 mt-1">
                                                <div class="progress-bar" style="width: <?php echo ($camp['budget'] > 0) ? min(100, ($camp['spent'] / $camp['budget']) * 100) : 0; ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $camp['status']; ?>">
                                                <?php 
                                                    if ($camp['status'] == 'active') echo 'Activa';
                                                    elseif ($camp['status'] == 'paused') echo 'Pausada';
                                                    elseif ($camp['status'] == 'completed') echo 'Concluída';
                                                    else echo 'Rascunho';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="campaign-detail.php?id=<?php echo $camp['id']; ?>" class="text-secondary hover:text-white"><i class="ph ph-eye"></i> Ver</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="glass-card p-6">
                    <h2 class="text-xl font-bold font-outfit mb-6">Resumo da Carteira</h2>
                    
                    <div class="text-center py-6">
                        <div class="text-sm text-secondary mb-2">Saldo Disponível</div>
                        <div class="text-3xl font-bold text-gold mb-6"><?php echo format_kz($balance); ?></div>
                        
                        <a href="wallet.php" class="btn btn-secondary w-full justify-center mb-4"><i class="ph ph-plus"></i> Adicionar Fundos</a>
                        
                        <?php if ($balance > 0): ?>
                            <a href="campaign-create.php" class="btn btn-primary w-full justify-center"><i class="ph ph-megaphone"></i> Nova Campanha</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
