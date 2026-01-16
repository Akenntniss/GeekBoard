import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/inventory_filter_bar.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';

class InventaireScreen extends StatefulWidget {
  const InventaireScreen({super.key});

  @override
  State<InventaireScreen> createState() => _InventaireScreenState();
}

class _InventaireScreenState extends State<InventaireScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  // Data
  List<Map<String, dynamic>> _items = [];
  Map<String, dynamic> _stats = {};
  
  // Pagination
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoading = true;
  
  // Filters State
  String _search = '';
  bool _lowStockOnly = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '50',
        'search': _search,
        'low_stock': _lowStockOnly ? 'true' : 'false',
      };

      final response = await _apiService.get(ApiConfig.inventoryListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          if (response['items'] != null) {
            _items = List<Map<String, dynamic>>.from(response['items']);
          } else {
             _items = [];
          }
          
          if (response['stats'] != null) {
             _stats = response['stats'];
          }
          
          if (response['pagination'] != null) {
            _totalPages = response['pagination']['totalPages'] ?? 1;
          }
        });
      }
    } catch (e) {
      print('Error loading inventory: $e');
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
    return AppShell(
      currentRoute: '/inventaire',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: Column(
          children: [
            // Header Stats
            _buildStatsHeader(),
            
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  children: [
                    // Filters
                    InventoryFilterBar(
                      onFilterChanged: (search, lowStockOnly) {
                        setState(() {
                          _search = search;
                          _lowStockOnly = lowStockOnly;
                          _currentPage = 1;
                        });
                        _loadData();
                      },
                    ),
                    
                    // Table
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withOpacity(0.1)),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.2),
                              blurRadius: 20,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: _isLoading 
                          ? const Center(child: CircularProgressIndicator()) 
                          : _items.isEmpty 
                              ? _buildEmptyState()
                              : Column(
                                  children: [
                                    _buildTableHeader(),
                                    Expanded(
                                      child: ListView.separated(
                                        padding: EdgeInsets.zero,
                                        itemCount: _items.length,
                                        separatorBuilder: (ctx, i) => Divider(
                                          height: 1, 
                                          thickness: 1, 
                                          color: Colors.white.withOpacity(0.05)
                                        ),
                                        itemBuilder: (context, index) {
                                          return _buildProductRow(_items[index]);
                                        },
                                      ),
                                    ),
                                    _buildPagination(),
                                  ],
                                ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatsHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
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
                        colors: [Color(0xFF3b82f6), Color(0xFF6366f1)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.inventory_2, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 16),
                  const Text(
                    'Inventaire',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      letterSpacing: -0.5,
                    ),
                  ),
                ],
              ),
              
              Row(
                children: [
                  ElevatedButton.icon(
                    onPressed: () {
                       ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Fonctionnalité Nouveau Produit à venir')),
                      );
                    },
                    icon: const Icon(Icons.add, size: 18),
                    label: const Text('Nouveau Produit'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3b82f6),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 4,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              _buildStatCard('Total Produits', _stats['total_items']?.toString() ?? '0', Icons.category, Colors.blue),
              const SizedBox(width: 16),
              _buildStatCard('Rupture Stock', _stats['out_of_stock']?.toString() ?? '0', Icons.warning, Colors.red),
              const SizedBox(width: 16),
              _buildStatCard('Stock Faible', _stats['low_stock']?.toString() ?? '0', Icons.warning_amber, Colors.orange),
              const SizedBox(width: 16),
              _buildStatCard(
                'Valeur Totale', 
                '${((_stats['total_value'] ?? 0) as num).toStringAsFixed(2)} €', 
                Icons.euro, 
                Colors.green,
                isWide: true,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color, {bool isWide = false}) {
    return Expanded(
      flex: isWide ? 2 : 1,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.1)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[400],
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

  Widget _buildTableHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A).withOpacity(0.5),
        border: Border(bottom: BorderSide(color: Colors.white.withOpacity(0.1))),
      ),
      child: Row(
        children: [
          Expanded(flex: 3, child: _buildHeaderCell('PRODUIT')),
          Expanded(flex: 2, child: _buildHeaderCell('RÉFÉRENCE')),
          Expanded(flex: 2, child: _buildHeaderCell('FOURNISSEUR')),
          Expanded(flex: 1, child: _buildHeaderCell('STOCK')),
          Expanded(flex: 1, child: _buildHeaderCell('PRIX')),
          const SizedBox(width: 50), // Actions
        ],
      ),
    );
  }

  Widget _buildHeaderCell(String text) {
    return Text(
      text,
      style: TextStyle(
        color: Colors.grey[500],
        fontSize: 11,
        fontWeight: FontWeight.bold,
        letterSpacing: 1,
      ),
    );
  }

  Widget _buildProductRow(Map<String, dynamic> item) {
    final stockStatus = item['stock_status']; // ok, low, out
    Color stockColor = const Color(0xFF10B981); // Green
    if (stockStatus == 'low') stockColor = const Color(0xFFF59E0B);
    if (stockStatus == 'out') stockColor = const Color(0xFFEF4444);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          // Product Name
          Expanded(
            flex: 3,
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: const Color(0xFF334155),
                    borderRadius: BorderRadius.circular(8),
                    image: item['photo'] != null && item['photo'].toString().isNotEmpty
                      ? DecorationImage(
                          image: NetworkImage(item['photo']), // Assuming absolute URL or handled by dedicated loader
                          fit: BoxFit.cover,
                        )
                      : null,
                  ),
                  child: item['photo'] == null || item['photo'].toString().isEmpty
                      ? const Icon(Icons.image_not_supported, size: 16, color: Colors.grey)
                      : null,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    item['nom'] ?? 'Sans nom',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                    maxLines: 2, overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
          
          // Reference
          Expanded(
            flex: 2,
            child: Text(
              item['reference'] ?? '-',
              style: TextStyle(color: Colors.grey[400], fontSize: 12),
            ),
          ),
          
          // Supplier
          Expanded(
            flex: 2,
            child: Text(
              item['fournisseur_nom'] ?? '-',
              style: TextStyle(color: Colors.grey[400], fontSize: 13),
            ),
          ),
          
          // Stock
          Expanded(
            flex: 1,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: stockColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(6),
                border: Border.all(color: stockColor.withOpacity(0.2)),
              ),
              child: Text(
                '${item['quantite']} un.',
                textAlign: TextAlign.center,
                style: TextStyle(color: stockColor, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ),
          ),
          
          // Price
          Expanded(
            flex: 1,
            child: Text(
              item['prix_vente_formatted'] ?? '0.00 €',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
          ),
          
          // Actions
          SizedBox(
            width: 50,
            child: IconButton(
              icon: const Icon(Icons.edit, color: Colors.blue, size: 20),
              tooltip: 'Modifier',
              onPressed: () {
                 // Future: Edit logic
                 ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Modification à venir')),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey[700]),
          const SizedBox(height: 16),
          Text(
            'Aucun produit trouvé',
            style: TextStyle(color: Colors.grey[400], fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A).withOpacity(0.3),
        border: Border(top: BorderSide(color: Colors.white.withOpacity(0.1))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left, color: Colors.white),
            onPressed: _currentPage > 1 ? () {
              setState(() => _currentPage--);
              _loadData();
            } : null,
          ),
          const SizedBox(width: 16),
          Text(
            'Page $_currentPage / $_totalPages',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 16),
          IconButton(
            icon: const Icon(Icons.chevron_right, color: Colors.white),
            onPressed: _currentPage < _totalPages ? () {
              setState(() => _currentPage++);
              _loadData();
            } : null,
          ),
        ],
      ),
    );
  }
}
