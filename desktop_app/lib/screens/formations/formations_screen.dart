import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../widgets/formation_card.dart';

class FormationsScreen extends StatefulWidget {
  const FormationsScreen({super.key});

  @override
  State<FormationsScreen> createState() => _FormationsScreenState();
}

class _FormationsScreenState extends State<FormationsScreen> {
  
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
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.get(ApiConfig.formationsListEndpoint);
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
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);
    final subTextColor = isDark ? Colors.grey.shade400 : Colors.grey.shade600;
    final headerColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade300;

    return AppShell(
      currentRoute: '/formations',
      content: Scaffold(
        backgroundColor: Colors.transparent, 
        body: Column(
          children: [
             _buildHeader(isDark, headerColor, borderColor, textColor, subTextColor),
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

  Widget _buildHeader(bool isDark, Color headerColor, Color borderColor, Color textColor, Color subTextColor) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: headerColor,
        border: Border(bottom: BorderSide(color: borderColor)),
      ),
      child: Column(
        children: [
          const Icon(Icons.school, size: 48, color: Color(0xFF3B82F6)),
          const SizedBox(height: 16),
          Text(
            'Centre de Formation',
            style: TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w800,
              color: textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Apprenez à maîtriser toutes les fonctionnalités de SERVO',
            style: TextStyle(color: subTextColor, fontSize: 16),
          ),
          
          const SizedBox(height: 32),
          
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildStatBadge(Icons.book, '${_stats['total'] ?? 0} formations', isDark, borderColor, textColor),
              const SizedBox(width: 16),
              _buildStatBadge(Icons.check_circle, '${_stats['available'] ?? 0} disponibles', isDark, borderColor, textColor),
              const SizedBox(width: 16),
              _buildStatBadge(Icons.timer, '~${_stats['total_duration_min'] ?? 60} min au total', isDark, borderColor, textColor),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatBadge(IconData icon, String label, bool isDark, Color borderColor, Color textColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: borderColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: const Color(0xFF3B82F6)),
          const SizedBox(width: 8),
          Text(
            label,
            style: TextStyle(color: textColor, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
