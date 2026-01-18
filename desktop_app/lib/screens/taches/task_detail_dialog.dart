import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../theme/macos_theme.dart';

class TaskDetailDialog extends StatelessWidget {
  final Map<String, dynamic> task;

  const TaskDetailDialog({super.key, required this.task});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Dialog(
      backgroundColor: isDark ? MacOSTheme.gray800 : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 500,
        constraints: const BoxConstraints(maxHeight: 600),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: _getUrgencyColor(task['urgence']),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(16),
                  topRight: Radius.circular(16),
                ),
              ),
              child: Row(
                children: [
                  const Icon(Icons.task_alt, color: Colors.white, size: 28),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      task['titre'] ?? 'Détails de la tâche',
                      style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),
            
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildInfoSection('Description', task['description'] ?? 'Aucune description'),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Expanded(child: _buildInfoSection('Urgence', (task['urgence'] ?? 'Normal').toString().toUpperCase())),
                        Expanded(child: _buildInfoSection('Statut', (task['statut'] ?? 'En cours').toString().toUpperCase())),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Row(
                       children: [
                         Expanded(child: _buildInfoSection('Date Création', _formatDate(task['date_creation']))),
                         if (task['date_echeance'] != null)
                            Expanded(child: _buildInfoSection('Échéance', _formatDate(task['date_echeance']))),
                       ],
                    ),
                    
                    // Show who completed the task if it's terminated
                    if (_isTaskCompleted())
                      Column(
                        children: [
                          const SizedBox(height: 20),
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.green.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.green.withOpacity(0.3)),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 40,
                                  height: 40,
                                  decoration: BoxDecoration(
                                    color: Colors.green,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: const Icon(Icons.check, color: Colors.white, size: 20),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'EFFECTUÉE PAR',
                                        style: TextStyle(
                                          color: Colors.green,
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                          letterSpacing: 1,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        _getCompletedByName(),
                                        style: const TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ],
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
            
            // Footer
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Fermer'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  bool _isTaskCompleted() {
    final status = (task['statut'] ?? '').toString().toLowerCase();
    return status == 'termine' || status == 'terminee';
  }

  String _getCompletedByName() {
    // Try different possible field names for the person who completed the task
    final completedBy = task['assigned_to_name'] 
        ?? task['completed_by_name']
        ?? task['technicien_nom']
        ?? task['employe_nom']
        ?? task['created_by_name'];
    
    if (completedBy != null && completedBy.toString().isNotEmpty) {
      return completedBy.toString();
    }
    return 'Non spécifié';
  }

  Widget _buildInfoSection(String title, String content) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title.toUpperCase(),
          style: const TextStyle(
            color: Colors.grey,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          content,
          style: const TextStyle(fontSize: 14),
        ),
      ],
    );
  }

  Color _getUrgencyColor(String? urgency) {
    switch (urgency?.toLowerCase()) {
      case 'haute':
      case 'high':
        return Colors.red;
      case 'moyenne':
      case 'medium':
        return Colors.orange;
      default:
        return Colors.blue;
    }
  }

  String _formatDate(dynamic date) {
    if (date == null) return '-';
    try {
      return DateFormat('dd/MM/yyyy HH:mm').format(DateTime.parse(date.toString()));
    } catch (_) {
      return date.toString();
    }
  }
}
