/// Employee Activity Timeline Modal
/// Shows daily activity history for an employee
/// Refactored for better visibility: Larger modal + 2-column layout

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:intl/date_symbol_data_local.dart';
import '../../services/api_service.dart';
import '../../theme/macos_theme.dart';

class EmployeeActivityModal extends StatefulWidget {
  final Map<String, dynamic> employee;
  final ApiService apiService;

  const EmployeeActivityModal({
    super.key,
    required this.employee,
    required this.apiService,
  });

  @override
  State<EmployeeActivityModal> createState() => _EmployeeActivityModalState();
}

class _EmployeeActivityModalState extends State<EmployeeActivityModal> {
  DateTime _selectedDate = DateTime.now();
  bool _isLoading = true;
  String? _error;
  List<Map<String, dynamic>> _activities = [];

  @override
  void initState() {
    super.initState();
    initializeDateFormatting('fr_FR', null).then((_) {
      _loadActivity();
    });
  }

  Future<void> _loadActivity() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final response = await widget.apiService.get(
        '/employees/activity.php?user_id=${widget.employee['id']}&start_date=$dateStr&end_date=$dateStr',
      );

      if (response['success'] == true) {
        setState(() {
          _activities = _groupActivities(List<Map<String, dynamic>>.from(response['logs'] ?? []));
          _isLoading = false;
        });
      } else {
        throw Exception(response['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  List<Map<String, dynamic>> _groupActivities(List<Map<String, dynamic>> logs) {
    final List<Map<String, dynamic>> groups = [];
    Map<String, dynamic>? currentGroup;

    for (final log in logs) {
      final logType = log['log_type'] ?? 'repair';
      
      if (logType == 'task') {
        if (currentGroup != null && 
            currentGroup['type'] == 'task' && 
            currentGroup['task_id'] == log['task_id']) {
          (currentGroup['logs'] as List).add(log);
        } else {
          currentGroup = {
            'type': 'task',
            'task_id': log['task_id'],
            'task_title': log['task_title'],
            'task_description': log['task_description'],
            'logs': [log],
          };
          groups.add(currentGroup);
        }
      } else if (logType == 'time_tracking') {
        currentGroup = {
          'type': 'time_tracking',
          'clock_in': log['clock_in'],
          'clock_out': log['clock_out'],
          'work_duration': log['work_duration'],
          'break_duration': log['break_duration'],
          'status': log['status'],
          'logs': [log],
        };
        groups.add(currentGroup);
      } else {
        // Repair
        if (currentGroup != null && 
            currentGroup['type'] == 'repair' && 
            currentGroup['reparation_id'] == log['reparation_id']) {
          (currentGroup['logs'] as List).add(log);
        } else {
          currentGroup = {
            'type': 'repair',
            'reparation_id': log['reparation_id'],
            'repair_model': log['repair_model'],
            'repair_problem': log['repair_problem'],
            'client': log['client'],
            'logs': [log],
          };
          groups.add(currentGroup);
        }
      }
    }
    
    return groups;
  }

  void _changeDate(int days) {
    setState(() {
      _selectedDate = _selectedDate.add(Duration(days: days));
    });
    _loadActivity();
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      locale: const Locale('fr', 'FR'),
    );
    if (picked != null && picked != _selectedDate) {
      setState(() => _selectedDate = picked);
      _loadActivity();
    }
  }

  @override
  Widget build(BuildContext context) {
    final employeeName = widget.employee['full_name'] ?? 'Employé';
    final busy = widget.employee['techbusy'] == 1 || widget.employee['techbusy'] == '1';
    final size = MediaQuery.of(context).size;

    return Dialog(
      backgroundColor: const Color(0xFF0F172A),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: Container(
        width: size.width * 0.85,
        height: size.height * 0.85,
        constraints: const BoxConstraints(maxWidth: 1400, maxHeight: 900),
        child: Column(
          children: [
            // Header
            _buildHeader(employeeName, busy),
            
            // Content Body
            Expanded(
              child: Row(
                children: [
                  // Left Panel: Stats & Context
                  Container(
                    width: 320,
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      border: Border(right: BorderSide(color: Colors.white.withOpacity(0.05))),
                    ),
                    child: Column(
                      children: [
                        _buildDateNavigation(),
                        const Divider(height: 1, color: Colors.white10),
                        Expanded(child: _buildStatsPanel()),
                      ],
                    ),
                  ),

                  // Right Panel: Timeline
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(24, 24, 24, 16),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'TIMELINE DES ACTIVITÉS',
                                style: TextStyle(
                                  color: Colors.white70, 
                                  fontSize: 13, 
                                  fontWeight: FontWeight.bold, 
                                  letterSpacing: 1.2
                                ),
                              ),
                              if (!_isLoading)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: Colors.blue.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(color: Colors.blue.withOpacity(0.3)),
                                  ),
                                  child: Text(
                                    '${_activities.length} événements',
                                    style: const TextStyle(color: Colors.blueAccent, fontSize: 13, fontWeight: FontWeight.w600),
                                  ),
                                ),
                            ],
                          ),
                        ),
                        Expanded(child: _buildTimelineContent()),
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

  Widget _buildHeader(String employeeName, bool busy) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        border: Border(bottom: BorderSide(color: Colors.white.withOpacity(0.05))),
      ),
      child: Row(
        children: [
          Hero(
            tag: 'avatar_${widget.employee['id']}',
            child: CircleAvatar(
              radius: 24,
              backgroundColor: Colors.blueAccent,
              child: Text(
                employeeName.isNotEmpty ? employeeName[0].toUpperCase() : 'E',
                style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  employeeName,
                  style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: busy ? Colors.orange.withOpacity(0.2) : Colors.green.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: busy ? Colors.orange.withOpacity(0.3) : Colors.green.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(busy ? Icons.build : Icons.check_circle, size: 12, color: busy ? Colors.orange : Colors.green),
                          const SizedBox(width: 4),
                          Text(
                            busy ? 'Occupé' : 'Disponible',
                            style: TextStyle(color: busy ? Colors.orange[200] : Colors.green[200], fontSize: 12, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close, color: Colors.white54),
            tooltip: 'Fermer',
          ),
        ],
      ),
    );
  }

  Widget _buildDateNavigation() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('DATE SÉLECTIONNÉE', style: TextStyle(color: Colors.white38, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1)),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.black26,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.white10),
            ),
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.chevron_left, color: Colors.white70),
                  onPressed: () => _changeDate(-1),
                  tooltip: 'Jour précédent',
                ),
                Expanded(
                  child: InkWell(
                    onTap: _selectDate,
                    borderRadius: BorderRadius.circular(8),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Column(
                        children: [
                          Text(
                            DateFormat('EEEE', 'fr_FR').format(_selectedDate).toUpperCase(),
                            style: const TextStyle(color: Colors.white54, fontSize: 11, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            DateFormat('d MMM yyyy', 'fr_FR').format(_selectedDate),
                            style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                IconButton(
                  icon: Icon(
                    Icons.chevron_right, 
                    color: _selectedDate.isBefore(DateTime.now().subtract(const Duration(days: 1))) 
                      ? Colors.white70 
                      : Colors.white24,
                  ),
                  onPressed: _selectedDate.isBefore(DateTime.now().subtract(const Duration(days: 1)))
                    ? () => _changeDate(1)
                    : null,
                  tooltip: 'Jour suivant',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsPanel() {
    if (_isLoading) return const Center(child: CircularProgressIndicator(strokeWidth: 2));
    if (_activities.isEmpty) return const SizedBox();

    // Calculate stats
    int repairsCount = _activities.where((a) => a['type'] == 'repair').length;
    int tasksCount = _activities.where((a) => a['type'] == 'task').length;
    
    // Find time tracking info
    final timeTracking = _activities.firstWhere(
      (a) => a['type'] == 'time_tracking', 
      orElse: () => <String, dynamic>{'type': 'none'}
    );

    String hours = "0h";
    if (timeTracking['type'] != 'none') {
       hours = '${double.tryParse(timeTracking['work_duration'].toString())?.toStringAsFixed(1) ?? '0'}h';
    }

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        // Daily Summary
        const Text('RÉSUMÉ DU JOUR', style: TextStyle(color: Colors.white38, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1)),
        const SizedBox(height: 16),
        
        Row(
          children: [
            Expanded(child: _buildStatItem(Icons.build_circle_outlined, repairsCount.toString(), "Réparations", Colors.blue)),
            const SizedBox(width: 12),
            Expanded(child: _buildStatItem(Icons.task_alt, tasksCount.toString(), "Tâches", Colors.purple)),
          ],
        ),
        const SizedBox(height: 12),
        _buildStatItem(Icons.access_time_filled, hours, "Heures travaillées", Colors.green, fullWidth: true),

        const SizedBox(height: 32),
        
        // Time Tracking Details if available
        if (timeTracking['type'] != 'none') ...[
          const Text('POINTAGE', style: TextStyle(color: Colors.white38, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1)),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.black26,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white10),
            ),
            child: Column(
              children: [
                 _buildTimeRow("Arrivée", timeTracking['clock_in'] != null ? _formatTime(timeTracking['clock_in']) : '-', Icons.login, Colors.green),
                 const Padding(padding: EdgeInsets.symmetric(vertical: 12), child: Divider(height: 1, color: Colors.white10)),
                 _buildTimeRow("Départ", timeTracking['clock_out'] != null ? _formatTime(timeTracking['clock_out']) : 'En cours', Icons.logout, Colors.red),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildStatItem(IconData icon, String value, String label, Color color, {bool fullWidth = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: color, size: 20),
              if (fullWidth)
                 Container(
                   padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                   decoration: BoxDecoration(color: Colors.black26, borderRadius: BorderRadius.circular(8)),
                   child: Text(value, style: TextStyle(color: color, fontWeight: FontWeight.bold)),
                 )
            ],
          ),
          const SizedBox(height: 12),
          if (!fullWidth)
            Text(value, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
          if (!fullWidth)
            const SizedBox(height: 4),
          Text(label, style: TextStyle(color: color.withOpacity(0.8), fontSize: 12)),
          
          if (fullWidth) ...[
             const SizedBox(height: 4),
             Text(value, style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold)),
          ]
        ],
      ),
    );
  }

  Widget _buildTimeRow(String label, String time, IconData icon, Color color) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(color: Colors.white70, fontSize: 13)),
          ],
        ),
        Text(time, style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w600)),
      ],
    );
  }

  Widget _buildTimelineContent() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: Colors.redAccent, size: 48),
            const SizedBox(height: 16),
            Text(_error!, style: const TextStyle(color: Colors.redAccent)),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _loadActivity, child: const Text("Réessayer"))
          ],
        ),
      );
    }

    if (_activities.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.history_toggle_off, color: Colors.white.withOpacity(0.1), size: 80),
            const SizedBox(height: 16),
            const Text("Aucune activité enregistrée", style: TextStyle(color: Colors.white38, fontSize: 16)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      itemCount: _activities.length,
      itemBuilder: (context, index) {
        final activity = _activities[index];
        // Skip time_tracking in timeline as it's shown in left panel
        if (activity['type'] == 'time_tracking') return const SizedBox.shrink();
        
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _buildCompactActivityCard(activity),
        );
      },
    );
  }

  Widget _buildCompactActivityCard(Map<String, dynamic> group) {
    final type = group['type'] ?? 'repair';
    final logs = List<Map<String, dynamic>>.from(group['logs'] ?? []);
    final isRepair = type == 'repair';
    final color = isRepair ? Colors.blue : Colors.purple;
    
    String title = isRepair 
        ? 'Réparation #${group['reparation_id']} - ${group['repair_model']}' 
        : 'Tâche: ${group['task_title']}';
        
    String subtitle = isRepair 
        ? (group['client'] ?? 'Client inconnu') 
        : (group['task_description'] ?? '');

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Card Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
              border: Border(bottom: BorderSide(color: color.withOpacity(0.1))),
            ),
            child: Row(
              children: [
                Icon(isRepair ? Icons.phonelink_setup : Icons.assignment, color: color, size: 18),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: TextStyle(color: Colors.white.withOpacity(0.9), fontWeight: FontWeight.bold, fontSize: 14)),
                      if (subtitle.isNotEmpty)
                        Text(subtitle, style: TextStyle(color: color.withOpacity(0.8), fontSize: 12)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.black26,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '${logs.length} action${logs.length > 1 ? 's' : ''}',
                    style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
          ),
          
          // Logs List (Compact)
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(12),
            itemCount: logs.length,
            separatorBuilder: (c, i) => Padding(
              padding: const EdgeInsets.only(left: 48),
              child: Divider(height: 16, color: Colors.white.withOpacity(0.05)),
            ),
            itemBuilder: (context, index) {
              final log = logs[index];
              return _buildCompactLogItem(log, index == logs.length - 1);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildCompactLogItem(Map<String, dynamic> log, bool isLast) {
    final actionType = log['action_type'] ?? '';
    final actionLabel = log['action_label'] ?? actionType;
    final time = log['time'] ?? '';
    final details = log['details']?.toString() ?? '';

    Color dotColor = Colors.grey;
    switch (actionType) {
      case 'demarrage': case 'start': dotColor = Colors.green; break;
      case 'terminer': case 'stop': dotColor = Colors.red; break;
      case 'changement_statut': dotColor = Colors.cyan; break;
      case 'ajout_note': dotColor = Colors.amber; break;
      case 'creation': dotColor = Colors.white; break;
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 40,
          child: Column(
            children: [
              Text(time, style: const TextStyle(color: Colors.white54, fontSize: 11, fontWeight: FontWeight.w600)),
            ],
          ),
        ),
        Container(
          margin: const EdgeInsets.only(top: 4, right: 12),
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            color: dotColor,
            shape: BoxShape.circle,
            border: Border.all(color: dotColor.withOpacity(0.3), width: 2),
          ),
        ),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(actionLabel, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500)),
              
              // Status Change Indicator
              if (log['statut_avant'] != null || log['statut_apres'] != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Row(
                    children: [
                      if (log['statut_avant'] != null) ...[
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: Colors.white10, borderRadius: BorderRadius.circular(4)),
                          child: Text(log['statut_avant'], style: const TextStyle(color: Colors.white70, fontSize: 10)),
                        ),
                        const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 6),
                          child: Icon(Icons.arrow_right_alt, color: Colors.white38, size: 14),
                        ),
                      ],
                      if (log['statut_apres'] != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: Colors.white10, borderRadius: BorderRadius.circular(4)),
                          child: Text(log['statut_apres'], style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                    ],
                  ),
                ),
                
              // Details Text
              if (details.isNotEmpty && !details.startsWith('Mise à jour du statut'))
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(
                    details, 
                    style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 12),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  String _formatTime(String? datetime) {
    if (datetime == null) return '-';
    try {
      final dt = DateTime.parse(datetime);
      return DateFormat('HH:mm').format(dt);
    } catch (e) {
      return datetime;
    }
  }
}
