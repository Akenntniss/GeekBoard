import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../../widgets/kpi_widgets.dart';

class KpiScreen extends StatefulWidget {
  const KpiScreen({super.key});

  @override
  State<KpiScreen> createState() => _KpiScreenState();
}

class _KpiScreenState extends State<KpiScreen> with SingleTickerProviderStateMixin {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  Map<String, dynamic> _data = {};

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
          _data = response ?? {};
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() { 
          _isLoading = false; 
        });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur chargement KPI: $e')));
      }
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
              Tab(icon: Icon(Icons.psychology), text: "Profils IA"),
            ],
          ),
        ),
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildDashboardTab(),
            _buildPlaceholderTab("Gestion des Notes Employés (À venir)"),
            _buildPlaceholderTab("Gestion des Notes Magasin (À venir)"),
            _buildPlaceholderTab("Profils IA & Analyse (À venir)"),
          ],
        ),
      ),
    );
  }

  Widget _buildDashboardTab() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    final globalStr = _data['global_stats'] ?? {};
    final repStr = _data['reparations_stats'] ?? {};
    final chartData = (_data['chart_data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final empData = (_data['employees_performance'] as List?) ?? [];

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Filter Bar
          Container(
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
                  onPressed: _loadDashboardData,
                ),
              ],
            ),
          ),

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

  Widget _buildPlaceholderTab(String title) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.construction, size: 64, color: Colors.grey),
          const SizedBox(height: 16),
          Text(title, style: const TextStyle(color: Colors.grey, fontSize: 20)),
        ],
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
