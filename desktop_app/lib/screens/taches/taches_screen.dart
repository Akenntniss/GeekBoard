/// Tâches Screen
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import 'package:intl/intl.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';
import 'task_detail_dialog.dart';

class TachesScreen extends StatefulWidget {
  const TachesScreen({super.key});
  @override
  State<TachesScreen> createState() => _TachesScreenState();
}

class _TachesScreenState extends State<TachesScreen> {
  List<Map<String, dynamic>> _taches = [];
  List<Map<String, dynamic>> _filteredTaches = [];
  bool _isLoading = true;
  String? _error;
  String _selectedStatusFilter = 'all'; // all, afaire, terminee
  bool _showMyTasksOnly = false; // Toggle my tasks

  @override
  void initState() {
    super.initState();
    _loadTaches();
  }

  Future<void> _loadTaches() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      // Load ALL tasks and filter locally to handle complex filters comfortably
      final response = await apiService.get(ApiConfig.tachesListEndpoint);
      
      if (mounted) {
        setState(() {
          _taches = List<Map<String, dynamic>>.from(response['taches'] ?? []);
          _applyFilters();
          _isLoading = false;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _isLoading = false; });
    }
  }

  void _applyFilters() {
    final authService = context.read<AuthService>();
    final currentUserId = authService.currentUser?.id;

    setState(() {
      _filteredTaches = _taches.where((t) {
        // Status Filter
        bool statusMatch = true;
        String status = (t['statut'] ?? '').toString().toLowerCase();
        
        if (_selectedStatusFilter == 'terminee') {
          statusMatch = status == 'terminee';
        } else if (_selectedStatusFilter == 'afaire') {
          statusMatch = status != 'terminee';
        } 
        // 'all' includes everything
        
        // My Tasks Filter
        bool myTaskMatch = true;
        if (_showMyTasksOnly && currentUserId != null) {
          // Check if assigned to me (technicien_id) or created by me (createur_id)?
          // Usually 'technicien_id' is the assignee
          myTaskMatch = (t['technicien_id'].toString() == currentUserId.toString()) || (t['employe_id'].toString() == currentUserId.toString());
        }

        return statusMatch && myTaskMatch;
      }).toList();
      
      // Sort: Urgent first, then date
      _filteredTaches.sort((a, b) {
         // Urgency
         int scoreA = _getUrgencyScore(a['urgence']);
         int scoreB = _getUrgencyScore(b['urgence']);
         if (scoreA != scoreB) return scoreB.compareTo(scoreA); // High score first
         
         // Date
         return (b['date_creation'] ?? '').compareTo(a['date_creation'] ?? '');
      });
    });
  }
  
  int _getUrgencyScore(String? u) {
    switch (u?.toLowerCase()) {
      case 'haute': return 3;
      case 'moyenne': return 2;
      default: return 1;
    }
  }

  Future<void> _updateTaskStatus(String id, String newStatus) async {
    try {
      // Optimistic update
      setState(() {
        final index = _taches.indexWhere((t) => t['id'].toString() == id);
        if (index != -1) {
          _taches[index]['statut'] = newStatus;
          _applyFilters();
        }
      });
      
      final authService = context.read<AuthService>();
      await authService.getApiService().post(ApiConfig.tachesUpdateEndpoint, {
        'id': id,
        'statut': newStatus,
      });
      
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('Tâche ${newStatus == 'en_cours' ? 'commencée' : 'terminée'}'),
        backgroundColor: Colors.green,
        duration: const Duration(seconds: 1),
      ));
    } catch (e) {
       _loadTaches(); // Revert on error
       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/taches',
      content: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header & Filters
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    const Text('Tâches', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                    const Spacer(),
                    ElevatedButton.icon(
                      onPressed: () {}, // Create task
                      icon: const Icon(Icons.add),
                      label: const Text("Nouvelle Tâche"),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blueAccent,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    _buildFilterChip('all', 'Toutes'),
                    const SizedBox(width: 12),
                    _buildFilterChip('afaire', 'À Faire'),
                    const SizedBox(width: 12),
                    _buildFilterChip('terminee', 'Terminées'),
                    
                    const Spacer(),
                    
                    // My Tasks Toggle
                    Row(
                      children: [
                        const Text("Mes tâches uniquement", style: TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(width: 8),
                        Switch.adaptive(
                          value: _showMyTasksOnly,
                          onChanged: (v) {
                            setState(() {
                              _showMyTasksOnly = v;
                              _applyFilters();
                            });
                          },
                          activeColor: Colors.blue,
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ),
          ),
          
          // List
          Expanded(
            child: _isLoading
                ? const Center(child: CupertinoActivityIndicator())
                : _error != null
                    ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
                    : _filteredTaches.isEmpty
                        ? const Center(child: Text("Aucune tâche trouvée"))
                        : ListView.builder(
                            padding: const EdgeInsets.all(24),
                            itemCount: _filteredTaches.length,
                            itemBuilder: (context, i) {
                              return _buildTaskCard(_filteredTaches[i]);
                            },
                          ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String code, String label) {
    final isSelected = _selectedStatusFilter == code;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (v) {
        if (v) {
          setState(() {
            _selectedStatusFilter = code;
            _applyFilters();
          });
        }
      },
      selectedColor: Colors.blue.withOpacity(0.2),
      labelStyle: TextStyle(
        color: isSelected ? Colors.blue[800] : Colors.black87,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20), 
        side: BorderSide(color: isSelected ? Colors.blue : Colors.grey.shade300)
      ),
    );
  }

  Widget _buildTaskCard(Map<String, dynamic> t) {
    bool isCompleted = t['statut'] == 'terminee';
    bool isInProgress = t['statut'] == 'en_cours';
    
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          showDialog(
            context: context,
            builder: (_) => TaskDetailDialog(task: t),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: MacOSCard(
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                // Icon
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: isCompleted ? Colors.green.withOpacity(0.1) : (isInProgress ? Colors.blue.withOpacity(0.1) : Colors.grey.withOpacity(0.1)),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    isCompleted ? Icons.check_circle : (isInProgress ? Icons.play_circle_fill : Icons.assignment),
                    color: isCompleted ? Colors.green : (isInProgress ? Colors.blue : Colors.grey),
                  ),
                ),
                const SizedBox(width: 16),
                
                // Content
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        t['titre'] ?? 'Tâche sans titre',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 16,
                          decoration: isCompleted ? TextDecoration.lineThrough : null,
                          color: isCompleted ? Colors.grey : Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        t['description'] ?? '',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(color: Colors.grey[600], fontSize: 13),
                      ),
                    ],
                  ),
                ),
                
                // Meta
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    PriorityBadge(priority: t['urgence'] ?? 'normale'),
                    const SizedBox(height: 8),
                    Text(
                      t['date_echeance'] != null ? 'Échéance: ${_formatDate(t['date_echeance'])}' : '',
                      style: const TextStyle(fontSize: 11, color: Colors.red),
                    ),
                  ],
                ),
                
                const SizedBox(width: 24),
                
                // Action Button
                if (!isCompleted)
                  ElevatedButton.icon(
                    onPressed: () {
                      if (isInProgress) {
                        _updateTaskStatus(t['id'].toString(), 'terminee');
                      } else {
                        _updateTaskStatus(t['id'].toString(), 'en_cours');
                      }
                    },
                    icon: Icon(isInProgress ? Icons.check : Icons.play_arrow, size: 16),
                    label: Text(isInProgress ? "Terminer" : "Commencer"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isInProgress ? Colors.green : Colors.blue,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
  
  String _formatDate(String? s) {
    if (s == null) return '';
    try {
      return DateFormat('dd/MM').format(DateTime.parse(s));
    } catch (_) { return s; }
  }
}
