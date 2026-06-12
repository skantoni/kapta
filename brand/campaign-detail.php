<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('brand');
$user = current_user();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('campaigns.php');
}

// Get campaign
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND brand_id = ?");
$stmt->execute([$id, $user['id']]);
$campaign = $stmt->fetch();

if (!$campaign) {
    flash('Campanha não encontrada.', 'error');
    redirect('campaigns.php');
}

$platforms = json_decode($campaign['platforms'], true) ?? [];
$progress = ($campaign['budget'] > 0) ? min(100, ($campaign['spent'] / $campaign['budget']) * 100) : 0;

// Get submissions
$stmt = $pdo->prepare("
    SELECT s.*, u.name as creator_name 
    FROM campaign_submissions s
    JOIN users u ON s.creator_id = u.id
    WHERE s.campaign_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$id]);
$submissions = $stmt->fetchAll();

$page_title = $campaign['title'];

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
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <div class="flex items-center">
                    <a href="campaigns.php" class="text-secondary hover:text-white mr-3"><i class="ph ph-arrow-left text-xl"></i></a>
                    <h1 class="topbar-title truncate max-w-md"><?php echo htmlspecialchars($campaign['title']); ?></h1>
                </div>
            </div>
            <div class="topbar-actions flex gap-2">
                <button class="btn btn-secondary text-sm py-2" onclick="alert('Funcionalidade de pausa em desenvolvimento.')"><i class="ph ph-pause"></i></button>
                <button class="btn btn-primary text-sm py-2 btn-sync-views" data-campaign="<?php echo $id; ?>"><i class="ph ph-arrows-clockwise"></i> Sincronizar Tudo</button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> mb-6">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Campaign Header Card -->
            <div class="glass-card p-6 mb-6">
                <div class="flex flex-wrap justify-between gap-6">
                    <div class="flex-1 min-w-[300px]">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="status-badge <?php echo $campaign['status']; ?>">
                                <?php 
                                    if ($campaign['status'] == 'active') echo 'Activa';
                                    elseif ($campaign['status'] == 'paused') echo 'Pausada';
                                    elseif ($campaign['status'] == 'completed') echo 'Concluída';
                                    else echo 'Rascunho';
                                ?>
                            </span>
                            <div class="campaign-platforms">
                                <?php if (in_array('youtube', $platforms)): ?><i class="ph ph-youtube-logo text-red-500 text-xl" title="YouTube"></i><?php endif; ?>
                                <?php if (in_array('tiktok', $platforms)): ?><i class="ph ph-tiktok-logo text-white text-xl" title="TikTok"></i><?php endif; ?>
                                <?php if (in_array('instagram', $platforms)): ?><i class="ph ph-instagram-logo text-purple-500 text-xl" title="Instagram"></i><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex gap-8 mb-4">
                            <div>
                                <div class="text-sm text-secondary">Taxa CPM</div>
                                <div class="text-xl font-bold text-gold"><?php echo format_kz($campaign['cpm_rate']); ?> <span class="text-sm font-normal text-secondary">/ 1k views</span></div>
                            </div>
                            <div>
                                <div class="text-sm text-secondary">Data Limite</div>
                                <div class="text-lg font-medium text-white"><?php echo date('d/m/Y', strtotime($campaign['deadline'])); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-secondary">Categoria</div>
                                <div class="text-lg font-medium text-white"><?php echo htmlspecialchars($campaign['category']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full md:w-80 bg-black bg-opacity-30 rounded-xl p-4 border border-dark-border">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-secondary">Orçamento Consumido</span>
                            <span class="font-bold text-white"><?php echo format_kz($campaign['spent']); ?></span>
                        </div>
                        <div class="progress-container h-2 mb-2">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-secondary">
                            <span>0%</span>
                            <span>Total: <?php echo format_kz($campaign['budget']); ?></span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-dark-border flex justify-between">
                            <span class="text-sm">Restante</span>
                            <span class="font-bold text-gold"><?php echo format_kz($campaign['budget'] - $campaign['spent']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" data-target="tab-submissions">Submissões (<?php echo count($submissions); ?>)</div>
                <div class="tab" data-target="tab-briefing">Briefing</div>
            </div>

            <div class="tab-content active" id="tab-submissions">
                <?php if (empty($submissions)): ?>
                    <div class="glass-card empty-state">
                        <div class="empty-state-icon"><i class="ph ph-video-camera"></i></div>
                        <h3 class="empty-state-title">Sem Submissões</h3>
                        <p class="empty-state-desc">Ainda nenhum creator submeteu conteúdo para esta campanha.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                        <div class="submission-card">
                            <div class="submission-thumb">
                                <?php if ($sub['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars($sub['thumbnail']); ?>" alt="Thumbnail">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-800 text-gray-400">
                                        <i class="ph ph-video-camera text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="submission-platform-icon">
                                    <?php if ($sub['platform'] == 'youtube') echo '<i class="ph ph-youtube-logo text-red-500"></i>'; ?>
                                    <?php if ($sub['platform'] == 'tiktok') echo '<i class="ph ph-tiktok-logo text-white"></i>'; ?>
                                    <?php if ($sub['platform'] == 'instagram') echo '<i class="ph ph-instagram-logo text-purple-500"></i>'; ?>
                                </div>
                            </div>
                            
                            <div class="submission-info">
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="submission-title"><a href="<?php echo htmlspecialchars($sub['video_url']); ?>" target="_blank" class="hover:underline text-white"><?php echo htmlspecialchars($sub['title'] ?: 'Vídeo submetido'); ?></a></h4>
                                    <span class="status-badge <?php echo $sub['status']; ?>">
                                        <?php 
                                            if ($sub['status'] == 'pending') echo 'Pendente';
                                            elseif ($sub['status'] == 'approved') echo 'Aprovado';
                                            elseif ($sub['status'] == 'active') echo 'Ativo';
                                            elseif ($sub['status'] == 'rejected') echo 'Rejeitado';
                                        ?>
                                    </span>
                                </div>
                                <div class="submission-creator">
                                    <i class="ph ph-user"></i> <?php echo htmlspecialchars($sub['creator_name']); ?> • 
                                    <span class="text-xs"><?php echo time_ago($sub['submitted_at']); ?></span>
                                </div>
                            </div>
                            
                            <div class="submission-stats shrink-0">
                                <div class="submission-stat">
                                    <span class="submission-stat-label">Views Registadas</span>
                                    <span class="submission-stat-value"><?php echo format_views($sub['views']); ?></span>
                                </div>
                                <div class="submission-stat">
                                    <span class="submission-stat-label">Pago</span>
                                    <span class="submission-stat-value gold"><?php echo format_kz($sub['earnings']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-2 shrink-0 ml-4 border-l border-dark-border pl-4">
                                <?php if ($sub['status'] == 'pending'): ?>
                                    <form action="../api/submissions.php?action=approve" method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3 w-full justify-center"><i class="ph ph-check"></i> Aprovar</button>
                                    </form>
                                    <form action="../api/submissions.php?action=reject" method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-secondary text-xs py-1.5 px-3 w-full justify-center text-red-400 border-red-900 bg-red-900 bg-opacity-20"><i class="ph ph-x"></i> Rejeitar</button>
                                    </form>
                                <?php elseif ($sub['status'] == 'approved' || $sub['status'] == 'active'): ?>
                                    <button class="btn btn-secondary text-xs py-1.5 px-3 btn-sync-views w-full justify-center" data-id="<?php echo $sub['id']; ?>"><i class="ph ph-arrows-clockwise"></i> Sincronizar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-content" id="tab-briefing">
                <div class="glass-card p-6">
                    <h3 class="text-lg font-bold mb-4 font-outfit">Instruções para os Creators</h3>
                    <div class="prose prose-invert max-w-none text-secondary bg-black bg-opacity-30 p-6 rounded-xl border border-dark-border whitespace-pre-wrap">
<?php echo htmlspecialchars($campaign['description']); ?>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
</div>

<script src="../assets/js/dashboard.js"></script>
<?php include '../includes/footer.php'; ?>
