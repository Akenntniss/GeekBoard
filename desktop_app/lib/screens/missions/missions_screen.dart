import 'package:flutter/material.dart';
import 'dart:math';
import 'package:provider/provider.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/mission_cards.dart';
import '../../widgets/custom_loader.dart';

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
      final withdrawalsRes = await apiService.get('/missions/withdrawals.php');
      
      if (mounted) {
        setState(() {
          _data = response;
          _data['withdrawals'] = withdrawalsRes['requests'] ?? [];
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
                ? const Center(child: CustomLoader(size: 50, color: Color(0xFF554cb5))) 
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

  Future<void> _showWithdrawalDialog() async {
    final amountController = TextEditingController();
    final detailsController = TextEditingController();
    String method = 'virement';

    return showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) {
          return AlertDialog(
            title: const Text('Demander un retrait'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: amountController,
                  decoration: const InputDecoration(labelText: 'Montant (€)', border: OutlineInputBorder()),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: method,
                  decoration: const InputDecoration(labelText: 'Méthode', border: OutlineInputBorder()),
                  items: const [
                    DropdownMenuItem(value: 'virement', child: Text('Virement Bancaire')),
                    DropdownMenuItem(value: 'paypal', child: Text('PayPal')),
                    DropdownMenuItem(value: 'especes', child: Text('Espèces')),
                  ],
                  onChanged: (v) => setState(() => method = v!),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: detailsController,
                  decoration: const InputDecoration(labelText: 'Détails (IBAN, Email, etc.)', border: OutlineInputBorder()),
                  maxLines: 2,
                ),
              ],
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
              ElevatedButton(
                onPressed: () async {
                   Navigator.pop(context);
                   final amount = double.tryParse(amountController.text) ?? 0;
                   if (amount <= 0) return;

                   setState(() => _isLoading = true);
                   try {
                     final auth = context.read<AuthService>();
                     final api = auth.getApiService();
                     final res = await api.post('/missions/withdrawals.php', {
                       'action': 'request',
                       'amount': amount,
                       'method': method,
                       'details': detailsController.text
                     });
                     
                     if (mounted) {
                       final success = res['success'] == true || (res['success'] != false && res['error'] == null);
                       ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                         content: Text(res['message'] ?? (success ? 'Demande envoyée' : 'Erreur inconnue')),
                         backgroundColor: success ? Colors.green : Colors.red,
                       ));
                       if (success) _loadData();
                     }
                   } catch (e) {
                      if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
                      }
                   } finally {
                      if (mounted) setState(() => _isLoading = false);
                   }
                },
                child: const Text('Envoyer'),
              ),
            ],
          );
        }
      ),
    );
  }

  void _showWithdrawalHistory() {
    final history = _data['withdrawals'] as List? ?? [];
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Historique des retraits"),
        content: SizedBox(
          width: 400,
          child: history.isEmpty 
          ? const Center(child: Padding(padding: EdgeInsets.all(20), child: Text("Aucune demande")))
          : ListView.builder(
              shrinkWrap: true,
              itemCount: min(history.length, 10), // Limit to 10 for dialog
              itemBuilder: (context, index) {
                final h = history[index];
                final status = h['statut']?.toString() ?? 'en_attente';
                Color color = Colors.orange;
                if (status == 'payee') color = Colors.green;
                if (status == 'refusee') color = Colors.red;

                return ListTile(
                  leading: Icon(Icons.payment, color: color),
                  title: Text("${h['montant']} €"),
                  subtitle: Text("${h['created_at']?.toString() ?? ''}\n${h['methode_paiement']?.toString() ?? 'N/A'}"),
                  trailing: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                    child: Text(status.toUpperCase(), style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
                  ),
                );
              },
            ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text("Fermer")),
        ],
      ),
    );
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
                    const SizedBox(width: 16),
                    ElevatedButton(
                      onPressed: _showWithdrawalDialog,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF10B981),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        textStyle: const TextStyle(fontSize: 12),
                      ),
                      child: const Text("Retirer"),
                    ),
                    const SizedBox(width: 8),
                    InkWell(
                      onTap: _showWithdrawalHistory,
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.white.withOpacity(0.3)),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.history, color: Colors.white, size: 20),
                      ),
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
