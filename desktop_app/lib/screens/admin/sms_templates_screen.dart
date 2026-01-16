/// SMS Templates Screen (Admin)
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class SmsTemplatesScreen extends StatefulWidget {
  const SmsTemplatesScreen({super.key});
  @override
  State<SmsTemplatesScreen> createState() => _SmsTemplatesScreenState();
}

class _SmsTemplatesScreenState extends State<SmsTemplatesScreen> {
  List<Map<String, dynamic>> _templates = [];
  bool _isLoading = true;

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final response = await api.get('/admin/sms_templates.php');
      setState(() { _templates = List<Map<String, dynamic>>.from(response['templates'] ?? []); _isLoading = false; });
    } catch (e) { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(currentRoute: '/admin/sms_templates', content: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Padding(padding: EdgeInsets.all(24), child: Text('Templates SMS', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold))),
      Expanded(child: _isLoading ? const Center(child: CupertinoActivityIndicator()) : _templates.isEmpty ? Center(child: Text('Aucun template')) : ListView.builder(padding: const EdgeInsets.symmetric(horizontal: 24), itemCount: _templates.length, itemBuilder: (context, i) { final t = _templates[i]; return Padding(padding: const EdgeInsets.only(bottom: 12), child: MacOSCard(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Row(children: [Icon(CupertinoIcons.doc_text_fill, color: MacOSTheme.accentBlue, size: 20), const SizedBox(width: 8), Expanded(child: Text(t['nom'] ?? 'Template', style: TextStyle(fontWeight: FontWeight.w600)))]), const SizedBox(height: 8), Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(8)), child: Text(t['contenu'] ?? '', style: TextStyle(fontSize: 12, color: Colors.grey[700])))]))); })),
    ]));
  }
}
