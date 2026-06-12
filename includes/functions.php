<?php
// ============================================================
// KAPTA — Funções Auxiliares
// includes/functions.php
// ============================================================

if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config.php';
}

// -----------------------------------------------
// Formatação
// -----------------------------------------------

/**
 * Formata um valor monetário no formato angolano.
 * Ex: 1000.50 → 'Kz 1.000,50'
 */
function format_kz(float $amount): string {
    return 'Kz ' . number_format($amount, 2, ',', '.');
}

/**
 * Formata um número de views de forma legível.
 * Ex: 1200000 → '1,2M' | 45000 → '45K'
 */
function format_views(int $views): string {
    if ($views >= 1_000_000_000) {
        return round($views / 1_000_000_000, 1) . 'B';
    }
    if ($views >= 1_000_000) {
        return round($views / 1_000_000, 1) . 'M';
    }
    if ($views >= 1_000) {
        return round($views / 1_000, 1) . 'K';
    }
    return (string)$views;
}

/**
 * Converte um datetime em tempo relativo.
 * Ex: '2 horas atrás', '3 dias atrás'
 */
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . ' atrás';
    if ($diff->m > 0) return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    if ($diff->d > 0) return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') . ' atrás';
    if ($diff->h > 0) return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    if ($diff->i > 0) return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    return 'agora mesmo';
}

// -----------------------------------------------
// Extracção de IDs de Vídeo
// -----------------------------------------------

/**
 * Extrai o ID de um vídeo YouTube a partir de várias formas de URL.
 */
function extract_youtube_id(string $url): ?string {
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return $m[1];
        }
    }
    return null;
}

/**
 * Extrai o ID de um vídeo TikTok a partir da URL.
 */
function extract_tiktok_id(string $url): ?string {
    if (preg_match('/tiktok\.com\/@[^\/]+\/video\/(\d+)/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/tiktok\.com\/v\/(\d+)/', $url, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Detecta a plataforma a partir da URL.
 */
function get_platform_from_url(string $url): ?string {
    $url = strtolower($url);
    if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
        return 'youtube';
    }
    if (str_contains($url, 'tiktok.com')) {
        return 'tiktok';
    }
    if (str_contains($url, 'instagram.com') || str_contains($url, 'instagr.am')) {
        return 'instagram';
    }
    return null;
}

// -----------------------------------------------
// Chamadas de API
// -----------------------------------------------

/**
 * Vai buscar as estatísticas de um vídeo YouTube via API v3.
 * Retorna: ['views' => int, 'title' => str, 'thumbnail' => str] ou null
 */
function get_youtube_stats(string $video_id): ?array {
    if (empty(YOUTUBE_API_KEY)) {
        return null;
    }

    $url = sprintf(
        'https://www.googleapis.com/youtube/v3/videos?key=%s&id=%s&part=statistics,snippet',
        urlencode(YOUTUBE_API_KEY),
        urlencode($video_id)
    );

    $response = http_get($url);
    if (!$response) return null;

    $data = json_decode($response, true);
    if (empty($data['items'])) return null;

    $item = $data['items'][0];
    return [
        'views'     => (int)($item['statistics']['viewCount'] ?? 0),
        'title'     => $item['snippet']['title'] ?? 'Sem título',
        'thumbnail' => $item['snippet']['thumbnails']['medium']['url']
                    ?? $item['snippet']['thumbnails']['default']['url']
                    ?? '',
    ];
}

/**
 * Vai buscar dados básicos de um vídeo TikTok via oEmbed.
 * Nota: o oEmbed do TikTok não retorna views — sinalizado.
 */
function get_tiktok_stats(string $url): ?array {
    $endpoint = 'https://www.tiktok.com/oembed?url=' . urlencode($url);
    $response = http_get($endpoint);
    if (!$response) return null;

    $data = json_decode($response, true);
    if (empty($data)) return null;

    return [
        'views'     => 0, // TikTok oEmbed não fornece views
        'title'     => $data['title'] ?? 'Vídeo TikTok',
        'thumbnail' => $data['thumbnail_url'] ?? '',
        'author'    => $data['author_name'] ?? '',
        'note'      => 'TikTok não fornece views via oEmbed. Sincroniza manualmente.',
    ];
}

/**
 * Helper: executa um GET HTTP com cURL.
 */
function http_get(string $url, array $headers = []): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'Kapta/1.0 (+https://kapta.ao)',
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || $response === false) return null;
    return $response;
}

// -----------------------------------------------
// Sincronização de Views
// -----------------------------------------------

/**
 * Sincroniza as views de uma submissão e actualiza os ganhos.
 * Retorna array com os novos dados ou null em caso de erro.
 */
function sync_submission_views(int $submission_id): ?array {
    $pdo = get_pdo();

    $stmt = $pdo->prepare('
        SELECT cs.*, c.cpm_rate, c.budget, c.spent, c.brand_id,
               cp.instagram_access_token
        FROM campaign_submissions cs
        JOIN campaigns c ON c.id = cs.campaign_id
        JOIN creator_profiles cp ON cp.user_id = cs.creator_id
        WHERE cs.id = ?
    ');
    $stmt->execute([$submission_id]);
    $sub = $stmt->fetch();

    if (!$sub) return null;

    $prev_views    = (int)$sub['views'];
    $prev_earnings = (float)$sub['earnings'];
    $new_views     = $prev_views;
    $title         = $sub['title'];
    $thumbnail     = $sub['thumbnail'];
    $note          = null;

    switch ($sub['platform']) {
        case 'youtube':
            $stats = get_youtube_stats($sub['video_id']);
            if ($stats) {
                $new_views = $stats['views'];
                $title     = $stats['title'];
                $thumbnail = $stats['thumbnail'];
            }
            break;

        case 'tiktok':
            $stats = get_tiktok_stats($sub['video_url']);
            if ($stats) {
                $title     = $stats['title'];
                $thumbnail = $stats['thumbnail'];
                $note      = $stats['note'] ?? null;
                // views ficam iguais (oEmbed não fornece)
            }
            break;

        case 'instagram':
            // Usa Graph API se tiver token
            if (!empty($sub['instagram_access_token'])) {
                $token = $sub['instagram_access_token'];
                $resp  = http_get("https://graph.instagram.com/me/media?fields=id,media_type,views_count,timestamp&access_token={$token}");
                if ($resp) {
                    $igData = json_decode($resp, true);
                    // Tenta encontrar por video_id
                    foreach (($igData['data'] ?? []) as $media) {
                        if (($media['id'] ?? '') === $sub['video_id']) {
                            $new_views = (int)($media['views_count'] ?? $new_views);
                            break;
                        }
                    }
                }
            }
            break;
    }

    // Calcula novos ganhos
    $new_earnings = calculate_earnings($new_views, (float)$sub['cpm_rate']);

    // Diferença de ganhos para actualizar a carteira do creator
    $earnings_diff = $new_earnings - $prev_earnings;

    $pdo->beginTransaction();
    try {
        // Actualiza a submissão
        $pdo->prepare('
            UPDATE campaign_submissions
            SET views = ?, earnings = ?, title = ?, thumbnail = ?, last_synced_at = NOW()
            WHERE id = ?
        ')->execute([$new_views, $new_earnings, $title, $thumbnail, $submission_id]);

        if ($earnings_diff > 0) {
            // Actualiza campanha.spent
            $pdo->prepare('
                UPDATE campaigns SET spent = spent + ? WHERE id = ?
            ')->execute([$earnings_diff, $sub['campaign_id']]);

            // Actualiza carteira do creator
            $walletStmt = $pdo->prepare('SELECT id FROM wallets WHERE user_id = ?');
            $walletStmt->execute([$sub['creator_id']]);
            $wallet = $walletStmt->fetch();

            if ($wallet) {
                $pdo->prepare('
                    UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE id = ?
                ')->execute([$earnings_diff, $wallet['id']]);

                // Regista transacção
                $pdo->prepare('
                    INSERT INTO transactions (wallet_id, user_id, type, amount, description, status)
                    VALUES (?, ?, "earning", ?, ?, "completed")
                ')->execute([
                    $wallet['id'],
                    $sub['creator_id'],
                    $earnings_diff,
                    'Ganhos sincronizados — ' . format_views($new_views) . ' views',
                ]);
            }

            // Actualiza total_earned no creator_profile
            $pdo->prepare('
                UPDATE creator_profiles SET total_earned = total_earned + ?, total_views = total_views + ?
                WHERE user_id = ?
            ')->execute([$earnings_diff, max(0, $new_views - $prev_views), $sub['creator_id']]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }

    return [
        'views'       => $new_views,
        'earnings'    => $new_earnings,
        'video_title' => $title,
        'thumbnail'   => $thumbnail,
        'note'        => $note,
    ];
}

/**
 * Calcula os ganhos com base em views e CPM.
 */
function calculate_earnings(int $views, float $cpm_rate): float {
    return round(($views / 1000) * $cpm_rate, 2);
}

// -----------------------------------------------
// Sessão e Autenticação
// -----------------------------------------------

/**
 * Verifica se há um utilizador autenticado.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['kapta_user']);
}

/**
 * Retorna o utilizador autenticado actual.
 */
function current_user(): ?array {
    return $_SESSION['kapta_user'] ?? null;
}

/**
 * Redireccionamento HTTP simples.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Define uma mensagem flash na sessão.
 */
function flash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Lê e limpa a mensagem flash.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// -----------------------------------------------
// Segurança
// -----------------------------------------------

/**
 * Gera ou obtém o token CSRF da sessão.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica o token CSRF de um POST.
 */
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Sanitiza output HTML.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// -----------------------------------------------
// Labels e Utilitários
// -----------------------------------------------

function platform_label(string $platform): string {
    return match($platform) {
        'youtube'   => 'YouTube',
        'tiktok'    => 'TikTok',
        'instagram' => 'Instagram',
        default     => ucfirst($platform),
    };
}

function status_label_campaign(string $status): string {
    return match($status) {
        'draft'     => 'Rascunho',
        'active'    => 'Activa',
        'paused'    => 'Pausada',
        'completed' => 'Concluída',
        default     => $status,
    };
}

function status_label_submission(string $status): string {
    return match($status) {
        'pending'  => 'Pendente',
        'approved' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'active'   => 'Activa',
        default    => $status,
    };
}

function category_options(): array {
    return ['Música','Lifestyle','Gaming','Tecnologia','Negócios','Entretenimento','Desporto','Moda','Gastronomia','Viagens'];
}

function get_platform_icon(string $platform): string {
    return match($platform) {
        'youtube'   => '▶',
        'tiktok'    => '♪',
        'instagram' => '◈',
        default     => '•',
    };
}
