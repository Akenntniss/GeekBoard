/// GeekBoard Desktop Application
/// Point d'entrée principal
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'providers/ui_provider.dart';
import 'services/auth_service.dart';
import 'services/api_service.dart';
import 'theme/macos_theme.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'widgets/servo_loader.dart';

import 'package:window_manager/window_manager.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await windowManager.ensureInitialized();

  WindowOptions windowOptions = const WindowOptions(
    size: Size(1280, 800),
    minimumSize: Size(1024, 700),
    center: true,
    backgroundColor: Colors.transparent,
    skipTaskbar: false,
    titleBarStyle: TitleBarStyle.normal,
  );

  windowManager.waitUntilReadyToShow(windowOptions, () async {
    await windowManager.show();
    await windowManager.focus();
  });

  runApp(const GeekBoardApp());
}

/// Intent pour le zoom avant
class ZoomInIntent extends Intent {
  const ZoomInIntent();
}

/// Intent pour le zoom arrière
class ZoomOutIntent extends Intent {
  const ZoomOutIntent();
}

/// Intent pour le reset zoom
class ZoomResetIntent extends Intent {
  const ZoomResetIntent();
}

class GeekBoardApp extends StatelessWidget {
  const GeekBoardApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Détecter la plateforme pour le modifier key
    final bool isMacOS = Platform.isMacOS;

    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthService()),
        ChangeNotifierProvider(create: (_) => UiProvider()),
        ProxyProvider<AuthService, ApiService>(
          update: (_, auth, __) => ApiService(token: auth.token),
        ),
      ],
      child: Consumer<UiProvider>(
        builder: (context, uiProvider, child) {
          return Shortcuts(
            shortcuts: <ShortcutActivator, Intent>{
              // Zoom In : CMD+= (Mac) ou CTRL+= (PC) — la touche + est sur = sans shift
              SingleActivator(LogicalKeyboardKey.equal,
                  meta: isMacOS, control: !isMacOS): const ZoomInIntent(),
              // Zoom In alt : CMD+Shift+= (Mac) ou CTRL+Shift+= (PC)
              SingleActivator(LogicalKeyboardKey.equal,
                  meta: isMacOS, control: !isMacOS, shift: true):
                  const ZoomInIntent(),
              // Zoom In : pavé numérique +
              SingleActivator(LogicalKeyboardKey.numpadAdd,
                  meta: isMacOS, control: !isMacOS): const ZoomInIntent(),
              // Zoom Out : CMD+- (Mac) ou CTRL+- (PC)
              SingleActivator(LogicalKeyboardKey.minus,
                  meta: isMacOS, control: !isMacOS): const ZoomOutIntent(),
              // Zoom Out : pavé numérique -
              SingleActivator(LogicalKeyboardKey.numpadSubtract,
                  meta: isMacOS, control: !isMacOS): const ZoomOutIntent(),
              // Reset : CMD+0 (Mac) ou CTRL+0 (PC)
              SingleActivator(LogicalKeyboardKey.digit0,
                  meta: isMacOS, control: !isMacOS): const ZoomResetIntent(),
              // Reset : pavé numérique 0
              SingleActivator(LogicalKeyboardKey.numpad0,
                  meta: isMacOS, control: !isMacOS): const ZoomResetIntent(),
            },
            child: Actions(
              actions: <Type, Action<Intent>>{
                ZoomInIntent: CallbackAction<ZoomInIntent>(
                  onInvoke: (intent) {
                    uiProvider.zoomIn();
                    return null;
                  },
                ),
                ZoomOutIntent: CallbackAction<ZoomOutIntent>(
                  onInvoke: (intent) {
                    uiProvider.zoomOut();
                    return null;
                  },
                ),
                ZoomResetIntent: CallbackAction<ZoomResetIntent>(
                  onInvoke: (intent) {
                    uiProvider.resetZoom();
                    return null;
                  },
                ),
              },
              child: _ZoomWrapper(
                scaleFactor: uiProvider.scaleFactor,
                scalePercent: uiProvider.scalePercent,
                child: MaterialApp(
                  title: 'GeekBoard Desktop',
                  debugShowCheckedModeBanner: false,
                  theme: MacOSTheme.lightTheme,
                  darkTheme: MacOSTheme.darkTheme,
                  themeMode: uiProvider.themeMode,
                  home: const AuthWrapper(),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Widget qui applique le zoom global et affiche un indicateur éphémère
class _ZoomWrapper extends StatefulWidget {
  final double scaleFactor;
  final int scalePercent;
  final Widget child;

  const _ZoomWrapper({
    required this.scaleFactor,
    required this.scalePercent,
    required this.child,
  });

  @override
  State<_ZoomWrapper> createState() => _ZoomWrapperState();
}

class _ZoomWrapperState extends State<_ZoomWrapper> {
  bool _showIndicator = false;

  @override
  void didUpdateWidget(covariant _ZoomWrapper oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.scaleFactor != widget.scaleFactor) {
      setState(() => _showIndicator = true);
      Future.delayed(const Duration(milliseconds: 1200), () {
        if (mounted) setState(() => _showIndicator = false);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final mq = MediaQuery.of(context);
    // Taille logique modifiée : plus petite = zoom in, plus grande = zoom out
    final effectiveSize = Size(
      mq.size.width / widget.scaleFactor,
      mq.size.height / widget.scaleFactor,
    );

    return Directionality(
      textDirection: TextDirection.ltr,
      child: Stack(
        children: [
          // App avec zoom appliqué via FittedBox
          FittedBox(
            fit: BoxFit.fill,
            alignment: Alignment.topLeft,
            child: SizedBox(
              width: effectiveSize.width,
              height: effectiveSize.height,
              child: MediaQuery(
                data: mq.copyWith(size: effectiveSize),
                child: widget.child,
              ),
            ),
          ),
          // Indicateur de zoom éphémère
          if (_showIndicator)
            Positioned(
              bottom: 40,
              left: 0,
              right: 0,
              child: Center(
                child: AnimatedOpacity(
                  opacity: _showIndicator ? 1.0 : 0.0,
                  duration: const Duration(milliseconds: 300),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.black87,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Zoom : ${widget.scalePercent}%',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        decoration: TextDecoration.none,
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}


/// Widget qui gère la navigation selon l'état d'authentification
class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  bool _isLoading = true;
  bool _subscriptionBlocked = false;

  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final authService = context.read<AuthService>();
    final isAuthenticated = await authService.init();
    
    // Check if subscription is blocked after init
    if (isAuthenticated && authService.isSubscriptionBlocked) {
      _subscriptionBlocked = true;
    }
    
    setState(() {
      _isLoading = false;
    });
    
    // Show dialog after build if subscription is blocked
    if (_subscriptionBlocked && mounted) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _showSubscriptionBlockedDialog(authService);
      });
    }
  }
  
  void _showSubscriptionBlockedDialog(AuthService authService) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: const [
            Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 28),
            SizedBox(width: 12),
            Text('Abonnement requis'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              authService.subscriptionMessage ?? 'Votre abonnement a expiré ou est inactif.',
              style: const TextStyle(fontSize: 15),
            ),
            const SizedBox(height: 16),
            const Text(
              'Cliquez sur le bouton ci-dessous pour renouveler votre abonnement en quelques clics.',
              style: TextStyle(color: Colors.grey, fontSize: 13),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Fermer'),
          ),
          ElevatedButton.icon(
            onPressed: () async {
              final url = authService.subscriptionRedirectUrl;
              if (url != null) {
                final uri = Uri.parse(url);
                if (await canLaunchUrl(uri)) {
                  await launchUrl(uri, mode: LaunchMode.externalApplication);
                }
              }
            },
            icon: const Icon(Icons.open_in_new),
            label: const Text('Activer mon abonnement'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF667eea),
              foregroundColor: Colors.white,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        body: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                const Color(0xFF1a1a2e),
                const Color(0xFF16213e),
                const Color(0xFF0f3460),
              ],
            ),
          ),
          child: const Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                ServoLoader(),
                SizedBox(height: 24),
                Text(
                  'Chargement du système...',
                  style: TextStyle(color: Colors.white, fontSize: 16, letterSpacing: 1.2),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final authService = context.watch<AuthService>();

    // If subscription is blocked, show login screen (dialog will appear)
    if (authService.isAuthenticated && !authService.isSubscriptionBlocked) {
      return const DashboardScreen();
    } else {
      return const LoginScreen();
    }
  }
}
