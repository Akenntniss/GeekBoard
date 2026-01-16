import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';

class SmsTemplateDialog extends StatefulWidget {
  final Map<String, dynamic>? template;
  final List<dynamic> statuts;
  final VoidCallback onSuccess;

  const SmsTemplateDialog({
    super.key, 
    this.template, 
    required this.statuts, 
    required this.onSuccess
  });

  @override
  State<SmsTemplateDialog> createState() => _SmsTemplateDialogState();
}

class _SmsTemplateDialogState extends State<SmsTemplateDialog> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  
  late TextEditingController _nomController;
  late TextEditingController _contenuController;
  int? _selectedStatutId;
  bool _estActif = true;
  List<dynamic> _variables = [];

  @override
  void initState() {
    super.initState();
    _nomController = TextEditingController(text: widget.template?['nom'] ?? '');
    _contenuController = TextEditingController(text: widget.template?['contenu'] ?? '');
    _selectedStatutId = widget.template?['statut_id'];
    _estActif = widget.template?['est_actif'] == 1 || widget.template == null;
    _fetchVariables();
  }

  Future<void> _fetchVariables() async {
    try {
      final response = await http.get(Uri.parse('${ApiConfig.baseUrl}${ApiConfig.smsTemplatesVariablesEndpoint}'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _variables = data['data'];
          });
        }
      }
    } catch (e) {
      // Ignore errors for variables
    }
  }

  @override
  void dispose() {
    _nomController.dispose();
    _contenuController.dispose();
    super.dispose();
  }

  void _insertVariable(String code) {
    final text = _contenuController.text;
    final selection = _contenuController.selection;
    
    String newText;
    int newSelectionIndex;

    if (selection.start >= 0) {
      newText = text.replaceRange(selection.start, selection.end, code);
      newSelectionIndex = selection.start + code.length;
    } else {
      newText = text + code;
      newSelectionIndex = newText.length;
    }

    _contenuController.value = TextEditingValue(
      text: newText,
      selection: TextSelection.collapsed(offset: newSelectionIndex),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final isEdit = widget.template != null;
      final endpoint = isEdit ? ApiConfig.smsTemplatesUpdateEndpoint : ApiConfig.smsTemplatesCreateEndpoint;
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      
      final body = {
        'nom': _nomController.text,
        'contenu': _contenuController.text,
        'statut_id': _selectedStatutId,
        'est_actif': _estActif ? 1 : 0,
      };

      if (isEdit) {
        body['id'] = widget.template!['id'];
      }

      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode(body),
      );

      final data = json.decode(response.body);
      
      if (response.statusCode == 200 && data['success']) {
        if (mounted) {
          Navigator.pop(context);
          widget.onSuccess();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isEdit ? 'Modèle mis à jour' : 'Modèle créé'), 
              backgroundColor: Colors.green
            ),
          );
        }
      } else {
        throw Exception(data['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.template != null;
    return Dialog(
      backgroundColor: const Color(0xFF1a1a2e),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 700,
        height: 600,
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                   Text(
                    isEdit ? 'Modifier le modèle' : 'Nouveau modèle',
                    style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              
              Row(
                children: [
                  Expanded(child: _buildTextField('Nom du modèle *', _nomController, required: true)),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Statut associé', style: TextStyle(color: Colors.grey, fontSize: 13)),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.05),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<int>(
                              value: _selectedStatutId,
                              dropdownColor: const Color(0xFF1a1a2e),
                              isExpanded: true,
                              hint: const Text('Aucun statut', style: TextStyle(color: Colors.grey)),
                              icon: const Icon(Icons.arrow_drop_down, color: Colors.white),
                              items: [
                                const DropdownMenuItem<int>(
                                  value: null,
                                  child: Text('Aucun (Manuel)', style: TextStyle(color: Colors.white)),
                                ),
                                ...widget.statuts.map((s) => DropdownMenuItem<int>(
                                  value: s['id'],
                                  child: Text(s['nom'], style: const TextStyle(color: Colors.white)),
                                )),
                              ],
                              onChanged: (v) => setState(() => _selectedStatutId = v),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              
              const SizedBox(height: 16),
              
              Row(
                children: [
                  Checkbox(
                    value: _estActif, 
                    onChanged: (v) => setState(() => _estActif = v ?? false),
                    fillColor: MaterialStateProperty.all(Colors.blue),
                  ),
                  const Text('Modèle Actif', style: TextStyle(color: Colors.white)),
                ],
              ),

              const SizedBox(height: 16),
              
              // Variables Chips
              if (_variables.isNotEmpty)
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _variables.map((v) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ActionChip(
                        label: Text(v['code'], style: const TextStyle(color: Colors.white, fontSize: 11)),
                        backgroundColor: Colors.blue.withOpacity(0.2),
                        onPressed: () => _insertVariable(v['code']),
                      ),
                    )).toList(),
                  ),
                ),
              
              const SizedBox(height: 12),

              Expanded(
                child: _buildTextField('Contenu du SMS *', _contenuController, required: true, maxLines: 10),
              ),

              const SizedBox(height: 24),
              
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Annuler', style: TextStyle(color: Colors.grey)),
                  ),
                  const SizedBox(width: 16),
                  ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                    ),
                    child: _isLoading 
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text(isEdit ? 'Enregistrer' : 'Créer', style: const TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, {bool required = false, int maxLines = 1}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          style: const TextStyle(color: Colors.white),
          maxLines: maxLines,
          validator: required ? (v) => v?.isEmpty ?? true ? 'Requis' : null : null,
          decoration: InputDecoration(
            filled: true,
            fillColor: Colors.white.withOpacity(0.05),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            contentPadding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }
}
