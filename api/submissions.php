<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_name(SESSION_NAME);
session_start();

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user = current_user();

try {
    if ($action === 'submit' && $user['role'] === 'creator') {
        $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
        $url = filter_input(INPUT_POST, 'video_url', FILTER_SANITIZE_URL);
        
        if (!$campaign_id || !$url) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Detect platform
        $platform = get_platform_from_url($url);
        if (!$platform) {
            flash('URL inválido ou plataforma não suportada.', 'error');
            redirect('../creator/submit.php?id=' . $campaign_id);
            exit;
        }
        
        // Extract ID
        $video_id = '';
        if ($platform === 'youtube') $video_id = extract_youtube_id($url);
        elseif ($platform === 'tiktok') $video_id = extract_tiktok_id($url);
        
        // Basic initial data mock
        $title = "Vídeo submetido - Aguardando processamento";
        $thumbnail = "";
        
        // Insert submission
        $stmt = $pdo->prepare("
            INSERT INTO campaign_submissions 
            (campaign_id, creator_id, platform, video_url, video_id, title, thumbnail, views, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending')
        ");
        
        $stmt->execute([$campaign_id, $user['id'], $platform, $url, $video_id, $title, $thumbnail]);
        
        flash('Vídeo submetido com sucesso! O cliente irá analisar a sua submissão.', 'success');
        redirect('../creator/dashboard.php');
        
    } 
    elseif ($action === 'approve' && $user['role'] === 'brand') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        // Check ownership
        $stmt = $pdo->prepare("SELECT s.id FROM campaign_submissions s JOIN campaigns c ON s.campaign_id = c.id WHERE s.id = ? AND c.brand_id = ?");
        $stmt->execute([$id, $user['id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE campaign_submissions SET status = 'approved', approved_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // Trigger initial sync
        // Using curl to call our own endpoint is overkill, let's just let the user click sync later or it'll sync on cron.
        
        flash('Submissão aprovada. As views começarão a ser contabilizadas.', 'success');
        redirect($_SERVER['HTTP_REFERER']);
    }
    elseif ($action === 'reject' && $user['role'] === 'brand') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        $stmt = $pdo->prepare("UPDATE campaign_submissions SET status = 'rejected' WHERE id = ? AND campaign_id IN (SELECT id FROM campaigns WHERE brand_id = ?)");
        $stmt->execute([$id, $user['id']]);
        
        flash('Submissão rejeitada.', 'success');
        redirect($_SERVER['HTTP_REFERER']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
