import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';

class SmsPreviewModal extends StatefulWidget {
  final ApiService apiService;
  final String repairId;
  final String templateId;
  final String templateName;
  final String? initialMessage; // New optional parameter

  const SmsPreviewModal({
    Key? key,
    required this.apiService,
    required this.repairId,
    required this.templateId,
    required this.templateName,
    this.initialMessage,
  }) : super(key: key);

  @override
  State<SmsPreviewModal> createState() => _SmsPreviewModalState();
}

class _SmsPreviewModalState extends State<SmsPreviewModal> {
  final TextEditingController _messageController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  bool _isLoading = true;
  bool _isSending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.initialMessage != null) {
         _messageController.text = widget.initialMessage!;
         _isLoading = false;
         // We might still need the phone number if not passed, but let's assume fetching preview also gets phone.
         // Or improved: fetch preview for phone only if needed, or just allow manual entry.
         // Let's do a quick fetch for phone if we have templateId, or just rely on manual input if missing.
         // Better: Fetch phone from repair details if missing? 
         // For now, let's just trigger the fetch but override the message if initialMessage is set.
         _fetchPreview(); 
    } else {
         _fetchPreview();
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _fetchPreview() async {
    try {
      final response = await widget.apiService.post(ApiConfig.smsPreviewEndpoint, {
        'reparation_id': widget.repairId,
        'template_id': widget.templateId,
      });

      if (mounted) {
        setState(() {
          // If we provided an initial message (e.g. from AI), use it. Otherwise use the template preview.
          if (widget.initialMessage != null) {
              _messageController.text = widget.initialMessage!;
          } else {
              _messageController.text = response['preview_message'] ?? '';
          }
          
          _phoneController.text = response['client_phone'] ?? '';
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = "Impossible de générer l'aperçu: $e";
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _sendSms() async {
    if (_messageController.text.trim().isEmpty) return;
    if (_phoneController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Le numéro de téléphone est requis"), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSending = true);

    try {
      await widget.apiService.post(ApiConfig.smsSendEndpoint, {
        'reparation_id': widget.repairId,
        'message': _messageController.text,
        'telephone': _phoneController.text,
      });

      if (mounted) {
        Navigator.pop(context, true); // Return true to indicate success
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text("SMS envoyé avec succès"),
            backgroundColor: MacOSTheme.successGreen,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSending = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Erreur lors de l'envoi: $e"), backgroundColor: Colors.red),
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
        height: 700,
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
                  const Icon(Icons.send_outlined, color: Colors.blue),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        "Envoyer un SMS",
                        style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        "Modèle : ${widget.templateName}",
                        style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 12),
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
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
                      : Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Phone Number Field
                              const Text("Numéro du client", style: TextStyle(color: Colors.white70)),
                              const SizedBox(height: 8),
                              TextField(
                                controller: _phoneController,
                                style: const TextStyle(color: Colors.white),
                                decoration: InputDecoration(
                                  filled: true,
                                  fillColor: Colors.white.withOpacity(0.05),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  prefixIcon: const Icon(Icons.phone, color: Colors.white54),
                                ),
                              ),
                              const SizedBox(height: 20),
                              
                              // Message Preview Field
                              const Text("Message (aperçu éditable)", style: TextStyle(color: Colors.white70)),
                              const SizedBox(height: 8),
                              Expanded(
                                child: TextField(
                                  controller: _messageController,
                                  style: const TextStyle(color: Colors.white),
                                  maxLines: null,
                                  expands: true,
                                  textAlignVertical: TextAlignVertical.top,
                                  decoration: InputDecoration(
                                    filled: true,
                                    fillColor: Colors.white.withOpacity(0.05),
                                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
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
                    onPressed: (_isLoading || _isSending) ? null : _sendSms,
                    icon: _isSending 
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.send),
                    label: Text(_isSending ? "Envoi..." : "Envoyer le SMS"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
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
