import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';
import '../screens/reparations/repair_detail_modal.dart';
import '../screens/clients/dialogs/client_history_dialog.dart';
import '../screens/commandes/command_detail_dialog.dart';

class UniversalSearchDialog extends StatefulWidget {
  final ApiService apiService;

  const UniversalSearchDialog({Key? key, required this.apiService}) : super(key: key);

  @override
  State<UniversalSearchDialog> createState() => _UniversalSearchDialogState();
}

class _UniversalSearchDialogState extends State<UniversalSearchDialog> with SingleTickerProviderStateMixin {
  final TextEditingController _searchController = TextEditingController();
  late TabController _tabController;
  
  bool _isLoading = false;
  String _errorMessage = '';
  
  List<dynamic> _reparations = [];
  List<dynamic> _clients = [];
  List<dynamic> _commandes = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _search() async {
    final term = _searchController.text.trim();
    if (term.length < 2) {
      setState(() => _errorMessage = "Minimum 2 caractères");
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final response = await widget.apiService.post(ApiConfig.universalSearchEndpoint, {'terme': term});
      
      if (mounted) {
        setState(() {
          _reparations = response['reparations'] ?? [];
          _clients = response['clients'] ?? [];
          _commandes = response['commandes'] ?? [];
          _isLoading = false;
        });

        // Switch to first tab with results
        if (_reparations.isNotEmpty) {
          _tabController.animateTo(0);
        } else if (_clients.isNotEmpty) {
          _tabController.animateTo(1);
        } else if (_commandes.isNotEmpty) {
          _tabController.animateTo(2);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _errorMessage = e.toString();
        });
      }
    }
  }

  double _getDialogHeight() {
    // Expand if loading or if we have any results in any category
    bool hasResults = _reparations.isNotEmpty || _clients.isNotEmpty || _commandes.isNotEmpty;
    return (_isLoading || hasResults) ? 800 : 340;
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 40),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
        width: 600,
        height: _getDialogHeight(),
        decoration: BoxDecoration(
          color: const Color(0xFF1C1C1E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.1)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header with gradient
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: MacOSTheme.primaryGradient,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      const Icon(Icons.search, color: Colors.white),
                      const SizedBox(width: 12),
                      const Text("Recherche Universelle", style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                      const Spacer(),
                      IconButton(
                        icon: const Icon(Icons.close, color: Colors.white),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Search input
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _searchController,
                            style: const TextStyle(color: Colors.white),
                            decoration: const InputDecoration(
                              hintText: "Client, réparation, commande...",
                              hintStyle: TextStyle(color: Colors.white60),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            ),
                            onSubmitted: (_) => _search(),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.search, color: Colors.white),
                          onPressed: _search,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            
            // Tabs
            Container(
              color: const Color(0xFF2C2C2E),
              child: TabBar(
                controller: _tabController,
                indicatorColor: MacOSTheme.accentBlue,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.grey,
                tabs: [
                  Tab(text: "Réparations (${_reparations.length})"),
                  Tab(text: "Clients (${_clients.length})"),
                  Tab(text: "Commandes (${_commandes.length})"),
                ],
              ),
            ),
            
            // Content
            Flexible(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _errorMessage.isNotEmpty
                      ? Center(child: Text(_errorMessage, style: const TextStyle(color: Colors.red)))
                      : TabBarView(
                          controller: _tabController,
                          children: [
                            _buildReparationsList(),
                            _buildClientsList(),
                            _buildCommandesList(),
                          ],
                        ),
            ),
          ],
        ),
      ),
    );
  }



  Widget _buildReparationsList() {
    if (_reparations.isEmpty) {
      return const Center(child: Text("Aucune réparation trouvée", style: TextStyle(color: Colors.grey)));
    }
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _reparations.length,
      separatorBuilder: (_, __) => const Divider(color: Colors.white10),
      itemBuilder: (ctx, i) {
        final item = _reparations[i];
        return ListTile(
          leading: const CircleAvatar(backgroundColor: MacOSTheme.accentBlue, child: Icon(Icons.build, color: Colors.white, size: 20)),
          title: Text(item['appareil'] ?? 'Appareil inconnu', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          subtitle: Text("${item['client_nom'] ?? ''} - ${item['probleme'] ?? ''}", style: const TextStyle(color: Colors.grey), maxLines: 1, overflow: TextOverflow.ellipsis),
          trailing: Text("#${item['id']}", style: const TextStyle(color: Colors.grey)),
          onTap: () {
            showDialog(
              context: context,
              builder: (_) => RepairDetailModal(
                repair: Map<String, dynamic>.from(item),
                apiService: widget.apiService,
                onUpdate: _search, // Refresh search results if repair status changes
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildClientsList() {
    if (_clients.isEmpty) {
      return const Center(child: Text("Aucun client trouvé", style: TextStyle(color: Colors.grey)));
    }
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _clients.length,
      separatorBuilder: (_, __) => const Divider(color: Colors.white10),
      itemBuilder: (ctx, i) {
        final item = _clients[i];
        return ListTile(
          leading: const CircleAvatar(backgroundColor: MacOSTheme.successGreen, child: Icon(Icons.person, color: Colors.white, size: 20)),
          title: Text("${item['nom'] ?? ''} ${item['prenom'] ?? ''}", style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          subtitle: Text("${item['telephone'] ?? ''}", style: const TextStyle(color: Colors.grey)),
          trailing: Text("#${item['id']}", style: const TextStyle(color: Colors.grey)),
          onTap: () {
            showDialog(
              context: context,
              builder: (_) => ClientHistoryDialog(
                clientId: item['id'],
                apiService: widget.apiService,
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildCommandesList() {
    if (_commandes.isEmpty) {
      return const Center(child: Text("Aucune commande trouvée", style: TextStyle(color: Colors.grey)));
    }
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _commandes.length,
      separatorBuilder: (_, __) => const Divider(color: Colors.white10),
      itemBuilder: (ctx, i) {
        final item = _commandes[i];
        return ListTile(
          leading: const CircleAvatar(backgroundColor: MacOSTheme.warningOrange, child: Icon(Icons.shopping_cart, color: Colors.white, size: 20)),
          title: Text("Commande #${item['reference'] ?? item['id']}", style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          subtitle: Text("${item['client_nom'] ?? ''} - ${item['statut'] ?? ''}", style: const TextStyle(color: Colors.grey)),
          trailing: Text("#${item['id']}", style: const TextStyle(color: Colors.grey)),
          onTap: () {
            showDialog(
              context: context,
              builder: (_) => CommandDetailDialog(
                command: Map<String, dynamic>.from(item),
                apiService: widget.apiService,
                onUpdate: _search,
              ),
            );
          },
        );
      },
    );
  }
}
