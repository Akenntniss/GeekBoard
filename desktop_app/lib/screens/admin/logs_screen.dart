import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/log_card.dart';

class LogsScreen extends StatefulWidget {
  const LogsScreen({super.key});

  @override
  State<LogsScreen> createState() => _LogsScreenState();
}

class _LogsScreenState extends State<LogsScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
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
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9);
    
    return AppShell(
      currentRoute: '/logs',
      content: Scaffold(
        backgroundColor: backgroundColor,
        body: Column(
          children: [
            _buildHeader(isDark),
            _buildFilters(isDark),
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

  Widget _buildHeader(bool isDark) {
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200;

    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: cardColor,
        border: Border(bottom: BorderSide(color: borderColor)),
      ),
      child: Row(
        children: [
          const Icon(Icons.history, size: 32, color: Color(0xFF3B82F6)),
          const SizedBox(width: 16),
          Column(
             crossAxisAlignment: CrossAxisAlignment.start,
             children: [
               Text(
                 'Logs d\'Activité',
                 style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: textColor),
               ),
               Text(
                 'Suivez toutes les actions réalisées sur l\'application',
                 style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontSize: 13),
               ),
             ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilters(bool isDark) {
    final bgColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9);
    final inputFill = isDark ? Colors.white.withOpacity(0.05) : Colors.white;
    final hintColor = isDark ? Colors.grey[500] : Colors.grey[600];
    final textColor = isDark ? Colors.white : Colors.black87;
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
      color: bgColor,
      child: Row(
        children: [
          // Search
          Expanded(
            child: TextField(
              controller: _searchController,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                filled: true,
                fillColor: inputFill,
                hintText: 'Rechercher...',
                hintStyle: TextStyle(color: hintColor),
                prefixIcon: Icon(Icons.search, color: hintColor),
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
            isDark: isDark,
            items: [
              DropdownMenuItem(value: 'all', child: Text('Tous les types', style: TextStyle(color: textColor))),
              DropdownMenuItem(value: 'reparations', child: Text('Réparations', style: TextStyle(color: textColor))),
              DropdownMenuItem(value: 'taches', child: Text('Tâches', style: TextStyle(color: textColor))),
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
              color: inputFill,
              borderRadius: BorderRadius.circular(12),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                value: _selectedEmployee,
                dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                style: TextStyle(color: textColor),
                icon: Icon(Icons.arrow_drop_down, color: isDark ? Colors.white : Colors.grey),
                onChanged: (val) {
                  if (val != null) {
                    setState(() => _selectedEmployee = val);
                    _loadData(refresh: true);
                  }
                },
                items: [
                  DropdownMenuItem(value: 0, child: Text('Tous les employés', style: TextStyle(color: textColor))),
                  ..._employees.map((e) => DropdownMenuItem<int>(
                    value: int.tryParse(e['id'].toString()) ?? 0,
                    child: Text(e['full_name'] ?? e['username'], style: TextStyle(color: textColor)),
                  )),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown({required String value, required List<DropdownMenuItem<String>> items, required Function(String?) onChanged, required bool isDark}) {
    final inputFill = isDark ? Colors.white.withOpacity(0.05) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: inputFill,
        borderRadius: BorderRadius.circular(12),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
          style: TextStyle(color: textColor),
          icon: Icon(Icons.arrow_drop_down, color: isDark ? Colors.white : Colors.grey),
          onChanged: onChanged,
          items: items,
        ),
      ),
    );
  }
}
