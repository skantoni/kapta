<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('creator');
$user = current_user();
$page_title = 'Explorar Campanhas';

$platform_filter = isset($_GET['platform']) ? $_GET['platform'] : 'all';

// Fetch available campaigns
$query = "
    SELECT c.*, b.company_name, u.avatar,
           (SELECT COUNT(*) FROM campaign_submissions WHERE campaign_id = c.id AND creator_id = ?) as has_submitted
    FROM campaigns c
    JOIN brand_profiles b ON c.brand_id = b.user_id
    JOIN users u ON b.user_id = u.id
    WHERE c.status = 'active'
";

$params = [$user['id']];

if ($platform_filter !== 'all') {
    $query .= " AND JSON_CONTAINS(c.platforms, '\"$platform_filter\"')";
}

$query .= " ORDER BY c.cpm_rate DESC, c.created_at DESC";

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
            <a href="campaigns.php" class="nav-item active"><i class="ph ph-magnifying-glass"></i> Explorar Campanhas</a>
            <a href="earnings.php" class="nav-item"><i class="ph ph-money"></i> Ganhos & Carteira</a>
            <a href="submit.php" class="nav-item bg-white bg-opacity-5"><i class="ph ph-upload-simple"></i> Submeter Vídeo</a>
        </div>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Marketplace de Campanhas</h1>
            </div>
            <div class="topbar-actions">
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-secondary"></i>
                    <input type="text" placeholder="Procurar campanhas..." class="form-control pl-10 py-2 w-64 bg-dark-surface">
                </div>
            </div>
        </header>
        
        <div class="content-wrapper max-w-6xl mx-auto">
            
            <div class="mb-8 flex flex-wrap gap-4 items-center justify-between">
                <h2 class="text-xl font-outfit text-white">Descobre as melhores oportunidades</h2>
                
                <div class="flex bg-dark-surface p-1 rounded-lg border border-dark-border">
                    <a href="?platform=all" class="px-4 py-2 rounded-md text-sm <?php echo $platform_filter === 'all' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?>">Todas</a>
                    <a href="?platform=youtube" class="px-4 py-2 rounded-md text-sm <?php echo $platform_filter === 'youtube' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?> flex items-center gap-1"><i class="ph ph-youtube-logo text-red-500"></i> YouTube</a>
                    <a href="?platform=tiktok" class="px-4 py-2 rounded-md text-sm <?php echo $platform_filter === 'tiktok' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?> flex items-center gap-1"><i class="ph ph-tiktok-logo"></i> TikTok</a>
                    <a href="?platform=instagram" class="px-4 py-2 rounded-md text-sm <?php echo $platform_filter === 'instagram' ? 'bg-white bg-opacity-10 text-gold' : 'text-secondary hover:text-white'; ?> flex items-center gap-1"><i class="ph ph-instagram-logo text-purple-500"></i> Instagram</a>
                </div>
            </div>

            <?php if (empty($campaigns)): ?>
                <div class="glass-card empty-state">
                    <div class="empty-state-icon"><i class="ph ph-magnifying-glass"></i></div>
                    <h3 class="empty-state-title">Nenhuma campanha encontrada</h3>
                    <p class="empty-state-desc">Neste momento não há campanhas disponíveis com este filtro. Volte mais tarde!</p>
                </div>
            <?php else: ?>
                <div class="campaign-grid">
                    <?php foreach ($campaigns as $camp): 
                        $platforms = json_decode($camp['platforms'], true) ?? [];
                        $progress = ($camp['budget'] > 0) ? min(100, ($camp['spent'] / $camp['budget']) * 100) : 0;
                    ?>
                        <div class="glass-card campaign-card p-6 border-t-4 <?php echo $camp['has_submitted'] ? 'border-t-dark-surface opacity-70' : 'border-t-gold'; ?>">
                            <div class="flex justify-between items-start mb-4">
                                <div class="brand-info">
                                    <div class="brand-logo text-white bg-black">
                                        <?php echo strtoupper(substr($camp['company_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($camp['company_name']); ?></div>
                                        <div class="text-xs text-secondary"><?php echo htmlspecialchars($camp['category']); ?></div>
                                    </div>
                                </div>
                                <?php if ($camp['has_submitted']): ?>
                                    <span class="status-badge bg-dark-surface border-dark-border text-secondary"><i class="ph ph-check mr-1"></i> Já Submetido</span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="campaign-title text-lg mb-2 truncate" title="<?php echo htmlspecialchars($camp['title']); ?>">
                                <?php echo htmlspecialchars($camp['title']); ?>
                            </h3>
                            
                            <div class="campaign-platforms mb-4">
                                <?php if (in_array('youtube', $platforms)): ?>
                                    <span class="text-lg text-red-500 bg-red-500 bg-opacity-10 w-8 h-8 rounded flex items-center justify-center" title="YouTube"><i class="ph ph-youtube-logo"></i></span>
                                <?php endif; ?>
                                <?php if (in_array('tiktok', $platforms)): ?>
                                    <span class="text-lg text-white bg-white bg-opacity-10 w-8 h-8 rounded flex items-center justify-center" title="TikTok"><i class="ph ph-tiktok-logo"></i></span>
                                <?php endif; ?>
                                <?php if (in_array('instagram', $platforms)): ?>
                                    <span class="text-lg text-purple-500 bg-purple-500 bg-opacity-10 w-8 h-8 rounded flex items-center justify-center" title="Instagram"><i class="ph ph-instagram-logo"></i></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-black bg-opacity-30 p-3 rounded-lg border border-dark-border mb-4">
                                <div class="text-xs text-secondary text-center uppercase tracking-wider mb-1">Ganha</div>
                                <div class="text-2xl font-bold text-center text-gold drop-shadow-sm font-outfit">
                                    <?php echo format_kz($camp['cpm_rate']); ?> <span class="text-xs text-secondary font-normal block mt-1 lowercase">por cada 1.000 views</span>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-secondary">Orçamento Disponível</span>
                                    <span class="font-medium text-white"><?php echo format_kz($camp['budget'] - $camp['spent']); ?></span>
                                </div>
                                <div class="progress-container h-1.5">
                                    <div class="progress-bar bg-dark-surface" style="width: 100%; position: relative;">
                                        <div class="absolute left-0 top-0 bottom-0 bg-gold" style="width: <?php echo 100 - $progress; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-xs text-secondary flex items-center gap-1 mb-4 border-t border-dark-border pt-4">
                                <i class="ph ph-calendar"></i> Termina a <?php echo date('d/m/Y', strtotime($camp['deadline'])); ?>
                                <?php if ($camp['min_followers'] > 0): ?>
                                    <span class="mx-2 text-dark-border">|</span>
                                    <i class="ph ph-users"></i> Min: <?php echo number_format($camp['min_followers'], 0, ',', '.'); ?> seg.
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-auto pt-2">
                                <?php if ($camp['has_submitted']): ?>
                                    <a href="dashboard.php" class="btn btn-secondary w-full justify-center opacity-70">Ver Submissão</a>
                                <?php else: ?>
                                    <a href="submit.php?id=<?php echo $camp['id']; ?>" class="btn btn-primary w-full justify-center font-bold">Candidatar-me <i class="ph ph-arrow-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../assets/js/dashboard.js"></script>
<?php include '../includes/footer.php'; ?>
