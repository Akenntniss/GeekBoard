import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../theme/macos_theme.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';

// Vibrant blue accent color matching iOS style
const Color _taskBlue = Color(0xFF007AFF);
const Color _taskBlueLight = Color(0xFF5AC8FA);

/// Modal de détail de tâche avec boutons Démarrer/Arrêter
class TaskDetailModal extends StatefulWidget {
  final Map<String, dynamic> task;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const TaskDetailModal({
    super.key,
    required this.task,
    required this.apiService,
    this.onUpdate,
  });

  @override
  State<TaskDetailModal> createState() => _TaskDetailModalState();
}

class _TaskDetailModalState extends State<TaskDetailModal> {
  bool _isLoading = false;
  Map<String, dynamic>? _activeTask;

  @override
  void initState() {
    super.initState();
    _checkActiveTask();
  }

  Future<void> _checkActiveTask() async {
    try {
      final response = await widget.apiService.post('/taches/update_status.php', {
        'action': 'check_active_task'
      });
      if (mounted && response['success'] == true) {
        setState(() {
          _activeTask = response['active_task'];
        });
      }
    } catch (e) {
      // Silent error
    }
  }

  Future<void> _startTask() async {
    // Check if user has an active task
    if (_activeTask != null) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text("Tâche en cours"),
            content: Text("Vous avez déjà une tâche en cours: \"${_activeTask!['titre']}\". Terminez-la avant d'en commencer une autre."),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text("OK"),
              )
            ],
          )
        );
      }
      return;
    }

    // Confirm start
    bool confirm = await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Démarrer la tâche"),
        content: Text("Voulez-vous commencer la tâche \"${widget.task['titre']}\" ?"),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.successGreen),
            onPressed: () => Navigator.pop(ctx, true), 
            child: const Text("Démarrer", style: TextStyle(color: Colors.white))
          ),
        ]
      )
    ) ?? false;

    if (!confirm) return;

    setState(() => _isLoading = true);

    try {
      final response = await widget.apiService.post('/taches/update_status.php', {
        'action': 'start',
        'tache_id': widget.task['id']
      });

      if (response['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Tâche démarrée avec succès'), backgroundColor: MacOSTheme.successGreen),
          );
          widget.onUpdate?.call();
          Navigator.pop(context);
        }
      } else {
        throw Exception(response['error'] ?? response['message'] ?? "Erreur");
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _handleTaskCompletion(bool isComplete) async {
    String title = isComplete ? "Terminer la tâche" : "Arrêter la tâche";
    String message = isComplete 
        ? "Voulez-vous marquer cette tâche comme terminée ?"
        : "Voulez-vous arrêter de travailler sur cette tâche ?";

    bool confirm = await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: isComplete ? MacOSTheme.successGreen : MacOSTheme.dangerRed
            ),
            onPressed: () => Navigator.pop(ctx, true), 
            child: Text(isComplete ? "Terminer" : "Arrêter", style: const TextStyle(color: Colors.white))
          ),
        ]
      )
    ) ?? false;

    if (!confirm) return;

    setState(() => _isLoading = true);

    try {
      final payload = {
        'action': isComplete ? 'complete' : 'stop',
        'tache_id': widget.task['id']
      };
      
      final response = await widget.apiService.post('/taches/update_status.php', payload);

      if (response['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isComplete ? 'Tâche terminée' : 'Tâche arrêtée'), 
              backgroundColor: MacOSTheme.successGreen
            ),
          );
          widget.onUpdate?.call();
          Navigator.pop(context);
        }
      } else {
        throw Exception(response['error'] ?? response['message'] ?? "Erreur");
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Color _getPriorityColor(String? priority) {
    switch (priority?.toLowerCase()) {
      case 'urgente':
        return MacOSTheme.dangerRed;
      case 'haute':
        return Colors.orange;
      case 'moyenne':
        return MacOSTheme.warningOrange;
      case 'basse':
        return Colors.grey;
      default:
        return MacOSTheme.accentBlue;
    }
  }

  String _getStatusLabel(String? status) {
    switch (status) {
      case 'a_faire':
        return 'À faire';
      case 'en_cours':
        return 'En cours';
      case 'termine':
        return 'Terminée';
      default:
        return status ?? 'Inconnu';
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = widget.task;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final authService = context.watch<AuthService>();
    final currentUser = authService.currentUser;
    
    // Determine if this is user's active task
    // Fix: Prioritize _activeTask (fresh from API) over potentially stale t['statut']
    print('DEBUG TASK MODAL: ID=${t['id']} Status=${t['statut']} Employe=${t['employe_id']} CurrentUser=${currentUser?.id}');
    if (_activeTask != null) {
      print('DEBUG ACTIVE TASK: ID=${_activeTask!['id']} Status=${_activeTask!['statut']}');
    } else {
      print('DEBUG ACTIVE TASK: NULL');
    }

    final isMyActiveTask = (_activeTask != null && _activeTask!['id'].toString() == t['id'].toString()) ||
                           (t['statut'] == 'en_cours' && t['employe_id']?.toString() == currentUser?.id.toString());
    
    print('DEBUG IS MY ACTIVE TASK: $isMyActiveTask');
    
    final isFinished = t['statut'] == 'termine';

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 500,
        constraints: const BoxConstraints(maxHeight: 600),
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
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.task_alt, color: Colors.white),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          t['titre'] ?? 'Tâche',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: _getPriorityColor(t['priorite']),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                (t['priorite'] ?? 'normale').toString().toUpperCase(),
                                style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              isMyActiveTask ? 'En cours' : _getStatusLabel(t['statut']),
                              style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 12),
                            ),
                          ],
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

            // Body
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Description
                    if (t['description'] != null && t['description'].toString().isNotEmpty) ...[
                      const Text(
                        'DESCRIPTION',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: _taskBlue, letterSpacing: 0.5),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          t['description'],
                          style: TextStyle(
                            color: Theme.of(context).textTheme.bodyMedium?.color,
                            height: 1.5,
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],

                    // Info grid
                    Row(
                      children: [
                        Expanded(
                          child: _InfoTile(
                            icon: Icons.person,
                            label: 'Assignée à',
                            value: t['employe_nom'] ?? t['assignee'] ?? 'Non assigné',
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _InfoTile(
                            icon: Icons.calendar_today,
                            label: 'Date limite',
                            value: t['date_limite'] ?? 'Aucune',
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _InfoTile(
                            icon: Icons.access_time,
                            label: 'Créée le',
                            value: t['date_creation'] ?? '-',
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _InfoTile(
                            icon: Icons.flag,
                            label: 'Priorité',
                            value: (t['priorite'] ?? 'normale').toString().toUpperCase(),
                            valueColor: _getPriorityColor(t['priorite']),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 24),

                    // Action Buttons
                    if (!isFinished) ...[
                      if (isMyActiveTask) ...[
                        // Stop and Complete buttons
                        // Stop and Complete buttons (Vertical for safety)
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _isLoading ? null : () {
                              _handleTaskCompletion(true);
                            },
                            icon: _isLoading 
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : const Icon(Icons.check_circle),
                            label: const Text('TERMINER LA TÂCHE'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: MacOSTheme.successGreen,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                        ),
                      ] else ...[
                        // Start button
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _isLoading ? null : _startTask,
                            icon: _isLoading 
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : const Icon(Icons.play_circle_filled),
                            label: const Text('DÉMARRER LA TÂCHE', style: TextStyle(fontWeight: FontWeight.bold)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: MacOSTheme.successGreen,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                        ),
                      ],
                    ] else ...[
                      // Completed badge
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: MacOSTheme.successGreen.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: MacOSTheme.successGreen.withOpacity(0.3)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.check_circle, color: MacOSTheme.successGreen),
                            const SizedBox(width: 8),
                            Text(
                              'TÂCHE TERMINÉE',
                              style: TextStyle(color: MacOSTheme.successGreen, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  const _InfoTile({
    required this.icon,
    required this.label,
    required this.value,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Icon(icon, size: 18, color: _taskBlue),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: TextStyle(fontSize: 10, color: Theme.of(context).textTheme.bodySmall?.color)),
                Text(
                  value, 
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: valueColor),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
