<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_name(SESSION_NAME);
session_start();

$page_title = 'Paga apenas por resultados';
include 'includes/header.php';
?>
<style>
    /* ── Landing Page Custom Layout (No Tailwind) ── */
    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }
    
    .landing-nav {
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 100;
        background: rgba(8, 8, 15, 0.85);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--dark-border);
        padding: 16px 0;
    }
    
    .nav-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .nav-logo {
        font-size: 1.5rem;
        font-weight: 800;
        font-family: 'Outfit', sans-serif;
    }
    
    .nav-menu {
        display: flex;
        gap: 32px;
        align-items: center;
    }
    
    .nav-link {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--text-secondary);
        transition: color 0.3s;
    }
    
    .nav-link:hover {
        color: var(--text-primary);
    }
    
    .nav-actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* Hero Section */
    .hero {
        min-height: 100vh;
        position: relative;
        display: flex;
        align-items: center;
        overflow: hidden;
        padding-top: 80px;
        background: radial-gradient(circle at center, rgba(245, 200, 66, 0.05) 0%, var(--dark-bg) 70%);
    }
    
    #auth-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0.4;
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 10;
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    
    .hero-title {
        font-size: 4.5rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 24px;
        font-family: 'Outfit', sans-serif;
    }
    
    .hero-subtitle {
        font-size: 1.25rem;
        color: var(--text-secondary);
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .hero-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    /* Floating Stats */
    .floating-stats {
        position: absolute;
        z-index: 5;
        border-radius: 16px;
        background: rgba(15, 15, 26, 0.6);
        backdrop-filter: blur(10px);
        border: 1px solid var(--dark-border);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        animation: float 6s ease-in-out infinite;
    }
    
    .fs-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .fs-label { font-size: 0.75rem; color: var(--text-secondary); }
    .fs-value { font-weight: 700; color: var(--text-primary); font-size: 1.25rem; }
    
    .fs-1 { top: 20%; left: 10%; animation-delay: 0s; }
    .fs-1 .fs-icon { background: rgba(239, 68, 68, 0.1); color: var(--accent-red); }
    
    .fs-2 { bottom: 25%; right: 10%; animation-delay: 2s; }
    .fs-2 .fs-icon { background: rgba(245, 200, 66, 0.1); color: var(--gold-primary); }
    
    .fs-3 { top: 30%; right: 15%; animation-delay: 4s; }
    .fs-3 .fs-icon { background: rgba(124, 58, 237, 0.1); color: var(--accent-purple); }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    /* Generic Sections */
    .section {
        padding: 96px 0;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 64px;
    }
    
    .section-title {
        font-size: 2.5rem;
        margin-bottom: 16px;
    }
    
    .section-desc {
        color: var(--text-secondary);
        font-size: 1.125rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Grid Layouts */
    .grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
    }
    
    .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 64px;
        align-items: center;
    }

    /* Step Cards */
    .step-card {
        text-align: center;
        padding: 40px 32px;
    }
    
    .step-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 24px auto;
    }
    
    .step-icon.brand { background: rgba(245, 200, 66, 0.1); border: 1px solid rgba(245, 200, 66, 0.2); color: var(--gold-primary); }
    .step-icon.creator { background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.2); color: var(--accent-purple); }
    .step-icon.system { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--accent-green); }

    /* Feature Lists */
    .feature-list {
        list-style: none;
        margin-top: 32px;
        margin-bottom: 32px;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        color: var(--text-primary);
        font-size: 1.05rem;
    }

    /* Decorative visuals */
    .visual-wrapper {
        position: relative;
        perspective: 1000px;
    }
    
    .visual-glow {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.2;
        z-index: 0;
    }
    
    .visual-glow.brand { background: var(--gold-primary); }
    .visual-glow.creator { background: var(--accent-purple); }
    
    .visual-card {
        position: relative;
        z-index: 10;
        padding: 32px;
        transform: rotateY(-10deg) rotateX(5deg);
        box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    
    .visual-card.creator {
        transform: rotateY(10deg) rotateX(5deg);
        text-align: center;
    }

    /* Logos Bar */
    .logos-bar {
        padding: 48px 0;
        border-top: 1px solid var(--dark-border);
        border-bottom: 1px solid var(--dark-border);
        background: rgba(0,0,0,0.4);
        text-align: center;
    }
    
    .logos-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--text-secondary);
        margin-bottom: 24px;
        font-weight: 600;
    }
    
    .logos-grid {
        display: flex;
        justify-content: center;
        gap: 64px;
        opacity: 0.5;
    }
    
    .logo-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* Badges */
    .pill-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 24px;
    }
    
    .pill-badge.brand { background: rgba(245, 200, 66, 0.1); color: var(--gold-primary); border: 1px solid rgba(245, 200, 66, 0.2); }
    .pill-badge.creator { background: rgba(124, 58, 237, 0.1); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.2); }

    /* Footer */
    .site-footer {
        background: #000;
        border-top: 1px solid var(--dark-border);
        padding: 40px 0;
    }
    
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .footer-links {
        display: flex;
        gap: 24px;
    }

    /* Utilities */
    .mt-4 { margin-top: 16px; }
    .mt-8 { margin-top: 32px; }
    .mb-2 { margin-bottom: 8px; }
    .mb-4 { margin-bottom: 16px; }
    .text-center { text-align: center; }
    
    /* Responsive */
    @media (max-width: 992px) {
        .grid-2, .grid-3 { grid-template-columns: 1fr; gap: 48px; }
        .hero-title { font-size: 3.5rem; }
        .floating-stats { display: none; }
        .visual-card { transform: none; }
        .nav-menu { display: none; }
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; }
        .hero-actions { flex-direction: column; }
        .logos-grid { flex-wrap: wrap; gap: 32px; }
        .footer-content { flex-direction: column; gap: 16px; }
    }
</style>

<!-- Navbar -->
<nav class="landing-nav">
    <div class="container nav-content">
        <a href="#" class="nav-logo gradient-text">Kapta.</a>
        
        <div class="nav-menu">
            <a href="#como-funciona" class="nav-link">Como Funciona</a>
            <a href="#marcas" class="nav-link">Para Marcas</a>
            <a href="#creators" class="nav-link">Para Creators</a>
        </div>
        
        <div class="nav-actions">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo current_user()['role']; ?>/dashboard.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="nav-link" style="margin-right: 16px;">Entrar</a>
                <a href="auth/register.php" class="btn btn-primary">Criar Conta</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <canvas id="auth-canvas"></canvas>
    
    <div class="floating-stats fs-1">
        <div class="fs-icon"><i class="ph ph-youtube-logo"></i></div>
        <div>
            <div class="fs-label">Views Pagas</div>
            <div class="fs-value">60M+</div>
        </div>
    </div>
    
    <div class="floating-stats fs-2">
        <div class="fs-icon"><i class="ph ph-money"></i></div>
        <div>
            <div class="fs-label">Pagamentos (Kz)</div>
            <div class="fs-value">Kz 2.5M+</div>
        </div>
    </div>
    
    <div class="floating-stats fs-3">
        <div class="fs-icon"><i class="ph ph-users"></i></div>
        <div>
            <div class="fs-label">Creators Ativos</div>
            <div class="fs-value">1,200+</div>
        </div>
    </div>
    
    <div class="container hero-content">
        <h1 class="hero-title animate-on-scroll">
            <span style="color: #fff; display: block; margin-bottom: 8px;">Capta Views.</span>
            <span class="gradient-text">Ganha Kz.</span>
        </h1>
        <p class="hero-subtitle animate-on-scroll" style="animation-delay: 0.2s;">
            A plataforma angolana que conecta marcas a creators. Paga apenas por resultados reais e verificados nas maiores redes sociais.
        </p>
        
        <div class="hero-actions animate-on-scroll" style="animation-delay: 0.4s;">
            <a href="auth/register.php?role=brand" class="btn btn-primary" style="padding: 16px 32px; font-size: 1.1rem; box-shadow: 0 0 30px rgba(245,200,66,0.3);">
                Sou uma Marca <i class="ph ph-arrow-right"></i>
            </a>
            <a href="auth/register.php?role=creator" class="btn btn-secondary" style="padding: 16px 32px; font-size: 1.1rem; background: rgba(255,255,255,0.05);">
                Sou um Creator <i class="ph ph-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Logos Section -->
<section class="logos-bar">
    <div class="container">
        <div class="logos-label">Funciona com as tuas plataformas favoritas</div>
        <div class="logos-grid">
            <div class="logo-item"><i class="ph ph-youtube-logo" style="font-size: 32px;"></i> YouTube</div>
            <div class="logo-item"><i class="ph ph-tiktok-logo" style="font-size: 32px;"></i> TikTok</div>
            <div class="logo-item"><i class="ph ph-instagram-logo" style="font-size: 32px;"></i> Instagram</div>
        </div>
    </div>
</section>

<!-- How it works -->
<section id="como-funciona" class="section">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2 class="section-title">Como a Kapta funciona</h2>
            <p class="section-desc">Um modelo justo para marcas e creators. O risco é eliminado e o foco é 100% no desempenho.</p>
        </div>
        
        <div class="grid-3">
            <div class="glass-card step-card animate-on-scroll">
                <div class="step-icon brand"><i class="ph ph-megaphone"></i></div>
                <h3 class="mb-4">1. Marca cria Campanha</h3>
                <p>A marca define o orçamento e o valor a pagar por cada 1.000 visualizações (CPM) num vídeo e as instruções.</p>
            </div>
            
            <div class="glass-card step-card animate-on-scroll" style="animation-delay: 0.2s;">
                <div class="step-icon creator"><i class="ph ph-video-camera"></i></div>
                <h3 class="mb-4">2. Creators Submetem</h3>
                <p>Creators gravam os vídeos, publicam nas suas redes (TikTok, YT, IG) e submetem o link na plataforma.</p>
            </div>
            
            <div class="glass-card step-card animate-on-scroll" style="animation-delay: 0.4s;">
                <div class="step-icon system"><i class="ph ph-coins"></i></div>
                <h3 class="mb-4">3. Views = Dinheiro</h3>
                <p>A Kapta monitoriza as views reais. A marca apenas paga pelas views geradas e o creator ganha em Kz automaticamente.</p>
            </div>
        </div>
    </div>
</section>

<!-- For Brands -->
<section id="marcas" class="section" style="background: rgba(0,0,0,0.3); border-top: 1px solid var(--dark-border);">
    <div class="container">
        <div class="grid-2">
            <div class="animate-on-scroll">
                <div class="pill-badge brand">Para Marcas</div>
                <h2 class="section-title">O Fim das Campanhas de Risco</h2>
                <p class="section-desc" style="margin: 0; text-align: left;">Já pagou fortunas a um influenciador por um post que não teve alcance? Com a Kapta, define um CPM e paga <strong>apenas e exclusivamente</strong> pelas views que recebe.</p>
                
                <ul class="feature-list">
                    <li class="feature-item"><i class="ph ph-check-circle text-gold text-xl"></i> Pague apenas pelo alcance comprovado</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-gold text-xl"></i> Acesso a milhares de micro e macro creators</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-gold text-xl"></i> Dashboard de performance em tempo real</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-gold text-xl"></i> Total controlo do orçamento</li>
                </ul>
                
                <a href="auth/register.php?role=brand" class="btn btn-primary">Criar Primeira Campanha</a>
            </div>
            
            <div class="visual-wrapper animate-on-scroll" style="animation-delay: 0.2s;">
                <div class="visual-glow brand"></div>
                <div class="glass-card visual-card">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                        <div style="width: 48px; height: 48px; background: var(--dark-surface); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">K.</div>
                        <div>
                            <div style="font-weight: bold; font-size: 1.1rem;">Lançamento Produto X</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Status: Ativa</div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px;">
                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Gasto: Kz 25.000 / Kz 100.000</span>
                        <span style="font-weight: bold; color: var(--gold-primary); font-size: 1.2rem;">25%</span>
                    </div>
                    <div style="height: 8px; background: var(--dark-surface); border-radius: 4px; overflow: hidden; margin-bottom: 32px;">
                        <div style="height: 100%; background: var(--gold-primary); width: 25%;"></div>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.5); padding: 16px; border-radius: 12px; border: 1px solid var(--dark-border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;"><i class="ph ph-youtube-logo text-red" style="font-size: 20px;"></i> Video Submetido</div>
                            <div style="color: var(--accent-green); font-weight: bold;">+ Kz 5.000</div>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">5.000 views aprovadas hoje</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- For Creators -->
<section id="creators" class="section">
    <div class="container">
        <div class="grid-2">
            <div class="visual-wrapper animate-on-scroll" style="order: 1;">
                <div class="visual-glow creator"></div>
                <div class="glass-card visual-card creator">
                    <h3 style="color: var(--text-secondary); margin-bottom: 8px; font-weight: normal;">Saldo Disponível</h3>
                    <div style="font-size: 3.5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: var(--gold-primary); margin-bottom: 32px;">Kz 124.500</div>
                    <button class="btn btn-primary" style="width: 100%;">Levantar para IBAN</button>
                    
                    <div style="margin-top: 32px; background: var(--dark-surface); padding: 16px; border-radius: 16px; border: 1px solid var(--dark-border); display: flex; align-items: center; gap: 16px; text-align: left;">
                        <i class="ph ph-tiktok-logo" style="font-size: 32px;"></i>
                        <div>
                            <div style="font-weight: 600; font-size: 1.1rem; color: #fff;">Reel Novo Smartphone</div>
                            <div style="font-size: 0.9rem; color: var(--gold-primary);">+ 15.000 views geradas hoje</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="animate-on-scroll" style="order: 2; animation-delay: 0.2s;">
                <div class="pill-badge creator">Para Creators</div>
                <h2 class="section-title">Monetiza a Tua Influência</h2>
                <p class="section-desc" style="margin: 0; text-align: left;">Não esperes ser contactado por marcas. Explora campanhas ativas, grava o teu conteúdo e ganha Kz por cada view que gerares na tua plataforma preferida.</p>
                
                <ul class="feature-list">
                    <li class="feature-item"><i class="ph ph-check-circle text-xl" style="color: var(--accent-purple);"></i> Sem contratos de exclusividade</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-xl" style="color: var(--accent-purple);"></i> Escolha as marcas com quem quer trabalhar</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-xl" style="color: var(--accent-purple);"></i> Levantamentos diretos para o IBAN</li>
                    <li class="feature-item"><i class="ph ph-check-circle text-xl" style="color: var(--accent-purple);"></i> Funciona com perfis de qualquer tamanho</li>
                </ul>
                
                <a href="auth/register.php?role=creator" class="btn btn-secondary mt-8" style="border-color: var(--accent-purple); color: #fff;">Criar Conta Creator</a>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="section" style="border-top: 1px solid var(--dark-border); background: linear-gradient(180deg, transparent 0%, #000 100%);">
    <div class="container text-center animate-on-scroll">
        <h2 class="section-title">Preço Transparente</h2>
        <p class="section-desc mb-8">Simples, direto e sem surpresas.</p>
        
        <div class="glass-card" style="padding: 64px 32px; max-width: 600px; margin: 0 auto; position: relative;">
            <div style="position: absolute; top: 0; right: 0; background: var(--gold-primary); color: #000; font-size: 0.8rem; font-weight: bold; padding: 6px 16px; border-bottom-left-radius: 12px;">MVP</div>
            
            <div style="font-size: 5rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #fff; margin-bottom: 16px; line-height: 1;">10%</div>
            <p style="color: var(--text-secondary); margin-bottom: 48px; font-size: 1.1rem;">Taxa de plataforma apenas no carregamento (Marcas)</p>
            
            <div style="display: flex; flex-direction: column; gap: 16px; text-align: left; max-width: 300px; margin: 0 auto 48px auto;">
                <div style="display: flex; gap: 12px; font-size: 1.05rem;"><i class="ph ph-check text-gold" style="font-size: 24px;"></i> Sem mensalidades</div>
                <div style="display: flex; gap: 12px; font-size: 1.05rem;"><i class="ph ph-check text-gold" style="font-size: 24px;"></i> Creators levantam a custo 0</div>
                <div style="display: flex; gap: 12px; font-size: 1.05rem;"><i class="ph ph-check text-gold" style="font-size: 24px;"></i> Análise de dados incluída</div>
            </div>
            
            <a href="auth/register.php" class="btn btn-primary" style="width: 100%; max-width: 300px; padding: 16px;">Começar Agora</a>
        </div>
    </div>
</section>

<!-- Scripts -->
<script src="assets/js/main.js"></script>

<!-- Footer -->
<footer class="site-footer">
    <div class="container footer-content">
        <div style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: #fff;">Kapta.</div>
        <div>&copy; <?php echo date('Y'); ?> Kapta Angola. Todos os direitos reservados.</div>
        <div class="footer-links">
            <a href="#" style="transition: color 0.3s;">Termos</a>
            <a href="#" style="transition: color 0.3s;">Privacidade</a>
        </div>
    </div>
</footer>
</body>
</html>
