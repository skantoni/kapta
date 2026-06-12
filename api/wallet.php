<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_name(SESSION_NAME);
session_start();

if (!is_logged_in()) {
    redirect('../auth/login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$user = current_user();

try {
    if ($action === 'deposit' && $user['role'] === 'brand') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        
        if (!$amount || $amount < 1000) {
            flash('O valor mínimo de depósito é de Kz 1.000,00', 'error');
            redirect('../brand/wallet.php');
            exit;
        }
        
        $fee = $amount * PLATFORM_FEE;
        $net_amount = $amount - $fee;
        
        $pdo->beginTransaction();
        
        // Update wallet
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ?, total_deposited = total_deposited + ? WHERE user_id = ?");
        $stmt->execute([$net_amount, $net_amount, $user['id']]);
        
        // Find wallet id
        $stmt = $pdo->prepare("SELECT id FROM wallets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $wallet_id = $stmt->fetchColumn();
        
        // Record main deposit
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, user_id, type, amount, description, status) VALUES (?, ?, 'deposit', ?, 'Depósito (MVP Auto-Aprovado)', 'completed')");
        $stmt->execute([$wallet_id, $user['id'], $net_amount]);
        
        // Record fee
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, user_id, type, amount, description, status) VALUES (?, ?, 'platform_fee', ?, 'Taxa de Serviço da Plataforma', 'completed')");
        $stmt->execute([$wallet_id, $user['id'], -$fee]);
        
        $pdo->commit();
        
        flash("Depósito simulado processado! Foram adicionados Kz " . number_format($net_amount, 2, ',', '.') . " à sua conta (após taxa).", 'success');
        redirect('../brand/wallet.php');
    }
    elseif ($action === 'withdraw' && $user['role'] === 'creator') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        
        // Withdraw full balance (simplified for MVP)
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$user['id']]);
        $balance = $stmt->fetchColumn();
        
        if ($balance < 5000) {
            $pdo->rollBack();
            flash('O valor mínimo para levantamento é de Kz 5.000,00', 'error');
            redirect('../creator/earnings.php');
            exit;
        }
        
        // Update wallet
        $stmt = $pdo->prepare("UPDATE wallets SET balance = 0, total_withdrawn = total_withdrawn + ? WHERE user_id = ?");
        $stmt->execute([$balance, $user['id']]);
        
        // Record transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, user_id, type, amount, description, status) VALUES ((SELECT id FROM wallets WHERE user_id = ?), ?, 'withdrawal', ?, 'Levantamento para IBAN', 'pending')");
        $stmt->execute([$user['id'], $user['id'], -$balance]);
        
        $pdo->commit();
        
        flash("Pedido de levantamento de " . format_kz($balance) . " efetuado com sucesso. Será processado nas próximas 48h.", 'success');
        redirect('../creator/earnings.php');
    }
    else {
        flash('Ação inválida.', 'error');
        redirect('../index.php');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('Erro no processamento: ' . $e->getMessage(), 'error');
    redirect($_SERVER['HTTP_REFERER']);
}
