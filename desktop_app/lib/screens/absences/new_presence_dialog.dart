import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';

class NewPresenceDialog extends StatefulWidget {
  final List<dynamic> types;
  final VoidCallback onSuccess;

  const NewPresenceDialog({
    super.key, 
    required this.types,
    required this.onSuccess
  });

  @override
  State<NewPresenceDialog> createState() => _NewPresenceDialogState();
}

class _NewPresenceDialogState extends State<NewPresenceDialog> {
  final _formKey = GlobalKey<FormState>();
  
  // State
  String? _selectedTypeId;
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  final TextEditingController _durationController = TextEditingController(text: '30'); // Minutes for late
  final TextEditingController _commentController = TextEditingController();
  
  bool _isLoading = false;

  // Helpers
  bool get _isRetard {
    if (_selectedTypeId == null) return false;
    final type = widget.types.firstWhere((t) => t['id'].toString() == _selectedTypeId, orElse: () => null);
    if (type == null) return false;
    return type['name'].toString().toLowerCase().contains('retard');
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: bgColor,
      child: Container(
        width: 500,
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                "Nouvelle Demande",
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: textColor),
              ),
              const SizedBox(height: 24),
              
              // Type Selection
              DropdownButtonFormField<String>(
                value: _selectedTypeId,
                decoration: _inputDecoration("Type d'événement", isDark),
                dropdownColor: bgColor,
                style: TextStyle(color: textColor),
                items: widget.types.map<DropdownMenuItem<String>>((t) {
                  return DropdownMenuItem(
                    value: t['id'].toString(),
                    child: Text(t['name'], style: TextStyle(color: textColor)),
                  );
                }).toList(),
                onChanged: (v) => setState(() => _selectedTypeId = v),
                validator: (v) => v == null ? 'Requis' : null,
              ),
              const SizedBox(height: 16),

              // Date Selection
              Row(
                children: [
                  Expanded(
                    child: _buildDatePicker(
                      label: "Début",
                      date: _startDate,
                      onPick: (d) => setState(() {
                        _startDate = d;
                        if (_endDate.isBefore(d)) _endDate = d;
                      }),
                      isDark: isDark,
                      textColor: textColor
                    ),
                  ),
                  if (!_isRetard) ...[
                    const SizedBox(width: 16),
                    Expanded(
                      child: _buildDatePicker(
                        label: "Fin",
                        date: _endDate,
                        onPick: (d) => setState(() => _endDate = d),
                        isDark: isDark,
                        textColor: textColor
                      ),
                    ),
                  ]
                ],
              ),
              
              // Duration if Retard
              if (_isRetard) ...[
                const SizedBox(height: 16),
                TextFormField(
                  controller: _durationController,
                  keyboardType: TextInputType.number,
                  style: TextStyle(color: textColor),
                  decoration: _inputDecoration("Durée du retard (minutes)", isDark).copyWith(
                    suffixText: 'min',
                  ),
                  validator: (v) => v!.isEmpty ? 'Requis' : null,
                ),
              ],

              const SizedBox(height: 16),
              
              // Comment (Required)
              TextFormField(
                controller: _commentController,
                maxLines: 3,
                style: TextStyle(color: textColor),
                decoration: _inputDecoration("Motif / Commentaire", isDark),
                validator: (v) => v!.isEmpty || v.length < 5 ? 'Veuillez détailler le motif' : null,
              ),

              const SizedBox(height: 32),

              // Buttons
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text("Annuler", style: TextStyle(color: textColor.withOpacity(0.7))),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: _isLoading 
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text("Envoyer la demande"),
                  ),
                ],
              )
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDatePicker({
    required String label, 
    required DateTime date, 
    required Function(DateTime) onPick,
    required bool isDark,
    required Color textColor
  }) {
    return InkWell(
      onTap: () async {
        final d = await showDatePicker(
          context: context,
          initialDate: date,
          firstDate: DateTime(2020),
          lastDate: DateTime(2030),
          builder: (context, child) {
            return Theme(
              data: isDark ? ThemeData.dark() : ThemeData.light(),
              child: child!,
            );
          }
        );
        if (d != null) onPick(d);
      },
      child: InputDecorator(
        decoration: _inputDecoration(label, isDark).copyWith(
          suffixIcon: const Icon(Icons.calendar_today, size: 18),
        ),
        child: Text(
          DateFormat('dd/MM/yyyy').format(date),
          style: TextStyle(color: textColor),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String label, bool isDark) {
    return InputDecoration(
      labelText: label,
      labelStyle: TextStyle(color: isDark ? Colors.white70 : Colors.grey[700]),
      filled: true,
      fillColor: isDark ? const Color(0xFF0F172A) : Colors.grey[100],
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.blue, width: 2)),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);

    try {
      final api = context.read<AuthService>().getApiService();
      
      final data = {
        'type_id': _selectedTypeId,
        'date_start': DateFormat('yyyy-MM-dd').format(_startDate),
        'date_end': DateFormat('yyyy-MM-dd').format(_endDate),
        'comment': _commentController.text,
      };

      if (_isRetard) {
        data['duration_minutes'] = _durationController.text;
      }

      final response = await api.post('/presence/create.php', data);
      
      if (response['success'] == true || response['id'] != null) {
        if (mounted) {
           Navigator.pop(context);
           widget.onSuccess();
           ScaffoldMessenger.of(context).showSnackBar(
             const SnackBar(content: Text("Demande envoyée avec succès"), backgroundColor: Colors.green),
           );
        }
      } else {
        throw Exception(response['error'] ?? 'Erreur inconnue');
      }

    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Erreur: $e"), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }
}
