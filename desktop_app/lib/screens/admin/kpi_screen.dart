import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/kpi_widgets.dart';

class KpiScreen extends StatefulWidget {
  const KpiScreen({super.key});

  @override
  State<KpiScreen> createState() => _KpiScreenState();
}

class _KpiScreenState extends State<KpiScreen> with SingleTickerProviderStateMixin {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  bool _isLoading = true;
  Map<String, dynamic> _data = {};

  // Tab Data
  List<dynamic> _employeeNotes = [];
  List<dynamic> _storeNotes = [];
  List<dynamic> _iaProfiles = [];
  bool _loadingNotes = false;
  bool _loadingStore = false;
  bool _loadingProfiles = false;

  // Filters
  late DateTime _startDate;
  late DateTime _endDate;
  int _selectedEmployee = 0; // 0 = all
  List<dynamic> _employees = [];

  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    
    // Default dates: Current month
    final now = DateTime.now();
    _startDate = DateTime(now.year, now.month, 1);
    _endDate = now;

    _loadEmployees();
    _loadDashboardData();
    _loadEmployeeNotes();
    _loadStoreNotes();
    _loadIAProfiles();
    
    _tabController.addListener(() {
        if (!_tabController.indexIsChanging) {
             // Refresh data when tab changes if needed
        }
    });
  }

  Future<void> _loadEmployees() async {
    try {
      final response = await _apiService.get(ApiConfig.employeesListEndpoint);
      if (mounted) {
        setState(() {
          _employees = response['employees'] ?? [];
        });
      }
    } catch (_) {}
  }

  Future<void> _loadDashboardData() async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'date_start': _startDate.toIso8601String().split('T')[0],
        'date_end': _endDate.toIso8601String().split('T')[0],
        if (_selectedEmployee > 0) 'employe_id': _selectedEmployee.toString(),
      };
      
      final response = await _apiService.get(ApiConfig.kpiDashboardEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          _data = Map<String, dynamic>.from(response ?? {});
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() { 
          _isLoading = false; 
        });
        // ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur chargement KPI: $e')));
      }
    }
  }

  Future<void> _loadEmployeeNotes() async {
      setState(() => _loadingNotes = true);
      try {
          final queryParams = {
             'date_start': _startDate.toIso8601String().split('T')[0],
             'date_end': _endDate.toIso8601String().split('T')[0],
             if (_selectedEmployee > 0) 'employee_id': _selectedEmployee.toString(),
          };
          final response = await _apiService.get(ApiConfig.kpiNotesEmployeesEndpoint, queryParams);
          if (mounted) {
              setState(() {
                  _employeeNotes = response['notes'] ?? [];
                  _loadingNotes = false;
              });
          }
      } catch (e) {
          if (mounted) setState(() => _loadingNotes = false);
      }
  }

  Future<void> _loadStoreNotes() async {
      setState(() => _loadingStore = true);
      try {
          final queryParams = {
             'date_start': _startDate.toIso8601String().split('T')[0],
             'date_end': _endDate.toIso8601String().split('T')[0],
          };
          final response = await _apiService.get(ApiConfig.kpiNotesStoreEndpoint, queryParams);
          if (mounted) {
              setState(() {
                  _storeNotes = response['notes'] ?? [];
                  _loadingStore = false;
              });
          }
      } catch (e) {
          if (mounted) setState(() => _loadingStore = false);
      }
  }

  Future<void> _loadIAProfiles() async {
      setState(() => _loadingProfiles = true);
      try {
          final response = await _apiService.get(ApiConfig.kpiIAProfilesEndpoint);
          if (mounted) {
              setState(() {
                  _iaProfiles = response['profiles'] ?? [];
                  _loadingProfiles = false;
              });
          }
      } catch (e) {
          if (mounted) setState(() => _loadingProfiles = false);
      }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/kpi',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        appBar: AppBar(
          backgroundColor: const Color(0xFF1E293B),
          elevation: 0,
          title: const Text("Dashboard KPI", style: TextStyle(fontWeight: FontWeight.bold)),
          bottom: TabBar(
            controller: _tabController,
            indicatorColor: const Color(0xFF3B82F6),
            tabs: const [
              Tab(icon: Icon(Icons.bar_chart), text: "Dashboard"),
              Tab(icon: Icon(Icons.note), text: "Notes Employés"),
              Tab(icon: Icon(Icons.store), text: "Notes Magasin"),
              Tab(icon: Icon(Icons.psychology), text: "Analyse IA"),
            ],
          ),
        ),
        floatingActionButton: _getFab(),
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildDashboardTab(),
            _buildEmployeeNotesTab(),
            _buildStoreNotesTab(),
            _buildIAProfilesTab(),
          ],
        ),
      ),
    );
  }

  Widget? _getFab() {
      // Use AnimatedBuilder or similar if tab index checking is needed dynamically, 
      // but setState is called on tab change via listener in initState ?? No I removed it.
      // Let's add listener back or use AnimatedBuilder. 
      // Actually tabController.index is not reactive.
      // I need to setState when tab changes.
      return AnimatedBuilder(
          animation: _tabController,
          builder: (context, child) {
              if (_tabController.index == 1) {
                  return FloatingActionButton.extended(
                      onPressed: _showAddEmployeeNoteDialog,
                      label: const Text("Ajouter Note Employé"),
                      icon: const Icon(Icons.add),
                      backgroundColor: Colors.blue,
                  );
              } else if (_tabController.index == 2) {
                  return FloatingActionButton.extended(
                      onPressed: _showAddStoreNoteDialog,
                      label: const Text("Ajouter Note Magasin"),
                      icon: const Icon(Icons.add),
                      backgroundColor: Colors.blue,
                  );
              }
              return const SizedBox.shrink();
          }
      );
  }

  void _showAddEmployeeNoteDialog() {
      final titleCtrl = TextEditingController();
      final descCtrl = TextEditingController();
      String severity = 'info';
      String type = 'remarque'; // avertissement, incident, appreciation, remarque, sanction, autre
      int? employeeId;
      DateTime date = DateTime.now();

      showDialog(
          context: context,
          builder: (context) => StatefulBuilder(
              builder: (context, setState) => AlertDialog(
                  backgroundColor: const Color(0xFF1E293B),
                  title: const Text("Nouvelle Note Employé", style: TextStyle(color: Colors.white)),
                  content: SingleChildScrollView(
                      child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                              // Employee Select
                             DropdownButtonFormField<int>(
                                dropdownColor: const Color(0xFF1E293B),
                                style: const TextStyle(color: Colors.white),
                                decoration: const InputDecoration(labelText: 'Employé', labelStyle: TextStyle(color: Colors.white70)),
                                items: _employees.map((e) => DropdownMenuItem<int>(
                                    value: int.tryParse(e['id'].toString()),
                                    child: Text(e['full_name'] ?? 'Inconnu'),
                                )).toList(),
                                onChanged: (v) => employeeId = v,
                             ),
                             const SizedBox(height: 16),
                             // Type
                             DropdownButtonFormField<String>(
                                 dropdownColor: const Color(0xFF1E293B),
                                 value: type,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Type', labelStyle: TextStyle(color: Colors.white70)),
                                 items: const [
                                     DropdownMenuItem(value: 'remarque', child: Text('Remarque')),
                                     DropdownMenuItem(value: 'appreciation', child: Text('Appréciation')),
                                     DropdownMenuItem(value: 'avertissement', child: Text('Avertissement')),
                                     DropdownMenuItem(value: 'incident', child: Text('Incident')),
                                     DropdownMenuItem(value: 'sanction', child: Text('Sanction')),
                                     DropdownMenuItem(value: 'autre', child: Text('Autre')),
                                 ],
                                 onChanged: (v) => setState(() => type = v!),
                             ),
                             const SizedBox(height: 16),
                             // Severity
                             DropdownButtonFormField<String>(
                                 dropdownColor: const Color(0xFF1E293B),
                                 value: severity,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Gravité', labelStyle: TextStyle(color: Colors.white70)),
                                 items: const [
                                     DropdownMenuItem(value: 'info', child: Text('Info (Neutre)')),
                                     DropdownMenuItem(value: 'low', child: Text('Faible')),
                                     DropdownMenuItem(value: 'medium', child: Text('Moyenne')),
                                     DropdownMenuItem(value: 'high', child: Text('Élevée')),
                                     DropdownMenuItem(value: 'critical', child: Text('Critique')),
                                 ],
                                 onChanged: (v) => setState(() => severity = v!),
                             ),
                             const SizedBox(height: 16),
                             TextField(
                                 controller: titleCtrl,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Titre', labelStyle: TextStyle(color: Colors.white70)),
                             ),
                             const SizedBox(height: 16),
                             TextField(
                                 controller: descCtrl,
                                 style: const TextStyle(color: Colors.white),
                                 maxLines: 3,
                                 decoration: const InputDecoration(labelText: 'Description', labelStyle: TextStyle(color: Colors.white70)),
                             ),
                          ],
                      ),
                  ),
                  actions: [
                      TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
                      ElevatedButton(
                          onPressed: () async {
                              if (employeeId == null || titleCtrl.text.isEmpty) return;
                              try {
                                  await _apiService.post(ApiConfig.kpiNotesEmployeesEndpoint, {
                                      'employee_id': employeeId,
                                      'note_type': type,
                                      'title': titleCtrl.text,
                                      'description': descCtrl.text,
                                      'date_incident': date.toIso8601String().split('T')[0],
                                      'severity': severity,
                                  });
                                  if (mounted) {
                                      Navigator.pop(context);
                                      _loadEmployeeNotes();
                                  }
                              } catch (e) {
                                  // Error handling
                              }
                          },
                          child: const Text("Créer"),
                      )
                  ],
              ),
          )
      );
  }

  void _showAddStoreNoteDialog() {
      final titleCtrl = TextEditingController();
      final descCtrl = TextEditingController();
      String impact = 'info';
      String type = 'autre'; 
      DateTime dateStart = DateTime.now();

      showDialog(
          context: context,
          builder: (context) => StatefulBuilder(
              builder: (context, setState) => AlertDialog(
                  backgroundColor: const Color(0xFF1E293B),
                  title: const Text("Nouvelle Note Magasin", style: TextStyle(color: Colors.white)),
                  content: SingleChildScrollView(
                      child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                             // Type
                             DropdownButtonFormField<String>(
                                 dropdownColor: const Color(0xFF1E293B),
                                 value: type,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Type', labelStyle: TextStyle(color: Colors.white70)),
                                 items: const [
                                     DropdownMenuItem(value: 'fermeture', child: Text('Fermeture')),
                                     DropdownMenuItem(value: 'travaux', child: Text('Travaux')),
                                     DropdownMenuItem(value: 'evenement', child: Text('Événement')),
                                     DropdownMenuItem(value: 'probleme_technique', child: Text('Problème Technique')),
                                     DropdownMenuItem(value: 'stock', child: Text('Stock / Appro')),
                                     DropdownMenuItem(value: 'autre', child: Text('Autre')),
                                 ],
                                 onChanged: (v) => setState(() => type = v!),
                             ),
                             const SizedBox(height: 16),
                             // Impact
                             DropdownButtonFormField<String>(
                                 dropdownColor: const Color(0xFF1E293B),
                                 value: impact,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Impact', labelStyle: TextStyle(color: Colors.white70)),
                                 items: const [
                                     DropdownMenuItem(value: 'info', child: Text('Info')),
                                     DropdownMenuItem(value: 'low', child: Text('Faible')),
                                     DropdownMenuItem(value: 'medium', child: Text('Moyen')),
                                     DropdownMenuItem(value: 'high', child: Text('Élevé')),
                                     DropdownMenuItem(value: 'critical', child: Text('Critique')),
                                 ],
                                 onChanged: (v) => setState(() => impact = v!),
                             ),
                             const SizedBox(height: 16),
                             TextField(
                                 controller: titleCtrl,
                                 style: const TextStyle(color: Colors.white),
                                 decoration: const InputDecoration(labelText: 'Titre', labelStyle: TextStyle(color: Colors.white70)),
                             ),
                             const SizedBox(height: 16),
                             TextField(
                                 controller: descCtrl,
                                 style: const TextStyle(color: Colors.white),
                                 maxLines: 3,
                                 decoration: const InputDecoration(labelText: 'Description', labelStyle: TextStyle(color: Colors.white70)),
                             ),
                          ],
                      ),
                  ),
                  actions: [
                      TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
                      ElevatedButton(
                          onPressed: () async {
                              if (titleCtrl.text.isEmpty) return;
                              try {
                                  await _apiService.post(ApiConfig.kpiNotesStoreEndpoint, {
                                      'note_type': type,
                                      'title': titleCtrl.text,
                                      'description': descCtrl.text,
                                      'date_start': dateStart.toIso8601String().split('T')[0],
                                      'impact_level': impact,
                                  });
                                  if (mounted) {
                                      Navigator.pop(context);
                                      _loadStoreNotes();
                                  }
                              } catch (e) {
                                  // Error handling
                              }
                          },
                          child: const Text("Créer"),
                      )
                  ],
              ),
          )
      );
  }

  Widget _buildDashboardTab() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final globalStr = _data['global_stats'] ?? <String, dynamic>{};
    final repStr = _data['reparations_stats'] ?? <String, dynamic>{};
    final chartData = (_data['chart_data'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
    final empData = (_data['employees_performance'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start, // Alignement gauche
        children: [
          // Filter Bar
          _buildFilterBar(),

          // KPI Cards
          Row(
            children: [
              Expanded(
                child: KpiStatCard(
                  label: "CA Encaissé",
                  value: "${globalStr['ca_encaisse'] ?? 0}",
                  subtext: "${globalStr['nb_restituees'] ?? 0} réparations",
                  icon: Icons.euro,
                  color: const Color(0xFF22c55e),
                  isCurrency: true,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: KpiStatCard(
                  label: "CA Total",
                  value: "${globalStr['ca_total'] ?? 0}",
                  subtext: "${globalStr['nb_total'] ?? 0} réparations",
                  icon: Icons.show_chart,
                  color: const Color(0xFF3b82f6),
                  isCurrency: true,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: KpiStatCard(
                  label: "Réparations",
                  value: "${globalStr['nb_reparations'] ?? 0}",
                  subtext: "${repStr['nb_nouvelles'] ?? 0} nouvelles",
                  icon: Icons.build,
                  color: const Color(0xFF06b6d4),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: KpiStatCard(
                  label: "Panier Moyen",
                  value: "${globalStr['panier_moyen'] ?? 0}",
                  subtext: "Sur encaissé",
                  icon: Icons.shopping_basket,
                  color: const Color(0xFFfbbf24),
                  isCurrency: true,
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 24),

          // Charts Row
          SizedBox(
            height: 350,
            child: Row(
              children: [
                // Line Chart
                Expanded(
                  flex: 2,
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Évolution Panier Moyen", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 20),
                        Expanded(child: KpiLineChart(data: chartData)),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 24),
                // Doughnut Chart
                Expanded(
                  flex: 1,
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Répartition", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 20),
                        Expanded(child: KpiDoughnutChart(stats: repStr)),
                        const SizedBox(height: 10),
                        // Legend
                        _buildLegendItem(const Color(0xFFfbbf24), "Nouvelles"),
                        _buildLegendItem(const Color(0xFF06b6d4), "En Cours"),
                        _buildLegendItem(const Color(0xFF22c55e), "Effectuées"),
                        _buildLegendItem(const Color(0xFF3b82f6), "Restituées"),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),

          // Employees Table
          const Text("Performance par Employé", style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          KpiEmployeeTable(employees: empData),
        ],
      ),
    );
  }

  Widget _buildFilterBar() {
      return Container(
        padding: const EdgeInsets.all(16),
        margin: const EdgeInsets.only(bottom: 24),
        decoration: BoxDecoration(
            color: const Color(0xFF1E293B),
            borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
            children: [
            // Date Range Picker (Simplified)
            TextButton.icon(
                onPressed: () async {
                final picked = await showDateRangePicker(
                    context: context,
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                    initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
                );
                if (picked != null) {
                    setState(() {
                    _startDate = picked.start;
                    _endDate = picked.end;
                    });
                    _loadDashboardData();
                    _loadEmployeeNotes(); // Refresh notes too
                }
                },
                icon: const Icon(Icons.calendar_today, color: Colors.white70),
                label: Text(
                "${_startDate.toIso8601String().split('T')[0]} - ${_endDate.toIso8601String().split('T')[0]}",
                style: const TextStyle(color: Colors.white),
                ),
            ),
            const SizedBox(width: 24),
            // Employee Dropdown
            DropdownButtonHideUnderline(
                child: DropdownButton<int>(
                value: _selectedEmployee,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: Colors.white),
                icon: const Icon(Icons.arrow_drop_down, color: Colors.white),
                onChanged: (val) {
                    if (val != null) {
                    setState(() => _selectedEmployee = val);
                    _loadDashboardData();
                    _loadEmployeeNotes(); // Refresh notes too
                    }
                },
                items: [
                    const DropdownMenuItem(value: 0, child: Text("Tous les employés")),
                    ..._employees.map((e) => DropdownMenuItem<int>(
                    value: int.tryParse(e['id'].toString()) ?? 0,
                    child: Text(e['full_name'] ?? 'Emp'),
                    )),
                ],
                ),
            ),
            const Spacer(),
            IconButton(
                icon: const Icon(Icons.refresh, color: Colors.blue),
                onPressed: () {
                    _loadDashboardData();
                    _loadEmployeeNotes();
                    _loadStoreNotes();
                },
            ),
            ],
        ),
      );
  }


  Widget _buildEmployeeNotesTab() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final notes = _data['employee_notes'] as List? ?? [];

    if (notes.isEmpty) {
      return const Center(child: Text("Aucune note employé trouvée", style: TextStyle(color: Colors.white70)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: notes.length,
      itemBuilder: (context, index) {
        final note = notes[index];
        final severityColor = _getSeverityColor(note['severity']);
        final date = note['date_incident'] ?? '';

        return Card(
          color: const Color(0xFF1E293B),
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: severityColor.withOpacity(0.2),
              child: Icon(_getSeverityIcon(note['severity']), color: severityColor),
            ),
            title: Text(
              "${note['title']} - ${note['employee_name'] ?? 'Inconnu'}",
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(note['description'] ?? '', style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 4),
                Text(
                  "Le $date • Par ${note['created_by_name'] ?? 'Inconnu'}",
                  style: const TextStyle(color: Colors.grey, fontSize: 12),
                ),
              ],
            ),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  icon: const Icon(Icons.edit, color: Colors.blue),
                  onPressed: () => _showEditEmployeeNoteDialog(note),
                ),
                IconButton(
                  icon: const Icon(Icons.delete, color: Colors.red),
                  onPressed: () => _deleteEmployeeNote(note['id']),
                ),
              ],
            ),
          ),
        );
      },
    );
  }


  IconData _getIconForNoteType(String? type) {
      switch(type) {
          case 'avertissement': return Icons.warning;
          case 'incident': return Icons.error_outline;
          case 'appreciation': return Icons.thumb_up;
          case 'remarque': return Icons.info_outline;
          default: return Icons.note;
      }
  }

  Color _getColorForSeverity(String? severity) {
      switch(severity) {
          case 'critical': return Colors.red;
          case 'high': return Colors.deepOrange;
          case 'medium': return Colors.orange;
          case 'info': return Colors.blue;
          default: return Colors.grey;
      }
  }

  IconData _getSeverityIcon(String? severity) {
      switch(severity) {
          case 'critical': return Icons.error;
          case 'high': return Icons.warning_amber;
          case 'medium': return Icons.info;
          case 'low': return Icons.check_circle_outline;
          case 'info': return Icons.info_outline;
          default: return Icons.note;
      }
  }

  Color _getSeverityColor(String? severity) {
      switch(severity) {
          case 'critical': return Colors.red;
          case 'high': return Colors.deepOrange;
          case 'medium': return Colors.orange;
          case 'low': return Colors.yellow.shade700;
          case 'info': return Colors.blue;
          default: return Colors.grey;
      }
  }

  IconData _getTypeIcon(String? type) {
      switch(type) {
          case 'fermeture': return Icons.lock;
          case 'travaux': return Icons.construction;
          case 'evenement': return Icons.event;
          case 'probleme_technique': return Icons.build;
          case 'stock': return Icons.inventory;
          default: return Icons.store;
      }
  }

  Color _getImpactColor(String? impact) {
      switch(impact) {
          case 'critical': return Colors.red;
          case 'high': return Colors.deepOrange;
          case 'medium': return Colors.orange;
          case 'low': return Colors.yellow.shade700;
          case 'info': return Colors.blue;
          default: return Colors.grey;
      }
  }

  // --- Store Notes Tab ---
  Widget _buildStoreNotesTab() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final notes = _data['store_notes'] as List? ?? [];

    if (notes.isEmpty) {
      return const Center(child: Text("Aucune note magasin trouvée", style: TextStyle(color: Colors.white70)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: notes.length,
      itemBuilder: (context, index) {
        final note = notes[index];
        final impactColor = _getImpactColor(note['impact_level']);
        final dateStart = note['date_start'] ?? '';

        return Card(
          color: const Color(0xFF1E293B),
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: impactColor.withOpacity(0.2),
              child: Icon(_getTypeIcon(note['note_type']), color: impactColor),
            ),
            title: Text(
              note['title'] ?? 'Sans titre',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(note['description'] ?? '', style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 4),
                Text(
                  "Depuis le $dateStart • Par ${note['created_by_name'] ?? 'Inconnu'}",
                  style: const TextStyle(color: Colors.grey, fontSize: 12),
                ),
              ],
            ),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  icon: const Icon(Icons.edit, color: Colors.blue),
                  onPressed: () => _showEditStoreNoteDialog(note),
                ),
                IconButton(
                  icon: const Icon(Icons.delete, color: Colors.red),
                  onPressed: () => _deleteStoreNote(note['id']),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  // --- IA Profiles Tab ---
  Widget _buildIAProfilesTab() {
    if (_loadingProfiles) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_iaProfiles.isEmpty) {
      return const Center(child: Text("Aucun profil IA trouvé", style: TextStyle(color: Colors.white70)));
    }

    return GridView.builder(
      padding: const EdgeInsets.all(12),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 5,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        childAspectRatio: 1.3,
      ),
      itemCount: _iaProfiles.length,
      itemBuilder: (context, index) {
        final profile = _iaProfiles[index];
        return Card(
          color: const Color(0xFF1E293B),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: BorderSide(color: Colors.blue.withOpacity(0.3))),
          child: InkWell(
            onTap: () => _showIAProfileDetails(profile),
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircleAvatar(
                    radius: 22,
                    backgroundColor: Colors.blue.withOpacity(0.2),
                    child: Icon(_getIconData(profile['icon']), size: 22, color: Colors.blue),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    profile['name'] ?? 'Inconnu',
                    style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 13),
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    profile['description'] ?? '',
                    style: const TextStyle(color: Colors.white70, fontSize: 10),
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: (profile['active'] == 1 || profile['active'] == true)
                          ? Colors.green.withOpacity(0.2)
                          : Colors.grey.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      (profile['active'] == 1 || profile['active'] == true) ? "ACTIF" : "INACTIF",
                      style: TextStyle(
                        color: (profile['active'] == 1 || profile['active'] == true)
                            ? Colors.green
                            : Colors.grey,
                        fontSize: 9,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  )
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  void _showIAProfileDetails(Map<String, dynamic> profile) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => _AIAnalysisModal(
        profile: profile,
        apiService: _apiService,
        employees: _employees,
        kpiData: _data,
        dateStart: _startDate,
        dateEnd: _endDate,
      ),
    );
  }

  IconData _getIconData(String? iconClass) {
    if (iconClass == null) return Icons.smart_toy;
    if (iconClass.contains('chart')) return Icons.assessment;
    if (iconClass.contains('user')) return Icons.person;
    if (iconClass.contains('robot')) return Icons.smart_toy;
    if (iconClass.contains('brain')) return Icons.psychology;
    if (iconClass.contains('trophy')) return Icons.emoji_events;
    if (iconClass.contains('briefcase')) return Icons.work;
    if (iconClass.contains('calculator')) return Icons.calculate;
    if (iconClass.contains('dollar')) return Icons.attach_money;
    if (iconClass.contains('exclamation')) return Icons.warning;
    return Icons.smart_toy;
  }

  Future<void> _deleteEmployeeNote(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text("Supprimer", style: TextStyle(color: Colors.white)),
        content: const Text("Voulez-vous vraiment supprimer cette note ?", style: TextStyle(color: Colors.white70)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text("Annuler")),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text("Supprimer", style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (confirm == true) {
      try {
        await _apiService.delete("${ApiConfig.kpiNotesEmployeesEndpoint}?id=$id");
        _loadEmployeeNotes();
      } catch (e) { /* Handle error */ }
    }
  }

  Future<void> _deleteStoreNote(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text("Supprimer", style: TextStyle(color: Colors.white)),
        content: const Text("Voulez-vous vraiment supprimer cette note ?", style: TextStyle(color: Colors.white70)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text("Annuler")),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text("Supprimer", style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (confirm == true) {
      try {
        await _apiService.delete("${ApiConfig.kpiNotesStoreEndpoint}?id=$id");
        _loadStoreNotes();
      } catch (e) { /* Handle error */ }
    }
  }

  void _showEditEmployeeNoteDialog(Map<String, dynamic> note) {
    final titleCtrl = TextEditingController(text: note['title']);
    final descCtrl = TextEditingController(text: note['description']);
    String severity = note['severity'] ?? 'info';
    String type = note['note_type'] ?? 'remarque';

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          title: const Text("Modifier Note Employé", style: TextStyle(color: Colors.white)),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<String>(
                  dropdownColor: const Color(0xFF1E293B),
                  value: type,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(labelText: 'Type', labelStyle: TextStyle(color: Colors.white70)),
                  items: const [
                    DropdownMenuItem(value: 'remarque', child: Text('Remarque')),
                    DropdownMenuItem(value: 'appreciation', child: Text('Appréciation')),
                    DropdownMenuItem(value: 'avertissement', child: Text('Avertissement')),
                    DropdownMenuItem(value: 'incident', child: Text('Incident')),
                  ],
                  onChanged: (v) => setState(() => type = v!),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  dropdownColor: const Color(0xFF1E293B),
                  value: severity,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(labelText: 'Gravité', labelStyle: TextStyle(color: Colors.white70)),
                  items: const [
                    DropdownMenuItem(value: 'info', child: Text('Info')),
                    DropdownMenuItem(value: 'low', child: Text('Faible')),
                    DropdownMenuItem(value: 'medium', child: Text('Moyenne')),
                    DropdownMenuItem(value: 'high', child: Text('Élevée')),
                    DropdownMenuItem(value: 'critical', child: Text('Critique')),
                  ],
                  onChanged: (v) => setState(() => severity = v!),
                ),
                const SizedBox(height: 16),
                TextField(controller: titleCtrl, style: const TextStyle(color: Colors.white), decoration: const InputDecoration(labelText: 'Titre', labelStyle: TextStyle(color: Colors.white70))),
                const SizedBox(height: 16),
                TextField(controller: descCtrl, style: const TextStyle(color: Colors.white), maxLines: 3, decoration: const InputDecoration(labelText: 'Description', labelStyle: TextStyle(color: Colors.white70))),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
            ElevatedButton(
              onPressed: () async {
                try {
                  await _apiService.put(ApiConfig.kpiNotesEmployeesEndpoint, {'id': note['id'], 'note_type': type, 'title': titleCtrl.text, 'description': descCtrl.text, 'severity': severity});
                  if (mounted) { Navigator.pop(context); _loadEmployeeNotes(); }
                } catch (e) { /* Error handling */ }
              },
              child: const Text("Enregistrer"),
            ),
          ],
        ),
      ),
    );
  }

  void _showEditStoreNoteDialog(Map<String, dynamic> note) {
    final titleCtrl = TextEditingController(text: note['title']);
    final descCtrl = TextEditingController(text: note['description']);
    String impact = note['impact_level'] ?? 'info';
    String type = note['note_type'] ?? 'autre';

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          title: const Text("Modifier Note Magasin", style: TextStyle(color: Colors.white)),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<String>(
                  dropdownColor: const Color(0xFF1E293B),
                  value: type,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(labelText: 'Type', labelStyle: TextStyle(color: Colors.white70)),
                  items: const [
                    DropdownMenuItem(value: 'fermeture', child: Text('Fermeture')),
                    DropdownMenuItem(value: 'travaux', child: Text('Travaux')),
                    DropdownMenuItem(value: 'evenement', child: Text('Événement')),
                    DropdownMenuItem(value: 'autre', child: Text('Autre')),
                  ],
                  onChanged: (v) => setState(() => type = v!),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  dropdownColor: const Color(0xFF1E293B),
                  value: impact,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(labelText: 'Impact', labelStyle: TextStyle(color: Colors.white70)),
                  items: const [
                    DropdownMenuItem(value: 'info', child: Text('Info')),
                    DropdownMenuItem(value: 'low', child: Text('Faible')),
                    DropdownMenuItem(value: 'medium', child: Text('Moyen')),
                    DropdownMenuItem(value: 'high', child: Text('Élevé')),
                    DropdownMenuItem(value: 'critical', child: Text('Critique')),
                  ],
                  onChanged: (v) => setState(() => impact = v!),
                ),
                const SizedBox(height: 16),
                TextField(controller: titleCtrl, style: const TextStyle(color: Colors.white), decoration: const InputDecoration(labelText: 'Titre', labelStyle: TextStyle(color: Colors.white70))),
                const SizedBox(height: 16),
                TextField(controller: descCtrl, style: const TextStyle(color: Colors.white), maxLines: 3, decoration: const InputDecoration(labelText: 'Description', labelStyle: TextStyle(color: Colors.white70))),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
            ElevatedButton(
              onPressed: () async {
                try {
                  await _apiService.put(ApiConfig.kpiNotesStoreEndpoint, {'id': note['id'], 'note_type': type, 'title': titleCtrl.text, 'description': descCtrl.text, 'impact_level': impact});
                  if (mounted) { Navigator.pop(context); _loadStoreNotes(); }
                } catch (e) { /* Error handling */ }
              },
              child: const Text("Enregistrer"),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLegendItem(Color color, String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Container(width: 12, height: 12, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 8),
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
        ],
      ),
    );
  }
}

// --- AI Analysis Modal Widget ---
class _AIAnalysisModal extends StatefulWidget {
  final Map<String, dynamic> profile;
  final ApiService apiService;
  final List<dynamic> employees;
  final Map<String, dynamic> kpiData;
  final DateTime dateStart;
  final DateTime dateEnd;

  const _AIAnalysisModal({
    required this.profile,
    required this.apiService,
    required this.employees,
    required this.kpiData,
    required this.dateStart,
    required this.dateEnd,
  });

  @override
  State<_AIAnalysisModal> createState() => _AIAnalysisModalState();
}

class _AIAnalysisModalState extends State<_AIAnalysisModal> {
  String _step = 'choice'; // choice, kpi_selection, loading, result
  String? _analysisResult;
  String? _error;
  int? _selectedEmployeeId;
  String? _selectedEmployeeName;
  String _analysisType = 'global'; // global or employee
  final TextEditingController _followUpController = TextEditingController();
  bool _sendingFollowUp = false;
  List<Map<String, String>> _chatHistory = [];
  
  // KPI Selection
  final Set<String> _selectedKpis = {
    'ca_encaisse', 'ca_total', 'panier_moyen', 
    'nb_reparations', 'nb_effectuees', 'nb_restituees',
  }; // Default selected
  
  final List<Map<String, dynamic>> _availableKpis = [
    {'key': 'ca_encaisse', 'title': 'CA Encaissé', 'icon': Icons.attach_money, 'category': 'Finances'},
    {'key': 'ca_total', 'title': 'CA Total', 'icon': Icons.account_balance_wallet, 'category': 'Finances'},
    {'key': 'panier_moyen', 'title': 'Panier Moyen', 'icon': Icons.shopping_cart, 'category': 'Finances'},
    {'key': 'nb_reparations', 'title': 'Nouvelles Réparations', 'icon': Icons.add_circle, 'category': 'Réparations'},
    {'key': 'nb_effectuees', 'title': 'Réparations Effectuées', 'icon': Icons.build, 'category': 'Réparations'},
    {'key': 'nb_restituees', 'title': 'Réparations Restituées', 'icon': Icons.check_circle, 'category': 'Réparations'},
    {'key': 'temps_moyen', 'title': 'Temps Moyen Réparation', 'icon': Icons.timer, 'category': 'Temps'},
    {'key': 'performance', 'title': 'Performance Employés', 'icon': Icons.trending_up, 'category': 'Performance'},
    {'key': 'notes_employes', 'title': 'Notes Employés', 'icon': Icons.note, 'category': 'Notes'},
  ];

  Future<void> _generateAnalysis({int? employeeId}) async {
    setState(() {
      _step = 'loading';
      _error = null;
    });

    try {
      final response = await widget.apiService.post(ApiConfig.kpiGenerateAnalysisEndpoint, {
        'profile_id': widget.profile['id'],
        'kpi_data': widget.kpiData,
        'employee_id': employeeId,
        'date_start': widget.dateStart.toIso8601String().split('T')[0],
        'date_end': widget.dateEnd.toIso8601String().split('T')[0],
      });

      if (mounted) {
        setState(() {
          _analysisResult = response['analysis'] ?? 'Aucune analyse générée';
          _step = 'result';
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _step = 'result';
        });
      }
    }
  }

  Future<void> _sendFollowUp() async {
    final message = _followUpController.text.trim();
    if (message.isEmpty) return;

    setState(() => _sendingFollowUp = true);

    try {
      final response = await widget.apiService.post(ApiConfig.kpiGenerateAnalysisEndpoint, {
        'profile_id': widget.profile['id'],
        'kpi_data': widget.kpiData,
        'date_start': widget.dateStart.toIso8601String().split('T')[0],
        'date_end': widget.dateEnd.toIso8601String().split('T')[0],
        'follow_up_message': message,
        'previous_analysis': _analysisResult ?? '',
      });

      if (mounted) {
        final aiResponse = response['analysis'] ?? 'Pas de réponse';
        setState(() {
          _chatHistory.add({'user': message, 'ai': aiResponse});
          _analysisResult = aiResponse;
          _followUpController.clear();
          _sendingFollowUp = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _sendingFollowUp = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: const Color(0xFF1E293B),
      contentPadding: const EdgeInsets.all(24),
      title: Row(
        children: [
          CircleAvatar(
            backgroundColor: Colors.blue.withOpacity(0.2),
            child: Icon(_getIconData(widget.profile['icon']), color: Colors.blue),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.profile['name'] ?? 'Analyse IA', 
                    style: const TextStyle(color: Colors.white, fontSize: 18)),
                Text(widget.profile['description'] ?? '', 
                    style: const TextStyle(color: Colors.white54, fontSize: 12),
                    maxLines: 1, overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.close, color: Colors.white54),
            onPressed: () => Navigator.pop(context),
          ),
        ],
      ),
      content: SizedBox(
        width: 600,
        height: 450,
        child: _buildStepContent(),
      ),
    );
  }

  Widget _buildStepContent() {
    switch (_step) {
      case 'choice':
        return _buildChoiceStep();
      case 'kpi_selection':
        return _buildKPISelectionStep();
      case 'loading':
        return _buildLoadingStep();
      case 'result':
        return _buildResultStep();
      default:
        return _buildChoiceStep();
    }
  }

  Widget _buildChoiceStep() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.psychology, size: 64, color: Colors.blue),
        const SizedBox(height: 24),
        const Text(
          "Quel type d'analyse souhaitez-vous ?",
          style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 32),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _buildChoiceCard(
              icon: Icons.bar_chart,
              title: "Analyse Globale",
              subtitle: "Vue d'ensemble des KPI",
              onTap: () {
                setState(() {
                  _analysisType = 'global';
                  _selectedEmployeeId = null;
                  _selectedEmployeeName = null;
                  _step = 'kpi_selection';
                });
              },
            ),
            const SizedBox(width: 24),
            _buildChoiceCard(
              icon: Icons.person,
              title: "Par Employé",
              subtitle: "Analyse individuelle",
              onTap: () => _showEmployeeSelector(),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildChoiceCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        width: 180,
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: const Color(0xFF0F172A),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.blue.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Icon(icon, size: 48, color: Colors.blue),
            const SizedBox(height: 16),
            Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(color: Colors.white54, fontSize: 12), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildKPISelectionStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.arrow_back, color: Colors.white54),
              onPressed: () => setState(() => _step = 'choice'),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _analysisType == 'global' 
                      ? "Analyse Globale" 
                      : "Analyse: $_selectedEmployeeName",
                    style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const Text(
                    "Sélectionnez les KPIs à analyser",
                    style: TextStyle(color: Colors.white54, fontSize: 12),
                  ),
                ],
              ),
            ),
            TextButton(
              onPressed: () {
                setState(() {
                  for (var kpi in _availableKpis) {
                    _selectedKpis.add(kpi['key']);
                  }
                });
              },
              child: const Text("Tout sélectionner"),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // KPI Grid
        Expanded(
          child: GridView.builder(
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
              childAspectRatio: 2.0,
            ),
            itemCount: _availableKpis.length,
            itemBuilder: (context, index) {
              final kpi = _availableKpis[index];
              final isSelected = _selectedKpis.contains(kpi['key']);
              return InkWell(
                onTap: () {
                  setState(() {
                    if (isSelected) {
                      _selectedKpis.remove(kpi['key']);
                    } else {
                      _selectedKpis.add(kpi['key']);
                    }
                  });
                },
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: isSelected 
                      ? Colors.blue.withOpacity(0.3) 
                      : const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isSelected ? Colors.blue : Colors.white24,
                      width: isSelected ? 2 : 1,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        isSelected ? Icons.check_circle : (kpi['icon'] as IconData),
                        color: isSelected ? Colors.blue : Colors.white54,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              kpi['title'],
                              style: TextStyle(
                                color: isSelected ? Colors.blue : Colors.white,
                                fontSize: 12,
                                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            Text(
                              kpi['category'],
                              style: const TextStyle(color: Colors.white38, fontSize: 10),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 12),
        // Launch button
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            icon: const Icon(Icons.psychology, color: Colors.white),
            label: Text(
              "Lancer l'analyse (${_selectedKpis.length} KPIs)",
              style: const TextStyle(color: Colors.white, fontSize: 16),
            ),
            onPressed: _selectedKpis.isEmpty 
              ? null 
              : () => _generateAnalysis(employeeId: _selectedEmployeeId),
          ),
        ),
      ],
    );
  }

  void _showEmployeeSelector() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text("Sélectionner un employé", style: TextStyle(color: Colors.white)),
        content: SizedBox(
          width: 300,
          height: 300,
          child: ListView.builder(
            itemCount: widget.employees.length,
            itemBuilder: (context, index) {
              final emp = widget.employees[index];
              return ListTile(
                leading: CircleAvatar(
                  backgroundColor: Colors.blue.withOpacity(0.2),
                  child: Text(
                    (emp['full_name'] ?? 'E')[0].toUpperCase(),
                    style: const TextStyle(color: Colors.blue),
                  ),
                ),
                title: Text(emp['full_name'] ?? 'Inconnu', style: const TextStyle(color: Colors.white)),
                subtitle: Text(emp['role'] ?? '', style: const TextStyle(color: Colors.white54)),
                onTap: () {
                  Navigator.pop(ctx);
                  setState(() {
                    _analysisType = 'employee';
                    _selectedEmployeeId = int.tryParse(emp['id'].toString());
                    _selectedEmployeeName = emp['full_name'];
                    _step = 'kpi_selection';
                  });
                },
              );
            },
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text("Annuler")),
        ],
      ),
    );
  }

  Widget _buildLoadingStep() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const SizedBox(
          width: 80,
          height: 80,
          child: CircularProgressIndicator(strokeWidth: 3, color: Colors.blue),
        ),
        const SizedBox(height: 32),
        const Text(
          "Génération de l'analyse en cours...",
          style: TextStyle(color: Colors.white, fontSize: 18),
        ),
        const SizedBox(height: 8),
        Text(
          "L'IA ${widget.profile['name']} analyse vos données",
          style: const TextStyle(color: Colors.white54),
        ),
      ],
    );
  }

  Widget _buildResultStep() {
    if (_error != null) {
      return Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 64, color: Colors.red),
          const SizedBox(height: 16),
          Text("Erreur: $_error", style: const TextStyle(color: Colors.red)),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () => setState(() => _step = 'choice'),
            child: const Text("Réessayer"),
          ),
        ],
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.check_circle, color: Colors.green),
            const SizedBox(width: 8),
            const Text("Analyse générée", style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
            const Spacer(),
            TextButton.icon(
              icon: const Icon(Icons.refresh, size: 16),
              label: const Text("Nouvelle analyse"),
              onPressed: () => setState(() => _step = 'choice'),
            ),
          ],
        ),
        const Divider(color: Colors.white24),
        Expanded(
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Chat history
                ..._chatHistory.map((msg) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        margin: const EdgeInsets.only(bottom: 8),
                        decoration: BoxDecoration(
                          color: Colors.blue.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.person, size: 16, color: Colors.blue),
                            const SizedBox(width: 8),
                            Expanded(child: Text(msg['user'] ?? '', style: const TextStyle(color: Colors.white))),
                          ],
                        ),
                      ),
                      SelectableText(msg['ai'] ?? '', style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.6)),
                    ],
                  ),
                )),
                // Current analysis
                if (_chatHistory.isEmpty)
                  SelectableText(
                    _analysisResult ?? '',
                    style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.6),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        // Follow-up input
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFF0F172A),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.blue.withOpacity(0.3)),
          ),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _followUpController,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(
                    hintText: "Posez une question de suivi...",
                    hintStyle: TextStyle(color: Colors.white38),
                    border: InputBorder.none,
                    contentPadding: EdgeInsets.symmetric(horizontal: 12),
                  ),
                  onSubmitted: (_) => _sendFollowUp(),
                ),
              ),
              _sendingFollowUp
                ? const Padding(
                    padding: EdgeInsets.all(8),
                    child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2)),
                  )
                : IconButton(
                    icon: const Icon(Icons.send, color: Colors.blue),
                    onPressed: _sendFollowUp,
                  ),
            ],
          ),
        ),
      ],
    );
  }

  IconData _getIconData(String? iconClass) {
    if (iconClass == null) return Icons.smart_toy;
    if (iconClass.contains('chart')) return Icons.assessment;
    if (iconClass.contains('user')) return Icons.person;
    if (iconClass.contains('robot')) return Icons.smart_toy;
    if (iconClass.contains('brain')) return Icons.psychology;
    if (iconClass.contains('trophy')) return Icons.emoji_events;
    if (iconClass.contains('briefcase')) return Icons.work;
    if (iconClass.contains('calculator')) return Icons.calculate;
    if (iconClass.contains('dollar')) return Icons.attach_money;
    if (iconClass.contains('exclamation')) return Icons.warning;
    return Icons.smart_toy;
  }
}

