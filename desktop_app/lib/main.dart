/// GeekBoard Desktop Application
/// Point d'entrée principal
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'providers/ui_provider.dart';
import 'services/auth_service.dart';
import 'services/api_service.dart';
import 'theme/macos_theme.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'widgets/servo_loader.dart';

void main() {
  runApp(const GeekBoardApp());
}

class GeekBoardApp extends StatelessWidget {
  const GeekBoardApp({super.key});

  @override
  Widget build(BuildContext context) {
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
          return MaterialApp(
            title: 'GeekBoard Desktop',
            debugShowCheckedModeBanner: false,
            theme: MacOSTheme.lightTheme,
            darkTheme: MacOSTheme.darkTheme,
            themeMode: uiProvider.themeMode, 
            home: const AuthWrapper(),
          );
        },
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

  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final authService = context.read<AuthService>();
    await authService.init();
    setState(() {
      _isLoading = false;
    });
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

    if (authService.isAuthenticated) {
      return const DashboardScreen();
    } else {
      return const LoginScreen();
    }
  }
}
