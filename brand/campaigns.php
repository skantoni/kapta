<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('brand');
$user = current_user();
$page_title = 'Campanhas';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$query = "
    SELECT c.*, 
        (SELECT COUNT(*) FROM campaign_submissions WHERE campaign_id = c.id) as submissions_count
    FROM campaigns c 
    WHERE c.brand_id = ?
";

$params = [$user['id']];

if ($status_filter !== 'all') {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="campaigns.php" class="nav-item active"><i class="ph ph-megaphone"></i> Campanhas</a>
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

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">As Minhas Campanhas</h1>
            </div>
            <div class="topbar-actions">
                <a href="campaign-create.php" class="btn btn-primary text-sm py-2"><i class="ph ph-plus"></i> Criar Campanha</a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> mb-6">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                <div class="flex bg-dark-surface p-1 rounded-lg border border-dark-border">
                    <a href="?status=all" class="px-4 py-2 rounded-md text-sm <?php echo $status_filter === 'all' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?>">Todas</a>
                    <a href="?status=active" class="px-4 py-2 rounded-md text-sm <?php echo $status_filter === 'active' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?>">Ativas</a>
                    <a href="?status=paused" class="px-4 py-2 rounded-md text-sm <?php echo $status_filter === 'paused' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?>">Pausadas</a>
                    <a href="?status=completed" class="px-4 py-2 rounded-md text-sm <?php echo $status_filter === 'completed' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?>">Concluídas</a>
                </div>
            </div>

            <?php if (empty($campaigns)): ?>
                <div class="glass-card empty-state">
                    <div class="empty-state-icon"><i class="ph ph-megaphone"></i></div>
                    <h3 class="empty-state-title">Nenhuma campanha encontrada</h3>
                    <p class="empty-state-desc">Ainda não tem campanhas com este estado. Crie uma nova campanha para começar a trabalhar com creators.</p>
                    <a href="campaign-create.php" class="btn btn-primary">Criar Nova Campanha</a>
                </div>
            <?php else: ?>
                <div class="campaign-grid">
                    <?php foreach ($campaigns as $camp): 
                        $platforms = json_decode($camp['platforms'], true) ?? [];
                        $progress = ($camp['budget'] > 0) ? min(100, ($camp['spent'] / $camp['budget']) * 100) : 0;
                    ?>
                        <div class="glass-card campaign-card p-5">
                            <div class="campaign-header">
                                <h3 class="campaign-title truncate pr-2" title="<?php echo htmlspecialchars($camp['title']); ?>">
                                    <?php echo htmlspecialchars($camp['title']); ?>
                                </h3>
                                <span class="status-badge <?php echo $camp['status']; ?> shrink-0">
                                    <?php 
                                        if ($camp['status'] == 'active') echo 'Activa';
                                        elseif ($camp['status'] == 'paused') echo 'Pausada';
                                        elseif ($camp['status'] == 'completed') echo 'Concluída';
                                        else echo 'Rascunho';
                                    ?>
                                </span>
                            </div>
                            
                            <div class="campaign-platforms">
                                <?php if (in_array('youtube', $platforms)): ?>
                                    <span class="badge badge-youtube"><i class="ph ph-youtube-logo"></i> YouTube</span>
                                <?php endif; ?>
                                <?php if (in_array('tiktok', $platforms)): ?>
                                    <span class="badge badge-tiktok"><i class="ph ph-tiktok-logo"></i> TikTok</span>
                                <?php endif; ?>
                                <?php if (in_array('instagram', $platforms)): ?>
                                    <span class="badge badge-instagram"><i class="ph ph-instagram-logo"></i> Instagram</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="campaign-cpm mt-2">
                                <?php echo format_kz($camp['cpm_rate']); ?> <span>por 1K views</span>
                            </div>
                            
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-secondary">Orçamento</span>
                                    <span class="font-medium"><?php echo format_kz($camp['spent']); ?> / <?php echo format_kz($camp['budget']); ?></span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="campaign-meta mt-2">
                                <div class="campaign-meta-item">
                                    <span>Submissões</span>
                                    <span class="campaign-meta-value"><i class="ph ph-video-camera text-gold mr-1"></i> <?php echo $camp['submissions_count']; ?></span>
                                </div>
                                <div class="campaign-meta-item">
                                    <span>Data Limite</span>
                                    <span class="campaign-meta-value"><i class="ph ph-calendar text-gold mr-1"></i> <?php echo date('d/m/Y', strtotime($camp['deadline'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="campaign-footer">
                                <a href="campaign-detail.php?id=<?php echo $camp['id']; ?>" class="btn btn-secondary w-full justify-center">Gerir Campanha</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
