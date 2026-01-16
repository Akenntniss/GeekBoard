/// Pointage Screen (Admin)
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class PointageScreen extends StatefulWidget {
  const PointageScreen({super.key});
  @override
  State<PointageScreen> createState() => _PointageScreenState();
}

class _PointageScreenState extends State<PointageScreen> {
  List<Map<String, dynamic>> _pointages = [];
  bool _isLoading = true;
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final date = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final response = await api.get('/admin/pointage.php?date=$date');
      setState(() { _pointages = List<Map<String, dynamic>>.from(response['pointages'] ?? []); _isLoading = false; });
    } catch (e) { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final dateStr = DateFormat('dd/MM/yyyy').format(_selectedDate);
    return AppShell(currentRoute: '/admin/pointage', content: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Padding(padding: const EdgeInsets.all(24), child: Row(children: [const Text('Pointage Admin', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)), const Spacer(), CupertinoButton(padding: EdgeInsets.zero, child: Icon(CupertinoIcons.chevron_left), onPressed: () { setState(() => _selectedDate = _selectedDate.subtract(Duration(days: 1))); _loadData(); }), Text(dateStr, style: TextStyle(fontWeight: FontWeight.w600)), CupertinoButton(padding: EdgeInsets.zero, child: Icon(CupertinoIcons.chevron_right), onPressed: () { setState(() => _selectedDate = _selectedDate.add(Duration(days: 1))); _loadData(); })])),
      Expanded(child: _isLoading ? const Center(child: CupertinoActivityIndicator()) : _pointages.isEmpty ? Center(child: Text('Aucun pointage ce jour')) : ListView.builder(padding: const EdgeInsets.symmetric(horizontal: 24), itemCount: _pointages.length, itemBuilder: (context, i) { final p = _pointages[i]; return Padding(padding: const EdgeInsets.only(bottom: 12), child: MacOSCard(child: Row(children: [Icon(CupertinoIcons.clock_fill, color: MacOSTheme.accentBlue), const SizedBox(width: 16), Expanded(child: Text(p['employe_nom'] ?? '', style: TextStyle(fontWeight: FontWeight.w600))), Text('${p['heure_arrivee'] ?? '-'}', style: TextStyle(color: MacOSTheme.successGreen, fontWeight: FontWeight.w600)), const SizedBox(width: 16), Text('${p['heure_depart'] ?? '-'}', style: TextStyle(color: MacOSTheme.dangerRed))]))); })),
    ]));
  }
}
