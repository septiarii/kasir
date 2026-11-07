<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Sistem Kasir Hermas Cell</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .navbar {
            background: #1a1a2e;
            color: white;
            padding: 0 0.6rem;
            min-height: 54px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
        .navbar-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.3rem;
        }
        .navbar-left { display: flex; align-items: center; min-width: 40px; }
        .navbar-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 0.2rem 0.3rem;
            min-width: 40px;
            min-height: 40px;
            border-radius: 8px;
            transition: all 0.2s;
            touch-action: manipulation;
        }
        .navbar-toggle:active { background: rgba(255,255,255,0.1); }
        .navbar-brand { display: flex; align-items: center; gap: 0.4rem; }
        .navbar-brand .icon { font-size: 1.1rem; }
        .navbar-brand h1 { font-size: 1rem; font-weight: 700; color: #e94560; white-space: nowrap; letter-spacing: -0.3px; }
        .user-info { display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; flex-shrink: 0; }
        .user-info .name { color: rgba(255,255,255,0.7); font-size: 0.7rem; max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .role-badge { background: rgba(255,255,255,0.1); padding: 0.1rem 0.5rem; border-radius: 12px; font-size: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(255,255,255,0.8); white-space: nowrap; font-weight: 700; }
        .navbar-menu { display: flex; list-style: none; gap: 0.15rem; flex-wrap: wrap; align-items: center; }
        .navbar-menu li a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 0.35rem 0.7rem; border-radius: 6px; transition: all 0.2s; font-weight: 500; font-size: 0.78rem; white-space: nowrap; }
        .navbar-menu li a:active { background: rgba(255,255,255,0.08); }
        .navbar-menu li a.active { background: #e94560; color: white; }
        .logout-btn { background: #e94560; color: white !important; }
        .logout-btn:active { background: #c73652 !important; }
        .navbar-menu .settings-link { border-left: 1px solid rgba(255,255,255,0.1); padding-left: 0.7rem; margin-left: 0.2rem; }

        @media (max-width: 768px) {
            .navbar { padding: 0 0.4rem; min-height: 48px; }
            .navbar-toggle { display: flex; align-items: center; justify-content: center; font-size: 1.2rem; min-width: 36px; min-height: 36px; }
            .navbar-brand { flex: 1; justify-content: center; }
            .navbar-brand .icon { font-size: 0.95rem; }
            .navbar-brand h1 { font-size: 0.85rem; }
            .user-info .name { display: none; }
            .role-badge { font-size: 0.45rem; padding: 0.08rem 0.4rem; background: rgba(255,255,255,0.08); }
            .navbar-menu {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 48px;
                left: 0;
                width: 100%;
                background: #1a1a2e;
                padding: 0.3rem 0;
                gap: 0;
                box-shadow: 0 4px 16px rgba(0,0,0,0.4);
                border-top: 1px solid rgba(255,255,255,0.05);
                max-height: calc(100vh - 48px);
                overflow-y: auto;
            }
            .navbar-menu.open { display: flex; animation: slideDown 0.25s ease; }
            .navbar-menu li a { padding: 0.6rem 1rem; display: block; width: 100%; border-radius: 0; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: rgba(255,255,255,0.85); padding-left: 1.2rem; }
            .navbar-menu li a:active { background: rgba(255,255,255,0.06); }
            .navbar-menu li:last-child a { border-bottom: none; }
            .navbar-menu .settings-link { border-left: none; padding-left: 0; margin-left: 0; }
            .navbar-menu li a.logout-btn { background: #e94560; color: white !important; margin-top: 0.15rem; border-radius: 0; padding: 0.7rem 1.2rem; text-align: center; font-weight: 700; }
            .navbar-menu li a.logout-btn:active { background: #c73652 !important; }
            .navbar-menu li a .menu-icon { margin-right: 0.6rem; font-size: 1rem; }
        }
        @media (max-width: 400px) {
            .navbar-brand .icon { font-size: 0.8rem; }
            .navbar-brand h1 { font-size: 0.75rem; }
            .navbar-toggle { font-size: 1rem; min-width: 32px; min-height: 32px; }
            .role-badge { font-size: 0.4rem; padding: 0.05rem 0.35rem; }
            .navbar-menu li a { font-size: 0.8rem; padding: 0.5rem 0.8rem; padding-left: 1rem; }
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <nav class="navbar" role="navigation">
        <div class="navbar-container">
            <div class="navbar-left">
                <button class="navbar-toggle" id="menuToggle" aria-label="Toggle menu" aria-expanded="false">
                    <span>☰</span>
                </button>
            </div>
            <div class="navbar-brand">
                <span class="icon">🏪</span>
                <h1>Hermas POS</h1>
            </div>
            <?php if (isLoggedIn()): ?>
            <div class="user-info">
                <span class="name"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
                <span class="role-badge"><?php echo htmlspecialchars(strtoupper($_SESSION['role'] ?? '')); ?></span>
            </div>
            <?php endif; ?>
            <ul class="navbar-menu" id="navbarMenu" role="menubar">
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php" role="menuitem"><span class="menu-icon">📊</span> Dashboard</a></li>
                    
                    <?php if (isAdmin()): ?>
                        <li><a href="users.php" role="menuitem"><span class="menu-icon">👥</span> Pengguna</a></li>
                        <li><a href="categories.php" role="menuitem"><span class="menu-icon">📁</span> Kategori</a></li>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                        <li><a href="products.php" role="menuitem"><span class="menu-icon">📦</span> Produk</a></li>
                    <?php endif; ?>
                    
                    <!-- ============================================================
                    BAGIAN 3: TAMBAHKAN MENU HISTORI DI SINI
                    ============================================================ -->
                    <?php if (isAdmin() || isKasir()): ?>
                        <li><a href="transactions.php" role="menuitem"><span class="menu-icon">🧾</span> Transaksi</a></li>
                        <li><a href="history.php" role="menuitem"><span class="menu-icon">📋</span> Histori</a></li>
                    <?php endif; ?>
                    
                    <?php if (isOwner() || isAdmin()): ?>
                        <li><a href="reports.php" role="menuitem"><span class="menu-icon">📊</span> Laporan</a></li>
                        <li><a href="forecasting.php" role="menuitem"><span class="menu-icon">📈</span> Prediksi</a></li>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                        <li class="settings-link"><a href="settings.php" role="menuitem"><span class="menu-icon">⚙️</span> Pengaturan</a></li>
                    <?php endif; ?>
                    
                    <li><a href="logout.php" class="logout-btn" role="menuitem">🚪 Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="container" role="main">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('menuToggle');
    const menu = document.getElementById('navbarMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen);
            toggle.innerHTML = isOpen ? '<span>✕</span>' : '<span>☰</span>';
        });
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '<span>☰</span>';
            }
        });
        menu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '<span>☰</span>';
            });
        });
    }
});
</script>