/// Employee Activity Timeline Modal
/// Shows daily activity history for an employee

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

    return Dialog(
      backgroundColor: const Color(0xFF1E293B),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 700,
        height: 600,
        padding: const EdgeInsets.all(0),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF3B82F6), Color(0xFF1E40AF)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: Colors.white.withOpacity(0.2),
                    child: Text(
                      employeeName.isNotEmpty ? employeeName[0].toUpperCase() : 'E',
                      style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          employeeName,
                          style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: busy ? Colors.orange.withOpacity(0.3) : Colors.green.withOpacity(0.3),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                busy ? '🔧 Occupé' : '✓ Disponible',
                                style: TextStyle(color: busy ? Colors.orange[200] : Colors.green[200], fontSize: 12),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text('• Suivi d\'activité', style: TextStyle(color: Colors.white70, fontSize: 12)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white70),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            
            // Date Navigation
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                border: Border(bottom: BorderSide(color: Colors.white.withOpacity(0.1))),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    icon: const Icon(Icons.chevron_left, color: Colors.white70),
                    onPressed: () => _changeDate(-1),
                  ),
                  const SizedBox(width: 8),
                  InkWell(
                    onTap: _selectDate,
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.calendar_today, color: Colors.blue, size: 18),
                          const SizedBox(width: 10),
                          Text(
                            DateFormat('EEEE d MMMM yyyy', 'fr_FR').format(_selectedDate),
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
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
                  ),
                ],
              ),
            ),

            // Activity Count
            if (!_isLoading && _error == null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'TIMELINE DES ACTIVITÉS',
                      style: TextStyle(color: Colors.white54, fontSize: 11, fontWeight: FontWeight.bold, letterSpacing: 1),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${_activities.length} activité${_activities.length > 1 ? 's' : ''}',
                        style: const TextStyle(color: Colors.blue, fontSize: 12, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),

            // Content
            Expanded(
              child: _isLoading
                ? const Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CircularProgressIndicator(color: Colors.blue),
                        SizedBox(height: 16),
                        Text('Chargement des activités...', style: TextStyle(color: Colors.white54)),
                      ],
                    ),
                  )
                : _error != null
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.error_outline, color: Colors.red, size: 48),
                          const SizedBox(height: 16),
                          Text('Erreur: $_error', style: const TextStyle(color: Colors.red)),
                          const SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: _loadActivity,
                            child: const Text('Réessayer'),
                          ),
                        ],
                      ),
                    )
                  : _activities.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.event_busy, color: Colors.white.withOpacity(0.2), size: 64),
                            const SizedBox(height: 16),
                            const Text('Aucune activité', style: TextStyle(color: Colors.white70, fontSize: 18, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            const Text('Aucun log trouvé pour cette date', style: TextStyle(color: Colors.white38)),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _activities.length,
                        itemBuilder: (context, index) => _buildActivityCard(_activities[index]),
                      ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActivityCard(Map<String, dynamic> group) {
    final type = group['type'] ?? 'repair';
    final logs = List<Map<String, dynamic>>.from(group['logs'] ?? []);

    if (type == 'time_tracking') {
      return _buildTimeTrackingCard(group);
    } else if (type == 'task') {
      return _buildTaskCard(group, logs);
    } else {
      return _buildRepairCard(group, logs);
    }
  }

  Widget _buildTimeTrackingCard(Map<String, dynamic> group) {
    final clockIn = group['clock_in'] != null ? _formatTime(group['clock_in']) : '-';
    final clockOut = group['clock_out'] != null ? _formatTime(group['clock_out']) : 'En cours';
    final workDuration = group['work_duration'] != null 
      ? '${double.tryParse(group['work_duration'].toString())?.toStringAsFixed(1) ?? '0'}h'
      : '-';
    final status = group['status'] ?? 'active';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.green.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.green.withOpacity(0.2), Colors.transparent],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.access_time, color: Colors.green, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Text('🕐 Pointage', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: status == 'completed' ? Colors.green : Colors.blue,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              status == 'completed' ? 'Terminé' : 'Actif',
                              style: const TextStyle(color: Colors.white, fontSize: 10),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.login, color: Colors.green[300], size: 14),
                          const SizedBox(width: 4),
                          Text(clockIn, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                          const SizedBox(width: 12),
                          Icon(Icons.logout, color: Colors.red[300], size: 14),
                          const SizedBox(width: 4),
                          Text(clockOut, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                        ],
                      ),
                    ],
                  ),
                ),
                Text(workDuration, style: const TextStyle(color: Colors.green, fontSize: 18, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskCard(Map<String, dynamic> group, List<Map<String, dynamic>> logs) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.purple.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.purple.withOpacity(0.2), Colors.transparent],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.task_alt, color: Colors.purple, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '📋 Tâche #${group['task_id']}',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        group['task_title'] ?? 'Sans titre',
                        style: const TextStyle(color: Colors.white70, fontSize: 13),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${logs.length} action${logs.length > 1 ? 's' : ''}',
                    style: const TextStyle(color: Colors.white54, fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          _buildLogsList(logs),
        ],
      ),
    );
  }

  Widget _buildRepairCard(Map<String, dynamic> group, List<Map<String, dynamic>> logs) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.blue.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.blue.withOpacity(0.2), Colors.transparent],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.blue.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.build, color: Colors.blue, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            '🔧 Réparation #${group['reparation_id']}',
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.person, color: Colors.white54, size: 12),
                                const SizedBox(width: 4),
                                Text(
                                  group['client'] ?? 'Client',
                                  style: const TextStyle(color: Colors.white54, fontSize: 11),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${group['repair_model'] ?? 'Modèle inconnu'}${group['repair_problem'] != null ? ' - ${group['repair_problem']}' : ''}',
                        style: const TextStyle(color: Colors.white54, fontSize: 12),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${logs.length} action${logs.length > 1 ? 's' : ''}',
                    style: const TextStyle(color: Colors.white54, fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          _buildLogsList(logs),
        ],
      ),
    );
  }

  Widget _buildLogsList(List<Map<String, dynamic>> logs) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: logs.asMap().entries.map((entry) {
          final index = entry.key;
          final log = entry.value;
          final isLast = index == logs.length - 1;
          
          return _buildLogItem(log, isLast);
        }).toList(),
      ),
    );
  }

  Widget _buildLogItem(Map<String, dynamic> log, bool isLast) {
    final actionType = log['action_type'] ?? '';
    final actionLabel = log['action_label'] ?? actionType;
    final time = log['time'] ?? '';

    Color iconColor;
    IconData iconData;

    switch (actionType) {
      case 'demarrage':
      case 'start':
        iconColor = Colors.green;
        iconData = Icons.play_arrow;
        break;
      case 'terminer':
      case 'stop':
        iconColor = Colors.red;
        iconData = Icons.stop;
        break;
      case 'changement_statut':
        iconColor = Colors.cyan;
        iconData = Icons.sync;
        break;
      case 'ajout_note':
        iconColor = Colors.amber;
        iconData = Icons.note_add;
        break;
      default:
        iconColor = Colors.grey;
        iconData = Icons.circle;
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: iconColor.withOpacity(0.2),
                shape: BoxShape.circle,
              ),
              child: Icon(iconData, color: iconColor, size: 14),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 32,
                color: Colors.white.withOpacity(0.1),
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: EdgeInsets.only(bottom: isLast ? 0 : 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(actionLabel, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500)),
                      if (log['statut_avant'] != null || log['statut_apres'] != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Row(
                            children: [
                              if (log['statut_avant'] != null)
                                StatusBadge(status: log['statut_avant']),
                              const Padding(
                                padding: EdgeInsets.symmetric(horizontal: 6),
                                child: Icon(Icons.arrow_forward, color: Colors.white38, size: 12),
                              ),
                              if (log['statut_apres'] != null)
                                StatusBadge(status: log['statut_apres']),
                            ],
                          ),
                        ),
                      if (log['details'] != null && log['details'].toString().isNotEmpty)
                        Container(
                          margin: const EdgeInsets.only(top: 8),
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.amber.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                            border: Border(left: BorderSide(color: Colors.amber, width: 3)),
                          ),
                          child: Text(
                            log['details'],
                            style: const TextStyle(color: Colors.white70, fontSize: 12),
                          ),
                        ),
                    ],
                  ),
                ),
                Text(time, style: const TextStyle(color: Colors.white38, fontSize: 12)),
              ],
            ),
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
