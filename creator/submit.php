<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('creator');
$user = current_user();

$campaign_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$campaign_id) {
    flash('Selecione uma campanha para submeter conteúdo.', 'error');
    redirect('campaigns.php');
}

// Get campaign
$stmt = $pdo->prepare("
    SELECT c.*, b.company_name, b.logo
    FROM campaigns c
    JOIN brand_profiles b ON c.brand_id = b.user_id
    WHERE c.id = ? AND c.status = 'active'
");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    flash('Campanha não encontrada ou não está ativa.', 'error');
    redirect('campaigns.php');
}

// Check if already submitted
$stmt = $pdo->prepare("SELECT id FROM campaign_submissions WHERE campaign_id = ? AND creator_id = ?");
$stmt->execute([$campaign_id, $user['id']]);
if ($stmt->fetch()) {
    flash('Já submeteu conteúdo para esta campanha.', 'error');
    redirect('dashboard.php');
}

$platforms = json_decode($campaign['platforms'], true) ?? [];
$page_title = 'Submeter Vídeo: ' . $campaign['title'];

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo gradient-text">Kapta.</a>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="ph ph-squares-four"></i> Dashboard</a>
            <a href="campaigns.php" class="nav-item"><i class="ph ph-magnifying-glass"></i> Explorar Campanhas</a>
            <a href="earnings.php" class="nav-item"><i class="ph ph-money"></i> Ganhos & Carteira</a>
            <a href="submit.php" class="nav-item active bg-white bg-opacity-5"><i class="ph ph-upload-simple"></i> Submeter Vídeo</a>
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
                    <h1 class="topbar-title">Submeter Vídeo</h1>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper max-w-4xl mx-auto">
            
            <div class="dashboard-row grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Submission Form -->
                <div class="md:col-span-2">
                    <div class="glass-card p-6 mb-6 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 opacity-10">
                            <i class="ph ph-upload-simple" style="font-size: 150px;"></i>
                        </div>
                        <h2 class="text-xl font-bold font-outfit mb-4 relative z-10">Colar Link do Vídeo</h2>
                        
                        <p class="text-secondary text-sm mb-6 relative z-10">Introduza o link direto para o vídeo que publicou para esta campanha. O sistema irá automaticamente detectar a plataforma e começar a contabilizar as views.</p>
                        
                        <form id="submissionForm" action="../api/submissions.php?action=submit" method="POST" class="relative z-10">
                            <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                            <input type="hidden" id="detected_platform" name="platform" value="">
                            
                            <div class="form-group mb-6">
                                <label for="video_url" class="form-label">Link do Vídeo</label>
                                <div class="input-icon">
                                    <i class="ph ph-link"></i>
                                    <input type="url" id="video_url" name="video_url" class="form-control" placeholder="https://www.tiktok.com/@user/video/123... ou https://youtu.be/..." required>
                                </div>
                                <div id="url_feedback" class="mt-2 text-sm text-secondary hidden flex items-center gap-1"></div>
                            </div>
                            
                            <div id="video_preview" class="hidden border border-dark-border rounded-xl p-4 bg-black bg-opacity-30 mb-6 flex gap-4 items-center">
                                <div class="w-24 h-16 bg-dark-surface rounded overflow-hidden flex-shrink-0 relative">
                                    <img id="preview_thumb" src="" class="w-full h-full object-cover hidden">
                                    <div id="preview_placeholder" class="w-full h-full flex items-center justify-center text-secondary">
                                        <i class="ph ph-video-camera text-xl"></i>
                                    </div>
                                    <div id="preview_platform_icon" class="absolute bottom-1 right-1 bg-black bg-opacity-70 rounded w-6 h-6 flex items-center justify-center text-xs"></div>
                                </div>
                                <div>
                                    <div id="preview_title" class="font-medium text-white mb-1 line-clamp-2">A carregar informações...</div>
                                    <div class="text-xs text-secondary">Views iniciais: <span id="preview_views" class="text-white font-medium">--</span></div>
                                </div>
                            </div>
                            
                            <div class="bg-gold bg-opacity-10 border border-gold border-opacity-30 rounded-xl p-4 mb-6 flex items-start gap-3">
                                <i class="ph ph-info text-gold text-xl mt-0.5"></i>
                                <div>
                                    <h4 class="text-sm font-bold text-gold mb-1">Estimativa de Ganhos</h4>
                                    <p class="text-xs text-white">Com este CPM de <?php echo format_kz($campaign['cpm_rate']); ?>, ganhará <strong class="text-gold"><?php echo format_kz($campaign['cpm_rate'] * 10); ?></strong> se o seu vídeo atingir 10.000 views.</p>
                                </div>
                            </div>
                            
                            <button type="submit" id="submitBtn" class="btn btn-primary w-full justify-center py-3 text-lg font-bold disabled:opacity-50 disabled:cursor-not-allowed">Submeter para Revisão <i class="ph ph-arrow-right"></i></button>
                        </form>
                    </div>
                </div>
                
                <!-- Campaign Summary Sidebar -->
                <div class="md:col-span-1">
                    <div class="glass-card p-0 overflow-hidden sticky top-24">
                        <div class="bg-black bg-opacity-40 p-6 border-b border-dark-border text-center">
                            <div class="w-12 h-12 rounded bg-dark-surface flex items-center justify-center font-bold text-lg mx-auto mb-3"><?php echo strtoupper(substr($campaign['company_name'], 0, 1)); ?></div>
                            <h3 class="font-bold text-white"><?php echo htmlspecialchars($campaign['company_name']); ?></h3>
                            <p class="text-xs text-secondary"><?php echo htmlspecialchars($campaign['category']); ?></p>
                        </div>
                        
                        <div class="p-6">
                            <h4 class="font-bold font-outfit text-white mb-4 leading-tight"><?php echo htmlspecialchars($campaign['title']); ?></h4>
                            
                            <div class="flex items-center justify-between mb-4 pb-4 border-b border-dark-border">
                                <span class="text-sm text-secondary">Taxa CPM</span>
                                <span class="font-bold text-gold"><?php echo format_kz($campaign['cpm_rate']); ?></span>
                            </div>
                            
                            <div class="mb-4 pb-4 border-b border-dark-border">
                                <span class="text-sm text-secondary block mb-2">Plataformas Aceites</span>
                                <div class="flex gap-2">
                                    <?php if (in_array('youtube', $platforms)): ?>
                                        <span class="badge bg-red-500 bg-opacity-10 text-red-500 border border-red-500 border-opacity-20"><i class="ph ph-youtube-logo"></i> YouTube</span>
                                    <?php endif; ?>
                                    <?php if (in_array('tiktok', $platforms)): ?>
                                        <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-20"><i class="ph ph-tiktok-logo"></i> TikTok</span>
                                    <?php endif; ?>
                                    <?php if (in_array('instagram', $platforms)): ?>
                                        <span class="badge bg-purple-500 bg-opacity-10 text-purple-500 border border-purple-500 border-opacity-20"><i class="ph ph-instagram-logo"></i> Instagram</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4 pb-4 border-b border-dark-border">
                                <span class="text-sm text-secondary block mb-2">Requisitos Mínimos</span>
                                <?php if ($campaign['min_followers'] > 0): ?>
                                    <div class="text-sm text-white"><i class="ph ph-users text-gold mr-1"></i> <?php echo number_format($campaign['min_followers'], 0, ',', '.'); ?> seguidores</div>
                                <?php else: ?>
                                    <div class="text-sm text-white"><i class="ph ph-users text-gold mr-1"></i> Qualquer número de seguidores</div>
                                <?php endif; ?>
                                <div class="text-sm text-white mt-1"><i class="ph ph-calendar text-gold mr-1"></i> Até <?php echo date('d/m/Y', strtotime($campaign['deadline'])); ?></div>
                            </div>
                            
                            <div>
                                <span class="text-sm text-secondary block mb-2">Briefing</span>
                                <div class="text-xs text-gray-300 bg-dark-surface p-3 rounded h-32 overflow-y-auto custom-scrollbar whitespace-pre-wrap"><?php echo htmlspecialchars($campaign['description']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('video_url');
    const urlFeedback = document.getElementById('url_feedback');
    const platformInput = document.getElementById('detected_platform');
    const previewDiv = document.getElementById('video_preview');
    const submitBtn = document.getElementById('submitBtn');
    
    // Allowed platforms from PHP
    const allowedPlatforms = <?php echo json_encode($platforms); ?>;
    
    let typingTimer;
    
    urlInput.addEventListener('input', function() {
        clearTimeout(typingTimer);
        const url = this.value.trim();
        
        previewDiv.classList.add('hidden');
        
        if (!url) {
            urlFeedback.classList.add('hidden');
            platformInput.value = '';
            return;
        }
        
        urlFeedback.classList.remove('hidden');
        urlFeedback.innerHTML = '<i class="ph ph-spinner animate-spin"></i> A analisar link...';
        urlFeedback.className = 'mt-2 text-sm text-secondary flex items-center gap-1';
        
        typingTimer = setTimeout(() => {
            detectPlatform(url);
        }, 800);
    });
    
    function detectPlatform(url) {
        let platform = null;
        let icon = '';
        let colorClass = '';
        
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            platform = 'youtube';
            icon = 'ph-youtube-logo';
            colorClass = 'text-red-500';
        } else if (url.includes('tiktok.com')) {
            platform = 'tiktok';
            icon = 'ph-tiktok-logo';
            colorClass = 'text-white';
        } else if (url.includes('instagram.com')) {
            platform = 'instagram';
            icon = 'ph-instagram-logo';
            colorClass = 'text-purple-500';
        }
        
        if (!platform) {
            urlFeedback.innerHTML = '<i class="ph ph-warning-circle text-red-500"></i> <span class="text-red-500">Link inválido. Insira um link válido do YouTube, TikTok ou Instagram.</span>';
            submitBtn.disabled = true;
            return;
        }
        
        if (!allowedPlatforms.includes(platform)) {
            urlFeedback.innerHTML = `<i class="ph ph-warning-circle text-red-500"></i> <span class="text-red-500">A marca não aceita vídeos do ${platform} nesta campanha.</span>`;
            submitBtn.disabled = true;
            return;
        }
        
        platformInput.value = platform;
        urlFeedback.innerHTML = `<i class="ph ph-check-circle text-green-500"></i> <span class="text-green-500">Link válido (${platform}).</span>`;
        submitBtn.disabled = false;
        
        // MVP: Simple preview logic without actually calling the API here to save complexity
        // We'll just show the platform icon and a generic message
        previewDiv.classList.remove('hidden');
        document.getElementById('preview_platform_icon').innerHTML = `<i class="ph ${icon} ${colorClass}"></i>`;
        document.getElementById('preview_title').textContent = "Link preparado para submissão";
        document.getElementById('preview_views').textContent = "Serão contabilizadas após a submissão";
    }
});
</script>


<?php include '../includes/footer.php'; ?>
