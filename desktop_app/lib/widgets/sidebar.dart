/// Sidebar - Menu de navigation latéral (Full version)
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import '../providers/ui_provider.dart';
import '../theme/macos_theme.dart';
import 'package:flutter_svg/flutter_svg.dart';
// Screens imports
import '../screens/login_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/reparations/reparations_screen.dart';
import '../screens/clients/clients_screen.dart';
import '../screens/taches/taches_screen.dart';
import '../screens/commandes/commandes_screen.dart';
import '../screens/devis/devis_screen.dart';
import '../screens/inventaire/inventaire_screen.dart';
import '../screens/suppliers/suppliers_screen.dart';
import '../screens/partners/partner_accounts_screen.dart';
import '../screens/catalogue/catalogue_screen.dart';
import '../screens/rachat/rachat_screen.dart';
import '../screens/missions/missions_screen.dart';
import '../screens/absences/presence_screen.dart';
import '../screens/sms/sms_historique_screen.dart';
import '../screens/formations/admin/admin_formation_screen.dart';
import '../screens/knowledge/knowledge_base_screen.dart';
import '../screens/formations/formations_screen.dart';
import '../screens/admin/employes_screen.dart';
import '../screens/admin/pointage_screen.dart';
import '../screens/admin/logs_screen.dart';
import '../screens/admin/kpi_screen.dart';
import '../screens/admin/bugs_screen.dart';
import '../screens/admin/sms_templates_screen.dart' as admin_sms;
import '../screens/settings/screen_settings_screen.dart';
import '../screens/settings/settings_screen.dart';

class Sidebar extends StatelessWidget {
  final String currentRoute;
  const Sidebar({super.key, required this.currentRoute});



  @override
  Widget build(BuildContext context) {
    final authService = context.watch<AuthService>();
    final uiProvider = context.watch<UiProvider>();
    final _collapsed = uiProvider.isSidebarCollapsed;
    
    final role = authService.currentUser?.role ?? '';
    final isAdmin = role == 'admin' || role == 'superadmin';

    // Width: 250 (Full) vs 80 (Collapsed)
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      width: _collapsed ? 80 : 250, 
      curve: Curves.easeInOut,
      decoration: BoxDecoration(
        color: Theme.of(context).brightness == Brightness.dark 
            ? MacOSTheme.sidebarBackgroundDark 
            : MacOSTheme.sidebarBackground,
        border: Border(right: BorderSide(color: MacOSTheme.divider, width: 1)),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: _collapsed ? MainAxisAlignment.center : MainAxisAlignment.start,
              children: [
                if (!_collapsed) ...[
                  SvgPicture.asset(
                    'assets/logoservo.svg',
                    height: 28,
                    width: 28,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('SERVO', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                        Text(authService.currentShop?.name ?? '', style: const TextStyle(color: MacOSTheme.textSecondary, fontSize: 10), overflow: TextOverflow.ellipsis),
                      ],
                    ),
                  ),
                  // Bouton collapse (Flèche gauche)
                  GestureDetector(
                    onTap: () => context.read<UiProvider>().toggleSidebar(),
                    child: const Icon(CupertinoIcons.chevron_left_circle, color: MacOSTheme.textSecondary, size: 20),
                  ),
                ] else ...[
                  // Mode collapsed : Juste l'icone "déplier" ou le logo
                   GestureDetector(
                    onTap: () => context.read<UiProvider>().toggleSidebar(),
                    child: SvgPicture.asset(
                      'assets/logoservo.svg', 
                      height: 32,
                      width: 32,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const Divider(height: 1, color: MacOSTheme.divider),
          
          // Menu
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 6),
              children: [
                if (!_collapsed) _buildSection('Principal'),
                _MenuItem(icon: CupertinoIcons.square_grid_2x2, label: 'Dashboard', route: '/dashboard', currentRoute: currentRoute, onTap: () => _nav(context, '/dashboard'), collapsed: _collapsed, description: 'Vue d\'ensemble de l\'activité'),
                _MenuItem(icon: CupertinoIcons.wrench, label: 'Réparations', route: '/reparations', currentRoute: currentRoute, onTap: () => _nav(context, '/reparations'), collapsed: _collapsed, description: 'Gestion des interventions techniques'),
                _MenuItem(icon: CupertinoIcons.doc_text, label: 'Devis', route: '/devis', currentRoute: currentRoute, onTap: () => _nav(context, '/devis'), collapsed: _collapsed, description: 'Création et suivi des devis'),
                _MenuItem(icon: CupertinoIcons.checkmark_circle, label: 'Tâches', route: '/taches', currentRoute: currentRoute, onTap: () => _nav(context, '/taches'), collapsed: _collapsed, description: 'Liste des tâches à faire'),
                _MenuItem(icon: CupertinoIcons.cube_box, label: 'Commandes', route: '/commandes', currentRoute: currentRoute, onTap: () => _nav(context, '/commandes'), collapsed: _collapsed, description: 'Suivi des commandes clients'),
                
                if (!_collapsed) _buildSection('Gestion'),
                _MenuItem(icon: CupertinoIcons.person_2, label: 'Clients', route: '/clients', currentRoute: currentRoute, onTap: () => _nav(context, '/clients'), collapsed: _collapsed, description: 'Base de données clients'),
                _MenuItem(icon: CupertinoIcons.bag, label: 'Catalogue', route: '/catalogue', currentRoute: currentRoute, onTap: () => _nav(context, '/catalogue'), collapsed: _collapsed, description: 'Catalogue produits et services'),
                _MenuItem(icon: CupertinoIcons.arrow_2_circlepath, label: 'Rachat', route: '/rachat', currentRoute: currentRoute, onTap: () => _nav(context, '/rachat'), collapsed: _collapsed, description: 'Gestion des rachats de matériel'),
                _MenuItem(icon: CupertinoIcons.archivebox, label: 'Inventaire', route: '/inventaire', currentRoute: currentRoute, onTap: () => _nav(context, '/inventaire'), collapsed: _collapsed, description: 'État du stock en temps réel'),
                
                if (!_collapsed) _buildSection('Équipe'),
                _MenuItem(icon: CupertinoIcons.flag, label: 'Missions', route: '/missions', currentRoute: currentRoute, onTap: () => _nav(context, '/missions'), collapsed: _collapsed, description: 'Missions attribuées à l\'équipe'),
                _MenuItem(icon: CupertinoIcons.calendar_badge_minus, label: 'Absences', route: '/absences', currentRoute: currentRoute, onTap: () => _nav(context, '/absences'), collapsed: _collapsed, description: 'Gestion des congés et absences'),
                _MenuItem(icon: CupertinoIcons.book, label: 'Base connaissance', route: '/knowledge', currentRoute: currentRoute, onTap: () => _nav(context, '/knowledge'), collapsed: _collapsed, description: 'Documentation et procédures'),
                _MenuItem(icon: CupertinoIcons.play_circle, label: 'Formation', route: '/formations', currentRoute: currentRoute, onTap: () => _nav(context, '/formations'), collapsed: _collapsed, description: 'Modules de formation interne'),
                
                if (!_collapsed) _buildSection('Contacts'),
                _MenuItem(icon: CupertinoIcons.building_2_fill, label: 'Fournisseurs', route: '/fournisseurs', currentRoute: currentRoute, onTap: () => _nav(context, '/fournisseurs'), collapsed: _collapsed, description: 'Liste des fournisseurs'),
                _MenuItem(icon: CupertinoIcons.person_2_fill, label: 'Partenaires', route: '/partenaires', currentRoute: currentRoute, onTap: () => _nav(context, '/partenaires'), collapsed: _collapsed, description: 'Comptes partenaires'),
                _MenuItem(icon: CupertinoIcons.bubble_left, label: 'SMS Historique', route: '/sms', currentRoute: currentRoute, onTap: () => _nav(context, '/sms'), collapsed: _collapsed, description: 'Historique des échanges SMS'),
                
                if (isAdmin) ...[
                  if (!_collapsed) _buildSection('Administration'),
                  _MenuItem(icon: CupertinoIcons.person_3, label: 'Employés', route: '/admin/employes', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/employes'), collapsed: _collapsed, description: 'Gestion des comptes employés'),
                  _MenuItem(icon: CupertinoIcons.clock, label: 'Pointage', route: '/admin/pointage', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/pointage'), collapsed: _collapsed, description: 'Suivi des heures de présence'),
                  _MenuItem(icon: CupertinoIcons.chart_bar, label: 'KPI Dashboard', route: '/admin/kpi', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/kpi'), collapsed: _collapsed, description: 'Indicateurs clés de performance'),
                  _MenuItem(icon: CupertinoIcons.doc_text_search, label: 'Logs', route: '/admin/logs', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/logs'), collapsed: _collapsed, description: 'Journaux d\'activité système'),
                  _MenuItem(icon: CupertinoIcons.ant, label: 'Bugs', route: '/admin/bugs', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/bugs'), collapsed: _collapsed, description: 'Suivi des signalements de bugs'),
                  _MenuItem(icon: CupertinoIcons.text_bubble, label: 'Templates SMS', route: '/admin/sms_templates', currentRoute: currentRoute, onTap: () => _nav(context, '/admin/sms_templates'), collapsed: _collapsed, description: 'Modèles de messages SMS'),
                  _MenuItem(icon: CupertinoIcons.book_solid, label: 'Suivi Formations', route: '/admin_formation', currentRoute: currentRoute, onTap: () => _nav(context, '/admin_formation'), collapsed: _collapsed, description: 'Suivi de progression formations'),
                  _MenuItem(icon: CupertinoIcons.desktopcomputer, label: 'Écrans Connectés', route: '/settings/screens', currentRoute: currentRoute, onTap: () => _nav(context, '/settings/screens'), collapsed: _collapsed, description: 'Gestion des affichages clients'),
                  _MenuItem(icon: CupertinoIcons.gear_alt_fill, label: 'Paramètres', route: '/settings', currentRoute: currentRoute, onTap: () => _nav(context, '/settings'), collapsed: _collapsed, description: 'Configuration de l\'application'),
                ],
              ],
            ),
          ),
          
          // User & Logout
          Container(
            padding: const EdgeInsets.all(12),
            decoration: const BoxDecoration(border: Border(top: BorderSide(color: MacOSTheme.divider))),
            child: Row(
              mainAxisAlignment: _collapsed ? MainAxisAlignment.center : MainAxisAlignment.start,
              children: [
                Container(width: 32, height: 32, decoration: BoxDecoration(color: MacOSTheme.accentPurple.withOpacity(0.1), borderRadius: BorderRadius.circular(16)), child: Center(child: Text(_getInitials(authService.currentUser?.name ?? ''), style: const TextStyle(color: MacOSTheme.accentPurple, fontWeight: FontWeight.bold, fontSize: 11)))),
                if (!_collapsed) ...[
                   const SizedBox(width: 8),
                   Expanded(child: Text(authService.currentUser?.name ?? '', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500), overflow: TextOverflow.ellipsis)),
                   // Smart Clock Button (shows either Entrée or Sortie based on status)
                   _SmartClockButton(authService: authService),
                   const SizedBox(width: 8),
                   CupertinoButton(padding: EdgeInsets.zero, minSize: 28, child: const Icon(CupertinoIcons.square_arrow_right, color: MacOSTheme.dangerRed, size: 18), onPressed: () async { await authService.logout(); if (context.mounted) Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginScreen()), (r) => false); }),
                ]
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection(String label) => Padding(padding: const EdgeInsets.only(left: 10, top: 12, bottom: 4), child: Text(label.toUpperCase(), style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: MacOSTheme.textSecondary, letterSpacing: 0.5)));
  String _getInitials(String name) { if (name.isEmpty) return '?'; final p = name.split(' '); if (p.length >= 2) return '${p[0][0]}${p[1][0]}'.toUpperCase(); return name[0].toUpperCase(); }

  void _nav(BuildContext context, String route) {
    if (route == currentRoute) return;
    Widget screen;
    switch (route) {
      case '/dashboard': screen = const DashboardScreen(); break;
      case '/reparations': screen = const ReparationsScreen(); break;
      case '/devis': screen = const DevisScreen(); break;
      case '/taches': screen = const TachesScreen(); break;
      case '/commandes': screen = const CommandesScreen(); break;
      case '/clients': screen = const ClientsScreen(); break;
      case '/catalogue': screen = const CatalogueScreen(); break;
      case '/rachat': screen = const RachatScreen(); break;
      case '/inventaire': screen = const InventaireScreen(); break;
      case '/missions': screen = const MissionsScreen(); break;
      case '/absences': screen = const PresenceScreen(); break;
      case '/knowledge': screen = const KnowledgeBaseScreen(); break;
      case '/formations': screen = const FormationsScreen(); break;
      case '/fournisseurs': screen = const SuppliersScreen(); break;
      case '/partenaires': screen = const PartnerAccountsScreen(); break;
      case '/sms': screen = const SmsHistoriqueScreen(); break;
      case '/admin/employes': screen = const EmployesScreen(); break;
      case '/admin/pointage': screen = const PointageScreen(); break;
      case '/admin/kpi': screen = const KpiScreen(); break;
      case '/admin/logs': screen = const LogsScreen(); break;
      case '/admin/bugs': screen = const BugsScreen(); break;
      case '/admin/sms_templates': screen = const admin_sms.SmsTemplatesScreen(); break;
      case '/admin_formation': screen = const AdminFormationScreen(); break;
      case '/settings/screens': screen = const ScreenSettingsScreen(); break;
      case '/settings': screen = const SettingsScreen(); break;
      default: return;
    }
    Navigator.of(context).pushReplacement(
      PageRouteBuilder(
        pageBuilder: (context, animation, secondaryAnimation) => screen,
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          // Animation fluide et moderne : Léger glissement depuis la droite + Fondu
          const begin = Offset(0.05, 0.0); 
          const end = Offset.zero;
          const curve = Curves.easeOutQuart; // Courbe très douce (Apple style)

          var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
          var fadeTween = Tween(begin: 0.0, end: 1.0).chain(CurveTween(curve: Curves.easeOut));

          return SlideTransition(
            position: animation.drive(tween),
            child: FadeTransition(
              opacity: animation.drive(fadeTween),
              child: child,
            ),
          );
        },
        transitionDuration: const Duration(milliseconds: 350),
      ),
    );
  }
}

class _MenuItem extends StatefulWidget {
  final IconData icon; final String label; final String route; final String currentRoute; final VoidCallback onTap; final bool collapsed; final String description;
  const _MenuItem({required this.icon, required this.label, required this.route, required this.currentRoute, required this.onTap, this.collapsed = false, this.description = ''});
  @override
  State<_MenuItem> createState() => _MenuItemState();
}

class _MenuItemState extends State<_MenuItem> {
  bool _hovered = false;
  OverlayEntry? _overlayEntry;
  
  // Active state computed from widget
  bool get active => widget.route == widget.currentRoute;
  
  @override
  void dispose() {
    _removeOverlay();
    super.dispose();
  }

  void _removeOverlay() {
    _overlayEntry?.remove();
    _overlayEntry = null;
  }

  void _showOverlay() {
    if (!widget.collapsed) return;

    final overlay = Overlay.of(context);
    final renderBox = context.findRenderObject() as RenderBox;
    final size = renderBox.size;
    final offset = renderBox.localToGlobal(Offset.zero);

    _overlayEntry = OverlayEntry(
      builder: (context) => Positioned(
        left: offset.dx + size.width + 10,
        top: offset.dy,
        child: Material(
          color: Colors.transparent,
          child: Container(
            constraints: const BoxConstraints(maxWidth: 200),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.9),
              borderRadius: BorderRadius.circular(6),
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 4, offset: const Offset(2, 2)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  widget.label,
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                ),
                if (widget.description.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    widget.description,
                    style: const TextStyle(color: Colors.white70, fontSize: 11),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );

    overlay.insert(_overlayEntry!);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return MouseRegion(
      onEnter: (_) {
        setState(() => _hovered = true);
        if (widget.collapsed) _showOverlay();
      },
      onExit: (_) {
        setState(() => _hovered = false);
        _removeOverlay();
      },
      child: GestureDetector(
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 150),
          margin: const EdgeInsets.symmetric(vertical: 2, horizontal: 8),
          padding: EdgeInsets.symmetric(horizontal: widget.collapsed ? 0 : 12, vertical: 8),
          decoration: BoxDecoration(
            // Active: Filled Blue (Finder Style), Hover: Light Gray
            color: active 
                ? MacOSTheme.accentBlue 
                : (_hovered 
                    ? (isDark ? Colors.white.withOpacity(0.1) : Colors.black.withOpacity(0.05)) 
                    : Colors.transparent),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            mainAxisAlignment: widget.collapsed ? MainAxisAlignment.center : MainAxisAlignment.start,
            children: [
            Icon(
              widget.icon, 
              size: 18, 
              // Active: White, Inactive: Theme Primary Color
              color: active ? Colors.white : (isDark ? Colors.white : MacOSTheme.textPrimary)
            ),
            if (!widget.collapsed) ...[
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  widget.label, 
                  style: TextStyle(
                    fontSize: 13, 
                    fontWeight: active ? FontWeight.w600 : FontWeight.normal, 
                    // Active: White, Inactive: Theme Primary
                    color: active ? Colors.white : (isDark ? Colors.white : MacOSTheme.textPrimary)
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ]
          ]),
        ),
      ),
    );
  }
}

/// Smart Clock Button - Shows Entrée or Sortie based on current status
class _SmartClockButton extends StatefulWidget {
  final AuthService authService;

  const _SmartClockButton({required this.authService});

  @override
  State<_SmartClockButton> createState() => _SmartClockButtonState();
}

class _SmartClockButtonState extends State<_SmartClockButton> {
  bool _isLoading = false;
  bool _isClockedIn = false;
  bool _isCheckingStatus = true;

  @override
  void initState() {
    super.initState();
    _checkStatus();
  }

  Future<void> _checkStatus() async {
    try {
      final api = widget.authService.getApiService();
      final subdomain = widget.authService.getSubdomain();
      
      final response = await api.getExternal(
        'https://$subdomain.servo.tools/time_tracking_api.php?action=get_status'
      );
      
      if (mounted && response['success'] == true && response['data'] != null) {
        setState(() {
          _isClockedIn = response['data']['is_clocked_in'] == true;
          _isCheckingStatus = false;
        });
      }
    } catch (e) {
      debugPrint('Clock status check error: $e');
      if (mounted) setState(() => _isCheckingStatus = false);
    }
  }

  Future<void> _handleClock() async {
    setState(() => _isLoading = true);
    try {
      final api = widget.authService.getApiService();
      final subdomain = widget.authService.getSubdomain();
      final action = _isClockedIn ? 'clock_out' : 'clock_in';
      
      final response = await api.postExternal(
        'https://$subdomain.servo.tools/time_tracking_api.php?action=$action',
        {}
      );
      
      if (response['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_isClockedIn ? 'Fin de journée enregistrée 👋' : 'Pointage enregistré ✅'),
              backgroundColor: _isClockedIn ? Colors.orange : MacOSTheme.successGreen,
            ),
          );
          // Toggle status
          setState(() => _isClockedIn = !_isClockedIn);
        }
      } else {
        throw Exception(response['message'] ?? 'Erreur');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isCheckingStatus) {
      return const SizedBox(
        width: 80,
        height: 24,
        child: Center(child: SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))),
      );
    }

    final color = _isClockedIn ? Colors.orange : MacOSTheme.successGreen;
    final label = _isClockedIn ? 'Sortie' : 'Entrée';
    final icon = _isClockedIn ? CupertinoIcons.arrow_left_circle : CupertinoIcons.arrow_right_circle;
    
    return Tooltip(
      message: _isClockedIn ? 'Pointer votre sortie' : 'Pointer votre arrivée',
      child: CupertinoButton(
        padding: EdgeInsets.zero,
        minSize: 32,
        onPressed: _isLoading ? null : _handleClock,
        child: _isLoading
          ? const SizedBox(width: 80, height: 28, child: Center(child: SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))))
          : Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), // Reduced vertical padding slightly for better optics
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(8),
                boxShadow: [
                  BoxShadow(
                    color: color.withOpacity(0.4),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Icon(icon, size: 14, color: Colors.white),
                  const SizedBox(width: 6),
                  Padding(
                    padding: const EdgeInsets.only(top: 1), // Optical correction for caps text
                    child: Text(
                      label.toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
      ),
    );
  }
}
