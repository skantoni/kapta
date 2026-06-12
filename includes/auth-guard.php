<?php
// ============================================================
// KAPTA — Guardião de Autenticação
// includes/auth-guard.php
// ============================================================

if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config.php';
}

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // true em HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Exige que o utilizador esteja autenticado.
 * Se não estiver, redireciona para o login com URL de retorno.
 */
function require_auth(): void {
    if (!is_logged_in()) {
        $return = urlencode($_SERVER['REQUEST_URI'] ?? '');
        redirect(APP_URL . '/auth/login.php?return=' . $return);
    }
}

/**
 * Exige que o utilizador tenha uma função específica.
 * Redireciona conforme a função actual do utilizador.
 */
function require_role(string $role): void {
    require_auth();

    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        // Redireciona para o dashboard apropriado
        $dashboard = match($user['role'] ?? '') {
            'brand'   => APP_URL . '/brand/dashboard.php',
            'creator' => APP_URL . '/creator/dashboard.php',
            'admin'   => APP_URL . '/admin/dashboard.php',
            default   => APP_URL . '/auth/login.php',
        };
        flash('Não tens permissão para aceder a essa página.', 'error');
        redirect($dashboard);
    }
}

/**
 * Redireciona utilizadores autenticados para o seu dashboard.
 * Útil nas páginas de login/registo.
 */
function redirect_if_logged_in(): void {
    if (is_logged_in()) {
        $user = current_user();
        $dashboard = match($user['role']) {
            'brand'   => APP_URL . '/brand/dashboard.php',
            'creator' => APP_URL . '/creator/dashboard.php',
            'admin'   => APP_URL . '/admin/dashboard.php',
            default   => APP_URL . '/',
        };
        redirect($dashboard);
    }
}
