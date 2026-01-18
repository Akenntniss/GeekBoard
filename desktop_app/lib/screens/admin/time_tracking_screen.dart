import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/timetracking_widgets.dart';

class TimeTrackingScreen extends StatefulWidget {
  const TimeTrackingScreen({super.key});

  @override
  State<TimeTrackingScreen> createState() => _TimeTrackingScreenState();
}

class _TimeTrackingScreenState extends State<TimeTrackingScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  bool _isLoading = true;
  Map<String, dynamic> _data = {};

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final response = await _apiService.get(ApiConfig.timeTrackingDashboardEndpoint);
      if (mounted) {
        setState(() {
          _data = response;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        // Error handling
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final stats = _data['stats'] ?? {};
    final activeUsers = _data['active_users'] as List? ?? [];
    
    return AppShell(
      currentRoute: '/timetracking', // Assuming route management
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: _isLoading 
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildHeader(),
                  const SizedBox(height: 32),
                  
                  // GRID STATS
                  GridView.count(
                    crossAxisCount: 4,
                    crossAxisSpacing: 24,
                    mainAxisSpacing: 24,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    childAspectRatio: 1.5,
                    children: [
                      TimeTrackingStatCard(
                        title: 'Employés Actifs',
                        value: '${stats['currently_working'] ?? 0}',
                        icon: Icons.people,
                        color: const Color(0xFF3B82F6),
                        subtitle: '${stats['active_employees'] ?? 0} total ce jour',
                      ),
                      TimeTrackingStatCard(
                        title: 'Heures Travaillées',
                        value: '${stats['total_work_hours'] ?? 0}h',
                        icon: Icons.access_time_filled,
                        color: const Color(0xFF10B981),
                        subtitle: 'Moyenne: ${stats['avg_work_hours']}h',
                      ),
                      TimeTrackingStatCard(
                        title: 'En Pause',
                        value: '${stats['on_break'] ?? 0}',
                        icon: Icons.coffee,
                        color: const Color(0xFFF59E0B),
                      ),
                      TimeTrackingStatCard(
                        title: 'Heures Sup',
                        value: '${stats['overtime_sessions'] ?? 0}',
                        icon: Icons.warning_amber_rounded,
                        color: const Color(0xFFEF4444),
                        subtitle: 'Sessions > 8h',
                      ),
                    ],
                  ),
                  
                  const SizedBox(height: 32),
                  
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ACTIVE USERS LIST
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'En direct',
                              style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 16),
                            if (activeUsers.isEmpty)
                              Container(
                                padding: const EdgeInsets.all(24),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.05),
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: const Center(
                                  child: Text(
                                    'Aucun employé actif pour le moment',
                                    style: TextStyle(color: Colors.grey),
                                  ),
                                ),
                              )
                            else
                              ListView.builder(
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                itemCount: activeUsers.length,
                                itemBuilder: (context, index) {
                                  return ActiveUserCard(user: activeUsers[index]);
                                },
                              ),
                          ],
                        ),
                      ),
                      
                      const SizedBox(width: 32),
                      
                      // PENDING REQUESTS OR ACTIONS
                      Expanded(
                        flex: 1,
                        child: Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(color: Colors.white.withOpacity(0.05)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Row(
                                children: [
                                  Icon(Icons.rule, color: Colors.white70),
                                  SizedBox(width: 12),
                                  Text(
                                    'Actions Rapides',
                                    style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 24),
                              _buildActionButton('Gérer les plannings', Icons.calendar_month, () {}),
                              const SizedBox(height: 12),
                              _buildActionButton('Historique complet', Icons.history, () {}),
                              const SizedBox(height: 12),
                              _buildActionButton('Exporter les données', Icons.download, () {}),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.timer_outlined, size: 32, color: Color(0xFF3B82F6)),
            const SizedBox(width: 16),
            const Text(
              'Suivi de Pointage',
              style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, color: Colors.white),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white.withOpacity(0.1)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today, color: Colors.white70, size: 16),
                  const SizedBox(width: 8),
                  Text(
                    _data['date'] ?? 'Aujourd\'hui',
                    style: const TextStyle(color: Colors.white),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          'Vue d\'ensemble de l\'activité des employés en temps réel',
          style: TextStyle(color: Colors.grey[400], fontSize: 16),
        ),
      ],
    );
  }

  Widget _buildActionButton(String label, IconData icon, VoidCallback onTap) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Fonctionnalité à venir dans la démo')),
            );
        },
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.white.withOpacity(0.1)),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              Icon(icon, color: Colors.blue[400], size: 20),
              const SizedBox(width: 16),
              Text(
                label,
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
              ),
              const Spacer(),
              Icon(Icons.arrow_forward_ios, color: Colors.grey[600], size: 14),
            ],
          ),
        ),
      ),
    );
  }
}
