import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/mission_cards.dart';

class MissionsScreen extends StatefulWidget {
  const MissionsScreen({super.key});

  @override
  State<MissionsScreen> createState() => _MissionsScreenState();
}

class _MissionsScreenState extends State<MissionsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  
  // Data
  Map<String, dynamic> _data = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }
  
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.get(ApiConfig.missionsListEndpoint);
      
      if (mounted) {
        setState(() {
          _data = response;
        });
      }
    } catch (e) {
      print('Error loading missions: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur chargement: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AppShell(
      currentRoute: '/missions',
      content: Scaffold(
        backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
        body: Column(
          children: [
            // Header & Stats
            _buildStatsHeader(),
            
            // Tab Bar
            Container(
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                border: Border(bottom: BorderSide(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200)),
              ),
              child: TabBar(
                controller: _tabController,
                indicatorColor: const Color(0xFF3b82f6),
                labelColor: const Color(0xFF3b82f6),
                unselectedLabelColor: Colors.grey[500],
                labelStyle: const TextStyle(fontWeight: FontWeight.bold),
                tabs: [
                  Tab(text: 'EN COURS (${_getList('in_progress').length})'),
                  Tab(text: 'DISPONIBLES (${_getList('available').length})'),
                  Tab(text: 'COMPLÉTÉES (${_getList('completed').length})'),
                ],
              ),
            ),
            
            // Content
            Expanded(
              child: _isLoading 
                ? const Center(child: CircularProgressIndicator()) 
                : TabBarView(
                    controller: _tabController,
                    children: [
                       _buildMissionGrid('in_progress'),
                       _buildMissionGrid('available'),
                       _buildMissionGrid('completed'),
                    ],
                  ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleMissionAction(String action, Map<String, dynamic> data) async {
    setState(() => _isLoading = true);
    
    try {
       final authService = context.read<AuthService>();
       final apiService = authService.getApiService();
       
       final response = await apiService.performMissionAction(action, data);
       
       if (response['success'] == true || response['message'] != null) { // Some APIs return specific success structure
         if (mounted) {
           ScaffoldMessenger.of(context).showSnackBar(
             SnackBar(content: Text(response['message'] ?? 'Action effectuée avec succès'), backgroundColor: Colors.green),
           );
           _loadData(); // Refresh list
         }
       }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _showSubmitTaskDialog(int missionId) async {
    final descriptionController = TextEditingController();
    final proofController = TextEditingController();
    
    return showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Valider une tâche'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: descriptionController,
              decoration: const InputDecoration(
                labelText: 'Description de la tâche',
                hintText: 'Ex: Réparation écran iPhone 11...',
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: proofController,
              decoration: const InputDecoration(
                labelText: 'Preuve (Optionnel)',
                hintText: 'Numéro de facture, lien...',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              if (descriptionController.text.isNotEmpty) {
                _handleMissionAction('soumettre_tache', {
                  'mission_id': missionId,
                  'description': descriptionController.text,
                  'preuve_text': proofController.text,
                });
              }
            },
            child: const Text('Soumettre'),
          ),
        ],
      ),
    );
  }

  List<dynamic> _getList(String key) {
    if (_data['missions'] != null && _data['missions'][key] != null) {
      return _data['missions'][key];
    }
    return [];
  }

  Widget _buildStatsHeader() {
    final stats = _data['stats'] ?? {};
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        border: Border(bottom: BorderSide(color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200)),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF6366f1), Color(0xFFa855f7)], // Indigo to Purple
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.rocket_launch, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 16),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Mes Missions',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: isDark ? Colors.white : Colors.black87,
                          letterSpacing: -0.5,
                        ),
                      ),
                      Text(
                        'Complétez des tâches pour gagner des primes',
                        style: TextStyle(color: isDark ? Colors.grey : Colors.grey[600], fontSize: 13),
                      ),
                    ],
                  ),
                ],
              ),
              
              // Wallet Card
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  ),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white.withOpacity(0.1)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.account_balance_wallet, color: Color(0xFF10B981)),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          stats['current_balance_formatted'] ?? '0.00 €',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          'Cagnotte Disponible',
                          style: TextStyle(color: Colors.grey[500], fontSize: 11),
                        ),
                      ],
                    ),
                  ],
                ),
              )
            ],
          ),
          
          const SizedBox(height: 24),
          
          Row(
            children: [
              _buildStatItem('Missions en cours', stats['active_count']?.toString() ?? '0', Icons.sync, Colors.blue),
              _buildStatItem('Missions terminées', stats['completed_count']?.toString() ?? '0', Icons.task_alt, Colors.purple),
              _buildStatItem('Gains totaux', stats['total_earnings_formatted'] ?? '0.00 €', Icons.emoji_events, Colors.amber),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.only(right: 16),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.2)),
        ),
        child: Row(
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: TextStyle(
                    color: color, // Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  label,
                  style: TextStyle(
                    color: color.withOpacity(0.8), // Colors.grey[400],
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMissionGrid(String status) {
    final missions = _getList(status);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    if (missions.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox, size: 48, color: isDark ? Colors.grey[700] : Colors.grey[300]),
            const SizedBox(height: 16),
            Text(
              'Aucune mission ici pour le moment',
              style: TextStyle(color: isDark ? Colors.grey[500] : Colors.grey[600]),
            ),
          ],
        ),
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(24),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 400,
        childAspectRatio: 0.85, // Taller cards
        crossAxisSpacing: 20,
        mainAxisSpacing: 20,
      ),
      itemCount: missions.length,
      itemBuilder: (context, index) {
        return MissionCard(
          mission: missions[index] as Map<String, dynamic>,
          status: status,
          onAction: () {
            if (status == 'available') {
              _handleMissionAction('rejoindre_mission', {'mission_id': missions[index]['id']});
            } else if (status == 'in_progress') {
              // Show dialog for task submission
              _showSubmitTaskDialog(missions[index]['id']);
            }
          },
        );
      },
    );
  }
}
