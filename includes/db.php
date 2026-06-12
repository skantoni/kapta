<?php
// ============================================================
// KAPTA — Ligação à Base de Dados (PDO)
// includes/db.php
// ============================================================

if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config.php';
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Em produção, nunca mostrar detalhes da ligação
        http_response_code(503);
        die(json_encode([
            'success' => false,
            'message' => 'Erro de ligação à base de dados. Tenta novamente mais tarde.',
            'debug'   => (defined('APP_VERSION') ? $e->getMessage() : 'hidden')
        ]));
    }

    return $pdo;
}

// Atalho global
$pdo = get_pdo();
