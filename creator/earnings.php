<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('creator');
$user = current_user();
$page_title = 'Meus Ganhos';

$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$wallet = $stmt->fetch();
$balance = $wallet['balance'] ?? 0;

$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

// Get submissions for breakdown
$stmt = $pdo->prepare("
    SELECT s.id, s.title, s.platform, s.views, s.earnings, s.status, c.title as campaign_title 
    FROM campaign_submissions s
    JOIN campaigns c ON s.campaign_id = c.id
    WHERE s.creator_id = ?
    ORDER BY s.earnings DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$submissions = $stmt->fetchAll();

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
            <a href="earnings.php" class="nav-item active"><i class="ph ph-money"></i> Ganhos & Carteira</a>
            <a href="campaigns.php" class="nav-item bg-white bg-opacity-5"><i class="ph ph-upload-simple"></i> Submeter Vídeo</a>
        </div>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Ganhos & Carteira</h1>
            </div>
            <div class="topbar-actions">
                <button class="btn btn-secondary text-sm py-2" onclick="alert('Funcionalidade de levantamento (MVP). Na versão final, integrará com Multicaixa/Bancos.')"><i class="ph ph-bank"></i> Levantar Fundo</button>
            </div>
        </header>
        
        <div class="content-wrapper">
            
            <div class="dashboard-row grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Balance Card -->
                <div class="md:col-span-1">
                    <div class="glass-card p-8 h-full flex flex-col justify-center relative overflow-hidden bg-gradient-to-br from-dark-card to-[#1a1811]">
                        <div class="absolute right-0 bottom-0 w-32 h-32 bg-gold opacity-10 rounded-full blur-2xl"></div>
                        
                        <h2 class="text-sm font-medium text-secondary mb-2">Saldo Disponível para Levantamento</h2>
                        <div class="text-4xl sm:text-5xl font-bold font-outfit text-gold mb-8">
                            <?php echo format_kz($balance); ?>
                        </div>
                        
                        <form action="../api/wallet.php?action=withdraw" method="POST">
                            <input type="hidden" name="amount" value="<?php echo $balance; ?>">
                            <button type="submit" class="btn btn-primary w-full justify-center py-3" <?php echo $balance <= 0 ? 'disabled' : ''; ?>>
                                Levantar para IBAN <i class="ph ph-arrow-right"></i>
                            </button>
                        </form>
                        <p class="text-xs text-center text-secondary mt-3">Mínimo de levantamento: Kz 5.000,00</p>
                    </div>
                </div>
                
                <!-- Performance Chart Placeholder -->
                <div class="md:col-span-2">
                    <div class="glass-card p-6 h-full flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold font-outfit">Evolução de Ganhos</h3>
                            <div class="flex gap-2">
                                <span class="badge bg-white bg-opacity-10 text-xs">Últimos 7 dias</span>
                            </div>
                        </div>
                        <div class="flex-1 min-h-[200px] flex items-end justify-between px-4 pb-2 relative border-b border-l border-dark-border">
                            <!-- Simple pure CSS bar chart for MVP -->
                            <?php 
                            $days = 7;
                            $max_h = 100;
                            for($i = $days; $i >= 1; $i--) {
                                $h = rand(10, $max_h);
                                $is_today = $i == 1;
                                echo '<div class="w-8 md:w-12 group relative flex justify-center">';
                                echo '<div class="absolute bottom-full mb-2 bg-black text-xs p-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10">Kz ' . rand(1, 10)*1000 . '</div>';
                                echo '<div class="w-full rounded-t-sm transition-all duration-500 ' . ($is_today ? 'bg-gold' : 'bg-gold bg-opacity-40 hover:bg-opacity-60') . '" style="height: '.$h.'%;"></div>';
                                echo '<div class="absolute top-full mt-2 text-xs text-secondary">' . date('d/m', strtotime("-$i days")) . '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-row">
                <!-- Highest Earning Videos -->
                <div class="glass-card p-0 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-dark-border flex justify-between items-center">
                        <h3 class="text-lg font-bold font-outfit">Vídeos Mais Rentáveis</h3>
                        <button class="btn btn-secondary text-xs px-2 py-1 btn-sync-views"><i class="ph ph-arrows-clockwise"></i> Sync Todos</button>
                    </div>
                    
                    <div class="p-0 flex-1 overflow-y-auto">
                        <?php if (empty($submissions)): ?>
                            <div class="p-8 text-center text-secondary">Ainda não tem vídeos com ganhos.</div>
                        <?php else: ?>
                            <div class="divide-y divide-dark-border">
                                <?php foreach ($submissions as $index => $sub): ?>
                                    <div class="p-4 flex items-center gap-4 hover:bg-white hover:bg-opacity-5 transition">
                                        <div class="w-8 h-8 rounded-full bg-dark-surface flex items-center justify-center font-bold text-xs text-secondary shrink-0">
                                            #<?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-white truncate text-sm"><?php echo htmlspecialchars($sub['title'] ?: 'Vídeo no ' . ucfirst($sub['platform'])); ?></div>
                                            <div class="text-xs text-secondary truncate"><?php echo htmlspecialchars($sub['campaign_title']); ?></div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-bold text-gold"><?php echo format_kz($sub['earnings']); ?></div>
                                            <div class="text-xs text-secondary"><?php echo format_views($sub['views']); ?> views</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Transactions List -->
                <div class="glass-card p-0 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-dark-border">
                        <h3 class="text-lg font-bold font-outfit">Histórico de Transações</h3>
                    </div>
                    
                    <div class="p-0 flex-1 overflow-y-auto">
                        <?php if (empty($transactions)): ?>
                            <div class="p-8 text-center text-secondary">Ainda não há movimentos na sua conta.</div>
                        <?php else: ?>
                            <div class="divide-y divide-dark-border">
                                <?php foreach ($transactions as $txn): 
                                    $is_positive = in_array($txn['type'], ['earning']);
                                    $color = $is_positive ? 'text-green-400' : 'text-white';
                                    
                                    $icon = 'ph-arrows-left-right';
                                    if ($txn['type'] == 'earning') $icon = 'ph-trend-up text-green-400';
                                    if ($txn['type'] == 'withdrawal') $icon = 'ph-bank text-white';
                                ?>
                                    <div class="p-4 flex items-center gap-4 hover:bg-white hover:bg-opacity-5 transition">
                                        <div class="w-10 h-10 rounded bg-black bg-opacity-40 border border-dark-border flex items-center justify-center shrink-0">
                                            <i class="ph <?php echo $icon; ?> text-lg"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($txn['description']); ?></div>
                                            <div class="text-xs text-secondary"><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-bold text-sm <?php echo $color; ?>">
                                                <?php echo $is_positive ? '+' : ''; ?><?php echo format_kz($txn['amount']); ?>
                                            </div>
                                            <div class="text-[10px] text-secondary uppercase bg-dark-surface px-1.5 py-0.5 rounded inline-block mt-1"><?php echo $txn['status']; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
</div>


<?php include '../includes/footer.php'; ?>
