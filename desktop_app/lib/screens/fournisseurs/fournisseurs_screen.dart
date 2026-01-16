/// Fournisseurs Screen
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class FournisseursScreen extends StatefulWidget {
  const FournisseursScreen({super.key});
  @override
  State<FournisseursScreen> createState() => _FournisseursScreenState();
}

class _FournisseursScreenState extends State<FournisseursScreen> {
  List<Map<String, dynamic>> _fournisseurs = [];
  bool _isLoading = true;

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final response = await api.get('/fournisseurs/list.php');
      setState(() { _fournisseurs = List<Map<String, dynamic>>.from(response['fournisseurs'] ?? []); _isLoading = false; });
    } catch (e) { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/fournisseurs',
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(padding: EdgeInsets.all(24), child: Text('Fournisseurs', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold))),
          Expanded(
            child: _isLoading ? const Center(child: CupertinoActivityIndicator())
                : _fournisseurs.isEmpty ? Center(child: Text('Aucun fournisseur'))
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    itemCount: _fournisseurs.length,
                    itemBuilder: (context, i) {
                      final f = _fournisseurs[i];
                      return Padding(padding: const EdgeInsets.only(bottom: 12), child: MacOSCard(child: Row(children: [Container(width: 50, height: 50, decoration: BoxDecoration(color: MacOSTheme.accentPurple.withOpacity(0.1), borderRadius: BorderRadius.circular(25)), child: Center(child: Icon(CupertinoIcons.building_2_fill, color: MacOSTheme.accentPurple))), const SizedBox(width: 16), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(f['nom'] ?? '', style: TextStyle(fontWeight: FontWeight.w600)), if (f['email'] != null) Text(f['email'], style: TextStyle(color: Colors.grey[600], fontSize: 12))])), if (f['telephone'] != null) Text(f['telephone'], style: TextStyle(color: Colors.grey[500]))])));
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
