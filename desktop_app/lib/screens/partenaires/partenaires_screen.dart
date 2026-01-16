/// Partenaires Screen
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class PartenairesScreen extends StatefulWidget {
  const PartenairesScreen({super.key});
  @override
  State<PartenairesScreen> createState() => _PartenairesScreenState();
}

class _PartenairesScreenState extends State<PartenairesScreen> {
  List<Map<String, dynamic>> _partenaires = [];
  bool _isLoading = true;

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final response = await api.get('/partenaires/list.php');
      setState(() { _partenaires = List<Map<String, dynamic>>.from(response['partenaires'] ?? []); _isLoading = false; });
    } catch (e) { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/partenaires',
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(padding: EdgeInsets.all(24), child: Text('Partenaires', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold))),
          Expanded(
            child: _isLoading ? const Center(child: CupertinoActivityIndicator())
                : _partenaires.isEmpty ? Center(child: Text('Aucun partenaire'))
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    itemCount: _partenaires.length,
                    itemBuilder: (context, i) {
                      final p = _partenaires[i];
                      return Padding(padding: const EdgeInsets.only(bottom: 12), child: MacOSCard(child: Row(children: [Container(width: 50, height: 50, decoration: BoxDecoration(color: MacOSTheme.successGreen.withOpacity(0.1), borderRadius: BorderRadius.circular(25)), child: Center(child: Icon(CupertinoIcons.person_2_fill, color: MacOSTheme.successGreen))), const SizedBox(width: 16), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(p['nom'] ?? '', style: TextStyle(fontWeight: FontWeight.w600)), if (p['email'] != null) Text(p['email'], style: TextStyle(color: Colors.grey[600], fontSize: 12))])), if (p['telephone'] != null) Text(p['telephone'], style: TextStyle(color: Colors.grey[500]))])));
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
