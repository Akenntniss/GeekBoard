/// Employés Screen (Admin)
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class EmployesScreen extends StatefulWidget {
  const EmployesScreen({super.key});
  @override
  State<EmployesScreen> createState() => _EmployesScreenState();
}

class _EmployesScreenState extends State<EmployesScreen> {
  List<Map<String, dynamic>> _employes = [];
  bool _isLoading = true;

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final response = await api.get('/admin/employes.php');
      setState(() { _employes = List<Map<String, dynamic>>.from(response['employes'] ?? []); _isLoading = false; });
    } catch (e) { setState(() => _isLoading = false); }
  }

  String _getInitials(String name) { if (name.isEmpty) return '?'; final p = name.split(' '); if (p.length >= 2) return '${p[0][0]}${p[1][0]}'.toUpperCase(); return name[0].toUpperCase(); }

  @override
  Widget build(BuildContext context) {
    return AppShell(currentRoute: '/admin/employes', content: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Padding(padding: EdgeInsets.all(24), child: Text('Employés', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold))),
      Expanded(child: _isLoading ? const Center(child: CupertinoActivityIndicator()) : ListView.builder(padding: const EdgeInsets.symmetric(horizontal: 24), itemCount: _employes.length, itemBuilder: (context, i) { final e = _employes[i]; final isOnline = e['is_online'] == 1 || e['is_online'] == true; return Padding(padding: const EdgeInsets.only(bottom: 12), child: MacOSCard(child: Row(children: [Stack(children: [Container(width: 50, height: 50, decoration: BoxDecoration(color: MacOSTheme.accentPurple.withOpacity(0.1), borderRadius: BorderRadius.circular(25)), child: Center(child: Text(_getInitials(e['full_name'] ?? ''), style: TextStyle(color: MacOSTheme.accentPurple, fontWeight: FontWeight.bold)))), Positioned(bottom: 0, right: 0, child: Container(width: 14, height: 14, decoration: BoxDecoration(color: isOnline ? MacOSTheme.successGreen : Colors.grey, borderRadius: BorderRadius.circular(7), border: Border.all(color: Colors.white, width: 2))))]), const SizedBox(width: 16), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(e['full_name'] ?? '', style: TextStyle(fontWeight: FontWeight.w600)), Text(e['email'] ?? '', style: TextStyle(color: Colors.grey[600], fontSize: 12))])), Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), decoration: BoxDecoration(color: MacOSTheme.accentBlue.withOpacity(0.1), borderRadius: BorderRadius.circular(4)), child: Text(e['role'] ?? '', style: TextStyle(color: MacOSTheme.accentBlue, fontSize: 11, fontWeight: FontWeight.w600)))]))); })),
    ]));
  }
}
