import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';
import 'sms_preview_modal.dart';

class SmsAiInputModal extends StatefulWidget {
  final ApiService apiService;
  final String repairId;
  final Function(Map<String, dynamic> template)? onTemplateSelected; // Optional, used if we switch to template
  
  const SmsAiInputModal({
    Key? key, 
    required this.apiService, 
    required this.repairId,
    this.onTemplateSelected,
  }) : super(key: key);

  @override
  State<SmsAiInputModal> createState() => _SmsAiInputModalState();
}

class _SmsAiInputModalState extends State<SmsAiInputModal> {
  final TextEditingController _inputController = TextEditingController();
  bool _isGenerating = false;

  Future<void> _generateAndPreview() async {
    if (_inputController.text.trim().isEmpty) return;

    setState(() => _isGenerating = true);

    try {
      final response = await widget.apiService.post(ApiConfig.smsAiGenerateEndpoint, {
        'input_text': _inputController.text,
        'repair_id': widget.repairId
      });

      if (mounted) {
        setState(() => _isGenerating = false);
        
        // Close this modal and open preview with generated text
        Navigator.pop(context); // Close input modal
        
        // Open Preview Modal (Using a "fake" template ID for AI generated)
        showDialog(
          context: context,
          builder: (ctx) => SmsPreviewModal(
            apiService: widget.apiService,
            repairId: widget.repairId,
            templateId: 'ai_generated', 
            templateName: 'Assistant IA',
            initialMessage: response['generated_message'], // Need to support this in SmsPreviewModal
          )
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isGenerating = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Erreur IA: $e"), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(24),
      child: Container(
        width: 600,
        height: 500,
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
                   Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.purple.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.auto_awesome, color: Colors.purpleAccent),
                  ),
                  const SizedBox(width: 12),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Assistant IA",
                        style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        "Décrivez le message, l'IA le rédige pour vous",
                        style: TextStyle(color: Colors.white54, fontSize: 12),
                      ),
                    ],
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
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Message brut du technicien :",
                      style: TextStyle(color: Colors.white70, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 8),
                    Expanded(
                      child: TextField(
                        controller: _inputController,
                        style: const TextStyle(color: Colors.white),
                        maxLines: null,
                        expands: true,
                        textAlignVertical: TextAlignVertical.top,
                        decoration: InputDecoration(
                          hintText: "Exemple: Bonjour iphone ecran cassé 80 euro...",
                          hintStyle: TextStyle(color: Colors.white.withOpacity(0.3)),
                          filled: true,
                          fillColor: Colors.white.withOpacity(0.05),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                          contentPadding: const EdgeInsets.all(16),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Footer
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text("Annuler", style: TextStyle(color: Colors.grey)),
                  ),
                  const SizedBox(width: 16),
                  ElevatedButton.icon(
                    onPressed: _isGenerating ? null : _generateAndPreview,
                    icon: _isGenerating 
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.auto_fix_high),
                    label: Text(_isGenerating ? "Rédaction en cours..." : "Générer le SMS"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.purple,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
