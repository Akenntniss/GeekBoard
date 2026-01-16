import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';

class AssignFormationDialog extends StatefulWidget {
  final List<dynamic> users;
  final Map<String, dynamic> formationsConfig;
  final VoidCallback onSuccess;

  const AssignFormationDialog({
    super.key, 
    required this.users, 
    required this.formationsConfig, 
    required this.onSuccess
  });

  @override
  State<AssignFormationDialog> createState() => _AssignFormationDialogState();
}

class _AssignFormationDialogState extends State<AssignFormationDialog> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  
  int? _selectedUserId;
  int? _selectedFormationId;
  String _priority = 'normal';
  DateTime? _dueDate;
  final TextEditingController _messageController = TextEditingController();

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedUserId == null || _selectedFormationId == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un employé et une formation'), backgroundColor: Colors.red));
      return;
    }

    setState(() => _isLoading = true);

    try {
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.formationAdminAssignEndpoint}');
      
      final body = {
        'user_id': _selectedUserId,
        'formation_id': _selectedFormationId,
        'priority': _priority,
        'due_date': _dueDate?.toIso8601String(),
        'message': _messageController.text,
      };

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
            SnackBar(content: Text(data['message'] ?? 'Formation assignée'), backgroundColor: Colors.green),
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
    // Filter available formations
    final availableFormations = widget.formationsConfig.entries
        .where((e) => e.value['disponible'] == true)
        .toList();

    return Dialog(
      backgroundColor: const Color(0xFF1a1a2e),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 500,
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Assigner une formation',
                    style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              
              _buildDropdown(
                label: 'Employé',
                value: _selectedUserId,
                items: widget.users.map((u) => DropdownMenuItem<int>(
                  value: int.tryParse(u['id'].toString()),
                  child: Text('${u['full_name']} (${u['role']})', style: const TextStyle(color: Colors.white)),
                )).toList(),
                onChanged: (v) => setState(() => _selectedUserId = v),
              ),
              
              const SizedBox(height: 16),
              
              _buildDropdown(
                label: 'Formation',
                value: _selectedFormationId,
                items: availableFormations.map((e) => DropdownMenuItem<int>(
                  value: int.parse(e.key.toString()),
                  child: Text('F${e.key}: ${e.value['titre']}', style: const TextStyle(color: Colors.white)),
                )).toList(),
                onChanged: (v) => setState(() => _selectedFormationId = v),
              ),

              const SizedBox(height: 16),

              Row(
                children: [
                  Expanded(
                    child: _buildDropdown(
                      label: 'Priorité',
                      value: _priority,
                      items: const [
                        DropdownMenuItem(value: 'normal', child: Text('Normale', style: TextStyle(color: Colors.white))),
                        DropdownMenuItem(value: 'important', child: Text('Importante', style: TextStyle(color: Colors.orange))),
                        DropdownMenuItem(value: 'urgent', child: Text('Urgente', style: TextStyle(color: Colors.red))),
                      ],
                      onChanged: (v) => setState(() => _priority = v.toString()),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Date limite', style: TextStyle(color: Colors.grey, fontSize: 13)),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () async {
                            final date = await showDatePicker(
                              context: context,
                              initialDate: DateTime.now().add(const Duration(days: 7)),
                              firstDate: DateTime.now(),
                              lastDate: DateTime.now().add(const Duration(days: 365)),
                            );
                            if (date != null) setState(() => _dueDate = date);
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.05),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  _dueDate != null ? '${_dueDate!.day}/${_dueDate!.month}/${_dueDate!.year}' : 'Aucune',
                                  style: const TextStyle(color: Colors.white),
                                ),
                                const Icon(Icons.calendar_today, color: Colors.white, size: 16),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 16),

              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Message (optionnel)', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _messageController,
                    style: const TextStyle(color: Colors.white),
                    maxLines: 3,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: Colors.white.withOpacity(0.05),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                      hintText: 'Message personnalisé pour l\'employé...',
                      hintStyle: TextStyle(color: Colors.white.withOpacity(0.3)),
                    ),
                  ),
                ],
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
                  ElevatedButton.icon(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                    ),
                    icon: _isLoading 
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.send, color: Colors.white, size: 18),
                    label: Text(_isLoading ? 'Envoi...' : 'Assigner', style: const TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDropdown<T>({
    required String label,
    required T value,
    required List<DropdownMenuItem<T>> items,
    required ValueChanged<T?> onChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.05),
            borderRadius: BorderRadius.circular(8),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<T>(
              value: value,
              dropdownColor: const Color(0xFF1a1a2e),
              isExpanded: true,
              icon: const Icon(Icons.arrow_drop_down, color: Colors.white),
              items: items,
              onChanged: onChanged,
            ),
          ),
        ),
      ],
    );
  }
}
