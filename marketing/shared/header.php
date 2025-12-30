<?php
// Charger le système i18n utilisé par le header marketing
require_once __DIR__ . '/../includes/i18n.php';

// Récupérer la langue actuelle
$currentLang = getCurrentLanguage();
$langInfo = MarketingI18n::getInstance()->getCurrentLanguageInfo();
?>
<!DOCTYPE html>
<html lang="<?php echo $langInfo['code']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic SEO Meta Tags -->
    <title><?php echo isset($meta['title']) ? $meta['title'] : t('meta_title', 'SERVO – L\'intelligence de la réparation'); ?></title>
    <meta name="description" content="<?php echo isset($meta['description']) ? $meta['description'] : t('meta_description_default'); ?>">
    <meta property="og:title" content="<?php echo isset($meta['title']) ? $meta['title'] : 'SERVO'; ?>">
    <meta property="og:description" content="<?php echo isset($meta['description']) ? $meta['description'] : ''; ?>">
    <meta property="og:image" content="https://servo.tools/assets/images/logo/logoservo_social.png">
    <meta name="twitter:card" content="summary_large_image">
    
    <!-- SEO Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "SERVO",
      "headline": "Logiciel de Gestion pour Réparateurs & SAV",
      "alternativeHeadline": "Le CRM #1 pour la réparation",
      "image": [
        "https://servo.tools/assets/images/logo/logoservo_social.png",
        "https://servo.tools/assets/images/screenshots/dashboard-preview.png"
      ],
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web, iOS, Android",
      "offers": {
        "@type": "Offer",
        "price": "49.00",
        "priceCurrency": "EUR",
        "priceValidUntil": "2026-12-31",
        "availability": "https://schema.org/InStock",
        "url": "https://servo.tools/pricing"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "ratingCount": "150",
        "bestRating": "5",
        "worstRating": "1"
      },
      "featureList": "Gestion SAV, Suivi Réparation, SMS Automatiques, Facturation, Stock, Achats Fournisseurs, Planning",
      "screenshot": "https://servo.tools/assets/images/screenshots/dashboard_hero.png"
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [{
        "@type": "ListItem",
        "position": 1,
        "name": "Accueil",
        "item": "https://servo.tools/"
      },{
        "@type": "ListItem",
        "position": 2,
        "name": "<?php echo isset($meta['title']) ? explode('|', $meta['title'])[0] : 'Page'; ?>",
        "item": "https://servo.tools<?php echo $_SERVER['REQUEST_URI']; ?>"
      }]
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "MDGEEK - SERVO",
      "url": "https://servo.tools",
      "logo": "https://servo.tools/assets/images/logo/logoservo.png",
      "sameAs": [
        "https://www.linkedin.com/company/mdgeek",
        "https://twitter.com/mdgeek"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+33-1-00-00-00-00",
        "contactType": "customer service",
        "areaServed": "FR",
        "availableLanguage": "French"
      }
    }
    </script>
    <!-- Performance Optimization -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Palette "Cyber-Tech" - Dark Mode Default */
            --bg-deep: #030712;      /* Ultra dark blue/black */
            --bg-dark: #0f172a;      /* Dark slate */
            --bg-card: rgba(30, 41, 59, 0.7); /* Glassy dark */
            
            /* Neons & Accents */
            --primary: #06b6d4;      /* Electric Cyan */
            --primary-glow: rgba(6, 182, 212, 0.6);
            --secondary: #8b5cf6;    /* Cyber Purple */
            --secondary-glow: rgba(139, 92, 246, 0.6);
            --accent: #ec4899;       /* Hot Pink */
            --success: #10b981;      /* Neon Green */
            
            /* Text Colors */
            --text-main: #f8fafc;
            --text-sec: #cbd5e1; /* Slate 300 - Better Contrast */
            --text-muted: #64748b;
            
            /* Technical */
            --glass-border: 1px solid rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-blur: blur(12px);
            --neon-shadow: 0 0 10px var(--primary-glow), 0 0 20px rgba(6, 182, 212, 0.3);
            
            /* Variables de compatibilité (pour ne pas casser le code existant) */
            --bg-primary: var(--bg-deep);
            --bg-secondary: var(--bg-dark);
            --text-primary: var(--text-main);
            --text-secondary: var(--text-sec);
            --border-color: rgba(255,255,255,0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            --border-radius: 12px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1e1b4b, #020617);
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        /* Glassmorphism Utilities */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: var(--glass-border);
        }

        .glass-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(6, 182, 212, 0.3);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.15);
        }

        /* Text Effects */
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .text-neon {
            color: var(--primary);
            text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
        }

        /* Buttons Retro-Futuristic */
        .btn-modern {
            position: relative;
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
            overflow: hidden;
            transition: 0.3s;
            z-index: 1;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 0%; height: 100%;
            background: var(--primary);
            z-index: -1;
            transition: 0.3s;
        }
        
        .btn-modern:hover {
            color: #000;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        
        .btn-modern:hover::before {
            width: 100%;
        }

        .btn-glow {
            background: var(--primary);
            color: black;
            font-weight: 700;
            box-shadow: 0 0 15px var(--primary-glow);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-glow:hover {
            transform: scale(1.05);
            background: #22d3ee;
            box-shadow: 0 0 25px var(--primary-glow), 0 0 10px #fff;
            color: black;
        }

        /* Navbar Override */
        .navbar-modern {
            background: rgba(2, 6, 23, 0.85) !important;
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .nav-link {
            color: var(--text-sec) !important;
            font-family: 'Space Grotesk', sans-serif;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
            text-shadow: 0 0 8px var(--primary-glow);
        }

        /* Footer Override */
        footer {
            background: #020617 !important;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* Scrollbar personnalisé */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Navigation */
        .navbar-modern {
            backdrop-filter: saturate(180%) blur(20px);
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color-light);
            padding: 1rem 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .navbar-modern.scrolled {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
        }

        @media (prefers-color-scheme: dark) {
            .navbar-modern {
                background: rgba(15, 23, 42, 0.95);
            }
            
            .navbar-modern.scrolled {
                background: rgba(15, 23, 42, 0.98);
            }
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary) !important;
            text-decoration: none;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-secondary) !important;
            padding: 0.5rem 1rem !important;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
            background: var(--bg-tertiary);
        }

        .dropdown-menu {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(16px);
            background: var(--bg-primary);
        }

        .dropdown-item {
            font-weight: 500;
            color: var(--text-secondary);
            padding: 0.75rem 1.25rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            color: var(--primary);
            background: var(--bg-tertiary);
        }

        /* Mobile navigation improvements */
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='var(--text-secondary)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (prefers-color-scheme: dark) {
            .navbar-toggler-icon {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(203, 213, 225, 0.75)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            }
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                margin-top: 1rem;
                padding: 1rem;
                background: var(--bg-primary);
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg);
            }
            
            .navbar-nav {
                text-align: center;
            }
            
            .nav-item {
                margin-bottom: 0.5rem;
            }
            
            .dropdown-menu {
                position: static !important;
                transform: none !important;
                border: none;
                box-shadow: none;
                background: var(--bg-tertiary);
                margin-top: 0.5rem;
            }
            
            .dropdown-item {
                text-align: center;
                padding: 0.5rem 1rem;
            }
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-glow);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.25);
            color: white;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
        }

        .btn-light {
            background: var(--bg-primary);
            color: var(--text-primary);
            box-shadow: var(--shadow);
        }

        .btn-light:hover {
            background: var(--bg-tertiary);
            transform: translateY(-1px);
            color: var(--text-primary);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Cards */
        .card-modern {
            border: 1px solid var(--border-color-light);
            border-radius: var(--border-radius-lg);
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        @media (prefers-color-scheme: dark) {
            .card-modern {
                background: rgba(30, 41, 59, 0.8);
            }
        }

        .card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            background: var(--bg-primary);
        }

        @media (prefers-color-scheme: dark) {
            .card-modern:hover {
                background: rgba(30, 41, 59, 0.95);
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            }
        }

        .card-modern:hover::before {
            opacity: 1;
        }

        .card-feature {
            border: 1px solid var(--border-color-light);
            border-radius: var(--border-radius-lg);
            background: var(--bg-primary);
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        @media (prefers-color-scheme: dark) {
            .card-feature {
                background: rgba(30, 41, 59, 0.8);
            }
        }

        .card-feature::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-card);
            transition: left 0.6s ease;
            z-index: 0;
        }

        .card-feature:hover::before {
            left: 0;
        }

        .card-feature > * {
            position: relative;
            z-index: 1;
        }

        .card-feature:hover {
            border-color: var(--primary-light);
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-xl);
            background: var(--bg-primary);
        }

        @media (prefers-color-scheme: dark) {
            .card-feature:hover {
                background: rgba(30, 41, 59, 0.95);
            }
        }

        /* Carte SERVO spéciale - garde les couleurs au hover */
        .bg-gradient-primary.card-modern {
            background: var(--gradient-primary) !important;
            color: white !important;
        }

        .bg-gradient-primary.card-modern:hover {
            background: var(--gradient-primary) !important;
            color: white !important;
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(14, 165, 233, 0.3);
        }

        .bg-gradient-primary.card-modern:hover::before {
            opacity: 0;
        }

        /* Typography */
        .display-1, .display-2, .display-3, .display-4 {
            font-weight: 900;
            line-height: 1.2;
            color: var(--text-primary);
        }

        /* Hero title specific styling */
        .bg-gradient-hero .display-3 {
            font-size: 3.5rem !important;
            line-height: 1.1;
            max-width: 600px;
            word-spacing: 0.2em;
        }

        @media (max-width: 992px) {
            .bg-gradient-hero .display-3 {
                font-size: 3rem !important;
                line-height: 1.1;
                max-width: 500px;
            }
        }

        @media (max-width: 768px) {
            .bg-gradient-hero .display-3 {
                font-size: 2.2rem !important;
                line-height: 1.1;
                max-width: 100%;
            }
        }

        .fw-black { font-weight: 900; }
        .fw-extrabold { font-weight: 800; }
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }

        .text-primary { color: var(--primary) !important; }
        .text-secondary { color: var(--secondary) !important; }
        .text-success { color: var(--success) !important; }
        .text-muted { color: var(--text-muted) !important; }

        /* Backgrounds */
        .bg-gradient-primary {
            background: var(--gradient-primary) !important;
            background-image: var(--gradient-primary) !important;
            position: relative;
            overflow: hidden;
        }

        /* Force le gradient même si Bootstrap l'override */
        section.bg-gradient-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0891b2 50%, #14b8a6 100%) !important;
            background-image: linear-gradient(135deg, #0ea5e9 0%, #0891b2 50%, #14b8a6 100%) !important;
        }

        .bg-gradient-primary::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(600px 300px at 30% 40%, rgba(255, 255, 255, 0.1), transparent 70%),
                radial-gradient(400px 200px at 70% 60%, rgba(20, 184, 166, 0.1), transparent 50%);
            pointer-events: none;
        }

        .bg-gradient-primary > * {
            position: relative;
            z-index: 1;
        }

        .bg-gradient-hero {
            background: var(--gradient-hero);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .bg-gradient-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(1200px 600px at 20% 20%, rgba(255, 255, 255, 0.15), transparent 60%),
                radial-gradient(800px 400px at 80% 80%, rgba(14, 165, 233, 0.15), transparent 50%),
                linear-gradient(0deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.05));
            pointer-events: none;
            animation: heroGlow 8s ease-in-out infinite alternate;
        }

        .bg-gradient-hero::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(14, 165, 233, 0.03) 60deg, transparent 120deg);
            animation: heroRotate 20s linear infinite;
            pointer-events: none;
        }

        .bg-gradient-hero > * {
            position: relative;
            z-index: 1;
        }

        /* Sections */
        .section {
            padding: 5rem 0;
            position: relative;
            z-index: 2;
            background: inherit;
        }

        .section-sm {
            padding: 3rem 0;
            position: relative;
            z-index: 2;
            background: inherit;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section {
                padding: 2rem 0;
            }
            
            .section-sm {
                padding: 1.5rem 0;
            }
            
            .btn-lg {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
                width: 100%;
                text-align: center;
            }
            
            .display-3 {
                font-size: 2.5rem !important;
                line-height: 1.1;
            }
            
            .display-1 {
                font-size: 3rem !important;
            }
            
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
            
            .min-vh-75 {
                min-height: auto !important;
                padding: 2rem 0;
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .card-modern {
                margin-bottom: 1rem;
            }
            
            .d-flex.gap-4 {
                flex-direction: column;
                gap: 1rem !important;
            }
            
            .pe-lg-5 {
                padding-right: 0 !important;
            }
        }
        
        @media (max-width: 576px) {
            .display-3 {
                font-size: 2rem !important;
            }
            
            .fs-5 {
                font-size: 1.1rem !important;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .btn-lg {
                padding: 1rem 1.25rem;
                font-size: 1rem;
            }
            
            .section {
                padding: 1.5rem 0;
            }
            
            .py-5 {
                padding-top: 1.5rem !important;
                padding-bottom: 1.5rem !important;
            }
            
            .navbar-brand img {
                height: 24px;
            }
            
            .icon-feature {
                width: 3rem;
                height: 3rem;
                font-size: 1.25rem;
            }
            
            .text-center .col-lg-3 {
                margin-bottom: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes heroGlow {
            0% {
                opacity: 0.8;
                transform: scale(1);
            }
            100% {
                opacity: 1;
                transform: scale(1.05);
            }
        }

        @keyframes heroRotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.8s ease-out forwards;
        }

        .animate-slide-in-right {
            animation: slideInRight 0.8s ease-out forwards;
        }

        /* Intersection Observer animations */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-in-left {
            opacity: 0;
            transform: translateX(-30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .fade-in-right {
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .scale-in {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .scale-in.visible {
            opacity: 1;
            transform: scale(1);
        }

        /* Icon styles */
        .icon-feature {
            width: 4rem;
            height: 4rem;
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-glow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .icon-feature::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            .card-feature:hover .icon-feature::before {
            opacity: 1;
        }

        .card-feature:hover .icon-feature {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3);
        }

        /* Mega Menu Styles Override */
        .mega-dropdown .mega-menu {
            min-width: 800px;
            max-width: 900px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            opacity: 0;
            visibility: hidden;
            margin-top: 0;
            border-radius: var(--border-radius);
            border: 1px solid rgba(6, 182, 212, 0.2);
            box-shadow: 
                0 0 60px rgba(6, 182, 212, 0.15),
                0 0 100px rgba(139, 92, 246, 0.1),
                inset 0 1px 0 rgba(255,255,255,0.05);
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(24px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .mega-dropdown:hover .mega-menu,
        .mega-dropdown .mega-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* Staggered item animation */
        .mega-menu .col-lg-3 {
            opacity: 0;
            transform: translateY(15px);
            animation: megaFadeIn 0.5s ease forwards;
        }
        .mega-dropdown:hover .mega-menu .col-lg-3:nth-child(1) { animation-delay: 0.05s; }
        .mega-dropdown:hover .mega-menu .col-lg-3:nth-child(2) { animation-delay: 0.1s; }
        .mega-dropdown:hover .mega-menu .col-lg-3:nth-child(3) { animation-delay: 0.15s; }
        .mega-dropdown:hover .mega-menu .col-lg-3:nth-child(4) { animation-delay: 0.2s; }

        @keyframes megaFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mega-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .mega-menu .dropdown-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .mega-menu .dropdown-item:hover {
            background: rgba(6, 182, 212, 0.08);
            border-color: rgba(6, 182, 212, 0.15);
            transform: translateX(8px);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.1);
        }

        .mega-menu .dropdown-item:hover::before {
            transform: scaleY(1);
        }

        .mega-menu .dropdown-item:hover i {
            transform: scale(1.2);
            filter: drop-shadow(0 0 8px currentColor);
        }

        .mega-menu .dropdown-item i {
            transition: all 0.3s ease;
        }

        .mega-menu .dropdown-item .fw-semibold {
            color: #f8fafc;
        }
        
        .mega-menu .dropdown-item small {
            color: #94a3b8 !important;
        }

        .mega-menu h6 {
            color: var(--primary);
            text-shadow: 0 0 15px rgba(6, 182, 212, 0.5);
            letter-spacing: 0.1em;
            position: relative;
            animation: titlePulse 2s ease-in-out infinite;
        }

        @keyframes titlePulse {
            0%, 100% { text-shadow: 0 0 15px rgba(6, 182, 212, 0.5); }
            50% { text-shadow: 0 0 25px rgba(6, 182, 212, 0.8), 0 0 40px rgba(6, 182, 212, 0.4); }
        }

        .mega-menu h6::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
        }

        /* Scanning light effect */
        .mega-dropdown .mega-menu::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), rgba(139, 92, 246, 0.8), transparent);
            animation: scanLine 3s linear infinite;
            z-index: 10;
        }

        @keyframes scanLine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Grid background pattern */
        .mega-dropdown .mega-menu::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(6, 182, 212, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .mega-menu > .row {
            position: relative;
            z-index: 1;
        }

        /* Pulsing icons */
        .mega-menu .dropdown-item i {
            transition: all 0.3s ease;
            animation: iconGlow 2.5s ease-in-out infinite;
        }

        @keyframes iconGlow {
            0%, 100% { filter: drop-shadow(0 0 2px currentColor); }
            50% { filter: drop-shadow(0 0 8px currentColor); }
        }

        /* Corner accents */
        .mega-dropdown .mega-menu {
            position: relative;
        }

        .mega-menu .corner-accent {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid var(--primary);
            opacity: 0.5;
        }

        .mega-menu .corner-accent.top-left {
            top: -1px;
            left: -1px;
            border-right: none;
            border-bottom: none;
        }

        .mega-menu .corner-accent.top-right {
            top: -1px;
            right: -1px;
            border-left: none;
            border-bottom: none;
        }

        .mega-menu .corner-accent.bottom-left {
            bottom: -1px;
            left: -1px;
            border-right: none;
            border-top: none;
        }

        .mega-menu .corner-accent.bottom-right {
            bottom: -1px;
            right: -1px;
            border-left: none;
            border-top: none;
        }

        /* Promo Banner Styles - Christmas Edition */
        .promo-banner {
            background: linear-gradient(90deg, #0f172a 0%, #4c1d3d 25%, #1e1b4b 50%, #4c1d3d 75%, #0f172a 100%);
            background-size: 200% 100%;
            animation: bannerGradient 8s ease infinite;
            border-bottom: 1px solid rgba(239, 68, 68, 0.4);
            color: white;
            padding: 0.6rem 0;
            text-align: center;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 1050;
            overflow: hidden;
        }

        .promo-banner::before {
            content: '✦';
            position: absolute;
            left: 10%;
            animation: twinkle 2s ease-in-out infinite;
            color: #fbbf24;
            font-size: 0.7rem;
        }

        .promo-banner::after {
            content: '✦';
            position: absolute;
            right: 10%;
            animation: twinkle 2s ease-in-out infinite 0.5s;
            color: #fbbf24;
            font-size: 0.7rem;
        }

        @keyframes bannerGradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        .promo-banner .promo-text {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .promo-banner .promo-highlight {
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #fbbf24);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 3s linear infinite;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }

        .promo-banner .gift-icon {
            animation: bounce 1s ease infinite;
            display: inline-block;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .promo-banner .btn-promo {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            margin-left: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            animation: btnPulse 2s ease-in-out infinite;
        }

        @keyframes btnPulse {
            0%, 100% { box-shadow: 0 0 15px rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 25px rgba(239, 68, 68, 0.8), 0 0 40px rgba(239, 68, 68, 0.4); }
        }

        .promo-banner .btn-promo:hover {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.8);
            transform: translateY(-2px) scale(1.05);
        }

        @media (max-width: 768px) {
            .promo-banner { font-size: 0.8rem; padding: 0.5rem 0; }
            .promo-banner::before, .promo-banner::after { display: none; }
            .promo-banner .btn-promo { margin-left: 0.5rem; padding: 0.25rem 0.75rem; }
        }

        /* Fix Visibility overrides */
        .navbar-modern .nav-link {
            color: #ffffff !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 3px rgba(0,0,0,0.8);
        }
        .navbar-modern .btn-primary {
            background: linear-gradient(135deg, #06b6d4 0%, #2563eb 100%) !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border: 1px solid rgba(255,255,255,0.4);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
        }
    </style>
</head>
<body>

<!-- Promo Banner -->
<div class="promo-banner">
    <div class="container">
        <span class="promo-text">
            <span class="gift-icon">🎁</span>
            <span class="text-white-50">Offre Spéciale Fêtes</span>
            <span class="promo-highlight">1 MOIS OFFERT</span>
            <span class="text-white-50">sur votre abonnement</span>
        </span>
        <a href="/inscription" class="btn-promo">
            <i class="fa-solid fa-arrow-right me-1"></i>J'en profite
        </a>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg sticky-top navbar-modern">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="/assets/images/logo/logoservo.png" alt="SERVO" height="40" class="me-2">
            <span class="fs-4 fw-black text-white tracking-tight">SERVO<span class="text-primary">.TOOLS</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <!-- Mega Menu Fonctionnalités -->
                <li class="nav-item dropdown mega-dropdown position-static">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo t('nav_features'); ?>
                    </a>
                    <div class="dropdown-menu mega-menu p-4">
                        <div class="row g-4">
                            <div class="col-lg-3">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fa-solid fa-comments me-2"></i>
                                    Communication
                                </h6>
                                <a class="dropdown-item mb-2" href="/sms-automatiques">
                                    <i class="fa-solid fa-message text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">SMS Automatiques</div>
                                        <small class="text-muted">Notifications clients auto</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/campagnes-sms-marketing">
                                    <i class="fa-solid fa-bullhorn text-warning me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Campagnes SMS</div>
                                        <small class="text-muted">Marketing automation</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/telephonie-voip">
                                    <i class="fa-solid fa-phone text-danger me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Téléphonie VOIP</div>
                                        <small class="text-muted">Appels intégrés</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/messagerie-interne">
                                    <i class="fa-solid fa-comments text-info me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Messagerie Interne</div>
                                        <small class="text-muted">Chat équipe sécurisé</small>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-lg-3">
                                <h6 class="fw-bold text-success mb-3">
                                    <i class="fa-solid fa-users me-2"></i>
                                    Gestion Équipe
                                </h6>
                                <a class="dropdown-item mb-2" href="/pointage-employes">
                                    <i class="fa-solid fa-qrcode text-success me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Pointage</div>
                                        <small class="text-muted">QR Code & WiFi</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/gestion-taches">
                                    <i class="fa-solid fa-list-check text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Gestion Tâches</div>
                                        <small class="text-muted">Suivi productivité</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/missions-gamification">
                                    <i class="fa-solid fa-trophy text-info me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Missions Gamifiées</div>
                                        <small class="text-muted">Motivez vos équipes</small>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-lg-3">
                                <h6 class="fw-bold text-warning mb-3">
                                    <i class="fa-solid fa-boxes me-2"></i>
                                    Stock & Fournisseurs
                                </h6>
                                <a class="dropdown-item mb-2" href="/catalogue-fournisseurs">
                                    <i class="fa-solid fa-boxes-stacked text-warning me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Catalogue Multi</div>
                                        <small class="text-muted">10+ fournisseurs</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/gestion-fournisseurs">
                                    <i class="fa-solid fa-handshake text-success me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Gestion Fournisseurs</div>
                                        <small class="text-muted">Comptes & Soldes</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/commandes-pieces">
                                    <i class="fa-solid fa-truck text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Commandes Pièces</div>
                                        <small class="text-muted">Workflow complet</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/rachat-conformite">
                                    <i class="fa-solid fa-shield-check text-secondary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Rachat Conformité</div>
                                        <small class="text-muted">Protection légale</small>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-lg-3">
                                <h6 class="fw-bold text-info mb-3">
                                    <i class="fa-solid fa-brain me-2"></i>
                                    Intelligence & Data
                                </h6>
                                <a class="dropdown-item mb-2" href="/base-connaissances-ia">
                                    <i class="fa-solid fa-brain text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Base Connaissances IA</div>
                                        <small class="text-muted">Recherche Groq</small>
                                    </div>
                                </a>
                                <a class="dropdown-item mb-2" href="/analytics-kpi">
                                    <i class="fa-solid fa-chart-line text-warning me-2"></i>
                                    <div>
                                        <div class="fw-semibold">Analytics & KPI</div>
                                        <small class="text-muted">Dashboard temps réel</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3 pt-3 border-top">
                            <div class="col-12">
                                <a href="/features" class="btn btn-sm btn-primary w-100">
                                    <i class="fa-solid fa-grid me-2"></i>
                                    Toutes les fonctionnalités
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/enterprise">Grandes Entreprises</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pricing"><?php echo t('nav_pricing'); ?></a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Ressources
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header text-primary small fw-bold text-uppercase tracking-wider">Produit</h6></li>
                        <li><a class="dropdown-item" href="/customers"><i class="fa-solid fa-users text-success me-2"></i>Nos Clients</a></li>
                        <li><a class="dropdown-item" href="/roadmap"><i class="fa-solid fa-map text-warning me-2"></i>Roadmap</a></li>
                        <li><a class="dropdown-item" href="/changelog"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Changelog</a></li>
                        <li><hr class="dropdown-divider border-light opacity-10"></li>
                        <li><h6 class="dropdown-header text-primary small fw-bold text-uppercase tracking-wider">Aide</h6></li>
                        <li><a class="dropdown-item" href="/help"><i class="fa-solid fa-circle-question text-muted me-2"></i>Centre d'aide</a></li>
                        <li><a class="dropdown-item" href="/api"><i class="fa-solid fa-code text-muted me-2"></i>API Docs</a></li>
                        <li><a class="dropdown-item" href="/status"><i class="fa-solid fa-signal text-success me-2"></i>Status</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Société
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/about">À Propos</a></li>
                        <li><a class="dropdown-item" href="/blog">Blog</a></li>
                        <li><a class="dropdown-item" href="/security">Sécurité & Trust</a></li>
                        <li><a class="dropdown-item" href="/careers">Carrières <span class="badge bg-success bg-opacity-10 text-success ms-1">Hiring</span></a></li>
                        <li><hr class="dropdown-divider border-light opacity-10"></li>
                        <li><a class="dropdown-item" href="/contact">Contact</a></li>
                    </ul>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-primary" href="/inscription">
                        <i class="fa-solid fa-rocket me-2"></i><?php echo t('btn_try_free'); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main id="main-content" role="main">

<script>
// Animation au scroll avec Intersection Observer
document.addEventListener('DOMContentLoaded', function() {
    // Effet de scroll sur la navbar
    const navbar = document.querySelector('.navbar-modern');
    let lastScrollY = window.scrollY;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Intersection Observer pour les animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec des classes d'animation
    const animatedElements = document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .scale-in');
    animatedElements.forEach(el => observer.observe(el));

    // Effet de parallaxe léger sur le hero (désactivé pour éviter la superposition)
    // const hero = document.querySelector('.bg-gradient-hero');
    // if (hero) {
    //     window.addEventListener('scroll', () => {
    //         const scrolled = window.pageYOffset;
    //         const parallax = scrolled * 0.5;
    //         hero.style.transform = `translateY(${parallax}px)`;
    //     });
    // }

    // Animation des cartes features au hover
    const featureCards = document.querySelectorAll('.card-feature');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Animation des boutons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
            ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

// Ajouter les keyframes CSS pour l'effet ripple
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
