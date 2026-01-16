import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/macos_theme.dart';

// Task blue color scheme
const Color _taskBlue = Color(0xFF007AFF);
const Color _taskBlueLight = Color(0xFF5AC8FA);

class CreateTaskDialog extends StatefulWidget {
  final ApiService apiService;

  const CreateTaskDialog({Key? key, required this.apiService}) : super(key: key);

  @override
  _CreateTaskDialogState createState() => _CreateTaskDialogState();
}

class _CreateTaskDialogState extends State<CreateTaskDialog> {
  final _formKey = GlobalKey<FormState>();
  
  final TextEditingController _titreController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _dateController = TextEditingController();

  String _priority = 'moyenne';
  String _status = 'a_faire';
  String? _selectedEmployeeId;
  DateTime? _selectedDate;
  
  List<dynamic> _employees = [];
  bool _isLoadingEmployees = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadEmployees();
  }

  @override
  void dispose() {
    _titreController.dispose();
    _descriptionController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  Future<void> _loadEmployees() async {
    try {
      final employees = await widget.apiService.getEmployees();
      if (mounted) {
        setState(() {
          _employees = employees;
          _isLoadingEmployees = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingEmployees = false);
      }
    }
  }

  Future<void> _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(primary: _taskBlue),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        _selectedDate = picked;
        _dateController.text = "${picked.day.toString().padLeft(2, '0')}/${picked.month.toString().padLeft(2, '0')}/${picked.year}";
      });
    }
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isSubmitting = true);

      try {
        final data = {
          'titre': _titreController.text,
          'description': _descriptionController.text,
          'priorite': _priority,
          'statut': _status,
          'date_limite': _selectedDate != null 
            ? "${_selectedDate!.year}-${_selectedDate!.month.toString().padLeft(2, '0')}-${_selectedDate!.day.toString().padLeft(2, '0')}"
            : null,
          'employe_id': _selectedEmployeeId,
        };

        await widget.apiService.createTask(data);

        if (mounted) {
          Navigator.of(context).pop(true);
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
          );
        }
      } finally {
        if (mounted) setState(() => _isSubmitting = false);
      }
    }
  }

  Color _getPriorityColor(String priority) {
    switch (priority) {
      case 'urgente': return MacOSTheme.dangerRed;
      case 'haute': return Colors.orange;
      case 'moyenne': return MacOSTheme.warningOrange;
      default: return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;
    final cardColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05);

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 600,
        constraints: const BoxConstraints(maxHeight: 650),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header with gradient
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [_taskBlue, _taskBlueLight],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.add_task, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 16),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Nouvelle Tâche',
                          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Créer et assigner une nouvelle tâche',
                          style: TextStyle(fontSize: 12, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),

            // Form Content
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Titre
                      _buildSectionLabel('TITRE', _taskBlue),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _titreController,
                        style: TextStyle(color: textColor, fontSize: 15),
                        decoration: _buildInputDecoration(
                          hint: 'Ex: Réparer imprimante, Appeler client...',
                          icon: Icons.title,
                          isDark: isDark,
                        ),
                        validator: (value) => value == null || value.isEmpty ? 'Champ requis' : null,
                      ),
                      const SizedBox(height: 20),

                      // Description
                      _buildSectionLabel('DESCRIPTION', _taskBlue),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _descriptionController,
                        style: TextStyle(color: textColor, fontSize: 15),
                        maxLines: 3,
                        decoration: _buildInputDecoration(
                          hint: 'Détails de la tâche...',
                          icon: Icons.description,
                          isDark: isDark,
                          alignIconTop: true,
                        ),
                        validator: (value) => value == null || value.isEmpty ? 'Champ requis' : null,
                      ),
                      const SizedBox(height: 20),

                      // Priority & Status Row
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildSectionLabel('PRIORITÉ', _taskBlue),
                                const SizedBox(height: 8),
                                Container(
                                  decoration: BoxDecoration(
                                    color: cardColor,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                                  ),
                                  child: DropdownButtonFormField<String>(
                                    value: _priority,
                                    dropdownColor: bgColor,
                                    style: TextStyle(color: textColor),
                                    decoration: const InputDecoration(
                                      border: InputBorder.none,
                                      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                    ),
                                    items: [
                                      _buildPriorityItem('basse', 'Basse', Colors.grey, textColor),
                                      _buildPriorityItem('moyenne', 'Moyenne', MacOSTheme.warningOrange, textColor),
                                      _buildPriorityItem('haute', 'Haute', Colors.orange, textColor),
                                      _buildPriorityItem('urgente', 'Urgente', MacOSTheme.dangerRed, textColor),
                                    ],
                                    onChanged: (val) => setState(() => _priority = val!),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildSectionLabel('STATUT', _taskBlue),
                                const SizedBox(height: 8),
                                Container(
                                  decoration: BoxDecoration(
                                    color: cardColor,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                                  ),
                                  child: DropdownButtonFormField<String>(
                                    value: _status,
                                    dropdownColor: bgColor,
                                    style: TextStyle(color: textColor),
                                    decoration: const InputDecoration(
                                      border: InputBorder.none,
                                      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                    ),
                                    items: [
                                      DropdownMenuItem(value: 'a_faire', child: Row(children: [
                                        Container(width: 8, height: 8, decoration: BoxDecoration(color: Colors.grey, shape: BoxShape.circle)),
                                        const SizedBox(width: 8),
                                        Text('À faire', style: TextStyle(color: textColor)),
                                      ])),
                                      DropdownMenuItem(value: 'en_cours', child: Row(children: [
                                        Container(width: 8, height: 8, decoration: BoxDecoration(color: _taskBlue, shape: BoxShape.circle)),
                                        const SizedBox(width: 8),
                                        Text('En cours', style: TextStyle(color: _taskBlue)),
                                      ])),
                                      DropdownMenuItem(value: 'termine', child: Row(children: [
                                        Container(width: 8, height: 8, decoration: BoxDecoration(color: MacOSTheme.successGreen, shape: BoxShape.circle)),
                                        const SizedBox(width: 8),
                                        Text('Terminé', style: TextStyle(color: MacOSTheme.successGreen)),
                                      ])),
                                    ],
                                    onChanged: (val) => setState(() => _status = val!),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Date & Assignee Row
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildSectionLabel('DATE LIMITE', _taskBlue),
                                const SizedBox(height: 8),
                                InkWell(
                                  onTap: _selectDate,
                                  borderRadius: BorderRadius.circular(12),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                                    decoration: BoxDecoration(
                                      color: cardColor,
                                      borderRadius: BorderRadius.circular(12),
                                      border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                                    ),
                                    child: Row(
                                      children: [
                                        Icon(Icons.calendar_today, size: 18, color: _taskBlue),
                                        const SizedBox(width: 12),
                                        Text(
                                          _dateController.text.isEmpty ? 'Sélectionner...' : _dateController.text,
                                          style: TextStyle(
                                            color: _dateController.text.isEmpty ? textColor.withOpacity(0.5) : textColor,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildSectionLabel('ASSIGNER À', _taskBlue),
                                const SizedBox(height: 8),
                                Container(
                                  decoration: BoxDecoration(
                                    color: cardColor,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                                  ),
                                  child: _isLoadingEmployees
                                    ? const Padding(
                                        padding: EdgeInsets.all(14),
                                        child: Center(child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))),
                                      )
                                    : DropdownButtonFormField<String>(
                                        value: _selectedEmployeeId,
                                        dropdownColor: bgColor,
                                        style: TextStyle(color: textColor),
                                        decoration: const InputDecoration(
                                          border: InputBorder.none,
                                          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                        ),
                                        hint: Row(children: [
                                          Icon(Icons.person_outline, size: 18, color: textColor.withOpacity(0.5)),
                                          const SizedBox(width: 8),
                                          Text('Non assigné', style: TextStyle(color: textColor.withOpacity(0.5))),
                                        ]),
                                        items: _employees.map((e) => DropdownMenuItem(
                                          value: e['id'].toString(),
                                          child: Row(children: [
                                            CircleAvatar(
                                              radius: 12,
                                              backgroundColor: _taskBlue.withOpacity(0.1),
                                              child: Text(
                                                (e['full_name']?.toString().substring(0, 1) ?? '?').toUpperCase(),
                                                style: TextStyle(color: _taskBlue, fontSize: 10, fontWeight: FontWeight.bold),
                                              ),
                                            ),
                                            const SizedBox(width: 8),
                                            Text(e['full_name'] ?? e['username'] ?? 'Inconnu', style: TextStyle(color: textColor)),
                                          ]),
                                        )).toList(),
                                        onChanged: (val) => setState(() => _selectedEmployeeId = val),
                                      ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),

            // Action buttons
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: Theme.of(context).dividerColor.withOpacity(0.3))),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.of(context).pop(false),
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    ),
                    child: Text("Annuler", style: TextStyle(color: textColor.withOpacity(0.6))),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton(
                    onPressed: _isSubmitting ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _taskBlue,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    child: _isSubmitting 
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.add, size: 18),
                            SizedBox(width: 8),
                            Text("Créer la tâche", style: TextStyle(fontWeight: FontWeight.w600)),
                          ],
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

  Widget _buildSectionLabel(String label, Color color) {
    return Text(
      label,
      style: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w600,
        color: color,
        letterSpacing: 0.5,
      ),
    );
  }

  InputDecoration _buildInputDecoration({
    required String hint,
    required IconData icon,
    required bool isDark,
    bool alignIconTop = false,
  }) {
    final cardColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05);
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.black38),
      prefixIcon: Padding(
        padding: EdgeInsets.only(top: alignIconTop ? 12 : 0),
        child: Icon(icon, color: _taskBlue, size: 20),
      ),
      filled: true,
      fillColor: cardColor,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Theme.of(context).dividerColor.withOpacity(0.3)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: _taskBlue, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    );
  }

  DropdownMenuItem<String> _buildPriorityItem(String value, String label, Color color, Color textColor) {
    return DropdownMenuItem(
      value: value,
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(label, style: TextStyle(color: value == _priority ? color : textColor)),
        ],
      ),
    );
  }
}
