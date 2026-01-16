import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../../widgets/formation_card.dart';

class FormationsScreen extends StatefulWidget {
  const FormationsScreen({super.key});

  @override
  State<FormationsScreen> createState() => _FormationsScreenState();
}

class _FormationsScreenState extends State<FormationsScreen> {
  final ApiService _apiService = ApiService();
  
  List<dynamic> _formations = [];
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
      final response = await _apiService.get(ApiConfig.formationsListEndpoint);
      if (mounted) {
        setState(() {
          _formations = response['formations'] ?? [];
          _stats = response['stats'] ?? {};
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        // Fallback or error handling
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/formations',
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
                       maxCrossAxisExtent: 350,
                       childAspectRatio: 0.8,
                       crossAxisSpacing: 24,
                       mainAxisSpacing: 24,
                     ),
                     itemCount: _formations.length,
                     itemBuilder: (context, index) {
                       return FormationCard(
                         formation: _formations[index],
                         onTap: () {
                           // Launch tutorial logic
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Tutoriel non disponible dans la démo')),
                            );
                         },
                       );
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
      child: Column(
        children: [
          const Icon(Icons.school, size: 48, color: Color(0xFF3B82F6)),
          const SizedBox(height: 16),
          const Text(
            'Centre de Formation',
            style: TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Apprenez à maîtriser toutes les fonctionnalités de SERVO',
            style: TextStyle(color: Colors.grey[400], fontSize: 16),
          ),
          
          const SizedBox(height: 32),
          
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildStatBadge(Icons.book, '${_stats['total'] ?? 0} formations'),
              const SizedBox(width: 16),
              _buildStatBadge(Icons.check_circle, '${_stats['available'] ?? 0} disponibles'),
              const SizedBox(width: 16),
              _buildStatBadge(Icons.timer, '~${_stats['total_duration_min'] ?? 60} min au total'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatBadge(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: const Color(0xFF3B82F6)),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
