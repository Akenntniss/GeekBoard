import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
import 'package:geekboard_desktop/screens/sms/dialogs/sms_template_dialog.dart';

class SmsTemplatesScreen extends StatefulWidget {
  const SmsTemplatesScreen({super.key});

  @override
  State<SmsTemplatesScreen> createState() => _SmsTemplatesScreenState();
}

class _SmsTemplatesScreenState extends State<SmsTemplatesScreen> {
  bool _isLoading = true;
  List<dynamic> _templates = [];
  List<dynamic> _statuts = [];

  @override
  void initState() {
    super.initState();
    _fetchTemplates();
  }

  Future<void> _fetchTemplates() async {
    setState(() => _isLoading = true);
    try {
      final response = await http.get(Uri.parse('${ApiConfig.baseUrl}${ApiConfig.smsTemplatesListEndpoint}'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _templates = data['data']['templates'];
            _statuts = data['data']['statuts'];
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleActive(int id, bool isActive) async {
    // Optimistic UI update
    final index = _templates.indexWhere((t) => t['id'] == id);
    if (index != -1) {
      setState(() {
        _templates[index]['est_actif'] = isActive ? 1 : 0;
      });
    }

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.smsTemplatesToggleEndpoint}'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id': id, 'est_actif': isActive ? 1 : 0}),
      );
      
      final data = json.decode(response.body);
      if (!data['success']) {
         // Revert on error
         _fetchTemplates();
         if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Erreur'), backgroundColor: Colors.red));
      }
    } catch (e) {
       _fetchTemplates();
    }
  }

  Future<void> _deleteTemplate(dynamic template) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1a1a2e),
        title: const Text('Confirmation', style: TextStyle(color: Colors.red)),
        content: Text('Supprimer le modèle "${template['nom']}" ?', style: const TextStyle(color: Colors.white70)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}${ApiConfig.smsTemplatesDeleteEndpoint}'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id': template['id']}),
      );

      final data = json.decode(response.body);
      if (mounted) {
        if (response.statusCode == 200 && data['success']) {
           ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Modèle supprimé'), backgroundColor: Colors.green));
           _fetchTemplates();
        } else {
           ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Erreur'), backgroundColor: Colors.red));
        }
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: Row(
        children: [
          const Sidebar(currentRoute: '/sms_templates'),
          Expanded(
            child: Column(
              children: [
                // Header
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: const BoxDecoration(
                    color: Color(0xFF1E293B),
                    border: Border(bottom: BorderSide(color: Color(0xFF334155))),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Modèles de SMS', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 4),
                          Text('Automatisez vos communications', style: TextStyle(color: Colors.white.withOpacity(0.7))),
                        ],
                      ),
                      ElevatedButton.icon(
                        onPressed: () => showDialog(
                          context: context, 
                          builder: (_) => SmsTemplateDialog(
                            statuts: _statuts,
                            onSuccess: () => _fetchTemplates()
                          )
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.add, color: Colors.white),
                        label: const Text('Nouveau Modèle', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                ),

                // Grid
                Expanded(
                  child: _isLoading 
                      ? const Center(child: CircularProgressIndicator())
                      : Padding(
                          padding: const EdgeInsets.all(24),
                          child: GridView.builder(
                            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                              maxCrossAxisExtent: 400,
                              childAspectRatio: 1.5,
                              crossAxisSpacing: 20,
                              mainAxisSpacing: 20,
                            ),
                            itemCount: _templates.length,
                            itemBuilder: (context, index) {
                              final t = _templates[index];
                              final isActive = t['est_actif'] == 1;
                              
                              return Container(
                                decoration: BoxDecoration(
                                  color: const Color(0xFF1E293B),
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: isActive ? Colors.blue.withOpacity(0.3) : Colors.transparent, 
                                    width: 1
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.1),
                                      blurRadius: 10,
                                      offset: const Offset(0, 4),
                                    )
                                  ],
                                ),
                                clipBehavior: Clip.antiAlias,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Card Header
                                    Container(
                                      padding: const EdgeInsets.all(16),
                                      color: isActive ? Colors.blue.withOpacity(0.1) : Colors.black.withOpacity(0.2),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              t['nom'], 
                                              style: TextStyle(
                                                color: Colors.white, 
                                                fontSize: 16, 
                                                fontWeight: FontWeight.bold,
                                                decoration: isActive ? null : TextDecoration.lineThrough,
                                              ),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          Switch(
                                            value: isActive,
                                            activeColor: Colors.blue,
                                            onChanged: (v) => _toggleActive(t['id'], v),
                                          ),
                                        ],
                                      ),
                                    ),
                                    
                                    // Status Badge
                                    Padding(
                                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: (t['statut_nom'] != null ? Colors.purple : Colors.grey).withOpacity(0.2),
                                          borderRadius: BorderRadius.circular(20),
                                          border: Border.all(color: (t['statut_nom'] != null ? Colors.purple : Colors.grey).withOpacity(0.5)),
                                        ),
                                        child: Text(
                                          t['statut_nom'] ?? 'Déclenchement Manuel',
                                          style: TextStyle(
                                            color: t['statut_nom'] != null ? Colors.purple[200] : Colors.grey[400], 
                                            fontSize: 12, 
                                            fontWeight: FontWeight.bold
                                          ),
                                        ),
                                      ),
                                    ),

                                    // Content Preview
                                    Expanded(
                                      child: Padding(
                                        padding: const EdgeInsets.all(16),
                                        child: Text(
                                          t['contenu'],
                                          style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 13, height: 1.4),
                                          maxLines: 4,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ),

                                    // Actions
                                    Padding(
                                      padding: const EdgeInsets.all(8),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.end,
                                        children: [
                                          IconButton(
                                            icon: const Icon(Icons.edit, color: Colors.blue),
                                            tooltip: 'Modifier',
                                            onPressed: () => showDialog(
                                              context: context, 
                                              builder: (_) => SmsTemplateDialog(
                                                template: t,
                                                statuts: _statuts,
                                                onSuccess: () => _fetchTemplates()
                                              )
                                            ),
                                          ),
                                          IconButton(
                                            icon: const Icon(Icons.delete, color: Colors.red),
                                            tooltip: 'Supprimer',
                                            onPressed: () => _deleteTemplate(t),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
