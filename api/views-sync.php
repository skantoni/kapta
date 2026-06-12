<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST
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

$submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
$user = current_user();

if (!$submission_id && !$campaign_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Determine which submissions to sync
    $query = "
        SELECT s.*, c.cpm_rate, c.budget, c.spent, c.brand_id 
        FROM campaign_submissions s
        JOIN campaigns c ON s.campaign_id = c.id
        WHERE s.status IN ('approved', 'active')
    ";
    
    $params = [];
    
    if ($submission_id) {
        // Brand checking its campaign's submission, or Creator checking its own submission
        if ($user['role'] === 'brand') {
            $query .= " AND s.id = ? AND c.brand_id = ?";
            $params = [$submission_id, $user['id']];
        } else {
            $query .= " AND s.id = ? AND s.creator_id = ?";
            $params = [$submission_id, $user['id']];
        }
    } else if ($campaign_id) {
        // Brand checking all submissions in a campaign
        $query .= " AND s.campaign_id = ? AND c.brand_id = ?";
        $params = [$campaign_id, $user['id']];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
    
    if (empty($submissions)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No active submissions found']);
        exit;
    }
    
    $total_new_views = 0;
    $total_new_earnings = 0;
    $updated_count = 0;
    
    foreach ($submissions as $sub) {
        $new_views = $sub['views']; // Default to current
        $video_data = ['title' => $sub['title'], 'thumbnail' => $sub['thumbnail']];
        
        // MVP: Mocking the API response to demonstrate the logic without real API keys
        // In a real scenario, we would call the respective APIs here
        if (YOUTUBE_API_KEY && $sub['platform'] == 'youtube') {
            $video_data = get_youtube_stats($sub['video_id']);
            if ($video_data && isset($video_data['views'])) {
                $new_views = $video_data['views'];
            }
        } else {
            // Simulated growth for MVP
            $growth_factor = rand(100, 5000);
            // Cap at 10 million total simulated views
            if ($sub['views'] < 10000000) {
                $new_views = $sub['views'] + $growth_factor;
            }
        }
        
        // Calculate earnings diff
        if ($new_views > $sub['views']) {
            $views_diff = $new_views - $sub['views'];
            $earnings_diff = calculate_earnings($views_diff, $sub['cpm_rate']);
            
            // Check if budget allows paying this
            $remaining_budget = $sub['budget'] - $sub['spent'];
            
            if ($earnings_diff > 0 && $remaining_budget > 0) {
                // If earnings exceed remaining budget, cap it
                if ($earnings_diff > $remaining_budget) {
                    $earnings_diff = $remaining_budget;
                }
                
                $new_total_earnings = $sub['earnings'] + $earnings_diff;
                
                // Update submission
                $upd_sub = $pdo->prepare("UPDATE campaign_submissions SET views = ?, earnings = ?, title = ?, thumbnail = ?, last_synced_at = NOW(), status = 'active' WHERE id = ?");
                $upd_sub->execute([$new_views, $new_total_earnings, $video_data['title'], $video_data['thumbnail'], $sub['id']]);
                
                // Update campaign spent
                $upd_camp = $pdo->prepare("UPDATE campaigns SET spent = spent + ? WHERE id = ?");
                $upd_camp->execute([$earnings_diff, $sub['campaign_id']]);
                
                // Update creator wallet
                $upd_wall = $pdo->prepare("UPDATE wallets SET balance = balance + ?, total_deposited = total_deposited + ? WHERE user_id = ?");
                $upd_wall->execute([$earnings_diff, $earnings_diff, $sub['creator_id']]);
                
                // Record earning transaction
                $ins_txn = $pdo->prepare("INSERT INTO transactions (wallet_id, user_id, type, amount, description, status) VALUES ((SELECT id FROM wallets WHERE user_id = ?), ?, 'earning', ?, ?, 'completed')");
                $ins_txn->execute([$sub['creator_id'], $sub['creator_id'], $earnings_diff, "Ganhos gerados: " . $sub['title']]);
                
                $total_new_views += $views_diff;
                $total_new_earnings += $earnings_diff;
                $updated_count++;
            } else if ($new_views > $sub['views']) {
                // Budget is 0, just update views without paying
                $upd_sub = $pdo->prepare("UPDATE campaign_submissions SET views = ?, title = ?, thumbnail = ?, last_synced_at = NOW(), status = 'active' WHERE id = ?");
                $upd_sub->execute([$new_views, $video_data['title'], $video_data['thumbnail'], $sub['id']]);
                $updated_count++;
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Sync completed', 
        'updated_count' => $updated_count,
        'total_new_views' => $total_new_views,
        'total_new_earnings' => $total_new_earnings
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
