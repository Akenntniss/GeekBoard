/// Écran de Login GeekBoard Desktop
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/auth_service.dart';
import 'dashboard_screen.dart';
import '../widgets/servo_animation.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _subdomainController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _rememberMe = false;

  @override
  void initState() {
    super.initState();
    _loadLastSubdomain();
  }

  Future<void> _loadLastSubdomain() async {
    final authService = context.read<AuthService>();
    final lastSubdomain = await authService.getLastSubdomain();
    if (lastSubdomain != null) {
      _subdomainController.text = lastSubdomain;
    }
  }

  @override
  void dispose() {
    _subdomainController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    final authService = context.read<AuthService>();
    final success = await authService.login(
      _subdomainController.text.trim(),
      _emailController.text.trim(),
      _passwordController.text,
      rememberMe: _rememberMe,
    );

    if (success && mounted) {
      // Vérifier l'abonnement après login réussi
      final subscriptionOk = await authService.checkSubscription();
      
      if (!subscriptionOk && mounted) {
        // Afficher le dialogue d'abonnement expiré
        _showSubscriptionBlockedDialog(authService);
        return;
      }
      
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const DashboardScreen()),
      );
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
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(32),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Card(
                elevation: 20,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(24),
                ),
                color: Colors.white.withValues(alpha: 0.95),
                child: Padding(
                  padding: const EdgeInsets.all(40),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Logo / Titre
                        // Logo / Titre
                        Image.asset(
                          'assets/servologo.png',
                          height: 80,
                          width: 80,
                        ),
                        const SizedBox(height: 24),
                        const ServoAnimation(height: 40),
                        const SizedBox(height: 40),

                        // Champ Sous-domaine
                        TextFormField(
                          controller: _subdomainController,
                          style: const TextStyle(color: Colors.black87),
                          decoration: InputDecoration(
                            labelText: 'Sous-domaine',
                            labelStyle: TextStyle(color: Colors.grey[700]),
                            hintText: 'exemple : mdg , phonerepair , fixit',
                            hintStyle: TextStyle(color: Colors.grey[400]),
                            prefixIcon: const Icon(Icons.store, color: Colors.grey),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            filled: true,
                            fillColor: Colors.grey[50],
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Veuillez entrer votre sous-domaine';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),

                        // Champ Email ou Username
                        TextFormField(
                          controller: _emailController,
                          keyboardType: TextInputType.text, // Changed from emailAddress
                          style: const TextStyle(color: Colors.black87),
                          decoration: InputDecoration(
                            labelText: 'Email ou Nom d\'utilisateur',
                            labelStyle: TextStyle(color: Colors.grey[700]),
                            prefixIcon: const Icon(Icons.person, color: Colors.grey), // Changed icon
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            filled: true,
                            fillColor: Colors.grey[50],
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Veuillez entrer votre identifiant';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),

                        // Champ Mot de passe
                        TextFormField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          style: const TextStyle(color: Colors.black87),
                          decoration: InputDecoration(
                            labelText: 'Mot de passe',
                            labelStyle: TextStyle(color: Colors.grey[700]),
                            prefixIcon: const Icon(Icons.lock, color: Colors.grey),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                color: Colors.grey,
                              ),
                              onPressed: () {
                                setState(() {
                                  _obscurePassword = !_obscurePassword;
                                });
                              },
                            ),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            filled: true,
                            fillColor: Colors.grey[50],
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Veuillez entrer votre mot de passe';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Checkbox Rester connecté
                        CheckboxListTile(
                          value: _rememberMe,
                          onChanged: (value) {
                            setState(() {
                              _rememberMe = value ?? false;
                            });
                          },
                          title: Text(
                            "Rester connecté",
                            style: GoogleFonts.poppins(fontSize: 14, color: Colors.grey[800]),
                          ),
                          controlAffinity: ListTileControlAffinity.leading,
                          contentPadding: EdgeInsets.zero,
                          activeColor: const Color(0xFF667eea),
                          dense: true,
                        ),
                        
                        const SizedBox(height: 24),

                        // Affichage des erreurs
                        Consumer<AuthService>(
                          builder: (context, auth, _) {
                            if (auth.error != null) {
                              return Container(
                                padding: const EdgeInsets.all(12),
                                margin: const EdgeInsets.only(bottom: 16),
                                decoration: BoxDecoration(
                                  color: Colors.red[50],
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: Colors.red[200]!),
                                ),
                                child: Row(
                                  children: [
                                    Icon(Icons.error, color: Colors.red[700]),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        auth.error!,
                                        style: TextStyle(color: Colors.red[700]),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            }
                            return const SizedBox.shrink();
                          },
                        ),

                        // Bouton Connexion
                        Consumer<AuthService>(
                          builder: (context, auth, _) {
                            return SizedBox(
                              width: double.infinity,
                              height: 50,
                              child: ElevatedButton(
                                onPressed: auth.isLoading ? null : _handleLogin,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF667eea),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  elevation: 4,
                                ),
                                child: auth.isLoading
                                    ? const SizedBox(
                                        width: 24,
                                        height: 24,
                                        child: CircularProgressIndicator(
                                          color: Colors.white,
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : Text(
                                        'Se connecter',
                                        style: GoogleFonts.poppins(
                                          fontSize: 16,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
