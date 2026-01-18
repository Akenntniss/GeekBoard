import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/api_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';
import 'screen_detail_screen.dart';

class ScreenSettingsScreen extends StatefulWidget {
  const ScreenSettingsScreen({super.key});

  @override
  State<ScreenSettingsScreen> createState() => _ScreenSettingsScreenState();
}

class _ScreenSettingsScreenState extends State<ScreenSettingsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _screens = [];

  @override
  void initState() {
    super.initState();
    _loadScreens();
  }

  Future<void> _loadScreens() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<ApiService>();
      final screens = await api.getScreens();
      setState(() {
        _screens = screens;
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }

  Future<void> _createScreen() async {
    final TextEditingController nameCtrl = TextEditingController();
    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Nouvel Écran"),
        content: TextField(
          controller: nameCtrl,
          decoration: const InputDecoration(labelText: "Nom de l'écran (ex: iPad Accueil)"),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text("Annuler")),
          ElevatedButton(
            onPressed: () async {
              if (nameCtrl.text.isEmpty) return;
              Navigator.pop(ctx);
              try {
                await context.read<ApiService>().createScreen(nameCtrl.text);
                _loadScreens();
              } catch (e) {
                if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
              }
            },
            child: const Text("Créer"),
          )
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/settings/screens',
      content: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          title: const Text("Écrans Connectés"),
          backgroundColor: Colors.transparent,
          elevation: 0,
          actions: [
            IconButton(
              icon: const Icon(CupertinoIcons.add),
              tooltip: "Ajouter un écran",
              onPressed: _createScreen,
            ),
            const SizedBox(width: 16),
          ],
        ),
        body: Column(
          children: [
            // Info Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              color: Theme.of(context).cardColor,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline, color: Theme.of(context).primaryColor),
                      const SizedBox(width: 8),
                      Text("À quoi servent les Écrans Connectés ?", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Theme.of(context).textTheme.titleLarge?.color)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    "Transformez n'importe quel écran (TV, Tablette, Moniteur) en affichage dynamique pour votre boutique.",
                    style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, height: 1.5),
                  ),
                  const SizedBox(height: 12),
                  const Text("Pourquoi l'utiliser ?", style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 4),
                  _buildBulletPointWithBadge(context, "Pour vos clients : Affichez la file d'attente des réparations prêtes.", "(fonctionnalité à venir)"),
                  _buildBulletPointWithBadge(context, "Pour votre atelier : Suivez les tâches techniques en temps réel.", "(fonctionnalité à venir)"),
                  _buildBulletPoint(context, "Pour votre vitrine : Diffusez des messages et promotions."),
                  _buildBulletPoint(context, "Lors de la prise en charge : Affichez et collectez les signatures de vos clients lors des prises en charge ou des rachats."),
                ],
              ),
            ),
            const Divider(height: 1),
            
            // Content
            Expanded(
              child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _screens.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(CupertinoIcons.device_laptop, size: 64, color: Colors.grey),
                            const SizedBox(height: 16),
                            const Text("Aucun écran configuré", style: TextStyle(fontSize: 18, color: Colors.grey)),
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              icon: const Icon(Icons.add),
                              label: const Text("Configurer un appareil"),
                              onPressed: _createScreen,
                            )
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(20),
                        itemCount: _screens.length,
                        itemBuilder: (ctx, i) {
                          final s = _screens[i];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              leading: Container(
                                width: 40, height: 40,
                                decoration: BoxDecoration(
                                  color: MacOSTheme.accentBlue.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.tablet_mac, color: MacOSTheme.accentBlue),
                              ),
                              title: Text(s['name'] ?? 'Écran sans nom', style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: Text("Status: ${s['status']} • Vu: ${s['last_seen'] ?? 'Jamais'}"),
                              trailing: const Icon(CupertinoIcons.chevron_right, size: 16),
                              onTap: () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => ScreenDetailScreen(screenId: s['id'], screenName: s['name'])),
                                );
                                _loadScreens();
                              },
                            ),
                          );
                        },
                      ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBulletPoint(BuildContext context, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text("• ", style: TextStyle(fontWeight: FontWeight.bold)),
          Expanded(child: Text(text, style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color?.withOpacity(0.8)))),
        ],
      ),
    );
  }

  Widget _buildBulletPointWithBadge(BuildContext context, String text, String badge) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text("• ", style: TextStyle(fontWeight: FontWeight.bold)),
          Expanded(
            child: Wrap(
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Text(text, style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color?.withOpacity(0.8))),
                const SizedBox(width: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(badge, style: const TextStyle(fontSize: 10, color: Colors.orange, fontWeight: FontWeight.w600)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
