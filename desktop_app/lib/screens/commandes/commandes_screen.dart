import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../widgets/app_shell.dart';
import '../../widgets/commandes_filter_bar.dart';
import 'command_detail_dialog.dart';

class CommandesScreen extends StatefulWidget {
  const CommandesScreen({super.key});

  @override
  State<CommandesScreen> createState() => _CommandesScreenState();
}

class _CommandesScreenState extends State<CommandesScreen> {
  final TextEditingController _searchController = TextEditingController();
  List<Map<String, dynamic>> _commandes = [];
  List<Map<String, dynamic>> _filteredCommandes = [];
  bool _isLoading = true;
  String? _error;
  String _selectedStatus = 'all';
  Set<String> _selectedIds = {};

  @override
  void initState() {
    super.initState();
    _loadCommandes();
  }

  Future<void> _loadCommandes({String? search}) async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      String url = '${ApiConfig.commandesListEndpoint}?limit=50'; // Assuming limit parameter exists
      if (search != null && search.isNotEmpty) url += '&search=$search';

      // The API might return a list directly or an object. 
      // Based on typical PHP API pattern here, it often returns { "status": "success", "data": [...] } or just [...]
      // But looking at DevisScreen, it expected response['devis'].
      // I'll assume response['data'] or response['commandes'] or just the list if it's a list.
      // Let's print the response to debug if needed, but for now assuming typical structure.
      final response = await apiService.get(url);

      if (mounted) {
        setState(() {
          // Flexible parsing
          if (response['commandes'] != null) {
             _commandes = List<Map<String, dynamic>>.from(response['commandes']);
          } else if (response['data'] != null) {
             _commandes = List<Map<String, dynamic>>.from(response['data']);
          } else {
             _commandes = [];
          }
          
          _isLoading = false;
          _error = null;
          _updateStatusFilter(_selectedStatus);
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _isLoading = false; });
    }
  }

  void _updateStatusFilter(String filterCode) {
    setState(() {
      _selectedStatus = filterCode;
      if (filterCode == 'all') {
        _filteredCommandes = List.from(_commandes);
      } else {
        _filteredCommandes = _commandes.where((c) => c['statut'] == filterCode).toList();
      }
    });
  }

  Map<String, int> _calculateCounts() {
    var counts = <String, int>{
      'all': _commandes.length,
      'en_attente': 0,
      'commande': 0,
      'recue': 0,
      'utilise': 0,
      'a_retourner': 0,
    };

    for (var c in _commandes) {
      var s = c['statut'];
      if (counts.containsKey(s)) {
        counts[s] = (counts[s] ?? 0) + 1;
      }
    }
    return counts;
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/commandes',
      content: Column(
        children: [
          // Filter Bar
          CommandesFilterBar(
            selectedFilter: _selectedStatus,
            onFilterSelected: _updateStatusFilter,
            counts: _calculateCounts(),
          ),

          // Toolbar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            child: Row(
              children: [
                SizedBox(
                  width: 300,
                  child: TextField(
                    controller: _searchController,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'Rechercher une commande...',
                      hintStyle: TextStyle(color: Colors.grey[400]),
                      prefixIcon: const Icon(Icons.search, color: Colors.grey),
                      filled: true,
                      fillColor: const Color(0xFF1E293B),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                    ),
                    onSubmitted: (v) => _loadCommandes(search: v),
                  ),
                ),
                
                if (_selectedIds.isNotEmpty) ...[
                   const SizedBox(width: 16),
                   ElevatedButton.icon(
                     onPressed: _openBatchStatusDialog,
                     icon: const Icon(Icons.update),
                     label: Text("Modifier Statut (${_selectedIds.length})"),
                     style: ElevatedButton.styleFrom(
                       backgroundColor: Colors.orange,
                       foregroundColor: Colors.white,
                       padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                     ),
                   ),
                ],

                const Spacer(),
                ElevatedButton.icon(
                  onPressed: () {}, // TODO: Create Command Dialog
                  icon: const Icon(Icons.add_shopping_cart),
                  label: const Text("NOUVELLE COMMANDE"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blueAccent,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],
            ),
          ),

          // Table Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            color: const Color(0xFF0F172A),
            child: Row(
              children: [
                SizedBox(
                  width: 40, 
                  child: Checkbox(
                    value: _filteredCommandes.isNotEmpty && _selectedIds.containsAll(_filteredCommandes.map((e) => e['id'].toString())),
                    onChanged: (v) {
                       setState(() {
                         if (v == true) {
                           _selectedIds.addAll(_filteredCommandes.map((e) => e['id'].toString()));
                         } else {
                           _selectedIds.removeAll(_filteredCommandes.map((e) => e['id'].toString()));
                         }
                       });
                    },
                    side: const BorderSide(color: Colors.white),
                  )
                ),
                const Expanded(flex: 3, child: Text("CLIENT", style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 80, child: Text("DATE", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 120, child: Text("FOURNISSEUR", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const Expanded(flex: 3, child: Text("PIÈCE", style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 60, child: Text("QTÉ", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 80, child: Text("PRIX", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 120, child: Text("STATUT", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
                const SizedBox(width: 100, child: Text("ACTIONS", textAlign: TextAlign.center, style: TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12))),
              ],
            ),
          ),

          // List
          Expanded(
             child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _filteredCommandes.isEmpty
                    ? const Center(child: Text("Aucune commande trouvée", style: TextStyle(color: Colors.grey)))
                    : ListView.builder(
                        itemCount: _filteredCommandes.length,
                        itemBuilder: (context, index) {
                          final item = _filteredCommandes[index];
                          final bool isEven = index % 2 == 0;
                          return _buildCommandRow(item, isEven);
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildCommandRow(Map<String, dynamic> c, bool isEven) {
    final isSelected = _selectedIds.contains(c['id'].toString());
    
    return InkWell(
      onTap: () {
        final authService = context.read<AuthService>();
        showDialog(
          context: context,
          builder: (_) => CommandDetailDialog(
            command: c,
            apiService: authService.getApiService(),
            onUpdate: _loadCommandes,
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: isSelected ? Colors.blue.withOpacity(0.05) : (isEven ? Colors.white : const Color(0xFFF8FAFC)),
          border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        child: Row(
          children: [
            // Checkbox
            SizedBox(
              width: 40,
              child: Checkbox(
                value: isSelected,
                onChanged: (v) {
                  setState(() {
                    if (v == true) {
                      _selectedIds.add(c['id'].toString());
                    } else {
                      _selectedIds.remove(c['id'].toString());
                    }
                  });
                },
              ),
            ),
            
            // Client
            Expanded(
              flex: 3,
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: const Color(0xFF667eea),
                    child: const Icon(Icons.person, color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${c['client_prenom'] ?? ''} ${c['client_nom'] ?? ''}',
                          style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (c['type_appareil'] != null)
                          Text(
                            '${c['type_appareil']} ${c['modele'] ?? ''}',
                            style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                            overflow: TextOverflow.ellipsis,
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Date
            SizedBox(
              width: 80,
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                       BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4),
                    ],
                  ),
                  child: Text(
                    _formatDate(c['date_creation']),
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.black87),
                  ),
                ),
              ),
            ),

            // Fournisseur
            SizedBox(
              width: 120,
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getHashColor(c['fournisseur_nom'] ?? ''),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    (c['fournisseur_nom'] ?? 'Inconnu').toString().toUpperCase(),
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
            ),

            // Piece
            Expanded(
              flex: 3,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0),
                child: Text(
                  c['nom_piece'] ?? 'Sans nom',
                  style: const TextStyle(color: Color(0xFF495057), decoration: TextDecoration.underline, decorationStyle: TextDecorationStyle.dotted),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),

            // Qty
            SizedBox(
              width: 60,
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${c['quantite'] ?? 1}',
                    style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black87, fontSize: 12),
                  ),
                ),
              ),
            ),

            // Price
            SizedBox(
              width: 80,
              child: Center(
                child: Text(
                  '${c['prix_estime'] ?? '0.00'} €',
                  style: const TextStyle(color: Color(0xFF198754), fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ),
            ),

            // Status (Clickable)
            SizedBox(
              width: 120,
              child: InkWell(
                onTap: () => _showStatusDialog(c),
                child: Center(
                  child: _buildStatusBadge(c['statut']),
                ),
              ),
            ),

            // Actions (Google Button Only)
            SizedBox(
              width: 100,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    icon: const Icon(Icons.public, size: 20),
                    color: Colors.blue,
                    tooltip: 'Recherche Google',
                    onPressed: () {
                      final query = '${c['type_appareil'] ?? ''} ${c['modele'] ?? ''} ${c['nom_piece'] ?? ''}';
                      launchUrl(Uri.parse('https://www.google.com/search?q=${Uri.encodeComponent(query)}'));
                    },
                    constraints: const BoxConstraints(),
                    padding: const EdgeInsets.all(8),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showStatusDialog(Map<String, dynamic> command) {
     showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text("Changer statut"),
          content: Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildStatusOption(command, 'en_attente', 'En attente', Colors.orange),
              _buildStatusOption(command, 'commande', 'Commandé', Colors.cyan),
              _buildStatusOption(command, 'recue', 'Reçu', Colors.green),
              _buildStatusOption(command, 'utilise', 'Utilisé', Colors.indigo),
              _buildStatusOption(command, 'a_retourner', 'À retourner', Colors.grey),
              _buildStatusOption(command, 'annulee', 'Annulé', Colors.red),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Annuler"),
            )
          ],
        );
      }
    );
  }

  Future<void> _openBatchStatusDialog() async {
     showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text("Modifier le statut de ${_selectedIds.length} commandes"),
          content: Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
               _buildBatchStatusOption('en_attente', 'En attente', Colors.orange),
               _buildBatchStatusOption('commande', 'Commandé', Colors.cyan),
               _buildBatchStatusOption('recue', 'Reçu', Colors.green),
               _buildBatchStatusOption('utilise', 'Utilisé', Colors.indigo),
               _buildBatchStatusOption('a_retourner', 'À retourner', Colors.grey),
               _buildBatchStatusOption('annulee', 'Annulé', Colors.red),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Annuler"),
            )
          ],
        );
      }
    );
  }

  Widget _buildBatchStatusOption(String code, String label, Color color) {
    return ActionChip(
      label: Text(label, style: const TextStyle(color: Colors.white)),
      backgroundColor: color,
      onPressed: () async {
         Navigator.pop(context);
         if (_selectedIds.isEmpty) return;
         
         // Loop update
         final authService = context.read<AuthService>();
         int success = 0;
         for (String id in _selectedIds) {
             try {
                await authService.getApiService().post(ApiConfig.commandesUpdateEndpoint, {
                  'id': id,
                  'statut': code,
                });
                success++;
             } catch (e) {
                print("Cmd $id failed: $e");
             }
         }
         
         if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(
              content: Text('$success commandes mises à jour'), 
              backgroundColor: Colors.green
            ));
            setState(() {
              _selectedIds.clear();
            });
            _loadCommandes();
         }
      },
    );
  }
  Widget _buildStatusOption(Map<String, dynamic> command, String code, String label, Color color) {
    return ActionChip(
      label: Text(label, style: const TextStyle(color: Colors.white)),
      backgroundColor: color,
      onPressed: () async {
        Navigator.pop(context);
        await _updateStatus(command['id'].toString(), code);
      },
    );
  }

  Future<void> _updateStatus(String id, String status) async {
    try {
        final authService = context.read<AuthService>();
        await authService.getApiService().post(ApiConfig.commandesUpdateEndpoint, {
          'id': id,
          'statut': status,
        });
        _loadCommandes();
        if (mounted) {
           ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Statut mis à jour'), backgroundColor: Colors.green));
        }
    } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '--/--';
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('dd/MM').format(date);
    } catch (_) {
      return '--/--';
    }
  }

  Color _getHashColor(String s) {
    if (s.isEmpty) return Colors.grey;
    final int hash = s.codeUnits.fold(0, (p, c) => p + c);
    final List<Color> colors = [
      Colors.blue, Colors.purple, Colors.orange, Colors.teal, Colors.pink, Colors.indigo
    ];
    return colors[hash % colors.length];
  }

  Widget _buildStatusBadge(String? status) {
    Color color;
    String label;
    IconData icon;

    switch (status) {
      case 'en_attente':
        color = Colors.orange;
        label = 'En attente';
        icon = Icons.hourglass_empty;
        break;
      case 'commande':
        color = Colors.cyan;
        label = 'Commandé';
        icon = Icons.local_shipping;
        break;
      case 'recue':
        color = Colors.green;
        label = 'Reçu';
        icon = Icons.check_circle;
        break;
      case 'utilise':
        color = Colors.indigo;
        label = 'Utilisé';
        icon = Icons.build;
        break;
      case 'a_retourner':
        color = Colors.grey;
        label = 'Retour';
        icon = Icons.undo;
        break;
      case 'annulee':
        color = Colors.red;
        label = 'Annulé';
        icon = Icons.cancel;
        break;
      default:
        color = Colors.grey;
        label = status ?? 'N/A';
        icon = Icons.help_outline;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
           Icon(icon, size: 12, color: color),
           const SizedBox(width: 4),
           Flexible(
             child: Text(
               label,
               style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold),
               overflow: TextOverflow.ellipsis,
             ),
           ),
        ],
      ),
    );
  }
}
