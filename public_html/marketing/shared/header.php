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
    <title><?php echo t('meta_title', 'MDGEEK – L\'intelligence de la réparation'); ?></title>
    <meta name="description" content="<?php echo t('meta_description_default'); ?>">
    <link rel="icon" type="image/png" href="/assets/images/logo/logoservo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Mode clair (par défaut) */
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #7dd3fc;
            --secondary: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --accent: #0891b2;
            --accent-light: #67e8f9;
            --cyan: #06b6d4;
            --cyan-light: #a5f3fc;
            --teal: #14b8a6;
            --teal-light: #5eead4;
            
            /* Couleurs adaptatives pour le système de thème */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-color-light: rgba(226, 232, 240, 0.5);
            
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            --gradient-primary: linear-gradient(135deg, #0ea5e9 0%, #0891b2 50%, #14b8a6 100%);
            --gradient-hero: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0891b2 100%);
            --gradient-accent: linear-gradient(135deg, #14b8a6 0%, #0ea5e9 100%);
            --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
            --gradient-card-dark: linear-gradient(145deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0.01) 100%);
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-glow: 0 0 20px rgba(14, 165, 233, 0.15);
            --border-radius: 16px;
            --border-radius-lg: 24px;
            --border-radius-xl: 32px;
        }

        /* Mode sombre automatique basé sur les préférences système */
        @media (prefers-color-scheme: dark) {
            :root {
                /* Couleurs de base inversées pour le mode sombre */
                --bg-primary: #0f172a;
                --bg-secondary: #1e293b;
                --bg-tertiary: #334155;
                --text-primary: #f8fafc;
                --text-secondary: #cbd5e1;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --border-color-light: rgba(51, 65, 85, 0.5);
                
                /* Ajustement des grays pour le mode sombre */
                --gray-50: #1e293b;
                --gray-100: #334155;
                --gray-200: #475569;
                --gray-300: #64748b;
                --gray-400: #94a3b8;
                --gray-500: #cbd5e1;
                --gray-600: #e2e8f0;
                --gray-700: #f1f5f9;
                --gray-800: #f8fafc;
                --gray-900: #ffffff;
                
                /* Gradients adaptés pour le mode sombre */
                --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0.01) 100%);
                --gradient-card-dark: linear-gradient(145deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
                
                /* Ombres plus subtiles en mode sombre */
                --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.2);
                --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.3), 0 1px 2px -1px rgb(0 0 0 / 0.3);
                --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.4), 0 4px 6px -4px rgb(0 0 0 / 0.4);
                --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.5), 0 8px 10px -6px rgb(0 0 0 / 0.5);
                --shadow-glow: 0 0 20px rgba(14, 165, 233, 0.25);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            color: var(--text-primary);
            line-height: 1.6;
            font-weight: 400;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
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

        .servo-text {
            font-weight: 900;
            font-size: 2.75rem;
            line-height: 44px;
            color: var(--primary);
            letter-spacing: -0.02em;
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
            
            .servo-text {
                font-size: 2rem;
                line-height: 32px;
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
                height: 28px;
            }
            
            .servo-text {
                font-size: 1.75rem;
                line-height: 28px;
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
            transition: opacity 0.3s ease;
        }

        .card-feature:hover .icon-feature::before {
            opacity: 1;
        }

        .card-feature:hover .icon-feature {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3);
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg sticky-top navbar-modern">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/assets/images/logo/logoservo.png" alt="MDGEEK" height="44">
            <span class="servo-text">SERVO</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="/features"><?php echo t('nav_features'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pricing"><?php echo t('nav_pricing'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/testimonials"><?php echo t('nav_testimonials'); ?></a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <?php echo t('nav_resources', 'Ressources'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/roi"><?php echo t('nav_roi'); ?></a></li>
                        <li><a class="dropdown-item" href="/integrations"><?php echo t('nav_integrations'); ?></a></li>
                        <li><a class="dropdown-item" href="/multistore"><?php echo t('nav_multistore'); ?></a></li>
                        <li><a class="dropdown-item" href="/security"><?php echo t('nav_security'); ?></a></li>
                        <li><a class="dropdown-item" href="/customer-portal"><?php echo t('nav_customer_portal'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/vs-repairdesk"><?php echo t('nav_vs_repairdesk'); ?></a></li>
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
