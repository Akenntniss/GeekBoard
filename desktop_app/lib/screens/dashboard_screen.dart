/// Dashboard Screen - Écran principal avec statistiques (Style MacOS Taohe)
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';
import '../widgets/sidebar.dart';
import '../config/api_config.dart';
import '../models/reparation.dart';
import '../theme/macos_theme.dart';
import 'taches/taches_screen.dart';
import 'taches/task_detail_modal.dart';
import 'reparations/reparations_screen.dart';
import 'commandes/commandes_screen.dart';
import 'create_task_dialog.dart';
import 'create_command_dialog.dart';
import 'reparations/create_repair_dialog.dart';
import 'reparations/repair_detail_modal.dart';
import 'clients/dialogs/client_history_dialog.dart';
import 'commandes/command_detail_dialog.dart';
import '../widgets/universal_search_dialog.dart';
import 'dashboard/employee_activity_modal.dart';
import 'daily_stats_dialog.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic> _stats = {};
  List<Reparation> _recentReparations = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final data = await apiService.getDashboardStats();

      setState(() {
        _stats = data;
        // Support legacy key if api lists are missing
        if (data['listes'] != null && data['listes']['reparations'] != null) {
           _recentReparations = (data['listes']['reparations'] as List)
              .map((json) => Reparation.fromJson(json))
              .toList();
        } else if (data['reparations_recentes'] != null) {
           _recentReparations = (data['reparations_recentes'] as List)
              .map((json) => Reparation.fromJson(json))
              .toList();
        }
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  void _showUniversalSearch(BuildContext context) async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (_) => UniversalSearchDialog(apiService: apiService),
    );
    
    if (result != null && mounted) {
      // Handle the selected result
      if (result['type'] == 'reparation') {
        showDialog(
          context: context,
          builder: (_) => RepairDetailModal(
            repair: result['data'],
            apiService: apiService,
            onUpdate: _loadDashboardData,
          ),
        );
      } else if (result['type'] == 'client') {
        showDialog(
          context: context,
          builder: (_) => ClientHistoryDialog(
            clientId: int.parse(result['data']['id'].toString()),
            apiService: apiService,
          ),
        );
      } else if (result['type'] == 'commande') {
        showDialog(
          context: context,
          builder: (_) => CommandDetailDialog(
            command: result['data'],
            apiService: apiService,
            onUpdate: _loadDashboardData,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null) {
      return Scaffold(
        body: Center(child: Text('Erreur: $_error', style: const TextStyle(color: MacOSTheme.dangerRed))),
      );
    }

    // Extraction des données (Safety checks)
    final kpi = _stats['kpi'] ?? {};
    final listes = _stats['listes'] ?? {};
    final taches = listes['taches'] as List? ?? [];
    final commandes = listes['commandes'] as List? ?? [];
    final daily = _stats['daily'] ?? {};
    final employes = _stats['employes'] as List? ?? [];

    return Scaffold(
      body: Row(
        children: [
          const Sidebar(currentRoute: '/dashboard'),
          Expanded(
            child: Container(
             color: Theme.of(context).scaffoldBackgroundColor, // Ensure background is correct
             child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // 1. Boutons d'Action
                  Row(
                    children: [
                      _buildActionCard(
                        "Rechercher", 
                        "", 
                        Icons.search, 
                        MacOSTheme.accentBlue,
                        () => _showUniversalSearch(context)
                      ),
                      const SizedBox(width: 16),
                      _buildActionCard(
                        "Nouvelle Tâche", 
                        "", 
                        Icons.task, 
                        MacOSTheme.successGreen,
                        () => _showCreateDialog(context, "Tâche")
                      ),
                      const SizedBox(width: 16),
                      _buildActionCard(
                        "Nouvelle Réparation", 
                        "", 
                        Icons.build, 
                        MacOSTheme.warningOrange,
                        () => _showCreateDialog(context, "Réparation")
                      ),
                      const SizedBox(width: 16),
                      _buildActionCard(
                        "Nouvelle Commande", 
                        "", 
                        Icons.shopping_cart, 
                        MacOSTheme.accentPurple,
                        () => _showCreateDialog(context, "Commande")
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // 2. Barre d'état (KPIs)
                  Container(
                    decoration: BoxDecoration(
                      gradient: isDark ? MacOSTheme.primaryGradient : null,
                      color: isDark ? null : Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                         BoxShadow(
                            color: isDark ? const Color(0xFF2563EB).withOpacity(0.3) : Colors.black.withOpacity(0.05),
                            blurRadius: 20,
                            offset: const Offset(0, 4),
                         )
                      ]
                    ),
                    child: MacOSCard(
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _buildKpiItem(
                            "Tâches", 
                            kpi['taches_actives']?.toString() ?? '0', 
                            isDark ? Colors.white : MacOSTheme.successGreen, 
                            Icons.check_circle, 
                            isDark,
                            () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const TachesScreen())),
                          ),
                          Container(height: 40, width: 1, color: isDark ? Colors.white.withOpacity(0.2) : Theme.of(context).dividerColor),
                          _buildKpiItem(
                            "Réparations", 
                            kpi['reparations_actives']?.toString() ?? '0', 
                            isDark ? Colors.white : MacOSTheme.warningOrange, 
                            Icons.build_circle, 
                            isDark,
                            () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const ReparationsScreen())),
                          ),
                          Container(height: 40, width: 1, color: isDark ? Colors.white.withOpacity(0.2) : Theme.of(context).dividerColor),
                          _buildKpiItem(
                            "Commandes", 
                            kpi['commandes_attente']?.toString() ?? '0', 
                            isDark ? Colors.white : MacOSTheme.accentPurple, 
                            Icons.shopping_bag, 
                            isDark,
                            () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const CommandesScreen())),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 24),

                  // 3. Les 3 Listes (Colonnes)
                  SizedBox(
                    height: 420, 
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Tâches
                        Expanded(child: _buildListCard(
                          "Tâches en cours", 
                          taches, 
                          MacOSTheme.successGreen, 
                          "titre", 
                          "description", 
                          Icons.task_alt,
                          onHeaderTap: () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const TachesScreen())),
                          onItemTap: (item) => _showTaskDetailModal(context, item)
                        )),
                        const SizedBox(width: 16),
                        // Réparations
                        Expanded(child: _buildReparationListCard(
                          "Réparations récentes", 
                          _recentReparations, 
                          MacOSTheme.accentBlue,
                          onHeaderTap: () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const ReparationsScreen())),
                          onItemTap: (item) => _showRepairDetailModal(context, item)
                        )),
                        const SizedBox(width: 16),
                        // Commandes
                        Expanded(child: _buildListCard(
                          "Commandes récentes", 
                          commandes, 
                          MacOSTheme.accentPurple, 
                          "nom_piece", 
                          "fournisseur_nom", 
                          Icons.shopping_cart_outlined,
                          onHeaderTap: () => Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const CommandesScreen())),
                          onItemTap: (item) {
                            final authService = context.read<AuthService>();
                            showDialog(
                              context: context,
                              builder: (_) => CommandDetailDialog(
                                command: item,
                                apiService: authService.getApiService(),
                                onUpdate: _loadDashboardData,
                              ),
                            );
                          }
                        )),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // 4. Statistiques du jour
                  Text("Statistiques du jour", style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildDailyStatCard("Nouvelles", daily['nouvelles']?.toString() ?? '0', MacOSTheme.accentBlue, 'nouvelles', daily),
                      const SizedBox(width: 16),
                      _buildDailyStatCard("Effectuées", daily['effectuees']?.toString() ?? '0', MacOSTheme.successGreen, 'effectuees', daily),
                      const SizedBox(width: 16),
                      _buildDailyStatCard("Restituées", daily['restituees']?.toString() ?? '0', Colors.teal, 'restituees', daily),
                      const SizedBox(width: 16),
                      _buildDailyStatCard("Devis", daily['devis']?.toString() ?? '0', MacOSTheme.accentPurple, 'devis', daily), 
                    ],
                  ),

                  const SizedBox(height: 32),

                  // 5. Statut des Employés
                  Text("Statut des employés", style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  MacOSCard(
                    padding: EdgeInsets.zero,
                    child: SizedBox(
                      width: double.infinity,
                      child: DataTable(
                        headingRowColor: MaterialStateProperty.all(Colors.transparent),
                        dataRowColor: MaterialStateProperty.all(Colors.transparent),
                        columns: [
                          DataColumn(label: Text('Technicien', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color))),
                          DataColumn(label: Text('Statut', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color))),
                          DataColumn(label: Text('Activité', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color))),
                        ], 
                        rows: employes.map<DataRow>((emp) {
                          bool busy = (emp['active_repair_id'] != null && emp['active_repair_id'].toString() != '0');
                          if (emp['techbusy'] == 1 || emp['techbusy'] == '1') busy = true;

                          return DataRow(cells: [
                            DataCell(
                              Row(
                                children: [
                                  CircleAvatar(
                                    radius: 12, 
                                    backgroundColor: busy ? MacOSTheme.warningOrange.withOpacity(0.2) : MacOSTheme.successGreen.withOpacity(0.2),
                                    child: Icon(busy ? Icons.build : Icons.check, size: 12, color: busy ? MacOSTheme.warningOrange : MacOSTheme.successGreen),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(emp['full_name'] ?? 'Inconnu', style: TextStyle(fontWeight: FontWeight.w500, color: Theme.of(context).textTheme.bodyLarge?.color)),
                                ],
                              ),
                              onTap: () => _showEmployeeActivity(context, emp),
                            ),
                            DataCell(
                              StatusBadge(status: busy ? 'en_cours' : 'termine', label: busy ? "Occupé" : "Disponible"),
                              onTap: () => _showEmployeeActivity(context, emp),
                            ),
                            DataCell(
                              busy
                              ? InkWell(
                                  onTap: () {
                                    if (emp['active_repair_id'] != null) {
                                      _openActiveRepair(context, int.parse(emp['active_repair_id'].toString()));
                                    }
                                  },
                                  child: Text(
                                    emp['active_repair_model'] ?? '-', 
                                    style: TextStyle(
                                      color: MacOSTheme.accentBlue,
                                      decoration: TextDecoration.underline,
                                      fontWeight: FontWeight.w500
                                    )
                                  ),
                                )
                              : Text(emp['active_repair_model'] ?? '-', style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color)),
                              onTap: busy ? null : () => _showEmployeeActivity(context, emp),
                            ),
                          ]);
                        }).toList(),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openActiveRepair(BuildContext context, int repairId) async {
    try {
      // Show loading
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => const Center(child: CircularProgressIndicator()),
      );

      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      // Utilisation directe de get() pour récupérer la Map brute complète (avec photos, logs, etc.)
      // et gérer correctement la clé de réponse ('reparation' vs 'data')
      final response = await apiService.get(ApiConfig.reparationsGetEndpoint, {'id': repairId.toString()});
      
      // Close loading
      if (context.mounted) Navigator.of(context).pop();

      final repairData = response['reparation'] ?? response['data'];

      if (repairData == null) {
         if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Impossible de récupérer les détails de la réparation"), backgroundColor: MacOSTheme.dangerRed));
         }
         return;
      }

      if (context.mounted) {
        showDialog(
          context: context,
          builder: (ctx) => RepairDetailModal(
            repair: Map<String, dynamic>.from(repairData),
            apiService: apiService,
            onUpdate: _loadDashboardData,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
         // Close loading if potentially open (safe to pop even if not top? No, check if loading dialog is top)
         // Assuming loading was popped before error if it happened after api call, 
         // but if api call failed, we need to pop.
         // A cleaner way is to use a local variable for dialog context or verify route.
         // Simple fix: force pop just in case implies risk of popping screen.
         // Better: relies on finally block or careful placement.
         // Here loading is popped after api call success.
         // If api call throws, loading is NOT popped.
         Navigator.of(context).pop(); 
         
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur lors de l'ouverture de la réparation: $e"), backgroundColor: MacOSTheme.dangerRed));
      }
    }
  }

  // --- Widgets Helper ---

  Widget _buildActionCard(String title, String subtitle, IconData icon, Color color, VoidCallback onTap) {
    return Expanded(
      child: MacOSCard(
        onTap: onTap,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
        child: ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 80),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10), // Reduced from 12
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: color, size: 24), // Reduced from 28
              ),
              const SizedBox(width: 12), // Reduced from 16
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(title, 
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold, 
                        fontSize: 14 // Force smaller font if needed
                      ), 
                      maxLines: 1, 
                      overflow: TextOverflow.ellipsis
                    ),
                    if (subtitle.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(subtitle, style: Theme.of(context).textTheme.bodySmall, maxLines: 1, overflow: TextOverflow.ellipsis),
                    ],
                  ],
                ),
              ),
              // Removed arrow icon to save space
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildKpiItem(String label, String value, Color color, IconData icon, [bool isWhite = false, VoidCallback? onTap]) {
    final textColor = isWhite ? Colors.white : MacOSTheme.textPrimary;
    final subTextColor = isWhite ? Colors.white.withOpacity(0.8) : MacOSTheme.textSecondary;
    final iconColor = isWhite ? Colors.white : color;
    final iconBg = isWhite ? Colors.white.withOpacity(0.2) : color.withOpacity(0.1);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: iconBg, borderRadius: BorderRadius.circular(10)),
              child: Icon(icon, color: iconColor, size: 22),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(value, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold, color: textColor)),
                Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: subTextColor)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildListCard(
    String title, 
    List<dynamic> items, 
    Color accentColor, 
    String titleKey, 
    String subKey, 
    IconData icon,
    {VoidCallback? onHeaderTap, Function(Map<String, dynamic>)? onItemTap}
  ) {
    return MacOSCard(
      padding: EdgeInsets.zero,
      child: Column(
        children: [
          // Header
          InkWell(
            onTap: onHeaderTap,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                children: [
                  Icon(icon, color: accentColor, size: 18),
                  const SizedBox(width: 8),
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
          // List
          Expanded(
            child: items.isEmpty 
              ? Center(child: Text("Aucune donnée", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)))
              : ListView.separated(
                  padding: const EdgeInsets.all(8),
                  itemCount: items.length,
                  separatorBuilder: (ctx, i) => Divider(height: 1, indent: 16, endIndent: 16, color: Theme.of(context).dividerColor.withOpacity(0.5)),
                  itemBuilder: (ctx, i) {
                    final item = items[i];
                    return ListTile(
                      onTap: onItemTap != null ? () => onItemTap(item) : null,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      dense: true,
                      leading: Container(
                        width: 4, height: 32,
                        decoration: BoxDecoration(color: accentColor, borderRadius: BorderRadius.circular(2)),
                      ),
                      title: Text(item[titleKey] ?? '-', style: const TextStyle(fontWeight: FontWeight.w500)),
                      subtitle: Text(item[subKey] ?? '-', maxLines: 1, overflow: TextOverflow.ellipsis),
                    );
                  },
                ),
          ),
        ],
      ),
    );
  }

    Widget _buildReparationListCard(
      String title, 
      List<Reparation> items, 
      Color accentColor,
      {VoidCallback? onHeaderTap, Function(Reparation)? onItemTap}
    ) {
    return MacOSCard(
      padding: EdgeInsets.zero,
      child: Column(
        children: [
          // Header
          InkWell(
            onTap: onHeaderTap,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                children: [
                  Icon(Icons.build, color: accentColor, size: 18),
                  const SizedBox(width: 8),
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
            ),
          ),
          // List
          Expanded(
            child: items.isEmpty 
              ? Center(child: Text("Aucune réparation", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)))
              : ListView.separated(
                  padding: const EdgeInsets.all(8),
                  itemCount: items.length,
                  separatorBuilder: (ctx, i) => Divider(height: 1, indent: 16, endIndent: 16, color: Theme.of(context).dividerColor.withOpacity(0.5)),
                  itemBuilder: (ctx, i) {
                    final item = items[i];
                      return InkWell(
                        onTap: onItemTap != null ? () => onItemTap(item) : null,
                        child:  Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          child: Row(
                            children: [
                              Container(
                                width: 4, height: 32,
                                decoration: BoxDecoration(color: accentColor, borderRadius: BorderRadius.circular(2)),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text("${item.clientNom} ${item.clientPrenom}", style: const TextStyle(fontWeight: FontWeight.w500)),
                                    const SizedBox(height: 2),
                                    Text("Modèle: ${item.modele ?? '-'}", maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodySmall),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              StatusBadge(status: item.statutStr),
                            ],
                          ),
                        ),
                      );
                    },
                ),
          ),
        ],
      ),
    );
  }

  Widget _buildDailyStatCard(String label, String value, Color color, String tabKey, Map<String, dynamic> dailyStats) {
    return Expanded(
      child: MacOSCard(
        onTap: () => _showDailyStatsDialog(context, tabKey, dailyStats),
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Icon(Icons.bar_chart, color: color, size: 28),
            const SizedBox(height: 8),
            Text(value, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold, color: color)),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }

  void _showDailyStatsDialog(BuildContext context, String initialTab, Map<String, dynamic> dailyStats) {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    showDialog(
      context: context,
      builder: (_) => DailyStatsDialog(
        apiService: apiService,
        initialTab: initialTab,
        dailyStats: dailyStats,
      ),
    );
  }

  // --- Dialog Helpers ---

  void _showRepairDetailModal(BuildContext context, Reparation repair) {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    showDialog(
      context: context,
      builder: (ctx) => RepairDetailModal(
        repair: repair.toMap(),
        apiService: apiService,
        onUpdate: () => _loadDashboardData(),
      ),
    );
  }

  void _showCreateDialog(BuildContext context, String type) async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    bool? result;

    if (type == "Tâche") {
      result = await showDialog<bool>(
        context: context,
        builder: (ctx) => CreateTaskDialog(apiService: apiService),
      );
    } else if (type == "Commande") {
      result = await showDialog<bool>(
        context: context,
        builder: (ctx) => CreateCommandDialog(apiService: apiService),
      );
    } else if (type == "Réparation") {
      result = await showDialog<bool>(
        context: context,
        builder: (ctx) => CreateRepairDialog(apiService: apiService),
      );
    }

    if (result == true) {
      // Refresh dashboard data
      _loadDashboardData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("$type créée avec succès"), backgroundColor: MacOSTheme.successGreen),
        );
      }
    }
  }

  void _showTaskDetailModal(BuildContext context, Map<String, dynamic> task) {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    showDialog(
      context: context,
      builder: (ctx) => TaskDetailModal(
        task: task,
        apiService: apiService,
        onUpdate: () {
          _loadDashboardData();
        },
      ),
    );
  }

  void _showInfoDialog(BuildContext context, String title, String message) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text("Fermer"),
          ),
        ],
      ),
    );
  }

  void _showEmployeeActivity(BuildContext context, Map<String, dynamic> employee) {
    showDialog(
      context: context,
      builder: (ctx) => EmployeeActivityModal(
        employee: employee,
        apiService: Provider.of<AuthService>(context, listen: false).getApiService(),
      ),
    );
  }
}
