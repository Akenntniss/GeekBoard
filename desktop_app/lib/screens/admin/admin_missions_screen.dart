import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/theme/macos_theme.dart';

class AdminMissionsScreen extends StatefulWidget {
  const AdminMissionsScreen({super.key});

  @override
  State<AdminMissionsScreen> createState() => _AdminMissionsScreenState();
}

class _AdminMissionsScreenState extends State<AdminMissionsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  Map<String, dynamic> _data = {
    'stats': {},
    'missions': [],
    'validations': [],
    'types': [],
    'withdrawals': []
  };
  String _filterType = 'all'; // all, active, in_progress, completed

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final apiService = context.read<AuthService>().getApiService();
      final response = await apiService.get(ApiConfig.adminMissionsListEndpoint);
      
      // Fetch Withdrawals
      final withdrawalsRes = await apiService.get('/missions/withdrawals.php');
      
      if (mounted) {
        setState(() {
          _data = response;
          _data['withdrawals'] = withdrawalsRes['requests'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _handleAction(String action, Map<String, dynamic> payload) async {
    try {
      final apiService = context.read<AuthService>().getApiService();
      await apiService.post(ApiConfig.adminMissionsActionEndpoint, {
        'action': action,
        ...payload
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Action effectuée'), backgroundColor: Colors.green));
        _loadData();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/admin/missions',
      content: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: Column(
          children: [
            _buildHeader(),
            Expanded(
              child: _isLoading 
                ? const Center(child: CircularProgressIndicator()) 
                : Column(
                    children: [
                      _buildStats(),
                      Container(
                        color: Theme.of(context).cardColor,
                        child: TabBar(
                          controller: _tabController,
                          tabs: [
                            Tab(text: "GESTION DES MISSIONS (${_data['missions'].length})"),
                            Tab(text: "VALIDATIONS EN ATTENTE (${_data['validations'].length})"),
                            Tab(text: "DEMANDES DE RETRAIT (${_data['withdrawals']?.length ?? 0})"),
                          ],
                        ),
                      ),
                      Expanded(
                        child: TabBarView(
                          controller: _tabController,
                          children: [
                            _buildMissionsList(),
                            _buildValidationsList(),
                            _buildWithdrawalsList(),
                          ],
                        ),
                      ),
                    ],
                  ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              const Icon(Icons.shield, size: 32, color: Colors.blue),
              const SizedBox(width: 16),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("Admin Missions", style: Theme.of(context).textTheme.headlineMedium),
                  Text("Gérer les missions et valider les tâches", style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ],
          ),
          ElevatedButton.icon(
            onPressed: _showCreateMissionDialog,
            icon: const Icon(Icons.add),
            label: const Text("Nouvelle Mission"),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStats() {
    final stats = _data['stats'] ?? {};
    return Container(
      padding: const EdgeInsets.all(24),
      child: Row(
        children: [
          _buildStatCard("Missions Actives", stats['active']?.toString() ?? '0', Icons.rocket_launch, Colors.blue, 'active'),
          const SizedBox(width: 16),
          _buildStatCard("En cours", stats['in_progress']?.toString() ?? '0', Icons.sync, Colors.orange, 'in_progress'),
          const SizedBox(width: 16),
          _buildStatCard("En attente validation", stats['pending_validations']?.toString() ?? '0', Icons.pending_actions, Colors.red, 'waiting_validation'),
          const SizedBox(width: 16),
          _buildStatCard("Complétées (Mois)", stats['completed_month']?.toString() ?? '0', Icons.check_circle, Colors.green, 'completed'),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color, String type) {
    final isSelected = _filterType == type;
    final displayColor = isSelected ? color : color.withOpacity(0.5);
    final bg = isSelected ? color.withOpacity(0.1) : Colors.transparent;
    final border = isSelected ? color : Colors.grey.withOpacity(0.2);

    return Expanded(
      child: InkWell(
        onTap: () {
          print('Tapped: $type'); // Debug
          setState(() {
            if (_filterType == type) {
               _filterType = 'all'; // Toggle off
            } else {
               _filterType = type;
               // Logic for switching tabs based on type
               if (type == 'waiting_validation') {
                 _tabController.animateTo(1);
               } else {
                 _tabController.animateTo(0);
               }
            }
          });
        },
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: border, width: isSelected ? 2 : 1),
          ),
          child: Row(
            children: [
              Icon(icon, color: displayColor, size: 28),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: displayColor)),
                  Text(label, style: TextStyle(fontSize: 12, color: displayColor.withOpacity(0.8))),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMissionsList() {
    var missions = _data['missions'] as List? ?? [];
    
    // Filtering
    if (_filterType == 'active') {
      missions = missions.where((m) => m['statut'] == 'active').toList();
    } else if (_filterType == 'in_progress') {
      missions = missions.where((m) => (m['nb_participants'] ?? 0) > 0).toList();
    } else if (_filterType == 'completed') {
       missions = missions.where((m) => (m['nb_completes'] ?? 0) > 0).toList();
    }

    if (missions.isEmpty) return const Center(child: Text("Aucune mission ne correspond aux filtres"));

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: missions.length,
      itemBuilder: (context, index) {
        final m = missions[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: (m['statut'] == 'active' ? Colors.green : Colors.grey).withOpacity(0.2),
              child: Icon(Icons.rocket, color: m['statut'] == 'active' ? Colors.green : Colors.grey),
            ),
            title: Text(m['titre'] ?? 'Sans titre', style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(m['description'] ?? ''),
                const SizedBox(height: 4),
                Row(
                  children: [
                    _badge(Icons.euro, "${m['recompense_euros']} €"),
                    const SizedBox(width: 8),
                    _badge(Icons.star, "${m['recompense_points']} pts"),
                    const SizedBox(width: 8),
                    _badge(Icons.group, "${m['nb_participants']} participants"),
                  ],
                ),
              ],
            ),
            trailing: PopupMenuButton(
              onSelected: (value) {
                if (value == 'delete') {
                  _handleAction('delete_mission', {'mission_id': m['id']});
                } else if (value == 'edit') {
                  _showEditMissionDialog(m);
                }
              },
              itemBuilder: (context) => [
                const PopupMenuItem(value: 'edit', child: Text('Modifier')),
                const PopupMenuItem(value: 'delete', child: Text('Supprimer', style: TextStyle(color: Colors.red))),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _badge(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(color: Colors.grey.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
      child: Row(children: [Icon(icon, size: 12), const SizedBox(width: 4), Text(label, style: const TextStyle(fontSize: 12))]),
    );
  }

  Widget _buildValidationsList() {
    final validations = _data['validations'] as List? ?? [];
    if (validations.isEmpty) return const Center(child: Text("Aucune validation en attente"));

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: validations.length,
      itemBuilder: (context, index) {
        final v = validations[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text("Validation #${v['id']} - ${v['user_nom']}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(color: Colors.orange.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                      child: const Text("En attente", style: TextStyle(color: Colors.orange, fontWeight: FontWeight.bold, fontSize: 12)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text("Mission: ${v['mission_titre']}"),
                Text("Progression: ${v['progression_actuelle']} / ${v['objectif_nombre']}"),
                const Divider(),
                const Text("Message:", style: TextStyle(fontWeight: FontWeight.w600)),
                Text(v['description'] ?? 'Aucun message'),
                if (v['preuve_text'] != null && v['preuve_text'].toString().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  const Text("Preuve:", style: TextStyle(fontWeight: FontWeight.w600)),
                  Text(v['preuve_text']),
                ],
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    OutlinedButton(
                      onPressed: () => _handleAction('reject_task', {'validation_id': v['id'], 'commentaire': 'Refusé par admin'}),
                      style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                      child: const Text("Refuser"),
                    ),
                    const SizedBox(width: 12),
                    ElevatedButton(
                      onPressed: () => _handleAction('validate_task', {'validation_id': v['id'], 'commentaire': 'Validé'}),
                      style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
                      child: const Text("Valider"),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildWithdrawalsList() {
    final requests = _data['withdrawals'] as List? ?? [];
    if (requests.isEmpty) return const Center(child: Text("Aucune demande de retrait"));

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: requests.length,
      itemBuilder: (context, index) {
        final r = requests[index];
        final status = r['statut'] ?? 'en_attente';
        Color statusColor = Colors.orange;
        if (status == 'payee') statusColor = Colors.green;
        if (status == 'refusee') statusColor = Colors.red;

        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text("Retrait #${r['id']} - ${r['user_name'] ?? 'Utilisateur'}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(color: statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                      child: Text(status.toUpperCase(), style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12)),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    _badge(Icons.euro, "${r['montant']} €"),
                    const SizedBox(width: 8),
                    _badge(Icons.payment, "${r['methode_paiement']} (${r['details_paiement']})"),
                  ],
                ),
                if (status == 'en_attente') ...[
                  const Divider(),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      OutlinedButton(
                        onPressed: () => _showConfirmationDialog(
                          title: "Refuser le retrait",
                          content: "Motif du refus :",
                          onConfirm: (comment) => _handleWithdrawalAction('reject', r['id'], comment),
                          isDestructive: true
                        ),
                        style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                        child: const Text("Refuser"),
                      ),
                      const SizedBox(width: 12),
                      ElevatedButton(
                        onPressed: () => _showConfirmationDialog(
                          title: "Valider le paiement",
                          content: "Commentaire (Optionnel) :",
                          onConfirm: (comment) => _handleWithdrawalAction('validate', r['id'], comment),
                        ),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
                        child: const Text("Valider & Payer"),
                      ),
                    ],
                  ),
                ],
                if (r['processed_at'] != null)
                   Padding(
                     padding: const EdgeInsets.only(top: 8.0),
                     child: Text("Traité le ${r['processed_at']} par Admin", style: TextStyle(color: Colors.grey[600], fontSize: 12, fontStyle: FontStyle.italic)),
                   ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<void> _handleWithdrawalAction(String action, int id, String comment) async {
    try {
      final apiService = context.read<AuthService>().getApiService();
      final res = await apiService.post('/missions/withdrawals.php', {
        'action': action,
        'request_id': id,
        'comment': comment
      });
      
      if (mounted) {
         final success = res['success'] == true;
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(
           content: Text(res['message'] ?? (success ? 'Action effectuée' : 'Erreur')), 
           backgroundColor: success ? Colors.green : Colors.red
         ));
         if (success) _loadData();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    }
  }

  void _showConfirmationDialog({required String title, required String content, required Function(String) onConfirm, bool isDestructive = false}) {
    final commentController = TextEditingController();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(content),
            const SizedBox(height: 8),
            TextField(controller: commentController, decoration: const InputDecoration(border: OutlineInputBorder())),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              onConfirm(commentController.text);
            },
            style: ElevatedButton.styleFrom(backgroundColor: isDestructive ? Colors.red : Colors.green, foregroundColor: Colors.white),
            child: const Text("Confirmer"),
          ),
        ],
      )
    );
  }

  void _showCreateMissionDialog() {
    _showMissionFormDialog();
  }

  void _showEditMissionDialog(Map<String, dynamic> mission) {
    _showMissionFormDialog(mission: mission);
  }

  void _showMissionFormDialog({Map<String, dynamic>? mission}) {
    final isEdit = mission != null;
    final titreController = TextEditingController(text: mission?['titre'] ?? '');
    final descController = TextEditingController(text: mission?['description'] ?? '');
    final eurosController = TextEditingController(text: mission?['recompense_euros']?.toString() ?? '0');
    final pointsController = TextEditingController(text: mission?['recompense_points']?.toString() ?? '10');
    final quantityController = TextEditingController(text: mission?['objectif_quantite']?.toString() ?? '1');
    String? selectedTypeId = mission?['mission_type_id']?.toString() ?? mission?['type_id']?.toString();
    
    // Ensure selectedTypeId matches one of the options, otherwise null (or default validation will fail)
    
    final types = _data['types'] as List? ?? [];

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) {
          return AlertDialog(
            title: Text(isEdit ? "Modifier la Mission" : "Nouvelle Mission"),
            content: SingleChildScrollView(
              child: SizedBox(
                width: 500,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: titreController, 
                      decoration: const InputDecoration(
                        labelText: "Titre de la mission",
                        border: OutlineInputBorder(),
                        filled: true,
                      )
                    ),
                    const SizedBox(height: 16),
                    
                    DropdownButtonFormField<String>(
                      value: selectedTypeId,
                      decoration: const InputDecoration(
                        labelText: "Type de mission",
                        border: OutlineInputBorder(),
                        filled: true,
                      ),
                      items: types.isNotEmpty 
                        ? types.map<DropdownMenuItem<String>>((t) {
                            return DropdownMenuItem<String>(
                              value: t['id'].toString(),
                              child: Text(t['nom'] ?? 'Type ${t['id']}'),
                            );
                          }).toList()
                        : const [
                            DropdownMenuItem(value: "1", child: Text("Trottinettes")),
                            DropdownMenuItem(value: "2", child: Text("Smartphones")),
                            DropdownMenuItem(value: "3", child: Text("LeBonCoin")),
                            DropdownMenuItem(value: "4", child: Text("eBay")),
                            DropdownMenuItem(value: "5", child: Text("Réparations Express")),
                            DropdownMenuItem(value: "6", child: Text("Service Client")),
                          ],
                      onChanged: (v) => setState(() => selectedTypeId = v),
                    ),
                    const SizedBox(height: 16),
                    
                    TextField(
                      controller: descController, 
                      decoration: const InputDecoration(
                        labelText: "Description",
                        border: OutlineInputBorder(),
                        filled: true,
                      ), 
                      maxLines: 3
                    ),
                    const SizedBox(height: 16),
                    
                    Row(children: [
                      Expanded(
                        child: TextField(
                          controller: quantityController, 
                          decoration: const InputDecoration(labelText: "Objectif (Qté)", border: OutlineInputBorder(), filled: true), 
                          keyboardType: TextInputType.number
                        )
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextField(
                          controller: eurosController, 
                          decoration: const InputDecoration(labelText: "Récomp. (€)", border: OutlineInputBorder(), filled: true), 
                          keyboardType: TextInputType.number
                        )
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextField(
                          controller: pointsController, 
                          decoration: const InputDecoration(labelText: "Points", border: OutlineInputBorder(), filled: true), 
                          keyboardType: TextInputType.number
                        )
                      ),
                    ]),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
              ElevatedButton(
                onPressed: () {
                  if (selectedTypeId == null) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Veuillez choisir un type"), backgroundColor: Colors.red));
                    return;
                  }
                  
                  Navigator.pop(context);
                  
                  final payload = {
                    'titre': titreController.text,
                    'type_id': int.parse(selectedTypeId!),
                    'description': descController.text,
                    'objectif_quantite': int.tryParse(quantityController.text) ?? 1,
                    'recompense_euros': double.tryParse(eurosController.text) ?? 0,
                    'recompense_points': int.tryParse(pointsController.text) ?? 0,
                  };

                  if (isEdit) {
                    _handleAction('update_mission', {
                      'mission_id': mission['id'],
                      ...payload
                    });
                  } else {
                    _handleAction('create_mission', payload);
                  }
                },
                child: Text(isEdit ? "Modifier" : "Créer"),
              ),
            ],
          );
        }
      ),
    );
  }
}

