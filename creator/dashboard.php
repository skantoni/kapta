<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('creator');
$user = current_user();
$page_title = 'Dashboard Creator';

// Fetch profile & stats
$stmt = $pdo->prepare("SELECT * FROM creator_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$wallet = $stmt->fetch();
$balance = $wallet['balance'] ?? 0;

// Submissions stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_submissions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_submissions,
        IFNULL(SUM(views), 0) as total_views,
        IFNULL(SUM(earnings), 0) as total_earnings
    FROM campaign_submissions 
    WHERE creator_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Recent active submissions
$stmt = $pdo->prepare("
    SELECT s.*, c.title as campaign_title, c.cpm_rate 
    FROM campaign_submissions s
    JOIN campaigns c ON s.campaign_id = c.id
    WHERE s.creator_id = ? AND s.status IN ('active', 'approved')
    ORDER BY s.submitted_at DESC
    LIMIT 4
");
$stmt->execute([$user['id']]);
$active_submissions = $stmt->fetchAll();

// Available campaigns
$stmt = $pdo->prepare("
    SELECT c.*, b.company_name, u.avatar 
    FROM campaigns c
    JOIN brand_profiles b ON c.brand_id = b.user_id
    JOIN users u ON b.user_id = u.id
    WHERE c.status = 'active' 
    AND c.id NOT IN (SELECT campaign_id FROM campaign_submissions WHERE creator_id = ?)
    ORDER BY c.created_at DESC
    LIMIT 3
");
$stmt->execute([$user['id']]);
$available_campaigns = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="campaigns.php" class="nav-item"><i class="ph ph-magnifying-glass"></i> Explorar Campanhas</a>
            <a href="earnings.php" class="nav-item"><i class="ph ph-money"></i> Ganhos & Carteira</a>
            <a href="submit.php" class="nav-item bg-white bg-opacity-5"><i class="ph ph-upload-simple"></i> Submeter Vídeo</a>
        </div>
        <div class="sidebar-footer">
            <div class="user-profile mb-4">
                <div class="user-avatar" style="background: linear-gradient(135deg, var(--accent-purple), var(--gold-primary));"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Creator</div>
                </div>
            </div>
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Dashboard</h1>
            </div>
            <div class="topbar-actions">
                <div class="wallet-badge">
                    <i class="ph ph-coins text-gold"></i> <?php echo format_kz($balance); ?>
                </div>
                <a href="campaigns.php" class="btn btn-primary text-sm py-2"><i class="ph ph-magnifying-glass"></i> Explorar</a>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> mb-6">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Big Earnings Display -->
            <div class="glass-card p-8 mb-6 text-center relative overflow-hidden">
                <div class="absolute -right-20 -bottom-20 text-gold opacity-5">
                    <i class="ph ph-money" style="font-size: 300px;"></i>
                </div>
                <h2 class="text-secondary text-lg mb-2">Total Ganho</h2>
                <div class="text-5xl md:text-6xl font-bold font-outfit text-gold drop-shadow-md mb-2">
                    Kz <span class="animate-number" data-value="<?php echo $stats['total_earnings']; ?>">0</span>
                </div>
                <div class="text-sm text-secondary">
                    Saldo disponível para levantamento: <strong class="text-white"><?php echo format_kz($balance); ?></strong>
                    <a href="earnings.php" class="text-gold ml-2 hover:underline">Levantar <i class="ph ph-arrow-right"></i></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="glass-card stat-card">
                    <i class="ph ph-eye stat-icon"></i>
                    <div class="stat-title">Total de Views</div>
                    <div class="stat-value animate-number" data-value="<?php echo $stats['total_views']; ?>" data-format="views">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-video-camera stat-icon"></i>
                    <div class="stat-title">Submissões Ativas</div>
                    <div class="stat-value animate-number" data-value="<?php echo $stats['active_submissions']; ?>">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-check-circle stat-icon"></i>
                    <div class="stat-title">Campanhas Aprovadas</div>
                    <div class="stat-value animate-number" data-value="<?php echo $stats['approved_submissions'] + $stats['active_submissions']; ?>">0</div>
                </div>
                
                <div class="glass-card stat-card">
                    <i class="ph ph-trophy stat-icon"></i>
                    <div class="stat-title">Posição Global</div>
                    <div class="stat-value animate-number text-gold" data-value="<?php echo rand(10, 500); // Mock for MVP ?>">0</div>
                    <div class="stat-trend positive"><i class="ph ph-trend-up"></i> Top 5%</div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <!-- My Submissions -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold font-outfit">As Minhas Submissões</h2>
                        <?php if (!empty($active_submissions)): ?>
                            <button class="btn btn-secondary text-xs py-1.5 px-3 btn-sync-views"><i class="ph ph-arrows-clockwise"></i> Sincronizar Tudo</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($active_submissions)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="ph ph-video-camera-slash"></i></div>
                            <h3 class="empty-state-title">Ainda sem ganhos</h3>
                            <p class="empty-state-desc mb-4">Ainda não tem submissões ativas. Explore as campanhas disponíveis e submeta os seus vídeos para começar a ganhar.</p>
                            <a href="campaigns.php" class="btn btn-primary">Explorar Campanhas</a>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col gap-4">
                            <?php foreach ($active_submissions as $sub): ?>
                                <div class="submission-card bg-black bg-opacity-20 m-0">
                                    <div class="submission-thumb w-24 h-14">
                                        <?php if ($sub['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars($sub['thumbnail']); ?>" alt="Thumbnail">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gray-800 text-gray-400">
                                                <i class="ph ph-video-camera text-xl"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="submission-platform-icon" style="width: 20px; height: 20px; font-size: 10px;">
                                            <?php if ($sub['platform'] == 'youtube') echo '<i class="ph ph-youtube-logo text-red-500"></i>'; ?>
                                            <?php if ($sub['platform'] == 'tiktok') echo '<i class="ph ph-tiktok-logo text-white"></i>'; ?>
                                            <?php if ($sub['platform'] == 'instagram') echo '<i class="ph ph-instagram-logo text-purple-500"></i>'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="submission-info">
                                        <h4 class="submission-title text-sm"><a href="<?php echo htmlspecialchars($sub['video_url']); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars($sub['title'] ?: 'Vídeo submetido'); ?></a></h4>
                                        <div class="text-xs text-secondary truncate"><?php echo htmlspecialchars($sub['campaign_title']); ?></div>
                                    </div>
                                    
                                    <div class="submission-stats shrink-0 gap-4">
                                        <div class="submission-stat">
                                            <span class="submission-stat-label">Views</span>
                                            <span class="submission-stat-value text-sm"><?php echo format_views($sub['views']); ?></span>
                                        </div>
                                        <div class="submission-stat">
                                            <span class="submission-stat-label">Ganhos</span>
                                            <span class="submission-stat-value text-sm gold"><?php echo format_kz($sub['earnings']); ?></span>
                                        </div>
                                        <div class="ml-2 pl-4 border-l border-dark-border">
                                            <button class="btn btn-secondary text-xs p-2 btn-sync-views w-8 h-8 flex items-center justify-center" data-id="<?php echo $sub['id']; ?>" title="Sincronizar views"><i class="ph ph-arrows-clockwise"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="earnings.php" class="text-sm text-gold hover:underline">Ver todo o histórico <i class="ph ph-arrow-right"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Available Campaigns -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold font-outfit">Campanhas em Destaque</h2>
                        <a href="campaigns.php" class="text-gold text-sm hover:underline">Ver Todas</a>
                    </div>
                    
                    <?php if (empty($available_campaigns)): ?>
                        <div class="p-6 text-center text-secondary border border-dark-border border-dashed rounded-xl">
                            Nenhuma campanha nova disponível no momento.
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col gap-4">
                            <?php foreach ($available_campaigns as $camp): 
                                $platforms = json_decode($camp['platforms'], true) ?? [];
                            ?>
                                <div class="bg-black bg-opacity-30 border border-dark-border rounded-xl p-4 hover:border-gold transition group">
                                    <div class="flex justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-dark-surface flex items-center justify-center font-bold text-xs"><?php echo strtoupper(substr($camp['company_name'], 0, 1)); ?></div>
                                            <span class="text-xs text-secondary"><?php echo htmlspecialchars($camp['company_name']); ?></span>
                                        </div>
                                        <div class="flex gap-1 text-sm">
                                            <?php if (in_array('youtube', $platforms)) echo '<i class="ph ph-youtube-logo text-red-500"></i>'; ?>
                                            <?php if (in_array('tiktok', $platforms)) echo '<i class="ph ph-tiktok-logo text-white"></i>'; ?>
                                            <?php if (in_array('instagram', $platforms)) echo '<i class="ph ph-instagram-logo text-purple-500"></i>'; ?>
                                        </div>
                                    </div>
                                    
                                    <h4 class="font-bold text-white mb-2 truncate group-hover:text-gold transition"><?php echo htmlspecialchars($camp['title']); ?></h4>
                                    
                                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-dark-border">
                                        <div>
                                            <span class="text-xs text-secondary block">Taxa CPM</span>
                                            <span class="font-bold text-gold"><?php echo format_kz($camp['cpm_rate']); ?></span>
                                        </div>
                                        <a href="submit.php?id=<?php echo $camp['id']; ?>" class="btn btn-secondary text-xs py-1.5 px-3">Candidatar</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>


<?php include '../includes/footer.php'; ?>
