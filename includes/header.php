<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Core Stone Indonesia - Toko online batu akik terpercaya di Indonesia. Menyediakan berbagai jenis batu akik alami berkualitas tinggi.">
    <meta name="keywords" content="batu akik, batu mulia, cincin akik, batu bacan, kalimaya, giok, kecubung, toko batu akik">
    <meta name="author" content="Core Stone Indonesia">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:title" content="Core Stone Indonesia - Batu Akik Berkualitas">
    <meta property="og:description" content="Toko online batu akik terpercaya di Indonesia">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Core Stone Indonesia - Batu Akik Berkualitas">
    <meta property="twitter:description" content="Toko online batu akik terpercaya di Indonesia">
    
    <title><?php echo $pageTitle ?? 'Core Stone Indonesia'; ?> - Batu Akik Berkualitas</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Store",
        "name": "Core Stone Indonesia",
        "description": "Toko online batu akik terpercaya di Indonesia",
        "url": "<?php echo SITE_URL; ?>",
        "logo": "<?php echo SITE_URL; ?>/assets/images/logo.png",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+62-812-1493-2916",
            "contactType": "customer service"
        },
        "sameAs": [
            "https://www.facebook.com/corestoneid",
            "https://www.instagram.com/corestoneid"
        ]
    }
    </script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <div class="container">
                <div class="header-top-left">
                    <i class="fas fa-truck"></i> Gratis ongkir untuk pembelian di atas Rp 500.000
                </div>
                <div class="header-top-right">
                    <i class="fas fa-phone"></i> Hubungi: 0812-1493-2916
                </div>
            </div>
        </div>
        
        <div class="header-main">
            <div class="container">
                <a href="<?php echo SITE_URL; ?>" class="logo">
                    <i class="fas fa-gem"></i>
                    <span>Core Stone</span>
                </a>
                
                <div class="search-bar">
                    <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
                        <input type="text" name="q" placeholder="Cari batu akik...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/account.php" class="header-action-item">
                            <i class="fas fa-user"></i>
                            <span>Akun</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="header-action-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="header-action-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Keranjang</span>
                        <?php 
                        $cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
                        if ($cart_count > 0):
                        ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <nav class="nav-menu" id="navMenu">
            <div class="container">
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>" class="<?php echo ($currentPage ?? '') == 'home' ? 'active' : ''; ?>">Beranda</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php" class="<?php echo ($currentPage ?? '') == 'products' ? 'active' : ''; ?>">Produk</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/categories.php" class="<?php echo ($currentPage ?? '') == 'categories' ? 'active' : ''; ?>">Kategori</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about.php" class="<?php echo ($currentPage ?? '') == 'about' ? 'active' : ''; ?>">Tentang Kami</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo ($currentPage ?? '') == 'contact' ? 'active' : ''; ?>">Kontak</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
