<?php
// ============================================================
// KAPTA — Instalador da Base de Dados
// setup/install.php
// ============================================================

$root = dirname(__DIR__);
require_once $root . '/config.php';

$installed = false;
$errors    = [];
$messages  = [];
$already   = false;

// Verifica se já está instalado
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $testPdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $testPdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $already = true;
    }
} catch (Exception $e) {
    // DB pode não existir ainda — normal na primeira instalação
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        $errors[] = 'Ficheiro database.sql não encontrado em ' . $sqlFile;
    } else {
        try {
            // Conecta sem DB para criar
            $pdoInit = new PDO(
                'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sql = file_get_contents($sqlFile);

            // Divide em statements individuais
            $pdoInit->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdoInit->exec('USE `' . DB_NAME . '`');

            // Remove comentários de linha
            $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

            // Executa cada statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => strlen($s) > 3
            );

            foreach ($statements as $statement) {
                try {
                    $pdoInit->exec($statement);
                    $messages[] = '✓ Executado: ' . substr(trim($statement), 0, 60) . '...';
                } catch (PDOException $e) {
                    // Ignora duplicados de chave primária no seed data
                    if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
                        $messages[] = '⚠ Já existe (ignorado): ' . substr(trim($statement), 0, 60) . '...';
                    } else {
                        $errors[] = 'Erro: ' . $e->getMessage() . ' em: ' . substr(trim($statement), 0, 80);
                    }
                }
            }

            if (empty($errors)) {
                $installed = true;
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro de ligação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kapta — Instalação</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --gold: #F5C842;
      --gold-dark: #C9A227;
      --dark: #08080F;
      --card: #0F0F1A;
      --surface: #161628;
      --border: rgba(245,200,66,0.2);
      --green: #10B981;
      --red: #EF4444;
      --yellow: #F59E0B;
      --text: #fff;
      --muted: #8B8BA8;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 40px 20px;
    }
    .install-wrap {
      width: 100%;
      max-width: 760px;
    }
    .logo {
      font-family: 'Outfit', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #F5C842, #FFE566, #C9A227);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-align: center;
      margin-bottom: 8px;
    }
    .subtitle {
      text-align: center;
      color: var(--muted);
      margin-bottom: 40px;
      font-size: 0.95rem;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px;
      margin-bottom: 24px;
    }
    .card h2 {
      font-family: 'Outfit', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 16px;
      color: var(--gold);
    }
    .warning-box {
      background: rgba(245, 158, 11, 0.1);
      border: 1px solid rgba(245, 158, 11, 0.4);
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 24px;
    }
    .warning-box p { color: var(--yellow); font-weight: 500; }
    .warning-box small { color: var(--muted); display: block; margin-top: 6px; }
    .success-box {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.4);
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 24px;
    }
    .success-box h3 { color: var(--green); font-family: 'Outfit', sans-serif; margin-bottom: 8px; }
    .success-box p  { color: #ccc; font-size: 0.9rem; }
    .error-box {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.4);
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 24px;
    }
    .error-box h3 { color: var(--red); margin-bottom: 8px; font-family: 'Outfit', sans-serif; }
    .error-box ul { list-style: none; }
    .error-box ul li { color: #f87171; font-size: 0.85rem; padding: 2px 0; }
    .log-box {
      background: #060610;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 10px;
      padding: 16px;
      max-height: 300px;
      overflow-y: auto;
      font-family: monospace;
      font-size: 0.8rem;
      margin-top: 16px;
    }
    .log-box p { padding: 2px 0; }
    .log-box .ok  { color: var(--green); }
    .log-box .warn { color: var(--yellow); }
    .log-box .err { color: var(--red); }
    .config-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .config-item {
      background: var(--surface);
      border-radius: 8px;
      padding: 12px 16px;
    }
    .config-item label { font-size: 0.75rem; color: var(--muted); display: block; margin-bottom: 4px; }
    .config-item span  { font-size: 0.9rem; color: var(--text); font-weight: 500; }
    .config-item span.ok  { color: var(--green); }
    .config-item span.err { color: var(--red); }
    .accounts-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
    }
    .accounts-table th {
      text-align: left;
      padding: 8px 12px;
      color: var(--muted);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 1px solid var(--border);
    }
    .accounts-table td {
      padding: 10px 12px;
      font-size: 0.88rem;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .badge-admin   { background: rgba(124,58,237,0.2); color: #a78bfa; }
    .badge-brand   { background: rgba(245,200,66,0.2);  color: #F5C842; }
    .badge-creator { background: rgba(16,185,129,0.2); color: #34d399; }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 32px;
      border-radius: 12px;
      font-family: 'Outfit', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      text-decoration: none;
      transition: all 0.3s;
    }
    .btn-gold {
      background: linear-gradient(135deg, #F5C842, #C9A227);
      color: #08080F;
    }
    .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(245,200,66,0.4); }
    .btn-outline {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text);
    }
    .btn-outline:hover { border-color: var(--gold); color: var(--gold); }
    .btn-danger {
      background: rgba(239,68,68,0.15);
      border: 1px solid rgba(239,68,68,0.4);
      color: var(--red);
    }
    .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
    form { margin-top: 8px; }
    .check-row {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 0.9rem;
    }
    .check-row:last-child { border-bottom: none; }
    .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot.ok { background: var(--green); }
    .dot.err { background: var(--red); }
    .dot.warn { background: var(--yellow); }
  </style>
</head>
<body>
<div class="install-wrap">
  <div class="logo">⚡ Kapta</div>
  <p class="subtitle">Instalador da Plataforma v<?= APP_VERSION ?></p>

  <?php if ($already && !$installed): ?>
  <div class="warning-box">
    <p>⚠️ A base de dados já parece estar instalada!</p>
    <small>A tabela 'users' já existe em '<?= DB_NAME ?>'. Prosseguir irá re-executar o SQL (os dados de seed podem ser ignorados por já existirem).</small>
  </div>
  <?php endif; ?>

  <?php if ($installed): ?>
  <div class="success-box">
    <h3>✅ Instalação Concluída com Sucesso!</h3>
    <p>A base de dados <strong><?= DB_NAME ?></strong> foi criada e configurada. Podes agora aceder à plataforma.</p>
  </div>
  <div class="actions" style="margin-bottom:24px;">
    <a href="<?= APP_URL ?>" class="btn btn-gold">🏠 Ir para o Início</a>
    <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline">🔐 Iniciar Sessão</a>
  </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
  <div class="error-box">
    <h3>❌ Erros durante a instalação</h3>
    <ul>
      <?php foreach ($errors as $e): ?>
        <li>• <?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- Configuração Actual -->
  <div class="card">
    <h2>⚙️ Configuração Detectada</h2>
    <div class="config-grid">
      <div class="config-item">
        <label>Servidor MySQL</label>
        <span><?= DB_HOST ?></span>
      </div>
      <div class="config-item">
        <label>Base de Dados</label>
        <span><?= DB_NAME ?></span>
      </div>
      <div class="config-item">
        <label>Utilizador</label>
        <span><?= DB_USER ?></span>
      </div>
      <div class="config-item">
        <label>URL da App</label>
        <span><?= APP_URL ?></span>
      </div>
      <div class="config-item">
        <label>YouTube API Key</label>
        <span class="<?= YOUTUBE_API_KEY ? 'ok' : 'err' ?>">
          <?= YOUTUBE_API_KEY ? '✓ Configurada' : '✗ Não configurada' ?>
        </span>
      </div>
      <div class="config-item">
        <label>Meta App ID</label>
        <span class="<?= META_APP_ID ? 'ok' : 'err' ?>">
          <?= META_APP_ID ? '✓ Configurado' : '✗ Não configurado' ?>
        </span>
      </div>
    </div>

    <div style="margin-top:20px;">
      <?php
      // Verificações de sistema
      $checks = [
        ['PDO Extension',         extension_loaded('pdo'),      true],
        ['PDO MySQL Driver',      extension_loaded('pdo_mysql'), true],
        ['cURL Extension',        extension_loaded('curl'),      true],
        ['JSON Extension',        extension_loaded('json'),      true],
        ['PHP 8.0+',              version_compare(PHP_VERSION,'8.0','>='), true],
        ['ficheiro database.sql', file_exists(__DIR__.'/database.sql'), true],
      ];
      foreach ($checks as [$label, $ok, $required]):
        $cls = $ok ? 'ok' : ($required ? 'err' : 'warn');
      ?>
      <div class="check-row">
        <div class="dot <?= $cls ?>"></div>
        <span style="flex:1;"><?= $label ?></span>
        <span style="color:<?= $ok ? 'var(--green)' : 'var(--red)' ?>;font-size:0.85rem;">
          <?= $ok ? 'OK' : ($required ? 'ERRO' : 'Aviso') ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Botão de Instalação -->
  <?php if (!$installed): ?>
  <div class="card">
    <h2>🚀 Instalar Base de Dados</h2>
    <p style="color:var(--muted);margin-bottom:20px;font-size:0.9rem;">
      Isto irá criar a base de dados <strong style="color:var(--text)"><?= DB_NAME ?></strong>,
      todas as tabelas necessárias e inserir dados de demonstração.
      <?php if ($already): ?>
        <br><br><strong style="color:var(--yellow);">⚠️ Atenção: A base de dados já existe. Os dados actuais podem ser afectados.</strong>
      <?php endif; ?>
    </p>
    <form method="POST">
      <input type="hidden" name="install" value="1">
      <div class="actions">
        <button type="submit" class="btn btn-gold">
          ⚡ <?= $already ? 'Re-instalar' : 'Instalar Agora' ?>
        </button>
        <a href="<?= APP_URL ?>" class="btn btn-outline">← Cancelar</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- Log de execução -->
  <?php if (!empty($messages)): ?>
  <div class="card">
    <h2>📋 Log de Execução</h2>
    <div class="log-box">
      <?php foreach ($messages as $msg): ?>
        <p class="<?= str_starts_with($msg,'✓') ? 'ok' : (str_starts_with($msg,'⚠') ? 'warn' : 'err') ?>">
          <?= htmlspecialchars($msg) ?>
        </p>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Contas de Demo -->
  <?php if ($installed): ?>
  <div class="card">
    <h2>👤 Contas de Demonstração</h2>
    <table class="accounts-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Email</th>
          <th>Password</th>
          <th>Função</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Administrador Kapta</td>
          <td>admin@kapta.ao</td>
          <td><code>admin123</code></td>
          <td><span class="badge badge-admin">Admin</span></td>
        </tr>
        <tr>
          <td>Demo Marca</td>
          <td>marca@demo.ao</td>
          <td><code>demo123</code></td>
          <td><span class="badge badge-brand">Marca</span></td>
        </tr>
        <tr>
          <td>João Creator</td>
          <td>creator@demo.ao</td>
          <td><code>demo123</code></td>
          <td><span class="badge badge-creator">Creator</span></td>
        </tr>
      </tbody>
    </table>
    <div class="actions" style="margin-top:20px;">
      <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-gold">🔐 Iniciar Sessão</a>
    </div>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
