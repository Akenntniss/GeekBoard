import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';
import 'sms_ai_input_modal.dart';

class SmsTemplateSelectionModal extends StatefulWidget {
  final ApiService apiService;
  final Function(Map<String, dynamic> template) onTemplateSelected;
  final String repairId;

  const SmsTemplateSelectionModal({
    Key? key,
    required this.apiService,
    required this.onTemplateSelected,
    required this.repairId,
  }) : super(key: key);

  @override
  State<SmsTemplateSelectionModal> createState() => _SmsTemplateSelectionModalState();
}

class _SmsTemplateSelectionModalState extends State<SmsTemplateSelectionModal> {
  List<dynamic> _templates = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchTemplates();
  }

  Future<void> _fetchTemplates() async {
    try {
      final response = await widget.apiService.get(ApiConfig.smsTemplatesEndpoint);
      if (mounted) {
        setState(() {
          _templates = response['templates'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = "Impossible de charger les modèles: $e";
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(24),
      child: Container(
        width: 800,
        height: 600,
        decoration: BoxDecoration(
          color: const Color(0xFF1C1C1E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.1)),
        ),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.sms_outlined, color: Colors.blue),
                  const SizedBox(width: 12),
                  const Text(
                    "Choisir un modèle de SMS",
                    style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(width: 24),
                  ElevatedButton.icon(
                    onPressed: () {
                         Navigator.pop(context); // Close template modal
                         showDialog(
                             context: context, 
                             builder: (ctx) => SmsAiInputModal(
                                 apiService: widget.apiService, 
                                 repairId: widget.repairId,
                             )
                         );
                    },
                    icon: const Icon(Icons.auto_awesome, size: 16),
                    label: const Text("Assistant IA"),
                    style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.purple,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            
            // Content
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
                      : _templates.isEmpty
                          ? const Center(child: Text("Aucun modèle disponible", style: TextStyle(color: Colors.white70)))
                          : GridView.builder(
                              padding: const EdgeInsets.all(20),
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 3,
                                childAspectRatio: 1.5,
                                crossAxisSpacing: 16,
                                mainAxisSpacing: 16,
                              ),
                              itemCount: _templates.length,
                              itemBuilder: (context, index) {
                                final template = _templates[index];
                                return InkWell(
                                  onTap: () {
                                    widget.onTemplateSelected(template);
                                    Navigator.pop(context);
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withOpacity(0.05),
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(color: Colors.white.withOpacity(0.1)),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          template['titre'] ?? 'Sans titre',
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        const SizedBox(height: 8),
                                        Expanded(
                                          child: Text(
                                            template['contenu'] ?? '',
                                            style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 12),
                                            maxLines: 4,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Align(
                                          alignment: Alignment.centerRight,
                                          child: Icon(Icons.arrow_forward, size: 16, color: Colors.white.withOpacity(0.5)),
                                        )
                                      ],
                                    ),
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
}
