import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/employee_card.dart';

class EmployeesScreen extends StatefulWidget {
  const EmployeesScreen({super.key});

  @override
  State<EmployeesScreen> createState() => _EmployeesScreenState();
}

class _EmployeesScreenState extends State<EmployeesScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  List<dynamic> _employees = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final response = await _apiService.get(ApiConfig.employeesListEndpoint);
      if (mounted) {
        setState(() {
          _employees = response['employees'] ?? [];
          _stats = response['stats'] ?? {};
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
           SnackBar(content: Text('Erreur: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/employees',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: Column(
          children: [
             _buildHeader(),
             Expanded(
               child: _isLoading 
                 ? const Center(child: CircularProgressIndicator()) 
                 : GridView.builder(
                     padding: const EdgeInsets.all(32),
                     gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                       maxCrossAxisExtent: 400,
                       childAspectRatio: 0.85,
                       crossAxisSpacing: 24,
                       mainAxisSpacing: 24,
                     ),
                     itemCount: _employees.length,
                     itemBuilder: (context, index) {
                       return EmployeeCard(
                         employee: _employees[index],
                         onTap: () {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Profil non disponible dans la démo')),
                            );
                         },
                       );
                     },
                   ),
             ),
          ],
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () {
             ScaffoldMessenger.of(context).showSnackBar(
               const SnackBar(content: Text('Ajout non disponible dans la démo')),
             );
          },
          backgroundColor: const Color(0xFF3B82F6),
          icon: const Icon(Icons.add),
          label: const Text('Ajouter un employé'),
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
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)]),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.people, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Gestion des Employés',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Text(
                    '${_stats['total'] ?? 0} membres',
                    style: TextStyle(color: Colors.grey[400], fontSize: 13),
                  ),
                  const SizedBox(width: 8),
                  Container(width: 4, height: 4, decoration: const BoxDecoration(color: Colors.grey, shape: BoxShape.circle)),
                  const SizedBox(width: 8),
                  Text(
                    '${_stats['active_now'] ?? 0} actifs maintenant',
                    style: const TextStyle(color: Color(0xFF34D399), fontSize: 13, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
