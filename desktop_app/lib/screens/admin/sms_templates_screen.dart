/// SMS Templates Screen (Admin)
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

import '../../screens/sms/dialogs/sms_template_dialog.dart';

class SmsTemplatesScreen extends StatefulWidget {
  const SmsTemplatesScreen({super.key});
  @override
  State<SmsTemplatesScreen> createState() => _SmsTemplatesScreenState();
}

class _SmsTemplatesScreenState extends State<SmsTemplatesScreen> {
  List<Map<String, dynamic>> _templates = [];
  List<dynamic> _statuts = [];
  bool _isLoading = true;

  @override
  void initState() { super.initState(); _loadData(); }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final response = await api.get('/admin/sms_templates.php');
      setState(() { 
        _templates = List<Map<String, dynamic>>.from(response['templates'] ?? []); 
        _statuts = response['statuts'] ?? [];
        _isLoading = false; 
      });
    } catch (e) { setState(() => _isLoading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return AppShell(
      currentRoute: '/admin/sms_templates', 
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start, 
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Templates SMS', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Text(
                  'Visualiser et modifier les SMS qui sont envoyés automatiquement à vos clients.', 
                  style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontSize: 14)
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading 
              ? const Center(child: CupertinoActivityIndicator()) 
              : _templates.isEmpty 
                ? const Center(child: Text('Aucun template')) 
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 24), 
                    itemCount: _templates.length, 
                    itemBuilder: (context, i) { 
                      final t = _templates[i]; 
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12), 
                        child: GestureDetector(
                          onTap: () => showDialog(
                            context: context,
                            builder: (context) => SmsTemplateDialog(
                              template: t,
                              statuts: _statuts,
                              onSuccess: () => _loadData(),
                            ),
                          ),
                          child: MouseRegion(
                            cursor: SystemMouseCursors.click,
                            child: MacOSCard(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start, 
                                children: [
                                  Row(
                                    children: [
                                      Icon(CupertinoIcons.doc_text_fill, color: MacOSTheme.accentBlue, size: 20), 
                                      const SizedBox(width: 8), 
                                      Expanded(child: Text(t['nom'] ?? 'Template', style: const TextStyle(fontWeight: FontWeight.w600))),
                                      const Icon(CupertinoIcons.pencil, size: 16, color: Colors.grey),
                                    ]
                                  ), 
                                  const SizedBox(height: 8), 
                                  Container(
                                    padding: const EdgeInsets.all(12), 
                                    decoration: BoxDecoration(
                                      color: isDark ? const Color(0xFF1E293B) : Colors.grey[100], 
                                      borderRadius: BorderRadius.circular(8)
                                    ), 
                                    child: Text(
                                      t['contenu'] ?? '', 
                                      style: TextStyle(fontSize: 12, color: isDark ? Colors.grey[300] : Colors.grey[700])
                                    )
                                  )
                                ]
                              )
                            ),
                          ),
                        ),
                      ); 
                    }
                  ),
          ),
        ]
      )
    );
  }
}
