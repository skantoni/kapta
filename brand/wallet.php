<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth-guard.php';

require_role('brand');
$user = current_user();
$page_title = 'Carteira';

// Get current wallet balance
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$wallet = $stmt->fetch();
$balance = $wallet['balance'] ?? 0;

// Get transactions
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

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
            <a href="campaign-create.php" class="nav-item"><i class="ph ph-plus-circle"></i> Criar Campanha</a>
            <a href="wallet.php" class="nav-item active"><i class="ph ph-wallet"></i> Carteira</a>
        </div>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item" style="color: var(--accent-red);"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="flex items-center">
                <button id="mobile-toggle" class="mobile-toggle mr-4"><i class="ph ph-list"></i></button>
                <h1 class="topbar-title">Carteira</h1>
            </div>
            <div class="topbar-actions">
                <div class="wallet-badge bg-gold bg-opacity-20 border-gold border-opacity-30">
                    <i class="ph ph-coins text-gold"></i> <span class="text-gold"><?php echo format_kz($balance); ?></span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> mb-6">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-row">
                <!-- Left Column - Balance & Deposit -->
                <div>
                    <div class="glass-card p-8 mb-6 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 text-gold opacity-10">
                            <i class="ph ph-wallet" style="font-size: 200px;"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <h2 class="text-sm font-medium text-secondary mb-2 uppercase tracking-wider">Saldo Disponível</h2>
                            <div class="text-4xl sm:text-5xl font-bold font-outfit text-gold mb-8 drop-shadow-md">
                                <?php echo format_kz($balance); ?>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div class="bg-black bg-opacity-40 p-4 rounded-xl border border-dark-border">
                                    <div class="text-xs text-secondary mb-1">Total Depositado</div>
                                    <div class="font-bold text-white"><?php echo format_kz($wallet['total_deposited'] ?? 0); ?></div>
                                </div>
                                <div class="bg-black bg-opacity-40 p-4 rounded-xl border border-dark-border">
                                    <div class="text-xs text-secondary mb-1">Gasto em Campanhas</div>
                                    <div class="font-bold text-white"><?php echo format_kz($wallet['total_withdrawn'] ?? 0); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h3 class="text-xl font-bold font-outfit mb-4 flex items-center gap-2"><i class="ph ph-plus-circle text-gold"></i> Adicionar Fundos</h3>
                        <p class="text-sm text-secondary mb-6">O saldo adicionado será usado para financiar as suas campanhas de performance. A plataforma cobra uma taxa de processamento de <?php echo (PLATFORM_FEE * 100); ?>% no depósito.</p>
                        
                        <form action="../api/wallet.php?action=deposit" method="POST">
                            <div class="form-group mb-2">
                                <label for="deposit_amount" class="form-label">Valor do Depósito (Kz)</label>
                                <div class="input-group">
                                    <div class="input-group-text">Kz</div>
                                    <input type="number" step="0.01" min="1000" id="deposit_amount" name="amount" class="form-control" placeholder="Ex: 50000" required data-fee="<?php echo PLATFORM_FEE; ?>">
                                </div>
                            </div>
                            
                            <div id="fee_preview" class="mb-6"></div>
                            
                            <button type="submit" class="btn btn-primary w-full justify-center py-3 text-lg"><i class="ph ph-credit-card"></i> Pagar c/ Referência Multicaixa</button>
                            <p class="text-xs text-center text-secondary mt-3">Para efeitos de MVP, o depósito é simulado e creditado imediatamente.</p>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Transactions -->
                <div class="glass-card p-0 overflow-hidden flex flex-col h-full">
                    <div class="p-6 border-b border-dark-border">
                        <h3 class="text-xl font-bold font-outfit">Histórico de Transações</h3>
                    </div>
                    
                    <div class="overflow-y-auto flex-1 p-0">
                        <?php if (empty($transactions)): ?>
                            <div class="empty-state p-8">
                                <div class="empty-state-icon w-12 h-12 text-2xl mb-4"><i class="ph ph-receipt"></i></div>
                                <h4 class="text-white font-medium">Sem Transações</h4>
                                <p class="text-sm text-secondary mt-1">Ainda não realizou nenhuma operação financeira.</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-dark-border">
                                <?php foreach ($transactions as $txn): 
                                    $is_positive = in_array($txn['type'], ['deposit', 'earning']);
                                    $color = $is_positive ? 'text-green-400' : 'text-white';
                                    if ($txn['type'] == 'platform_fee') $color = 'text-red-400';
                                    
                                    $icon = 'ph-arrows-left-right';
                                    if ($txn['type'] == 'deposit') $icon = 'ph-arrow-down-left text-green-400';
                                    if ($txn['type'] == 'campaign_fund') $icon = 'ph-megaphone text-gold';
                                    if ($txn['type'] == 'platform_fee') $icon = 'ph-percent text-red-400';
                                ?>
                                <div class="p-4 hover:bg-white hover:bg-opacity-5 transition flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-black bg-opacity-40 border border-dark-border flex items-center justify-center shrink-0">
                                        <i class="ph <?php echo $icon; ?> text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($txn['description']); ?></p>
                                        <p class="text-xs text-secondary"><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-bold <?php echo $color; ?>">
                                            <?php echo $is_positive ? '+' : ''; ?><?php echo format_kz($txn['amount']); ?>
                                        </p>
                                        <p class="text-xs text-secondary capitalize"><?php echo $txn['status']; ?></p>
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
