import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';
import '../../widgets/reparations_filter_bar.dart';
import '../../widgets/status_drop_overlay.dart';
import '../../widgets/status_selection_modal.dart';
import 'package:geekboard_desktop/widgets/numeric_keyboard_modal.dart';
import 'package:geekboard_desktop/widgets/my_repairs_modal.dart';
import 'package:geekboard_desktop/widgets/technician_assignment_modal.dart';
import 'package:geekboard_desktop/widgets/bulk_status_modal.dart';
import 'repair_detail_modal.dart';
import 'create_repair_dialog.dart';

// ... (existing imports)



class ReparationsScreen extends StatefulWidget {
  const ReparationsScreen({super.key});
  @override
  State<ReparationsScreen> createState() => _ReparationsScreenState();
}

class _ReparationsScreenState extends State<ReparationsScreen> with SingleTickerProviderStateMixin {
  final TextEditingController _searchController = TextEditingController();
  List<Map<String, dynamic>> _reparations = [];
  Map<String, int> _counts = {
    'new': 0, 'processing': 0, 'pending': 0, 'done': 0, 'recent': 0, 'archived': 0
  };
  
  bool _isLoading = true;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  int _currentPage = 1;
  final int _limit = 25;
  String? _error;
  int _myRepairsCount = 0;
  String _selectedStatus = 'recent'; // Par défaut

  final ScrollController _scrollController = ScrollController();
  
  bool _isDragging = false;
  Map<String, dynamic>? _draggedRepair;
  
  @override
  void initState() { 
    super.initState(); 
    _scrollController.addListener(_scrollListener);
    _loadCounts();
    _loadReparations(); 
  }

  // ... dispose ...

  void _scrollListener() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoading && !_isLoadingMore && _hasMore) {
        _loadReparations(loadMore: true);
      }
    }
  }

  String? _getStatusListForFilter(String filterCode) {
    switch (filterCode) {
      case 'recent':
        // "Récentes" = Nouveaux dépôts + En cours (exclut Terminé, Livré, Non réparable)
        return '1,2,3,4,5,6,7,8,9,10';
      case 'waiting':
        // En attente de pièce, En attente devis, etc.
        return '4,5,6';
      case 'in_progress':
        // En cours de réparation
        return '3';
      case 'done':
        // Terminé, Livré
        return '11,12,13';
      case 'my_repairs':
        // Handled by technicien_id filter instead
        return null;
      default:
        return null;
    }
  }


  Future<void> _loadCounts() async {
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      // 1. Load global counts
      final response = await apiService.get(ApiConfig.reparationsCountsEndpoint);
      
      // 2. Load "My Repairs" count
      int myCount = 0;
      if (authService.currentUser?.id != null) {
        try {
          final myData = await apiService.get(ApiConfig.reparationsListEndpoint, {
             'limit': '1',
             'technicien_id': authService.currentUser!.id.toString()
          });
          if (myData['pagination'] != null) {
            myCount = myData['pagination']['total'] ?? 0;
          }
        } catch (_) {}
      }

      if (mounted) {
        setState(() {
          if (response.containsKey('counts')) {
            _counts = Map<String, int>.from(response['counts']);
          }
          _myRepairsCount = myCount;
        });
      }
    } catch (e) {
      print("Erreur chargement compteurs: $e");
    }
  }

  Future<void> _loadReparations({bool loadMore = false, String? search}) async {
    if (loadMore) {
        if (_isLoadingMore || !_hasMore) return;
        setState(() => _isLoadingMore = true);
    } else {
        setState(() {
            _isLoading = true;
            _currentPage = 1;
            _reparations.clear();
            _hasMore = true;
        });
    }

    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      String url = '${ApiConfig.reparationsListEndpoint}?limit=$_limit&page=$_currentPage'; 
      // Ajouter timestamp pour éviter le cache
      url += '&_=${DateTime.now().millisecondsSinceEpoch}';
      
      if (search != null && search.isNotEmpty) {
          url += '&search=$search';
      }
      
      // Ajout du filtre de statut (Server-Side)
      if (_selectedStatus != 'all') {
          final statusList = _getStatusListForFilter(_selectedStatus);
          if (statusList != null) {
              url += '&status_list=$statusList';
          }
      }
      
      final response = await apiService.get(url);
      
      if (mounted) {
        setState(() { 
          final newItems = List<Map<String, dynamic>>.from(response['reparations'] ?? []);
          
          if (newItems.length < _limit) {
              _hasMore = false;
          }
          
          if (loadMore) {
              _reparations.addAll(newItems);
              _isLoadingMore = false;
          } else {
              _reparations = newItems;
              _isLoading = false;
          }
          
          _currentPage++;
          _error = null;
          
          // Debug stats
          print("Loaded ${newItems.length} items. Total: ${_reparations.length}. Status: $_selectedStatus");
        });
      }
    } catch (e) { 
      if (mounted) setState(() { 
          _error = e.toString(); 
          _isLoading = false; 
          _isLoadingMore = false;
      }); 
    }
  }

  void _showCreateDialog() async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => CreateRepairDialog(apiService: apiService),
    );
    
    if (result == true) {
      _loadReparations();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: const Text('Réparation créée avec succès'), backgroundColor: MacOSTheme.successGreen),
      );
    }
  }

  void _showRepairDetail(Map<String, dynamic> repair) {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    showDialog(
      context: context,
      builder: (ctx) => RepairDetailModal(
        repair: repair,
        apiService: apiService,
        onUpdate: () => _loadReparations(),
      ),
    );
  }

  void _updateStatusFilter(String filterCode) {
    if (_selectedStatus == filterCode) return;
    setState(() {
      _selectedStatus = filterCode;
    });
    // Recharger avec le nouveau filtre (Server-Side)
    _loadReparations();
  }

  Future<void> _showMyRepairs() async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    await showDialog(
      context: context,
      builder: (_) => MyRepairsModal(apiService: apiService),
    );
    // Reload main list if needed
    _loadReparations();
  }

  Future<void> _openUpdateStatusDialog() async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    
    showDialog(
      context: context,
      builder: (_) => BulkStatusModal(
        apiService: apiService, 
        onUpdate: _loadReparations
      ),
    );
  }

  Future<void> _openAssignDialog() async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();

    showDialog(
      context: context, 
      builder: (_) => TechnicianAssignmentModal(
        apiService: apiService, 
        onAssigned: () => _loadReparations(),
      )
    );
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/reparations',
      content: Column(
        children: [
          // En-tête avec filtres "Web Style"
          ReparationsFilterBar(
            selectedFilter: _selectedStatus,
            onFilterSelected: _updateStatusFilter,
            counts: _counts,
            onCardDropped: _handleDrop,
          ),
          
          // Barre d'outils secondaire (Recherche + Actions)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            child: Row(
              children: [
                 SizedBox(
                  width: 300, 
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Rechercher...',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: Theme.of(context).dividerColor),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(color: Theme.of(context).dividerColor),
                      ),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                    ),
                    onSubmitted: (v) => _loadReparations(search: v),
                  )
                ),
                const SizedBox(width: 16),
                
                // Mes Réparations
                Badge(
                  isLabelVisible: _myRepairsCount > 0,
                  label: Text(_myRepairsCount.toString()),
                  backgroundColor: Colors.red,
                  offset: const Offset(-5, 5),
                  child: OutlinedButton.icon(
                    onPressed: _showMyRepairs,
                    icon: const Icon(Icons.handyman_outlined, size: 18),
                    label: const Text("Mes réparations"),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),

                // Mise à jour statut
                OutlinedButton.icon(
                  onPressed: _openUpdateStatusDialog,
                  icon: const Icon(Icons.update, size: 18),
                  label: const Text("Mise à jour statut"),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                 const SizedBox(width: 8),

                // Attribuer
                OutlinedButton.icon(
                  onPressed: _openAssignDialog,
                  icon: const Icon(Icons.person_add_outlined, size: 18),
                  label: const Text("Attribuer"),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),

                const Spacer(),
                ElevatedButton.icon(
                  onPressed: _showCreateDialog,
                  icon: const Icon(Icons.add),
                  label: const Text("Nouvelle Réparation"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MacOSTheme.accentBlue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator())
              : _reparations.isEmpty
                ? Center(child: Text("Aucune réparation trouvée", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)))
                : Column(
                    children: [
                      Expanded(child: _buildGridView()),
                      if (_isLoadingMore)
                        const Padding(
                          padding: EdgeInsets.all(8.0),
                          child: SizedBox(
                            width: 20, 
                            height: 20, 
                            child: CircularProgressIndicator(strokeWidth: 2)
                          ),
                        ),
                    ],
                  ), 
          ),
        ],
      ),
    );
  }

  Future<void> _handleDrop(Map<String, dynamic> repair, String categoryId) async {
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();

      // 1. Fetcher les statuts disponibles pour cette catégorie
      final response = await apiService.get(ApiConfig.reparationsGetStatusesEndpoint, {
        'category_id': categoryId,
        '_': DateTime.now().millisecondsSinceEpoch.toString() // Eviter cache
      });

      if (!response.containsKey('statuts')) {
        throw Exception('Format de réponse invalide');
      }

      final List<Map<String, dynamic>> statuts = List<Map<String, dynamic>>.from(response['statuts']);

      // FILTRE SPÉCIFIQUE : Retirer "Terminé" si on est dans "En attente" (Catégorie 3)
      if (categoryId == '3') {
        statuts.removeWhere((s) => s['code'] == 'termine' || s['nom'] == 'Terminé');
      }
      
      if (statuts.isEmpty) {
        // Pas de sous-statuts, update direct de la catégorie
        await _updateDirectCategory(repair['id'].toString(), categoryId);
        return;
      }

      // 2. Afficher le modal de sélection
      if (!mounted) return;
      
      String categoryName = "Inconnu";
      if (categoryId == '1') categoryName = "Nouvelle";
      if (categoryId == '2') categoryName = "En cours";
      if (categoryId == '3') categoryName = "En attente";
      if (categoryId == '4') categoryName = "Terminé";
      if (categoryId == '5') categoryName = "Annulé";

      showDialog(
        context: context,
        builder: (context) => StatusSelectionModal(
          categoryId: categoryId,
          categoryName: categoryName,
          repair: repair,
          statusOptions: statuts,
          onStatusSelected: (statusId, sendSms) async {
            Navigator.pop(context); // Fermer le modal de statut

            // --- VÉRIFICATION DU PRIX POUR STATUTS DE CLÔTURE (Terminé / Restitué) ---
            // Catégorie 4 (Terminé) ou 5 (Annulé/Restitué)
            if (categoryId == '4' || categoryId == '5') {
               double prix = 0.0;
               try {
                 prix = double.parse(repair['prix_reparation'].toString());
               } catch (e) {
                 prix = 0.0;
               }

               if (prix == 0) {
                 // Demander confirmation ou édition
                 final confirmed = await _showPriceConfirmationDialog();
                 if (!confirmed) {
                    // Ouvrir l'éditeur de prix
                    final double? newPrice = await _showPriceEditorModal();
                    if (newPrice != null) {
                       // Mettre à jour le prix
                       try {
                         await _updatePrice(repair['id'].toString(), newPrice);
                         // Mettre à jour localement le repair pour la suite (optionnel car on reload à la fin)
                         repair['prix_reparation'] = newPrice;
                       } catch (e) {
                          // Erreur update prix
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur maj prix: $e")));
                          }
                          return; // Stop flow
                       }
                    } else {
                      // Annulé l'édition -> on annule tout ou on continue avec 0 ?
                      // On annule le changement de statut ?
                      return; 
                    }
                 }
                 // Si confirmed == true (Oui le prix est correct à 0), on continue.
               }
            }

            // --- MISE À JOUR DU STATUT ---
            await _updateSpecificStatus(repair['id'].toString(), statusId, sendSms);
          },
        ),
      );

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: $e'),
          backgroundColor: MacOSTheme.dangerRed,
        ),
      );
    }
  }

  Future<void> _updateDirectCategory(String repairId, String categoryId) async {
     try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      await apiService.post(ApiConfig.reparationsUpdateStatusEndpoint, {
        'repair_id': repairId,
        'category_id': categoryId,
      });
      
      _notifySuccess();
      _loadReparations();
    } catch (e) {
      throw e;
    }
  }

  Future<void> _updateSpecificStatus(String repairId, String statusId, bool sendSms) async {
    try {
      print('FLUTTER DEBUG: Updating Repair $repairId to StatusID $statusId (SMS: $sendSms)');
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      await apiService.post(ApiConfig.reparationsUpdateSpecificStatusEndpoint, {
        'repair_id': repairId,
        'status_id': statusId,
        'send_sms': sendSms,
      });
      
      _notifySuccess();
      _loadReparations();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur lors de la mise à jour: $e'),
            backgroundColor: MacOSTheme.dangerRed,
          ),
        );
      }
    }
  }

  void _notifySuccess() {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Statut mis à jour avec succès'),
          backgroundColor: MacOSTheme.successGreen,
          duration: Duration(seconds: 2),
        ),
      );
    }
  }

  Future<bool> _showPriceConfirmationDialog() async {
    return await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1E1E1E),
        title: const Text("Vérification du prix", style: TextStyle(color: Colors.white)),
        content: const Text(
          "Le prix de cette réparation est de 0 €. Est-ce normal ?", 
          style: TextStyle(color: Colors.white70)
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false), // Non -> Editer
            child: const Text("Non, modifier", style: TextStyle(color: MacOSTheme.accentBlue)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true), // Oui -> Continuer
            style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.accentPurple),
            child: const Text("Oui, c'est correct"),
          ),
        ],
      ),
    ) ?? false;
  }

  Future<double?> _showPriceEditorModal() async {
    return await showDialog<double>(
      context: context,
      barrierDismissible: true,
      barrierColor: Colors.black.withOpacity(0.8), // Fond sombre blurré par le modal lui-même
      builder: (context) => const NumericKeyboardModal(initialValue: 0),
    );
  }

  Future<void> _updatePrice(String repairId, double price) async {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      await apiService.post(ApiConfig.reparationsUpdatePriceEndpoint, {
        'reparation_id': repairId, 
        'price': price,
      });
  }



  // ... (Keep _handleDrop etc)

  Future<void> _startRepair(Map<String, dynamic> repair) async {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();

    // 1. Check active repair
    try {
        final response = await apiService.post(ApiConfig.repairAssignmentEndpoint, {
            'action': 'check_active_repair'
        });
        
        if (response['success'] == true && response['has_active_repair'] == true) {
             final active = response['active_repair'];
             if (active != null) {
                 if (mounted) {
                     showDialog(
                         context: context,
                         builder: (ctx) => AlertDialog(
                             title: const Text("Réparation en cours"),
                             content: Text("Vous avez déjà une réparation en cours (#${active['id']}). Veuillez la terminer avant d'en commencer une nouvelle."),
                             actions: [
                                 TextButton(
                                     onPressed: () => Navigator.pop(ctx),
                                     child: const Text("OK"),
                                 )
                             ],
                         )
                     );
                 }
                 return;
             }
        }
        
        // 2. Assign repair
        if (!mounted) return;
        bool confirm = await showDialog(
            context: context,
            builder: (ctx) => AlertDialog(
                title: const Text("Démarrer la réparation"),
                content: Text("Voulez-vous commencer la réparation #${repair['id']} ?"),
                actions: [
                    TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.successGreen),
                      onPressed: () => Navigator.pop(ctx, true), 
                      child: const Text("Démarrer")
                    ),
                ]
            )
        ) ?? false;
        
        if (!confirm) return;

        final assignResponse = await apiService.post(ApiConfig.repairAssignmentEndpoint, {
            'action': 'assign_repair',
            'reparation_id': repair['id']
        });
        
        if (assignResponse['success'] == true) {
            if (mounted) {
                 ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Réparation démarrée avec succès'), backgroundColor: MacOSTheme.successGreen),
                 );
                 _loadReparations();
            }
        } else {
             throw Exception(assignResponse['message'] ?? "Erreur lors de l'attribution");
        }

    } catch (e) {
        if (mounted) {
             ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
             );
        }
    }
  }

  Future<void> _stopRepair(Map<String, dynamic> repair) async {
       final authService = context.read<AuthService>();
       final apiService = authService.getApiService();

       if (!mounted) return;
       bool confirm = await showDialog(
            context: context,
            builder: (ctx) => AlertDialog(
                title: const Text("Terminer la réparation"),
                content: const Text("Voulez-vous terminer l'intervention sur cette réparation ?"),
                actions: [
                    TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.dangerRed),
                      onPressed: () => Navigator.pop(ctx, true), 
                      child: const Text("Terminer")
                    ),
                ]
            )
        ) ?? false;

        if (!confirm) return;

        try {
            final response = await apiService.post(ApiConfig.repairAssignmentEndpoint, {
                'action': 'complete_active_repair',
                'reparation_id': repair['id']
            });

            if (response['success'] == true) {
                 if (mounted) {
                     ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Intervention terminée'), backgroundColor: MacOSTheme.successGreen),
                     );
                     _loadReparations();
                 }
            } else {
                 throw Exception(response['message'] ?? "Erreur lors de la fin d'intervention");
            }
        } catch (e) {
             if (mounted) {
                 ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
                 );
             }
        }
  }



  // ...

  Widget _buildGridView() {
    return GridView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(24),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 420,
        childAspectRatio: 1.0, 
        crossAxisSpacing: 20,
        mainAxisSpacing: 20,
      ),
      itemCount: _reparations.length,
      itemBuilder: (context, index) {
        final reparation = _reparations[index];
        return _buildRepairCard(reparation);
      },
    );
  }

  Widget _buildRepairCard(Map<String, dynamic> r) {
    final authService = context.read<AuthService>();
    final apiService = authService.getApiService();
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final cardWidget = MacOSCard(
      padding: EdgeInsets.zero,
      child: Column(
        children: [
          // Header with Status and ID
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: isDark ? Colors.white.withOpacity(0.03) : Colors.grey.withOpacity(0.03),
              border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                StatusBadge(status: r['statut'] ?? 'inconnu'),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: MacOSTheme.accentBlue.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    "#${r['id']}",
                    style: const TextStyle(
                      color: MacOSTheme.accentBlue,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Body
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                   // Client Name
                   Text(
                     '${r['client_nom'] ?? ''} ${r['client_prenom'] ?? ''}'.trim().isEmpty
                         ? 'Client inconnu'
                         : '${r['client_nom'] ?? ''} ${r['client_prenom'] ?? ''}',
                     style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                     overflow: TextOverflow.ellipsis,
                   ),
                   const SizedBox(height: 6),
                   // Phone
                   Row(
                     children: [
                       Icon(Icons.phone, size: 14, color: Theme.of(context).textTheme.bodySmall?.color),
                       const SizedBox(width: 6),
                       Text(
                         r['client_telephone'] ?? 'N/A', 
                         style: Theme.of(context).textTheme.bodySmall,
                       ),
                     ],
                   ),
                   
                   const SizedBox(height: 12),
                   
                   // Device Info Card
                   Expanded(
                     child: Container(
                       width: double.infinity,
                       padding: const EdgeInsets.all(12),
                       decoration: BoxDecoration(
                         color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                         borderRadius: BorderRadius.circular(10),
                         border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.5)),
                       ),
                       child: Column(
                         crossAxisAlignment: CrossAxisAlignment.start,
                         children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: MacOSTheme.accentBlue.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: const Icon(Icons.smartphone, color: MacOSTheme.accentBlue, size: 16),
                                ),
                                const SizedBox(width: 10),
                                Expanded(child: Text(
                                  '${r['marque'] ?? ''} ${r['modele'] ?? ''}'.trim().isEmpty
                                      ? 'Appareil Inconnu'
                                      : '${r['marque'] ?? ''} ${r['modele'] ?? ''}',
                                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                                  overflow: TextOverflow.ellipsis,
                                )), 
                              ]
                            ),
                           if ((r['description_probleme'] ?? '').isNotEmpty) ...[
                             const SizedBox(height: 8),
                             Expanded(
                               child: Text(
                                 r['description_probleme'] ?? '',
                                 style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 12),
                                 maxLines: 3,
                                 overflow: TextOverflow.ellipsis,
                               ),
                             ),
                           ],
                         ],
                       ),
                     ),
                   ),
                ],
              ),
            ),
          ),
          
          // Action Buttons Row
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: isDark ? Colors.white.withOpacity(0.02) : Colors.grey.withOpacity(0.02),
              border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
            ),
            child: Row(
              children: [
                // Date
                Text(
                  r['date_creation'] != null 
                      ? DateFormat('dd/MM/yy').format(DateTime.parse(r['date_creation'])) 
                      : '-',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11),
                ),
                
                const Spacer(),
                
                // SMS Button
                _ActionButton(
                  icon: Icons.sms,
                  label: 'SMS',
                  color: MacOSTheme.accentPurple,
                  onTap: () {
                    // Show SMS dialog
                    showDialog(
                      context: context,
                      builder: (ctx) => RepairDetailModal(
                        repair: r,
                        apiService: apiService,
                        onUpdate: () => _loadReparations(),
                      ),
                    );
                  },
                ),
                
                const SizedBox(width: 8),
                
                // Start/Stop Button logic
                Builder(
                  builder: (context) {
                    final currentUser = authService.currentUser;
                    final isMyActiveRepair = r['employe_id'].toString() == currentUser?.id.toString() && 
                                           (r['statut'] == 'en_cours_intervention' || r['statut'] == 'en_cours_diagnostique');
                    
                    if (isMyActiveRepair) {
                      return _ActionButton(
                        icon: Icons.stop_circle,
                        label: 'Arrêter',
                        color: MacOSTheme.dangerRed,
                        onTap: () => _stopRepair(r),
                      );
                    } else {
                      // Only show start if not finished/archived
                      final isFinished = ['termine', 'restitue', 'abandonne', 'annule', 'archive'].contains(r['statut']);
                      if (!isFinished) {
                        return _ActionButton(
                          icon: Icons.play_arrow,
                          label: 'Démarrer',
                          color: MacOSTheme.successGreen,
                          onTap: () => _startRepair(r),
                        );
                      }
                      return const SizedBox.shrink();
                    }
                  }
                ),
              ],
            ),
          ),
          
          // Price Footer
          Container(
             padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
             decoration: BoxDecoration(
               color: isDark ? MacOSTheme.successGreen.withOpacity(0.1) : MacOSTheme.successGreen.withOpacity(0.05),
               borderRadius: const BorderRadius.only(
                 bottomLeft: Radius.circular(16),
                 bottomRight: Radius.circular(16),
               ),
             ),
             child: Row(
               mainAxisAlignment: MainAxisAlignment.spaceBetween,
               children: [
                 const Text("Prix", style: TextStyle(fontSize: 12)),
                 Text(
                   "${r['prix_final'] ?? r['prix'] ?? '0.00'} €",
                   style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: MacOSTheme.successGreen),
                 ),
               ],
             ),
          ),
        ],
      ),
    );

    // Wrapper avec drag & drop et tap
    return GestureDetector(
      onTap: () => _showRepairDetail(r),
      child: Draggable<Map<String, dynamic>>(
        data: r,
        onDragStarted: () {
          setState(() {
            _isDragging = true;
            _draggedRepair = r;
          });
        },
        onDragEnd: (details) {
          setState(() {
            _isDragging = false;
            _draggedRepair = null;
          });
        },
        feedback: Material(
          elevation: 8,
          borderRadius: BorderRadius.circular(16),
          child: Opacity(
            opacity: 0.8,
            child: SizedBox(
              width: 350,
              height: 250,
              child: cardWidget,
            ),
          ),
        ),
        childWhenDragging: Opacity(
          opacity: 0.3,
          child: cardWidget,
        ),
        child: cardWidget,
      ),
    );
  }
}

/// Petit bouton d'action pour les cartes
class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withOpacity(0.1),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: color),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
