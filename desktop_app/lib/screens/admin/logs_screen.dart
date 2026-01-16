import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../../widgets/log_card.dart';

class LogsScreen extends StatefulWidget {
  const LogsScreen({super.key});

  @override
  State<LogsScreen> createState() => _LogsScreenState();
}

class _LogsScreenState extends State<LogsScreen> {
  final ApiService _apiService = ApiService();
  final ScrollController _scrollController = ScrollController();
  
  List<dynamic> _logs = [];
  bool _isLoading = true;
  int _currentPage = 1;
  int _totalPages = 1;
  
  // Filters
  String _selectedType = 'all';
  int _selectedEmployee = 0;
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  List<dynamic> _employees = []; // To function filter

  @override
  void initState() {
    super.initState();
    _loadData();
    _loadEmployees();
  }

  Future<void> _loadEmployees() async {
    // Assuming we can reuse employees list or fetch it lightly.
    // For now we will just populate it if we had the endpoint.
    // Let's rely on a separate specific call if needed, or simplistic approach.
    try {
      final response = await _apiService.get(ApiConfig.employeesListEndpoint);
      if (mounted) {
        setState(() {
          _employees = response['employees'] ?? [];
        });
      }
    } catch (_) {}
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _logs = [];
    }
    
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '20',
        'log_type': _selectedType,
        'employe_id': _selectedEmployee.toString(),
        'q': _searchQuery,
      };
      
      final response = await _apiService.get(ApiConfig.logsListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          final newLogs = response['logs'] as List? ?? [];
          if (refresh) {
            _logs = newLogs;
          } else {
            _logs.addAll(newLogs);
          }
          
          final meta = response['meta'] ?? {};
          _totalPages = meta['total_pages'] ?? 1;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/logs',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: Column(
          children: [
            _buildHeader(),
            _buildFilters(),
            Expanded(
              child: _logs.isEmpty && !_isLoading
                  ? const Center(child: Text('Aucun log trouvé', style: TextStyle(color: Colors.grey)))
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(32),
                      itemCount: _logs.length + (_isLoading ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index == _logs.length) {
                          return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()));
                        }
                        
                        // Pagination logic could go here (detect end of list)
                        if (index == _logs.length - 1 && _currentPage < _totalPages && !_isLoading) {
                           _currentPage++;
                           _loadData();
                        }

                        return LogCard(log: _logs[index]);
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        border: Border(bottom: BorderSide(color: Colors.white.withOpacity(0.05))),
      ),
      child: Row(
        children: [
          const Icon(Icons.history, size: 32, color: Color(0xFF3B82F6)),
          const SizedBox(width: 16),
          Column(
             crossAxisAlignment: CrossAxisAlignment.start,
             children: [
               const Text(
                 'Logs d\'Activité',
                 style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white),
               ),
               Text(
                 'Suivez toutes les actions réalisées sur l\'application',
                 style: TextStyle(color: Colors.grey[400], fontSize: 13),
               ),
             ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
      color: const Color(0xFF0F172A),
      child: Row(
        children: [
          // Search
          Expanded(
            child: TextField(
              controller: _searchController,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                filled: true,
                fillColor: Colors.white.withOpacity(0.05),
                hintText: 'Rechercher...',
                hintStyle: TextStyle(color: Colors.grey[500]),
                prefixIcon: Icon(Icons.search, color: Colors.grey[500]),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16),
              ),
              onSubmitted: (value) {
                setState(() => _searchQuery = value);
                _loadData(refresh: true);
              },
            ),
          ),
          const SizedBox(width: 16),
          
          // Type Log Dropdown
          _buildDropdown(
            value: _selectedType,
            items: const [
              DropdownMenuItem(value: 'all', child: Text('Tous les types')),
              DropdownMenuItem(value: 'reparations', child: Text('Réparations')),
              DropdownMenuItem(value: 'taches', child: Text('Tâches')),
            ],
            onChanged: (val) {
              if (val != null) {
                setState(() => _selectedType = val);
                _loadData(refresh: true);
              }
            },
          ),
          
          const SizedBox(width: 16),
          
          // Employee Dropdown
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                value: _selectedEmployee,
                dropdownColor: const Color(0xFF1E293B),
                style: const TextStyle(color: Colors.white),
                icon: const Icon(Icons.arrow_drop_down, color: Colors.white),
                onChanged: (val) {
                  if (val != null) {
                    setState(() => _selectedEmployee = val);
                    _loadData(refresh: true);
                  }
                },
                items: [
                  const DropdownMenuItem(value: 0, child: Text('Tous les employés')),
                  ..._employees.map((e) => DropdownMenuItem<int>(
                    value: int.tryParse(e['id'].toString()) ?? 0,
                    child: Text(e['full_name'] ?? e['username']),
                  )),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown({required String value, required List<DropdownMenuItem<String>> items, required Function(String?) onChanged}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          dropdownColor: const Color(0xFF1E293B),
          style: const TextStyle(color: Colors.white),
          icon: const Icon(Icons.arrow_drop_down, color: Colors.white),
          onChanged: onChanged,
          items: items,
        ),
      ),
    );
  }
}
