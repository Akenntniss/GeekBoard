<?php
// pages/appels.php
// Interface pour la fonctionnalité VOIP - Version Standalone (No Bootstrap dependency)

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php?page=login';</script>";
    exit;
}
?>

<style>
    /* ============================================
       ENHANCED DESKTOP VOIP INTERFACE
       Premium Design with Glassmorphism
       ============================================ */
    
    /* Reset & Base Styles */
    .voip-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #e0e0e0;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
        padding: 100px 30px 30px 30px; 
        min-height: 100vh;
        display: grid;
        grid-template-columns: 360px 1fr;
        grid-template-rows: 1fr;
        gap: 20px;
        box-sizing: border-box;
        margin-top: 0;
        position: relative;
        z-index: 1;
    }
    
    /* Left column wrapper for sidebar + history */
    .voip-left-column {
        display: flex;
        flex-direction: column;
        gap: 15px;
        height: calc(100vh - 130px);
    }
    
    .voip-sidebar { flex: 0 0 auto; max-height: 50%; overflow: hidden; }
    .call-history-section { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    .voip-main { height: calc(100vh - 130px); }

    .voip-container * {
        box-sizing: border-box;
    }

    /* Sidebar - Users List with Glassmorphism */
    .voip-sidebar {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 
            0 8px 32px rgba(0, 0, 0, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .voip-sidebar-header {
        padding: 24px;
        background: linear-gradient(180deg, rgba(99, 102, 241, 0.15) 0%, transparent 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .voip-sidebar-header h2 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.02em;
    }
    
    .voip-sidebar-header h2 i {
        color: #818cf8;
        font-size: 1.1rem;
    }

    .voip-users-list {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .voip-users-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .voip-users-list::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.4);
        border-radius: 3px;
    }

    .voip-user-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: rgba(51, 65, 85, 0.5);
        border-radius: 14px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.05);
        cursor: pointer;
    }

    .voip-user-card:hover {
        background: rgba(99, 102, 241, 0.2);
        border-color: rgba(99, 102, 241, 0.4);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }

    .voip-user-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .user-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        position: relative;
        flex-shrink: 0;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .status-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        border: 2px solid #1e293b;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .status-online { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
        box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
    }
    .status-offline { background-color: #64748b; }
    .status-busy { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
    }

    .user-name {
        font-weight: 600;
        color: #f1f5f9;
        font-size: 0.95rem;
        letter-spacing: -0.01em;
    }

    .user-status-text {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 2px;
    }
    
    .user-status-text.online {
        color: #4ade80;
    }
    
    /* User actions wrapper */
    .user-actions-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Message button */
    .btn-message-user {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        flex-shrink: 0;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .btn-message-user:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5);
        color: white;
    }
    
    .btn-call-user {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        flex-shrink: 0;
    }

    .btn-call-user:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
    }

    .btn-call-user:disabled {
        background: #475569;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    /* Call Type Tooltip - Enhanced */
    .call-type-wrapper {
        position: relative;
    }

    .call-type-tooltip {
        position: absolute;
        right: 55px;
        top: 50%;
        transform: translateY(-50%) scale(0.9);
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 14px;
        padding: 10px;
        display: flex;
        gap: 8px;
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 100;
    }

    .call-type-tooltip.visible {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(-50%) scale(1);
    }

    .call-type-tooltip::after {
        content: '';
        position: absolute;
        right: -8px;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-left-color: rgba(15, 23, 42, 0.95);
    }

    .btn-call-type {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        border: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        gap: 3px;
    }

    .btn-call-type i {
        font-size: 1.1rem;
    }

    .btn-call-type span {
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .btn-audio-call {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-audio-call:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .btn-video-call {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-video-call:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    /* Main Area - Call Interface with Premium Design */
    .voip-main {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 
            0 20px 60px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .video-container {
        width: 100%;
        height: 100%;
        position: relative;
        background: #0f172a;
    }

    video#remote-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    video#local-video {
        position: absolute;
        bottom: 30px;
        right: 30px;
        width: 260px;
        height: 195px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        border: 3px solid rgba(255, 255, 255, 0.15);
        object-fit: cover;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.5),
            0 0 0 1px rgba(255, 255, 255, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 10;
    }

    video#local-video:hover {
        transform: scale(1.05);
        border-color: rgba(99, 102, 241, 0.5);
        box-shadow: 
            0 25px 50px rgba(0, 0, 0, 0.6),
            0 0 30px rgba(99, 102, 241, 0.2);
    }

    /* Controls Bar - Premium Glassmorphism */
    .call-controls {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 16px;
        padding: 16px 32px;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 60px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 20;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 
            0 10px 40px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .call-controls.hidden {
        transform: translate(-50%, 100px);
        opacity: 0;
        pointer-events: none;
    }

    .control-btn {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-mic, .btn-cam {
        background: rgba(51, 65, 85, 0.8);
        color: #e2e8f0;
    }

    .btn-mic:hover, .btn-cam:hover {
        background: rgba(71, 85, 105, 0.9);
        transform: scale(1.1);
    }

    .btn-mic.active, .btn-cam.active {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.4);
    }

    .btn-hangup {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        width: 68px;
        height: 68px;
        margin: -6px 8px;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    .btn-hangup:hover {
        transform: scale(1.12);
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.5);
    }

    /* Status Overlay - Enhanced */
    .call-info-overlay {
        position: absolute;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 28px;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 40px;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 20;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: none;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        font-size: 0.95rem;
        letter-spacing: -0.01em;
    }

    .call-info-overlay.visible {
        display: flex;
    }

    .status-dot {
        width: 10px;
        height: 10px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
        70% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    /* Empty State - Enhanced */
    .empty-state {
        text-align: center;
        color: #94a3b8;
        padding: 40px;
    }

    .empty-state-icon {
        font-size: 5rem;
        margin-bottom: 24px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        opacity: 0.8;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #f1f5f9;
        margin: 0 0 10px 0;
        letter-spacing: -0.02em;
    }
    
    .empty-state p {
        font-size: 1rem;
        color: #64748b;
        margin: 0;
    }

    /* Custom Modal for Incoming Call - Premium */
    .custom-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .custom-modal-backdrop.active {
        opacity: 1;
        pointer-events: auto;
    }

    .incoming-call-card {
        background: #1e293b;
        padding: 40px;
        border-radius: 24px;
        text-align: center;
        width: 90%;
        max-width: 400px;
        border: 1px solid #475569;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        transform: translateY(20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .custom-modal-backdrop.active .incoming-call-card {
        transform: translateY(0);
    }

    .caller-image-large {
        width: 120px;
        height: 120px;
        background: #334155;
        border-radius: 50%;
        margin: 0 auto 20px;
        border: 4px solid #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        animation: ring 1.5s infinite;
    }

    @keyframes ring {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(59, 130, 246, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    .incoming-actions {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 30px;
    }

    .btn-action-large {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .btn-action-large:hover {
        transform: scale(1.1);
    }

    .btn-accept { background: #22c55e; box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.5); }
    .btn-reject { background: #ef4444; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.5); }

    /* Responsive */
    @media (max-width: 850px) {
        .voip-container {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            padding: 80px 10px 10px 10px;
            height: auto;
            min-height: calc(100vh - 80px);
        }
        
        .voip-left-column {
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: auto;
        }

        .voip-sidebar {
            height: auto;
            max-height: 180px;
            flex-shrink: 0;
            border-radius: 12px;
        }
        
        .call-history-section {
            flex: none;
            max-height: 200px;
        }
        
        .voip-main {
            height: auto;
            min-height: 300px;
        }
        
        .voip-sidebar-header {
            padding: 10px 15px;
        }
        
        .voip-sidebar-header h2 {
            font-size: 1rem;
        }

        /* Horizontal scrollable users list */
        .voip-users-list {
            flex-direction: row;
            overflow-x: auto;
            overflow-y: hidden;
            gap: 10px;
            padding: 10px;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x mandatory;
        }
        
        .voip-users-list::-webkit-scrollbar {
            height: 4px;
        }
        
        .voip-users-list::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .voip-user-card {
            flex-direction: column;
            min-width: 100px;
            max-width: 100px;
            padding: 12px 8px;
            text-align: center;
            flex-shrink: 0;
            scroll-snap-align: start;
            border-radius: 12px;
        }
        
        .voip-user-info {
            flex-direction: column;
            gap: 6px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            font-size: 1rem;
            margin: 0 auto;
        }
        
        .user-name {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90px;
        }
        
        .user-status-text {
            font-size: 0.65rem;
        }
        
        .call-type-wrapper {
            margin-top: 8px;
        }
        
        .btn-call-user {
            width: 32px;
            height: 32px;
            font-size: 0.85rem;
        }
        
        /* Call type tooltip - show above on mobile */
        .call-type-tooltip {
            right: auto;
            left: 50%;
            transform: translateX(-50%) scale(0.9);
            top: auto;
            bottom: 45px;
        }
        
        .call-type-tooltip.visible {
            transform: translateX(-50%) scale(1);
        }
        
        .call-type-tooltip::after {
            right: auto;
            left: 50%;
            top: auto;
            bottom: -8px;
            transform: translateX(-50%);
            border: 8px solid transparent;
            border-top-color: #1e293b;
            border-left-color: transparent;
        }

        video#local-video {
            width: 100px;
            height: 75px;
            bottom: 100px;
            right: 10px;
        }
        
        .call-controls {
            width: 90%;
            justify-content: space-around;
            padding: 12px 20px;
        }
        
        .control-btn {
            width: 44px;
            height: 44px;
        }
        
        .btn-hangup {
            width: 54px;
            height: 54px;
        }

        .voip-main {
            height: auto;
            flex: 1;
            min-height: 300px;
        }
        
        .empty-state-icon {
            font-size: 2.5rem;
        }
        
        .empty-state h3 {
            font-size: 1.1rem;
        }
        
        .empty-state p {
            font-size: 0.85rem;
        }
    }
    
    /* Extra small screens */
    @media (max-width: 400px) {
        .voip-user-card {
            min-width: 85px;
            max-width: 85px;
            padding: 10px 6px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
        }
        
        .user-name {
            font-size: 0.7rem;
            max-width: 75px;
        }
    }
    
    /* ============================================
       CALL HISTORY STYLES - PREMIUM
       ============================================ */
    .call-history-section {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        overflow: hidden;
        box-shadow: 
            0 8px 32px rgba(0, 0, 0, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }
    
    .call-history-header {
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.1) 0%, transparent 100%);
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .call-history-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.02em;
    }
    
    .call-history-header h3 i {
        color: #34d399;
        font-size: 0.95rem;
    }
    
    .call-history-list {
        flex: 1;
        overflow-y: auto;
    }
    
    .call-history-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .call-history-list::-webkit-scrollbar-thumb {
        background: rgba(16, 185, 129, 0.4);
        border-radius: 3px;
    }
    
    .call-history-item {
        display: flex;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .call-history-item:hover {
        background: rgba(99, 102, 241, 0.15);
    }
    
    .call-history-item:last-child {
        border-bottom: none;
    }
    
    .call-direction-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 14px;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .call-direction-icon.outgoing {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.3) 0%, rgba(99, 102, 241, 0.3) 100%);
        color: #60a5fa;
    }
    
    .call-direction-icon.incoming {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.3) 0%, rgba(16, 185, 129, 0.3) 100%);
        color: #4ade80;
    }
    
    .call-direction-icon.missed {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.3) 0%, rgba(220, 38, 38, 0.3) 100%);
        color: #f87171;
    }
    
    .call-history-info {
        flex: 1;
        min-width: 0;
    }
    
    .call-history-name {
        font-weight: 600;
        color: #f1f5f9;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: -0.01em;
    }
    
    .call-history-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 3px;
    }
    
    .call-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.68rem;
        font-weight: 600;
        background: rgba(99, 102, 241, 0.2);
        color: #a5b4fc;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    
    .call-direction-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.68rem;
        font-weight: 600;
    }
    
    .call-direction-label.outgoing {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
    }
    
    .call-direction-label.incoming {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
    }
    
    .call-direction-label.missed {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }
    
    .call-history-time {
        text-align: right;
        font-size: 0.78rem;
        color: #64748b;
    }
    
    .call-history-duration {
        color: #94a3b8;
        font-weight: 500;
    }
    
    .call-history-empty {
        padding: 30px;
        text-align: center;
        color: #64748b;
    }
    
    .call-history-empty i {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.5;
    }
    
    .btn-refresh-history {
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .btn-refresh-history:hover {
        background: rgba(255,255,255,0.1);
        color: #f8fafc;
    }
    
    /* Mobile adjustments for history */
    @media (max-width: 850px) {
        .call-history-section {
            margin-top: 10px;
        }
        
        .call-history-list {
            max-height: 200px;
        }
        
        .call-history-item {
            padding: 10px 12px;
        }
        
        .call-direction-icon {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
            margin-right: 10px;
        }
    }
</style>

<div class="voip-container">
    <!-- Left Column: Sidebar + History -->
    <div class="voip-left-column">
        <!-- Sidebar -->
        <aside class="voip-sidebar">
            <div class="voip-sidebar-header">
                <h2>
                    <i class="fas fa-users-cog"></i>
                    Collègues
                </h2>
            </div>
            <div class="voip-users-list" id="users-list">
                <!-- Loading State -->
                <div style="text-align: center; padding: 20px; color: #64748b;">
                    <i class="fas fa-circle-notch fa-spin"></i> Chargement...
                </div>
            </div>
        </aside>

        <!-- Call History Section -->
        <section class="call-history-section" id="call-history-section">
            <div class="call-history-header">
                <h3><i class="fas fa-history"></i> Historique des appels</h3>
                <button class="btn-refresh-history" id="btn-refresh-history" title="Actualiser">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="call-history-list" id="call-history-list">
                <div style="text-align: center; padding: 20px; color: #64748b;">
                    <i class="fas fa-circle-notch fa-spin"></i> Chargement...
                </div>
            </div>
        </section>
    </div>

    <!-- Main Area -->
    <main class="voip-main">
        <div class="video-container">
            <video id="remote-video" autoplay playsinline></video>
            <video id="local-video" autoplay playsinline muted></video>
            
            <!-- Empty State Placeholder -->
            <div id="no-call-placeholder" class="empty-state" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <div class="empty-state-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Prêt pour les appels</h3>
                <p>Sélectionnez un collègue pour démarrer</p>
            </div>
        </div>

        <!-- Status Overlay -->
        <div id="call-status" class="call-info-overlay">
            <div class="status-dot"></div>
            <span id="call-status-text">Connexion...</span>
        </div>

        <!-- Controls -->
        <div id="call-controls" class="call-controls hidden">
            <button id="btn-mute-audio" class="control-btn btn-mic" title="Audio">
                <i class="fas fa-microphone"></i>
            </button>
            <button id="btn-hangup" class="control-btn btn-hangup" title="Raccrocher">
                <i class="fas fa-phone-slash"></i>
            </button>
            <button id="btn-mute-video" class="control-btn btn-cam" title="Vidéo">
                <i class="fas fa-video"></i>
            </button>
        </div>
    </main>
</div>

<!-- Incoming Call Modal -->
<div id="incoming-modal" class="custom-modal-backdrop">
    <div class="incoming-call-card">
        <div class="caller-image-large">
            <i class="fas fa-user"></i>
        </div>
        <h3 style="color: white; margin: 0 0 5px 0;" id="incoming-caller-name">Inconnu</h3>
        <p style="color: #94a3b8; margin: 0;">Appel entrant...</p>
        
        <div class="incoming-actions">
            <button id="btn-reject-incoming" class="btn-action-large btn-reject">
                <i class="fas fa-phone-slash"></i>
            </button>
            <button id="btn-accept-incoming" class="btn-action-large btn-accept">
                <i class="fas fa-phone"></i>
            </button>
        </div>
    </div>
</div>

<audio id="ringtone-audio" loop src="assets/sounds/ringtone.mp3"></audio>

<script>
(function() {
    // Configuration
    const API_URL = 'api/voip/handler.php';
    const POLLING_INTERVAL = 3000;
    
    // DOM Elements
    const elUsersList = document.getElementById('users-list');
    const elLocalVideo = document.getElementById('local-video');
    const elRemoteVideo = document.getElementById('remote-video');
    const elControls = document.getElementById('call-controls');
    const elPlaceholder = document.getElementById('no-call-placeholder');
    const elStatusOverlay = document.getElementById('call-status');
    const elStatusText = document.getElementById('call-status-text');
    const elIncomingModal = document.getElementById('incoming-modal');
    
    // State
    let state = {
        isCalling: false,
        callId: null,
        localStream: null,
        peerConnection: null,
        pollInterval: null,
        incomingPollInterval: null,
        processedCandidates: new Set()
    };
    
    const ICE_CONFIG = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    // --- Helpers ---
    function log(msg, data) {
        console.log('[VOIP] ' + msg, data || '');
    }

    function showStatus(text, isError) {
        if (!elStatusOverlay || !elStatusText) return;
        elStatusOverlay.classList.add('visible');
        elStatusText.textContent = text;
        elStatusText.style.color = isError ? '#ef4444' : 'white';
    }

    function hideStatus() {
        if (elStatusOverlay) elStatusOverlay.classList.remove('visible');
    }

    // --- API & Users ---
    async function fetchUsers() {
        console.log('[VOIP] Fetching users...');
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'get_online_users' })
            });
            console.log('[VOIP] Response status:', res.status);
            const data = await res.json();
            console.log('[VOIP] API Response:', data);
            
            if (data.status === 'success') {
                renderUsers(data.users);
            } else {
                console.error('[VOIP] API Error:', data.message);
                if (elUsersList) elUsersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;">Erreur: ' + (data.message || 'Inconnue') + '</div>';
            }
        } catch (e) {
            console.error('[VOIP] Fetch users error:', e);
            if (elUsersList) elUsersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;">Erreur de connexion</div>';
        }
    }

    function renderUsers(users) {
        if (!elUsersList) return;
        
        if (!users || users.length === 0) {
            elUsersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #64748b;">Aucun utilisateur trouvé</div>';
            return;
        }

        // Build HTML with call type tooltip
        const html = users.map(function(user) {
            const isOnline = user.is_online == 1;
            const statusClass = isOnline ? 'status-online' : 'status-offline';
            const initials = user.full_name ? user.full_name.substring(0, 2).toUpperCase() : '??';
            const cleanName = user.full_name ? user.full_name.replace(/"/g, '&quot;') : 'Utilisateur';
            
            return '<div class="voip-user-card">' +
                   '    <div class="voip-user-info">' +
                   '        <div class="user-avatar">' + initials + '<div class="status-indicator ' + statusClass + '"></div></div>' +
                   '        <div>' +
                   '            <div class="user-name">' + cleanName + '</div>' +
                   '            <div class="user-status-text">' + user.status_label + '</div>' +
                   '        </div>' +
                   '    </div>' +
                   '    <div class="user-actions-wrapper">' +
                   '        <a href="index.php?page=messagerie&new_conv=' + user.id + '" class="btn-message-user" title="Envoyer un message">' +
                   '            <i class="fas fa-comment"></i>' +
                   '        </a>' +
                   '        <div class="call-type-wrapper">' +
                   '            <div class="call-type-tooltip" data-user-id="' + user.id + '" data-user-name="' + cleanName + '">' +
                   '                <button class="btn-call-type btn-audio-call" data-type="audio" title="Appel audio">' +
                   '                    <i class="fas fa-phone"></i>' +
                   '                    <span>Audio</span>' +
                   '                </button>' +
                   '                <button class="btn-call-type btn-video-call" data-type="video" title="Appel vidéo">' +
                   '                    <i class="fas fa-video"></i>' +
                   '                    <span>Vidéo</span>' +
                   '                </button>' +
                   '            </div>' +
                   '            <button class="btn-call-user" data-id="' + user.id + '" data-name="' + cleanName + '" ' + (state.isCalling ? 'disabled' : '') + '>' +
                   '                <i class="fas fa-phone"></i>' +
                   '            </button>' +
                   '        </div>' +
                   '    </div>' +
                   '</div>';
        }).join('');
        
        elUsersList.innerHTML = html;
    }

    // --- WebRTC ---
    async function setupWebRTC(videoEnabled = true) {
        try {
            state.peerConnection = new RTCPeerConnection(ICE_CONFIG);
            state.videoEnabled = videoEnabled;
            
            state.peerConnection.onicecandidate = function(event) {
                if (event.candidate && state.callId) {
                    fetch(API_URL, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'send_ice_candidate',
                            call_id: state.callId,
                            candidate: event.candidate
                        })
                    });
                }
            };
            
            state.peerConnection.ontrack = function(event) {
                if (elRemoteVideo && elRemoteVideo.srcObject !== event.streams[0]) {
                    elRemoteVideo.srcObject = event.streams[0];
                }
            };
            
            // Audio always enabled, video based on parameter
            state.localStream = await navigator.mediaDevices.getUserMedia({ 
                audio: true, 
                video: videoEnabled 
            });
            if (elLocalVideo) {
                elLocalVideo.srcObject = state.localStream;
                elLocalVideo.style.display = videoEnabled ? 'block' : 'none';
            }
            
            state.localStream.getTracks().forEach(track => {
                state.peerConnection.addTrack(track, state.localStream);
            });
            
            return true;
        } catch (e) {
            console.error('WebRTC Setup Error', e);
            alert("Erreur: Impossible d'accéder à la caméra/micro. Vérifiez les permissions.");
            return false;
        }
    }

    async function startVoipCall(receiverId, name, videoEnabled = true) {
        if (state.isCalling) return;
        
        const callType = videoEnabled ? 'Appel vidéo' : 'Appel audio';
        enableCallUI(true, callType + ' vers ' + name + '...');
        
        if (!await setupWebRTC(videoEnabled)) {
            endCallUI();
            return;
        }
        
        const offer = await state.peerConnection.createOffer();
        await state.peerConnection.setLocalDescription(offer);
        
        const res = await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify({
                action: 'initiate_call',
                receiver_id: receiverId,
                offer: JSON.stringify(offer),
                call_type: videoEnabled ? 'video' : 'audio'
            })
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            state.callId = data.call_id;
            state.pollInterval = setInterval(pollCallStatus, 1500);
        } else {
            alert('Erreur serveur');
            endCall();
        }
    }

    // --- Incoming ---
    async function checkIncoming() {
        if (state.isCalling) return;
        try {
            const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'check_incoming' }) });
            const data = await res.json();
            if (data.status === 'incoming') {
                showIncomingModal(data.call, data.caller_name);
            }
        } catch(e) {}
    }

    function showIncomingModal(callData, name) {
        const elName = document.getElementById('incoming-caller-name');
        if (elName) elName.textContent = name;
        if (elIncomingModal) elIncomingModal.classList.add('active');
        
        const btnAccept = document.getElementById('btn-accept-incoming');
        const btnReject = document.getElementById('btn-reject-incoming');
        
        // Clone to remove previous listeners
        const newAccept = btnAccept.cloneNode(true);
        const newReject = btnReject.cloneNode(true);
        btnAccept.parentNode.replaceChild(newAccept, btnAccept);
        btnReject.parentNode.replaceChild(newReject, btnReject);
        
        newAccept.onclick = function() { acceptCall(callData); };
        newReject.onclick = function() { rejectCall(callData.id); };
        
        try { document.getElementById('ringtone-audio').play().catch(function(){}); } catch(e){}
    }

    async function acceptCall(callData) {
        if (elIncomingModal) elIncomingModal.classList.remove('active');
        try { 
            const audio = document.getElementById('ringtone-audio');
            audio.pause();
            audio.currentTime = 0;
        } catch(e){}
        
        enableCallUI(true, 'Connexion...');
        state.callId = callData.id;
        
        if (!await setupWebRTC()) {
            endCallUI();
            return;
        }
        
        let remoteDesc = callData.sdp_offer;
        if (typeof remoteDesc === 'string') {
            try { remoteDesc = JSON.parse(remoteDesc); } catch(e) {}
        }
        
        await state.peerConnection.setRemoteDescription(new RTCSessionDescription(remoteDesc));
        
        const answer = await state.peerConnection.createAnswer();
        await state.peerConnection.setLocalDescription(answer);
        
        await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify({
                action: 'answer_call',
                call_id: callData.id,
                answer: JSON.stringify(answer)
            })
        });
        
        state.pollInterval = setInterval(pollCallStatus, 1500);
    }

    async function rejectCall(callId) {
        if (elIncomingModal) elIncomingModal.classList.remove('active');
        try { document.getElementById('ringtone-audio').pause(); } catch(e){}
        fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'reject_call', call_id: callId }) });
    }

    async function endCall() {
        if (state.localStream) {
            state.localStream.getTracks().forEach(t => t.stop());
        }
        if (state.peerConnection) state.peerConnection.close();
        if (state.callId) {
            fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'hangup_call', call_id: state.callId }) });
        }
        endCallUI();
    }

    async function pollCallStatus() {
        if (!state.callId) return;
        
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                body: JSON.stringify({ action: 'poll_call_status', call_id: state.callId })
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                const status = data.call_status;
                
                if (status === 'ended' || status === 'rejected') {
                    endCallUI();
                    alert(status === 'rejected' ? 'Appel refusé' : 'Appel terminé');
                    return;
                }
                
                if (status === 'accepted') {
                    if (elStatusText && elStatusText.textContent !== 'En ligne') showStatus('En ligne', false);
                    
                    if (state.peerConnection && state.peerConnection.signalingState === 'have-local-offer' && data.sdp_answer) {
                        let answer = data.sdp_answer;
                        if (typeof answer === 'string') {
                            try { answer = JSON.parse(answer); } catch(e){}
                        }
                        await state.peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
                    }
                }
                
                if (data.remote_candidates && data.remote_candidates.length > 0) {
                    for (let cand of data.remote_candidates) {
                        const str = JSON.stringify(cand);
                        if (!state.processedCandidates.has(str)) {
                            state.processedCandidates.add(str);
                            if (state.peerConnection) state.peerConnection.addIceCandidate(cand).catch(e => console.log(e));
                        }
                    }
                }
            }
        } catch(e) { console.error(e); }
    }

    function enableCallUI(isActive, status) {
        state.isCalling = isActive;
        if (isActive) {
            if (elPlaceholder) elPlaceholder.style.display = 'none';
            if (elControls) elControls.classList.remove('hidden');
            showStatus(status, false);
        }
    }

    function endCallUI() {
        state.isCalling = false;
        state.callId = null;
        state.localStream = null;
        state.peerConnection = null;
        state.processedCandidates.clear();
        
        if (state.pollInterval) clearInterval(state.pollInterval);
        
        if (elControls) elControls.classList.add('hidden');
        if (elPlaceholder) elPlaceholder.style.display = 'block';
        hideStatus();
        
        if (elLocalVideo) elLocalVideo.srcObject = null;
        if (elRemoteVideo) elRemoteVideo.srcObject = null;
    }

    // Init Events
    document.addEventListener('DOMContentLoaded', () => {
        fetchUsers();
        setInterval(fetchUsers, 10000);
        setInterval(checkIncoming, POLLING_INTERVAL);
        setInterval(() => {
             fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'heartbeat' }) });
        }, 30000);
        
        // Check if we have an incoming call to auto-answer (from banner redirect)
        const urlParams = new URLSearchParams(window.location.search);
        const incomingCallId = urlParams.get('incoming_call');
        const autoAnswer = urlParams.get('auto_answer');
        
        if (incomingCallId && autoAnswer === '1') {
            log('Auto-answering call #' + incomingCallId);
            // Fetch the call data and auto-answer
            (async function() {
                try {
                    const res = await fetch(API_URL, {
                        method: 'POST',
                        body: JSON.stringify({ action: 'check_incoming' })
                    });
                    const data = await res.json();
                    
                    if (data.status === 'incoming' && data.call && data.call.id == incomingCallId) {
                        // Auto-answer the call
                        acceptCall(data.call);
                    } else {
                        // Call may have ended or been answered elsewhere
                        showStatus('Appel non disponible', true);
                        setTimeout(hideStatus, 2000);
                    }
                } catch (e) {
                    console.error('[VOIP] Error auto-answering:', e);
                    showStatus('Erreur connexion', true);
                }
                
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname + '?page=appels');
            })();
        }
        
        // Event Delegation for Call Buttons and Tooltip
        let activeTooltip = null;
        
        if (elUsersList) {
            // Show/hide tooltip on call button click
            elUsersList.addEventListener('click', function(e) {
                // Check if clicked on call type button (Audio/Video)
                const callTypeBtn = e.target.closest('.btn-call-type');
                if (callTypeBtn) {
                    const tooltip = callTypeBtn.closest('.call-type-tooltip');
                    const userId = tooltip.dataset.userId;
                    const userName = tooltip.dataset.userName;
                    const callType = callTypeBtn.dataset.type;
                    
                    // Hide tooltip
                    tooltip.classList.remove('visible');
                    activeTooltip = null;
                    
                    // Start call with appropriate type
                    const videoEnabled = callType === 'video';
                    startVoipCall(userId, userName, videoEnabled);
                    return;
                }
                
                // Check if clicked on main call button
                const btn = e.target.closest('.btn-call-user');
                if (btn && !btn.disabled) {
                    // Toggle tooltip visibility
                    const wrapper = btn.closest('.call-type-wrapper');
                    const tooltip = wrapper.querySelector('.call-type-tooltip');
                    
                    // Hide any other open tooltip
                    if (activeTooltip && activeTooltip !== tooltip) {
                        activeTooltip.classList.remove('visible');
                    }
                    
                    // Toggle this tooltip
                    tooltip.classList.toggle('visible');
                    activeTooltip = tooltip.classList.contains('visible') ? tooltip : null;
                    return;
                }
                
                // If clicked elsewhere inside the list, hide tooltip
                if (activeTooltip && !e.target.closest('.call-type-tooltip')) {
                    activeTooltip.classList.remove('visible');
                    activeTooltip = null;
                }
            });
        }
        
        // Close tooltip when clicking outside
        document.addEventListener('click', function(e) {
            if (activeTooltip && !e.target.closest('.voip-user-card')) {
                activeTooltip.classList.remove('visible');
                activeTooltip = null;
            }
        });
        
        const btnHangup = document.getElementById('btn-hangup');
        if (btnHangup) btnHangup.onclick = endCall;
        
        const btnMuteAudio = document.getElementById('btn-mute-audio');
        if (btnMuteAudio) {
            btnMuteAudio.onclick = function() {
                if (state.localStream) {
                    const track = state.localStream.getAudioTracks()[0];
                    track.enabled = !track.enabled;
                    this.classList.toggle('active');
                    this.innerHTML = track.enabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
                }
            };
        }
        
        const btnMuteVideo = document.getElementById('btn-mute-video');
        if (btnMuteVideo) {
            btnMuteVideo.onclick = function() {
                if (state.localStream) {
                    const track = state.localStream.getVideoTracks()[0];
                    track.enabled = !track.enabled;
                    this.classList.toggle('active');
                    this.innerHTML = track.enabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
                }
            };
        }
        
        // --- Call History ---
        const elHistoryList = document.getElementById('call-history-list');
        const btnRefreshHistory = document.getElementById('btn-refresh-history');
        
        async function fetchCallHistory() {
            if (!elHistoryList) return;
            
            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'get_call_history', limit: 20 })
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    renderCallHistory(data.calls);
                } else {
                    elHistoryList.innerHTML = '<div class="call-history-empty"><i class="fas fa-exclamation-triangle"></i><br>Erreur de chargement</div>';
                }
            } catch (e) {
                console.error('[VOIP] Fetch history error:', e);
                elHistoryList.innerHTML = '<div class="call-history-empty"><i class="fas fa-exclamation-triangle"></i><br>Erreur de connexion</div>';
            }
        }
        
        function renderCallHistory(calls) {
            if (!calls || calls.length === 0) {
                elHistoryList.innerHTML = '<div class="call-history-empty"><i class="fas fa-phone-slash"></i><br>Aucun appel récent</div>';
                return;
            }
            
            const html = calls.map(function(call) {
                // Determine icon based on call direction and status
                let iconClass = 'outgoing';
                let iconHtml = '<i class="fas fa-phone-alt" style="transform: rotate(135deg)"></i>';
                
                if (!call.is_outgoing) {
                    iconClass = 'incoming';
                    iconHtml = '<i class="fas fa-phone-alt" style="transform: rotate(-45deg)"></i>';
                }
                
                if (call.status === 'missed' || call.status === 'rejected') {
                    iconClass = 'missed';
                    iconHtml = '<i class="fas fa-phone-slash"></i>';
                }
                
                // Format date
                const callDate = new Date(call.created_at);
                const now = new Date();
                const diffMs = now - callDate;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHrs = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);
                
                let timeStr = '';
                if (diffMins < 1) {
                    timeStr = "À l'instant";
                } else if (diffMins < 60) {
                    timeStr = 'Il y a ' + diffMins + ' min';
                } else if (diffHrs < 24) {
                    timeStr = 'Il y a ' + diffHrs + 'h';
                } else if (diffDays < 7) {
                    timeStr = 'Il y a ' + diffDays + 'j';
                } else {
                    timeStr = callDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
                }
                
                const callTypeIcon = call.call_type === 'video' ? 'fa-video' : 'fa-phone';
                const callTypeLabel = call.call_type === 'video' ? 'Vidéo' : 'Audio';
                
                const durationShow = (call.status === 'ended' && call.duration_seconds > 0) ? call.duration_formatted : '';
                
                // Direction label
                let directionLabel = call.is_outgoing ? 'Sortant' : 'Entrant';
                if (call.status === 'missed') directionLabel = 'Manqué';
                if (call.status === 'rejected') directionLabel = 'Refusé';
                
                return '<div class="call-history-item" data-contact-id="' + call.contact_id + '" data-contact-name="' + (call.contact_name || 'Inconnu').replace(/"/g, '&quot;') + '">' +
                       '    <div class="call-direction-icon ' + iconClass + '">' + iconHtml + '</div>' +
                       '    <div class="call-history-info">' +
                       '        <div class="call-history-name">' + (call.contact_name || 'Inconnu') + '</div>' +
                       '        <div class="call-history-meta">' +
                       '            <span class="call-direction-label ' + iconClass + '">' + directionLabel + '</span>' +
                       '            <span class="call-type-badge"><i class="fas ' + callTypeIcon + '"></i> ' + callTypeLabel + '</span>' +
                       '        </div>' +
                       '    </div>' +
                       '    <div class="call-history-time">' +
                       '        <div>' + timeStr + '</div>' +
                       (durationShow ? '        <div class="call-history-duration">' + durationShow + '</div>' : '') +
                       '    </div>' +
                       '</div>';
            }).join('');
            
            elHistoryList.innerHTML = html;
            
            // Add click handlers to recall
            elHistoryList.querySelectorAll('.call-history-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    const contactId = this.dataset.contactId;
                    const contactName = this.dataset.contactName;
                    if (contactId && !state.isCalling) {
                        // Default to audio call on history click
                        startVoipCall(contactId, contactName, true);
                    }
                });
            });
        }
        
        // Refresh button
        if (btnRefreshHistory) {
            btnRefreshHistory.addEventListener('click', function() {
                this.querySelector('i').classList.add('fa-spin');
                fetchCallHistory().finally(() => {
                    setTimeout(() => this.querySelector('i').classList.remove('fa-spin'), 500);
                });
            });
        }
        
        // Load history on page load
        fetchCallHistory();
    });

})();
</script>
