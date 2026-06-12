<?php
// ============================================================
// KAPTA — Plataforma de Marketing de Influenciadores
// config.php — Configuração Global
// ============================================================

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'kapta_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'Kapta');
define('APP_URL', 'http://localhost/kapta');
define('APP_VERSION', '1.0.0');

// Platform fee (10%)
define('PLATFORM_FEE', 0.10);

// API Keys — Preenche aqui
define('YOUTUBE_API_KEY', 'AIzaSyBSc2ogU5MDldj4GCHdKyHCQV79HSKqNk4'); // Google Cloud Console > YouTube Data API v3
define('META_APP_ID', '');     // Facebook Developer Portal
define('META_APP_SECRET', ''); // Facebook Developer Portal
define('META_REDIRECT_URI', APP_URL . '/api/instagram-callback.php');

// Session
define('SESSION_NAME', 'kapta_session');
define('SESSION_LIFETIME', 86400); // 24h

// Timezone
date_default_timezone_set('Africa/Luanda');

// Error reporting (desativa em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
