import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../widgets/app_shell.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/devis_filter_bar.dart';

import '../../widgets/quote_detail_modal.dart';
import '../../widgets/quote_batch_send_modal.dart';

class DevisScreen extends StatefulWidget {
  const DevisScreen({super.key});
  @override
  State<DevisScreen> createState() => _DevisScreenState();
}

class _DevisScreenState extends State<DevisScreen> {
  final TextEditingController _searchController = TextEditingController();
  List<Map<String, dynamic>> _devis = [];
  List<Map<String, dynamic>> _filteredDevis = [];
  bool _isLoading = true;
  String? _error;
  String _selectedStatus = 'all';
  bool _isDragging = false; // To show drop zones

  @override
  void initState() {
    super.initState();
    _loadDevis();
  }

  Future<void> _loadDevis({String? search}) async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      String url = '${ApiConfig.devisListEndpoint}?limit=50';
      if (search != null && search.isNotEmpty) url += '&search=$search';

      final response = await apiService.get(url);

      if (mounted) {
        setState(() {
          _devis = List<Map<String, dynamic>>.from(response['devis'] ?? []);
          _isLoading = false;
          _error = null;
          _updateStatusFilter(_selectedStatus);
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _isLoading = false; });
    }
  }
  
  // _resendAllQuotes moved to near usage site or replaced.
  // Keeping this empty or removing it.
  // Actually, I should have targeted the original _resendAllQuotes. 
  // Let me just delete the original block lines 56-89.

  Future<void> _updateQuoteStatus(int devisId, String newStatus) async {
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final shopId = authService.currentUser?.shopId;
      
      await apiService.post(ApiConfig.devisUpdateStatusEndpoint, {
        'devis_id': devisId,
        'status': newStatus,
        if (shopId != null) 'shop_id': shopId,
      });
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text("Statut mis à jour : ${_getStatutLabel(newStatus)}"), 
          backgroundColor: Colors.green
        ));
        _loadDevis();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e"), backgroundColor: Colors.red));
    }
  }

  void _updateStatusFilter(String filterCode) {
    setState(() {
      _selectedStatus = filterCode;
      if (filterCode == 'all') {
        _filteredDevis = List.from(_devis);
      } else {
        _filteredDevis = _devis.where((d) => d['statut'] == filterCode).toList();
      }
    });
  }

  Map<String, int> _calculateCounts() {
    return {
      'envoye': _devis.where((d) => d['statut'] == 'envoye').length,
      'accepte': _devis.where((d) => d['statut'] == 'accepte').length,
      'refuse': _devis.where((d) => d['statut'] == 'refuse').length,
      'expire': _devis.where((d) => d['statut'] == 'expire').length,
      'all': _devis.length,
    };
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/devis',
      content: Column(
          children: [
            // Filtres
            DevisFilterBar(
              selectedFilter: _selectedStatus,
              onFilterSelected: _updateStatusFilter,
              counts: _calculateCounts(),
              onStatusDropped: (devisId, newStatus) {
                // Confirm before changing status via drag
                _updateQuoteStatus(devisId, newStatus);
              },
            ),

            // Barre d'outils
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              child: Row(
                children: [
                  SizedBox(
                    width: 300,
                    child: TextField(
                      controller: _searchController,
                      decoration: InputDecoration(
                        hintText: 'Rechercher un devis...',
                        prefixIcon: const Icon(Icons.search),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Theme.of(context).dividerColor)),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Theme.of(context).dividerColor)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                      ),
                      onSubmitted: (v) => _loadDevis(search: v),
                    ),
                  ),
                  const SizedBox(width: 12),
                  // Renvoyer tout le monde button
                  ElevatedButton.icon(
                    onPressed: _resendAllQuotes,
                    icon: const Icon(Icons.send),
                    label: const Text("Renvoyer tout les devis"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ],
              ),
            ),

            // Grille
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _filteredDevis.isEmpty
                      ? Center(child: Text("Aucun devis trouvé", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)))
                      : GridView.builder(
                          padding: const EdgeInsets.all(24),
                          gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                            maxCrossAxisExtent: 400,
                            childAspectRatio: 0.85,
                            crossAxisSpacing: 24,
                            mainAxisSpacing: 24,
                          ),
                          itemCount: _filteredDevis.length,
                          itemBuilder: (context, index) => _buildDevisCard(_filteredDevis[index]),
                        ),
            ),
          ],
      ),
    );
  }

  // Old overlay widgets removed.


  Widget _buildDevisCard(Map<String, dynamic> d) {
    final card = _buildCardContent(d);
    
    // Only draggble if status is 'envoye' or 'brouillon' (i.e. not final)
    // Assuming 'accepte' and 'refuse' are final states logic wise, but usually we can re-drag?
    // User said: "pour quand je le met dans devis accepte ou devis refuse", implying moving TO these states.
    // If already accepted, maybe we don't drag? Let's allow drag for all for flexibility unless restricted.
    
    return Draggable<int>(
      data: int.tryParse(d['id'].toString()) ?? 0,
      feedback: Material(
        color: Colors.transparent,
        child: SizedBox(
          width: 350,
          height: 200,
          child: Opacity(opacity: 0.9, child: Card(child: _buildCardContent(d))),
        ),
      ),
      childWhenDragging: Opacity(opacity: 0.3, child: card),
      onDragStarted: () => setState(() => _isDragging = true),
      onDragEnd: (_) => setState(() => _isDragging = false),
      child: card,
    );
  }

  Future<void> _resendAllQuotes() async {
    final authService = context.read<AuthService>();
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => QuoteBatchSendModal(
        apiService: authService.getApiService(),
        shopId: authService.currentUser?.shopId,
      ),
    );

    if (result == true) {
      _loadDevis();
    }
  }

  String _getStatutLabel(String? status) {
    switch (status) {
      case 'envoye': return 'En Attente';
      case 'accepte': return 'Accepté';
      case 'refuse': return 'Refusé';
      case 'brouillon': return 'Brouillon';
      case 'expire': return 'Expiré';
      default: return status ?? 'Inconnu';
    }
  }

  Widget _buildCardContent(Map<String, dynamic> d) {
    // Statut mapping for color
    Color statusColor;
    switch(d['statut']) {
      case 'accepte': statusColor = MacOSTheme.successGreen; break;
      case 'refuse': statusColor = MacOSTheme.dangerRed; break;
      case 'envoye': statusColor = MacOSTheme.warningOrange; break;
      case 'expire': statusColor = MacOSTheme.textSecondary; break;
      default: statusColor = MacOSTheme.accentBlue;
    }

    return MacOSCard(
      padding: EdgeInsets.zero,
      onTap: () {
        showDialog(
          context: context,
          builder: (ctx) => QuoteDetailModal(
            devisId: int.tryParse(d['id'].toString()) ?? 0,
            onUpdate: _loadDevis,
          ),
        );
      },
      child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    "DEVIS #${d['numero'] ?? d['id']}",
                    style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).textTheme.bodyLarge?.color),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      d['statut']?.toString().toUpperCase() ?? 'N/A',
                      style: TextStyle(color: statusColor, fontSize: 10, fontWeight: FontWeight.bold),
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
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 16,
                          backgroundColor: MacOSTheme.accentBlue.withOpacity(0.2),
                          child: Text(
                            (d['client_prenom'] ?? 'C').toString().substring(0, 1).toUpperCase(),
                            style: const TextStyle(color: MacOSTheme.accentBlue, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${d['client_prenom'] ?? ''} ${d['client_nom'] ?? ''}',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                overflow: TextOverflow.ellipsis,
                              ),
                              Text(
                                DateFormat('dd/MM/yyyy').format(DateTime.parse(d['date_creation'] ?? DateTime.now().toIso8601String())),
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),
                   Container(
                       padding: const EdgeInsets.all(12),
                       width: double.infinity,
                       decoration: BoxDecoration(
                         color: Theme.of(context).dividerColor.withOpacity(0.1),
                         borderRadius: BorderRadius.circular(8),
                       ),
                       child: Column(
                         crossAxisAlignment: CrossAxisAlignment.start,
                         children: [
                           Text(
                             d['titre'] ?? 'Appareil inconnu',
                             style: const TextStyle(fontWeight: FontWeight.w600),
                           ),
                           if (d['description_generale'] != null && d['description_generale'].toString().isNotEmpty)
                             Text(
                               d['description_generale'] ?? '',
                               style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11),
                               maxLines: 1, 
                               overflow: TextOverflow.ellipsis,
                             ),
                         ],
                       ),
                    ),
                    const Spacer(),
                     Align(
                      alignment: Alignment.centerRight,
                      child: Text(
                        '${double.tryParse(d['total_ht'].toString())?.toStringAsFixed(2) ?? '0.00'} € HT',
                        style: const TextStyle(
                          color: MacOSTheme.successGreen,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            
            // Footer Actions
             Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  IconButton(icon: const Icon(Icons.visibility), color: MacOSTheme.accentBlue, iconSize: 20, onPressed: (){}),
                  IconButton(icon: const Icon(Icons.edit), color: MacOSTheme.warningOrange, iconSize: 20, onPressed: (){}),
                  IconButton(icon: const Icon(Icons.print), color: MacOSTheme.textSecondary, iconSize: 20, onPressed: (){}),
                ],
              ),
            ),
          ],
        ),
    );
  }
}
